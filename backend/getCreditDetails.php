<?php
/**
 * getCreditDetails.php — Restaurant POS
 * AJAX: renders credit ledger + pay form for a customer.
 * Identical to grocery version (credit system unchanged).
 */
require_once 'database.php';
require_once __DIR__ . '/csrf.php';
if (!isset($_SESSION['userID'])) {
    echo '<div class="alert alert-danger">Not logged in.</div>'; exit();
}
if (!in_array($_SESSION['roleName'], ['Admin', 'Cashier'])) {
    echo '<div class="alert alert-danger">Access denied.</div>'; exit();
}

$customerID = intval($_GET['customerID'] ?? 0);
if (!$customerID) { echo '<div class="alert alert-danger">Invalid customer.</div>'; exit(); }

$custStmt = $conn->prepare("SELECT * FROM customer WHERE customerID=? AND dateDeleted IS NULL");
$custStmt->bind_param("i", $customerID);
$custStmt->execute();
$cust = $custStmt->get_result()->fetch_assoc();
$custStmt->close();
if (!$cust) { echo '<div class="alert alert-danger">Customer not found.</div>'; exit(); }

$ledgerStmt = $conn->prepare(
    "SELECT cc.*, CONCAT(u.givenName,' ',u.surName) AS byUser
     FROM customer_credit cc
     JOIN users u ON cc.userID=u.userID
     WHERE cc.customerID=?
     ORDER BY cc.dateCreated DESC LIMIT 15"
);
$ledgerStmt->bind_param("i", $customerID);
$ledgerStmt->execute();
$ledger = $ledgerStmt->get_result();
$ledgerStmt->close();

$balClass = $cust['credit_balance'] > 0 ? 'warning' : 'success';
?>
<div class="alert alert-<?= $balClass ?> py-2 text-center mb-3">
  Current Balance: <strong>₱<?= number_format($cust['credit_balance'], 2) ?></strong>
</div>

<table class="table table-sm table-striped mb-3">
  <thead class="table-light">
    <tr><th>Date</th><th>Type</th><th>Amount</th><th>Notes</th><th>By</th></tr>
  </thead>
  <tbody>
  <?php $hasRows = false; while ($l = $ledger->fetch_assoc()): $hasRows = true; ?>
  <tr>
    <td class="small"><?= date('M d, Y', strtotime($l['dateCreated'])) ?></td>
    <td><span class="badge <?= $l['type'] === 'DEBIT' ? 'bg-danger' : 'bg-success' ?>"><?= $l['type'] ?></span></td>
    <td>₱<?= number_format($l['amount'], 2) ?></td>
    <td class="small text-muted"><?= htmlspecialchars($l['notes'] ?? '—') ?></td>
    <td class="small text-muted"><?= htmlspecialchars($l['byUser'] ?? '—') ?></td>
  </tr>
  <?php endwhile; ?>
  <?php if (!$hasRows): ?>
  <tr><td colspan="5" class="text-center text-muted small">No credit transactions yet.</td></tr>
  <?php endif; ?>
  </tbody>
</table>

<form method="POST" action="../backend/customerAuth.php">
  <?php csrf_field(); ?>
  <input type="hidden" name="customerID" value="<?= $customerID ?>">
  <div class="mb-2">
    <label class="form-label small fw-semibold">Pay Amount (₱)</label>
    <input type="number" name="amount" step="0.01" min="0.01" max="<?= $cust['credit_balance'] ?>"
           class="form-control form-control-sm" required>
  </div>
  <div class="mb-2">
    <input type="text" name="notes" placeholder="Payment notes" class="form-control form-control-sm">
  </div>
  <button type="submit" name="payCredit" class="btn btn-success btn-sm w-100">
    <i class="bi bi-check-circle me-1"></i>Record Payment
  </button>
</form>
