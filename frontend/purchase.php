<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';

session_start();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if(!in_array($_SESSION['roleName'], ['Admin','Owner'])){ header("Location: dashboard.php"); exit(); }

$pageTitle = "Purchase Orders – 7Evelyn POS";

// Fetch suppliers and products for the create modal
$suppliers = $conn->query("SELECT supplierID, companyName FROM supplier WHERE dateDeleted IS NULL ORDER BY companyName");
$products  = $conn->query("SELECT productID, productName, cost FROM product WHERE status='Active' ORDER BY productName");

// Summary stats
$totalPending  = $conn->query("SELECT COUNT(*) AS c FROM purchase_order WHERE status='Pending'")->fetch_assoc()['c'];
$totalReceived = $conn->query("SELECT COUNT(*) AS c FROM purchase_order WHERE status='Received'")->fetch_assoc()['c'];
$totalCancelled= $conn->query("SELECT COUNT(*) AS c FROM purchase_order WHERE status='Cancelled'")->fetch_assoc()['c'];
$monthSpend    = $conn->query("SELECT COALESCE(SUM(pod.qty_ordered * pod.unit_cost),0) AS t FROM purchase_order_details pod JOIN purchase_order po ON pod.poID=po.poID WHERE po.status='Received' AND MONTH(po.dateReceived)=MONTH(CURDATE()) AND YEAR(po.dateReceived)=YEAR(CURDATE())")->fetch_assoc()['t'];

// Filter
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

<!-- Topbar -->
<div class="topbar no-print">
    <h5><i class="bi bi-bag-check me-2" style="color:var(--ev-purple);"></i>Purchase Orders</h5>
    <div class="ms-auto">
        <div class="user-badge">
            <i class="bi bi-person-circle" style="color:var(--ev-purple);"></i>
            <span><?php echo htmlspecialchars($_SESSION['userName']); ?></span>
            <span class="role-pill"><?php echo htmlspecialchars($_SESSION['roleName']); ?></span>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php include 'Message/purchaseMessage.php'; ?>

<div class="page-body">

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card bg-grad-orange">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-val"><?php echo $totalPending; ?></div>
                        <div class="stat-lbl">Pending POs</div>
                    </div>
                    <i class="bi bi-hourglass-split stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card bg-grad-blue">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-val"><?php echo $totalReceived; ?></div>
                        <div class="stat-lbl">Received POs</div>
                    </div>
                    <i class="bi bi-box-seam stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card bg-grad-red">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-val"><?php echo $totalCancelled; ?></div>
                        <div class="stat-lbl">Cancelled</div>
                    </div>
                    <i class="bi bi-x-circle stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card bg-grad-green">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-val" style="font-size:1.3rem;">₱<?php echo number_format($monthSpend,0); ?></div>
                        <div class="stat-lbl">This Month Spend</div>
                    </div>
                    <i class="bi bi-currency-exchange stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="fw-bold mb-0" style="color:#004a38;">Purchase Order List</h5>
        <div class="d-flex gap-2 flex-wrap">
            <!-- Status Filter -->
            <form method="GET" class="d-flex gap-1 align-items-center">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width:140px;">
                    <option value="">All Statuses</option>
                    <option value="Pending"   <?php echo ($statusFilter==='Pending')   ? 'selected' : ''; ?>>Pending</option>
                    <option value="Received"  <?php echo ($statusFilter==='Received')  ? 'selected' : ''; ?>>Received</option>
                    <option value="Cancelled" <?php echo ($statusFilter==='Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </form>
            <?php if($_SESSION['roleName']==='Admin'): ?>
            <button class="btn btn-ev" data-bs-toggle="modal" data-bs-target="#createPOModal">
                <i class="bi bi-plus-circle me-1"></i>New Purchase Order
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Table -->
    <div class="card card-shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="poTable" class="table table-bordered table-hover mb-0 text-center">
                    <thead style="background:var(--ev-gradient);color:#fff;">
                        <tr>
                            <th>#</th>
                            <th>Supplier</th>
                            <th>Created By</th>
                            <th>Status</th>
                            <th>Total Amount</th>
                            <th>Date Created</th>
                            <th>Date Received</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $pos->data_seek(0);
                    $poRows = [];
                    while($po = $pos->fetch_assoc()):
                        $poRows[] = $po;
                        $statusClass = match($po['status']) {
                            'Pending'   => 'badge-pending',
                            'Received'  => 'badge-received',
                            'Cancelled' => 'badge-inactive',
                            default     => 'badge-active',
                        };
                    ?>
                    <tr>
                        <td class="fw-bold"><?php echo $po['poID']; ?></td>
                        <td class="text-start"><?php echo htmlspecialchars($po['companyName']); ?></td>
                        <td class="text-muted small"><?php echo htmlspecialchars($po['createdBy']); ?></td>
                        <td><span class="<?php echo $statusClass; ?>"><?php echo $po['status']; ?></span></td>
                        <td><strong>₱<?php echo number_format($po['total_amount'],2); ?></strong></td>
                        <td><?php echo date('M d, Y', strtotime($po['dateCreated'])); ?></td>
                        <td><?php echo $po['dateReceived'] ? date('M d, Y', strtotime($po['dateReceived'])) : '—'; ?></td>
                        <td class="text-muted small text-start"><?php echo htmlspecialchars($po['notes'] ?? '—'); ?></td>
                        <td>
                            <div class="d-flex gap-1 justify-content-center flex-wrap">
                                <!-- View -->
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewPOModal<?php echo $po['poID']; ?>" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </button>

                                <?php if($po['status'] === 'Pending' && $_SESSION['roleName']==='Admin'): ?>
                                <!-- Receive -->
                                <form method="POST" action="../backend/purchaseAuth.php" class="d-inline" id="receiveForm<?php echo $po['poID']; ?>
            <?php csrf_field(); ?>">
                                    <input type="hidden" name="poID" value="<?php echo $po['poID']; ?>">
                                    <button type="button" name="receivePO" class="btn btn-sm btn-success" title="Receive PO"
                                        onclick="Swal.fire({
                                            title: 'Receive PO #<?php echo $po['poID']; ?>?',
                                            text: 'This will update inventory based on the order items.',
                                            icon: 'question',
                                            showCancelButton: true,
                                            confirmButtonColor: '#008161',
                                            confirmButtonText: 'Yes, Receive',
                                            cancelButtonText: 'No, go back'
                                        }).then(r => { if(r.isConfirmed){ let f=document.getElementById('receiveForm<?php echo $po['poID']; ?>'); f.innerHTML+='<input name=\'receivePO\' type=\'hidden\'>'; f.submit(); } })">
                                        <i class="bi bi-check2-circle"></i>
                                    </button>
                                </form>
                                <!-- Cancel -->
                                <form method="POST" action="../backend/purchaseAuth.php" class="d-inline" id="cancelForm<?php echo $po['poID']; ?>
            <?php csrf_field(); ?>">
                                    <input type="hidden" name="poID" value="<?php echo $po['poID']; ?>">
                                    <button type="button" class="btn btn-sm btn-danger" title="Cancel PO"
                                        onclick="Swal.fire({
                                            title: 'Cancel PO #<?php echo $po['poID']; ?>?',
                                            text: 'This action cannot be undone.',
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#f01b2d',
                                            confirmButtonText: 'Yes, Cancel PO',
                                            cancelButtonText: 'No, go back'
                                        }).then(r => { if(r.isConfirmed){ let f=document.getElementById('cancelForm<?php echo $po['poID']; ?>'); f.innerHTML+='<input name=\'cancelPO\' type=\'hidden\'>'; f.submit(); } })">
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
        </div>
    </div>
</div>

<!-- Create PO Modal (once, outside loop) -->
<?php if($_SESSION['roleName']==='Admin'): ?>
<?php include 'Modal/purchaseCreateModal.php'; ?>
<?php endif; ?>

<!-- View PO Modals (rendered outside the table to avoid broken HTML structure) -->
<?php foreach($poRows as $po): ?>
<?php include 'Modal/purchaseViewModal.php'; ?>
<?php endforeach; ?>

<script>
$(document).ready(function(){
    $('#poTable').DataTable({
        pageLength: 15,
        order: [[0, 'desc']],
        columnDefs: [{ orderable: false, targets: [8] }]
    });
});
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
</body>
</html>