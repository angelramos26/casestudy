<?php
require_once 'database.php';
require_once 'pusher-broadcast.php';
require_once __DIR__ . '/csrf.php';
session_start();
csrf_verify();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }

// Helper: handle product image upload
function handleProductImage($fileKey, $existingImage = null) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
        return $existingImage; // keep existing
    }
    if ($_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) return $existingImage;

    $allowedTypes = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($_FILES[$fileKey]['type'], $allowedTypes)) return $existingImage;
    if ($_FILES[$fileKey]['size'] > 2 * 1024 * 1024) return $existingImage; // 2MB limit

    $uploadDir = __DIR__ . '/../uploads/products/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // Delete old image if replacing
    if ($existingImage && file_exists(__DIR__ . '/../' . $existingImage)) {
        @unlink(__DIR__ . '/../' . $existingImage);
    }

    $ext = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
    $filename = 'prod_' . uniqid() . '.' . strtolower($ext);
    move_uploaded_file($_FILES[$fileKey]['tmp_name'], $uploadDir . $filename);
    return 'uploads/products/' . $filename;
}

if(isset($_POST['productSave'])){
    $name   = sanitize($_POST['productName']);
    $barcode= sanitize($_POST['barcode']??'');
    $catID  = intval($_POST['categoryID']);
    $price  = floatval($_POST['price']);
    $cost   = floatval($_POST['cost']);
    $qty    = intval($_POST['stock_quantity']??0);
    $reorder= intval($_POST['reorder_level']??10);
    $expiry = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $status = sanitize($_POST['status']);
    if(!$name || !$catID || !$price){ header("Location: ../frontend/product.php?emptyFields"); exit(); }
    $stmt = $conn->prepare("CALL AddProduct(?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssiiddiss", $name, $barcode, $catID, $price, $cost, $qty, $reorder, $expiry, $status);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if($r['result'] === 'duplicate_barcode'){ header("Location: ../frontend/product.php?barcodeExists"); exit(); }
    // Free remaining result sets (prevents "Commands out of sync")
    while($stmt->more_results() && $stmt->next_result()) { if($s = $stmt->get_result()) $s->free(); }
    $stmt->close();
    while($conn->more_results()) { $conn->next_result(); }
    // Get the new productID and update image
    $newID = $conn->insert_id;
    if($newID == 0){
        // fallback: get last inserted
        $res = $conn->query("SELECT productID FROM product ORDER BY productID DESC LIMIT 1");
        $newID = $res->fetch_assoc()['productID'];
    }
    $imagePath = handleProductImage('product_image');
    if($imagePath){
        $stmt2 = $conn->prepare("UPDATE product SET product_image=? WHERE productID=?");
        $stmt2->bind_param("si", $imagePath, $newID);
        $stmt2->execute();
    }
    pusherBroadcast('product-changed', ['action'=>'added','productName'=>$name,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/product.php?savedData"); exit();
}

if(isset($_POST['productUpdate'])){
    $id     = intval($_POST['productID']);
    $name   = sanitize($_POST['productName']);
    $barcode= sanitize($_POST['barcode']??'');
    $catID  = intval($_POST['categoryID']);
    $price  = floatval($_POST['price']);
    $cost   = floatval($_POST['cost']);
    $reorder= intval($_POST['reorder_level']??10);
    $expiry = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $status = sanitize($_POST['status']);
    $stmt = $conn->prepare("CALL UpdateProduct(?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("issiiddss", $id, $name, $barcode, $catID, $price, $cost, $reorder, $expiry, $status);
    $stmt->execute();
    // Free all result sets left open by the stored procedure (prevents "Commands out of sync")
    do { if($r = $stmt->get_result()) $r->free(); } while($stmt->more_results() && $stmt->next_result());
    $stmt->close();
    while($conn->more_results()) { $conn->next_result(); }
    // Handle image upload — only update if a new file was actually uploaded
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $res = $conn->prepare("SELECT product_image FROM product WHERE productID=?");
        $res->bind_param("i",$id); $res->execute();
        $existing = $res->get_result()->fetch_assoc()['product_image'] ?? null;
        $imagePath = handleProductImage('product_image', $existing);
        $stmt2 = $conn->prepare("UPDATE product SET product_image=? WHERE productID=?");
        $stmt2->bind_param("si", $imagePath, $id);
        $stmt2->execute();
    }
    pusherBroadcast('product-changed', ['action'=>'updated','productID'=>$id,'productName'=>$name,'price'=>$price,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/product.php?updatedProduct"); exit();
}

if(isset($_POST['productReactivate'])){
    $id = intval($_POST['productID']);
    $stmt = $conn->prepare("UPDATE product SET status='Active' WHERE productID=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    pusherBroadcast('product-changed', ['action'=>'reactivated','productID'=>$id,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/product.php?productReactivated"); exit();
}

if(isset($_POST['productDelete'])){
    $id = intval($_POST['productID']);
    $stmt = $conn->prepare("CALL DeleteProduct(?)");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    pusherBroadcast('product-changed', ['action'=>'deactivated','productID'=>$id,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/product.php?productDeleted"); exit();
}
?>