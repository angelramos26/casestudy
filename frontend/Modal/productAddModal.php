<?php
// productAddModal.php
// The "Add Product" modal rendered once outside the data loop.
// Requires: $categories (MySQLi result, will be rewound).
// Wrap in role check: <?php if($_SESSION['roleName']==='Admin'): ?> ... <?php endif; ?>
?>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
<script>
function previewAddImage(input){
  var preview = document.getElementById('addImgPreview');
  var img = document.getElementById('addImgPreviewImg');
  if(input.files && input.files[0]){
    var reader = new FileReader();
    reader.onload = function(e){ img.src = e.target.result; preview.style.display='block'; }
    reader.readAsDataURL(input.files[0]);
  } else { preview.style.display='none'; }
}
</script>
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" action="../backend/productAuth.php" enctype="multipart/form-data">
        <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title"><i class="bi bi-plus-circle me-1"></i>Add New Product</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Product Name *</label><input type="text" name="productName" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label">Barcode</label><input type="text" name="barcode" class="form-control"></div>
          <div class="col-md-6"><label class="form-label">Category *</label>
            <select name="categoryID" class="form-select" required>
              <option value="">— Select —</option>
              <?php $categories->data_seek(0); while($c=$categories->fetch_assoc()): ?>
              <option value="<?php echo $c['categoryID']; ?>"><?php echo htmlspecialchars($c['categoryName']); ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-3"><label class="form-label">Price (₱) *</label><input type="number" step="0.01" name="price" class="form-control" required min="0"></div>
          <div class="col-md-3"><label class="form-label">Cost (₱) *</label><input type="number" step="0.01" name="cost" class="form-control" required min="0"></div>
          <div class="col-md-3"><label class="form-label">Initial Stock</label><input type="number" name="stock_quantity" class="form-control" value="0" min="0"></div>
          <div class="col-md-3"><label class="form-label">Reorder Level</label><input type="number" name="reorder_level" class="form-control" value="10" min="0"></div>
          <div class="col-md-3"><label class="form-label">Expiry Date</label><input type="date" name="expiry_date" class="form-control"></div>
          <div class="col-md-3"><label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option>Active</option><option>Inactive</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Product Image <small class="text-muted">(optional, max 2MB)</small></label>
            <input type="file" name="product_image" class="form-control" accept="image/*" onchange="previewAddImage(this)">
            <div id="addImgPreview" class="mt-2" style="display:none;">
              <img id="addImgPreviewImg" src="" alt="Preview" style="max-height:100px;max-width:180px;border-radius:8px;border:1.5px solid #b3ddd2;object-fit:cover;">
              <small class="ms-2 text-muted">Preview</small>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="productSave" class="btn btn-ev">Save Product</button>
      </div>
    </form>
  </div></div>
</div>
