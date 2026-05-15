<?php
require_once 'database.php';
require_once 'pusher-broadcast.php';
require_once __DIR__ . '/csrf.php';
session_start();
csrf_verify();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if(!in_array($_SESSION['roleName'], ['Admin','Owner'])){ header("Location: ../frontend/dashboard.php"); exit(); }

$userID = $_SESSION['userID'];

if(isset($_POST['createPO'])){
    $supplierID = intval($_POST['supplierID']);
    $notes      = sanitize($_POST['notes'] ?? '');
    if(!$supplierID){ header("Location: ../frontend/purchase.php?emptyFields"); exit(); }
    $stmt = $conn->prepare("CALL CreatePurchaseOrder(?,?,?)");
    $stmt->bind_param("iis", $supplierID, $userID, $notes);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $poID = intval($r['poID']);
    $stmt->close();
    while($conn->more_results()) $conn->next_result();

    $products = $_POST['productID'] ?? [];
    $qtys     = $_POST['qty'] ?? [];
    $costs    = $_POST['unit_cost'] ?? [];
    $stmtItem = $conn->prepare("INSERT INTO purchase_order_details (poID, productID, qty_ordered, unit_cost) VALUES (?,?,?,?)");
    $itemCount = 0;
    foreach($products as $i => $pid){
        $pid  = intval($pid);
        $qty  = intval($qtys[$i] ?? 0);
        $cost = floatval($costs[$i] ?? 0);
        if($pid > 0 && $qty > 0){
            $stmtItem->bind_param("iiid", $poID, $pid, $qty, $cost);
            $stmtItem->execute();
            $itemCount++;
        }
    }
    // Get supplier name for notification
    $sq = $conn->prepare("SELECT companyName FROM supplier WHERE supplierID=?");
    $sq->bind_param("i", $supplierID); $sq->execute();
    $sr = $sq->get_result()->fetch_assoc(); $sq->close();
    pusherBroadcast('purchase-changed', [
        'action'=>'created','poID'=>$poID,
        'supplier'=>$sr['companyName']??'',
        'items'=>$itemCount,'by'=>$_SESSION['userName']??'',
    ]);
    header("Location: ../frontend/purchase.php?poCreated"); exit();
}

if(isset($_POST['receivePO'])){
    $poID  = intval($_POST['poID']);
    $items = $conn->query("SELECT pod.productID, pod.qty_ordered, pod.unit_cost, po.supplierID FROM purchase_order_details pod JOIN purchase_order po ON pod.poID=po.poID WHERE pod.poID=$poID");
    $conn->begin_transaction();
    try {
        $receivedProducts = [];
        while($item = $items->fetch_assoc()){
            $productID  = $item['productID'];
            $qty        = $item['qty_ordered'];
            $cost       = $item['unit_cost'];
            $supplierID = $item['supplierID'];
            $notes      = "PO #$poID received";
            $stmt = $conn->prepare("CALL AddStock(?,?,?,?,?,?)");
            $stmt->bind_param("iiidis", $productID, $qty, $cost, $supplierID, $userID, $notes);
            $stmt->execute();
            $stmt->get_result();
            $stmt->close();
            while($conn->more_results()) $conn->next_result();
            $receivedProducts[] = ['productID'=>$productID,'qty'=>$qty];
        }
        $conn->query("UPDATE purchase_order SET status='Received', dateReceived=NOW() WHERE poID=$poID");
        $conn->commit();

        // Broadcast stock updates for each received product
        foreach($receivedProducts as $rp){
            $sq = $conn->prepare("SELECT stock_quantity, productName FROM product WHERE productID=?");
            $sq->bind_param("i", $rp['productID']); $sq->execute();
            $sr = $sq->get_result()->fetch_assoc(); $sq->close();
            if($sr){
                pusherBroadcast('stock-updated', [
                    'productID'=>$rp['productID'],'productName'=>$sr['productName'],
                    'stock_quantity'=>(int)$sr['stock_quantity'],
                    'type'=>'IN','qty'=>$rp['qty'],'by'=>$_SESSION['userName']??'',
                ]);
            }
        }
        pusherBroadcast('purchase-changed', ['action'=>'received','poID'=>$poID,'by'=>$_SESSION['userName']??'']);
        header("Location: ../frontend/purchase.php?poReceived"); exit();
    } catch(Exception $e){
        $conn->rollback();
        header("Location: ../frontend/purchase.php?error"); exit();
    }
}

if(isset($_POST['cancelPO'])){
    $poID = intval($_POST['poID']);
    $conn->query("UPDATE purchase_order SET status='Cancelled' WHERE poID=$poID AND status='Pending'");
    pusherBroadcast('purchase-changed', ['action'=>'cancelled','poID'=>$poID,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/purchase.php?poCancelled"); exit();
}
?>
