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

<style>
/* ── PAGE BODY ───────────────────────────────────────────────── */
.page-body { background: #f5f4f9; min-height: calc(100vh - 56px); padding: 28px 28px 48px; }

/* ── SECTION HEADER ──────────────────────────────────────────── */
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; }
.section-title {
  font-size: 1.1rem; font-weight: 800; color: var(--col-navy);
  letter-spacing: -.01em; display: flex; align-items: center; gap: 10px;
}
.section-title .title-dot { width: 10px; height: 10px; background: var(--col-yellow); border-radius: 50%; display: inline-block; }

/* ── BUTTONS ─────────────────────────────────────────────────── */
.btn-ev {
  background: var(--col-yellow); color: var(--col-navy); border: none;
  font-weight: 700; border-radius: var(--radius-pill); padding: 9px 20px;
  font-size: .87rem; display: inline-flex; align-items: center; gap: 6px;
  transition: background .18s, transform .14s, box-shadow .18s;
  box-shadow: 0 3px 12px var(--col-yellow-30);
}
.btn-ev:hover { background: #f5ce28; color: var(--col-navy); transform: translateY(-1px); }

.btn-out {
  background: rgba(224,82,82,.1); color: var(--col-danger); border: 1.5px solid rgba(224,82,82,.25);
  font-weight: 700; border-radius: var(--radius-pill); padding: 8px 18px;
  font-size: .87rem; display: inline-flex; align-items: center; gap: 6px;
  transition: background .15s, transform .14s;
}
.btn-out:hover { background: rgba(224,82,82,.18); color: var(--col-danger); transform: translateY(-1px); }

.btn-adj {
  background: var(--col-navy-10); color: var(--col-navy); border: 1.5px solid rgba(38,35,65,.15);
  font-weight: 700; border-radius: var(--radius-pill); padding: 8px 18px;
  font-size: .87rem; display: inline-flex; align-items: center; gap: 6px;
  transition: background .15s, transform .14s;
}
.btn-adj:hover { background: rgba(38,35,65,.13); color: var(--col-navy); transform: translateY(-1px); }

.btn-secondary {
  background: var(--col-navy-10); color: var(--col-navy); border: none;
  border-radius: var(--radius-pill); font-weight: 600; font-size: .87rem; padding: 9px 20px;
}
.btn-secondary:hover { background: rgba(38,35,65,.14); color: var(--col-navy); }
.btn-danger-ev  { background: var(--col-danger);  color: #fff; border: none; border-radius: var(--radius-pill); font-weight: 700; font-size: .87rem; padding: 9px 22px; }
.btn-neutral-ev { background: var(--col-navy);    color: #fff; border: none; border-radius: var(--radius-pill); font-weight: 700; font-size: .87rem; padding: 9px 22px; }

/* ── CARD ────────────────────────────────────────────────────── */
.card-ev { background: #fff; border-radius: var(--radius-card); box-shadow: var(--shadow-card); border: none; overflow: hidden; margin-bottom: 28px; }

/* ── TABLE ───────────────────────────────────────────────────── */
.table-ev { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .875rem; }
.table-ev thead th {
  background: var(--col-navy); color: var(--col-yellow);
  font-weight: 700; letter-spacing: .04em; font-size: .73rem;
  text-transform: uppercase; padding: 13px 14px; border: none; white-space: nowrap;
}
.table-ev tbody tr { transition: background .15s; }
.table-ev tbody tr:nth-child(even) { background: var(--col-mint); }
.table-ev tbody tr:hover { background: var(--col-yellow-30); }
.table-ev tbody td {
  padding: 11px 14px; border-bottom: 1px solid rgba(38,35,65,.06);
  vertical-align: middle; color: var(--col-navy);
}

/* ── BADGES ──────────────────────────────────────────────────── */
.badge-ev { display: inline-block; padding: 4px 12px; border-radius: var(--radius-pill); font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
.badge-active   { background: rgba(45,190,138,.14);  color: var(--col-success); }
.badge-inactive { background: rgba(224,82,82,.12);   color: var(--col-danger);  }
.badge-low      { background: rgba(240,168,71,.15);  color: var(--col-warning); }
.badge-pending  { background: rgba(38,35,65,.10);    color: var(--col-navy);    }

.expiry-expired { color: var(--col-danger);  font-weight: 700; }
.expiry-soon    { color: var(--col-warning); font-weight: 700; }

/* type badge overrides */
.type-in   { background: rgba(45,190,138,.14);  color: var(--col-success); }
.type-out  { background: rgba(224,82,82,.12);   color: var(--col-danger);  }
.type-adj  { background: rgba(38,35,65,.10);    color: var(--col-navy);    }

/* ── MODAL ───────────────────────────────────────────────────── */
.modal-content { border: none; border-radius: var(--radius-card); overflow: hidden; box-shadow: 0 24px 64px rgba(38,35,65,.22); }
.modal-header-ev      { background: var(--col-navy); color: #fff; padding: 18px 24px; border: none; }
.modal-header-danger  { background: var(--col-danger);  color: #fff; padding: 18px 24px; border: none; }
.modal-header-neutral { background: var(--col-navy-80); color: #fff; padding: 18px 24px; border: none; }
.modal-title { font-weight: 800; font-size: .97rem; display: flex; align-items: center; gap: 8px; }
.modal-title i { color: var(--col-yellow); }
.modal-header-danger .modal-title i,
.modal-header-neutral .modal-title i { color: rgba(255,255,255,.75); }
.modal-body   { padding: 24px; }
.modal-footer { padding: 14px 24px; border-top: 1px solid rgba(38,35,65,.07); background: #fafafa; }

.form-label { font-size: .78rem; font-weight: 700; color: var(--col-navy); letter-spacing: .03em; text-transform: uppercase; margin-bottom: 5px; }
.form-control, .form-select { border-radius: 10px; border: 1.5px solid #ddd; font-size: .875rem; padding: 9px 13px; color: var(--col-navy); transition: border-color .15s, box-shadow .15s; }
.form-control:focus, .form-select:focus { border-color: var(--col-yellow); box-shadow: 0 0 0 3px var(--col-yellow-30); outline: none; }

/* ── DATATABLE overrides ─────────────────────────────────────── */
div.dataTables_wrapper .dataTables_filter input { border-radius: 10px; border: 1.5px solid #ddd; padding: 7px 13px; font-size: .85rem; margin-left: 6px; }
div.dataTables_wrapper .dataTables_length select { border-radius: 8px; border: 1.5px solid #ddd; padding: 5px 10px; font-size: .85rem; }
div.dataTables_wrapper .dataTables_info { font-size: .8rem; color: var(--col-muted); }
div.dataTables_wrapper .dataTables_paginate .paginate_button { border-radius: 8px !important; font-size: .82rem !important; }
div.dataTables_wrapper .dataTables_paginate .paginate_button.current,
div.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover { background: var(--col-navy) !important; color: var(--col-yellow) !important; border-color: var(--col-navy) !important; }
div.dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: var(--col-yellow-30) !important; color: var(--col-navy) !important; border-color: transparent !important; }
</style>

<?php
$topbarTitle = 'Inventory Management';
$topbarIcon  = 'bi-archive';
$topbarSub   = 'Track and adjust stock levels';
include 'topbar.php';
?>

<?php
$msgs=['stockIn'=>['success','Stock In','Stock added successfully.'],'stockOut'=>['success','Stock Out','Stock removed.'],'stockAdjust'=>['success','Adjusted','Stock adjusted.'],'insufficient'=>['error','Insufficient Stock','Not enough stock for removal.']];
foreach($msgs as $k=>[$i,$t,$tx]) if(isset($_GET[$k])) echo "<script>Swal.fire({icon:'$i',title:'$t',text:'$tx',timer:2000}).then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";
?>

<div class="page-body">

    <!-- Current Stock -->
    <div class="section-header">
        <div class="section-title"><span class="title-dot"></span>Current Stock Levels</div>
        <?php if($_SESSION['roleName']==='Admin'): ?>
        <div style="display:flex;gap:8px;">
            <button class="btn btn-ev"  data-bs-toggle="modal" data-bs-target="#stockInModal"><i class="bi bi-plus-circle"></i>Stock In</button>
            <button class="btn btn-out" data-bs-toggle="modal" data-bs-target="#stockOutModal"><i class="bi bi-dash-circle"></i>Stock Out</button>
            <button class="btn btn-adj" data-bs-toggle="modal" data-bs-target="#adjustModal"><i class="bi bi-sliders"></i>Adjust</button>
        </div>
        <?php endif; ?>
    </div>

    <div class="card-ev">
        <div class="p-3">
            <table id="invTable" class="table-ev" style="width:100%">
                <thead>
                    <tr><th>Product</th><th>Category</th><th>Stock</th><th>Reorder</th><th>Cost</th><th>Price</th><th>Expiry</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php
                $prods = $conn->query("SELECT p.*, c.categoryName FROM product p JOIN category c ON p.categoryID=c.categoryID WHERE p.status='Active' ORDER BY p.stock_quantity ASC");
                while($r=$prods->fetch_assoc()):
                    $sc = $r['stock_quantity'] == 0 ? 'badge-ev badge-inactive' : ($r['stock_quantity'] <= $r['reorder_level'] ? 'badge-ev badge-low' : 'badge-ev badge-active');
                    $exClass = '';
                    if($r['expiry_date']){
                        $d = (strtotime($r['expiry_date'])-time())/86400;
                        if($d < 0) $exClass='expiry-expired';
                        elseif($d <= 30) $exClass='expiry-soon';
                    }
                    $stLabel = $r['stock_quantity'] == 0 ? '<span class="badge-ev badge-inactive">OUT</span>' : ($r['stock_quantity'] <= $r['reorder_level'] ? '<span class="badge-ev badge-low">LOW</span>' : '<span class="badge-ev badge-active">OK</span>');
                ?>
                <tr>
                    <td style="font-weight:700;"><?php echo htmlspecialchars($r['productName']); ?></td>
                    <td style="color:var(--col-muted);"><?php echo htmlspecialchars($r['categoryName']); ?></td>
                    <td style="text-align:center;"><span class="<?php echo $sc; ?>"><?php echo $r['stock_quantity']; ?></span></td>
                    <td style="text-align:center;color:var(--col-muted);"><?php echo $r['reorder_level']; ?></td>
                    <td style="color:var(--col-muted);">₱<?php echo number_format($r['cost'],2); ?></td>
                    <td style="font-weight:600;">₱<?php echo number_format($r['price'],2); ?></td>
                    <td class="<?php echo $exClass; ?>"><?php echo $r['expiry_date'] ? date('M d, Y',strtotime($r['expiry_date'])) : '—'; ?></td>
                    <td style="text-align:center;"><?php echo $stLabel; ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Movement Log -->
    <div class="section-header" style="margin-top:8px;">
        <div class="section-title"><span class="title-dot" style="background:var(--col-navy);"></span>Stock Movement History</div>
    </div>

    <div class="card-ev">
        <div class="p-3">
            <table id="logTable" class="table-ev" style="width:100%">
                <thead>
                    <tr><th>Date</th><th>Product</th><th>Type</th><th>Qty</th><th>Cost</th><th>Supplier</th><th>Encoded by</th><th>Notes</th></tr>
                </thead>
                <tbody>
                <?php
                $logs = $conn->query("SELECT st.*, p.productName, CONCAT(u.givenName,' ',u.surName) AS userName, s.companyName FROM stocks st JOIN product p ON st.productID=p.productID JOIN users u ON st.userID=u.userID LEFT JOIN supplier s ON st.supplierID=s.supplierID ORDER BY st.dateAdded DESC LIMIT 100");
                while($r=$logs->fetch_assoc()):
                    $tc = $r['type']==='IN' ? 'badge-ev type-in' : ($r['type']==='OUT' ? 'badge-ev type-out' : 'badge-ev type-adj');
                ?>
                <tr>
                    <td><small style="color:var(--col-muted);"><?php echo date('M d, Y H:i',strtotime($r['dateAdded'])); ?></small></td>
                    <td style="font-weight:600;"><?php echo htmlspecialchars($r['productName']); ?></td>
                    <td style="text-align:center;"><span class="<?php echo $tc; ?>"><?php echo $r['type']; ?></span></td>
                    <td style="text-align:center;font-weight:700;"><?php echo $r['qty']; ?></td>
                    <td>₱<?php echo number_format($r['cost'],2); ?></td>
                    <td style="color:var(--col-muted);"><?php echo htmlspecialchars($r['companyName']??'—'); ?></td>
                    <td><?php echo htmlspecialchars($r['userName']); ?></td>
                    <td style="color:var(--col-muted);font-size:.82rem;"><?php echo htmlspecialchars($r['notes']??'—'); ?></td>
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
      <div class="modal-header modal-header-ev">
        <h5 class="modal-title"><i class="bi bi-plus-circle"></i>Stock In</h5>
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
      <div class="modal-header modal-header-danger">
        <h5 class="modal-title" style="font-weight:800;font-size:.97rem;"><i class="bi bi-dash-circle" style="color:rgba(255,255,255,.75);"></i>Stock Out</h5>
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
        <button type="submit" name="stockOut" class="btn btn-danger-ev">Remove Stock</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Adjust Modal -->
<div class="modal fade" id="adjustModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/stocksAuth.php">
      <?php csrf_field(); ?>
      <div class="modal-header modal-header-neutral">
        <h5 class="modal-title" style="font-weight:800;font-size:.97rem;"><i class="bi bi-sliders" style="color:var(--col-yellow);"></i>Stock Adjustment</h5>
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
        <button type="submit" name="stockAdjust" class="btn btn-neutral-ev">Apply Adjustment</button>
      </div>
    </form>
  </div></div>
</div>

<?php endif; ?>

</div></div>
<script>
$(document).ready(function(){
    $('#invTable').DataTable({
        pageLength: 25,
        order: [[2,'asc']],
        language: { search: '', searchPlaceholder: 'Search inventory…' }
    });
    $('#logTable').DataTable({
        pageLength: 15,
        order: [[0,'desc']],
        language: { search: '', searchPlaceholder: 'Search history…' }
    });
});
</script>

<!-- ── Pusher Real-time ───────────────────────────────────── -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
    const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>
</body></html>