<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
$pageTitle = "Order History – Restaurant POS";

$dateFrom = $_GET['date_from'] ?? date('Y-m-d');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');
$typeFilter   = $_GET['order_type']  ?? '';
$statusFilter = $_GET['status']      ?? '';

$where  = "WHERE DATE(o.dateCreated) BETWEEN ? AND ?";
$params = [$dateFrom, $dateTo];
$types  = "ss";

if($typeFilter)   { $where .= " AND o.orderType=?"; $params[] = $typeFilter;   $types .= "s"; }
if($statusFilter) { $where .= " AND o.status=?";    $params[] = $statusFilter; $types .= "s"; }

$stmt = $conn->prepare("
    SELECT o.orderID, o.orderNo, o.orderType, o.status, o.pax,
           o.subtotal, o.discount_amount, o.service_charge, o.total_amount,
           o.payment_method, o.discount_type,
           o.dateCreated, o.datePaid,
           dt.tableNo, dt.section,
           CONCAT(u.givenName,' ',u.surName) AS staffName,
           c.customerName,
           COUNT(oi.orderItemID) AS item_count
    FROM orders o
    LEFT JOIN dining_table dt ON o.tableID = dt.tableID
    LEFT JOIN users u ON o.userID = u.userID
    LEFT JOIN customer c ON o.customerID = c.customerID
    LEFT JOIN order_items oi ON o.orderID = oi.orderID AND oi.item_status != 'Void'
    $where
    GROUP BY o.orderID, o.orderNo, o.orderType, o.status, o.pax,
             o.subtotal, o.discount_amount, o.service_charge, o.total_amount,
             o.payment_method, o.discount_type,
             o.dateCreated, o.datePaid,
             dt.tableNo, dt.section,
             u.givenName, u.surName,
             c.customerName
    ORDER BY o.dateCreated DESC
    LIMIT 300
");
if (!$stmt) {
    die("Query prepare failed: " . $conn->error);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$_res = $stmt->get_result();
$orders = [];
while ($_row = $_res->fetch_assoc()) $orders[] = $_row;
$stmt->close();

// Quick stats
$totalRev    = array_sum(array_column(array_filter($orders, fn($o) => $o['status'] === 'Paid'), 'total_amount'));
$totalOrders = count($orders);
$paidCount   = count(array_filter($orders, fn($o) => $o['status'] === 'Paid'));
$openCount   = count(array_filter($orders, fn($o) => in_array($o['status'], ['Open','Active'])));
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<style>
.oh-body { padding: 24px 28px; background: var(--s-bg); min-height: calc(100vh - var(--topbar-h)); }

/* Filter card */
.filter-card {
    background: var(--c-white); border-radius: var(--r-md);
    padding: 16px 20px; margin-bottom: 20px;
    border: 1.5px solid var(--s-border); box-shadow: var(--sh-sm);
}
.filter-row { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
.filter-group { display: flex; flex-direction: column; gap: 4px; }
.filter-group label { font-size: 11px; font-weight: 700; color: var(--t-muted); text-transform: uppercase; letter-spacing: .5px; }
.filter-group input, .filter-group select {
    border: 1.5px solid var(--s-border); border-radius: var(--r-sm);
    padding: 8px 12px; font-size: 13px; color: var(--t-main);
    background: var(--s-bg); outline: none; font-family: var(--font-ui);
    transition: border-color .2s;
}
.filter-group input:focus, .filter-group select:focus { border-color: var(--charcoal); background: var(--c-white); }
.btn-filter {
    background: var(--charcoal); color: var(--orange); border: none;
    border-radius: var(--r-sm); padding: 9px 20px; font-size: 13px;
    font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
    transition: all .2s; font-family: var(--font-ui);
}
.btn-filter:hover { background: var(--charcoal-mid); }

/* Stats row */
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px; }
@media(max-width:900px){ .stats-row { grid-template-columns: repeat(2,1fr); } }
.stat-tile {
    background: var(--c-white); border-radius: var(--r-md);
    padding: 18px 20px; box-shadow: var(--sh-sm);
    border: 1.5px solid var(--s-border); display: flex; align-items: center; gap: 14px;
}
.stat-icon {
    width: 46px; height: 46px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; flex-shrink: 0;
}
.stat-num { font-size: 1.4rem; font-weight: 900; color: var(--charcoal); font-family: var(--font-mono); line-height: 1; }
.stat-lbl { font-size: 11.5px; color: var(--t-muted); font-weight: 600; margin-top: 2px; text-transform: uppercase; letter-spacing: .5px; }

/* Table card */
.orders-card {
    background: var(--c-white); border-radius: var(--r-md);
    box-shadow: var(--sh-sm); border: 1.5px solid var(--s-border); overflow: hidden;
}
.orders-card-header {
    padding: 14px 20px; border-bottom: 1.5px solid var(--s-border);
    background: #FFF8F0; display: flex; align-items: center; justify-content: space-between;
}
.orders-card-title { font-size: 14px; font-weight: 700; color: var(--charcoal); display: flex; align-items: center; gap: 8px; }

/* Status pills */
.status-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
    white-space: nowrap;
}
.s-Open    { background: #fef3c7; color: #92400e; }
.s-Active  { background: #dbeafe; color: #1e40af; }
.s-Paid    { background: #dcfce7; color: #166534; }
.s-Cancelled { background: #fee2e2; color: #991b1b; }
.s-Void    { background: #f3f4f6; color: #6b7280; }

/* Order type pills */
.type-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 20px; font-size: 10.5px; font-weight: 700;
}
.t-DineIn   { background: #ede9ff; color: #3d2ea0; }
.t-Takeout  { background: #e6f9ef; color: #166534; }
.t-Delivery { background: #fff0e6; color: #b85c00; }

/* Receipt modal */
.rcpt-body { font-family: 'Courier New', monospace; font-size: 12.5px; max-height: 80vh; overflow-y: auto; }
</style>

<?php
$topbarTitle = 'Order History';
$topbarIcon  = 'bi-receipt';
$topbarSub   = 'View and manage all restaurant orders';
include 'topbar.php';
?>

<div class="oh-body">

    <!-- Filter Card -->
    <div class="filter-card">
        <form method="GET" action="orders.php">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Date From</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                </div>
                <div class="filter-group">
                    <label>Date To</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                </div>
                <div class="filter-group">
                    <label>Order Type</label>
                    <select name="order_type">
                        <option value="">All Types</option>
                        <option value="Dine-In"  <?= $typeFilter==='Dine-In'  ? 'selected' : '' ?>>Dine-In</option>
                        <option value="Takeout"  <?= $typeFilter==='Takeout'  ? 'selected' : '' ?>>Takeout</option>
                        <option value="Delivery" <?= $typeFilter==='Delivery' ? 'selected' : '' ?>>Delivery</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="Open"      <?= $statusFilter==='Open'      ? 'selected' : '' ?>>Open</option>
                        <option value="Active"    <?= $statusFilter==='Active'    ? 'selected' : '' ?>>Active</option>
                        <option value="Paid"      <?= $statusFilter==='Paid'      ? 'selected' : '' ?>>Paid</option>
                        <option value="Cancelled" <?= $statusFilter==='Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <button type="submit" class="btn-filter">
                    <i class="bi bi-search"></i> Search
                </button>
                <a href="orders.php" class="btn-filter" style="background:var(--s-bg);color:var(--t-mid);border:1.5px solid var(--s-border);">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-tile">
            <div class="stat-icon" style="background:#ede9ff;color:#3d2ea0;">
                <i class="bi bi-receipt"></i>
            </div>
            <div>
                <div class="stat-num"><?= $totalOrders ?></div>
                <div class="stat-lbl">Total Orders</div>
            </div>
        </div>
        <div class="stat-tile">
            <div class="stat-icon" style="background:#dcfce7;color:#166534;">
                <i class="bi bi-cash-coin"></i>
            </div>
            <div>
                <div class="stat-num">₱<?= number_format($totalRev, 0) ?></div>
                <div class="stat-lbl">Revenue</div>
            </div>
        </div>
        <div class="stat-tile">
            <div class="stat-icon" style="background:#dbeafe;color:#1e40af;">
                <i class="bi bi-check-circle"></i>
            </div>
            <div>
                <div class="stat-num"><?= $paidCount ?></div>
                <div class="stat-lbl">Paid Orders</div>
            </div>
        </div>
        <div class="stat-tile">
            <div class="stat-icon" style="background:#fef3c7;color:#92400e;">
                <i class="bi bi-clock"></i>
            </div>
            <div>
                <div class="stat-num"><?= $openCount ?></div>
                <div class="stat-lbl">Open / Active</div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="orders-card">
        <div class="orders-card-header">
            <div class="orders-card-title">
                <div style="width:28px;height:28px;background:var(--orange);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;color:var(--charcoal);">
                    <i class="bi bi-list-ul"></i>
                </div>
                Order List
            </div>
            <small style="color:var(--t-muted);font-size:12px;font-weight:600;">
                <?= $dateFrom === $dateTo ? date('M d, Y', strtotime($dateFrom)) : date('M d', strtotime($dateFrom)) . ' – ' . date('M d, Y', strtotime($dateTo)) ?>
            </small>
        </div>

        <div style="overflow-x:auto;">
            <table id="ordersTable" class="table table-hover mb-0" style="font-size:13px;width:100%;">
                <thead>
                    <tr style="background:#f7f6fb;">
                        <th style="padding:10px 16px;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--t-muted);white-space:nowrap;">Order #</th>
                        <th style="padding:10px 16px;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--t-muted);">Type</th>
                        <th style="padding:10px 16px;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--t-muted);">Table / Ref</th>
                        <th style="padding:10px 16px;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--t-muted);">Items</th>
                        <th style="padding:10px 16px;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--t-muted);">Guest</th>
                        <th style="padding:10px 16px;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--t-muted);">Payment</th>
                        <th style="padding:10px 16px;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--t-muted);">Total</th>
                        <th style="padding:10px 16px;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--t-muted);">Status</th>
                        <th style="padding:10px 16px;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--t-muted);">Staff</th>
                        <th style="padding:10px 16px;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--t-muted);">Date</th>
                        <th style="padding:10px 16px;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--t-muted);"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($orders as $o):
                    $typeClass = match($o['orderType']) {
                        'Dine-In'  => 't-DineIn',
                        'Takeout'  => 't-Takeout',
                        'Delivery' => 't-Delivery',
                        default    => 't-DineIn'
                    };
                    $typeIcon = match($o['orderType']) {
                        'Dine-In'  => 'bi-people-fill',
                        'Takeout'  => 'bi-bag-fill',
                        'Delivery' => 'bi-bicycle',
                        default    => 'bi-people-fill'
                    };
                    $statClass = 's-' . str_replace('-', '', $o['status']);
                    $tableRef = $o['tableNo'] ? 'T' . $o['tableNo'] . ($o['section'] ? ' · ' . $o['section'] : '') : '#' . $o['orderID'];
                    $dateDisplay = date('M d, h:iA', strtotime($o['dateCreated']));
                ?>
                <tr style="border-bottom:1px solid var(--s-border);">
                    <td style="padding:11px 16px;font-weight:800;color:var(--charcoal);"><?= htmlspecialchars($o['orderNo'] ?? '#' . $o['orderID']) ?></td>
                    <td style="padding:11px 16px;">
                        <span class="type-pill <?= $typeClass ?>">
                            <i class="bi <?= $typeIcon ?>"></i>
                            <?= htmlspecialchars($o['orderType']) ?>
                        </span>
                    </td>
                    <td style="padding:11px 16px;font-weight:700;color:var(--t-mid);"><?= htmlspecialchars($tableRef) ?></td>
                    <td style="padding:11px 16px;">
                        <span style="background:var(--s-bg);padding:3px 9px;border-radius:6px;font-weight:700;font-size:12px;">
                            <?= (int)$o['item_count'] ?> itm
                        </span>
                    </td>
                    <td style="padding:11px 16px;font-size:12.5px;color:var(--t-mid);">
                        <?= htmlspecialchars($o['customerName'] ?: 'Walk-in') ?>
                    </td>
                    <td style="padding:11px 16px;font-size:12px;">
                        <?php if($o['payment_method']): ?>
                        <span style="background:var(--s-bg);padding:2px 8px;border-radius:5px;font-weight:700;font-size:11.5px;">
                            <?= htmlspecialchars($o['payment_method']) ?>
                        </span>
                        <?php else: ?>
                        <span style="color:var(--t-muted);">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:11px 16px;font-weight:900;color:var(--charcoal);font-family:var(--font-mono);">
                        ₱<?= number_format($o['total_amount'] ?? 0, 2) ?>
                    </td>
                    <td style="padding:11px 16px;">
                        <span class="status-pill <?= $statClass ?>">
                            <?= htmlspecialchars($o['status']) ?>
                        </span>
                    </td>
                    <td style="padding:11px 16px;font-size:12px;color:var(--t-muted);"><?= htmlspecialchars($o['staffName'] ?? '—') ?></td>
                    <td style="padding:11px 16px;font-size:12px;color:var(--t-muted);white-space:nowrap;" data-order="<?= strtotime($o['dateCreated']) ?>"><?= $dateDisplay ?></td>
                    <td style="padding:11px 16px;">
                        <button class="btn-view-order" onclick="viewOrder(<?= $o['orderID'] ?>)"
                            style="background:var(--charcoal);color:var(--orange);border:none;border-radius:7px;padding:5px 12px;font-size:11.5px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:4px;transition:all .15s;"
                            onmouseover="this.style.background='var(--charcoal-mid)'" onmouseout="this.style.background='var(--charcoal)'">
                            <i class="bi bi-eye"></i> View
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ── Order Receipt Modal ── -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:460px;">
        <div class="modal-content" style="border:none;border-radius:18px;overflow:hidden;">
            <div class="modal-header" style="background:var(--charcoal);border:none;padding:15px 22px;">
                <h5 class="modal-title" style="font-weight:800;color:#fff;font-size:.95rem;">
                    <i class="bi bi-receipt me-2" style="color:var(--orange);"></i>
                    <span id="orderModalTitle">Order Details</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body rcpt-body" id="orderModalBody" style="padding:20px;">
                <div class="text-center py-4"><div class="spinner-border text-secondary" role="status"></div></div>
            </div>
            <div class="modal-footer" style="border-top:1.5px solid var(--s-border);padding:12px 20px;gap:8px;">
                <button class="btn btn-sm" onclick="printOrder()"
                    style="background:var(--s-bg);border:1.5px solid var(--s-border);color:var(--t-mid);font-weight:700;font-size:12px;border-radius:8px;padding:6px 14px;">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
                <?php if(in_array($_SESSION['roleName'], ['Admin'])): ?>
                <button class="btn btn-sm" id="btnCancelOrder" onclick="cancelCurrentOrder()"
                    style="background:var(--c-danger-bg);border:1.5px solid var(--c-danger-bg);color:var(--c-danger);font-weight:700;font-size:12px;border-radius:8px;padding:6px 14px;">
                    <i class="bi bi-x-circle me-1"></i>Cancel Order
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
let currentViewOrderID = null;

function viewOrder(orderID) {
    currentViewOrderID = orderID;
    document.getElementById('orderModalTitle').textContent = 'Loading…';
    document.getElementById('orderModalBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-secondary" role="status"></div></div>';
    var cancelBtn = document.getElementById('btnCancelOrder');
    if (cancelBtn) cancelBtn.style.display = '';

    // Reuse existing modal instance (Bootstrap 5)
    var orderModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('orderModal'));
    orderModal.show();

    fetch('../backend/getOrderDetails.php?orderID=' + encodeURIComponent(orderID))
        .then(function(r) { return r.text(); })
        .then(function(html) {
            document.getElementById('orderModalBody').innerHTML = html;
            document.getElementById('orderModalTitle').textContent = 'Order #' + orderID;
        })
        .catch(function() {
            document.getElementById('orderModalBody').innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Could not load order details.</div>';
        });
}

function printOrder() {
    const content = document.getElementById('orderModalBody').innerHTML;
    const w = window.open('', '_blank', 'width=360,height=700');
    w.document.write(`<html><head><title>Receipt</title><style>
        body{font-family:'Courier New',monospace;font-size:12px;padding:16px;max-width:300px;margin:0 auto;}
        table{width:100%;border-collapse:collapse;font-size:11.5px;}
        td,th{padding:3px 4px;}
        .text-center{text-align:center;} .text-end{text-align:right;} .text-muted{color:#888;}
        .fw-bold{font-weight:bold;} .fs-6{font-size:13px;} .mb-0{margin-bottom:0;} .my-2{margin:6px 0;} .my-1{margin:3px 0;} .mt-1{margin-top:4px;}
        .small{font-size:10.5px;} hr{border-top:1px dashed #ccc;margin:5px 0;}
        .table-sm td,.table-sm th{padding:3px 4px;}
    </style></head><body>${content}
</body></html>`);
    w.document.close(); w.print();
}

function cancelCurrentOrder() {
    if(!currentViewOrderID) return;
    Swal.fire({
        title: 'Cancel this order?',
        text: 'This will cancel the order and release the table.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--c-danger)',
        confirmButtonText: 'Yes, cancel it',
        cancelButtonText: 'Go back'
    }).then(r => {
        if(!r.isConfirmed) return;
        const fd = new FormData();
        fd.append('cancelOrder', 1);
        fd.append('orderID', currentViewOrderID);
        fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
        fetch('../backend/orderAuth.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.success) {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('orderModal')).hide();
                    Swal.fire({ icon: 'success', title: 'Order Cancelled', timer: 1200, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: d.message });
                }
            });
    });
}

// Init DataTable
$(document).ready(function(){
    $('#ordersTable').DataTable({
        pageLength: 25,
        order: [[9, 'desc']],
        columnDefs: [{ orderable: false, targets: [10] }],
        language: {
            search: '',
            searchPlaceholder: 'Search orders…',
            lengthMenu: 'Show _MENU_ orders',
            info: '_START_–_END_ of _TOTAL_ orders',
            emptyTable: '<div style="padding:40px 0;color:var(--t-muted);"><i class="bi bi-receipt" style="font-size:2.5rem;opacity:.2;display:block;margin-bottom:10px;"></i>No orders found for the selected filters.</div>',
            zeroRecords: '<div style="padding:40px 0;color:var(--t-muted);"><i class="bi bi-search" style="font-size:2rem;opacity:.2;display:block;margin-bottom:8px;"></i>No matching orders found.</div>'
        }
    });
});
</script>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
    const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>

<?php include 'footer.php'; ?>