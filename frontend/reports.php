<?php
require_once '../backend/database.php';
session_start();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if(!in_array($_SESSION['roleName'], ['Admin','Owner'])){ header("Location: dashboard.php"); exit(); }
$pageTitle = "Reports – 7Evelyn POS";

$report   = $_GET['report'] ?? 'daily_sales';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to'] ?? date('Y-m-d');
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<style>

/* ── Page ─────────────────────────────────── */
.rp-page { padding: 28px 32px; background: var(--brand-mint); min-height: 100vh; }

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
.topbar-actions { display: flex; align-items: center; gap: 8px; }
.btn-print {
  display: inline-flex; align-items: center; gap: 6px;
  border: 1.5px solid var(--border); background: #fff;
  color: var(--text-main); border-radius: var(--radius-sm);
  padding: 7px 14px; font-size: .82rem; font-weight: 600; cursor: pointer;
  transition: border-color .15s;
}
.btn-print:hover { border-color: var(--brand-navy); }
.btn-excel {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--brand-yellow); color: var(--brand-navy);
  border: none; border-radius: var(--radius-sm);
  padding: 7px 14px; font-size: .82rem; font-weight: 700; cursor: pointer;
  box-shadow: var(--shadow-btn); transition: background .15s;
}
.btn-excel:hover { background: var(--brand-yellow-d); }

/* ── Report type tabs ─────────────────────── */
.report-tabs {
  display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px;
  background: var(--card-bg); border-radius: var(--radius-lg);
  padding: 12px 16px; box-shadow: var(--shadow-card); border: 1.5px solid var(--border);
}
.report-tab {
  display: inline-flex; align-items: center; gap: 6px;
  border: 1.5px solid var(--border); border-radius: 50px;
  padding: 6px 14px; font-size: .78rem; font-weight: 600;
  color: var(--text-muted); text-decoration: none; background: transparent;
  transition: all .15s; white-space: nowrap;
}
.report-tab:hover { border-color: var(--brand-navy); color: var(--brand-navy); background: var(--brand-mint); }
.report-tab.active {
  background: var(--brand-navy); color: var(--brand-yellow);
  border-color: var(--brand-navy);
}
.report-tab.active i { color: var(--brand-yellow); }

/* ── Date filter card ─────────────────────── */
.date-filter-card {
  background: var(--card-bg); border-radius: var(--radius-md);
  padding: 16px 20px; margin-bottom: 20px;
  box-shadow: var(--shadow-card); border: 1.5px solid var(--border);
  display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap;
}
.date-filter-card label { font-size: .73rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .05em; display: block; margin-bottom: 5px; }
.date-input {
  border: 1.5px solid var(--border); border-radius: var(--radius-sm);
  padding: 8px 12px; font-size: .86rem; color: var(--text-main);
  background: var(--card-bg); outline: none; transition: border-color .15s;
}
.date-input:focus { border-color: var(--brand-yellow); box-shadow: 0 0 0 3px rgba(249,217,74,.18); }
.btn-generate {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--brand-navy); color: #fff;
  border: none; border-radius: var(--radius-sm);
  padding: 9px 18px; font-size: .84rem; font-weight: 700; cursor: pointer;
  transition: opacity .15s;
}
.btn-generate:hover { opacity: .88; }

/* ── Report header row ────────────────────── */
.report-header-row {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 18px; flex-wrap: wrap; gap: 8px;
}
.report-title { font-size: 1.05rem; font-weight: 700; color: var(--brand-navy); margin: 0; }
.date-range-pill {
  background: var(--brand-navy); color: var(--brand-yellow);
  border-radius: 50px; padding: 4px 14px; font-size: .75rem; font-weight: 600;
}

/* ── Stat cards ───────────────────────────── */
.stat-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 20px; }
.stat-row-3 { grid-template-columns: repeat(3,1fr); }
.stat-card-rp {
  background: var(--card-bg); border-radius: var(--radius-lg);
  padding: 18px 20px; display: flex; align-items: center; gap: 14px;
  box-shadow: var(--shadow-card); border: 1.5px solid var(--border);
  transition: transform .18s, box-shadow .18s;
}
.stat-card-rp:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(38,35,65,.12); }
.stat-icon-rp {
  width: 44px; height: 44px; border-radius: 11px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.2rem; flex-shrink: 0;
}
.ic-purple { background: #f3f0ff; color: #553c9a; }
.ic-blue   { background: #ebf4ff; color: #2b6cb0; }
.ic-orange { background: #fff7ed; color: #c05621; }
.ic-green  { background: #f0fff4; color: var(--success); }
.ic-red    { background: #fff5f5; color: var(--danger); }
.ic-teal   { background: #e6fffa; color: #285e61; }
.ic-yellow { background: #fffff0; color: #975a16; }
.stat-lbl-rp { font-size: .72rem; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: .05em; }
.stat-val-rp { font-size: 1.35rem; font-weight: 800; color: var(--brand-navy); line-height: 1.2; }
.stat-val-rp.sm { font-size: 1.05rem; }

/* ── Main card / table ────────────────────── */
.main-card {
  background: var(--card-bg); border-radius: var(--radius-lg);
  box-shadow: var(--shadow-card); border: 1.5px solid var(--border);
  overflow: hidden; margin-bottom: 20px;
}
.main-card-header {
  padding: 16px 22px; border-bottom: 1.5px solid var(--border);
  display: flex; align-items: center; gap: 8px;
  font-size: .92rem; font-weight: 700; color: var(--brand-navy);
}
.main-card-header i { color: var(--brand-yellow); }

#reportTable { width: 100%; border-collapse: collapse; margin: 0; }
#reportTable thead th {
  background: var(--brand-navy); color: #fff;
  font-size: .72rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: .06em; padding: 12px 14px; border: none; white-space: nowrap;
}
#reportTable tbody tr { border-bottom: 1px solid #f0f0f5; transition: background .12s; }
#reportTable tbody tr:hover { background: #f8f8fc; }
#reportTable tbody tr:last-child { border-bottom: none; }
#reportTable tbody td { padding: 11px 14px; font-size: .86rem; color: var(--text-main); vertical-align: middle; border: none; }
#reportTable tfoot td { padding: 12px 14px; background: #f7f7fb; font-weight: 700; font-size: .88rem; border-top: 2px solid var(--border); border-left: none; border-right: none; border-bottom: none; }

/* ── Rank badge ───────────────────────────── */
.rank-badge {
  display: inline-flex; align-items: center; justify-content: center;
  width: 26px; height: 26px; border-radius: 50%;
  background: var(--brand-navy); color: var(--brand-yellow);
  font-size: .72rem; font-weight: 800;
}

/* ── Stock status ─────────────────────────── */
.stock-ok  { display:inline-flex;align-items:center;gap:4px;background:#f0fff4;color:var(--success);border:1px solid #9ae6b4;border-radius:50px;padding:3px 10px;font-size:.72rem;font-weight:700; }
.stock-low { display:inline-flex;align-items:center;gap:4px;background:#fff7ed;color:#c05621;border:1px solid #fbd38d;border-radius:50px;padding:3px 10px;font-size:.72rem;font-weight:700; }
.stock-out { display:inline-flex;align-items:center;gap:4px;background:#fff5f5;color:var(--danger);border:1px solid #fed7d7;border-radius:50px;padding:3px 10px;font-size:.72rem;font-weight:700; }

/* ── Category pill ────────────────────────── */
.cat-pill { background: var(--brand-mint); color: var(--brand-navy); border-radius:50px; padding:3px 10px; font-size:.72rem; font-weight:600; }

/* ── Amount helpers ───────────────────────── */
.amt-pos { color: var(--success); font-weight: 700; }
.amt-neg { color: var(--danger); font-weight: 700; }
.amt-main{ color: var(--brand-navy); font-weight: 700; }

/* ── Profit breakdown table ───────────────── */
.profit-table { width: 100%; border-collapse: collapse; }
.profit-table td { padding: 13px 20px; font-size: .9rem; color: var(--text-main); border-bottom: 1px solid #f0f0f5; }
.profit-table tr:last-child td { border-bottom: none; background: #f7f7fb; font-size: 1rem; font-weight: 800; }
.profit-table .label { font-weight: 500; }
.profit-table .sub-label { color: var(--text-muted); font-size: .84rem; padding-left: 28px; }
.profit-table .separator td { border-top: 2px solid var(--border); background: #fafafa; font-weight: 700; }

/* ── Expenses total banner ────────────────── */
.exp-banner {
  display: flex; align-items: center; gap: 12px;
  background: #fff7ed; border: 1.5px solid #fbd38d; border-radius: var(--radius-md);
  padding: 13px 18px; margin-bottom: 16px;
  font-size: .9rem; color: #7b341e; font-weight: 600;
}
.exp-banner i { font-size: 1.2rem; }
.exp-banner strong { font-size: 1.1rem; }

/* ── Contact chip ─────────────────────────── */
.contact-chip { display:inline-flex;align-items:center;gap:4px;background:var(--brand-mint);border-radius:50px;padding:3px 10px;font-size:.78rem;color:var(--text-main); }

/* ── Chart canvas ─────────────────────────── */
.chart-card {
  background: var(--card-bg); border-radius: var(--radius-lg);
  box-shadow: var(--shadow-card); border: 1.5px solid var(--border);
  padding: 20px 22px; margin-bottom: 20px;
}
.chart-card-title { font-size: .88rem; font-weight: 700; color: var(--brand-navy); margin-bottom: 14px; }

/* ── DataTables override ──────────────────── */
div.dataTables_wrapper div.dataTables_filter label,
div.dataTables_wrapper div.dataTables_length label { color: var(--text-muted); font-size: .83rem; }
div.dataTables_wrapper div.dataTables_filter input,
div.dataTables_wrapper div.dataTables_length select {
  border: 1.5px solid var(--border); border-radius: var(--radius-sm);
  padding: 5px 10px; font-size: .83rem; color: var(--text-main);
  background: var(--card-bg); outline: none;
}
div.dataTables_wrapper div.dataTables_filter input:focus { border-color: var(--brand-yellow); }
div.dataTables_wrapper div.dataTables_paginate .paginate_button { border-radius: var(--radius-sm) !important; font-size: .8rem; }
div.dataTables_wrapper div.dataTables_paginate .paginate_button.current,
div.dataTables_wrapper div.dataTables_paginate .paginate_button.current:hover {
  background: var(--brand-navy) !important; color: #fff !important; border-color: var(--brand-navy) !important;
}
div.dataTables_wrapper .dataTables_info { color: var(--text-muted); font-size: .8rem; }
.dt-controls { padding: 14px 22px 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }

@media(max-width:900px){ .stat-row{grid-template-columns:repeat(2,1fr);} }
@media(max-width:480px){ .stat-row{grid-template-columns:1fr;} .rp-page{padding:14px;} }
</style>

<?php
$types = [
    'daily_sales'   => ['bi-calendar-day',   'Daily Sales'],
    'weekly_sales'  => ['bi-calendar-week',  'Weekly Sales'],
    'monthly_sales' => ['bi-calendar-month', 'Monthly Sales'],
    'product_sales' => ['bi-box-seam',       'Product Sales'],
    'inventory'     => ['bi-archive',        'Inventory'],
    'expenses'      => ['bi-wallet2',        'Expenses'],
    'profit'        => ['bi-graph-up-arrow', 'Profit / Loss'],
    'customer'      => ['bi-people',         'Customer Report'],
];
?>

<?php
  $topbarTitle = 'Reports &amp; Analytics';
  $topbarIcon  = 'bi-bar-chart-line-fill';
  $topbarSub   = 'Generate insights from your sales, inventory, and finances';
  $topbarExtra = '<button class="btn-print no-print" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>'
               . '<button class="btn-excel no-print" onclick="exportExcel()"><i class="bi bi-file-earmark-excel"></i> Export Excel</button>';
  include 'topbar.php';
  ?>
<div class="rp-page no-print">

  <!-- REPORT TYPE TABS -->
  <div class="report-tabs no-print">
    <?php foreach($types as $k=>[$icon,$label]): ?>
    <a href="?report=<?php echo $k; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>"
       class="report-tab<?php echo $report===$k ? ' active' : ''; ?>">
      <i class="bi <?php echo $icon; ?>"></i><?php echo $label; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- DATE FILTER -->
  <?php if(!in_array($report, ['inventory'])): ?>
  <form method="GET" class="date-filter-card no-print">
    <input type="hidden" name="report" value="<?php echo htmlspecialchars($report); ?>">
    <div>
      <label>From</label>
      <input type="date" name="date_from" class="date-input" value="<?php echo $dateFrom; ?>">
    </div>
    <div>
      <label>To</label>
      <input type="date" name="date_to" class="date-input" value="<?php echo $dateTo; ?>">
    </div>
    <button type="submit" class="btn-generate"><i class="bi bi-funnel-fill"></i> Generate</button>
  </form>
  <?php endif; ?>

  <!-- REPORT HEADER -->
  <div class="report-header-row">
    <h6 class="report-title"><i class="bi <?php echo $types[$report][0]; ?> me-2" style="color:var(--brand-yellow);"></i><?php echo $types[$report][1]; ?> Report</h6>
    <?php if(!in_array($report,['inventory'])): ?>
    <span class="date-range-pill">
      <i class="bi bi-calendar3 me-1"></i>
      <?php echo date('M d, Y', strtotime($dateFrom)); ?> – <?php echo date('M d, Y', strtotime($dateTo)); ?>
    </span>
    <?php endif; ?>
  </div>

  <!-- ══════════════ DAILY SALES ══════════════ -->
  <?php if($report === 'daily_sales'):
    $data   = $conn->query("SELECT DATE(saleDate) AS d, COUNT(*) AS txn, SUM(total_amount) AS revenue, SUM(discount_amount) AS discounts FROM sales WHERE DATE(saleDate) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY DATE(saleDate) ORDER BY d DESC");
    $totals = $conn->query("SELECT COUNT(*) AS txn, SUM(total_amount) AS revenue, SUM(discount_amount) AS discounts FROM sales WHERE DATE(saleDate) BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc();
  ?>
  <div class="stat-row stat-row-3">
    <div class="stat-card-rp"><div class="stat-icon-rp ic-purple"><i class="bi bi-cash-stack"></i></div><div><div class="stat-lbl-rp">Total Revenue</div><div class="stat-val-rp sm">₱<?php echo number_format($totals['revenue'],2); ?></div></div></div>
    <div class="stat-card-rp"><div class="stat-icon-rp ic-blue"><i class="bi bi-receipt"></i></div><div><div class="stat-lbl-rp">Transactions</div><div class="stat-val-rp"><?php echo $totals['txn']; ?></div></div></div>
    <div class="stat-card-rp"><div class="stat-icon-rp ic-orange"><i class="bi bi-tag"></i></div><div><div class="stat-lbl-rp">Discounts Given</div><div class="stat-val-rp sm">₱<?php echo number_format($totals['discounts'],2); ?></div></div></div>
  </div>
  <div class="main-card">
    <div class="main-card-header"><i class="bi bi-table"></i> Daily Breakdown</div>
    <div class="dt-controls" id="dt-search-area"></div>
    <div style="overflow-x:auto;">
    <table id="reportTable">
      <thead><tr><th>Date</th><th>Transactions</th><th>Discounts</th><th>Revenue</th></tr></thead>
      <tbody>
      <?php while($r=$data->fetch_assoc()): ?>
      <tr>
        <td style="font-weight:600;"><?php echo date('D, M d, Y', strtotime($r['d'])); ?></td>
        <td style="text-align:center;"><?php echo $r['txn']; ?></td>
        <td style="text-align:right;" class="amt-neg">-₱<?php echo number_format($r['discounts'],2); ?></td>
        <td style="text-align:right;" class="amt-main">₱<?php echo number_format($r['revenue'],2); ?></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
      <tfoot><tr><td>TOTAL</td><td style="text-align:center;"><?php echo $totals['txn']; ?></td><td style="text-align:right;" class="amt-neg">-₱<?php echo number_format($totals['discounts'],2); ?></td><td style="text-align:right;" class="amt-main">₱<?php echo number_format($totals['revenue'],2); ?></td></tr></tfoot>
    </table>
    </div>
  </div>

  <!-- ══════════════ WEEKLY SALES ══════════════ -->
  <?php elseif($report === 'weekly_sales'):
    $data = $conn->query("SELECT YEARWEEK(saleDate,1) AS wk, MIN(DATE(saleDate)) AS week_start, MAX(DATE(saleDate)) AS week_end, COUNT(*) AS txn, SUM(total_amount) AS revenue FROM sales WHERE DATE(saleDate) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY wk ORDER BY wk DESC");
  ?>
  <div class="main-card">
    <div class="main-card-header"><i class="bi bi-table"></i> Weekly Breakdown</div>
    <div class="dt-controls" id="dt-search-area"></div>
    <div style="overflow-x:auto;">
    <table id="reportTable">
      <thead><tr><th>Week</th><th>Period</th><th>Transactions</th><th>Revenue</th></tr></thead>
      <tbody>
      <?php while($r=$data->fetch_assoc()): ?>
      <tr>
        <td style="font-weight:700;">Week <?php echo substr($r['wk'],4); ?>, <?php echo substr($r['wk'],0,4); ?></td>
        <td style="color:var(--text-muted);font-size:.83rem;"><?php echo date('M d',strtotime($r['week_start'])).' – '.date('M d, Y',strtotime($r['week_end'])); ?></td>
        <td style="text-align:center;"><?php echo $r['txn']; ?></td>
        <td style="text-align:right;" class="amt-main">₱<?php echo number_format($r['revenue'],2); ?></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
    </div>
  </div>

  <!-- ══════════════ MONTHLY SALES ══════════════ -->
  <?php elseif($report === 'monthly_sales'):
    $data = $conn->query("SELECT DATE_FORMAT(saleDate,'%Y-%m') AS mo, DATE_FORMAT(saleDate,'%M %Y') AS mo_label, COUNT(*) AS txn, SUM(total_amount) AS revenue, SUM(discount_amount) AS discounts FROM sales WHERE DATE(saleDate) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY mo ORDER BY mo DESC");
    $rows = [];
    while($r=$data->fetch_assoc()) $rows[]=$r;
  ?>
  <div class="chart-card">
    <div class="chart-card-title"><i class="bi bi-bar-chart-fill me-2" style="color:var(--brand-yellow);"></i>Monthly Revenue</div>
    <canvas id="monthChart" height="70"></canvas>
  </div>
  <div class="main-card">
    <div class="main-card-header"><i class="bi bi-table"></i> Monthly Breakdown</div>
    <div class="dt-controls" id="dt-search-area"></div>
    <div style="overflow-x:auto;">
    <table id="reportTable">
      <thead><tr><th>Month</th><th>Transactions</th><th>Discounts</th><th>Revenue</th></tr></thead>
      <tbody>
      <?php foreach($rows as $r): ?>
      <tr>
        <td style="font-weight:600;"><?php echo $r['mo_label']; ?></td>
        <td style="text-align:center;"><?php echo $r['txn']; ?></td>
        <td style="text-align:right;" class="amt-neg">-₱<?php echo number_format($r['discounts'],2); ?></td>
        <td style="text-align:right;" class="amt-main">₱<?php echo number_format($r['revenue'],2); ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
  new Chart(document.getElementById('monthChart'),{
    type:'bar',
    data:{
      labels:<?php echo json_encode(array_column($rows,'mo_label')); ?>,
      datasets:[{
        label:'Revenue (₱)',
        data:<?php echo json_encode(array_column($rows,'revenue')); ?>,
        backgroundColor:'#262341',
        borderRadius:8,
        hoverBackgroundColor:'#F9D94A'
      }]
    },
    options:{
      responsive:true,
      plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return ' ₱'+Number(c.raw).toLocaleString('en-PH',{minimumFractionDigits:2});}}}},
      scales:{y:{beginAtZero:true,ticks:{callback:function(v){return '₱'+v.toLocaleString();}}},x:{grid:{display:false}}}
    }
  });
  </script>

  <!-- ══════════════ PRODUCT SALES ══════════════ -->
  <?php elseif($report === 'product_sales'):
    $data = $conn->query("SELECT p.productName, c.categoryName, SUM(sd.sold_quantity) AS total_sold, SUM(sd.subtotal) AS revenue, AVG(sd.price) AS avg_price FROM sales_details sd JOIN product p ON sd.productID=p.productID JOIN category c ON p.categoryID=c.categoryID JOIN sales s ON sd.salesID=s.salesID WHERE DATE(s.saleDate) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY sd.productID ORDER BY total_sold DESC");
  ?>
  <div class="main-card">
    <div class="main-card-header"><i class="bi bi-table"></i> Product Performance</div>
    <div class="dt-controls" id="dt-search-area"></div>
    <div style="overflow-x:auto;">
    <table id="reportTable">
      <thead><tr><th>#</th><th>Product</th><th>Category</th><th>Qty Sold</th><th>Avg Price</th><th>Revenue</th></tr></thead>
      <tbody>
      <?php $rank=1; while($r=$data->fetch_assoc()): ?>
      <tr>
        <td><span class="rank-badge"><?php echo $rank++; ?></span></td>
        <td style="font-weight:600;"><?php echo htmlspecialchars($r['productName']); ?></td>
        <td><span class="cat-pill"><?php echo htmlspecialchars($r['categoryName']); ?></span></td>
        <td style="text-align:center;font-weight:700;"><?php echo $r['total_sold']; ?></td>
        <td style="text-align:right;color:var(--text-muted);">₱<?php echo number_format($r['avg_price'],2); ?></td>
        <td style="text-align:right;" class="amt-main">₱<?php echo number_format($r['revenue'],2); ?></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
    </div>
  </div>

  <!-- ══════════════ INVENTORY ══════════════ -->
  <?php elseif($report === 'inventory'):
    $data = $conn->query("SELECT p.*, c.categoryName FROM product p JOIN category c ON p.categoryID=c.categoryID WHERE p.status='Active' ORDER BY p.stock_quantity ASC");
  ?>
  <div class="main-card">
    <div class="main-card-header"><i class="bi bi-table"></i> Inventory Status</div>
    <div class="dt-controls" id="dt-search-area"></div>
    <div style="overflow-x:auto;">
    <table id="reportTable">
      <thead><tr><th>Product</th><th>Category</th><th>Stock</th><th>Reorder Lvl</th><th>Cost</th><th>Price</th><th>Expiry</th><th>Status</th></tr></thead>
      <tbody>
      <?php while($r=$data->fetch_assoc()):
        $st  = $r['stock_quantity']==0 ? 'OUT' : ($r['stock_quantity']<=$r['reorder_level'] ? 'LOW' : 'OK');
        $cls = $st==='OUT' ? 'stock-out' : ($st==='LOW' ? 'stock-low' : 'stock-ok');
        $icon= $st==='OUT' ? 'bi-x-circle-fill' : ($st==='LOW' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill');
      ?>
      <tr>
        <td style="font-weight:600;"><?php echo htmlspecialchars($r['productName']); ?></td>
        <td><span class="cat-pill"><?php echo htmlspecialchars($r['categoryName']); ?></span></td>
        <td style="text-align:center;font-weight:700;"><?php echo $r['stock_quantity']; ?></td>
        <td style="text-align:center;color:var(--text-muted);"><?php echo $r['reorder_level']; ?></td>
        <td style="text-align:right;color:var(--text-muted);">₱<?php echo number_format($r['cost'],2); ?></td>
        <td style="text-align:right;" class="amt-main">₱<?php echo number_format($r['price'],2); ?></td>
        <td style="font-size:.82rem;color:var(--text-muted);"><?php echo $r['expiry_date'] ? date('M d, Y',strtotime($r['expiry_date'])) : '—'; ?></td>
        <td><span class="<?php echo $cls; ?>"><i class="bi <?php echo $icon; ?>" style="font-size:.68rem;"></i> <?php echo $st; ?></span></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
    </div>
  </div>

  <!-- ══════════════ EXPENSES ══════════════ -->
  <?php elseif($report === 'expenses'):
    $data     = $conn->query("SELECT e.*, ec.categoryName, CONCAT(u.givenName,' ',u.surName) AS byUser FROM expense e JOIN expense_category ec ON e.expenseCategoryID=ec.expenseCategoryID JOIN users u ON e.userID=u.userID WHERE e.expense_date BETWEEN '$dateFrom' AND '$dateTo' ORDER BY e.expense_date DESC");
    $totalExp = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM expense WHERE expense_date BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc()['t'];
  ?>
  <div class="exp-banner">
    <i class="bi bi-wallet2"></i>
    <span>Total Expenses for period: <strong>₱<?php echo number_format($totalExp,2); ?></strong></span>
  </div>
  <div class="main-card">
    <div class="main-card-header"><i class="bi bi-table"></i> Expense Breakdown</div>
    <div class="dt-controls" id="dt-search-area"></div>
    <div style="overflow-x:auto;">
    <table id="reportTable">
      <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>Recorded By</th></tr></thead>
      <tbody>
      <?php while($r=$data->fetch_assoc()): ?>
      <tr>
        <td style="font-size:.83rem;color:var(--text-muted);"><?php echo date('M d, Y',strtotime($r['expense_date'])); ?></td>
        <td><span class="cat-pill"><?php echo htmlspecialchars($r['categoryName']); ?></span></td>
        <td><?php echo htmlspecialchars($r['description']??'—'); ?></td>
        <td style="text-align:right;" class="amt-neg">₱<?php echo number_format($r['amount'],2); ?></td>
        <td style="font-size:.8rem;color:var(--text-muted);"><?php echo htmlspecialchars($r['byUser']); ?></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
      <tfoot><tr><td colspan="3">Total</td><td style="text-align:right;" class="amt-neg">₱<?php echo number_format($totalExp,2); ?></td><td></td></tr></tfoot>
    </table>
    </div>
  </div>

  <!-- ══════════════ PROFIT / LOSS ══════════════ -->
  <?php elseif($report === 'profit'):
    $salesData   = $conn->query("SELECT COALESCE(SUM(sd.sold_quantity*p.cost),0) AS cogs, COALESCE(SUM(sd.subtotal),0) AS revenue FROM sales_details sd JOIN product p ON sd.productID=p.productID JOIN sales s ON sd.salesID=s.salesID WHERE DATE(s.saleDate) BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc();
    $expTotal    = floatval($conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM expense WHERE expense_date BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc()['t']);
    $grossProfit = $salesData['revenue'] - $salesData['cogs'];
    $netProfit   = $grossProfit - $expTotal;
  ?>
  <div class="stat-row">
    <div class="stat-card-rp"><div class="stat-icon-rp ic-purple"><i class="bi bi-cash-stack"></i></div><div><div class="stat-lbl-rp">Total Revenue</div><div class="stat-val-rp sm">₱<?php echo number_format($salesData['revenue'],2); ?></div></div></div>
    <div class="stat-card-rp"><div class="stat-icon-rp ic-orange"><i class="bi bi-box-seam"></i></div><div><div class="stat-lbl-rp">Cost of Goods</div><div class="stat-val-rp sm">₱<?php echo number_format($salesData['cogs'],2); ?></div></div></div>
    <div class="stat-card-rp"><div class="stat-icon-rp <?php echo $grossProfit>=0?'ic-green':'ic-red'; ?>"><i class="bi bi-graph-up-arrow"></i></div><div><div class="stat-lbl-rp">Gross Profit</div><div class="stat-val-rp sm <?php echo $grossProfit>=0?'amt-pos':'amt-neg'; ?>">₱<?php echo number_format($grossProfit,2); ?></div></div></div>
    <div class="stat-card-rp"><div class="stat-icon-rp <?php echo $netProfit>=0?'ic-teal':'ic-red'; ?>"><i class="bi bi-bar-chart-line-fill"></i></div><div><div class="stat-lbl-rp">Net Profit</div><div class="stat-val-rp sm <?php echo $netProfit>=0?'amt-pos':'amt-neg'; ?>">₱<?php echo number_format($netProfit,2); ?></div></div></div>
  </div>
  <div class="main-card">
    <div class="main-card-header"><i class="bi bi-calculator"></i> Profit &amp; Loss Statement</div>
    <table class="profit-table">
      <tr><td class="label">Gross Revenue</td><td style="text-align:right;" class="amt-main">₱<?php echo number_format($salesData['revenue'],2); ?></td></tr>
      <tr><td class="sub-label">– Cost of Goods Sold (COGS)</td><td style="text-align:right;" class="amt-neg">-₱<?php echo number_format($salesData['cogs'],2); ?></td></tr>
      <tr class="separator"><td class="label">Gross Profit</td><td style="text-align:right;" class="<?php echo $grossProfit>=0?'amt-pos':'amt-neg'; ?>">₱<?php echo number_format($grossProfit,2); ?></td></tr>
      <tr><td class="sub-label">– Operating Expenses</td><td style="text-align:right;" class="amt-neg">-₱<?php echo number_format($expTotal,2); ?></td></tr>
      <tr class="separator"><td>Net Profit / Loss</td><td style="text-align:right;" class="<?php echo $netProfit>=0?'amt-pos':'amt-neg'; ?>">₱<?php echo number_format($netProfit,2); ?></td></tr>
    </table>
  </div>

  <!-- ══════════════ CUSTOMER REPORT ══════════════ -->
  <?php elseif($report === 'customer'):
    $data = $conn->query("SELECT c.customerName, c.contactNo, c.credit_balance, COUNT(s.salesID) AS purchases, COALESCE(SUM(s.total_amount),0) AS total_spent FROM customer c LEFT JOIN sales s ON c.customerID=s.customerID AND DATE(s.saleDate) BETWEEN '$dateFrom' AND '$dateTo' WHERE c.dateDeleted IS NULL GROUP BY c.customerID ORDER BY total_spent DESC");
  ?>
  <div class="main-card">
    <div class="main-card-header"><i class="bi bi-table"></i> Customer Activity</div>
    <div class="dt-controls" id="dt-search-area"></div>
    <div style="overflow-x:auto;">
    <table id="reportTable">
      <thead><tr><th>Customer</th><th>Contact</th><th>Purchases</th><th>Total Spent</th><th>Credit Balance</th></tr></thead>
      <tbody>
      <?php while($r=$data->fetch_assoc()): ?>
      <tr>
        <td style="font-weight:600;"><?php echo htmlspecialchars($r['customerName']); ?></td>
        <td>
          <?php if(!empty($r['contactNo'])): ?>
          <span class="contact-chip"><i class="bi bi-telephone-fill" style="font-size:.7rem;"></i><?php echo htmlspecialchars($r['contactNo']); ?></span>
          <?php else: ?><span style="color:#ccc;">—</span><?php endif; ?>
        </td>
        <td style="text-align:center;font-weight:700;"><?php echo $r['purchases']; ?></td>
        <td style="text-align:right;" class="amt-main">₱<?php echo number_format($r['total_spent'],2); ?></td>
        <td style="text-align:right;" class="<?php echo $r['credit_balance']>0?'amt-neg':'amt-pos'; ?>">₱<?php echo number_format($r['credit_balance'],2); ?></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /rp-page -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
$(document).ready(function(){
  if($('#reportTable').length){
    $('#reportTable').DataTable({
      pageLength: 25,
      order: [],
      paging: false,
      dom: '<"dt-wrap"lf>rtip',
      language: {
        search: '',
        searchPlaceholder: '🔍  Search…',
        info: 'Showing _START_–_END_ of _TOTAL_ entries',
        paginate: { previous: '‹', next: '›' }
      }
    });
    $('.dataTables_filter').appendTo('#dt-search-area');
    $('.dataTables_length').appendTo('#dt-search-area');
  }
});

function exportExcel(){
  const wb  = XLSX.utils.book_new();
  const tbl = document.getElementById('reportTable');
  if(!tbl){ alert('No table to export'); return; }
  const ws = XLSX.utils.table_to_sheet(tbl);
  XLSX.utils.book_append_sheet(wb, ws, 'Report');
  XLSX.writeFile(wb, '7evelyn_report_<?php echo $report; ?>_<?php echo $dateFrom; ?>_<?php echo $dateTo; ?>.xlsx');
}
</script>
</body></html>