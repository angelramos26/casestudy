<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';

session_start();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }

$pageTitle = "Dashboard – 7Evelyn POS";

$totalProducts   = $conn->query("SELECT COUNT(*) AS c FROM product WHERE status='Active'")->fetch_assoc()['c'];
$lowStock        = $conn->query("SELECT COUNT(*) AS c FROM product WHERE stock_quantity <= reorder_level AND status='Active' AND stock_quantity > 0")->fetch_assoc()['c'];
$outOfStock      = $conn->query("SELECT COUNT(*) AS c FROM product WHERE stock_quantity = 0 AND status='Active'")->fetch_assoc()['c'];
$todaySales      = $conn->query("SELECT COALESCE(SUM(total_amount),0) AS c FROM sales WHERE DATE(saleDate)=CURDATE()")->fetch_assoc()['c'];
$totalSalesCount = $conn->query("SELECT COUNT(*) AS c FROM sales WHERE DATE(saleDate)=CURDATE()")->fetch_assoc()['c'];
$totalCustomers  = $conn->query("SELECT COUNT(*) AS c FROM customer WHERE dateDeleted IS NULL")->fetch_assoc()['c'];
$totalSuppliers  = $conn->query("SELECT COUNT(*) AS c FROM supplier WHERE dateDeleted IS NULL")->fetch_assoc()['c'];
$todayExpenses   = $conn->query("SELECT COALESCE(SUM(amount),0) AS c FROM expense WHERE expense_date=CURDATE()")->fetch_assoc()['c'];
$pendingPO       = $conn->query("SELECT COUNT(*) AS c FROM purchase_order WHERE status='Pending'")->fetch_assoc()['c'];
$expiryAlert     = $conn->query("SELECT COUNT(*) AS c FROM product WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date >= CURDATE() AND status='Active'")->fetch_assoc()['c'];

$weekSales = $conn->query("SELECT DATE(saleDate) AS d, SUM(total_amount) AS total FROM sales WHERE saleDate >= DATE_SUB(CURDATE(),INTERVAL 6 DAY) GROUP BY DATE(saleDate) ORDER BY d ASC");
$chartLabels = []; $chartData = [];
while($r = $weekSales->fetch_assoc()){ $chartLabels[] = date('D',strtotime($r['d'])); $chartData[] = floatval($r['total']); }

$recentSales   = $conn->query("SELECT s.salesID, CONCAT(u.givenName,' ',u.surName) AS cashier, s.total_amount, s.payment_method, s.saleDate FROM sales s JOIN users u ON s.userID=u.userID ORDER BY s.saleDate DESC LIMIT 8");
$lowStockProds = $conn->query("SELECT productName, stock_quantity, reorder_level FROM product WHERE stock_quantity <= reorder_level AND status='Active' ORDER BY stock_quantity ASC LIMIT 8");
$bestSellers   = $conn->query("SELECT p.productName, SUM(sd.sold_quantity) AS total_sold, SUM(sd.subtotal) AS revenue FROM sales_details sd JOIN product p ON sd.productID=p.productID JOIN sales s ON sd.salesID=s.salesID WHERE s.saleDate >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) GROUP BY sd.productID ORDER BY total_sold DESC LIMIT 5");
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<style>
/* ── Dashboard page-specific overrides ── */
.page-body { padding: 28px 32px; }
.dash-welcome { display:flex; align-items:center; justify-content:space-between; margin-bottom:22px; }
.dash-welcome .greet-name { font-size:1.4rem; font-weight:800; color:#262341; letter-spacing:-0.3px; }
.dash-welcome .greet-sub  { font-size:13px; color:#6b6880; margin-top:2px; }
.dash-date-badge { background:#262341; color:#F9D94A; padding:6px 16px; border-radius:20px; font-size:12.5px; font-weight:600; letter-spacing:.3px; }

.dash-alert { display:flex; align-items:center; gap:12px; padding:11px 18px; border-radius:10px; font-size:13.5px; font-weight:500; margin-bottom:10px; border-left:4px solid; }
.dash-alert.danger  { background:#fff0f2; border-color:#e8435a; color:#7f1d1d; }
.dash-alert.warning { background:#fffbeb; border-color:#F9D94A; color:#78350f; }
.dash-alert.info    { background:#eff6ff; border-color:#3b82f6; color:#1e40af; }
.dash-alert .alert-action { margin-left:auto; padding:4px 14px; border-radius:6px; font-size:12px; font-weight:700; text-decoration:none; white-space:nowrap; border:none; cursor:pointer; }
.dash-alert.danger  .alert-action { background:#e8435a; color:#fff; }
.dash-alert.warning .alert-action { background:#F9D94A; color:#262341; }
.dash-alert.info    .alert-action { background:#3b82f6; color:#fff; }

.stat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:22px; }
.section-label { font-size:10px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:#6b6880; margin-bottom:12px; display:flex; align-items:center; gap:8px; }
.section-label::after { content:''; flex:1; height:1px; background:#e5e2f0; }

.dash-card { background:#fff; border-radius:16px; border:1.5px solid #e5e2f0; overflow:hidden; }
.dash-card-header { padding:15px 20px; border-bottom:1.5px solid #e5e2f0; display:flex; align-items:center; gap:10px; font-weight:700; font-size:14px; color:#1a1830; background:#fafbff; }
.dash-card-header .header-icon { width:30px; height:30px; background:#F9D94A; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:14px; color:#262341; flex-shrink:0; }
.dash-card-body { padding:0; }

.dash-table { width:100%; border-collapse:collapse; font-size:13px; }
.dash-table thead tr { background:#f7f6fb; }
.dash-table thead th { padding:10px 16px; font-size:10.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:#6b6880; white-space:nowrap; border-bottom:1.5px solid #e5e2f0; }
.dash-table tbody td { padding:10px 16px; border-bottom:1px solid #f3f1f9; color:#1a1830; vertical-align:middle; }
.dash-table tbody tr:last-child td { border-bottom:none; }
.dash-table tbody tr:hover td { background:#fdfcff; }

.pill { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11.5px; font-weight:700; }
.pill-active { background:#dcfce7; color:#166534; }
.pill-out    { background:#fee2e2; color:#991b1b; }
.pill-low    { background:#fef3c7; color:#92400e; }
.pill-method { background:#ede9ff; color:#3d2ea0; }

.rank-dot { width:24px; height:24px; background:#262341; color:#F9D94A; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; }
.btn-viewall { margin-left:auto; background:#262341; color:#F9D94A; border:none; padding:5px 14px; border-radius:8px; font-size:12px; font-weight:700; text-decoration:none; transition:opacity .15s; }
.btn-viewall:hover { opacity:.85; color:#F9D94A; }
.chart-wrap { padding:20px; }

@media(max-width:900px){ .stat-grid { grid-template-columns:repeat(2,1fr); } .page-body { padding:16px; } }
@media(max-width:580px){ .stat-grid { grid-template-columns:1fr 1fr; gap:10px; } .scard-val { font-size:1.3rem; } }
</style>

<?php
$topbarTitle = 'Dashboard';
$topbarIcon  = 'bi-speedometer2';
$topbarSub   = 'Overview of your store performance';
include 'topbar.php';
?>

<div class="page-body">

    <!-- Welcome -->
    <div class="dash-welcome">
        <div>
            <div class="greet-name">Good <?php
                $h = (int)date('G');
                echo $h < 12 ? 'morning' : ($h < 17 ? 'afternoon' : 'evening');
            ?>, <?php echo htmlspecialchars(explode(' ',$_SESSION['userName'])[0]); ?> 👋</div>
            <div class="greet-sub">Here's what's happening at 7Evelyn today.</div>
        </div>
        <div class="dash-date-badge">
            <i class="bi bi-calendar3 me-1"></i><?php echo date('l, F d, Y'); ?>
        </div>
    </div>

    <!-- Alerts -->
    <?php if($outOfStock > 0): ?>
    <div class="dash-alert danger">
        <i class="bi bi-x-circle-fill"></i>
        <span><strong><?php echo $outOfStock; ?> product(s)</strong> are currently out of stock.</span>
        <a href="stocks.php" class="alert-action">Restock Now</a>
    </div>
    <?php endif; ?>
    <?php if($expiryAlert > 0): ?>
    <div class="dash-alert warning">
        <i class="bi bi-clock-history"></i>
        <span><strong><?php echo $expiryAlert; ?> product(s)</strong> are expiring within 30 days.</span>
        <a href="product.php" class="alert-action">Review</a>
    </div>
    <?php endif; ?>
    <?php if($pendingPO > 0): ?>
    <div class="dash-alert info">
        <i class="bi bi-bag-check"></i>
        <span><strong><?php echo $pendingPO; ?> purchase order(s)</strong> are awaiting receipt.</span>
        <a href="purchase.php" class="alert-action">View POs</a>
    </div>
    <?php endif; ?>

    <div style="height:6px;"></div>

    <!-- Stat Cards Row 1 -->
    <div class="section-label">Today's Overview</div>
    <div class="stat-grid">
        <div class="scard scard-navy">
            <div class="scard-icon"><i class="bi bi-cash-coin"></i></div>
            <div>
                <div class="scard-val">₱<?php echo number_format($todaySales,2); ?></div>
                <div class="scard-lbl">Sales · <?php echo $totalSalesCount; ?> transactions</div>
            </div>
        </div>
        <div class="scard scard-yellow">
            <div class="scard-icon"><i class="bi bi-box-seam"></i></div>
            <div>
                <div class="scard-val"><?php echo $totalProducts; ?></div>
                <div class="scard-lbl">Active Products</div>
            </div>
        </div>
        <div class="scard scard-deep">
            <div class="scard-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div>
                <div class="scard-val"><?php echo $lowStock + $outOfStock; ?></div>
                <div class="scard-lbl">Low / Out of Stock</div>
            </div>
        </div>
        <div class="scard scard-mint">
            <div class="scard-icon"><i class="bi bi-people"></i></div>
            <div>
                <div class="scard-val"><?php echo $totalCustomers; ?></div>
                <div class="scard-lbl">Total Customers</div>
            </div>
        </div>
    </div>

    <!-- Stat Cards Row 2 -->
    <div class="section-label">Operations</div>
    <div class="stat-grid" style="margin-bottom:26px;">
        <div class="scard scard-yellow">
            <div class="scard-icon"><i class="bi bi-wallet2"></i></div>
            <div>
                <div class="scard-val">₱<?php echo number_format($todayExpenses,2); ?></div>
                <div class="scard-lbl">Today's Expenses</div>
            </div>
        </div>
        <div class="scard scard-mint">
            <div class="scard-icon"><i class="bi bi-truck"></i></div>
            <div>
                <div class="scard-val"><?php echo $totalSuppliers; ?></div>
                <div class="scard-lbl">Suppliers</div>
            </div>
        </div>
        <div class="scard scard-navy">
            <div class="scard-icon"><i class="bi bi-clock-history"></i></div>
            <div>
                <div class="scard-val"><?php echo $expiryAlert; ?></div>
                <div class="scard-lbl">Expiring (30 days)</div>
            </div>
        </div>
        <div class="scard scard-deep">
            <div class="scard-icon"><i class="bi bi-bag-check"></i></div>
            <div>
                <div class="scard-val"><?php echo $pendingPO; ?></div>
                <div class="scard-lbl">Pending POs</div>
            </div>
        </div>
    </div>

    <!-- Charts + Best Sellers -->
    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <div class="dash-card h-100">
                <div class="dash-card-header">
                    <div class="header-icon"><i class="bi bi-bar-chart-line"></i></div>
                    7-Day Sales Overview
                </div>
                <div class="chart-wrap">
                    <canvas id="salesChart" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dash-card h-100">
                <div class="dash-card-header">
                    <div class="header-icon"><i class="bi bi-trophy"></i></div>
                    Top Products <span style="font-weight:400; color:#a09dc0; font-size:12px;">(last 30 days)</span>
                </div>
                <div class="dash-card-body">
                    <table class="dash-table">
                        <thead>
                            <tr><th>#</th><th>Product</th><th>Sold</th></tr>
                        </thead>
                        <tbody>
                        <?php $rank=1; while($r=$bestSellers->fetch_assoc()): ?>
                        <tr>
                            <td><span class="rank-dot"><?php echo $rank++; ?></span></td>
                            <td style="max-width:130px;" class="text-truncate"><?php echo htmlspecialchars($r['productName']); ?></td>
                            <td><strong><?php echo $r['total_sold']; ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($rank===1): ?>
                        <tr><td colspan="3" style="text-align:center;padding:20px;color:#a09dc0;">No sales data yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions + Low Stock -->
    <div class="row g-3">
        <div class="col-md-7">
            <div class="dash-card">
                <div class="dash-card-header">
                    <div class="header-icon"><i class="bi bi-receipt"></i></div>
                    Recent Transactions
                    <a href="sales.php" class="btn-viewall">View All</a>
                </div>
                <div class="dash-card-body">
                    <table class="dash-table">
                        <thead>
                            <tr><th>#</th><th>Cashier</th><th>Total</th><th>Method</th><th>Time</th></tr>
                        </thead>
                        <tbody>
                        <?php while($r=$recentSales->fetch_assoc()): ?>
                        <tr>
                            <td style="color:#a09dc0;font-size:12px;">#<?php echo $r['salesID']; ?></td>
                            <td><?php echo htmlspecialchars($r['cashier']); ?></td>
                            <td><strong>₱<?php echo number_format($r['total_amount'],2); ?></strong></td>
                            <td><span class="pill pill-method"><?php echo htmlspecialchars($r['payment_method']); ?></span></td>
                            <td style="color:#a09dc0;font-size:12px;"><?php echo date('h:i A',strtotime($r['saleDate'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="dash-card h-100">
                <div class="dash-card-header">
                    <div class="header-icon" style="background:#fde8ea;"><i class="bi bi-exclamation-triangle" style="color:#e0293a;"></i></div>
                    Low Stock Alert
                </div>
                <div class="dash-card-body">
                    <table class="dash-table">
                        <thead>
                            <tr><th>Product</th><th>Stock</th><th>Reorder At</th></tr>
                        </thead>
                        <tbody>
                        <?php while($r=$lowStockProds->fetch_assoc()): ?>
                        <tr>
                            <td style="max-width:130px;" class="text-truncate"><?php echo htmlspecialchars($r['productName']); ?></td>
                            <td>
                                <?php if($r['stock_quantity'] == 0): ?>
                                <span class="pill pill-out">OUT</span>
                                <?php else: ?>
                                <span class="pill pill-low"><?php echo $r['stock_quantity']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="color:#a09dc0;"><?php echo $r['reorder_level']; ?></td>
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
            backgroundColor: '#262341',
            hoverBackgroundColor: '#F9D94A',
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#262341',
                titleColor: '#F9D94A',
                bodyColor: '#fff',
                padding: 10,
                cornerRadius: 8,
                callbacks: {
                    label: ctx => ' ₱' + ctx.parsed.y.toLocaleString('en-PH', {minimumFractionDigits:2})
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f0eef8' },
                ticks: { color: '#a09dc0', font: { size: 11 }, callback: v => '₱'+v.toLocaleString() }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#7a7898', font: { size: 12, weight: '600' } }
            }
        }
    }
});
</script>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
    const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>
</body></html>