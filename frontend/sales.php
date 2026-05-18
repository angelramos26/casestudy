<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';

if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
$pageTitle = "Sales Records – Restaurant POS";

$dateFrom   = $_GET['date_from'] ?? date('Y-m-d');
$dateTo     = $_GET['date_to']   ?? date('Y-m-d');
$roleFilter = in_array($_SESSION['roleName'], ['Admin','Owner']);
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<style>

.sr-body { padding: 24px 28px; background: var(--s-bg); min-height: calc(100vh - 52px); }

/* ── Filter card ── */
.filter-card {
    background: var(--c-white);
    border-radius: var(--r-md);
    box-shadow: var(--sh-sm);
    padding: 16px 20px;
    margin-bottom: 20px;
    border: 1px solid var(--s-border);
}
.filter-card .fc-title {
    font-size: 11px; font-weight: 800; letter-spacing: 1px;
    color: var(--t-muted); text-transform: uppercase; margin-bottom: 12px;
    display: flex; align-items: center; gap: 6px;
}
.filter-card .form-label { font-size: 12px; font-weight: 700; color: var(--t-mid); margin-bottom: 4px; }
.filter-card .form-control,
.filter-card .form-select {
    border: 1.5px solid var(--s-border);
    border-radius: var(--r-sm);
    font-size: 13px; color: var(--t-main);
    background: var(--s-bg);
    padding: 7px 12px;
    outline: none;
    transition: border-color .2s;
}
.filter-card .form-control:focus,
.filter-card .form-select:focus { border-color: var(--charcoal); background: var(--c-white); box-shadow: none; }

.btn-filter {
    background: var(--charcoal); color: var(--orange);
    border: none; border-radius: var(--r-sm);
    padding: 8px 20px; font-size: 13px; font-weight: 700;
    cursor: pointer; transition: all .2s;
    display: inline-flex; align-items: center; gap: 6px;
}
.btn-filter:hover { background: var(--charcoal-mid); }

.btn-export {
    background: var(--c-white); color: var(--charcoal);
    border: 1.5px solid var(--s-border);
    border-radius: var(--r-sm);
    padding: 7px 16px; font-size: 13px; font-weight: 700;
    cursor: pointer; transition: all .2s;
    display: inline-flex; align-items: center; gap: 6px;
}
.btn-export:hover { border-color: var(--charcoal); }

/* ── Stat cards ── */
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px; }
@media(max-width:900px){ .stats-row { grid-template-columns: repeat(2,1fr); } }

.stat-tile {
    background: var(--c-white);
    border-radius: var(--r-md);
    padding: 18px 20px;
    box-shadow: var(--sh-sm);
    border: 1px solid var(--s-border);
    display: flex; align-items: center; gap: 14px;
    transition: box-shadow .2s, transform .2s;
}
.stat-tile:hover { box-shadow: var(--sh-md); transform: translateY(-2px); }
.stat-tile .icon-wrap {
    width: 48px; height: 48px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; flex-shrink: 0;
}
.icon-wrap.navy  { background: var(--charcoal);       color: var(--orange); }
.icon-wrap.gold  { background: var(--gold-dim);   color: var(--charcoal); }
.icon-wrap.ice   { background: var(--s-border);   color: var(--charcoal-light); }
.icon-wrap.green { background: #d0f5e8;            color: #0d8f56; }

.stat-tile .stat-val { font-size: 1.25rem; font-weight: 900; color: var(--t-main); line-height: 1.2; }
.stat-tile .stat-lbl { font-size: 11.5px; color: var(--t-muted); font-weight: 600; margin-top: 2px; }

/* ── Table card ── */
.table-card {
    background: var(--c-white);
    border-radius: var(--r-md);
    box-shadow: var(--sh-sm);
    border: 1px solid var(--s-border);
    overflow: hidden;
}
.table-card-head {
    padding: 14px 20px;
    border-bottom: 1px solid var(--s-border);
    display: flex; align-items: center; justify-content: space-between;
    background: #FFF8F0;
}
.table-card-head .tc-title {
    font-size: 13.5px; font-weight: 800; color: var(--t-main);
    display: flex; align-items: center; gap: 7px;
}
.table-card-head .tc-title i { color: var(--charcoal-light); }

.table-wrap { overflow-x: auto; }

table#salesTable { width: 100%; border-collapse: collapse; }
table#salesTable thead tr th {
    background: var(--charcoal);
    color: var(--orange);
    font-size: 11.5px;
    font-weight: 800;
    letter-spacing: .5px;
    text-transform: uppercase;
    padding: 11px 14px;
    white-space: nowrap;
    border: none;
}
table#salesTable thead tr th:first-child { border-radius: 0; }
table#salesTable tbody tr { transition: background .12s; }
table#salesTable tbody tr:hover { background: #f5f8ff; }
table#salesTable tbody tr:nth-child(even) { background: #FFF8F0; }
table#salesTable tbody tr:nth-child(even):hover { background: #f0f4ff; }
table#salesTable tbody td {
    padding: 10px 14px;
    font-size: 12.5px;
    color: var(--t-main);
    border-bottom: 1px solid var(--s-bg);
    vertical-align: middle;
}

.txn-id { font-size: 11px; font-weight: 700; color: var(--t-muted); font-family: monospace; }
.cashier-name { font-weight: 700; color: var(--t-main); }
.customer-name { color: var(--t-muted); font-size: 12px; }
.amount-cell { font-weight: 800; color: var(--t-main); }
.discount-cell { color: var(--c-danger); font-weight: 700; }
.total-cell { font-size: 13.5px; font-weight: 900; color: var(--charcoal); }

.method-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 800; letter-spacing: .3px;
}
.method-badge.cash   { background: #e6f9f0; color: #0d8f56; }
.method-badge.gcash  { background: #eae6ff; color: #5b3fc8; }
.method-badge.card   { background: #e6f0ff; color: #1a5fd4; }
.method-badge.credit { background: #fff0e6; color: #b85c00; }

.btn-view {
    background: var(--s-bg); color: var(--charcoal);
    border: 1.5px solid var(--s-border);
    border-radius: var(--r-sm);
    width: 30px; height: 30px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 13px; cursor: pointer; transition: all .15s;
}
.btn-view:hover { background: var(--charcoal); color: var(--orange); border-color: var(--charcoal); }

/* DataTables overrides */
.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select {
    border: 1.5px solid var(--s-border) !important;
    border-radius: var(--r-sm) !important;
    padding: 5px 10px !important;
    font-size: 12.5px !important;
    outline: none !important;
    background: var(--s-bg) !important;
    color: var(--t-main) !important;
}
.dataTables_wrapper .dataTables_filter input:focus,
.dataTables_wrapper .dataTables_length select:focus {
    border-color: var(--charcoal) !important;
    background: var(--c-white) !important;
    box-shadow: none !important;
}
.dataTables_wrapper .dataTables_filter label,
.dataTables_wrapper .dataTables_length label,
.dataTables_wrapper .dataTables_info { font-size: 12px; color: var(--t-muted); }
.dataTables_wrapper .dataTables_paginate .paginate_button {
    border-radius: var(--r-sm) !important;
    font-size: 12px !important;
    color: var(--t-mid) !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
    background: var(--charcoal) !important;
    color: var(--orange) !important;
    border-color: var(--charcoal) !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: var(--s-bg) !important;
    color: var(--charcoal) !important;
    border-color: var(--s-border) !important;
}

/* ── Modal ── */
#saleDetailModal .modal-header {
    background: var(--charcoal); color: var(--c-white); border: none;
}
#saleDetailModal .modal-header .modal-title { font-weight: 800; }
#saleDetailModal .modal-header .modal-title i { color: var(--orange); }
#saleDetailModal .btn-close-white { filter: brightness(0) invert(1); opacity: .7; }
</style>

<!-- Topbar -->
<?php
$topbarTitle = 'Sales Records';
$topbarIcon  = 'bi-receipt-cutoff';
$topbarSub   = 'View and filter all completed transactions';
include 'topbar.php';
?>

<div class="sr-body">

    <!-- Filter Card -->
    <div class="filter-card no-print">
        <div class="fc-title"><i class="bi bi-funnel-fill"></i> Filter Sales</div>
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $dateFrom; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $dateTo; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Payment Method</label>
                <select name="method" class="form-select">
                    <option value="">All Methods</option>
                    <option value="Cash"   <?php echo ($_GET['method']??'')==='Cash'  ?'selected':''; ?>>Cash</option>
                    <option value="GCash"  <?php echo ($_GET['method']??'')==='GCash' ?'selected':''; ?>>GCash</option>
                    <option value="Card"   <?php echo ($_GET['method']??'')==='Card'  ?'selected':''; ?>>Card</option>
                    <option value="Credit" <?php echo ($_GET['method']??'')==='Credit'?'selected':''; ?>>Credit (Utang)</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn-filter">
                    <i class="bi bi-funnel"></i> Apply Filter
                </button>
                <button type="button" class="btn-export" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </form>
    </div>

    <?php
    $method = sanitize($_GET['method'] ?? '');
    $sql = "SELECT o.orderID AS salesID,
                   CONCAT(pu.givenName,' ',pu.surName) AS cashier,
                   c.customerName,
                   o.total_amount, o.discount_amount,
                   o.tax_amount, o.payment, o.change_amount,
                   o.payment_method,
                   o.datePaid AS saleDate,
                   o.orderNo, o.orderType,
                   o.service_charge,
                   CONCAT(u.givenName,' ',u.surName) AS waiterName
            FROM orders o
            LEFT JOIN users pu ON o.paid_by = pu.userID
            LEFT JOIN users u  ON o.userID  = u.userID
            LEFT JOIN customer c ON o.customerID = c.customerID
            WHERE o.status = 'Paid'
              AND DATE(o.datePaid) BETWEEN '$dateFrom' AND '$dateTo'";
    if($method) $sql .= " AND o.payment_method='" . $conn->real_escape_string($method) . "'";
    $sql .= " ORDER BY o.datePaid DESC";
    $sales = $conn->query($sql);

    $totalsql = "SELECT COALESCE(SUM(total_amount),0) AS revenue,
                        COUNT(*) AS txnCount,
                        COALESCE(SUM(discount_amount),0) AS totalDisc
                 FROM orders WHERE status='Paid' AND DATE(datePaid) BETWEEN '$dateFrom' AND '$dateTo'";
    if($method) $totalsql .= " AND payment_method='" . $conn->real_escape_string($method) . "'";
    $totals = $conn->query($totalsql)->fetch_assoc();

    if(!$sales) {
        $sales = null;
        $queryError = $conn->error;
    }
    if(!$totals) $totals = ['revenue' => 0, 'txnCount' => 0, 'totalDisc' => 0];
    $avgTxn = $totals['txnCount'] > 0
        ? number_format($totals['revenue'] / $totals['txnCount'], 2)
        : '0.00';
    ?>

    <!-- Stats Row -->
    <div class="stats-row">
        <div class="stat-tile">
            <div class="icon-wrap navy"><i class="bi bi-cash-coin"></i></div>
            <div>
                <div class="stat-val">₱<?php echo number_format($totals['revenue'], 2); ?></div>
                <div class="stat-lbl">Total Revenue</div>
            </div>
        </div>
        <div class="stat-tile">
            <div class="icon-wrap gold"><i class="bi bi-receipt"></i></div>
            <div>
                <div class="stat-val"><?php echo number_format($totals['txnCount']); ?></div>
                <div class="stat-lbl">Transactions</div>
            </div>
        </div>
        <div class="stat-tile">
            <div class="icon-wrap ice"><i class="bi bi-tag"></i></div>
            <div>
                <div class="stat-val">₱<?php echo number_format($totals['totalDisc'], 2); ?></div>
                <div class="stat-lbl">Total Discounts</div>
            </div>
        </div>
        <div class="stat-tile">
            <div class="icon-wrap green"><i class="bi bi-bar-chart-line"></i></div>
            <div>
                <div class="stat-val">₱<?php echo $avgTxn; ?></div>
                <div class="stat-lbl">Avg per Transaction</div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="table-card">
        <div class="table-card-head">
            <div class="tc-title">
                <i class="bi bi-table"></i>
                Sales Transactions
            </div>
            <small style="color:var(--t-muted);font-size:12px;">
                <?php echo date('M d', strtotime($dateFrom)); ?> – <?php echo date('M d, Y', strtotime($dateTo)); ?>
            </small>
        </div>
        <div class="table-wrap" style="padding:16px 20px;">
            <?php if(isset($queryError)): ?>
            <div class="alert alert-danger" style="font-size:13px;">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Query error:</strong> <?php echo htmlspecialchars($queryError); ?>
            </div>
            <?php endif; ?>
            <table id="salesTable" class="table" style="width:100%;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cashier</th>
                        <th>Customer</th>
                        <th>Subtotal</th>
                        <th>Discount</th>
                        <th>Total</th>
                        <th>Tendered</th>
                        <th>Method</th>
                        <th>Date / Time</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if(!isset($queryError)): ?>
                <?php while($r = $sales ? $sales->fetch_assoc() : null):
                    if(!$r) break;
                    $subtotal = $r['total_amount'] + $r['discount_amount'] - ($r['tax_amount'] ?? 0) - ($r['service_charge'] ?? 0);
                    $methodKey = strtolower($r['payment_method'] ?? '');
                ?>
                <tr>
                    <td><span class="txn-id"><?php echo htmlspecialchars($r['orderNo'] ?? '#'.$r['salesID']); ?></span></td>
                    <td><span class="cashier-name"><?php echo htmlspecialchars($r['cashier'] ?: ($r['waiterName'] ?: '—')); ?></span></td>
                    <td><span class="customer-name"><?php echo htmlspecialchars($r['customerName'] ?? 'Walk-in'); ?></span></td>
                    <td class="amount-cell">₱<?php echo number_format($subtotal, 2); ?></td>
                    <td class="discount-cell">−₱<?php echo number_format($r['discount_amount'], 2); ?></td>
                    <td class="total-cell">₱<?php echo number_format($r['total_amount'], 2); ?></td>
                    <td class="amount-cell">₱<?php echo number_format($r['payment'] ?? 0, 2); ?></td>
                    <td>
                        <span class="method-badge <?php echo $methodKey; ?>">
                            <?php
                            $icons = ['cash'=>'bi-cash','gcash'=>'bi-phone','card'=>'bi-credit-card','credit'=>'bi-clock-history'];
                            echo '<i class="bi '.($icons[$methodKey]??'bi-wallet').' me-1"></i>';
                            echo htmlspecialchars($r['payment_method'] ?? '—');
                            ?>
                        </span>
                    </td>
                    <td style="color:var(--t-muted);font-size:12px;white-space:nowrap;" data-order="<?php echo strtotime($r['saleDate']); ?>">
                        <?php echo date('M d, Y', strtotime($r['saleDate'])); ?><br>
                        <span style="font-size:11px;"><?php echo date('h:i A', strtotime($r['saleDate'])); ?></span>
                    </td>
                    <td>
                        <button class="btn-view" onclick="viewSaleDetails(<?php echo $r['salesID']; ?>)" title="View details">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Sale Detail Modal -->
<div class="modal fade" id="saleDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Transaction Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="saleDetailBody">
                <div class="text-center py-4">
                    <div class="spinner-border" style="color:var(--charcoal);"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $('#salesTable').DataTable({
        pageLength: 20,
        order: [[8, 'desc']],
        columnDefs: [{ orderable: false, targets: 9 }],
        language: {
            search: '',
            searchPlaceholder: 'Search transactions…',
            lengthMenu: 'Show _MENU_ rows',
            info: 'Showing _START_–_END_ of _TOTAL_ transactions',
            paginate: { previous: '‹', next: '›' },
            emptyTable: '<div style="padding:32px 0;color:var(--t-muted);"><i class="bi bi-receipt" style="font-size:2rem;opacity:.25;display:block;margin-bottom:8px;"></i>No paid orders found for this date range.</div>',
            zeroRecords: '<div style="padding:32px 0;color:var(--t-muted);"><i class="bi bi-search" style="font-size:1.6rem;opacity:.25;display:block;margin-bottom:8px;"></i>No matching transactions found.</div>'
        }
    });
});

function viewSaleDetails(salesID){
    // Bootstrap 5 modal API
    var modalEl = document.getElementById('saleDetailModal');
    var modal   = bootstrap.Modal.getOrCreateInstance(modalEl);
    document.getElementById('saleDetailBody').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border" style="color:var(--charcoal);"></div></div>';
    modal.show();

    fetch('getSaleDetails.php?salesID=' + encodeURIComponent(salesID))
        .then(function(r){ return r.text(); })
        .then(function(html){ document.getElementById('saleDetailBody').innerHTML = html; })
        .catch(function(){
            document.getElementById('saleDetailBody').innerHTML =
                '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Could not load transaction details.</div>';
        });
}
</script>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
    const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>
<?php include 'footer.php'; ?>
</body></html>