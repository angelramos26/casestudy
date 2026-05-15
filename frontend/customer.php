<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';

session_start();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if(!in_array($_SESSION['roleName'], ['Admin','Owner','Cashier'])){ header("Location: dashboard.php"); exit(); }
$pageTitle = "Customers – 7Evelyn POS";

// Pre-fetch all customers and their ledgers into PHP arrays for JS
$result = $conn->query("SELECT * FROM customer WHERE dateDeleted IS NULL ORDER BY customerName ASC");
$customers = [];
while($row = $result->fetch_assoc()) $customers[] = $row;
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<div class="topbar no-print">
    <h5><i class="bi bi-people me-2" style="color:var(--ev-purple);"></i>Customer Management</h5>
    <div class="ms-auto"><div class="user-badge"><i class="bi bi-person-circle" style="color:var(--ev-purple);"></i><span><?php echo htmlspecialchars($_SESSION['userName']); ?></span><span class="role-pill"><?php echo htmlspecialchars($_SESSION['roleName']); ?></span></div></div>
</div>

<?php
$alerts=['savedData'=>['success','Saved!','Customer added.'],'updatedCustomer'=>['success','Updated!','Customer updated.'],'customerDeleted'=>['success','Deleted!','Customer removed.'],'creditAdded'=>['success','Credit Added','Utang recorded.'],'creditPaid'=>['success','Paid!','Credit payment recorded.'],'emailExists'=>['error','Email Exists','Email already in use.'],'insufficientBalance'=>['error','Insufficient','Payment exceeds balance.'],'accessDenied'=>['error','Access Denied','You do not have permission to perform that action.'],'emptyFields'=>['warning','Required Fields','Fill in all required fields.']];
foreach($alerts as $k=>[$i,$t,$tx]) if(isset($_GET[$k])) echo "<script>Swal.fire({icon:'$i',title:'$t',text:'$tx',timer:2000}).then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";
?>

<div class="page-body">
    <div class="d-flex justify-content-between mb-3 align-items-center">
        <h5 class="fw-bold mb-0" style="color:#004a38;">Customers</h5>
        <?php if(in_array($_SESSION['roleName'], ['Admin','Cashier'])): ?>
        <button class="btn btn-ev" data-bs-toggle="modal" data-bs-target="#addCustomerModal"><i class="bi bi-person-plus me-1"></i>Add Customer</button>
        <?php endif; ?>
    </div>

    <div class="card card-shadow">
        <div class="card-body">
            <table id="customerTable" class="table table-bordered table-striped text-center">
                <thead style="background:var(--ev-gradient);color:#fff;">
                    <tr><th>Name</th><th>Contact</th><th>Email</th><th>Address</th><th>Credit Balance</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php foreach($customers as $row):
                    $creditClass = $row['credit_balance'] > 0 ? 'text-danger fw-bold' : 'text-success';
                ?>
                <tr>
                    <td class="text-start fw-semibold"><?php echo htmlspecialchars($row['customerName']); ?></td>
                    <td><?php echo htmlspecialchars($row['contactNo'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($row['email'] ?? '—'); ?></td>
                    <td class="text-start text-muted small"><?php echo htmlspecialchars($row['address'] ?? '—'); ?></td>
                    <td><span class="<?php echo $creditClass; ?>">₱<?php echo number_format($row['credit_balance'],2); ?></span></td>
                    <td>
                        <?php if(in_array($_SESSION['roleName'], ['Admin','Owner','Cashier'])): ?>
                        <button class="btn btn-sm btn-info text-white me-1"
                            onclick="openCreditModal(<?php echo $row['customerID']; ?>)"
                            title="Manage Utang"><i class="bi bi-wallet2"></i></button>
                        <?php endif; ?>
                        <?php if($_SESSION['roleName']==='Admin'): ?>
                        <button class="btn btn-sm btn-primary me-1"
                            onclick="openEditModal(<?php echo $row['customerID']; ?>)"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-danger"
                            onclick="openDeleteModal(<?php echo $row['customerID']; ?>, '<?php echo addslashes($row['customerName']); ?>')"><i class="bi bi-trash"></i></button>
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

<!-- Add Customer Modal -->
<?php if(in_array($_SESSION['roleName'], ['Admin','Cashier'])): ?>
<div class="modal fade" id="addCustomerModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/customerAuth.php">
            <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title"><i class="bi bi-person-plus me-1"></i>Add Customer</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label fw-semibold small">Name <span class="text-danger">*</span></label><input type="text" name="customerName" class="form-control" required></div>
        <div class="mb-2"><label class="form-label fw-semibold small">Contact No</label><input type="text" name="contactNo" class="form-control"></div>
        <div class="mb-2"><label class="form-label fw-semibold small">Email</label><input type="email" name="email" class="form-control"></div>
        <div class="mb-2"><label class="form-label fw-semibold small">Address</label><textarea name="address" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="customerSave" class="btn btn-ev">Save Customer</button>
      </div>
    </form>
  </div></div>
</div>
<?php endif; ?>

<!-- Credit / Utang Modal (single shared, populated via JS) -->
<div class="modal fade" id="creditModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
      <h5 class="modal-title"><i class="bi bi-wallet2 me-1"></i>Manage Credit – <span id="creditModalName"></span></h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" id="creditModalBody">
      <div class="text-center py-4"><div class="spinner-border" style="color:#008161"></div></div>
    </div>
  </div></div>
</div>

<!-- Edit Customer Modal (single shared) -->
<?php if($_SESSION['roleName']==='Admin'): ?>
<div class="modal fade" id="editCustomerModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/customerAuth.php">
            <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title">Edit Customer</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="customerID" id="editCustomerID">
        <div class="mb-2"><label class="form-label fw-semibold small">Name <span class="text-danger">*</span></label><input type="text" name="customerName" id="editCustomerName" class="form-control" required></div>
        <div class="mb-2"><label class="form-label fw-semibold small">Contact No</label><input type="text" name="contactNo" id="editContactNo" class="form-control"></div>
        <div class="mb-2"><label class="form-label fw-semibold small">Email</label><input type="email" name="email" id="editEmail" class="form-control"></div>
        <div class="mb-2"><label class="form-label fw-semibold small">Address</label><textarea name="address" id="editAddress" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="customerUpdate" class="btn btn-ev">Update</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Delete Modal (single shared) -->
<div class="modal fade" id="deleteCustomerModal" tabindex="-1">
  <div class="modal-dialog modal-sm"><div class="modal-content">
    <form method="POST" action="../backend/customerAuth.php">
            <?php csrf_field(); ?>
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Delete Customer</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="customerID" id="deleteCustomerID">
        <p>Delete <strong id="deleteCustomerName"></strong>?</p>
        <p class="text-muted small">This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="customerDeleted" class="btn btn-danger btn-sm">Delete</button>
      </div>
    </form>
  </div></div>
</div>
<?php endif; ?>

<!-- Embed customer data for JS -->
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

// DataTable
$(document).ready(function(){
    $('#customerTable').DataTable({pageLength:10, order:[[0,'asc']]});
});

// Open Credit/Utang modal — load content via AJAX
function openCreditModal(customerID){
    const c = customerData[customerID];
    document.getElementById('creditModalName').textContent = c.customerName;
    document.getElementById('creditModalBody').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border" style="color:#008161"></div></div>';
    new bootstrap.Modal(document.getElementById('creditModal')).show();

    fetch('../backend/getCreditDetails.php?customerID=' + customerID)
        .then(r => r.text())
        .then(html => { document.getElementById('creditModalBody').innerHTML = html; })
        .catch(() => { document.getElementById('creditModalBody').innerHTML = '<div class="alert alert-danger">Failed to load credit details.</div>'; });
}

// Open Edit modal
function openEditModal(customerID){
    const c = customerData[customerID];
    document.getElementById('editCustomerID').value   = c.customerID;
    document.getElementById('editCustomerName').value = c.customerName;
    document.getElementById('editContactNo').value    = c.contactNo;
    document.getElementById('editEmail').value        = c.email;
    document.getElementById('editAddress').value      = c.address;
    new bootstrap.Modal(document.getElementById('editCustomerModal')).show();
}

// Open Delete modal
function openDeleteModal(customerID, customerName){
    document.getElementById('deleteCustomerID').value      = customerID;
    document.getElementById('deleteCustomerName').textContent = customerName;
    new bootstrap.Modal(document.getElementById('deleteCustomerModal')).show();
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
