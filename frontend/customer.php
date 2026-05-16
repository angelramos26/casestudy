<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';

session_start();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if(!in_array($_SESSION['roleName'], ['Admin','Owner','Cashier'])){ header("Location: dashboard.php"); exit(); }
$pageTitle = "Customers – 7Evelyn POS";

$result = $conn->query("SELECT * FROM customer WHERE dateDeleted IS NULL ORDER BY customerName ASC");
$customers = [];
while($row = $result->fetch_assoc()) $customers[] = $row;
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<style>
/* ── Page wrapper ───────────────────────────── */
.cust-page { padding: 28px 32px; background: var(--brand-mint); min-height: 100vh; }

/* ── Top bar ────────────────────────────────── */
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

/* ── Stat cards ─────────────────────────────── */
.stat-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; margin-bottom: 24px; }
.stat-card {
  background: var(--card-bg); border-radius: var(--radius-lg);
  padding: 20px 22px; display: flex; align-items: center; gap: 16px;
  box-shadow: var(--shadow-card); border: 1.5px solid var(--border);
  transition: transform .18s, box-shadow .18s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(38,35,65,.12); }
.stat-icon {
  width: 48px; height: 48px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; flex-shrink: 0;
}
.stat-icon.yellow  { background: #fffbe6; color: #b8860b; }
.stat-icon.navy    { background: #eeedf6; color: var(--brand-navy); }
.stat-icon.red     { background: #fff5f5; color: var(--danger); }
.stat-label { font-size: .74rem; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: .05em; }
.stat-value { font-size: 1.6rem; font-weight: 800; color: var(--brand-navy); line-height: 1.1; }

/* ── Main card ──────────────────────────────── */
.main-card {
  background: var(--card-bg); border-radius: var(--radius-lg);
  box-shadow: var(--shadow-card); border: 1.5px solid var(--border);
  overflow: hidden;
}
.main-card-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1.5px solid var(--border);
  flex-wrap: wrap; gap: 12px;
}
.main-card-header h6 { font-size: 1rem; font-weight: 700; color: var(--brand-navy); margin: 0; }

/* ── Add Customer button ────────────────────── */
.btn-add-cust {
  display: inline-flex; align-items: center; gap: 7px;
  background: var(--brand-yellow); color: var(--brand-navy);
  font-weight: 700; font-size: .84rem;
  border: none; border-radius: var(--radius-sm);
  padding: 9px 18px; cursor: pointer; transition: background .18s, box-shadow .18s, transform .1s;
  box-shadow: var(--shadow-btn);
  text-decoration: none;
}
.btn-add-cust:hover { background: var(--brand-yellow-d); transform: translateY(-1px); color: var(--brand-navy); }

/* ── Table ──────────────────────────────────── */
#customerTable { width: 100%; margin: 0; border-collapse: collapse; }
#customerTable thead th {
  background: var(--brand-navy); color: #fff;
  font-size: .75rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: .06em; padding: 13px 16px; border: none;
  white-space: nowrap;
}
#customerTable thead th:first-child { border-radius: 0; }
#customerTable tbody tr { border-bottom: 1px solid #f0f0f5; transition: background .12s; }
#customerTable tbody tr:hover { background: #f8f8fc; }
#customerTable tbody tr:last-child { border-bottom: none; }
#customerTable tbody td { padding: 13px 16px; font-size: .875rem; color: var(--text-main); vertical-align: middle; border: none; }

/* ── Name cell ──────────────────────────────── */
.cust-name-cell { display: flex; align-items: center; gap: 10px; }
.cust-avatar {
  width: 34px; height: 34px; border-radius: 50%;
  background: linear-gradient(135deg, var(--brand-yellow) 0%, #e8c72a 100%);
  color: var(--brand-navy); font-weight: 800; font-size: .8rem;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; text-transform: uppercase;
}
.cust-name { font-weight: 600; color: var(--brand-navy); }

/* ── Badge chips ────────────────────────────── */
.contact-chip {
  display: inline-flex; align-items: center; gap: 5px;
  background: var(--brand-mint); border-radius: 50px;
  padding: 3px 10px; font-size: .78rem; color: var(--text-main);
}
.balance-positive {
  display: inline-flex; align-items: center; gap: 5px;
  background: #fff5f5; color: var(--danger);
  border-radius: 50px; padding: 4px 12px; font-size: .83rem; font-weight: 700;
  border: 1px solid #fed7d7;
}
.balance-zero {
  display: inline-flex; align-items: center; gap: 5px;
  background: #f0fff4; color: var(--success);
  border-radius: 50px; padding: 4px 12px; font-size: .83rem; font-weight: 700;
  border: 1px solid #c6f6d5;
}

/* ── Action buttons ─────────────────────────── */
.action-group { display: flex; gap: 6px; justify-content: center; }
.btn-action {
  width: 32px; height: 32px; border-radius: var(--radius-sm);
  border: none; cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: .9rem; transition: opacity .15s, transform .1s;
}
.btn-action:hover { opacity: .85; transform: scale(1.08); }
.btn-action.wallet { background: #eeedf6; color: var(--brand-navy); }
.btn-action.edit   { background: #ebf4ff; color: var(--info); }
.btn-action.del    { background: #fff5f5; color: var(--danger); }

/* ── DataTables override ────────────────────── */
div.dataTables_wrapper div.dataTables_filter label,
div.dataTables_wrapper div.dataTables_length label { color: var(--text-muted); font-size: .83rem; }
div.dataTables_wrapper div.dataTables_filter input,
div.dataTables_wrapper div.dataTables_length select {
  border: 1.5px solid var(--border); border-radius: var(--radius-sm);
  padding: 5px 10px; font-size: .83rem; color: var(--text-main);
  background: var(--card-bg); outline: none;
}
div.dataTables_wrapper div.dataTables_filter input:focus { border-color: var(--brand-yellow); }
div.dataTables_wrapper div.dataTables_paginate .paginate_button {
  border-radius: var(--radius-sm) !important; font-size: .8rem;
}
div.dataTables_wrapper div.dataTables_paginate .paginate_button.current,
div.dataTables_wrapper div.dataTables_paginate .paginate_button.current:hover {
  background: var(--brand-navy) !important; color: #fff !important; border-color: var(--brand-navy) !important;
}
div.dataTables_wrapper .dataTables_info { color: var(--text-muted); font-size: .8rem; }
.dt-controls { padding: 16px 24px 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }

/* ── Modal redesign ─────────────────────────── */
.modal-content { border-radius: var(--radius-lg); border: none; box-shadow: 0 8px 40px rgba(38,35,65,.18); overflow: hidden; }
.modal-header-brand {
  background: var(--brand-navy); color: #fff;
  padding: 18px 24px; display: flex; align-items: center; justify-content: space-between;
}
.modal-header-brand .modal-title { font-size: 1rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px; }
.modal-header-brand .btn-close { filter: brightness(0) invert(1); opacity: .7; }
.modal-header-danger { background: var(--danger); color: #fff; padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; }
.modal-header-danger .modal-title { font-size: 1rem; font-weight: 700; margin: 0; }
.modal-header-danger .btn-close { filter: brightness(0) invert(1); opacity: .7; }
.modal-body { padding: 22px 24px; }
.modal-footer { padding: 14px 24px; border-top: 1.5px solid var(--border); background: #fafafa; }
.form-label-styled { font-size: .78rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 5px; }
.form-control-styled {
  border: 1.5px solid var(--border); border-radius: var(--radius-sm);
  padding: 9px 13px; font-size: .88rem; color: var(--text-main);
  transition: border-color .15s, box-shadow .15s; width: 100%;
}
.form-control-styled:focus { outline: none; border-color: var(--brand-yellow); box-shadow: 0 0 0 3px rgba(249,217,74,.2); }
.btn-submit {
  background: var(--brand-yellow); color: var(--brand-navy);
  font-weight: 700; border: none; border-radius: var(--radius-sm);
  padding: 9px 22px; font-size: .88rem; cursor: pointer;
  transition: background .15s, box-shadow .15s;
}
.btn-submit:hover { background: var(--brand-yellow-d); box-shadow: var(--shadow-btn); }
.btn-cancel {
  background: transparent; color: var(--text-muted);
  font-weight: 600; border: 1.5px solid var(--border);
  border-radius: var(--radius-sm); padding: 9px 18px; font-size: .88rem; cursor: pointer;
  transition: border-color .15s;
}
.btn-cancel:hover { border-color: #aaa; color: var(--text-main); }
.btn-danger-solid {
  background: var(--danger); color: #fff;
  font-weight: 700; border: none; border-radius: var(--radius-sm);
  padding: 9px 20px; font-size: .88rem; cursor: pointer;
  transition: opacity .15s;
}
.btn-danger-solid:hover { opacity: .88; }

/* ── Address muted ──────────────────────────── */
.addr-cell { font-size: .8rem; color: var(--text-muted); max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

@media(max-width: 768px){
  .stat-row { grid-template-columns: 1fr 1fr; }
  .cust-page { padding: 16px; }
}
@media(max-width: 480px){
  .stat-row { grid-template-columns: 1fr; }
}
</style>

<?php
// Compute stats
$totalCustomers  = count($customers);
$withCredit      = count(array_filter($customers, fn($c) => $c['credit_balance'] > 0));
$totalCredit     = array_sum(array_column($customers, 'credit_balance'));
?>

<?php
  $topbarTitle = 'Customer Management';
  $topbarIcon  = 'bi-people-fill';
  $topbarSub   = 'Manage customers, contacts, and credit balances';
  include 'topbar.php';
  ?>
<div class="cust-page no-print">

  <!-- STAT CARDS -->
  <div class="stat-row">
    <div class="stat-card">
      <div class="stat-icon navy"><i class="bi bi-people-fill"></i></div>
      <div>
        <div class="stat-label">Total Customers</div>
        <div class="stat-value"><?php echo $totalCustomers; ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon red"><i class="bi bi-exclamation-circle-fill"></i></div>
      <div>
        <div class="stat-label">With Balance</div>
        <div class="stat-value"><?php echo $withCredit; ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon yellow"><i class="bi bi-currency-exchange"></i></div>
      <div>
        <div class="stat-label">Total Credit</div>
        <div class="stat-value" style="font-size:1.25rem;">₱<?php echo number_format($totalCredit, 2); ?></div>
      </div>
    </div>
  </div>

  <!-- Alerts -->
  <?php
  $alerts=['savedData'=>['success','Saved!','Customer added.'],'updatedCustomer'=>['success','Updated!','Customer updated.'],'customerDeleted'=>['success','Deleted!','Customer removed.'],'creditAdded'=>['success','Credit Added','Utang recorded.'],'creditPaid'=>['success','Paid!','Credit payment recorded.'],'emailExists'=>['error','Email Exists','Email already in use.'],'insufficientBalance'=>['error','Insufficient','Payment exceeds balance.'],'accessDenied'=>['error','Access Denied','You do not have permission to perform that action.'],'emptyFields'=>['warning','Required Fields','Fill in all required fields.']];
  foreach($alerts as $k=>[$i,$t,$tx]) if(isset($_GET[$k])) echo "<script>Swal.fire({icon:'$i',title:'$t',text:'$tx',timer:2000}).then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";
  ?>

  <!-- MAIN CARD -->
  <div class="main-card">
    <div class="main-card-header">
      <h6><i class="bi bi-table me-2" style="color:var(--brand-yellow);"></i>Customer List</h6>
      <?php if(in_array($_SESSION['roleName'], ['Admin','Cashier'])): ?>
      <button class="btn-add-cust" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
        <i class="bi bi-person-plus-fill"></i> Add Customer
      </button>
      <?php endif; ?>
    </div>

    <div class="dt-controls" id="dt-search-area"></div>

    <div style="padding: 0 0 8px;">
      <table id="customerTable" class="table" style="margin:0;">
        <thead>
          <tr>
            <th>Customer</th>
            <th>Contact</th>
            <th>Email</th>
            <th>Address</th>
            <th>Credit Balance</th>
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($customers as $row):
          $initials = strtoupper(substr($row['customerName'], 0, 1));
          $creditClass = $row['credit_balance'] > 0 ? 'balance-positive' : 'balance-zero';
          $creditIcon  = $row['credit_balance'] > 0 ? 'bi-arrow-up-circle-fill' : 'bi-check-circle-fill';
        ?>
        <tr>
          <td>
            <div class="cust-name-cell">
              <div class="cust-avatar"><?php echo $initials; ?></div>
              <span class="cust-name"><?php echo htmlspecialchars($row['customerName']); ?></span>
            </div>
          </td>
          <td>
            <?php if(!empty($row['contactNo'])): ?>
              <span class="contact-chip"><i class="bi bi-telephone-fill" style="font-size:.7rem;color:var(--brand-navy);"></i><?php echo htmlspecialchars($row['contactNo']); ?></span>
            <?php else: ?><span style="color:#ccc;">—</span><?php endif; ?>
          </td>
          <td style="font-size:.83rem;color:var(--text-muted);"><?php echo htmlspecialchars($row['email'] ?? '—'); ?></td>
          <td><div class="addr-cell" title="<?php echo htmlspecialchars($row['address'] ?? ''); ?>"><?php echo htmlspecialchars($row['address'] ?? '—'); ?></div></td>
          <td>
            <span class="<?php echo $creditClass; ?>">
              <i class="bi <?php echo $creditIcon; ?>" style="font-size:.75rem;"></i>
              ₱<?php echo number_format($row['credit_balance'], 2); ?>
            </span>
          </td>
          <td>
            <div class="action-group">
              <?php if(in_array($_SESSION['roleName'], ['Admin','Owner','Cashier'])): ?>
              <button class="btn-action wallet" onclick="openCreditModal(<?php echo $row['customerID']; ?>)" title="Manage Credit">
                <i class="bi bi-wallet2"></i>
              </button>
              <?php endif; ?>
              <?php if($_SESSION['roleName']==='Admin'): ?>
              <button class="btn-action edit" onclick="openEditModal(<?php echo $row['customerID']; ?>)" title="Edit">
                <i class="bi bi-pencil-fill"></i>
              </button>
              <button class="btn-action del" onclick="openDeleteModal(<?php echo $row['customerID']; ?>, '<?php echo addslashes($row['customerName']); ?>')" title="Delete">
                <i class="bi bi-trash-fill"></i>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div><!-- /main-card -->

</div><!-- /cust-page -->


<!-- ═══════════════════════════════════ MODALS ════════════════════════════════ -->

<!-- Add Customer -->
<?php if(in_array($_SESSION['roleName'], ['Admin','Cashier'])): ?>
<div class="modal fade" id="addCustomerModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="../backend/customerAuth.php">
        <?php csrf_field(); ?>
        <div class="modal-header-brand">
          <span class="modal-title"><i class="bi bi-person-plus-fill"></i> Add Customer</span>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label-styled">Name <span style="color:var(--danger);">*</span></label>
            <input type="text" name="customerName" class="form-control-styled" required placeholder="Full name">
          </div>
          <div class="mb-3">
            <label class="form-label-styled">Contact No</label>
            <input type="text" name="contactNo" class="form-control-styled" placeholder="09xx-xxx-xxxx">
          </div>
          <div class="mb-3">
            <label class="form-label-styled">Email</label>
            <input type="email" name="email" class="form-control-styled" placeholder="email@example.com">
          </div>
          <div class="mb-0">
            <label class="form-label-styled">Address</label>
            <textarea name="address" class="form-control-styled" rows="2" placeholder="Street, City"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="customerSave" class="btn-submit">Save Customer</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Credit / Utang Modal -->
<div class="modal fade" id="creditModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header-brand">
        <span class="modal-title"><i class="bi bi-wallet2"></i> Manage Credit – <span id="creditModalName"></span></span>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="creditModalBody">
        <div class="text-center py-4">
          <div class="spinner-border" style="color:var(--brand-navy);"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Customer Modal -->
<?php if($_SESSION['roleName']==='Admin'): ?>
<div class="modal fade" id="editCustomerModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="../backend/customerAuth.php">
        <?php csrf_field(); ?>
        <div class="modal-header-brand">
          <span class="modal-title"><i class="bi bi-pencil-fill"></i> Edit Customer</span>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="customerID" id="editCustomerID">
          <div class="mb-3">
            <label class="form-label-styled">Name <span style="color:var(--danger);">*</span></label>
            <input type="text" name="customerName" id="editCustomerName" class="form-control-styled" required>
          </div>
          <div class="mb-3">
            <label class="form-label-styled">Contact No</label>
            <input type="text" name="contactNo" id="editContactNo" class="form-control-styled">
          </div>
          <div class="mb-3">
            <label class="form-label-styled">Email</label>
            <input type="email" name="email" id="editEmail" class="form-control-styled">
          </div>
          <div class="mb-0">
            <label class="form-label-styled">Address</label>
            <textarea name="address" id="editAddress" class="form-control-styled" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="customerUpdate" class="btn-submit">Update Customer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteCustomerModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="../backend/customerAuth.php">
        <?php csrf_field(); ?>
        <div class="modal-header-danger">
          <span class="modal-title"><i class="bi bi-exclamation-triangle-fill me-1"></i> Delete Customer</span>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" style="text-align:center; padding: 28px 24px;">
          <input type="hidden" name="customerID" id="deleteCustomerID">
          <div style="width:52px;height:52px;border-radius:50%;background:#fff5f5;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:1.5rem;color:var(--danger);">
            <i class="bi bi-trash-fill"></i>
          </div>
          <p style="font-weight:700;color:var(--text-main);margin-bottom:6px;">Delete <strong id="deleteCustomerName"></strong>?</p>
          <p style="font-size:.82rem;color:var(--text-muted);margin:0;">This action cannot be undone.</p>
        </div>
        <div class="modal-footer" style="justify-content:center; gap:10px;">
          <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="customerDeleted" class="btn-danger-solid">Yes, Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Embedded customer data for JS -->
<script>
const customerData = <?php
  $jsData = [];
  foreach($customers as $c){
    $jsData[$c['customerID']] = [
      'customerID'     => $c['customerID'],
      'customerName'   => $c['customerName'],
      'contactNo'      => $c['contactNo'] ?? '',
      'email'          => $c['email'] ?? '',
      'address'        => $c['address'] ?? '',
      'credit_balance' => $c['credit_balance'],
    ];
  }
  echo json_encode($jsData);
?>;

$(document).ready(function(){
  const dt = $('#customerTable').DataTable({
    pageLength: 10,
    order: [[0,'asc']],
    dom: '<"dt-wrap"lf>rtip',
    language: {
      search: '',
      searchPlaceholder: '🔍  Search customers…',
      lengthMenu: 'Show _MENU_ entries',
      info: 'Showing _START_–_END_ of _TOTAL_ customers',
      paginate: { previous: '‹', next: '›' }
    }
  });
  // Move length + filter into our custom area
  $('.dataTables_filter').appendTo('#dt-search-area');
  $('.dataTables_length').appendTo('#dt-search-area');
});

function openCreditModal(customerID){
  const c = customerData[customerID];
  document.getElementById('creditModalName').textContent = c.customerName;
  document.getElementById('creditModalBody').innerHTML =
    '<div class="text-center py-4"><div class="spinner-border" style="color:var(--brand-navy);"></div></div>';
  new bootstrap.Modal(document.getElementById('creditModal')).show();
  fetch('../backend/getCreditDetails.php?customerID=' + customerID)
    .then(r => r.text())
    .then(html => { document.getElementById('creditModalBody').innerHTML = html; })
    .catch(() => { document.getElementById('creditModalBody').innerHTML = '<div class="alert alert-danger">Failed to load credit details.</div>'; });
}

function openEditModal(customerID){
  const c = customerData[customerID];
  document.getElementById('editCustomerID').value   = c.customerID;
  document.getElementById('editCustomerName').value = c.customerName;
  document.getElementById('editContactNo').value    = c.contactNo;
  document.getElementById('editEmail').value        = c.email;
  document.getElementById('editAddress').value      = c.address;
  new bootstrap.Modal(document.getElementById('editCustomerModal')).show();
}

function openDeleteModal(customerID, customerName){
  document.getElementById('deleteCustomerID').value         = customerID;
  document.getElementById('deleteCustomerName').textContent = customerName;
  new bootstrap.Modal(document.getElementById('deleteCustomerModal')).show();
}
</script>

<!-- Pusher Real-time -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
  const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
  const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>
</body></html>