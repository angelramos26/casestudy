<?php
// customerModalAuth.php
// Renders per-row Credit, Edit, and Delete modals for each $row (customer row).
// Must be included inside the while($row = $result->fetch_assoc()) loop.
// Requires: $row (current customer row), $conn (DB connection for ledger query).
?>

<!-- Credit / Utang Modal -->
<div class="modal fade" id="creditModal<?php echo $row['customerID']; ?>" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
      <h5 class="modal-title"><i class="bi bi-wallet2 me-1"></i>Manage Credit – <?php echo htmlspecialchars($row['customerName']); ?></h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <div class="alert alert-<?php echo $row['credit_balance'] > 0 ? 'warning' : 'success'; ?> py-2 text-center mb-3">
        Current Balance: <strong>₱<?php echo number_format($row['credit_balance'],2); ?></strong>
      </div>
      <!-- Ledger -->
      <?php
      $ledger = $conn->query("SELECT cc.*, CONCAT(u.givenName,' ',u.surName) AS byUser
                              FROM customer_credit cc
                              JOIN users u ON cc.userID=u.userID
                              WHERE cc.customerID={$row['customerID']}
                              ORDER BY cc.dateCreated DESC LIMIT 10");
      ?>
      <table class="table table-sm table-striped mb-3">
        <thead class="table-light"><tr><th>Date</th><th>Type</th><th>Amount</th><th>Notes</th></tr></thead>
        <tbody>
        <?php while($l=$ledger->fetch_assoc()): ?>
        <tr>
          <td class="small"><?php echo date('M d, Y', strtotime($l['dateCreated'])); ?></td>
          <td><span class="<?php echo $l['type']==='DEBIT' ? 'badge-inactive' : 'badge-active'; ?>"><?php echo $l['type']; ?></span></td>
          <td>₱<?php echo number_format($l['amount'],2); ?></td>
          <td class="small text-muted"><?php echo htmlspecialchars($l['notes']??'—'); ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
      <!-- Add utang / pay -->
      <div class="row g-2">
        <div class="col-6">
          <form method="POST" action="../backend/customerAuth.php">
        <?php csrf_field(); ?>
            <input type="hidden" name="customerID" value="<?php echo $row['customerID']; ?>">
            <div class="mb-2"><label class="form-label small fw-semibold">Add Utang (₱)</label><input type="number" name="amount" step="0.01" min="0.01" class="form-control form-control-sm" required></div>
            <div class="mb-2"><input type="text" name="notes" placeholder="Notes" class="form-control form-control-sm"></div>
            <button type="submit" name="addCredit" class="btn btn-danger btn-sm w-100"><i class="bi bi-plus-circle me-1"></i>Add Utang</button>
          </form>
        </div>
        <div class="col-6">
          <form method="POST" action="../backend/customerAuth.php">
        <?php csrf_field(); ?>
            <input type="hidden" name="customerID" value="<?php echo $row['customerID']; ?>">
            <div class="mb-2"><label class="form-label small fw-semibold">Pay Amount (₱)</label><input type="number" name="amount" step="0.01" min="0.01" max="<?php echo $row['credit_balance']; ?>" class="form-control form-control-sm" required></div>
            <div class="mb-2"><input type="text" name="notes" placeholder="Notes" class="form-control form-control-sm"></div>
            <button type="submit" name="payCredit" class="btn btn-success btn-sm w-100"><i class="bi bi-check-circle me-1"></i>Record Payment</button>
          </form>
        </div>
      </div>
    </div>
  </div></div>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal<?php echo $row['customerID']; ?>" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/customerAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title">Edit Customer</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="customerID" value="<?php echo $row['customerID']; ?>">
        <div class="mb-2"><label class="form-label fw-semibold small">Name <span class="text-danger">*</span></label><input type="text" name="customerName" value="<?php echo htmlspecialchars($row['customerName']); ?>" class="form-control" required></div>
        <div class="mb-2"><label class="form-label fw-semibold small">Contact No</label><input type="text" name="contactNo" value="<?php echo htmlspecialchars($row['contactNo']??''); ?>" class="form-control"></div>
        <div class="mb-2"><label class="form-label fw-semibold small">Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($row['email']??''); ?>" class="form-control"></div>
        <div class="mb-2"><label class="form-label fw-semibold small">Address</label><textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($row['address']??''); ?></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="customerUpdate" class="btn btn-ev">Update</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Delete Customer Modal -->
<div class="modal fade" id="deleteCustomerModal<?php echo $row['customerID']; ?>" tabindex="-1">
  <div class="modal-dialog modal-sm"><div class="modal-content">
    <form method="POST" action="../backend/customerAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Delete Customer</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="customerID" value="<?php echo $row['customerID']; ?>">
        <p>Delete <strong><?php echo htmlspecialchars($row['customerName']); ?></strong>? This cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="customerDeleted" class="btn btn-danger btn-sm">Delete</button>
      </div>
    </form>
  </div></div>
</div>
