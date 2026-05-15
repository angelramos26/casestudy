<?php
// productModalAuth.php
// Renders per-row Edit and Deactivate modals for each $row (product row).
// Must be included inside the while($row = $result->fetch_assoc()) loop.
// Requires: $row (current product row), $categories (MySQLi result, will be rewound).
?>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProd<?php echo $row['productID']; ?>" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" action="../backend/productAuth.php" enctype="multipart/form-data">
        <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title">Edit Product – <?php echo htmlspecialchars($row['productName']); ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="productID" value="<?php echo $row['productID']; ?>">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Product Name *</label><input type="text" name="productName" value="<?php echo htmlspecialchars($row['productName']); ?>" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label">Barcode</label><input type="text" name="barcode" value="<?php echo htmlspecialchars($row['barcode']??''); ?>" class="form-control"></div>
          <div class="col-md-6"><label class="form-label">Category *</label>
            <select name="categoryID" class="form-select" required>
              <?php $categories->data_seek(0); while($c=$categories->fetch_assoc()): ?>
              <option value="<?php echo $c['categoryID']; ?>" <?php echo $c['categoryID']==$row['categoryID']?'selected':''; ?>><?php echo htmlspecialchars($c['categoryName']); ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-3"><label class="form-label">Price (₱) *</label><input type="number" step="0.01" name="price" value="<?php echo $row['price']; ?>" class="form-control" required></div>
          <div class="col-md-3"><label class="form-label">Cost (₱) *</label><input type="number" step="0.01" name="cost" value="<?php echo $row['cost']; ?>" class="form-control" required></div>
          <div class="col-md-3"><label class="form-label">Reorder Level</label><input type="number" name="reorder_level" value="<?php echo $row['reorder_level']; ?>" class="form-control" min="0"></div>
          <div class="col-md-3"><label class="form-label">Expiry Date</label><input type="date" name="expiry_date" value="<?php echo $row['expiry_date']??''; ?>" class="form-control"></div>
          <div class="col-md-3"><label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option <?php echo $row['status']==='Active'  ?'selected':''; ?>>Active</option>
              <option <?php echo $row['status']==='Inactive'?'selected':''; ?>>Inactive</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Product Image <small class="text-muted">(leave blank to keep current, max 2MB)</small></label>
            <?php if(!empty($row['product_image'])): ?>
            <div class="mb-2 d-flex align-items-center gap-2">
              <img src="../<?php echo htmlspecialchars($row['product_image']); ?>" alt="Current" style="height:60px;width:60px;object-fit:cover;border-radius:8px;border:1.5px solid #b3ddd2;">
              <small class="text-muted">Current image</small>
            </div>
            <?php endif; ?>
            <input type="file" name="product_image" class="form-control" accept="image/*"
              onchange="previewEditImage(this, <?php echo $row['productID']; ?>)">
            <div id="editImgPreview<?php echo $row['productID']; ?>" class="mt-2" style="display:none;">
              <img id="editImgPreviewImg<?php echo $row['productID']; ?>" src="" alt="New preview"
                style="max-height:80px;max-width:160px;border-radius:8px;border:1.5px solid #c3b1e1;object-fit:cover;">
              <small class="ms-2 text-muted">New image preview</small>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="productUpdate" class="btn btn-ev">Update Product</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Deactivate Product Modal -->
<div class="modal fade" id="delProd<?php echo $row['productID']; ?>" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/productAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Deactivate Product</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="productID" value="<?php echo $row['productID']; ?>">
        <p>Deactivate <strong><?php echo htmlspecialchars($row['productName']); ?></strong>?</p>
        <p class="text-muted small">It will be hidden from POS and marked as Inactive.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="productDelete" class="btn btn-danger">Deactivate</button>
      </div>
    </form>
  </div></div>
</div>

<script>
function previewEditImage(input, id){
  var preview = document.getElementById('editImgPreview' + id);
  var img = document.getElementById('editImgPreviewImg' + id);
  if(input.files && input.files[0]){
    var reader = new FileReader();
    reader.onload = function(e){ img.src = e.target.result; preview.style.display='block'; }
    reader.readAsDataURL(input.files[0]);
  } else { preview.style.display='none'; }
}
</script>
