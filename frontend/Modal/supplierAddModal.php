<?php
// supplierAddModal.php
// The "Add Supplier" modal rendered once outside the data loop.
// Wrap with role check: <?php if($_SESSION['roleName']==='Admin'): ?> ... <?php endif; ?>
?>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/supplierAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title"><i class="bi bi-truck me-1"></i>Add Supplier</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label fw-semibold small">Company Name <span class="text-danger">*</span></label><input type="text" name="companyName" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-semibold small">Contact Person <span class="text-danger">*</span></label><input type="text" name="supplierName" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-semibold small">Email</label><input type="email" name="email" class="form-control"></div>
        <div class="mb-3"><label class="form-label fw-semibold small">Contact No</label><input type="text" name="contactNo" class="form-control"></div>
        <div class="mb-3"><label class="form-label fw-semibold small">Address</label><textarea name="address" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="supplierSave" class="btn btn-ev">Save Supplier</button>
      </div>
    </form>
  </div></div>
</div>
