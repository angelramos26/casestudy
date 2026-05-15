<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';

session_start();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
$pageTitle = "Sales Records – 7Evelyn POS";

$dateFrom = $_GET['date_from'] ?? date('Y-m-d');
$dateTo   = $_GET['date_to'] ?? date('Y-m-d');
$roleFilter = in_array($_SESSION['roleName'], ['Admin','Owner']);
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<div class="topbar no-print">
    <h5><i class="bi bi-receipt me-2" style="color:var(--ev-purple);"></i>Sales Records</h5>
    <div class="ms-auto"><div class="user-badge"><i class="bi bi-person-circle" style="color:var(--ev-purple);"></i><span><?php echo htmlspecialchars($_SESSION['userName']); ?></span><span class="role-pill"><?php echo htmlspecialchars($_SESSION['roleName']); ?></span></div></div>
</div>

<div class="page-body">

    <!-- Filter -->
    <div class="card card-shadow mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3"><label class="form-label small">From</label><input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo $dateFrom; ?>"></div>
                <div class="col-md-3"><label class="form-label small">To</label><input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo $dateTo; ?>"></div>
                <div class="col-md-3"><label class="form-label small">Payment Method</label>
                  <select name="method" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="Cash" <?php echo ($_GET['method']??'')==='Cash'?'selected':''; ?>>Cash</option>
                    <option value="GCash" <?php echo ($_GET['method']??'')==='GCash'?'selected':''; ?>>GCash</option>
                    <option value="Card" <?php echo ($_GET['method']??'')==='Card'?'selected':''; ?>>Card</option>
                    <option value="Credit" <?php echo ($_GET['method']??'')==='Credit'?'selected':''; ?>>Credit</option>
                  </select>
                </div>
                <div class="col-md-3"><button type="submit" class="btn btn-ev btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button></div>
            </form>
        </div>
    </div>

    <?php
    $method = sanitize($_GET['method'] ?? '');
    $sql = "SELECT s.salesID, CONCAT(u.givenName,' ',u.surName) AS cashier, c.customerName, s.total_amount, s.discount_amount, s.tax_amount, s.payment, s.change_amount, s.payment_method, s.saleDate FROM sales s JOIN users u ON s.userID=u.userID LEFT JOIN customer c ON s.customerID=c.customerID WHERE DATE(s.saleDate) BETWEEN '$dateFrom' AND '$dateTo'";
    if($method) $sql .= " AND s.payment_method='$method'";
    $sql .= " ORDER BY s.saleDate DESC";
    $sales = $conn->query($sql);

    // Totals
    $totalsql = "SELECT COALESCE(SUM(total_amount),0) AS revenue, COUNT(*) AS txnCount, COALESCE(SUM(discount_amount),0) AS totalDisc FROM sales WHERE DATE(saleDate) BETWEEN '$dateFrom' AND '$dateTo'";
    if($method) $totalsql .= " AND payment_method='$method'";
    $totals = $conn->query($totalsql)->fetch_assoc();
    ?>

    <!-- Summary Strip -->
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="stat-card bg-grad-purple">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-val">₱<?php echo number_format($totals['revenue'],2); ?></div><div class="stat-lbl">Total Revenue</div></div>
                    <i class="bi bi-cash-coin stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-grad-blue">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-val"><?php echo $totals['txnCount']; ?></div><div class="stat-lbl">Transactions</div></div>
                    <i class="bi bi-receipt stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-grad-orange">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-val">₱<?php echo number_format($totals['totalDisc'],2); ?></div><div class="stat-lbl">Total Discounts</div></div>
                    <i class="bi bi-tag stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-grad-green">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-val">₱<?php echo $totals['txnCount'] > 0 ? number_format($totals['revenue']/$totals['txnCount'],2) : '0.00'; ?></div><div class="stat-lbl">Avg per Transaction</div></div>
                    <i class="bi bi-bar-chart stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-shadow">
        <div class="card-body">
            <table id="salesTable" class="table table-bordered table-striped text-center">
                <thead style="background:var(--ev-gradient);color:#fff;">
                    <tr><th>#</th><th>Cashier</th><th>Customer</th><th>Subtotal</th><th>Discount</th><th>Total</th><th>Payment</th><th>Method</th><th>Date/Time</th><th>Details</th></tr>
                </thead>
                <tbody>
                <?php while($r=$sales->fetch_assoc()): ?>
                <tr>
                    <td><small class="text-muted">#<?php echo $r['salesID']; ?></small></td>
                    <td><?php echo htmlspecialchars($r['cashier']); ?></td>
                    <td class="text-muted small"><?php echo htmlspecialchars($r['customerName'] ?? 'Walk-in'); ?></td>
                    <td>₱<?php echo number_format($r['total_amount']+$r['discount_amount']-$r['tax_amount'],2); ?></td>
                    <td class="text-danger">-₱<?php echo number_format($r['discount_amount'],2); ?></td>
                    <td><strong>₱<?php echo number_format($r['total_amount'],2); ?></strong></td>
                    <td>₱<?php echo number_format($r['payment'],2); ?></td>
                    <td><span class="badge-active"><?php echo $r['payment_method']; ?></span></td>
                    <td class="small text-muted"><?php echo date('M d, Y h:i A',strtotime($r['saleDate'])); ?></td>
                    <td><button class="btn btn-sm btn-outline-secondary" onclick="viewSaleDetails(<?php echo $r['salesID']; ?>)"><i class="bi bi-eye"></i></button></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Sale Detail Modal -->
<div class="modal fade" id="saleDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
      <h5 class="modal-title"><i class="bi bi-receipt me-1"></i>Transaction Details</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" id="saleDetailBody"><div class="text-center py-4"><div class="spinner-border" style="color:#008161"></div></div></div>
  </div></div>
</div>

<script>
$(document).ready(function(){ $('#salesTable').DataTable({pageLength:20,order:[[8,'desc'],paging:false}); });

function viewSaleDetails(salesID){
    $('#saleDetailModal').modal('show');
    $('#saleDetailBody').html('<div class="text-center py-4"><div class="spinner-border" style="color:#008161"></div></div>');
    $.get('../backend/getSaleDetails.php', {salesID: salesID}, function(data){
        $('#saleDetailBody').html(data);
    });
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
