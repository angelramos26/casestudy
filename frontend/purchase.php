<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if(!in_array($_SESSION['roleName'], ['Admin','Owner'])){ header("Location: dashboard.php"); exit(); }

$pageTitle = "Purchase Orders – Beng's Unli Lugaw";

$suppliers = $conn->query("SELECT supplierID, companyName FROM supplier WHERE dateDeleted IS NULL ORDER BY companyName");
$products  = $conn->query("SELECT productID, productName, cost FROM product WHERE status='Active' ORDER BY productName");

$totalPending  = $conn->query("SELECT COUNT(*) AS c FROM purchase_order WHERE status='Pending'")->fetch_assoc()['c'];
$totalReceived = $conn->query("SELECT COUNT(*) AS c FROM purchase_order WHERE status='Received'")->fetch_assoc()['c'];
$totalCancelled= $conn->query("SELECT COUNT(*) AS c FROM purchase_order WHERE status='Cancelled'")->fetch_assoc()['c'];
$monthSpend    = $conn->query("SELECT COALESCE(SUM(pod.qty_ordered * pod.unit_cost),0) AS t FROM purchase_order_details pod JOIN purchase_order po ON pod.poID=po.poID WHERE po.status='Received' AND MONTH(po.dateReceived)=MONTH(CURDATE()) AND YEAR(po.dateReceived)=YEAR(CURDATE())")->fetch_assoc()['t'];

$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT po.poID, po.status, po.dateCreated, po.dateReceived, po.notes,
               s.companyName,
               CONCAT(u.givenName,' ',u.surName) AS createdBy,
               COALESCE(SUM(pod.qty_ordered * pod.unit_cost),0) AS total_amount
        FROM purchase_order po
        JOIN supplier s  ON po.supplierID = s.supplierID
        JOIN users u     ON po.userID     = u.userID
        LEFT JOIN purchase_order_details pod ON po.poID = pod.poID";
if($statusFilter) $sql .= " WHERE po.status = '" . $conn->real_escape_string($statusFilter) . "'";
$sql .= " GROUP BY po.poID ORDER BY po.dateCreated DESC";
$pos = $conn->query($sql);
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<style>

/* ── Page wrapper ─────────────────────────── */
.po-page { padding: 28px 32px; background: var(--brand-mint); min-height: 100vh; }

.user-chip {
  display: flex; align-items: center; gap: 8px;
  background: #fff; border: 1.5px solid var(--border);
  border-radius: 50px; padding: 6px 14px 6px 8px;
}
.user-chip i { font-size: 1.1rem; color: var(--brand-navy); }
.user-chip .uname { font-size: .82rem; font-weight: 600; color: var(--text-main); }
.user-chip .role-tag {
  background: var(--brand-yellow); color: var(--brand-navy);
  font-size: .68rem; font-weight: 700; border-radius: 50px;
  padding: 2px 9px; text-transform: uppercase; letter-spacing: .04em;
}

/* ── Stat cards ───────────────────────────── */
.stat-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 24px; }
.stat-card {
  background: var(--card-bg); border-radius: var(--r-lg);
  padding: 20px 22px; display: flex; align-items: center; gap: 16px;
  box-shadow: var(--shadow-card); border: 1.5px solid var(--border);
  transition: transform .18s, box-shadow .18s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(26,26,26,.12); }
.stat-icon-box {
  width: 48px; height: 48px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; flex-shrink: 0;
}
.ic-orange { background: #fff7ed; color: #c05621; }
.ic-blue   { background: #ebf4ff; color: #2b6cb0; }
.ic-red    { background: #fff5f5; color: var(--c-danger); }
.ic-green  { background: #f0fff4; color: var(--c-success); }
.stat-label { font-size: .72rem; color: var(--t-muted); font-weight: 500; text-transform: uppercase; letter-spacing: .05em; }
.stat-value { font-size: 1.55rem; font-weight: 800; color: var(--brand-navy); line-height: 1.1; }
.stat-value.small-val { font-size: 1.15rem; }

/* ── Main card ────────────────────────────── */
.main-card {
  background: var(--card-bg); border-radius: var(--r-lg);
  box-shadow: var(--shadow-card); border: 1.5px solid var(--border);
  overflow: hidden;
}
.main-card-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 18px 24px; border-bottom: 1.5px solid var(--border);
  flex-wrap: wrap; gap: 12px;
}
.main-card-header h6 { font-size: 1rem; font-weight: 700; color: var(--brand-navy); margin: 0; }

/* ── Buttons ──────────────────────────────── */
.btn-add {
  display: inline-flex; align-items: center; gap: 7px;
  background: var(--brand-yellow); color: var(--brand-navy);
  font-weight: 700; font-size: .84rem;
  border: none; border-radius: var(--r-sm);
  padding: 9px 18px; cursor: pointer;
  transition: background .18s, transform .1s;
  box-shadow: var(--shadow-btn); text-decoration: none;
}
.btn-add:hover { background: var(--brand-yellow-d); transform: translateY(-1px); color: var(--brand-navy); }

.filter-select {
  border: 1.5px solid var(--border); border-radius: var(--r-sm);
  padding: 7px 12px; font-size: .83rem; color: var(--text-main);
  background: var(--card-bg); cursor: pointer; outline: none;
  transition: border-color .15s;
}
.filter-select:focus { border-color: var(--brand-yellow); }

/* ── Table ────────────────────────────────── */
#poTable { width: 100%; border-collapse: collapse; margin: 0; }
#poTable thead th {
  background: var(--brand-navy); color: #fff;
  font-size: .73rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: .06em; padding: 13px 14px; border: none; white-space: nowrap;
}
#poTable tbody tr { border-bottom: 1px solid #f0f0f5; transition: background .12s; }
#poTable tbody tr:hover { background: #f8f8fc; }
#poTable tbody tr:last-child { border-bottom: none; }
#poTable tbody td { padding: 12px 14px; font-size: .86rem; color: var(--text-main); vertical-align: middle; border: none; }

/* ── PO ID badge ──────────────────────────── */
.po-id-badge {
  display: inline-block;
  background: var(--brand-navy); color: var(--brand-yellow);
  font-size: .72rem; font-weight: 800; border-radius: 6px;
  padding: 3px 9px; letter-spacing: .04em;
}

/* ── Supplier chip ────────────────────────── */
.supplier-cell { display: flex; align-items: center; gap: 8px; }
.supplier-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: var(--brand-yellow); flex-shrink: 0;
}

/* ── Status badges ────────────────────────── */
.status-pill {
  display: inline-flex; align-items: center; gap: 5px;
  border-radius: 50px; padding: 4px 12px; font-size: .76rem; font-weight: 700;
}
.status-pending  { background: #fff7ed; color: #c05621; border: 1px solid #fbd38d; }
.status-received { background: #f0fff4; color: var(--c-success); border: 1px solid #9ae6b4; }
.status-cancelled{ background: #fff5f5; color: var(--c-danger); border: 1px solid #fed7d7; }

/* ── Amount ───────────────────────────────── */
.amount-cell { font-weight: 700; color: var(--brand-navy); }

/* ── Notes cell ───────────────────────────── */
.notes-cell { font-size: .78rem; color: var(--t-muted); max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* ── Action buttons ───────────────────────── */
.action-group { display: flex; gap: 5px; justify-content: center; }
.btn-action {
  width: 32px; height: 32px; border-radius: var(--r-sm);
  border: none; cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: .88rem; transition: opacity .15s, transform .1s;
}
.btn-action:hover { opacity: .82; transform: scale(1.08); }
.btn-view    { background: #ebf4ff; color: var(--info); }
.btn-receive { background: #f0fff4; color: var(--c-success); }
.btn-cancel  { background: #fff5f5; color: var(--c-danger); }

/* ── DataTable overrides ──────────────────── */
div.dataTables_wrapper div.dataTables_filter label,
div.dataTables_wrapper div.dataTables_length label { color: var(--t-muted); font-size: .83rem; }
div.dataTables_wrapper div.dataTables_filter input,
div.dataTables_wrapper div.dataTables_length select {
  border: 1.5px solid var(--border); border-radius: var(--r-sm);
  padding: 5px 10px; font-size: .83rem; color: var(--text-main);
  background: var(--card-bg); outline: none;
}
div.dataTables_wrapper div.dataTables_filter input:focus { border-color: var(--brand-yellow); }
div.dataTables_wrapper div.dataTables_paginate .paginate_button { border-radius: var(--r-sm) !important; font-size: .8rem; }
div.dataTables_wrapper div.dataTables_paginate .paginate_button.current,
div.dataTables_wrapper div.dataTables_paginate .paginate_button.current:hover {
  background: var(--brand-navy) !important; color: #fff !important; border-color: var(--brand-navy) !important;
}
div.dataTables_wrapper .dataTables_info { color: var(--t-muted); font-size: .8rem; }
.dt-controls { padding: 16px 24px 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }

/* ── Modal ────────────────────────────────── */
.modal-content { border-radius: var(--r-lg); border: none; box-shadow: 0 8px 40px rgba(26,26,26,.18); overflow: hidden; }
.modal-header-brand {
  background: var(--brand-navy); color: #fff;
  padding: 18px 24px; display: flex; align-items: center; justify-content: space-between;
}
.modal-header-brand .modal-title { font-size: 1rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px; }
.modal-header-brand .btn-close { filter: brightness(0) invert(1); opacity: .7; }
.modal-body  { padding: 22px 24px; }
.modal-footer{ padding: 14px 24px; border-top: 1.5px solid var(--border); background: #fafafa; }
.form-label-styled { font-size: .75rem; font-weight: 600; color: var(--t-muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 5px; display: block; }
.form-control-styled {
  border: 1.5px solid var(--border); border-radius: var(--r-sm);
  padding: 9px 13px; font-size: .88rem; color: var(--text-main);
  transition: border-color .15s, box-shadow .15s; width: 100%;
}
.form-control-styled:focus { outline: none; border-color: var(--brand-yellow); box-shadow: 0 0 0 3px rgba(239,130,13,.2); }
.btn-submit {
  background: var(--brand-yellow); color: var(--brand-navy);
  font-weight: 700; border: none; border-radius: var(--r-sm);
  padding: 9px 22px; font-size: .88rem; cursor: pointer;
  transition: background .15s;
}
.btn-submit:hover { background: var(--brand-yellow-d); }
.btn-cancel-modal {
  background: transparent; color: var(--t-muted);
  font-weight: 600; border: 1.5px solid var(--border);
  border-radius: var(--r-sm); padding: 9px 18px; font-size: .88rem; cursor: pointer;
  transition: border-color .15s;
}
.btn-cancel-modal:hover { border-color: #aaa; color: var(--text-main); }

@media(max-width: 900px){ .stat-row { grid-template-columns: repeat(2,1fr); } }
@media(max-width: 480px){ .stat-row { grid-template-columns: 1fr; } .po-page { padding: 16px; } }
</style>

<?php
  $topbarTitle = 'Purchase Orders';
  $topbarIcon  = 'bi-bag-check-fill';
  $topbarSub   = 'Track, receive, and manage supplier orders';
  include 'topbar.php';
  ?>

<div class="po-page no-print">

  <!-- Alerts -->
  <?php include 'Message/purchaseMessage.php'; ?>

  <!-- STAT CARDS -->
  <div class="stat-row">
    <div class="stat-card">
      <div class="stat-icon-box ic-orange"><i class="bi bi-hourglass-split"></i></div>
      <div>
        <div class="stat-label">Pending</div>
        <div class="stat-value"><?php echo $totalPending; ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon-box ic-blue"><i class="bi bi-box-seam"></i></div>
      <div>
        <div class="stat-label">Received</div>
        <div class="stat-value"><?php echo $totalReceived; ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon-box ic-red"><i class="bi bi-x-circle"></i></div>
      <div>
        <div class="stat-label">Cancelled</div>
        <div class="stat-value"><?php echo $totalCancelled; ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon-box ic-green"><i class="bi bi-currency-exchange"></i></div>
      <div>
        <div class="stat-label">Month Spend</div>
        <div class="stat-value small-val">₱<?php echo number_format($monthSpend, 0); ?></div>
      </div>
    </div>
  </div>

  <!-- MAIN CARD -->
  <div class="main-card">
    <div class="main-card-header">
      <h6><i class="bi bi-table me-2" style="color:var(--brand-yellow);"></i>Purchase Order List</h6>
      <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <!-- Status filter -->
        <form method="GET" style="display:flex; align-items:center; gap:6px;">
          <i class="bi bi-funnel" style="color:var(--t-muted); font-size:.9rem;"></i>
          <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="Pending"   <?php echo ($statusFilter==='Pending')   ? 'selected' : ''; ?>>Pending</option>
            <option value="Received"  <?php echo ($statusFilter==='Received')  ? 'selected' : ''; ?>>Received</option>
            <option value="Cancelled" <?php echo ($statusFilter==='Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
          </select>
        </form>
        <?php if($_SESSION['roleName']==='Admin'): ?>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#createPOModal">
          <i class="bi bi-plus-circle-fill"></i> New Purchase Order
        </button>
        <?php endif; ?>
      </div>
    </div>

    <div class="dt-controls" id="dt-search-area"></div>

    <div style="overflow-x:auto;">
      <table id="poTable">
        <thead>
          <tr>
            <th>PO #</th>
            <th>Supplier</th>
            <th>Created By</th>
            <th>Status</th>
            <th>Total Amount</th>
            <th>Date Created</th>
            <th>Date Received</th>
            <th>Notes</th>
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $pos->data_seek(0);
        $poRows = [];
        while($po = $pos->fetch_assoc()):
            $poRows[] = $po;
            $pillClass = match($po['status']) {
                'Pending'   => 'status-pending',
                'Received'  => 'status-received',
                'Cancelled' => 'status-cancelled',
                default     => 'status-pending',
            };
            $pillIcon = match($po['status']) {
                'Pending'   => 'bi-hourglass-split',
                'Received'  => 'bi-check-circle-fill',
                'Cancelled' => 'bi-x-circle-fill',
                default     => 'bi-circle',
            };
        ?>
        <tr>
          <td><span class="po-id-badge">PO-<?php echo str_pad($po['poID'], 4, '0', STR_PAD_LEFT); ?></span></td>
          <td>
            <div class="supplier-cell">
              <span class="supplier-dot"></span>
              <span style="font-weight:600;"><?php echo htmlspecialchars($po['companyName']); ?></span>
            </div>
          </td>
          <td style="font-size:.8rem; color:var(--t-muted);"><?php echo htmlspecialchars($po['createdBy']); ?></td>
          <td>
            <span class="status-pill <?php echo $pillClass; ?>">
              <i class="bi <?php echo $pillIcon; ?>" style="font-size:.68rem;"></i>
              <?php echo $po['status']; ?>
            </span>
          </td>
          <td class="amount-cell">₱<?php echo number_format($po['total_amount'], 2); ?></td>
          <td style="font-size:.82rem; color:var(--t-muted);"><?php echo date('M d, Y', strtotime($po['dateCreated'])); ?></td>
          <td style="font-size:.82rem; color:var(--t-muted);"><?php echo $po['dateReceived'] ? date('M d, Y', strtotime($po['dateReceived'])) : '<span style="color:#ccc;">—</span>'; ?></td>
          <td><div class="notes-cell" title="<?php echo htmlspecialchars($po['notes'] ?? ''); ?>"><?php echo htmlspecialchars($po['notes'] ?? '—'); ?></div></td>
          <td>
            <div class="action-group">
              <!-- View -->
              <button class="btn-action btn-view" data-bs-toggle="modal" data-bs-target="#viewPOModal<?php echo $po['poID']; ?>" title="View Details">
                <i class="bi bi-eye-fill"></i>
              </button>

              <?php if($po['status'] === 'Pending' && $_SESSION['roleName']==='Admin'): ?>
              <!-- Receive -->
              <form method="POST" action="../backend/purchaseAuth.php" class="d-inline" id="receiveForm<?php echo $po['poID']; ?>">
                <?php csrf_field(); ?>
                <input type="hidden" name="poID" value="<?php echo $po['poID']; ?>">
                <button type="button" class="btn-action btn-receive" title="Mark as Received"
                  onclick="Swal.fire({
                    title:'Receive PO-<?php echo str_pad($po['poID'],4,'0',STR_PAD_LEFT); ?>?',
                    text:'This will update inventory based on order items.',
                    icon:'question',
                    showCancelButton:true,
                    confirmButtonColor:'#276749',
                    confirmButtonText:'Yes, Receive',
                    cancelButtonText:'Cancel'
                  }).then(r=>{ if(r.isConfirmed){ let f=document.getElementById('receiveForm<?php echo $po['poID']; ?>'); f.innerHTML+='<input name=\'receivePO\' type=\'hidden\'>'; f.submit(); } })">
                  <i class="bi bi-check2-circle"></i>
                </button>
              </form>
              <!-- Cancel -->
              <form method="POST" action="../backend/purchaseAuth.php" class="d-inline" id="cancelForm<?php echo $po['poID']; ?>">
                <?php csrf_field(); ?>
                <input type="hidden" name="poID" value="<?php echo $po['poID']; ?>">
                <button type="button" class="btn-action btn-cancel" title="Cancel PO"
                  onclick="Swal.fire({
                    title:'Cancel PO-<?php echo str_pad($po['poID'],4,'0',STR_PAD_LEFT); ?>?',
                    text:'This action cannot be undone.',
                    icon:'warning',
                    showCancelButton:true,
                    confirmButtonColor:'#e53e3e',
                    confirmButtonText:'Yes, Cancel',
                    cancelButtonText:'Go back'
                  }).then(r=>{ if(r.isConfirmed){ let f=document.getElementById('cancelForm<?php echo $po['poID']; ?>'); f.innerHTML+='<input name=\'cancelPO\' type=\'hidden\'>'; f.submit(); } })">
                  <i class="bi bi-x-lg"></i>
                </button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div><!-- /main-card -->

</div><!-- /po-page -->

<!-- ═══ MODALS ════════════════════════════════════════════════════════════ -->
<?php if($_SESSION['roleName']==='Admin'): ?>
<?php include 'Modal/purchaseCreateModal.php'; ?>
<?php endif; ?>
<?php foreach($poRows as $po): ?>
<?php include 'Modal/purchaseViewModal.php'; ?>
<?php endforeach; ?>

<script>
$(document).ready(function(){
  $('#poTable').DataTable({
    pageLength: 15,
    order: [[0,'desc']],
    columnDefs: [{ orderable: false, targets: [8] }],
    dom: '<"dt-wrap"lf>rtip',
    language: {
      search: '',
      searchPlaceholder: '🔍  Search orders…',
      lengthMenu: 'Show _MENU_ entries',
      info: 'Showing _START_–_END_ of _TOTAL_ orders',
      paginate: { previous: '‹', next: '›' }
    }
  });
  $('.dataTables_filter').appendTo('#dt-search-area');
  $('.dataTables_length').appendTo('#dt-search-area');
});
</script>

<!-- ── Pusher Real-time ─────────────────────────────────────────────────── -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
  const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
  const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>
<?php include 'footer.php'; ?>
</body></html>