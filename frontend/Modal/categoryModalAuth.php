<?php
// categoryModalAuth.php
// Renders per-row Edit and Delete modals for each $r (category row).
// Must be included inside the while($r = $cats->fetch_assoc()) loop.
?>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCat<?php echo $r['categoryID']; ?>" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/categoryAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title">Edit Category</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="categoryID" value="<?php echo $r['categoryID']; ?>">
        <label class="form-label">Category Name *</label>
        <input type="text" name="categoryName" value="<?php echo htmlspecialchars($r['categoryName']); ?>" class="form-control" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="catUpdate" class="btn btn-ev">Update</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="delCat<?php echo $r['categoryID']; ?>" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/categoryAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Delete Category</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="categoryID" value="<?php echo $r['categoryID']; ?>">
        <p>Delete <strong><?php echo htmlspecialchars($r['categoryName']); ?></strong>? This cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="catDelete" class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div></div>
</div>
