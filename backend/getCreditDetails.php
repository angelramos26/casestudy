<?php
require_once 'database.php';
session_start();
if(!isset($_SESSION['userID'])){ echo '<div class="alert alert-danger">Not logged in.</div>'; exit(); }
if(!in_array($_SESSION['roleName'], ['Admin','Owner','Cashier'])){ echo '<div class="alert alert-danger">Access denied.</div>'; exit(); }

$customerID = intval($_GET['customerID'] ?? 0);
if(!$customerID){ echo '<div class="alert alert-danger">Invalid customer.</div>'; exit(); }

$cust = $conn->query("SELECT * FROM customer WHERE customerID=$customerID AND dateDeleted IS NULL")->fetch_assoc();
if(!$cust){ echo '<div class="alert alert-danger">Customer not found.</div>'; exit(); }

$ledger = $conn->query("SELECT cc.*, CONCAT(u.givenName,' ',u.surName) AS byUser FROM customer_credit cc JOIN users u ON cc.userID=u.userID WHERE cc.customerID=$customerID ORDER BY cc.dateCreated DESC LIMIT 15");

$balClass = $cust['credit_balance'] > 0 ? 'warning' : 'success';
?>
<div class="alert alert-<?php echo $balClass; ?> py-2 text-center mb-3">
    Current Balance: <strong>₱<?php echo number_format($cust['credit_balance'],2); ?></strong>
</div>

<table class="table table-sm table-striped mb-3">
  <thead class="table-light">
    <tr><th>Date</th><th>Type</th><th>Amount</th><th>Notes</th><th>By</th></tr>
  </thead>
  <tbody>
  <?php
  $hasRows = false;
  while($l = $ledger->fetch_assoc()):
      $hasRows = true;
  ?>
  <tr>
    <td class="small"><?php echo date('M d, Y', strtotime($l['dateCreated'])); ?></td>
    <td><span class="<?php echo $l['type']==='DEBIT' ? 'badge-inactive' : 'badge-active'; ?>"><?php echo $l['type']; ?></span></td>
    <td>₱<?php echo number_format($l['amount'],2); ?></td>
    <td class="small text-muted"><?php echo htmlspecialchars($l['notes']??'—'); ?></td>
    <td class="small text-muted"><?php echo htmlspecialchars($l['byUser']??'—'); ?></td>
  </tr>
  <?php endwhile; ?>
  <?php if(!$hasRows): ?>
  <tr><td colspan="5" class="text-center text-muted small">No credit transactions yet.</td></tr>
  <?php endif; ?>
  </tbody>
</table>

<div class="row g-2">
  <?php if(in_array($_SESSION['roleName'], ['Admin','Owner'])): ?>
  <div class="col-6">
    <form method="POST" action="../backend/customerAuth.php">
      <input type="hidden" name="customerID" value="<?php echo $customerID; ?>">
      <div class="mb-2"><label class="form-label small fw-semibold">Add Utang (₱)</label>
        <input type="number" name="amount" step="0.01" min="0.01" class="form-control form-control-sm" required></div>
      <div class="mb-2"><input type="text" name="notes" placeholder="Notes" class="form-control form-control-sm"></div>
      <button type="submit" name="addCredit" class="btn btn-danger btn-sm w-100">
        <i class="bi bi-plus-circle me-1"></i>Add Utang
      </button>
    </form>
  </div>
  <div class="col-6">
  <?php else: ?>
  <div class="col-12">
  <?php endif; ?>
    <form method="POST" action="../backend/customerAuth.php">
      <input type="hidden" name="customerID" value="<?php echo $customerID; ?>">
      <div class="mb-2"><label class="form-label small fw-semibold">Pay Amount (₱)</label>
        <input type="number" name="amount" step="0.01" min="0.01" max="<?php echo $cust['credit_balance']; ?>" class="form-control form-control-sm" required></div>
      <div class="mb-2"><input type="text" name="notes" placeholder="Notes" class="form-control form-control-sm"></div>
      <button type="submit" name="payCredit" class="btn btn-success btn-sm w-100">
        <i class="bi bi-check-circle me-1"></i>Record Payment
      </button>
    </form>
  </div>
</div>
