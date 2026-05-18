<?php
require_once '../backend/database.php';
if(!isset($_SESSION['userID'])){ exit(); }

$salesID = intval($_GET['salesID']);
if(!$salesID) exit();

// Pull from orders (restaurant POS — no separate 'sales' table)
$sale = $conn->query("
    SELECT o.*,
           CONCAT(COALESCE(pu.givenName,''), ' ', COALESCE(pu.surName,'')) AS cashier,
           CONCAT(u.givenName,' ',u.surName) AS waiterName,
           c.customerName,
           dt.tableNo, dt.section
    FROM orders o
    LEFT JOIN users pu ON o.paid_by  = pu.userID
    LEFT JOIN users u  ON o.userID   = u.userID
    LEFT JOIN customer c ON o.customerID = c.customerID
    LEFT JOIN dining_table dt ON o.tableID = dt.tableID
    WHERE o.orderID = $salesID AND o.status = 'Paid'
")->fetch_assoc();

if(!$sale) {
    echo '<div class="alert alert-warning">Transaction not found.</div>';
    exit();
}

$items = $conn->query("
    SELECT oi.*, mi.itemName
    FROM order_items oi
    JOIN menu_item mi ON oi.itemID = mi.itemID
    WHERE oi.orderID = $salesID AND oi.item_status != 'Void'
    ORDER BY oi.dateAdded
");

$cashierDisplay = trim($sale['cashier']) ?: $sale['waiterName'] ?: '—';
$tableLabel = $sale['tableNo'] ? 'Table '.$sale['tableNo'].($sale['section'] ? ' ('.$sale['section'].')' : '') : $sale['orderType'];
?>
<div class="row mb-3">
    <div class="col-6">
        <small class="text-muted">Order #</small><br>
        <strong><?php echo htmlspecialchars($sale['orderNo'] ?? '#'.$sale['orderID']); ?></strong>
    </div>
    <div class="col-6 text-end">
        <small class="text-muted">Date & Time</small><br>
        <strong><?php echo date('M d, Y h:i A', strtotime($sale['datePaid'])); ?></strong>
    </div>
</div>
<div class="row mb-3">
    <div class="col-4"><small class="text-muted">Cashier</small><br><?php echo htmlspecialchars($cashierDisplay); ?></div>
    <div class="col-4"><small class="text-muted">Customer</small><br><?php echo htmlspecialchars($sale['customerName'] ?? 'Walk-in'); ?></div>
    <div class="col-4"><small class="text-muted">Table / Type</small><br><?php echo htmlspecialchars($tableLabel); ?></div>
</div>

<table class="table table-sm table-bordered mb-3">
    <thead class="table-light">
        <tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr>
    </thead>
    <tbody>
    <?php while($item = $items->fetch_assoc()): ?>
    <tr>
        <td>
            <?php echo htmlspecialchars($item['itemName']); ?>
            <?php if($item['notes']): ?><br><small class="text-muted"><?php echo htmlspecialchars($item['notes']); ?></small><?php endif; ?>
        </td>
        <td class="text-center"><?php echo $item['quantity']; ?></td>
        <td class="text-end">₱<?php echo number_format($item['price'], 2); ?></td>
        <td class="text-end">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<div class="row justify-content-end">
    <div class="col-md-6">
        <table class="table table-sm">
            <tr><td class="text-muted">Subtotal:</td><td class="text-end">₱<?php echo number_format($sale['subtotal'], 2); ?></td></tr>
            <?php if($sale['discount_amount'] > 0): ?>
            <tr><td class="text-muted">Discount (<?php echo htmlspecialchars($sale['discount_type'] ?? ''); ?>):</td>
                <td class="text-end text-danger">-₱<?php echo number_format($sale['discount_amount'], 2); ?></td></tr>
            <?php endif; ?>
            <?php if($sale['tax_amount'] > 0): ?>
            <tr><td class="text-muted">VAT:</td><td class="text-end">₱<?php echo number_format($sale['tax_amount'], 2); ?></td></tr>
            <?php endif; ?>
            <?php if($sale['service_charge'] > 0): ?>
            <tr><td class="text-muted">Service Charge:</td><td class="text-end">₱<?php echo number_format($sale['service_charge'], 2); ?></td></tr>
            <?php endif; ?>
            <tr class="fw-bold"><td>Total:</td><td class="text-end" style="color:var(--charcoal);">₱<?php echo number_format($sale['total_amount'], 2); ?></td></tr>
            <tr><td class="text-muted">Payment (<?php echo htmlspecialchars($sale['payment_method'] ?? ''); ?>):</td>
                <td class="text-end">₱<?php echo number_format($sale['payment'] ?? 0, 2); ?></td></tr>
            <tr><td class="text-muted">Change:</td><td class="text-end">₱<?php echo number_format($sale['change_amount'] ?? 0, 2); ?></td></tr>
        </table>
    </div>
</div>
