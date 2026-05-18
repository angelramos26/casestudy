<?php
/**
 * categoryAuth.php — Restaurant POS
 * Manages menu section categories (e.g. Appetizers, Main Course, Beverages).
 * Functionally identical to grocery version; just terminology changed.
 *
 * Roles: Admin, Manager
 */
require_once 'database.php';
require_once 'pusher-broadcast.php';
require_once __DIR__ . '/csrf.php';
csrf_verify();
if (!isset($_SESSION['userID'])) { header("Location: ../index.php"); exit(); }
if (!in_array($_SESSION['roleName'], ['Admin'])) {
    header("Location: ../frontend/dashboard.php"); exit();
}

if (isset($_POST['catSave'])) {
    $name = sanitize($_POST['categoryName']);
    if (!$name) { header("Location: ../frontend/category.php?emptyFields"); exit(); }

    $stmt = $conn->prepare("CALL AddCategory(?)");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();

    if ($r['result'] === 'success') {
        pusherBroadcast('category-changed', [
            'action'       => 'added',
            'categoryName' => $name,
            'by'           => $_SESSION['userName'] ?? '',
        ]);
        header("Location: ../frontend/category.php?catAdded"); exit();
    }
    header("Location: ../frontend/category.php?catDuplicate"); exit();
}

if (isset($_POST['catUpdate'])) {
    $id   = intval($_POST['categoryID']);
    $name = sanitize($_POST['categoryName']);

    $stmt = $conn->prepare("CALL UpdateCategory(?,?)");
    $stmt->bind_param("is", $id, $name);
    $stmt->execute();

    pusherBroadcast('category-changed', [
        'action'       => 'updated',
        'categoryID'   => $id,
        'categoryName' => $name,
        'by'           => $_SESSION['userName'] ?? '',
    ]);
    header("Location: ../frontend/category.php?catUpdated"); exit();
}

if (isset($_POST['catDelete'])) {
    $id = intval($_POST['categoryID']);

    // Check if any active menu items use this category
    $chk = $conn->prepare("SELECT COUNT(*) AS c FROM menu_item WHERE categoryID=? AND status='Active'");
    $chk->bind_param("i", $id);
    $chk->execute();
    $cnt = $chk->get_result()->fetch_assoc()['c'];

    if ($cnt > 0) {
        header("Location: ../frontend/category.php?catHasItems"); exit();
    }

    $stmt = $conn->prepare("CALL DeleteCategory(?)");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    pusherBroadcast('category-changed', [
        'action'     => 'deleted',
        'categoryID' => $id,
        'by'         => $_SESSION['userName'] ?? '',
    ]);
    header("Location: ../frontend/category.php?catDeleted"); exit();
}
