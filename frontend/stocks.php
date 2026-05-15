<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';
session_start();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if(!in_array($_SESSION['roleName'], ['Admin','Owner','Cashier'])){ header("Location: dashboard.php"); exit(); }
$pageTitle = "Inventory – 7Evelyn POS";
$suppliers = $conn->query("SELECT supplierID, companyName FROM supplier WHERE dateDeleted IS NULL ORDER BY companyName");
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>
<div class="topbar no-print">
    <h5><i class="bi bi-archive me-2" style="color:var(--ev-purple);"></i>Inventory Management</h5>
    <div class="ms-auto"><span class="text-muted small"><?php echo htmlspecialchars($_SESSION['userName']); ?></span></div>
</div>
<?php
$msgs=['stockIn'=>['success','Stock In','Stock added successfully.'],'stockOut'=>['success','Stock Out','Stock removed.'],'stockAdjust'=>['success','Adjusted','Stock adjusted.'],'insufficient'=>['error','Insufficient Stock','Not enough stock for removal.']];
foreach($msgs as $k=>[$i,$t,$tx]) if(isset($_GET[$k])) echo "<script>Swal.fire({icon:'$i',title:'$t',text:'$tx',timer:2000}).then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";
?>
<div class="page-body">
    <!-- Current Inventory Table -->
    <div class="d-flex justify-content-between mb-3 align-items-center">
        <h5 class="fw-bold mb-0" style="color:#004a38;">Current Stock Levels</h5>
        <?php if($_SESSION['roleName']==='Admin'): ?>
        <div class="d-flex gap-2">
            <button class="btn btn-ev" data-bs-toggle="modal" data-bs-target="#stockInModal"><i class="bi bi-plus-circle me-1"></i>Stock In</button>
            <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#stockOutModal"><i class="bi bi-dash-circle me-1"></i>Stock Out</button>
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#adjustModal"><i class="bi bi-sliders me-1"></i>Adjust</button>
        </div>
        <?php endif; ?>
    </div>

    <div class="card card-shadow mb-4">
        <div class="card-body">
            <table id="invTable" class="table table-bordered table-striped text-center">
                <thead style="background:var(--ev-gradient);color:#fff;">
                    <tr><th>Product</th><th>Category</th><th>Stock</th><th>Reorder</th><th>Cost</th><th>Price</th><th>Expiry</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php
                $prods = $conn->query("SELECT p.*, c.categoryName FROM product p JOIN category c ON p.categoryID=c.categoryID WHERE p.status='Active' ORDER BY p.stock_quantity ASC");
                while($r=$prods->fetch_assoc()):
                    $sc = $r['stock_quantity'] == 0 ? 'badge-inactive' : ($r['stock_quantity'] <= $r['reorder_level'] ? 'badge-low' : 'badge-active');
                    $exClass = '';
                    if($r['expiry_date']){
                        $d = (strtotime($r['expiry_date'])-time())/86400;
                        if($d < 0) $exClass='text-danger fw-bold';
                        elseif($d <= 30) $exClass='text-warning fw-bold';
                    }
                ?>
                <tr>
                    <td class="text-start fw-semibold"><?php echo htmlspecialchars($r['productName']); ?></td>
                    <td><?php echo htmlspecialchars($r['categoryName']); ?></td>
                    <td><span class="<?php echo $sc; ?>"><?php echo $r['stock_quantity']; ?></span></td>
                    <td><?php echo $r['reorder_level']; ?></td>
                    <td>₱<?php echo number_format($r['cost'],2); ?></td>
                    <td>₱<?php echo number_format($r['price'],2); ?></td>
                    <td class="<?php echo $exClass; ?>"><?php echo $r['expiry_date'] ? date('M d, Y',strtotime($r['expiry_date'])) : '—'; ?></td>
                    <td><?php echo $r['stock_quantity'] == 0 ? '<span class="badge-inactive">OUT</span>' : ($r['stock_quantity'] <= $r['reorder_level'] ? '<span class="badge-low">LOW</span>' : '<span class="badge-active">OK</span>'); ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Stock Movement Log -->
    <h5 class="fw-bold mb-3" style="color:#004a38;">Stock Movement History</h5>
    <div class="card card-shadow">
        <div class="card-body">
            <table id="logTable" class="table table-bordered table-striped text-center">
                <thead style="background:var(--ev-gradient);color:#fff;">
                    <tr><th>Date</th><th>Product</th><th>Type</th><th>Qty</th><th>Cost</th><th>Supplier</th><th>Encoded by</th><th>Notes</th></tr>
                </thead>
                <tbody>
                <?php
                $logs = $conn->query("SELECT st.*, p.productName, CONCAT(u.givenName,' ',u.surName) AS userName, s.companyName FROM stocks st JOIN product p ON st.productID=p.productID JOIN users u ON st.userID=u.userID LEFT JOIN supplier s ON st.supplierID=s.supplierID ORDER BY st.dateAdded DESC LIMIT 100");
                while($r=$logs->fetch_assoc()):
                    $tc = $r['type']==='IN' ? 'badge-active' : ($r['type']==='OUT' ? 'badge-inactive' : 'badge-pending');
                ?>
                <tr>
                    <td><small><?php echo date('M d, Y H:i',strtotime($r['dateAdded'])); ?></small></td>
                    <td class="text-start"><?php echo htmlspecialchars($r['productName']); ?></td>
                    <td><span class="<?php echo $tc; ?>"><?php echo $r['type']; ?></span></td>
                    <td><strong><?php echo $r['qty']; ?></strong></td>
                    <td>₱<?php echo number_format($r['cost'],2); ?></td>
                    <td><?php echo htmlspecialchars($r['companyName']??'—'); ?></td>
                    <td><?php echo htmlspecialchars($r['userName']); ?></td>
                    <td class="text-muted small"><?php echo htmlspecialchars($r['notes']??'—'); ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if($_SESSION['roleName']==='Admin'): ?>
<!-- Stock In Modal -->
<div class="modal fade" id="stockInModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/stocksAuth.php">
            <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title"><i class="bi bi-plus-circle me-1"></i>Stock In</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Product *</label>
          <select name="productID" class="form-select" required>
            <option value="">— Select Product —</option>
            <?php $ps=$conn->query("SELECT productID,productName,stock_quantity FROM product WHERE status='Active' ORDER BY productName"); while($p=$ps->fetch_assoc()): ?>
            <option value="<?php echo $p['productID']; ?>"><?php echo htmlspecialchars($p['productName']); ?> (Stock: <?php echo $p['stock_quantity']; ?>)</option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label">Quantity *</label><input type="number" name="qty" class="form-control" min="1" required></div>
        <div class="mb-3"><label class="form-label">Cost per Unit (₱)</label><input type="number" step="0.01" name="cost" class="form-control" min="0" value="0"></div>
        <div class="mb-3"><label class="form-label">Supplier</label>
          <select name="supplierID" class="form-select">
            <option value="">— No Supplier —</option>
            <?php $suppliers->data_seek(0); while($s=$suppliers->fetch_assoc()): ?>
            <option value="<?php echo $s['supplierID']; ?>"><?php echo htmlspecialchars($s['companyName']); ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="stockIn" class="btn btn-ev">Add Stock</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Stock Out Modal -->
<div class="modal fade" id="stockOutModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/stocksAuth.php">
            <?php csrf_field(); ?>
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-dash-circle me-1"></i>Stock Out</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Product *</label>
          <select name="productID" class="form-select" required>
            <option value="">— Select Product —</option>
            <?php $ps2=$conn->query("SELECT productID,productName,stock_quantity FROM product WHERE status='Active' AND stock_quantity > 0 ORDER BY productName"); while($p=$ps2->fetch_assoc()): ?>
            <option value="<?php echo $p['productID']; ?>"><?php echo htmlspecialchars($p['productName']); ?> (Stock: <?php echo $p['stock_quantity']; ?>)</option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label">Quantity *</label><input type="number" name="qty" class="form-control" min="1" required></div>
        <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2" placeholder="Reason for stock out..."></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="stockOut" class="btn btn-danger">Remove Stock</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Adjust Modal -->
<div class="modal fade" id="adjustModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/stocksAuth.php">
            <?php csrf_field(); ?>
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title"><i class="bi bi-sliders me-1"></i>Stock Adjustment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Product *</label>
          <select name="productID" class="form-select" required>
            <option value="">— Select Product —</option>
            <?php $ps3=$conn->query("SELECT productID,productName,stock_quantity FROM product WHERE status='Active' ORDER BY productName"); while($p=$ps3->fetch_assoc()): ?>
            <option value="<?php echo $p['productID']; ?>"><?php echo htmlspecialchars($p['productName']); ?> (Stock: <?php echo $p['stock_quantity']; ?>)</option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label">New Stock Quantity *</label><input type="number" name="qty" class="form-control" min="0" required></div>
        <div class="mb-3"><label class="form-label">Reason *</label><textarea name="notes" class="form-control" rows="2" required placeholder="Reason for adjustment..."></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="stockAdjust" class="btn btn-ev">Apply Adjustment</button>
      </div>
    </form>
  </div></div>
</div>
<?php endif; ?>

</div></div>
<script>$(document).ready(function(){ $('#invTable').DataTable({pageLength:25,order:[[2,'asc']]}); $('#logTable').DataTable({pageLength:15,order:[[0,'desc']]}); });</script>

<!-- ── Pusher Real-time ──────────────────────────────────────────────────── -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
    const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>
<!-- ─────────────────────────────────────────────────────────────────────── -->

</body></html>
