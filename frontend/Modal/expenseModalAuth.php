<?php
// expenseModalAuth.php
// Contains the Add Expense Category and Add Expense modals.
// Rendered once outside the data loop.
// Requires: $categories (MySQLi result, will be rewound).
// Add Expense modal is wrapped in role check inside this file.
?>

<!-- Add Expense Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
  <div class="modal-dialog modal-sm"><div class="modal-content">
    <form method="POST" action="../backend/expenseAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title">Add Expense Category</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label fw-semibold small">Category Name <span class="text-danger">*</span></label><input type="text" name="categoryName" class="form-control" required></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="addExpenseCategory" class="btn btn-ev btn-sm">Save</button>
      </div>
    </form>
  </div></div>
</div>

<?php if($_SESSION['roleName']==='Admin'): ?>
<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/expenseAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title"><i class="bi bi-wallet2 me-1"></i>Record Expense</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label fw-semibold small">Category <span class="text-danger">*</span></label>
          <select name="expenseCategoryID" class="form-select" required>
            <option value="">— Select Category —</option>
            <?php $categories->data_seek(0); while($c=$categories->fetch_assoc()): ?>
            <option value="<?php echo $c['expenseCategoryID']; ?>"><?php echo htmlspecialchars($c['categoryName']); ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label fw-semibold small">Amount (₱) <span class="text-danger">*</span></label><input type="number" name="amount" step="0.01" min="0.01" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-semibold small">Description</label><input type="text" name="description" class="form-control" placeholder="What was this for?"></div>
        <div class="mb-3"><label class="form-label fw-semibold small">Date <span class="text-danger">*</span></label><input type="date" name="expense_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="expenseSave" class="btn btn-ev">Save Expense</button>
      </div>
    </form>
  </div></div>
</div>
<?php endif; ?>
