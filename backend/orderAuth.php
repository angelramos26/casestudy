<?php
/**
 * orderAuth.php — Restaurant POS
 * Core order lifecycle:
 *   openOrder → addItem → sendToKitchen → settleOrder
 *   + transfer table, void item, cancel order
 *
 * Replaces: salesAuth.php (grocery checkout → restaurant order flow)
 *
 * Restaurant-specific vs grocery:
 *   - Orders have open/active/paid states (not instant checkout)
 *   - Dine-in / Takeout / Delivery order types
 *   - Table assignment & release
 *   - Kitchen send step (items go to kitchen display)
 *   - Item-level void with reason logging
 *   - Service charge + discount types (Senior, PWD, Promo, Manual)
 *   - Payment methods: Cash, Card, GCash, Maya, Credit
 */
ob_start(); // Buffer ALL output to prevent stray warnings corrupting JSON
require_once 'database.php';
require_once 'pusher-broadcast.php';
require_once __DIR__ . '/csrf.php';
csrf_verify(true); // JSON endpoint — verify but don't redirect
ob_clean(); // Discard any output from includes above
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['userID'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Not logged in']); exit();
}

$userID = intval($_SESSION['userID']);

// ============================================================
// OPEN ORDER
// ============================================================
if (isset($_POST['openOrder'])) {
    $tableID    = !empty($_POST['tableID'])    ? intval($_POST['tableID'])   : null;
    $orderType  = sanitize($_POST['orderType']  ?? 'Dine-In');
    $pax        = max(1, intval($_POST['pax']   ?? 1));
    $customerID = !empty($_POST['customerID'])  ? intval($_POST['customerID']) : null;
    $notes      = sanitize($_POST['notes']      ?? '');

    // Validate order type
    $validTypes = ['Dine-In', 'Takeout', 'Delivery'];
    if (!in_array($orderType, $validTypes)) $orderType = 'Dine-In';

    // For Dine-In, table must be Available
    if ($orderType === 'Dine-In' && $tableID) {
        $chk = $conn->prepare("SELECT status FROM dining_table WHERE tableID=?");
        $chk->bind_param("i", $tableID);
        $chk->execute();
        $t = $chk->get_result()->fetch_assoc();
        $chk->close();
        if (!$t || $t['status'] !== 'Available') {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Table is not available.']); exit();
        }
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("CALL OpenOrder(?,?,?,?,?,?)");
        $stmt->bind_param("isiiss", $tableID, $orderType, $pax, $customerID, $userID, $notes);
        $stmt->execute();
        $r       = $stmt->get_result()->fetch_assoc();
        $orderID = intval($r['orderID']);
        $stmt->close();
        while ($conn->more_results()) $conn->next_result();

        $conn->commit();

        pusherBroadcast('order-opened', [
            'orderID'   => $orderID,
            'tableID'   => $tableID,
            'orderType' => $orderType,
            'by'        => $_SESSION['userName'] ?? '',
        ]);

        ob_end_clean();
        echo json_encode(['success' => true, 'orderID' => $orderID]);
    } catch (Exception $e) {
        $conn->rollback();
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ============================================================
// ADD ITEM TO ORDER
// ============================================================
if (isset($_POST['addItem'])) {
    $orderID  = intval($_POST['orderID']);
    $itemID   = intval($_POST['itemID']);
    $quantity = max(1, intval($_POST['quantity'] ?? 1));
    $notes    = sanitize($_POST['notes'] ?? '');

    // Verify order is still open/active
    $chkOrder = $conn->prepare("SELECT status FROM orders WHERE orderID=?");
    $chkOrder->bind_param("i", $orderID);
    $chkOrder->execute();
    $ord = $chkOrder->get_result()->fetch_assoc();
    $chkOrder->close();
    if (!$ord || !in_array($ord['status'], ['Open', 'Active'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Order is no longer open.']); exit();
    }

    // Verify item is available
    $chkItem = $conn->prepare("SELECT price, is_available, status FROM menu_item WHERE itemID=?");
    $chkItem->bind_param("i", $itemID);
    $chkItem->execute();
    $item = $chkItem->get_result()->fetch_assoc();
    $chkItem->close();
    if (!$item || !$item['is_available'] || $item['status'] !== 'Active') {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Item is not available.']); exit();
    }

    $price = floatval($item['price']);

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("CALL AddOrderItem(?,?,?,?,?)");
        $stmt->bind_param("iiids", $orderID, $itemID, $quantity, $price, $notes);
        $stmt->execute();
        $stmt->close();
        while ($conn->more_results()) $conn->next_result();

        $conn->commit();

        // Fetch updated order total
        $tot = $conn->prepare("SELECT total_amount FROM orders WHERE orderID=?");
        $tot->bind_param("i", $orderID);
        $tot->execute();
        $totRow = $tot->get_result()->fetch_assoc();
        $tot->close();

        pusherBroadcast('order-updated', [
            'orderID'      => $orderID,
            'action'       => 'item-added',
            'itemID'       => $itemID,
            'qty'          => $quantity,
            'total_amount' => floatval($totRow['total_amount'] ?? 0),
            'by'           => $_SESSION['userName'] ?? '',
        ]);

        ob_end_clean();
        echo json_encode([
            'success'      => true,
            'total_amount' => floatval($totRow['total_amount'] ?? 0),
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ============================================================
// UPDATE ORDER ITEM QUANTITY
// ============================================================
if (isset($_POST['updateItem'])) {
    $orderItemID = intval($_POST['orderItemID']);
    $quantity    = intval($_POST['quantity']);
    $notes       = sanitize($_POST['notes'] ?? '');

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("CALL UpdateOrderItem(?,?,?)");
        $stmt->bind_param("iis", $orderItemID, $quantity, $notes);
        $stmt->execute();
        $stmt->close();
        while ($conn->more_results()) $conn->next_result();

        $conn->commit();

        ob_end_clean();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ============================================================
// SEND ORDER TO KITCHEN
// ============================================================
if (isset($_POST['sendToKitchen'])) {
    $orderID = intval($_POST['orderID']);

    // POS sends items as JSON array — insert them first before marking as Sent
    if (!empty($_POST['items'])) {
        $incoming = json_decode($_POST['items'], true);
        if (is_array($incoming) && count($incoming)) {
            $conn->begin_transaction();
            try {
                $ins = $conn->prepare(
                    "INSERT INTO order_items (orderID, itemID, quantity, price, notes, item_status)
                     SELECT ?, itemID, ?, price, ?, 'Pending'
                     FROM menu_item WHERE itemID=? AND status='Active'"
                );
                foreach ($incoming as $it) {
                    $iID  = intval($it['itemID'] ?? 0);
                    $qty  = max(1, intval($it['quantity'] ?? 1));
                    $note = substr(sanitize($it['notes'] ?? ''), 0, 255);
                    $ins->bind_param('iisi', $orderID, $qty, $note, $iID);
                    $ins->execute();
                }
                $ins->close();
                // Recalculate subtotal
                $recalc = $conn->prepare(
                    "UPDATE orders o
                     SET o.subtotal = (SELECT COALESCE(SUM(oi.quantity * oi.price),0)
                                       FROM order_items oi
                                       WHERE oi.orderID = ? AND oi.item_status != 'Void'),
                         o.total_amount = o.subtotal + o.service_charge - o.discount_amount + o.tax_amount
                     WHERE o.orderID = ?"
                );
                $recalc->bind_param("ii", $orderID, $orderID);
                $recalc->execute();
                $recalc->close();
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Failed to save items: ' . $e->getMessage()]); exit();
            }
        }
    }

    // Check there are pending items
    $chk = $conn->prepare(
        "SELECT COUNT(*) AS c FROM order_items WHERE orderID=? AND item_status='Pending'"
    );
    $chk->bind_param("i", $orderID);
    $chk->execute();
    $pendingCount = intval($chk->get_result()->fetch_assoc()['c']);
    $chk->close();

    if ($pendingCount === 0) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'No pending items to send.']); exit();
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("CALL SendToKitchen(?,?)");
        $stmt->bind_param("ii", $orderID, $userID);
        $stmt->execute();
        $stmt->close();
        while ($conn->more_results()) $conn->next_result();

        $conn->commit();

        // Fetch items sent for kitchen display broadcast
        $kitchenItems = [];
        $qi = $conn->prepare(
            "SELECT oi.orderItemID, mi.itemName, oi.quantity, oi.notes
             FROM order_items oi
             JOIN menu_item mi ON oi.itemID = mi.itemID
             WHERE oi.orderID=? AND oi.item_status='Sent'"
        );
        $qi->bind_param("i", $orderID);
        $qi->execute();
        $res = $qi->get_result();
        while ($row = $res->fetch_assoc()) $kitchenItems[] = $row;
        $qi->close();

        // Fetch table info for broadcast
        $tableInfo = null;
        $qt = $conn->prepare("SELECT o.tableID, dt.tableNo, o.orderType, o.pax FROM orders o LEFT JOIN dining_table dt ON o.tableID=dt.tableID WHERE o.orderID=?");
        $qt->bind_param("i", $orderID); $qt->execute();
        $tableInfo = $qt->get_result()->fetch_assoc();
        $qt->close();

        pusherBroadcast('kitchen-order', [
            'orderID'   => $orderID,
            'tableNo'   => $tableInfo['tableNo'] ?? ($tableInfo['orderType'] ?? 'Takeout'),
            'orderType' => $tableInfo['orderType'] ?? '',
            'pax'       => $tableInfo['pax'] ?? 1,
            'items'     => $kitchenItems,
            'by'        => $_SESSION['userName'] ?? '',
        ]);

        ob_end_clean();
        echo json_encode(['success' => true, 'itemsSent' => count($kitchenItems)]);
    } catch (Exception $e) {
        $conn->rollback();
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ============================================================
// KITCHEN: MARK ITEM READY
// ============================================================
if (isset($_POST['markReady'])) {
    $orderItemID = intval($_POST['orderItemID']);
    try {
        $stmt = $conn->prepare("CALL MarkItemReady(?)");
        $stmt->bind_param("i", $orderItemID);
        $stmt->execute();
        $stmt->close();
        while ($conn->more_results()) $conn->next_result();

        pusherBroadcast('kitchen-update', [
            'action'      => 'ready',
            'orderItemID' => $orderItemID,
            'by'          => $_SESSION['userName'] ?? '',
        ]);
        ob_end_clean();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ============================================================
// KITCHEN: MARK ITEM SERVED
// ============================================================
if (isset($_POST['markServed'])) {
    $orderItemID = intval($_POST['orderItemID']);
    try {
        $stmt = $conn->prepare("CALL MarkItemServed(?)");
        $stmt->bind_param("i", $orderItemID);
        $stmt->execute();
        $stmt->close();
        while ($conn->more_results()) $conn->next_result();

        pusherBroadcast('kitchen-update', [
            'action'      => 'served',
            'orderItemID' => $orderItemID,
            'by'          => $_SESSION['userName'] ?? '',
        ]);
        ob_end_clean();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ============================================================
// VOID ORDER ITEM
// ============================================================
if (isset($_POST['voidItem'])) {
    // Only Managers and Admins can void items
    if (!in_array($_SESSION['roleName'], ['Admin', 'Cashier'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Access denied.']); exit();
    }

    $orderItemID = intval($_POST['orderItemID']);
    $reason      = sanitize($_POST['reason'] ?? 'No reason given');

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("CALL VoidOrderItem(?,?,?)");
        $stmt->bind_param("isi", $orderItemID, $reason, $userID);
        $stmt->execute();
        $stmt->close();
        while ($conn->more_results()) $conn->next_result();

        $conn->commit();

        pusherBroadcast('order-updated', [
            'action'      => 'item-voided',
            'orderItemID' => $orderItemID,
            'reason'      => $reason,
            'by'          => $_SESSION['userName'] ?? '',
        ]);

        ob_end_clean();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ============================================================
// SETTLE (PAY) ORDER
// ============================================================
if (isset($_POST['settleOrder'])) {
    $orderID        = intval($_POST['orderID']);
    // POS sends 'amount_tendered'; fall back to 'payment' for compatibility
    $payment        = floatval($_POST['amount_tendered'] ?? $_POST['payment'] ?? 0);
    $payment_method = sanitize($_POST['payment_method']);
    $discount_type  = sanitize($_POST['discount_type']   ?? 'None');
    $discount_amount= floatval($_POST['discount_amount'] ?? 0);
    $tax_amount     = floatval($_POST['tax_amount']       ?? 0);
    $service_charge = floatval($_POST['service_charge']   ?? 0);
    $total_amount   = floatval($_POST['total_amount']);
    $change_amount  = floatval($_POST['change_amount']    ?? 0);
    $customerID     = !empty($_POST['customerID']) ? intval($_POST['customerID']) : 0;
    // Calculate subtotal from DB if not provided (POS doesn't send it)
    if (empty($_POST['subtotal'])) {
        $stmtSub = $conn->prepare("SELECT COALESCE(SUM(quantity*price),0) AS s FROM order_items WHERE orderID=? AND item_status!='Void'");
        $stmtSub->bind_param("i", $orderID);
        $stmtSub->execute();
        $stRow = $stmtSub->get_result()->fetch_assoc();
        $stmtSub->close();
        $subtotal = floatval($stRow['s']);
    } else {
        $subtotal = floatval($_POST['subtotal']);
    }

    // Validate payment covers total (except Credit)
    if ($payment_method !== 'Credit' && $payment < $total_amount) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Insufficient payment amount.']); exit();
    }

    // Credit requires a customer
    if ($payment_method === 'Credit' && !$customerID) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Credit payment requires a customer.']); exit();
    }

    $conn->begin_transaction();
    try {
        // Use the SettleOrder stored procedure — it handles table release and credit in one transaction
        $stmt = $conn->prepare("CALL SettleOrder(?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param(
            "iidsddddsddi",
            $orderID, $userID,
            $subtotal, $discount_type, $discount_amount,
            $tax_amount, $service_charge, $total_amount,
            $payment_method, $payment, $change_amount,
            $customerID
        );
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        while ($conn->more_results()) $conn->next_result();

        // Fetch table ID for broadcast (SP already released it)
        $tq = $conn->prepare("SELECT tableID FROM orders WHERE orderID=?");
        $tq->bind_param("i", $orderID); $tq->execute();
        $tRow = $tq->get_result()->fetch_assoc(); $tq->close();

        $conn->commit();

        pusherBroadcast('order-paid', [
            'orderID'       => $orderID,
            'total'         => $total_amount,
            'paymentMethod' => $payment_method,
            'cashier'       => $_SESSION['userName'] ?? 'Unknown',
            'tableID'       => $tRow['tableID'] ?? null,
        ]);

        ob_end_clean();
        echo json_encode(['success' => true, 'orderID' => $orderID]);
    } catch (Exception $e) {
        $conn->rollback();
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ============================================================
// CANCEL ORDER
// ============================================================
if (isset($_POST['cancelOrder'])) {
    if (!in_array($_SESSION['roleName'], ['Admin'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Access denied.']); exit();
    }

    $orderID = intval($_POST['orderID']);
    $reason  = sanitize($_POST['reason'] ?? 'Cancelled by manager');

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("CALL CancelOrder(?,?,?)");
        $stmt->bind_param("isi", $orderID, $reason, $userID);
        $stmt->execute();
        $stmt->close();
        while ($conn->more_results()) $conn->next_result();

        $conn->commit();

        pusherBroadcast('order-cancelled', [
            'orderID' => $orderID,
            'reason'  => $reason,
            'by'      => $_SESSION['userName'] ?? '',
        ]);

        ob_end_clean();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ============================================================
// TRANSFER TABLE
// ============================================================
if (isset($_POST['transferTable'])) {
    if (!in_array($_SESSION['roleName'], ['Admin', 'Cashier'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Access denied.']); exit();
    }

    $orderID     = intval($_POST['orderID']);
    $newTableID  = intval($_POST['newTableID']);

    // Check new table is available
    $chk = $conn->prepare("SELECT status, tableNo FROM dining_table WHERE tableID=?");
    $chk->bind_param("i", $newTableID); $chk->execute();
    $nt = $chk->get_result()->fetch_assoc(); $chk->close();

    if (!$nt || $nt['status'] !== 'Available') {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Target table is not available.']); exit();
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("CALL TransferTable(?,?,?)");
        $stmt->bind_param("iii", $orderID, $newTableID, $userID);
        $stmt->execute();
        $stmt->close();
        while ($conn->more_results()) $conn->next_result();

        $conn->commit();

        pusherBroadcast('table-changed', [
            'action'      => 'transferred',
            'orderID'     => $orderID,
            'newTableID'  => $newTableID,
            'newTableNo'  => $nt['tableNo'],
            'by'          => $_SESSION['userName'] ?? '',
        ]);

        ob_end_clean();
        echo json_encode(['success' => true, 'newTableNo' => $nt['tableNo']]);
    } catch (Exception $e) {
        $conn->rollback();
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ============================================================
// GET ORDER DETAILS (AJAX for receipt / kitchen view)
// ============================================================
if (isset($_GET['getOrder'])) {
    $orderID = intval($_GET['orderID']);

    $ord = $conn->prepare(
        "SELECT o.*, dt.tableNo, dt.section,
                CONCAT(u.givenName,' ',u.surName) AS waiterName,
                c.customerName
         FROM orders o
         LEFT JOIN dining_table dt ON o.tableID=dt.tableID
         LEFT JOIN users u ON o.userID=u.userID
         LEFT JOIN customer c ON o.customerID=c.customerID
         WHERE o.orderID=?"
    );
    $ord->bind_param("i", $orderID);
    $ord->execute();
    $order = $ord->get_result()->fetch_assoc();
    $ord->close();

    if (!$order) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Order not found.']); exit();
    }

    $items = [];
    $qi = $conn->prepare(
        "SELECT oi.*, mi.itemName, mi.categoryID,
                c.categoryName
         FROM order_items oi
         JOIN menu_item mi ON oi.itemID=mi.itemID
         LEFT JOIN category c ON mi.categoryID=c.categoryID
         WHERE oi.orderID=? AND oi.item_status != 'Void'
         ORDER BY oi.dateAdded"
    );
    $qi->bind_param("i", $orderID);
    $qi->execute();
    $res = $qi->get_result();
    while ($row = $res->fetch_assoc()) $items[] = $row;
    $qi->close();

    ob_end_clean();
    echo json_encode(['success' => true, 'order' => $order, 'items' => $items]);
    exit();
}
