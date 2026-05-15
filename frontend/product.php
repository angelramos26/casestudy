<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';

session_start();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if(!in_array($_SESSION['roleName'], ['Admin','Owner'])){ header("Location: dashboard.php"); exit(); }
$pageTitle = "Products – 7Evelyn POS";
$categories = $conn->query("SELECT * FROM category ORDER BY categoryName ASC");

// Fetch products
$productResult = $conn->query("SELECT p.*, c.categoryName FROM product p JOIN category c ON p.categoryID=c.categoryID ORDER BY p.productName ASC");
$products = [];
while($row = $productResult->fetch_assoc()) $products[] = $row;
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<div class="topbar no-print">
    <h5><i class="bi bi-box-seam me-2" style="color:var(--ev-purple);"></i>Product Management</h5>
    <div class="ms-auto"><span class="text-muted small"><?php echo htmlspecialchars($_SESSION['userName']); ?></span></div>
</div>

<?php
$alerts=['savedData'=>['success','Product Added','Product has been added successfully.'],'updatedProduct'=>['success','Product Updated','Product information updated.'],'productDeleted'=>['success','Deactivated','Product set to inactive.'],'productReactivated'=>['success','Reactivated','Product is now active.'],'barcodeExists'=>['error','Barcode Exists','This barcode is already registered.'],'emptyFields'=>['warning','Required Fields','Please fill in all required fields.']];
foreach($alerts as $k=>[$i,$t,$tx]) if(isset($_GET[$k])) echo "<script>Swal.fire({icon:'$i',title:'$t',text:'$tx',timer:2500}).then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";
?>

<div class="page-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0" style="color:#004a38;">Products</h5>
        <?php if($_SESSION['roleName']==='Admin'): ?>
        <button class="btn btn-ev" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="bi bi-plus-circle me-1"></i>Add Product
        </button>
        <?php endif; ?>
    </div>

    <div class="card card-shadow">
        <div class="card-body">
            <table id="productTable" class="table table-bordered table-striped text-center">
                <thead style="background:var(--ev-gradient);color:#fff;">
                    <tr><th>Image</th><th>Barcode</th><th>Product Name</th><th>Category</th><th>Price</th><th>Cost</th><th>Stock</th><th>Reorder</th><th>Expiry</th><th>Status</th><th width="120">Action</th></tr>
                </thead>
                <tbody>
                <?php foreach($products as $row):
                    $stockClass  = $row['stock_quantity'] == 0 ? 'badge-inactive' : ($row['stock_quantity'] <= $row['reorder_level'] ? 'badge-low' : 'badge-active');
                    $expiryClass = '';
                    if($row['expiry_date']){
                        $daysLeft = (strtotime($row['expiry_date']) - time()) / 86400;
                        if($daysLeft < 0) $expiryClass = 'text-danger fw-bold';
                        elseif($daysLeft <= 30) $expiryClass = 'text-warning fw-bold';
                    }
                ?>
                <tr>
                    <td>
                        <?php if(!empty($row['product_image'])): ?>
                        <img src="../<?php echo htmlspecialchars($row['product_image']); ?>" alt="" style="width:38px;height:38px;object-fit:cover;border-radius:7px;border:1px solid #b3ddd2;">
                        <?php else: ?>
                        <span style="display:inline-flex;width:38px;height:38px;border-radius:7px;background:#f0ede8;align-items:center;justify-content:center;"><i class="bi bi-image" style="color:#bbb;font-size:1rem;"></i></span>
                        <?php endif; ?>
                    </td>
                    <td><small><?php echo htmlspecialchars($row['barcode']??'—'); ?></small></td>
                    <td class="text-start fw-semibold"><?php echo htmlspecialchars($row['productName']); ?></td>
                    <td><?php echo htmlspecialchars($row['categoryName']); ?></td>
                    <td>₱<?php echo number_format($row['price'],2); ?></td>
                    <td>₱<?php echo number_format($row['cost'],2); ?></td>
                    <td><span class="<?php echo $stockClass; ?>"><?php echo $row['stock_quantity']; ?></span></td>
                    <td><?php echo $row['reorder_level']; ?></td>
                    <td class="<?php echo $expiryClass; ?>"><?php echo $row['expiry_date'] ? date('M d, Y',strtotime($row['expiry_date'])) : '—'; ?></td>
                    <td><span class="<?php echo $row['status']==='Active'?'badge-active':'badge-inactive'; ?>"><?php echo $row['status']; ?></span></td>
                    <td>
                        <?php if($_SESSION['roleName']==='Admin'): ?>
                        <button class="btn btn-sm btn-outline-primary" onclick="openEditProduct(<?php echo $row['productID']; ?>)" title="Edit"><i class="bi bi-pencil"></i></button>
                        <?php if($row['status']==='Active'): ?>
                        <button class="btn btn-sm btn-outline-danger" onclick="openDeactivateProduct(<?php echo $row['productID']; ?>, '<?php echo addslashes($row['productName']); ?>')" title="Deactivate"><i class="bi bi-x-circle"></i></button>
                        <?php else: ?>
                        <button class="btn btn-sm btn-outline-success" onclick="openReactivateProduct(<?php echo $row['productID']; ?>, '<?php echo addslashes($row['productName']); ?>')" title="Reactivate"><i class="bi bi-arrow-clockwise"></i></button>
                        <?php endif; ?>
                        <?php else: ?>
                        <span class="text-muted small">View only</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ==================== SHARED MODALS (outside table) ==================== -->

<?php if($_SESSION['roleName']==='Admin'): ?>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" action="../backend/productAuth.php">
            <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title"><i class="bi bi-plus-circle me-1"></i>Add New Product</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Product Name *</label><input type="text" name="productName" class="form-control" required></div>
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
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="productSave" class="btn btn-ev">Save Product</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Edit Product Modal (single shared) -->
<div class="modal fade" id="editProductModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" action="../backend/productAuth.php" enctype="multipart/form-data">
            <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title">Edit Product</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="productID" id="editProductID">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Product Name *</label><input type="text" name="productName" id="editProductName" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label">Barcode</label><input type="text" name="barcode" id="editBarcode" class="form-control"></div>
          <div class="col-md-6"><label class="form-label">Category *</label>
            <select name="categoryID" id="editCategoryID" class="form-select" required>
              <?php $categories->data_seek(0); while($c=$categories->fetch_assoc()): ?>
              <option value="<?php echo $c['categoryID']; ?>"><?php echo htmlspecialchars($c['categoryName']); ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-3"><label class="form-label">Price (₱) *</label><input type="number" step="0.01" name="price" id="editPrice" class="form-control" required></div>
          <div class="col-md-3"><label class="form-label">Cost (₱) *</label><input type="number" step="0.01" name="cost" id="editCost" class="form-control" required></div>
          <div class="col-md-3"><label class="form-label">Reorder Level</label><input type="number" name="reorder_level" id="editReorder" class="form-control" min="0"></div>
          <div class="col-md-3"><label class="form-label">Expiry Date</label><input type="date" name="expiry_date" id="editExpiry" class="form-control"></div>
          <div class="col-md-3"><label class="form-label">Status</label>
            <select name="status" id="editStatus" class="form-select">
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Product Image <small class="text-muted">(leave blank to keep current, max 2MB)</small></label>
            <div id="editCurrentImgWrap" class="mb-2 align-items-center gap-2" style="display:none;">
              <img id="editCurrentImg" src="" alt="Current" style="height:60px;width:60px;object-fit:cover;border-radius:8px;border:1.5px solid #b3ddd2;">
              <small class="text-muted">Current image</small>
            </div>
            <input type="file" name="product_image" id="editProductImage" class="form-control" accept="image/*" onchange="previewEditModalImage(this)">
            <div id="editImgPreviewWrap" class="mt-2" style="display:none;">
              <img id="editImgPreviewImg" src="" alt="New preview" style="max-height:80px;max-width:160px;border-radius:8px;border:1.5px solid #c3b1e1;object-fit:cover;">
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

<!-- Deactivate Modal (single shared) -->
<div class="modal fade" id="deactivateProductModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/productAuth.php">
            <?php csrf_field(); ?>
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Deactivate Product</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="productID" id="deactivateProductID">
        <p>Deactivate <strong id="deactivateProductName"></strong>?</p>
        <p class="text-muted small">It will be hidden from POS and marked as Inactive.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="productDelete" class="btn btn-danger">Deactivate</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Reactivate Modal (single shared) -->
<div class="modal fade" id="reactivateProductModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/productAuth.php">
            <?php csrf_field(); ?>
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-arrow-clockwise me-1"></i>Reactivate Product</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="productID" id="reactivateProductID">
        <p>Reactivate <strong id="reactivateProductName"></strong>?</p>
        <p class="text-muted small">Product will be marked as Active and will appear in POS.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="productReactivate" class="btn btn-success">Reactivate</button>
      </div>
    </form>
  </div></div>
</div>

<?php endif; ?>

<!-- Embed product data for JS -->
<script>
const productData = <?php
    $jsProds = [];
    foreach($products as $p){
        $jsProds[$p['productID']] = [
            'productID'    => $p['productID'],
            'productName'  => $p['productName'],
            'barcode'      => $p['barcode'] ?? '',
            'categoryID'   => $p['categoryID'],
            'price'        => $p['price'],
            'cost'         => $p['cost'],
            'reorder_level'=> $p['reorder_level'],
            'expiry_date'  => $p['expiry_date'] ?? '',
            'status'       => $p['status'],
            'product_image'=> $p['product_image'] ?? '',
        ];
    }
    echo json_encode($jsProds);
?>;

$(document).ready(function(){ $('#productTable').DataTable({pageLength:25}); });

function openEditProduct(id){
    const p = productData[id];
    document.getElementById('editProductID').value    = p.productID;
    document.getElementById('editProductName').value  = p.productName;
    document.getElementById('editBarcode').value      = p.barcode;
    document.getElementById('editCategoryID').value   = p.categoryID;
    document.getElementById('editPrice').value        = p.price;
    document.getElementById('editCost').value         = p.cost;
    document.getElementById('editReorder').value      = p.reorder_level;
    document.getElementById('editExpiry').value       = p.expiry_date;
    document.getElementById('editStatus').value       = p.status;
    // Reset image field
    document.getElementById('editProductImage').value = '';
    document.getElementById('editImgPreviewWrap').style.display = 'none';
    // Show current image if exists
    const imgWrap = document.getElementById('editCurrentImgWrap');
    const imgEl   = document.getElementById('editCurrentImg');
    if(p.product_image){
        imgEl.src = '../' + p.product_image;
        imgWrap.style.display = 'flex';
    } else {
        imgEl.src = '';
        imgWrap.style.display = 'none';
    }
    new bootstrap.Modal(document.getElementById('editProductModal')).show();
}

function previewEditModalImage(input){
    var wrap = document.getElementById('editImgPreviewWrap');
    var img  = document.getElementById('editImgPreviewImg');
    if(input.files && input.files[0]){
        var reader = new FileReader();
        reader.onload = function(e){ img.src = e.target.result; wrap.style.display='block'; }
        reader.readAsDataURL(input.files[0]);
    } else { wrap.style.display='none'; }
}

function openDeactivateProduct(id, name){
    document.getElementById('deactivateProductID').value        = id;
    document.getElementById('deactivateProductName').textContent = name;
    new bootstrap.Modal(document.getElementById('deactivateProductModal')).show();
}

function openReactivateProduct(id, name){
    document.getElementById('reactivateProductID').value        = id;
    document.getElementById('reactivateProductName').textContent = name;
    new bootstrap.Modal(document.getElementById('reactivateProductModal')).show();
}
</script>
</div></div>

<!-- ── Pusher Real-time ───────────────────────────────────────────────────── -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
    const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>
<!-- ─────────────────────────────────────────────────────────────────────── -->
</body></html>