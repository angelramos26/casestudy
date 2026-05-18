<?php
/**
 * getOrderDetails.php — Restaurant POS
 * AJAX helper: returns order + items.
 * ?format=json  → JSON for POS loadOrder()
 * (no param)    → HTML receipt for Order History modal
 */
require_once 'database.php';
require_once __DIR__ . '/csrf.php';

$isJson = ($_GET['format'] ?? '') === 'json';

if (!isset($_SESSION['userID'])) {
    if ($isJson) { echo json_encode(['success' => false, 'message' => 'Not logged in.']); exit(); }
    echo '<div class="alert alert-danger">Not logged in.</div>'; exit();
}

$orderID = intval($_GET['orderID'] ?? 0);
if (!$orderID) {
    if ($isJson) { echo json_encode(['success' => false, 'message' => 'Invalid order.']); exit(); }
    echo '<div class="alert alert-danger">Invalid order.</div>'; exit();
}

// ── Fetch order ─────────────────────────────────────────────
$ord = $conn->prepare(
    "SELECT o.*, dt.tableNo, dt.section,
            CONCAT(u.givenName,' ',u.surName) AS waiterName,
            c.customerName,
            pu.givenName AS cashierFirst, pu.surName AS cashierLast
     FROM orders o
     LEFT JOIN dining_table dt ON o.tableID = dt.tableID
     LEFT JOIN users u         ON o.userID  = u.userID
     LEFT JOIN users pu        ON o.paid_by = pu.userID
     LEFT JOIN customer c      ON o.customerID = c.customerID
     WHERE o.orderID = ?"
);
$ord->bind_param("i", $orderID);
$ord->execute();
$order = $ord->get_result()->fetch_assoc();
$ord->close();

if (!$order) {
    if ($isJson) { echo json_encode(['success' => false, 'message' => 'Order not found.']); exit(); }
    echo '<div class="alert alert-danger">Order not found.</div>'; exit();
}

// ── Fetch items ──────────────────────────────────────────────
$items = $conn->prepare(
    "SELECT oi.*, mi.itemName
     FROM order_items oi
     JOIN menu_item mi ON oi.itemID = mi.itemID
     WHERE oi.orderID = ? AND oi.item_status != 'Void'
     ORDER BY oi.dateAdded"
);
$items->bind_param("i", $orderID);
$items->execute();
$itemsArr = $items->get_result()->fetch_all(MYSQLI_ASSOC);
$items->close();

// ── JSON response (for POS loadOrder) ───────────────────────
if ($isJson) {
    echo json_encode([
        'success' => true,
        'order'   => [
            'orderID'         => (int)$order['orderID'],
            'orderNo'         => $order['orderNo'],
            'orderType'       => $order['orderType'],
            'tableID'         => $order['tableID'] ? (int)$order['tableID'] : null,
            'tableNo'         => $order['tableNo'] ?? null,
            'pax'             => (int)$order['pax'],
            'status'          => $order['status'],
            'subtotal'        => (float)$order['subtotal'],
            'discount_type'   => $order['discount_type'],
            'discount_amount' => (float)$order['discount_amount'],
            'tax_amount'      => (float)$order['tax_amount'],
            'service_charge'  => (float)$order['service_charge'],
            'total_amount'    => (float)$order['total_amount'],
            'customerID'      => $order['customerID'] ? (int)$order['customerID'] : null,
            'customerName'    => $order['customerName'] ?? null,
        ],
        'items' => array_map(fn($it) => [
            'orderItemID' => (int)$it['orderItemID'],
            'itemID'      => (int)$it['itemID'],
            'itemName'    => $it['itemName'],
            'price'       => (float)$it['price'],
            'quantity'    => (int)$it['quantity'],
            'notes'       => $it['notes'] ?? '',
            'status'      => $it['item_status'],
        ], $itemsArr),
    ]);
    exit();
}

// ── HTML receipt (for Order History modal) ───────────────────
$currency = htmlspecialchars(getSetting($conn, 'currency')           ?: '₱');
$restName = htmlspecialchars(getSetting($conn, 'restaurant_name')    ?: 'Restaurant POS');
$restAddr = htmlspecialchars(getSetting($conn, 'restaurant_address') ?: '');
$restTel  = htmlspecialchars(getSetting($conn, 'restaurant_contact') ?: '');
$footer   = htmlspecialchars(getSetting($conn, 'receipt_footer')     ?: 'Thank you for dining with us!');

$tableLabel = $order['tableNo']
    ? 'Table ' . htmlspecialchars($order['tableNo']) . ($order['section'] ? ' (' . htmlspecialchars($order['section']) . ')' : '')
    : htmlspecialchars($order['orderType']);
?>
<div class="text-center mb-3">
  <h6 class="fw-bold"><?= $restName ?></h6>
  <?php if($restAddr): ?><div class="small text-muted"><?= $restAddr ?></div><?php endif; ?>
  <?php if($restTel):  ?><div class="small text-muted"><?= $restTel  ?></div><?php endif; ?>
  <hr class="my-2">
  <div class="fw-bold"><?= htmlspecialchars($order['orderNo'] ?? '#'.$order['orderID']) ?></div>
  <div class="small"><?= $tableLabel ?> &bull; <?= htmlspecialchars($order['orderType']) ?></div>
  <div class="small">Waiter: <?= htmlspecialchars($order['waiterName'] ?? '—') ?></div>
  <?php if ($order['customerName']): ?>
  <div class="small">Guest: <?= htmlspecialchars($order['customerName']) ?></div>
  <?php endif; ?>
  <div class="small text-muted"><?= date('M d, Y h:i A', strtotime($order['dateCreated'])) ?></div>
</div>

<table class="table table-sm table-borderless">
  <thead>
    <tr>
      <th>Item</th>
      <th class="text-center">Qty</th>
      <th class="text-end">Price</th>
      <th class="text-end">Sub</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($itemsArr as $item): ?>
  <tr>
    <td>
      <?= htmlspecialchars($item['itemName']) ?>
      <?php if ($item['notes']): ?>
        <br><small class="text-muted"><?= htmlspecialchars($item['notes']) ?></small>
      <?php endif; ?>
    </td>
    <td class="text-center"><?= $item['quantity'] ?></td>
    <td class="text-end"><?= $currency . number_format($item['price'], 2) ?></td>
    <td class="text-end"><?= $currency . number_format($item['price'] * $item['quantity'], 2) ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<hr class="my-1">
<table class="table table-sm table-borderless mb-0">
  <tr><td>Subtotal</td><td class="text-end"><?= $currency . number_format($order['subtotal'], 2) ?></td></tr>
  <?php if ($order['discount_amount'] > 0): ?>
  <tr class="text-danger">
    <td>Discount (<?= htmlspecialchars($order['discount_type']) ?>)</td>
    <td class="text-end">-<?= $currency . number_format($order['discount_amount'], 2) ?></td>
  </tr>
  <?php endif; ?>
  <?php if ($order['tax_amount'] > 0): ?>
  <tr><td>VAT</td><td class="text-end"><?= $currency . number_format($order['tax_amount'], 2) ?></td></tr>
  <?php endif; ?>
  <?php if ($order['service_charge'] > 0): ?>
  <tr><td>Service Charge</td><td class="text-end"><?= $currency . number_format($order['service_charge'], 2) ?></td></tr>
  <?php endif; ?>
  <tr class="fw-bold fs-6">
    <td>TOTAL</td>
    <td class="text-end"><?= $currency . number_format($order['total_amount'], 2) ?></td>
  </tr>
  <?php if ($order['payment']): ?>
  <tr>
    <td>Payment (<?= htmlspecialchars($order['payment_method']) ?>)</td>
    <td class="text-end"><?= $currency . number_format($order['payment'], 2) ?></td>
  </tr>
  <tr><td>Change</td><td class="text-end"><?= $currency . number_format($order['change_amount'], 2) ?></td></tr>
  <?php endif; ?>
</table>

<hr class="my-2">
<div class="text-center small text-muted"><?= $footer ?></div>
<?php if ($order['paid_by']): ?>
<div class="text-center small text-muted mt-1">
  Cashier: <?= htmlspecialchars(trim(($order['cashierFirst'] ?? '') . ' ' . ($order['cashierLast'] ?? ''))) ?>
</div>
<?php endif; ?>