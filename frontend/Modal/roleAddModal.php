<?php
// roleAddModal.php
// The "Add Role" modal rendered once outside the data loop.
?>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/roleAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title"><i class="bi bi-shield-plus me-1"></i>Add Role</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label fw-semibold small">Role Name <span class="text-danger">*</span></label><input type="text" name="roleName" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-semibold small">Description</label><input type="text" name="roleDesc" class="form-control" placeholder="e.g. Can only process sales"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="roleSave" class="btn btn-ev">Save Role</button>
      </div>
    </form>
  </div></div>
</div>
