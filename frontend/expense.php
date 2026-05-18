<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if(!in_array($_SESSION['roleName'], ['Admin','Owner'])){ header("Location: dashboard.php"); exit(); }
$pageTitle = "Expenses – Beng's Unli Lugaw";
$categories = $conn->query("SELECT * FROM expense_category ORDER BY categoryName");

// Summary stats
$totalMonth = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM expense WHERE MONTH(expense_date)=MONTH(CURDATE()) AND YEAR(expense_date)=YEAR(CURDATE())")->fetch_assoc()['t'];
$totalToday = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM expense WHERE expense_date=CURDATE()")->fetch_assoc()['t'];
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<style>
/* ── Body ── */
.ex-body { padding: 24px 28px; background: var(--s-bg); min-height: calc(100vh - 52px); }

/* ── Stats ── */
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px; }
@media(max-width:900px){ .stats-row { grid-template-columns: repeat(2,1fr); } }

.stat-tile {
    background: var(--c-white); border-radius: var(--r-md);
    padding: 18px 20px; box-shadow: var(--sh-sm);
    border: 1px solid var(--s-border);
    display: flex; align-items: center; gap: 14px;
    transition: box-shadow .2s, transform .2s;
}
.stat-tile:hover { box-shadow: var(--sh-md); transform: translateY(-2px); }
.icon-wrap {
    width: 48px; height: 48px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0;
}
.icon-wrap.navy   { background: var(--charcoal);    color: var(--orange); }
.icon-wrap.warn   { background: #fef3e2;         color: var(--warn); }
.icon-wrap.danger { background: #fde8ea;         color: var(--c-danger); }
.icon-wrap.ice    { background: var(--s-border); color: var(--charcoal-light); }

.stat-val { font-size: 1.2rem; font-weight: 900; color: var(--t-main); line-height: 1.2; }
.stat-lbl { font-size: 11.5px; color: var(--t-muted); font-weight: 600; margin-top: 2px; }

/* ── Page header ── */
.page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 18px;
}
.page-header h5 { margin: 0; font-size: 1.05rem; font-weight: 900; color: var(--t-main); display: flex; align-items: center; gap: 8px; }
.page-header h5 i { color: var(--charcoal-light); }
.header-actions { display: flex; gap: 8px; }

.btn-primary-act {
    background: var(--charcoal); color: var(--orange);
    border: none; border-radius: var(--r-sm);
    padding: 9px 18px; font-size: 13px; font-weight: 800;
    cursor: pointer; transition: all .2s;
    display: inline-flex; align-items: center; gap: 6px;
    box-shadow: 0 3px 12px rgba(26,26,26,.15);
}
.btn-primary-act:hover { background: var(--charcoal-mid); }

.btn-secondary-act {
    background: var(--c-white); color: var(--t-mid);
    border: 1.5px solid var(--s-border); border-radius: var(--r-sm);
    padding: 8px 16px; font-size: 13px; font-weight: 700;
    cursor: pointer; transition: all .2s;
    display: inline-flex; align-items: center; gap: 6px;
}
.btn-secondary-act:hover { border-color: var(--charcoal); color: var(--charcoal); }

/* ── Filter card ── */
.filter-card {
    background: var(--c-white); border-radius: var(--r-md);
    box-shadow: var(--sh-sm); border: 1px solid var(--s-border);
    padding: 16px 20px; margin-bottom: 18px;
}
.filter-card .fc-title { font-size: 11px; font-weight: 800; letter-spacing: 1px; color: var(--t-muted); text-transform: uppercase; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
.form-label-sm { font-size: 12px; font-weight: 700; color: var(--t-mid); margin-bottom: 4px; display: block; }
.form-field {
    width: 100%; padding: 8px 12px;
    border: 1.5px solid var(--s-border); border-radius: var(--r-sm);
    font-size: 13px; color: var(--t-main); background: var(--s-bg);
    outline: none; transition: border-color .2s;
}
.form-field:focus { border-color: var(--charcoal); background: var(--c-white); box-shadow: none; }

.btn-filter {
    background: var(--charcoal); color: var(--orange);
    border: none; border-radius: var(--r-sm);
    padding: 8px 20px; font-size: 13px; font-weight: 700;
    cursor: pointer; transition: all .2s;
    display: inline-flex; align-items: center; gap: 6px; width: 100%;
    justify-content: center;
}
.btn-filter:hover { background: var(--charcoal-mid); }

/* ── Table card ── */
.table-card { background: var(--c-white); border-radius: var(--r-md); box-shadow: var(--sh-sm); border: 1px solid var(--s-border); overflow: hidden; }
.table-card-head {
    padding: 14px 20px; border-bottom: 1px solid var(--s-border);
    display: flex; align-items: center; justify-content: space-between;
    background: #FFF8F0;
}
.table-card-head .tc-title { font-size: 13px; font-weight: 800; color: var(--t-main); display: flex; align-items: center; gap: 7px; }
.table-card-head .tc-title i { color: var(--charcoal-light); }
.tc-total { font-size: 14px; font-weight: 900; color: var(--c-danger); }

.table-wrap { padding: 16px 20px; overflow-x: auto; }

table#expenseTable { width: 100%; border-collapse: collapse; }
table#expenseTable thead tr th {
    background: var(--charcoal); color: var(--orange);
    font-size: 11px; font-weight: 800; letter-spacing: .6px;
    text-transform: uppercase; padding: 11px 14px;
    white-space: nowrap; border: none;
}
table#expenseTable tbody tr { transition: background .12s; }
table#expenseTable tbody tr:hover { background: #f5f8ff; }
table#expenseTable tbody tr:nth-child(even) { background: #FFF8F0; }
table#expenseTable tbody tr:nth-child(even):hover { background: #f0f4ff; }
table#expenseTable tbody td {
    padding: 10px 14px; font-size: 12.5px; color: var(--t-main);
    border-bottom: 1px solid var(--s-bg); vertical-align: middle;
}
table#expenseTable tfoot td {
    padding: 10px 14px; font-size: 13px; font-weight: 800;
    color: var(--charcoal); background: #f0f4ff;
    border-top: 2px solid var(--s-border);
}

.date-cell { font-size: 12px; white-space: nowrap; color: var(--t-mid); }
.cat-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 800; letter-spacing: .3px;
    background: #eae6ff; color: var(--charcoal-light);
}
.desc-cell { color: var(--t-main); font-size: 12.5px; }
.amount-cell { font-size: 13.5px; font-weight: 900; color: var(--c-danger); white-space: nowrap; }
.user-cell { color: var(--t-muted); font-size: 12px; }

.btn-del {
    background: #fde8ea; color: var(--c-danger);
    border: 1.5px solid #f7c0c6; border-radius: var(--r-sm);
    width: 30px; height: 30px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 13px; cursor: pointer; transition: all .15s;
}
.btn-del:hover { background: var(--c-danger); color: #fff; border-color: var(--c-danger); }

/* DataTables overrides */
.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select {
    border: 1.5px solid var(--s-border) !important; border-radius: var(--r-sm) !important;
    padding: 5px 10px !important; font-size: 12.5px !important; outline: none !important;
    background: var(--s-bg) !important; color: var(--t-main) !important;
}
.dataTables_wrapper .dataTables_filter input:focus,
.dataTables_wrapper .dataTables_length select:focus { border-color: var(--charcoal) !important; background: var(--c-white) !important; box-shadow: none !important; }
.dataTables_wrapper .dataTables_filter label,
.dataTables_wrapper .dataTables_length label,
.dataTables_wrapper .dataTables_info { font-size: 12px; color: var(--t-muted); }
.dataTables_wrapper .dataTables_paginate .paginate_button { border-radius: var(--r-sm) !important; font-size: 12px !important; color: var(--t-mid) !important; }
.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover { background: var(--charcoal) !important; color: var(--orange) !important; border-color: var(--charcoal) !important; }
.dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: var(--s-bg) !important; color: var(--charcoal) !important; border-color: var(--s-border) !important; }

/* ── Modals ── */
.modal-navy .modal-header { background: var(--charcoal); color: var(--c-white); border: none; }
.modal-navy .modal-title  { font-weight: 800; }
.modal-navy .modal-title i { color: var(--orange); }
.modal-navy .modal-content { border: none; border-radius: 16px; overflow: hidden; }
.modal-navy .btn-close-white { filter: brightness(0) invert(1); opacity: .7; }

.btn-save { background: var(--charcoal); color: var(--orange); border: none; border-radius: var(--r-sm); padding: 9px 22px; font-size: 13px; font-weight: 800; cursor: pointer; transition: all .2s; }
.btn-save:hover { background: var(--charcoal-mid); }
.btn-cancel { background: var(--s-bg); color: var(--t-mid); border: 1.5px solid var(--s-border); border-radius: var(--r-sm); padding: 8px 18px; font-size: 13px; font-weight: 700; cursor: pointer; transition: all .2s; }
.btn-cancel:hover { background: var(--s-border); }
</style>

<?php
$alerts = [
    'savedData'      => ['success', 'Saved!',            'Expense recorded.'],
    'expenseDeleted' => ['success', 'Deleted!',           'Expense removed.'],
    'categoryAdded'  => ['success', 'Category Added',     'Expense category saved.'],
    'emptyFields'    => ['warning', 'Required Fields',    'Fill in required fields.'],
];
foreach($alerts as $k => [$i,$t,$tx])
    if(isset($_GET[$k]))
        echo "<script>Swal.fire({icon:'$i',title:'$t',text:'$tx',timer:2000}).then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";
?>

<!-- Topbar -->
<?php
$topbarTitle = 'Expense Tracking';
$topbarIcon  = 'bi-wallet2';
$topbarSub   = 'Monitor and record business expenses';
include 'topbar.php';
?>

<div class="ex-body">

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-tile">
            <div class="icon-wrap warn"><i class="bi bi-calendar-day"></i></div>
            <div>
                <div class="stat-val">₱<?php echo number_format($totalToday, 2); ?></div>
                <div class="stat-lbl">Today's Expenses</div>
            </div>
        </div>
        <div class="stat-tile">
            <div class="icon-wrap danger"><i class="bi bi-calendar-month"></i></div>
            <div>
                <div class="stat-val">₱<?php echo number_format($totalMonth, 2); ?></div>
                <div class="stat-lbl">This Month</div>
            </div>
        </div>
    </div>

    <!-- Page Header -->
    <div class="page-header">
        <h5>
            <i class="bi bi-journal-text"></i>
            Expense Records
        </h5>
        <div class="header-actions">
            <button class="btn-secondary-act" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-tags"></i> Add Category
            </button>
            <?php if($_SESSION['roleName'] === 'Admin'): ?>
            <button class="btn-primary-act" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                <i class="bi bi-plus-circle-fill"></i> Add Expense
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card no-print">
        <div class="fc-title"><i class="bi bi-funnel-fill"></i> Filter Expenses</div>
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label-sm">Date From</label>
                <input type="date" name="date_from" class="form-field" value="<?php echo $_GET['date_from'] ?? date('Y-m-01'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label-sm">Date To</label>
                <input type="date" name="date_to" class="form-field" value="<?php echo $_GET['date_to'] ?? date('Y-m-d'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label-sm">Category</label>
                <select name="cat_filter" class="form-field" style="cursor:pointer;">
                    <option value="">All Categories</option>
                    <?php $categories->data_seek(0); while($c = $categories->fetch_assoc()): ?>
                    <option value="<?php echo $c['expenseCategoryID']; ?>"
                        <?php echo (isset($_GET['cat_filter']) && $_GET['cat_filter'] == $c['expenseCategoryID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['categoryName']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn-filter">
                    <i class="bi bi-funnel"></i> Apply Filter
                </button>
            </div>
        </form>
    </div>

    <?php
    $dateFrom  = $_GET['date_from'] ?? date('Y-m-01');
    $dateTo    = $_GET['date_to']   ?? date('Y-m-d');
    $catFilter = intval($_GET['cat_filter'] ?? 0);

    $sql = "SELECT e.*, ec.categoryName, CONCAT(u.givenName,' ',u.surName) AS byUser
            FROM expense e
            JOIN expense_category ec ON e.expenseCategoryID = ec.expenseCategoryID
            JOIN users u ON e.userID = u.userID
            WHERE e.expense_date BETWEEN '$dateFrom' AND '$dateTo'";
    if($catFilter) $sql .= " AND e.expenseCategoryID=$catFilter";
    $sql .= " ORDER BY e.expense_date DESC, e.dateCreated DESC";
    $expenses   = $conn->query($sql);
    $grandTotal = 0;
    $rows = [];
    while($r = $expenses->fetch_assoc()){ $grandTotal += $r['amount']; $rows[] = $r; }
    ?>

    <!-- Table Card -->
    <div class="table-card">
        <div class="table-card-head">
            <div class="tc-title">
                <i class="bi bi-table"></i>
                Expense Log
            </div>
            <span class="tc-total">Total: ₱<?php echo number_format($grandTotal, 2); ?></span>
        </div>
        <div class="table-wrap">
            <table id="expenseTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Recorded By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($rows as $r): ?>
                <tr>
                    <td><span class="date-cell"><?php echo date('M d, Y', strtotime($r['expense_date'])); ?></span></td>
                    <td>
                        <span class="cat-badge">
                            <i class="bi bi-tag-fill"></i>
                            <?php echo htmlspecialchars($r['categoryName']); ?>
                        </span>
                    </td>
                    <td><span class="desc-cell"><?php echo htmlspecialchars($r['description'] ?? '—'); ?></span></td>
                    <td><span class="amount-cell">₱<?php echo number_format($r['amount'], 2); ?></span></td>
                    <td><span class="user-cell"><?php echo htmlspecialchars($r['byUser']); ?></span></td>
                    <td>
                        <?php if($_SESSION['roleName'] === 'Admin'): ?>
                        <form method="POST" action="../backend/expenseAuth.php" class="d-inline"
                              onsubmit="return confirm('Delete this expense?')">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="expenseID" value="<?php echo $r['expenseID']; ?>">
                            <button type="submit" name="expenseDelete" class="btn-del" title="Delete">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align:right;font-weight:800;color:var(--t-mid);">Period Total</td>
                        <td style="color:var(--c-danger);font-size:14px;font-weight:900;">₱<?php echo number_format($grandTotal, 2); ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

<!-- Add Category Modal -->
<div class="modal fade modal-navy" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="../backend/expenseAuth.php">
                <?php csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-tags me-2"></i>Add Expense Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:20px 24px;">
                    <label class="form-label-sm">Category Name <span style="color:var(--c-danger);">*</span></label>
                    <input type="text" name="categoryName" class="form-field" required placeholder="e.g. Utilities, Rent…">
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--s-border);padding:12px 24px;gap:8px;">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="addExpenseCategory" class="btn-save">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<?php if($_SESSION['roleName'] === 'Admin'): ?>
<div class="modal fade modal-navy" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="../backend/expenseAuth.php">
                <?php csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-wallet2 me-2"></i>Record Expense</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:20px 24px;">
                    <div class="mb-3">
                        <label class="form-label-sm">Category <span style="color:var(--c-danger);">*</span></label>
                        <select name="expenseCategoryID" class="form-field" style="cursor:pointer;" required>
                            <option value="">— Select Category —</option>
                            <?php $categories->data_seek(0); while($c = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $c['expenseCategoryID']; ?>"><?php echo htmlspecialchars($c['categoryName']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm">Amount (₱) <span style="color:var(--c-danger);">*</span></label>
                        <input type="number" name="amount" step="0.01" min="0.01" class="form-field" required placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm">Description</label>
                        <input type="text" name="description" class="form-field" placeholder="What was this expense for?">
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm">Date <span style="color:var(--c-danger);">*</span></label>
                        <input type="date" name="expense_date" class="form-field" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--s-border);padding:12px 24px;gap:8px;">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="expenseSave" class="btn-save">
                        <i class="bi bi-plus-circle me-1"></i>Save Expense
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
$(document).ready(function(){
    $('#expenseTable').DataTable({
        pageLength: 15,
        order: [[0, 'desc']],
        columnDefs: [{ orderable: false, targets: 5 }],
        language: {
            search: '',
            searchPlaceholder: 'Search expenses…',
            lengthMenu: 'Show _MENU_ rows',
            info: 'Showing _START_–_END_ of _TOTAL_ records',
            paginate: { previous: '‹', next: '›' }
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
</body></html>