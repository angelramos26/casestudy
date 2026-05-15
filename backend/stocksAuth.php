<?php
require_once 'database.php';
require_once 'pusher-broadcast.php';
require_once __DIR__ . '/csrf.php';
session_start();
csrf_verify();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if($_SESSION['roleName'] !== 'Admin'){ header("Location: ../frontend/stocks.php"); exit(); }

$userID = $_SESSION['userID'];

/**
 * Drain all pending stored-procedure result sets then run a SELECT.
 * Avoids "Commands out of sync" error.
 */
function drainAndGetStock(mysqli $conn, mysqli_stmt $stmt, int $productID): ?array
{
    $stmt->close();
    while ($conn->more_results()) {
        $conn->next_result();
        $rs = $conn->use_result();
        if ($rs) $rs->close();
    }
    $q = $conn->prepare("SELECT stock_quantity, productName FROM product WHERE productID = ?");
    $q->bind_param("i", $productID);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $q->close();
    return $row ?: null;
}

// Stock In
if(isset($_POST['stockIn'])){
    $productID  = intval($_POST['productID']);
    $qty        = intval($_POST['qty']);
    $cost       = floatval($_POST['cost']);
    $supplierID = intval($_POST['supplierID']) ?: null;
    $notes      = sanitize($_POST['notes'] ?? '');
    if(!$productID || $qty <= 0){ header("Location: ../frontend/stocks.php?emptyFields"); exit(); }

    $stmt = $conn->prepare("CALL AddStock(?,?,?,?,?,?)");
    $stmt->bind_param("iiidis", $productID, $qty, $cost, $supplierID, $userID, $notes);
    $stmt->execute();

    $r = drainAndGetStock($conn, $stmt, $productID);
    if ($r) pusherBroadcast('stock-updated', [
        'productID' => $productID, 'productName' => $r['productName'],
        'stock_quantity' => (int)$r['stock_quantity'],
        'type' => 'IN', 'qty' => $qty, 'by' => $_SESSION['userName'] ?? 'Unknown',
    ]);

    header("Location: ../frontend/stocks.php?stockIn"); exit();
}

// Stock Out
if(isset($_POST['stockOut'])){
    $productID = intval($_POST['productID']);
    $qty       = intval($_POST['qty']);
    $notes     = sanitize($_POST['notes'] ?? '');
    $type      = 'OUT';
    if(!$productID || $qty <= 0){ header("Location: ../frontend/stocks.php?emptyFields"); exit(); }

    $check = $conn->prepare("SELECT stock_quantity FROM product WHERE productID=?");
    $check->bind_param("i", $productID);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();
    $check->close();
    if(!$row || $row['stock_quantity'] < $qty){ header("Location: ../frontend/stocks.php?insufficient"); exit(); }

    $stmt = $conn->prepare("CALL AdjustStock(?,?,?,?,?)");
    $stmt->bind_param("iisis", $productID, $qty, $type, $userID, $notes);
    $stmt->execute();

    $r = drainAndGetStock($conn, $stmt, $productID);
    if ($r) pusherBroadcast('stock-updated', [
        'productID' => $productID, 'productName' => $r['productName'],
        'stock_quantity' => (int)$r['stock_quantity'],
        'type' => 'OUT', 'qty' => $qty, 'by' => $_SESSION['userName'] ?? 'Unknown',
    ]);

    header("Location: ../frontend/stocks.php?stockOut"); exit();
}

// Stock Adjustment
if(isset($_POST['stockAdjust'])){
    $productID = intval($_POST['productID']);
    $qty       = intval($_POST['qty']);
    $type      = sanitize($_POST['type'] ?? 'ADJUST');
    $notes     = sanitize($_POST['notes'] ?? '');
    if(!$productID || $qty <= 0){ header("Location: ../frontend/stocks.php?emptyFields"); exit(); }

    $stmt = $conn->prepare("CALL AdjustStock(?,?,?,?,?)");
    $stmt->bind_param("iisis", $productID, $qty, $type, $userID, $notes);
    $stmt->execute();

    $r = drainAndGetStock($conn, $stmt, $productID);
    if ($r) pusherBroadcast('stock-updated', [
        'productID' => $productID, 'productName' => $r['productName'],
        'stock_quantity' => (int)$r['stock_quantity'],
        'type' => 'ADJUST', 'qty' => $qty, 'by' => $_SESSION['userName'] ?? 'Unknown',
    ]);

    header("Location: ../frontend/stocks.php?stockAdjust"); exit();
}
?>
