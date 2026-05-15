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
.report-type-btn { border-radius: 20px; font-size: 12px; }
.report-type-btn.active { background: var(--ev-gradient); color: white; border-color: transparent; }
</style>

<div class="topbar no-print">
    <h5><i class="bi bi-bar-chart-line me-2" style="color:var(--ev-purple);"></i>Reports & Analytics</h5>
    <div class="ms-auto d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary no-print" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
        <button class="btn btn-sm btn-ev no-print" onclick="exportExcel()"><i class="bi bi-file-earmark-excel me-1"></i>Excel</button>
        <div class="user-badge ms-2"><span><?php echo htmlspecialchars($_SESSION['userName']); ?></span><span class="role-pill"><?php echo htmlspecialchars($_SESSION['roleName']); ?></span></div>
    </div>
</div>

<div class="page-body">
    <!-- Report Type Selector -->
    <div class="d-flex flex-wrap gap-2 mb-3 no-print">
        <?php
        $types = [
            'daily_sales'    => ['bi-calendar-day','Daily Sales'],
            'weekly_sales'   => ['bi-calendar-week','Weekly Sales'],
            'monthly_sales'  => ['bi-calendar-month','Monthly Sales'],
            'product_sales'  => ['bi-box-seam','Product Sales'],
            'inventory'      => ['bi-archive','Inventory'],
            'expenses'       => ['bi-wallet2','Expenses'],
            'profit'         => ['bi-graph-up-arrow','Profit/Loss'],
            'customer'       => ['bi-people','Customer Report'],
        ];
        foreach($types as $k=>[$icon,$label]):
            $active = $report===$k ? ' active' : '';
        ?>
        <a href="?report=<?php echo $k; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>" class="btn btn-sm btn-outline-secondary report-type-btn<?php echo $active; ?>">
            <i class="bi <?php echo $icon; ?> me-1"></i><?php echo $label; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Date Filter -->
    <?php if(!in_array($report,['inventory'])): ?>
    <div class="card card-shadow mb-3 no-print">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="report" value="<?php echo htmlspecialchars($report); ?>">
                <div class="col-md-3"><label class="form-label small">From</label><input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo $dateFrom; ?>"></div>
                <div class="col-md-3"><label class="form-label small">To</label><input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo $dateTo; ?>"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-ev btn-sm"><i class="bi bi-funnel me-1"></i>Generate</button></div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Report Title -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0" style="color:#004a38;"><?php echo $types[$report][1]; ?> Report</h5>
        <small class="text-muted"><?php echo date('M d, Y',strtotime($dateFrom)); ?> – <?php echo date('M d, Y',strtotime($dateTo)); ?></small>
    </div>

    <?php
    // ===== DAILY SALES REPORT =====
    if($report === 'daily_sales'):
        $data = $conn->query("SELECT DATE(saleDate) AS d, COUNT(*) AS txn, SUM(total_amount) AS revenue, SUM(discount_amount) AS discounts, SUM(tax_amount) AS taxes FROM sales WHERE DATE(saleDate) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY DATE(saleDate) ORDER BY d DESC");
        $totals = $conn->query("SELECT COUNT(*) AS txn, SUM(total_amount) AS revenue, SUM(discount_amount) AS discounts FROM sales WHERE DATE(saleDate) BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc();
    ?>
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="stat-card bg-grad-purple"><div class="stat-val">₱<?php echo number_format($totals['revenue'],2); ?></div><div class="stat-lbl">Total Revenue</div></div></div>
        <div class="col-md-3"><div class="stat-card bg-grad-blue"><div class="stat-val"><?php echo $totals['txn']; ?></div><div class="stat-lbl">Transactions</div></div></div>
        <div class="col-md-3"><div class="stat-card bg-grad-orange"><div class="stat-val">₱<?php echo number_format($totals['discounts'],2); ?></div><div class="stat-lbl">Total Discounts</div></div></div>
    </div>
    <div class="card card-shadow"><div class="card-body">
    <table id="reportTable" class="table table-bordered table-striped">
        <thead style="background:var(--ev-gradient);color:#fff;"><tr><th>Date</th><th>Transactions</th><th>Discounts</th><th>Revenue</th></tr></thead>
        <tbody>
        <?php while($r=$data->fetch_assoc()): ?>
        <tr><td><?php echo date('D, M d, Y',strtotime($r['d'])); ?></td><td class="text-center"><?php echo $r['txn']; ?></td><td class="text-end text-danger">-₱<?php echo number_format($r['discounts'],2); ?></td><td class="text-end"><strong>₱<?php echo number_format($r['revenue'],2); ?></strong></td></tr>
        <?php endwhile; ?>
        </tbody>
        <tfoot><tr class="table-light fw-bold"><td>TOTAL</td><td class="text-center"><?php echo $totals['txn']; ?></td><td class="text-end text-danger">-₱<?php echo number_format($totals['discounts'],2); ?></td><td class="text-end">₱<?php echo number_format($totals['revenue'],2); ?></td></tr></tfoot>
    </table>
    </div></div>

    <?php elseif($report === 'weekly_sales'):
        $data = $conn->query("SELECT YEARWEEK(saleDate,1) AS wk, MIN(DATE(saleDate)) AS week_start, MAX(DATE(saleDate)) AS week_end, COUNT(*) AS txn, SUM(total_amount) AS revenue FROM sales WHERE DATE(saleDate) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY wk ORDER BY wk DESC");
    ?>
    <div class="card card-shadow"><div class="card-body">
    <table id="reportTable" class="table table-bordered table-striped">
        <thead style="background:var(--ev-gradient);color:#fff;"><tr><th>Week</th><th>Period</th><th>Transactions</th><th>Revenue</th></tr></thead>
        <tbody>
        <?php while($r=$data->fetch_assoc()): ?>
        <tr><td>Week <?php echo substr($r['wk'],4); ?>, <?php echo substr($r['wk'],0,4); ?></td><td><?php echo date('M d',strtotime($r['week_start'])).' – '.date('M d, Y',strtotime($r['week_end'])); ?></td><td class="text-center"><?php echo $r['txn']; ?></td><td class="text-end"><strong>₱<?php echo number_format($r['revenue'],2); ?></strong></td></tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div></div>

    <?php elseif($report === 'monthly_sales'):
        $data = $conn->query("SELECT DATE_FORMAT(saleDate,'%Y-%m') AS mo, DATE_FORMAT(saleDate,'%M %Y') AS mo_label, COUNT(*) AS txn, SUM(total_amount) AS revenue, SUM(discount_amount) AS discounts FROM sales WHERE DATE(saleDate) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY mo ORDER BY mo DESC");
        $rows = [];
        while($r=$data->fetch_assoc()) $rows[]=$r;
    ?>
    <canvas id="monthChart" height="80" class="mb-3"></canvas>
    <div class="card card-shadow"><div class="card-body">
    <table id="reportTable" class="table table-bordered table-striped">
        <thead style="background:var(--ev-gradient);color:#fff;"><tr><th>Month</th><th>Transactions</th><th>Discounts</th><th>Revenue</th></tr></thead>
        <tbody>
        <?php foreach($rows as $r): ?>
        <tr><td><?php echo $r['mo_label']; ?></td><td class="text-center"><?php echo $r['txn']; ?></td><td class="text-end text-danger">-₱<?php echo number_format($r['discounts'],2); ?></td><td class="text-end"><strong>₱<?php echo number_format($r['revenue'],2); ?></strong></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div></div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    new Chart(document.getElementById('monthChart'),{type:'bar',data:{labels:<?php echo json_encode(array_column($rows,'mo_label')); ?>,datasets:[{label:'Revenue (₱)',data:<?php echo json_encode(array_column($rows,'revenue')); ?>,backgroundColor:'rgba(0,129,97,0.85)',borderRadius:8}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
    </script>

    <?php elseif($report === 'product_sales'):
        $data = $conn->query("SELECT p.productName, c.categoryName, SUM(sd.sold_quantity) AS total_sold, SUM(sd.subtotal) AS revenue, AVG(sd.price) AS avg_price FROM sales_details sd JOIN product p ON sd.productID=p.productID JOIN category c ON p.categoryID=c.categoryID JOIN sales s ON sd.salesID=s.salesID WHERE DATE(s.saleDate) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY sd.productID ORDER BY total_sold DESC");
    ?>
    <div class="card card-shadow"><div class="card-body">
    <table id="reportTable" class="table table-bordered table-striped">
        <thead style="background:var(--ev-gradient);color:#fff;"><tr><th>#</th><th>Product</th><th>Category</th><th>Qty Sold</th><th>Avg Price</th><th>Revenue</th></tr></thead>
        <tbody>
        <?php $rank=1; while($r=$data->fetch_assoc()): ?>
        <tr><td><?php echo $rank++; ?></td><td class="fw-semibold"><?php echo htmlspecialchars($r['productName']); ?></td><td><span class="badge-active"><?php echo htmlspecialchars($r['categoryName']); ?></span></td><td class="text-center"><strong><?php echo $r['total_sold']; ?></strong></td><td class="text-end">₱<?php echo number_format($r['avg_price'],2); ?></td><td class="text-end"><strong>₱<?php echo number_format($r['revenue'],2); ?></strong></td></tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div></div>

    <?php elseif($report === 'inventory'):
        $data = $conn->query("SELECT p.*, c.categoryName FROM product p JOIN category c ON p.categoryID=c.categoryID WHERE p.status='Active' ORDER BY p.stock_quantity ASC");
    ?>
    <div class="card card-shadow"><div class="card-body">
    <table id="reportTable" class="table table-bordered table-striped">
        <thead style="background:var(--ev-gradient);color:#fff;"><tr><th>Product</th><th>Category</th><th>Stock</th><th>Reorder Lvl</th><th>Cost</th><th>Price</th><th>Expiry</th><th>Status</th></tr></thead>
        <tbody>
        <?php while($r=$data->fetch_assoc()):
            $st = $r['stock_quantity']==0 ? 'OUT' : ($r['stock_quantity']<=$r['reorder_level'] ? 'LOW' : 'OK');
            $cls = $st==='OUT' ? 'badge-inactive' : ($st==='LOW' ? 'badge-low' : 'badge-active');
        ?>
        <tr><td class="fw-semibold"><?php echo htmlspecialchars($r['productName']); ?></td><td><?php echo htmlspecialchars($r['categoryName']); ?></td><td class="text-center"><?php echo $r['stock_quantity']; ?></td><td class="text-center"><?php echo $r['reorder_level']; ?></td><td class="text-end">₱<?php echo number_format($r['cost'],2); ?></td><td class="text-end">₱<?php echo number_format($r['price'],2); ?></td><td><?php echo $r['expiry_date'] ? date('M d, Y',strtotime($r['expiry_date'])) : '—'; ?></td><td><span class="<?php echo $cls; ?>"><?php echo $st; ?></span></td></tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div></div>

    <?php elseif($report === 'expenses'):
        $data = $conn->query("SELECT e.*, ec.categoryName, CONCAT(u.givenName,' ',u.surName) AS byUser FROM expense e JOIN expense_category ec ON e.expenseCategoryID=ec.expenseCategoryID JOIN users u ON e.userID=u.userID WHERE e.expense_date BETWEEN '$dateFrom' AND '$dateTo' ORDER BY e.expense_date DESC");
        $totalExp = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM expense WHERE expense_date BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc()['t'];
    ?>
    <div class="alert alert-warning py-2"><strong>Total Expenses:</strong> ₱<?php echo number_format($totalExp,2); ?></div>
    <div class="card card-shadow"><div class="card-body">
    <table id="reportTable" class="table table-bordered table-striped">
        <thead style="background:var(--ev-gradient);color:#fff;"><tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>By</th></tr></thead>
        <tbody>
        <?php while($r=$data->fetch_assoc()): ?>
        <tr><td><?php echo date('M d, Y',strtotime($r['expense_date'])); ?></td><td><span class="badge-active"><?php echo htmlspecialchars($r['categoryName']); ?></span></td><td><?php echo htmlspecialchars($r['description']??'—'); ?></td><td class="text-end"><strong>₱<?php echo number_format($r['amount'],2); ?></strong></td><td class="small text-muted"><?php echo htmlspecialchars($r['byUser']); ?></td></tr>
        <?php endwhile; ?>
        <tr class="fw-bold table-light"><td colspan="3">Total</td><td class="text-end">₱<?php echo number_format($totalExp,2); ?></td><td></td></tr>
        </tbody>
    </table>
    </div></div>

    <?php elseif($report === 'profit'):
        $salesData = $conn->query("SELECT COALESCE(SUM(sd.sold_quantity*p.cost),0) AS cogs, COALESCE(SUM(sd.subtotal),0) AS revenue FROM sales_details sd JOIN product p ON sd.productID=p.productID JOIN sales s ON sd.salesID=s.salesID WHERE DATE(s.saleDate) BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc();
        $expTotal  = floatval($conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM expense WHERE expense_date BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc()['t']);
        $grossProfit = $salesData['revenue'] - $salesData['cogs'];
        $netProfit   = $grossProfit - $expTotal;
    ?>
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="stat-card bg-grad-purple"><div class="stat-val">₱<?php echo number_format($salesData['revenue'],2); ?></div><div class="stat-lbl">Total Revenue</div></div></div>
        <div class="col-md-3"><div class="stat-card bg-grad-blue"><div class="stat-val">₱<?php echo number_format($salesData['cogs'],2); ?></div><div class="stat-lbl">Cost of Goods</div></div></div>
        <div class="col-md-3"><div class="stat-card bg-grad-<?php echo $grossProfit>=0?'green':'red'; ?>"><div class="stat-val">₱<?php echo number_format($grossProfit,2); ?></div><div class="stat-lbl">Gross Profit</div></div></div>
        <div class="col-md-3"><div class="stat-card bg-grad-<?php echo $netProfit>=0?'teal':'red'; ?>"><div class="stat-val">₱<?php echo number_format($netProfit,2); ?></div><div class="stat-lbl">Net Profit</div></div></div>
    </div>
    <div class="card card-shadow"><div class="card-body">
    <table class="table table-sm mb-0">
        <tbody>
            <tr><td class="fw-semibold">Gross Revenue</td><td class="text-end">₱<?php echo number_format($salesData['revenue'],2); ?></td></tr>
            <tr><td class="text-muted">– Cost of Goods Sold (COGS)</td><td class="text-end text-danger">-₱<?php echo number_format($salesData['cogs'],2); ?></td></tr>
            <tr class="fw-bold border-top"><td>Gross Profit</td><td class="text-end <?php echo $grossProfit>=0?'text-success':'text-danger'; ?>">₱<?php echo number_format($grossProfit,2); ?></td></tr>
            <tr><td class="text-muted">– Operating Expenses</td><td class="text-end text-danger">-₱<?php echo number_format($expTotal,2); ?></td></tr>
            <tr class="fw-bold border-top table-light"><td>Net Profit / Loss</td><td class="text-end <?php echo $netProfit>=0?'text-success':'text-danger'; ?>" style="font-size:1.1rem;">₱<?php echo number_format($netProfit,2); ?></td></tr>
        </tbody>
    </table>
    </div></div>

    <?php elseif($report === 'customer'):
        $data = $conn->query("SELECT c.customerName, c.contactNo, c.credit_balance, COUNT(s.salesID) AS purchases, COALESCE(SUM(s.total_amount),0) AS total_spent FROM customer c LEFT JOIN sales s ON c.customerID=s.customerID AND DATE(s.saleDate) BETWEEN '$dateFrom' AND '$dateTo' WHERE c.dateDeleted IS NULL GROUP BY c.customerID ORDER BY total_spent DESC");
    ?>
    <div class="card card-shadow"><div class="card-body">
    <table id="reportTable" class="table table-bordered table-striped">
        <thead style="background:var(--ev-gradient);color:#fff;"><tr><th>Customer</th><th>Contact</th><th>Purchases</th><th>Total Spent</th><th>Credit Balance</th></tr></thead>
        <tbody>
        <?php while($r=$data->fetch_assoc()): ?>
        <tr><td class="fw-semibold"><?php echo htmlspecialchars($r['customerName']); ?></td><td class="text-muted small"><?php echo htmlspecialchars($r['contactNo']??'—'); ?></td><td class="text-center"><?php echo $r['purchases']; ?></td><td class="text-end"><strong>₱<?php echo number_format($r['total_spent'],2); ?></strong></td><td class="text-end <?php echo $r['credit_balance']>0?'text-danger fw-bold':'text-success'; ?>">₱<?php echo number_format($r['credit_balance'],2); ?></td></tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div></div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
$(document).ready(function(){
    if($('#reportTable').length){
        $('#reportTable').DataTable({pageLength:25,order:[],paging:false});
    }
});

function exportExcel(){
    const wb = XLSX.utils.book_new();
    const tbl = document.getElementById('reportTable');
    if(!tbl){ alert('No table to export'); return; }
    const ws = XLSX.utils.table_to_sheet(tbl);
    XLSX.utils.book_append_sheet(wb, ws, 'Report');
    XLSX.writeFile(wb, '7evelyn_report_<?php echo $report; ?>_<?php echo $dateFrom; ?>_<?php echo $dateTo; ?>.xlsx');
}
</script>
</div></div>
</body></html>
