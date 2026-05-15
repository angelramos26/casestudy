<?php
// supplierModalAuth.php
// Renders per-row Edit and Delete modals for each $row (supplier row).
// Must be included inside the while($row = $result->fetch_assoc()) loop.
?>

<!-- Edit Supplier Modal -->
<div class="modal fade" id="editSupplierModal<?php echo $row['supplierID']; ?>" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/supplierAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title"><i class="bi bi-pencil me-1"></i>Edit Supplier – <?php echo htmlspecialchars($row['companyName']); ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="supplierID" value="<?php echo $row['supplierID']; ?>">
        <div class="mb-3"><label class="form-label fw-semibold small">Company Name <span class="text-danger">*</span></label><input type="text" name="companyName" value="<?php echo htmlspecialchars($row['companyName']); ?>" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-semibold small">Contact Person <span class="text-danger">*</span></label><input type="text" name="supplierName" value="<?php echo htmlspecialchars($row['supplierName']); ?>" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-semibold small">Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" class="form-control"></div>
        <div class="mb-3"><label class="form-label fw-semibold small">Contact No</label><input type="text" name="contactNo" value="<?php echo htmlspecialchars($row['contactNo']); ?>" class="form-control"></div>
        <div class="mb-3"><label class="form-label fw-semibold small">Address</label><textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($row['address']??''); ?></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="supplierUpdate" class="btn btn-ev">Update</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Delete Supplier Modal -->
<div class="modal fade" id="deleteSupplierModal<?php echo $row['supplierID']; ?>" tabindex="-1">
  <div class="modal-dialog modal-sm"><div class="modal-content">
    <form method="POST" action="../backend/supplierAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-trash me-1"></i>Delete Supplier</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="supplierID" value="<?php echo $row['supplierID']; ?>">
        <p>Remove <strong><?php echo htmlspecialchars($row['companyName']); ?></strong>? This cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="supplierDeleted" class="btn btn-danger btn-sm">Delete</button>
      </div>
    </form>
  </div></div>
</div>
