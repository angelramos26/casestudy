<?php
// categoryAddModal.php
// The "Add Category" modal rendered once outside the data loop.
// Wrap with role check: <?php if($_SESSION['roleName']==='Admin'): ?> ... <?php endif; ?>
?>

<!-- Add Category Modal -->
<div class="modal fade" id="addCatModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/categoryAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title">Add Category</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Category Name *</label>
        <input type="text" name="categoryName" class="form-control" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="catSave" class="btn btn-ev">Save</button>
      </div>
    </form>
  </div></div>
</div>
