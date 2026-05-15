<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';

session_start();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }

$pageTitle = "Dashboard – 7Evelyn POS";

// Stats
$totalProducts   = $conn->query("SELECT COUNT(*) AS c FROM product WHERE status='Active'")->fetch_assoc()['c'];
$lowStock        = $conn->query("SELECT COUNT(*) AS c FROM product WHERE stock_quantity <= reorder_level AND status='Active' AND stock_quantity > 0")->fetch_assoc()['c'];
$outOfStock      = $conn->query("SELECT COUNT(*) AS c FROM product WHERE stock_quantity = 0 AND status='Active'")->fetch_assoc()['c'];
$todaySales      = $conn->query("SELECT COALESCE(SUM(total_amount),0) AS c FROM sales WHERE DATE(saleDate)=CURDATE()")->fetch_assoc()['c'];
$totalSalesCount = $conn->query("SELECT COUNT(*) AS c FROM sales WHERE DATE(saleDate)=CURDATE()")->fetch_assoc()['c'];
$totalCustomers  = $conn->query("SELECT COUNT(*) AS c FROM customer WHERE dateDeleted IS NULL")->fetch_assoc()['c'];
$totalSuppliers  = $conn->query("SELECT COUNT(*) AS c FROM supplier WHERE dateDeleted IS NULL")->fetch_assoc()['c'];
$todayExpenses   = $conn->query("SELECT COALESCE(SUM(amount),0) AS c FROM expense WHERE expense_date=CURDATE()")->fetch_assoc()['c'];
$pendingPO       = $conn->query("SELECT COUNT(*) AS c FROM purchase_order WHERE status='Pending'")->fetch_assoc()['c'];

// Alert: expiring soon
$expiryAlert = $conn->query("SELECT COUNT(*) AS c FROM product WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date >= CURDATE() AND status='Active'")->fetch_assoc()['c'];

// Weekly sales chart data
$weekSales = $conn->query("SELECT DATE(saleDate) AS d, SUM(total_amount) AS total FROM sales WHERE saleDate >= DATE_SUB(CURDATE(),INTERVAL 6 DAY) GROUP BY DATE(saleDate) ORDER BY d ASC");
$chartLabels = []; $chartData = [];
while($r = $weekSales->fetch_assoc()){ $chartLabels[] = date('D',strtotime($r['d'])); $chartData[] = floatval($r['total']); }

// Recent transactions
$recentSales = $conn->query("SELECT s.salesID, CONCAT(u.givenName,' ',u.surName) AS cashier, s.total_amount, s.payment_method, s.saleDate FROM sales s JOIN users u ON s.userID=u.userID ORDER BY s.saleDate DESC LIMIT 8");

// Low stock products
$lowStockProds = $conn->query("SELECT productName, stock_quantity, reorder_level FROM product WHERE stock_quantity <= reorder_level AND status='Active' ORDER BY stock_quantity ASC LIMIT 8");

// Best selling products
$bestSellers = $conn->query("SELECT p.productName, SUM(sd.sold_quantity) AS total_sold, SUM(sd.subtotal) AS revenue FROM sales_details sd JOIN product p ON sd.productID=p.productID JOIN sales s ON sd.salesID=s.salesID WHERE s.saleDate >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) GROUP BY sd.productID ORDER BY total_sold DESC LIMIT 5");
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<!-- Topbar -->
<div class="topbar no-print">
    <h5><i class="bi bi-speedometer2 me-2" style="color:var(--ev-purple);"></i>Dashboard</h5>
    <div class="ms-auto">
        <div class="user-badge">
            <i class="bi bi-person-circle" style="color:var(--ev-purple);"></i>
            <span><?php echo htmlspecialchars($_SESSION['userName']); ?></span>
            <span class="role-pill"><?php echo htmlspecialchars($_SESSION['roleName']); ?></span>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold" style="color:#004a38;">Welcome back, <?php echo htmlspecialchars(explode(' ',$_SESSION['userName'])[0]); ?>! 👋</h4>
            <p class="text-muted small mb-0"><?php echo date('l, F d, Y'); ?> &nbsp;|&nbsp; 7Evelyn POS</p>
        </div>
    </div>

    <!-- Alert Banners -->
    <?php if($outOfStock > 0): ?>
    <div class="alert alert-danger d-flex align-items-center mb-3 py-2" role="alert">
        <i class="bi bi-x-circle-fill me-2"></i>
        <strong><?php echo $outOfStock; ?> product(s) are out of stock!</strong>&nbsp;
        <a href="stocks.php" class="ms-auto btn btn-sm btn-danger">Restock Now</a>
    </div>
    <?php endif; ?>
    <?php if($expiryAlert > 0): ?>
    <div class="alert alert-warning d-flex align-items-center mb-3 py-2" role="alert">
        <i class="bi bi-clock-history me-2"></i>
        <strong><?php echo $expiryAlert; ?> product(s) expiring within 30 days!</strong>&nbsp;
        <a href="product.php" class="ms-auto btn btn-sm btn-warning">Review</a>
    </div>
    <?php endif; ?>
    <?php if($pendingPO > 0): ?>
    <div class="alert alert-info d-flex align-items-center mb-3 py-2" role="alert">
        <i class="bi bi-bag-check me-2"></i>
        <strong><?php echo $pendingPO; ?> pending purchase order(s) awaiting receipt.</strong>&nbsp;
        <a href="purchase.php" class="ms-auto btn btn-sm btn-info text-white">View POs</a>
    </div>
    <?php endif; ?>

    <!-- Stat Cards Row 1 -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="stat-card bg-grad-purple">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-val">₱<?php echo number_format($todaySales,2); ?></div>
                        <div class="stat-lbl">Today's Sales (<?php echo $totalSalesCount; ?> txn)</div>
                    </div>
                    <i class="bi bi-cash-coin stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card bg-grad-blue">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-val"><?php echo $totalProducts; ?></div>
                        <div class="stat-lbl">Active Products</div>
                    </div>
                    <i class="bi bi-box-seam stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card bg-grad-red">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-val"><?php echo $lowStock + $outOfStock; ?></div>
                        <div class="stat-lbl">Low/Out of Stock</div>
                    </div>
                    <i class="bi bi-exclamation-triangle stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card bg-grad-green">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-val"><?php echo $totalCustomers; ?></div>
                        <div class="stat-lbl">Total Customers</div>
                    </div>
                    <i class="bi bi-people stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat Cards Row 2 -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card bg-grad-orange">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-val">₱<?php echo number_format($todayExpenses,2); ?></div>
                        <div class="stat-lbl">Today's Expenses</div>
                    </div>
                    <i class="bi bi-wallet2 stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card bg-grad-teal">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-val"><?php echo $totalSuppliers; ?></div>
                        <div class="stat-lbl">Suppliers</div>
                    </div>
                    <i class="bi bi-truck stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card bg-grad-purple">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-val"><?php echo $expiryAlert; ?></div>
                        <div class="stat-lbl">Expiring (30 days)</div>
                    </div>
                    <i class="bi bi-clock-history stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card bg-grad-blue">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-val"><?php echo $pendingPO; ?></div>
                        <div class="stat-lbl">Pending POs</div>
                    </div>
                    <i class="bi bi-bag-check stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Sales Chart -->
        <div class="col-md-8">
            <div class="card card-shadow">
                <div class="card-header bg-white fw-bold py-3" style="border-bottom:1px solid #d6ede6;">
                    <i class="bi bi-bar-chart-line me-2" style="color:var(--ev-purple);"></i>7-Day Sales Overview
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="110"></canvas>
                </div>
            </div>
        </div>
        <!-- Best Sellers -->
        <div class="col-md-4">
            <div class="card card-shadow h-100">
                <div class="card-header bg-white fw-bold py-3" style="border-bottom:1px solid #d6ede6;">
                    <i class="bi bi-trophy me-2" style="color:var(--ev-purple);"></i>Top Products (30d)
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>#</th><th>Product</th><th>Sold</th></tr></thead>
                        <tbody>
                        <?php $rank=1; while($r=$bestSellers->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge" style="background:var(--ev-gradient);color:#fff;"><?php echo $rank++; ?></span></td>
                            <td class="text-truncate" style="max-width:130px;"><?php echo htmlspecialchars($r['productName']); ?></td>
                            <td><strong><?php echo $r['total_sold']; ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($rank === 1): ?><tr><td colspan="3" class="text-center text-muted py-3">No sales data yet.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <!-- Recent Transactions -->
        <div class="col-md-7">
            <div class="card card-shadow">
                <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between" style="border-bottom:1px solid #d6ede6;">
                    <span><i class="bi bi-receipt me-2" style="color:var(--ev-purple);"></i>Recent Transactions</span>
                    <a href="sales.php" class="btn btn-sm btn-ev">View All</a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light"><tr><th>#</th><th>Cashier</th><th>Total</th><th>Method</th><th>Time</th></tr></thead>
                        <tbody>
                        <?php while($r=$recentSales->fetch_assoc()): ?>
                        <tr>
                            <td><small class="text-muted">#<?php echo $r['salesID']; ?></small></td>
                            <td><?php echo htmlspecialchars($r['cashier']); ?></td>
                            <td><strong>₱<?php echo number_format($r['total_amount'],2); ?></strong></td>
                            <td><span class="badge-active"><?php echo $r['payment_method']; ?></span></td>
                            <td class="text-muted small"><?php echo date('h:i A',strtotime($r['saleDate'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Low Stock -->
        <div class="col-md-5">
            <div class="card card-shadow h-100">
                <div class="card-header bg-white fw-bold py-3" style="border-bottom:1px solid #d6ede6;">
                    <i class="bi bi-exclamation-triangle me-2 text-danger"></i>Low Stock Alert
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light"><tr><th>Product</th><th>Stock</th><th>Reorder</th></tr></thead>
                        <tbody>
                        <?php while($r=$lowStockProds->fetch_assoc()): ?>
                        <tr>
                            <td class="text-truncate" style="max-width:120px;"><?php echo htmlspecialchars($r['productName']); ?></td>
                            <td>
                                <?php if($r['stock_quantity'] == 0): ?>
                                <span class="badge-inactive">OUT</span>
                                <?php else: ?>
                                <span class="badge-low"><?php echo $r['stock_quantity']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?php echo $r['reorder_level']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</div></div><!-- end main-content + main-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chartLabels); ?>,
        datasets: [{
            label: 'Sales (₱)',
            data: <?php echo json_encode($chartData); ?>,
            backgroundColor: '#008161',
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f0f7f5' } },
            x: { grid: { display: false } }
        }
    }
});
</script>

<!-- ── Pusher Real-time ───────────────────────────────────────────────────── -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
    const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>
<!-- ─────────────────────────────────────────────────────────────────────── -->
</body></html>
