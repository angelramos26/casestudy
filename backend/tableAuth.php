<?php
/**
 * tableAuth.php — Restaurant POS
 * Manages dining tables: add, update, delete, change status.
 * NEW FILE — no grocery equivalent (restaurants need table management).
 *
 * Roles allowed: Admin, Manager
 */
require_once 'database.php';
require_once 'pusher-broadcast.php';
require_once __DIR__ . '/csrf.php';

// Use JSON mode for csrf_verify so fetch() calls get proper JSON errors
$isAjax = isset($_POST['tableStatus']);
csrf_verify($isAjax);
if (!isset($_SESSION['userID'])) { header("Location: ../index.php"); exit(); }

// ── Add table (Admin only) ───────────────────────────────────
if (isset($_POST['tableSave'])) {
    if (!in_array($_SESSION['roleName'], ['Admin'])) {
        header("Location: ../frontend/dashboard.php"); exit();
    }
    $tableNo  = sanitize($_POST['tableNo']);
    $capacity = intval($_POST['capacity']);
    $section  = sanitize($_POST['section'] ?? 'Main');

    if (!$tableNo || $capacity < 1) {
        header("Location: ../frontend/tables.php?emptyFields"); exit();
    }

    $stmt = $conn->prepare("CALL AddDiningTable(?,?,?)");
    $stmt->bind_param("sis", $tableNo, $capacity, $section);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    while ($conn->more_results()) $conn->next_result();

    if ($r['result'] === 'duplicate_tableNo') {
        header("Location: ../frontend/tables.php?tableExists"); exit();
    }

    pusherBroadcast('table-changed', [
        'action'  => 'added',
        'tableNo' => $tableNo,
        'by'      => $_SESSION['userName'] ?? '',
    ]);
    header("Location: ../frontend/tables.php?savedData"); exit();
}

// ── Update table (Admin only) ────────────────────────────────
if (isset($_POST['tableUpdate'])) {
    if (!in_array($_SESSION['roleName'], ['Admin'])) {
        header("Location: ../frontend/dashboard.php"); exit();
    }
    $id       = intval($_POST['tableID']);
    $tableNo  = sanitize($_POST['tableNo']);
    $capacity = intval($_POST['capacity']);
    $section  = sanitize($_POST['section'] ?? 'Main');

    $stmt = $conn->prepare("CALL UpdateDiningTable(?,?,?,?)");
    $stmt->bind_param("isis", $id, $tableNo, $capacity, $section);
    $stmt->execute();
    $stmt->close();
    while ($conn->more_results()) $conn->next_result();

    pusherBroadcast('table-changed', [
        'action'  => 'updated',
        'tableID' => $id,
        'tableNo' => $tableNo,
        'by'      => $_SESSION['userName'] ?? '',
    ]);
    header("Location: ../frontend/tables.php?updatedTable"); exit();
}

// ── Delete table (Admin only, no open orders) ────────────────
if (isset($_POST['tableDelete'])) {
    if (!in_array($_SESSION['roleName'], ['Admin'])) {
        header("Location: ../frontend/dashboard.php"); exit();
    }
    $id = intval($_POST['tableID']);

    // Check active orders on this table
    $chk = $conn->prepare(
        "SELECT COUNT(*) AS c FROM orders WHERE tableID=? AND status IN ('Open','Active')"
    );
    $chk->bind_param("i", $id);
    $chk->execute();
    $cnt = $chk->get_result()->fetch_assoc()['c'];
    $chk->close();

    if ($cnt > 0) {
        header("Location: ../frontend/tables.php?tableHasOrders"); exit();
    }

    $stmt = $conn->prepare("DELETE FROM dining_table WHERE tableID=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    pusherBroadcast('table-changed', [
        'action'  => 'deleted',
        'tableID' => $id,
        'by'      => $_SESSION['userName'] ?? '',
    ]);
    header("Location: ../frontend/tables.php?tableDeleted"); exit();
}

// ── Manually change table status (Admin + Cashier) ───────────
if (isset($_POST['tableStatus'])) {
    if (!in_array($_SESSION['roleName'], ['Admin', 'Cashier'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    $id      = intval($_POST['tableID']);
    $status  = sanitize($_POST['status']);
    $allowed = ['Available', 'Occupied', 'Reserved', 'Maintenance'];
    if (!in_array($status, $allowed)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE dining_table SET status=? WHERE tableID=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();

    pusherBroadcast('table-changed', [
        'action'  => 'status-changed',
        'tableID' => $id,
        'status'  => $status,
        'by'      => $_SESSION['userName'] ?? '',
    ]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit();
}
