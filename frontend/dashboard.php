<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }

if($_SESSION['roleName'] === 'Kitchen'){
    header("Location: kitchen.php"); exit();
}

$pageTitle = "Dashboard – Restaurant POS";

// ── Safe query helper: returns 0 on failure instead of crashing
function qval($conn, $sql) {
    $r = $conn->query($sql);
    if (!$r) {
        error_log("Dashboard query failed: " . $conn->error . " | SQL: " . $sql);
        return 0;
    }
    $row = $r->fetch_assoc();
    return $row['c'] ?? 0;
}

// ── Today's order stats
$todayRevenue   = qval($conn, "SELECT COALESCE(SUM(total_amount),0) AS c FROM orders WHERE status='Paid' AND DATE(datePaid)=CURDATE()");
$todayOrders    = qval($conn, "SELECT COUNT(*) AS c FROM orders WHERE DATE(dateCreated)=CURDATE()");
$openOrders     = qval($conn, "SELECT COUNT(*) AS c FROM orders WHERE status IN('Open','Active')");
$totalCustomers = qval($conn, "SELECT COUNT(*) AS c FROM customer WHERE dateDeleted IS NULL");
$todayExpenses  = qval($conn, "SELECT COALESCE(SUM(amount),0) AS c FROM expense WHERE expense_date=CURDATE()");
$occupiedTables = qval($conn, "SELECT COUNT(*) AS c FROM dining_table WHERE status='Occupied'");
$availTables    = qval($conn, "SELECT COUNT(*) AS c FROM dining_table WHERE status='Available'");
$totalTables    = qval($conn, "SELECT COUNT(*) AS c FROM dining_table");
$reservedTables = qval($conn, "SELECT COUNT(*) AS c FROM dining_table WHERE status='Reserved'");
$kitchenPending = qval($conn, "SELECT COUNT(*) AS c FROM order_items WHERE item_status IN('Pending','In Progress')");
$menuItems      = qval($conn, "SELECT COUNT(*) AS c FROM menu_item WHERE status='Active' AND is_available=1");
$unavailItems   = qval($conn, "SELECT COUNT(*) AS c FROM menu_item WHERE status='Active' AND is_available=0");

// ── 7-day revenue chart
$chartLabels = [];
$chartData   = [];
$weekSales = $conn->query("SELECT DATE(datePaid) AS d, SUM(total_amount) AS total FROM orders WHERE status='Paid' AND datePaid >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(datePaid) ORDER BY d ASC");
if ($weekSales) {
    while($r = $weekSales->fetch_assoc()){
        $chartLabels[] = date('D', strtotime($r['d']));
        $chartData[]   = floatval($r['total']);
    }
}

// ── Recent orders today
$recentOrders = $conn->query("
    SELECT o.orderID, o.orderNo, o.orderType, o.status, o.total_amount, o.payment_method,
           o.dateCreated, dt.tableNo,
           CONCAT(u.givenName,' ',u.surName) AS staffName,
           c.customerName
    FROM orders o
    LEFT JOIN dining_table dt ON o.tableID  = dt.tableID
    LEFT JOIN users         u  ON o.userID   = u.userID
    LEFT JOIN customer      c  ON o.customerID = c.customerID
    WHERE DATE(o.dateCreated) = CURDATE()
    ORDER BY o.dateCreated DESC
    LIMIT 10
");
if (!$recentOrders) {
    error_log("recentOrders query failed: " . $conn->error);
    $recentOrders = null;
}

// ── Best-selling items (30 days)
$bestItems = $conn->query("
    SELECT mi.itemName, SUM(oi.quantity) AS total_sold, SUM(oi.price * oi.quantity) AS revenue
    FROM order_items oi
    JOIN menu_item mi ON oi.itemID  = mi.itemID
    JOIN orders    o  ON oi.orderID = o.orderID
    WHERE o.status='Paid'
      AND o.datePaid >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
      AND oi.item_status != 'Void'
    GROUP BY oi.itemID
    ORDER BY total_sold DESC
    LIMIT 6
");
if (!$bestItems) {
    error_log("bestItems query failed: " . $conn->error);
    $bestItems = null;
}

// ── Kitchen status breakdown
$kitchenStats = ['Pending'=>0,'In Progress'=>0,'Ready'=>0,'Served'=>0];
$kitchenActive = $conn->query("
    SELECT oi.item_status, COUNT(*) AS cnt
    FROM order_items oi
    JOIN orders o ON oi.orderID = o.orderID
    WHERE o.status IN('Open','Active') AND oi.item_status != 'Void'
    GROUP BY oi.item_status
");
if ($kitchenActive) {
    while($r = $kitchenActive->fetch_assoc()){
        if (isset($kitchenStats[$r['item_status']])) {
            $kitchenStats[$r['item_status']] = (int)$r['cnt'];
        }
    }
}

// ── Order type breakdown today
$typeStats = [];
$typeBreakdown = $conn->query("
    SELECT orderType, COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS total
    FROM orders
    WHERE DATE(dateCreated) = CURDATE()
    GROUP BY orderType
");
if ($typeBreakdown) {
    while($r = $typeBreakdown->fetch_assoc()){
        $typeStats[$r['orderType']] = $r;
    }
}
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<style>
.page-body { padding: 24px 28px; }
.dash-welcome { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; }
.dash-welcome .greet-name { font-size:1.35rem; font-weight:800; color:#262341; letter-spacing:-0.3px; }
.dash-welcome .greet-sub  { font-size:13px; color:#6b6880; margin-top:2px; }
.dash-date-badge { background:#262341; color:#F9D94A; padding:6px 16px; border-radius:20px; font-size:12.5px; font-weight:600; }
.dash-alert { display:flex; align-items:center; gap:12px; padding:11px 18px; border-radius:10px; font-size:13.5px; font-weight:500; margin-bottom:10px; border-left:4px solid; }
.dash-alert.danger  { background:#fff0f2; border-color:#e8435a; color:#7f1d1d; }
.dash-alert.warning { background:#fffbeb; border-color:#F9D94A; color:#78350f; }
.dash-alert.info    { background:#eff6ff; border-color:#3b82f6; color:#1e40af; }
.dash-alert .alert-action { margin-left:auto; padding:4px 14px; border-radius:6px; font-size:12px; font-weight:700; text-decoration:none; white-space:nowrap; border:none; cursor:pointer; }
.dash-alert.danger  .alert-action { background:#e8435a; color:#fff; }
.dash-alert.warning .alert-action { background:#F9D94A; color:#262341; }
.dash-alert.info    .alert-action { background:#3b82f6; color:#fff; }
.section-label { font-size:10px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:#6b6880; margin-bottom:12px; display:flex; align-items:center; gap:8px; }
.section-label::after { content:''; flex:1; height:1px; background:#e5e2f0; }
.stat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:22px; }
@media(max-width:900px){ .stat-grid { grid-template-columns:repeat(2,1fr); } .page-body { padding:16px; } }
.dash-card { background:#fff; border-radius:16px; border:1.5px solid #e5e2f0; overflow:hidden; }
.dash-card-header { padding:14px 18px; border-bottom:1.5px solid #e5e2f0; display:flex; align-items:center; gap:10px; font-weight:700; font-size:14px; color:#1a1830; background:#fafbff; }
.dash-card-header .header-icon { width:30px; height:30px; background:#F9D94A; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:14px; color:#262341; flex-shrink:0; }
.dash-table { width:100%; border-collapse:collapse; font-size:13px; }
.dash-table thead tr { background:#f7f6fb; }
.dash-table thead th { padding:9px 14px; font-size:10px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:#6b6880; white-space:nowrap; border-bottom:1.5px solid #e5e2f0; }
.dash-table tbody td { padding:10px 14px; border-bottom:1px solid #f3f1f9; color:#1a1830; vertical-align:middle; }
.dash-table tbody tr:last-child td { border-bottom:none; }
.dash-table tbody tr:hover td { background:#fdfcff; }
.pill { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.pill-paid      { background:#dcfce7; color:#166534; }
.pill-open      { background:#fef3c7; color:#92400e; }
.pill-active    { background:#dbeafe; color:#1e40af; }
.pill-cancelled { background:#fee2e2; color:#991b1b; }
.pill-dinein    { background:#ede9ff; color:#3d2ea0; }
.pill-takeout   { background:#e6f0ff; color:#1a5fd4; }
.pill-delivery  { background:#fff0e6; color:#b85c00; }
.rank-dot { width:24px; height:24px; background:#262341; color:#F9D94A; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; }
.btn-viewall { margin-left:auto; background:#262341; color:#F9D94A; border:none; padding:5px 14px; border-radius:8px; font-size:12px; font-weight:700; text-decoration:none; transition:opacity .15s; }
.btn-viewall:hover { opacity:.85; color:#F9D94A; }
.chart-wrap { padding:18px; }
.kitchen-bar { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; padding:16px; }
.kstat { text-align:center; padding:14px; border-radius:12px; border:1.5px solid transparent; }
.kstat.pending  { background:#fff7ed; border-color:#fed7aa; }
.kstat.progress { background:#eff6ff; border-color:#bfdbfe; }
.kstat.ready    { background:#f0fdf4; border-color:#bbf7d0; }
.kstat.served   { background:#f9fafb; border-color:#e5e7eb; }
.kstat-num { font-size:1.8rem; font-weight:900; line-height:1; }
.kstat.pending  .kstat-num { color:#c2410c; }
.kstat.progress .kstat-num { color:#1d4ed8; }
.kstat.ready    .kstat-num { color:#15803d; }
.kstat.served   .kstat-num { color:#6b7280; }
.kstat-lbl { font-size:11px; font-weight:600; margin-top:4px; text-transform:uppercase; letter-spacing:.5px; color:#6b6880; }
.type-row { display:flex; align-items:center; padding:10px 18px; border-bottom:1px solid #f3f1f9; gap:12px; }
.type-row:last-child { border-bottom:none; }
.type-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:14px; flex-shrink:0; }
.table-util { display:flex; align-items:center; gap:20px; padding:16px 20px; }
.util-ring-wrap { position:relative; width:90px; height:90px; flex-shrink:0; }
.util-ring-label { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:1rem; font-weight:900; color:#262341; }
.util-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; font-size:12.5px; }
.util-dot { width:10px; height:10px; border-radius:50%; display:inline-block; margin-right:6px; }
.util-dot.occupied  { background:#e8435a; }
.util-dot.available { background:#22c87a; }
.util-dot.reserved  { background:#F9D94A; }
</style>

<?php
$topbarTitle = 'Dashboard';
$topbarIcon  = 'bi-speedometer2';
$topbarSub   = 'Live overview of your restaurant operations';
include 'topbar.php';
?>

<div class="page-body">

    <div class="dash-welcome">
        <div>
            <div class="greet-name">Good <?php $h=(int)date('G'); echo $h<12?'morning':($h<17?'afternoon':'evening'); ?>, <?php echo htmlspecialchars(explode(' ',$_SESSION['userName'])[0]); ?> 👋</div>
            <div class="greet-sub">Here's what's happening at your restaurant today.</div>
        </div>
        <div class="dash-date-badge"><i class="bi bi-calendar3 me-1"></i><?php echo date('l, F d, Y'); ?></div>
    </div>

    <?php if($openOrders > 0 && in_array($_SESSION['roleName'], ['Admin','Cashier','Kitchen'])): ?>
    <div class="dash-alert info">
        <i class="bi bi-clock-fill"></i>
        <span><strong><?php echo $openOrders; ?></strong> open order(s) are currently active on the floor.</span>
        <a href="kitchen.php" class="alert-action">Kitchen View</a>
    </div>
    <?php endif; ?>

    <?php if($kitchenPending > 0 && in_array($_SESSION['roleName'], ['Admin','Cashier','Kitchen'])): ?>
    <div class="dash-alert warning">
        <i class="bi bi-fire"></i>
        <span><strong><?php echo $kitchenPending; ?></strong> item(s) are pending in the kitchen queue.</span>
        <a href="kitchen.php" class="alert-action">View Kitchen</a>
    </div>
    <?php endif; ?>

    <?php if($unavailItems > 0): ?>
    <div class="dash-alert danger">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span><strong><?php echo $unavailItems; ?> menu item(s)</strong> are marked unavailable.</span>
        <a href="menu.php" class="alert-action">Update Menu</a>
    </div>
    <?php endif; ?>

    <div style="height:6px;"></div>

    <div class="section-label">Today's Performance</div>
    <div class="stat-grid">
        <div class="scard scard-navy">
            <div class="scard-icon"><i class="bi bi-cash-coin"></i></div>
            <div><div class="scard-val">&#8369;<?php echo number_format($todayRevenue,2); ?></div><div class="scard-lbl">Revenue (Paid Orders)</div></div>
        </div>
        <div class="scard scard-yellow">
            <div class="scard-icon"><i class="bi bi-receipt"></i></div>
            <div><div class="scard-val"><?php echo $todayOrders; ?></div><div class="scard-lbl">Orders Today</div></div>
        </div>
        <div class="scard scard-deep">
            <div class="scard-icon"><i class="bi bi-wallet2"></i></div>
            <div><div class="scard-val">&#8369;<?php echo number_format($todayExpenses,2); ?></div><div class="scard-lbl">Expenses Today</div></div>
        </div>
        <div class="scard scard-mint">
            <div class="scard-icon"><i class="bi bi-people"></i></div>
            <div><div class="scard-val"><?php echo $totalCustomers; ?></div><div class="scard-lbl">Registered Guests</div></div>
        </div>
    </div>

    <div class="section-label">Operations</div>
    <div class="stat-grid" style="margin-bottom:26px;">
        <div class="scard scard-yellow">
            <div class="scard-icon"><i class="bi bi-grid-3x3"></i></div>
            <div><div class="scard-val"><?php echo $occupiedTables; ?><span style="font-size:1rem;font-weight:600;color:#6b6880;"> / <?php echo $totalTables; ?></span></div><div class="scard-lbl">Tables Occupied</div></div>
        </div>
        <div class="scard scard-mint">
            <div class="scard-icon"><i class="bi bi-clock"></i></div>
            <div><div class="scard-val"><?php echo $openOrders; ?></div><div class="scard-lbl">Active Orders</div></div>
        </div>
        <div class="scard scard-navy">
            <div class="scard-icon"><i class="bi bi-fire"></i></div>
            <div><div class="scard-val"><?php echo $kitchenPending; ?></div><div class="scard-lbl">Kitchen Queue</div></div>
        </div>
        <div class="scard scard-deep">
            <div class="scard-icon"><i class="bi bi-journal-text"></i></div>
            <div><div class="scard-val"><?php echo $menuItems; ?></div><div class="scard-lbl">Available Items</div></div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <div class="dash-card h-100">
                <div class="dash-card-header"><div class="header-icon"><i class="bi bi-bar-chart-line"></i></div>7-Day Revenue Overview</div>
                <div class="chart-wrap"><canvas id="salesChart" height="120"></canvas></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dash-card h-100">
                <div class="dash-card-header"><div class="header-icon"><i class="bi bi-trophy"></i></div>Top Menu Items <span style="font-weight:400;color:#a09dc0;font-size:12px;">(30 days)</span></div>
                <table class="dash-table">
                    <thead><tr><th>#</th><th>Item</th><th>Sold</th></tr></thead>
                    <tbody>
                    <?php if($bestItems): $rank=1; while($r=$bestItems->fetch_assoc()): ?>
                    <tr>
                        <td><span class="rank-dot"><?php echo $rank++; ?></span></td>
                        <td style="max-width:130px;" class="text-truncate"><?php echo htmlspecialchars($r['itemName']); ?></td>
                        <td><strong><?php echo $r['total_sold']; ?></strong></td>
                    </tr>
                    <?php endwhile; endif; ?>
                    <?php if(!$bestItems || $rank===1): ?>
                    <tr><td colspan="3" style="text-align:center;padding:20px;color:#a09dc0;">No data yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-7">
            <div class="dash-card">
                <div class="dash-card-header"><div class="header-icon"><i class="bi bi-fire"></i></div>Kitchen Status<?php if(in_array($_SESSION['roleName'], ['Admin','Cashier','Kitchen'])): ?>
<a href="kitchen.php" class="btn-viewall">Full View</a>
<?php endif; ?></div>
                <div class="kitchen-bar">
                    <div class="kstat pending"><div class="kstat-num"><?php echo $kitchenStats['Pending']; ?></div><div class="kstat-lbl">Pending</div></div>
                    <div class="kstat progress"><div class="kstat-num"><?php echo $kitchenStats['In Progress']; ?></div><div class="kstat-lbl">In Progress</div></div>
                    <div class="kstat ready"><div class="kstat-num"><?php echo $kitchenStats['Ready']; ?></div><div class="kstat-lbl">Ready</div></div>
                    <div class="kstat served"><div class="kstat-num"><?php echo $kitchenStats['Served']; ?></div><div class="kstat-lbl">Served</div></div>
                </div>
                <div style="border-top:1.5px solid #e5e2f0;">
                <?php
                $types = [
                    'Dine-In'  => ['bi-people-fill','#6c63ff','#f3f0ff'],
                    'Takeout'  => ['bi-bag',         '#1a5fd4','#e6f0ff'],
                    'Delivery' => ['bi-bicycle',     '#b85c00','#fff0e6'],
                ];
                foreach($types as $type => [$icon,$color,$bg]):
                    $ts = $typeStats[$type] ?? ['cnt'=>0,'total'=>0];
                ?>
                <div class="type-row">
                    <div class="type-icon" style="background:<?php echo $bg; ?>;color:<?php echo $color; ?>;"><i class="bi <?php echo $icon; ?>"></i></div>
                    <div style="flex:1;font-weight:600;font-size:13px;"><?php echo $type; ?></div>
                    <div style="font-size:12px;color:#6b6880;"><?php echo $ts['cnt']; ?> orders</div>
                    <div style="font-weight:800;font-size:13px;color:#262341;margin-left:16px;">&#8369;<?php echo number_format($ts['total'],2); ?></div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="dash-card h-100">
                <div class="dash-card-header"><div class="header-icon"><i class="bi bi-grid-3x3"></i></div>Table Utilisation<a href="tables.php" class="btn-viewall">Map</a></div>
                <?php $pct = $totalTables > 0 ? round(($occupiedTables/$totalTables)*100) : 0; ?>
                <div class="table-util">
                    <div class="util-ring-wrap">
                        <svg width="90" height="90" viewBox="0 0 90 90">
                            <circle cx="45" cy="45" r="38" fill="none" stroke="#f0eef8" stroke-width="10"/>
                            <circle cx="45" cy="45" r="38" fill="none" stroke="#e8435a" stroke-width="10"
                                    stroke-dasharray="<?php echo $totalTables>0?round(238*$occupiedTables/$totalTables):0; ?> 238"
                                    stroke-linecap="round" transform="rotate(-90 45 45)"/>
                        </svg>
                        <div class="util-ring-label"><?php echo $pct; ?>%</div>
                    </div>
                    <div style="flex:1;">
                        <div class="util-row"><span><span class="util-dot occupied"></span>Occupied</span><strong><?php echo $occupiedTables; ?></strong></div>
                        <div class="util-row"><span><span class="util-dot available"></span>Available</span><strong><?php echo $availTables; ?></strong></div>
                        <div class="util-row"><span><span class="util-dot reserved"></span>Reserved</span><strong><?php echo $reservedTables; ?></strong></div>
                        <div class="util-row" style="border-top:1px solid #e5e2f0;padding-top:8px;margin-top:2px;"><span style="font-weight:700;">Total</span><strong><?php echo $totalTables; ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dash-card">
        <div class="dash-card-header"><div class="header-icon"><i class="bi bi-receipt"></i></div>Today's Orders<a href="orders.php" class="btn-viewall">View All</a></div>
        <table class="dash-table">
            <thead><tr><th>Order No</th><th>Type</th><th>Table</th><th>Staff</th><th>Guest</th><th>Total</th><th>Payment</th><th>Status</th><th>Time</th></tr></thead>
            <tbody>
            <?php if($recentOrders): while($r=$recentOrders->fetch_assoc()):
                $sk = strtolower($r['status']);
                $tk = strtolower(str_replace('-','',$r['orderType']));
            ?>
            <tr>
                <td style="font-weight:700;font-size:12px;"><?php echo htmlspecialchars($r['orderNo']); ?></td>
                <td><span class="pill pill-<?php echo $tk; ?>"><?php echo $r['orderType']; ?></span></td>
                <td style="font-size:12px;"><?php echo $r['tableNo'] ? 'T-'.htmlspecialchars($r['tableNo']) : '—'; ?></td>
                <td style="font-size:12px;font-weight:600;"><?php echo htmlspecialchars($r['staffName'] ?? '—'); ?></td>
                <td style="font-size:12px;color:#6b6880;"><?php echo htmlspecialchars($r['customerName'] ?? 'Walk-in'); ?></td>
                <td><strong>&#8369;<?php echo number_format($r['total_amount'],2); ?></strong></td>
                <td style="font-size:12px;"><?php echo htmlspecialchars($r['payment_method'] ?? '—'); ?></td>
                <td><span class="pill pill-<?php echo $sk; ?>"><?php echo $r['status']; ?></span></td>
                <td style="color:#6b6880;font-size:12px;"><?php echo date('h:i A', strtotime($r['dateCreated'])); ?></td>
            </tr>
            <?php endwhile; endif; ?>
            <?php if(!$recentOrders): ?>
            <tr><td colspan="9" style="text-align:center;padding:20px;color:#a09dc0;">No orders yet today.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</div></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chartLabels); ?>,
        datasets: [{
            label: 'Revenue (₱)',
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
<?php include 'footer.php'; ?>
</body></html>
