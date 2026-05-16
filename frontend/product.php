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

<style>
/* ── PAGE BODY ───────────────────────────────────────────────── */
.page-body {
  background: #f5f4f9;
  min-height: calc(100vh - 56px);
  padding: 28px 28px 48px;
}

/* ── SECTION HEADER ──────────────────────────────────────────── */
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 22px;
}
.section-title {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--col-navy);
  letter-spacing: -.01em;
  display: flex;
  align-items: center;
  gap: 10px;
}
.section-title .title-dot {
  width: 10px; height: 10px;
  background: var(--col-yellow);
  border-radius: 50%;
  display: inline-block;
}

/* ── PRIMARY BUTTON ──────────────────────────────────────────── */
.btn-add {
  background: var(--col-yellow);
  color: var(--col-navy);
  border: none;
  font-weight: 700;
  border-radius: var(--radius-pill);
  padding: 9px 22px;
  font-size: .87rem;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: background .18s, transform .14s, box-shadow .18s;
  box-shadow: 0 3px 12px var(--col-yellow-30);
}
.btn-add:hover {
  background: #f5ce28;
  color: var(--col-navy);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px var(--col-yellow-30);
}

/* ── CARD ────────────────────────────────────────────────────── */
.card-ev {
  background: #fff;
  border-radius: var(--radius-card);
  box-shadow: var(--shadow-card);
  border: none;
  overflow: hidden;
}

/* ── TABLE ───────────────────────────────────────────────────── */
.table-ev {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: .875rem;
}
.table-ev thead th {
  background: var(--col-navy);
  color: var(--col-yellow);
  font-weight: 700;
  letter-spacing: .04em;
  font-size: .75rem;
  text-transform: uppercase;
  padding: 13px 14px;
  border: none;
  white-space: nowrap;
}
.table-ev thead th:first-child { border-radius: 0; }
.table-ev tbody tr {
  transition: background .15s;
}
.table-ev tbody tr:nth-child(even) { background: var(--col-mint); }
.table-ev tbody tr:hover { background: var(--col-yellow-30); }
.table-ev tbody td {
  padding: 11px 14px;
  border-bottom: 1px solid rgba(38,35,65,.06);
  vertical-align: middle;
  color: var(--col-navy);
}

/* Product image thumbnail */
.product-thumb {
  width: 40px; height: 40px;
  border-radius: 10px;
  object-fit: cover;
  border: 2px solid var(--col-mint);
  background: #f5f4f9;
}
.product-thumb-placeholder {
  width: 40px; height: 40px;
  border-radius: 10px;
  background: var(--col-mint);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: var(--col-muted);
  font-size: .95rem;
  border: 2px solid #e2f0f0;
}

/* ── BADGES ──────────────────────────────────────────────────── */
.badge-ev {
  display: inline-block;
  padding: 4px 12px;
  border-radius: var(--radius-pill);
  font-size: .72rem;
  font-weight: 700;
  letter-spacing: .04em;
  text-transform: uppercase;
}
.badge-active   { background: rgba(45,190,138,.14); color: var(--col-success); }
.badge-inactive { background: rgba(224,82,82,.12);  color: var(--col-danger);  }
.badge-low      { background: rgba(240,168,71,.15); color: var(--col-warning); }

/* ── ACTION BUTTONS ──────────────────────────────────────────── */
.btn-act {
  width: 30px; height: 30px;
  border-radius: 8px;
  border: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: .8rem;
  cursor: pointer;
  transition: transform .13s, opacity .13s;
}
.btn-act:hover { transform: scale(1.12); opacity: .85; }
.btn-act-edit   { background: var(--col-navy-10); color: var(--col-navy); }
.btn-act-del    { background: rgba(224,82,82,.12); color: var(--col-danger); }
.btn-act-ok     { background: rgba(45,190,138,.12); color: var(--col-success); }

/* ── MODAL ───────────────────────────────────────────────────── */
.modal-content {
  border: none;
  border-radius: var(--radius-card);
  overflow: hidden;
  box-shadow: 0 24px 64px rgba(38,35,65,.22);
}
.modal-header-ev {
  background: var(--col-navy);
  color: #fff;
  padding: 18px 24px;
}
.modal-header-ev .modal-title {
  font-weight: 800;
  font-size: .97rem;
  display: flex;
  align-items: center;
  gap: 8px;
}
.modal-header-ev .modal-title i { color: var(--col-yellow); }
.modal-header-danger  { background: var(--col-danger); }
.modal-header-success { background: var(--col-success); }
.modal-body { padding: 24px; }
.modal-footer { padding: 14px 24px; border-top: 1px solid var(--col-col-mint); background: #fafafa; }

.form-label {
  font-size: .78rem;
  font-weight: 700;
  color: var(--col-navy);
  letter-spacing: .03em;
  text-transform: uppercase;
  margin-bottom: 5px;
}
.form-control, .form-select {
  border-radius: 10px;
  border: 1.5px solid #ddd;
  font-size: .875rem;
  padding: 9px 13px;
  color: var(--col-navy);
  transition: border-color .15s, box-shadow .15s;
}
.form-control:focus, .form-select:focus {
  border-color: var(--col-yellow);
  box-shadow: 0 0 0 3px var(--col-yellow-30);
  outline: none;
}

/* ── DATATABLE overrides ─────────────────────────────────────── */
div.dataTables_wrapper .dataTables_filter input {
  border-radius: 10px;
  border: 1.5px solid #ddd;
  padding: 7px 13px;
  font-size: .85rem;
  margin-left: 6px;
}
div.dataTables_wrapper .dataTables_length select {
  border-radius: 8px;
  border: 1.5px solid #ddd;
  padding: 5px 10px;
  font-size: .85rem;
}
div.dataTables_wrapper .dataTables_info { font-size: .8rem; color: var(--col-muted); }
div.dataTables_wrapper .dataTables_paginate .paginate_button {
  border-radius: 8px !important;
  font-size: .82rem !important;
}
div.dataTables_wrapper .dataTables_paginate .paginate_button.current,
div.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
  background: var(--col-navy) !important;
  color: var(--col-yellow) !important;
  border-color: var(--col-navy) !important;
}
div.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
  background: var(--col-yellow-30) !important;
  color: var(--col-navy) !important;
  border-color: transparent !important;
}

/* expiry colors */
.expiry-expired { color: var(--col-danger); font-weight: 700; }
.expiry-soon    { color: var(--col-warning); font-weight: 700; }

/* btn-secondary override in modals */
.btn-secondary {
  background: var(--col-navy-10);
  color: var(--col-navy);
  border: none;
  border-radius: var(--radius-pill);
  font-weight: 600;
  font-size: .87rem;
  padding: 9px 20px;
}
.btn-secondary:hover { background: rgba(38,35,65,.14); color: var(--col-navy); }
.btn-danger-ev {
  background: var(--col-danger);
  color: #fff;
  border: none;
  border-radius: var(--radius-pill);
  font-weight: 700;
  font-size: .87rem;
  padding: 9px 22px;
}
.btn-success-ev {
  background: var(--col-success);
  color: #fff;
  border: none;
  border-radius: var(--radius-pill);
  font-weight: 700;
  font-size: .87rem;
  padding: 9px 22px;
}

/* image preview in edit */
.img-current-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
  background: var(--col-mint);
  border-radius: 10px;
  padding: 8px 12px;
  margin-bottom: 8px;
}
.img-new-preview {
  background: var(--col-mint);
  border-radius: 10px;
  padding: 8px 12px;
  margin-top: 8px;
  display: flex;
  align-items: center;
  gap: 10px;
}
</style>

<?php
$topbarTitle = 'Product Management';
$topbarIcon  = 'bi-box-seam';
$topbarSub   = 'Add, edit, and manage store products';
include 'topbar.php';
?>

<?php
$alerts=['savedData'=>['success','Product Added','Product has been added successfully.'],'updatedProduct'=>['success','Product Updated','Product information updated.'],'productDeleted'=>['success','Deactivated','Product set to inactive.'],'productReactivated'=>['success','Reactivated','Product is now active.'],'barcodeExists'=>['error','Barcode Exists','This barcode is already registered.'],'emptyFields'=>['warning','Required Fields','Please fill in all required fields.']];
foreach($alerts as $k=>[$i,$t,$tx]) if(isset($_GET[$k])) echo "<script>Swal.fire({icon:'$i',title:'$t',text:'$tx',timer:2500}).then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";
?>

<div class="page-body">
    <div class="section-header">
        <div class="section-title">
            <span class="title-dot"></span>
            Products
        </div>
        <?php if($_SESSION['roleName']==='Admin'): ?>
        <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="bi bi-plus-circle"></i>Add Product
        </button>
        <?php endif; ?>
    </div>

    <div class="card-ev">
        <div class="p-3">
            <table id="productTable" class="table-ev" style="width:100%">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Barcode</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Cost</th>
                        <th>Stock</th>
                        <th>Reorder</th>
                        <th>Expiry</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($products as $row):
                    $stockClass  = $row['stock_quantity'] == 0 ? 'badge-ev badge-inactive' : ($row['stock_quantity'] <= $row['reorder_level'] ? 'badge-ev badge-low' : 'badge-ev badge-active');
                    $expiryClass = '';
                    if($row['expiry_date']){
                        $daysLeft = (strtotime($row['expiry_date']) - time()) / 86400;
                        if($daysLeft < 0) $expiryClass = 'expiry-expired';
                        elseif($daysLeft <= 30) $expiryClass = 'expiry-soon';
                    }
                ?>
                <tr>
                    <td style="text-align:center;">
                        <?php if(!empty($row['product_image'])): ?>
                        <img src="../<?php echo htmlspecialchars($row['product_image']); ?>" alt="" class="product-thumb">
                        <?php else: ?>
                        <span class="product-thumb-placeholder"><i class="bi bi-image"></i></span>
                        <?php endif; ?>
                    </td>
                    <td><small style="color:var(--col-muted);font-family:monospace;"><?php echo htmlspecialchars($row['barcode']??'—'); ?></small></td>
                    <td style="font-weight:700;"><?php echo htmlspecialchars($row['productName']); ?></td>
                    <td style="color:var(--col-muted);"><?php echo htmlspecialchars($row['categoryName']); ?></td>
                    <td style="font-weight:600;">₱<?php echo number_format($row['price'],2); ?></td>
                    <td style="color:var(--col-muted);">₱<?php echo number_format($row['cost'],2); ?></td>
                    <td style="text-align:center;"><span class="<?php echo $stockClass; ?>"><?php echo $row['stock_quantity']; ?></span></td>
                    <td style="text-align:center;color:var(--col-muted);"><?php echo $row['reorder_level']; ?></td>
                    <td class="<?php echo $expiryClass; ?>"><?php echo $row['expiry_date'] ? date('M d, Y',strtotime($row['expiry_date'])) : '—'; ?></td>
                    <td style="text-align:center;"><span class="badge-ev <?php echo $row['status']==='Active'?'badge-active':'badge-inactive'; ?>"><?php echo $row['status']; ?></span></td>
                    <td style="text-align:center;">
                        <?php if($_SESSION['roleName']==='Admin'): ?>
                        <div style="display:flex;gap:5px;justify-content:center;">
                        <button class="btn-act btn-act-edit" onclick="openEditProduct(<?php echo $row['productID']; ?>)" title="Edit"><i class="bi bi-pencil"></i></button>
                        <?php if($row['status']==='Active'): ?>
                        <button class="btn-act btn-act-del" onclick="openDeactivateProduct(<?php echo $row['productID']; ?>, '<?php echo addslashes($row['productName']); ?>')" title="Deactivate"><i class="bi bi-x-circle"></i></button>
                        <?php else: ?>
                        <button class="btn-act btn-act-ok" onclick="openReactivateProduct(<?php echo $row['productID']; ?>, '<?php echo addslashes($row['productName']); ?>')" title="Reactivate"><i class="bi bi-arrow-clockwise"></i></button>
                        <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <span style="font-size:.75rem;color:var(--col-muted);">View only</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ==================== SHARED MODALS ==================== -->

<?php if($_SESSION['roleName']==='Admin'): ?>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" action="../backend/productAuth.php">
      <?php csrf_field(); ?>
      <div class="modal-header modal-header-ev">
        <h5 class="modal-title"><i class="bi bi-plus-circle"></i>Add New Product</h5>
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
        <button type="submit" name="productSave" class="btn btn-add">Save Product</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" action="../backend/productAuth.php" enctype="multipart/form-data">
      <?php csrf_field(); ?>
      <div class="modal-header modal-header-ev">
        <h5 class="modal-title"><i class="bi bi-pencil-square"></i>Edit Product</h5>
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
            <label class="form-label">Product Image <small style="font-weight:400;text-transform:none;color:var(--col-muted);">(leave blank to keep current, max 2MB)</small></label>
            <div id="editCurrentImgWrap" class="img-current-wrap" style="display:none;">
              <img id="editCurrentImg" src="" alt="Current" class="product-thumb">
              <small style="color:var(--col-muted);">Current image</small>
            </div>
            <input type="file" name="product_image" id="editProductImage" class="form-control" accept="image/*" onchange="previewEditModalImage(this)">
            <div id="editImgPreviewWrap" class="img-new-preview" style="display:none;">
              <img id="editImgPreviewImg" src="" alt="New preview" class="product-thumb">
              <small style="color:var(--col-muted);">New image preview</small>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="productUpdate" class="btn btn-add">Update Product</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Deactivate Modal -->
<div class="modal fade" id="deactivateProductModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/productAuth.php">
      <?php csrf_field(); ?>
      <div class="modal-header modal-header-danger text-white">
        <h5 class="modal-title" style="font-weight:800;font-size:.97rem;"><i class="bi bi-x-circle me-2"></i>Deactivate Product</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="productID" id="deactivateProductID">
        <p style="color:var(--col-navy);margin-bottom:6px;">Deactivate <strong id="deactivateProductName"></strong>?</p>
        <p style="color:var(--col-muted);font-size:.85rem;margin:0;">It will be hidden from POS and marked as Inactive.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="productDelete" class="btn btn-danger-ev">Deactivate</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Reactivate Modal -->
<div class="modal fade" id="reactivateProductModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/productAuth.php">
      <?php csrf_field(); ?>
      <div class="modal-header modal-header-success text-white">
        <h5 class="modal-title" style="font-weight:800;font-size:.97rem;"><i class="bi bi-arrow-clockwise me-2"></i>Reactivate Product</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="productID" id="reactivateProductID">
        <p style="color:var(--col-navy);margin-bottom:6px;">Reactivate <strong id="reactivateProductName"></strong>?</p>
        <p style="color:var(--col-muted);font-size:.85rem;margin:0;">Product will be marked as Active and will appear in POS.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="productReactivate" class="btn btn-success-ev">Reactivate</button>
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

$(document).ready(function(){
    $('#productTable').DataTable({
        pageLength: 25,
        language: {
            search: '',
            searchPlaceholder: 'Search products…',
        },
        columnDefs: [{ orderable: false, targets: [0, 10] }]
    });
});

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
    document.getElementById('editProductImage').value = '';
    document.getElementById('editImgPreviewWrap').style.display = 'none';
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
        reader.onload = function(e){ img.src = e.target.result; wrap.style.display='flex'; }
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

<!-- ── Pusher Real-time ───────────────────────────────────── -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
    const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>
</body></html>