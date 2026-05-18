<?php
/**
 * menuItemAuth.php — Restaurant POS
 */
require_once 'database.php';
require_once 'pusher-broadcast.php';
require_once __DIR__ . '/csrf.php';
csrf_verify();
if (!isset($_SESSION['userID'])) { header("Location: ../index.php"); exit(); }
if (!in_array($_SESSION['roleName'], ['Admin'])) {
    header("Location: ../frontend/dashboard.php"); exit();
}

function handleItemImage($fileKey, $existingImage = null) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
        return $existingImage;
    }
    if ($_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) return $existingImage;

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($_FILES[$fileKey]['type'], $allowedTypes)) return $existingImage;
    if ($_FILES[$fileKey]['size'] > 2 * 1024 * 1024) return $existingImage;

    $uploadDir = __DIR__ . '/../uploads/menu/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if ($existingImage && file_exists(__DIR__ . '/../' . $existingImage)) {
        @unlink(__DIR__ . '/../' . $existingImage);
    }

    $ext      = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
    $filename = 'item_' . uniqid() . '.' . $ext;
    move_uploaded_file($_FILES[$fileKey]['tmp_name'], $uploadDir . $filename);
    return 'uploads/menu/' . $filename;
}

// ── Add new menu item ───────────────────────────────────────
if (isset($_POST['itemSave'])) {
    $name        = sanitize($_POST['itemName']);
    $catID       = intval($_POST['categoryID']);
    $price       = floatval($_POST['price']);
    $cost        = floatval($_POST['cost']);
    $description = sanitize($_POST['description'] ?? '');
    $available   = isset($_POST['is_available']) ? 1 : 0;
    $status      = sanitize($_POST['status']);

    if (!$name || !$catID || !$price) {
        header("Location: ../frontend/menu.php?emptyFields"); exit();
    }

    $stmt = $conn->prepare(
        "INSERT INTO menu_item (itemName, categoryID, price, cost, description, is_available, status)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("siddsis", $name, $catID, $price, $cost, $description, $available, $status);
    $stmt->execute();
    $newID = $conn->insert_id;
    $stmt->close();

    $imagePath = handleItemImage('item_image');
    if ($imagePath) {
        $stmt2 = $conn->prepare("UPDATE menu_item SET item_image=? WHERE itemID=?");
        $stmt2->bind_param("si", $imagePath, $newID);
        $stmt2->execute();
    }

    pusherBroadcast('menu-changed', [
        'action'   => 'added',
        'itemName' => $name,
        'by'       => $_SESSION['userName'] ?? '',
    ]);
    header("Location: ../frontend/menu.php?savedData"); exit();
}

// ── Update existing menu item ───────────────────────────────
if (isset($_POST['itemUpdate'])) {
    $id          = intval($_POST['itemID']);
    $name        = sanitize($_POST['itemName']);
    $catID       = intval($_POST['categoryID']);
    $price       = floatval($_POST['price']);
    $cost        = floatval($_POST['cost']);
    $description = sanitize($_POST['description'] ?? '');
    $available   = isset($_POST['is_available']) ? 1 : 0;
    $status      = sanitize($_POST['status']);

    $stmt = $conn->prepare(
        "UPDATE menu_item SET itemName=?, categoryID=?, price=?, cost=?,
         description=?, is_available=?, status=? WHERE itemID=?"
    );
    $stmt->bind_param("siddsisi", $name, $catID, $price, $cost, $description, $available, $status, $id);
    $stmt->execute();
    $stmt->close();

    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        $res = $conn->prepare("SELECT item_image FROM menu_item WHERE itemID=?");
        $res->bind_param("i", $id); $res->execute();
        $existing  = $res->get_result()->fetch_assoc()['item_image'] ?? null;
        $imagePath = handleItemImage('item_image', $existing);
        if ($imagePath) {
            $stmt2 = $conn->prepare("UPDATE menu_item SET item_image=? WHERE itemID=?");
            $stmt2->bind_param("si", $imagePath, $id);
            $stmt2->execute();
        }
    }

    pusherBroadcast('menu-changed', [
        'action'   => 'updated',
        'itemID'   => $id,
        'itemName' => $name,
        'price'    => $price,
        'by'       => $_SESSION['userName'] ?? '',
    ]);
    header("Location: ../frontend/menu.php?updatedItem"); exit();
}

// ── Toggle availability (86'd) ──────────────────────────────
if (isset($_POST['itemToggleAvailable'])) {
    header('Content-Type: application/json');
    $id        = intval($_POST['itemID']);
    $available = intval($_POST['is_available']);
    $stmt = $conn->prepare("UPDATE menu_item SET is_available=? WHERE itemID=?");
    $stmt->bind_param("ii", $available, $id);
    if ($stmt->execute()) {
        pusherBroadcast('menu-changed', [
            'action'    => $available ? 'available' : '86d',
            'itemID'    => $id,
            'available' => $available,
            'by'        => $_SESSION['userName'] ?? '',
        ]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit();
}

// ── Toggle status (Active ↔ Inactive) ──────────────────────
if (isset($_POST['itemToggleStatus'])) {
    header('Content-Type: application/json');
    $id        = intval($_POST['itemID']);
    $newStatus = $_POST['status'] === 'Active' ? 'Active' : 'Inactive';
    // When reactivating, also restore availability
    $newAvail  = $newStatus === 'Active' ? 1 : 0;

    $stmt = $conn->prepare("UPDATE menu_item SET status=?, is_available=? WHERE itemID=?");
    $stmt->bind_param("sii", $newStatus, $newAvail, $id);
    if ($stmt->execute()) {
        pusherBroadcast('menu-changed', [
            'action' => $newStatus === 'Active' ? 'reactivated' : 'deactivated',
            'itemID' => $id,
            'by'     => $_SESSION['userName'] ?? '',
        ]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit();
}

// ── Soft-delete (set status = Inactive) ────────────────────
if (isset($_POST['itemDelete'])) {
    $id = intval($_POST['itemID']);
    $stmt = $conn->prepare("UPDATE menu_item SET status='Inactive', is_available=0 WHERE itemID=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    pusherBroadcast('menu-changed', [
        'action' => 'deactivated',
        'itemID' => $id,
        'by'     => $_SESSION['userName'] ?? '',
    ]);
    header("Location: ../frontend/menu.php?itemDeleted"); exit();
}