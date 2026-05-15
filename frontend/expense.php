<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';

session_start();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if(!in_array($_SESSION['roleName'], ['Admin','Owner'])){ header("Location: dashboard.php"); exit(); }
$pageTitle = "Expenses – 7Evelyn POS";
$categories = $conn->query("SELECT * FROM expense_category ORDER BY categoryName");
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<div class="topbar no-print">
    <h5><i class="bi bi-wallet2 me-2" style="color:var(--ev-purple);"></i>Expense Tracking</h5>
    <div class="ms-auto"><div class="user-badge"><i class="bi bi-person-circle" style="color:var(--ev-purple);"></i><span><?php echo htmlspecialchars($_SESSION['userName']); ?></span><span class="role-pill"><?php echo htmlspecialchars($_SESSION['roleName']); ?></span></div></div>
</div>

<?php
$alerts=['savedData'=>['success','Saved!','Expense recorded.'],'expenseDeleted'=>['success','Deleted!','Expense removed.'],'categoryAdded'=>['success','Category Added','Expense category saved.'],'emptyFields'=>['warning','Required Fields','Fill in required fields.']];
foreach($alerts as $k=>[$i,$t,$tx]) if(isset($_GET[$k])) echo "<script>Swal.fire({icon:'$i',title:'$t',text:'$tx',timer:2000}).then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";

// Summary stats
$totalMonth = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM expense WHERE MONTH(expense_date)=MONTH(CURDATE()) AND YEAR(expense_date)=YEAR(CURDATE())")->fetch_assoc()['t'];
$totalToday = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM expense WHERE expense_date=CURDATE()")->fetch_assoc()['t'];
?>

<div class="page-body">
    <!-- Summary -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card bg-grad-orange">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-val">₱<?php echo number_format($totalToday,2); ?></div><div class="stat-lbl">Today's Expenses</div></div>
                    <i class="bi bi-calendar-day stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-grad-red">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-val">₱<?php echo number_format($totalMonth,2); ?></div><div class="stat-lbl">This Month</div></div>
                    <i class="bi bi-calendar-month stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mb-3 align-items-center">
        <h5 class="fw-bold mb-0" style="color:#004a38;">Expense Records</h5>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal"><i class="bi bi-tags me-1"></i>Add Category</button>
            <?php if($_SESSION['roleName']==='Admin'): ?>
            <button class="btn btn-ev" data-bs-toggle="modal" data-bs-target="#addExpenseModal"><i class="bi bi-plus-circle me-1"></i>Add Expense</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter -->
    <div class="card card-shadow mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3"><label class="form-label small">From</label><input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo $_GET['date_from'] ?? date('Y-m-01'); ?>"></div>
                <div class="col-md-3"><label class="form-label small">To</label><input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo $_GET['date_to'] ?? date('Y-m-d'); ?>"></div>
                <div class="col-md-3"><label class="form-label small">Category</label>
                  <select name="cat_filter" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <?php $categories->data_seek(0); while($c=$categories->fetch_assoc()): ?>
                    <option value="<?php echo $c['expenseCategoryID']; ?>" <?php echo (isset($_GET['cat_filter']) && $_GET['cat_filter']==$c['expenseCategoryID']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['categoryName']); ?></option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <div class="col-md-3"><button type="submit" class="btn btn-ev btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button></div>
            </form>
        </div>
    </div>

    <div class="card card-shadow">
        <div class="card-body">
            <table id="expenseTable" class="table table-bordered table-striped text-center">
                <thead style="background:var(--ev-gradient);color:#fff;">
                    <tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>Recorded By</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php
                $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
                $dateTo   = $_GET['date_to'] ?? date('Y-m-d');
                $catFilter= intval($_GET['cat_filter'] ?? 0);

                $sql = "SELECT e.*, ec.categoryName, CONCAT(u.givenName,' ',u.surName) AS byUser FROM expense e JOIN expense_category ec ON e.expenseCategoryID=ec.expenseCategoryID JOIN users u ON e.userID=u.userID WHERE e.expense_date BETWEEN '$dateFrom' AND '$dateTo'";
                if($catFilter) $sql .= " AND e.expenseCategoryID=$catFilter";
                $sql .= " ORDER BY e.expense_date DESC, e.dateCreated DESC";
                $expenses = $conn->query($sql);
                $grandTotal = 0;
                while($r=$expenses->fetch_assoc()):
                    $grandTotal += $r['amount'];
                ?>
                <tr>
                    <td><?php echo date('M d, Y',strtotime($r['expense_date'])); ?></td>
                    <td><span class="badge-active"><?php echo htmlspecialchars($r['categoryName']); ?></span></td>
                    <td class="text-start"><?php echo htmlspecialchars($r['description']??'—'); ?></td>
                    <td><strong>₱<?php echo number_format($r['amount'],2); ?></strong></td>
                    <td class="text-muted small"><?php echo htmlspecialchars($r['byUser']); ?></td>
                    <td>
                        <?php if($_SESSION['roleName']==='Admin'): ?>
                        <form method="POST" action="../backend/expenseAuth.php" class="d-inline" onsubmit="return confirm('Delete this expense?')">
            <?php csrf_field(); ?>
                            <input type="hidden" name="expenseID" value="<?php echo $r['expenseID']; ?>">
                            <button type="submit" name="expenseDelete" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold"><td colspan="3" class="text-end">Total:</td><td>₱<?php echo number_format($grandTotal,2); ?></td><td colspan="2"></td></tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
  <div class="modal-dialog modal-sm"><div class="modal-content">
    <form method="POST" action="../backend/expenseAuth.php">
            <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;"><h5 class="modal-title">Add Expense Category</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><div class="mb-2"><label class="form-label fw-semibold small">Category Name <span class="text-danger">*</span></label><input type="text" name="categoryName" class="form-control" required></div></div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" name="addExpenseCategory" class="btn btn-ev btn-sm">Save</button></div>
    </form>
  </div></div>
</div>

<!-- Add Expense Modal -->
<?php if($_SESSION['roleName']==='Admin'): ?>
<div class="modal fade" id="addExpenseModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/expenseAuth.php">
            <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;"><h5 class="modal-title"><i class="bi bi-wallet2 me-1"></i>Record Expense</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label fw-semibold small">Category <span class="text-danger">*</span></label>
          <select name="expenseCategoryID" class="form-select" required>
            <option value="">— Select Category —</option>
            <?php $categories->data_seek(0); while($c=$categories->fetch_assoc()): ?>
            <option value="<?php echo $c['expenseCategoryID']; ?>"><?php echo htmlspecialchars($c['categoryName']); ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label fw-semibold small">Amount (₱) <span class="text-danger">*</span></label><input type="number" name="amount" step="0.01" min="0.01" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-semibold small">Description</label><input type="text" name="description" class="form-control" placeholder="What was this for?"></div>
        <div class="mb-3"><label class="form-label fw-semibold small">Date <span class="text-danger">*</span></label><input type="date" name="expense_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="expenseSave" class="btn btn-ev">Save Expense</button></div>
    </form>
  </div></div>
</div>
<?php endif; ?>

<script>$(document).ready(function(){ $('#expenseTable').DataTable({pageLength:15,order:[[0,'desc']],paging:false}); });</script>
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
