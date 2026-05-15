<?php
require_once 'database.php';
session_start();
if(!isset($_SESSION['userID'])){ exit(); }

$salesID = intval($_GET['salesID']);
if(!$salesID) exit();

$sale = $conn->query("SELECT s.*, CONCAT(u.givenName,' ',u.surName) AS cashier, c.customerName FROM sales s JOIN users u ON s.userID=u.userID LEFT JOIN customer c ON s.customerID=c.customerID WHERE s.salesID=$salesID")->fetch_assoc();
if(!$sale) exit();

$items = $conn->query("SELECT sd.*, p.productName FROM sales_details sd JOIN product p ON sd.productID=p.productID WHERE sd.salesID=$salesID");
?>
<div class="row mb-3">
    <div class="col-6">
        <small class="text-muted">Transaction #</small><br><strong>#<?php echo $sale['salesID']; ?></strong>
    </div>
    <div class="col-6 text-end">
        <small class="text-muted">Date & Time</small><br><strong><?php echo date('M d, Y h:i A',strtotime($sale['saleDate'])); ?></strong>
    </div>
</div>
<div class="row mb-3">
    <div class="col-6"><small class="text-muted">Cashier</small><br><?php echo htmlspecialchars($sale['cashier']); ?></div>
    <div class="col-6"><small class="text-muted">Customer</small><br><?php echo htmlspecialchars($sale['customerName'] ?? 'Walk-in'); ?></div>
</div>

<table class="table table-sm table-bordered mb-3">
    <thead class="table-light"><tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
    <tbody>
    <?php while($item=$items->fetch_assoc()): ?>
    <tr>
        <td><?php echo htmlspecialchars($item['productName']); ?></td>
        <td class="text-center"><?php echo $item['sold_quantity']; ?></td>
        <td class="text-end">₱<?php echo number_format($item['price'],2); ?></td>
        <td class="text-end">₱<?php echo number_format($item['subtotal'],2); ?></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<div class="row justify-content-end">
    <div class="col-md-6">
        <table class="table table-sm">
            <tr><td class="text-muted">Subtotal:</td><td class="text-end">₱<?php echo number_format($sale['total_amount']+$sale['discount_amount']-$sale['tax_amount'],2); ?></td></tr>
            <?php if($sale['discount_amount'] > 0): ?>
            <tr><td class="text-muted">Discount:</td><td class="text-end text-danger">-₱<?php echo number_format($sale['discount_amount'],2); ?></td></tr>
            <?php endif; ?>
            <?php if($sale['tax_amount'] > 0): ?>
            <tr><td class="text-muted">Tax:</td><td class="text-end">₱<?php echo number_format($sale['tax_amount'],2); ?></td></tr>
            <?php endif; ?>
            <tr class="fw-bold"><td>Total:</td><td class="text-end" style="color:var(--ev-purple);">₱<?php echo number_format($sale['total_amount'],2); ?></td></tr>
            <tr><td class="text-muted">Payment (<?php echo $sale['payment_method']; ?>):</td><td class="text-end">₱<?php echo number_format($sale['payment'],2); ?></td></tr>
            <tr><td class="text-muted">Change:</td><td class="text-end">₱<?php echo number_format($sale['change_amount'],2); ?></td></tr>
        </table>
    </div>
</div>
