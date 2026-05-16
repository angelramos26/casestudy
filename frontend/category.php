<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';

session_start();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if(!in_array($_SESSION['roleName'], ['Admin','Owner'])){ header("Location: dashboard.php"); exit(); }
$pageTitle = "Categories – 7Evelyn POS";
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<style>
/* ── Category-specific styles only ── */
/* All color tokens, radii, shadows, fonts come from assets/css/smpos.css */

.page-body {
    background: var(--c-ice);
    min-height: calc(100vh - 64px);
    padding: 36px 24px 48px;
}

/* ── Section Header ── */
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}
.section-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--c-navy);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.section-title::before {
    content: '';
    display: inline-block;
    width: 5px;
    height: 22px;
    background: var(--c-gold);
    border-radius: 3px;
}

/* ── Add Button ── */
.btn-add {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--c-gold);
    color: var(--c-navy);
    font-weight: 700;
    font-size: .88rem;
    padding: 10px 22px;
    border: none;
    border-radius: var(--r-md);
    box-shadow: var(--sh-sm);
    cursor: pointer;
    transition: background .18s, transform .15s, box-shadow .18s;
    letter-spacing: .01em;
}
.btn-add:hover {
    background: var(--c-gold-dark);
    transform: translateY(-1px);
    box-shadow: var(--sh-gold);
}
.btn-add i { font-size: 1rem; }

/* ── Card ── */
.cat-card {
    background: var(--s-card);
    border-radius: var(--r-lg);
    box-shadow: var(--sh-md);
    border: 1px solid var(--s-border-dark);
    overflow: hidden;
}

/* ── Table ── */
.cat-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: .9rem;
}
.cat-table thead tr {
    background: var(--c-navy);
}
.cat-table thead th {
    color: var(--c-gold);
    font-weight: 700;
    padding: 14px 20px;
    text-align: center;
    font-size: .82rem;
    letter-spacing: .07em;
    text-transform: uppercase;
    border: none;
}
.cat-table tbody tr {
    transition: background .15s;
}
.cat-table tbody tr:nth-child(even) {
    background: #f9fbfb;
}
.cat-table tbody tr:hover {
    background: var(--c-gold-soft);
}
.cat-table td {
    padding: 13px 20px;
    color: var(--t-main);
    border-bottom: 1px solid var(--s-border-dark);
    vertical-align: middle;
    text-align: center;
}
.cat-table td.name-col {
    text-align: left;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* ── Category Icon ── */
.cat-icon {
    width: 32px;
    height: 32px;
    background: var(--c-gold-soft);
    border-radius: var(--r-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--c-navy);
    font-size: .95rem;
    flex-shrink: 0;
}

/* ── ID Badge ── */
.id-badge {
    display: inline-block;
    background: var(--c-navy);
    color: var(--c-gold);
    font-size: .72rem;
    font-weight: 700;
    padding: 2px 9px;
    border-radius: 99px;
    letter-spacing: .04em;
}

/* ── Count Badge ── */
.count-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: var(--c-ice);
    color: var(--c-navy);
    font-size: .78rem;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 99px;
    border: 1px solid var(--s-border-dark);
}
.count-badge i { color: var(--c-success); font-size: .75rem; }

/* ── Icon Action Buttons ── */
.btn-icon {
    width: 34px;
    height: 34px;
    border: none;
    border-radius: var(--r-xs);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .9rem;
    cursor: pointer;
    transition: background .15s, transform .12s;
}
.btn-icon:hover { transform: scale(1.1); }
.btn-edit {
    background: var(--c-ice);
    color: var(--c-navy);
    border: 1px solid var(--s-border-dark);
}
.btn-edit:hover { background: var(--c-gold-soft); color: var(--c-navy); }
.btn-del {
    background: var(--c-danger-bg);
    color: var(--c-danger);
    border: 1px solid var(--c-danger-bdr);
}
.btn-del:hover { background: var(--c-danger); color: #fff; }

/* ── Empty State ── */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}
.empty-state i {
    font-size: 3rem;
    color: var(--s-border-dark);
    margin-bottom: 12px;
}
.empty-state p { color: var(--t-muted); margin: 0; }

/* ── Modal Confirm Buttons ── */
.btn-confirm-gold {
    background: var(--c-gold);
    color: var(--c-navy);
    font-weight: 700;
    border: none;
    border-radius: var(--r-xs);
    padding: 9px 22px;
    font-size: .88rem;
    cursor: pointer;
    transition: background .15s;
}
.btn-confirm-gold:hover { background: var(--c-gold-dark); }
.btn-confirm-danger {
    background: var(--c-danger);
    color: #fff;
    font-weight: 700;
    border: none;
    border-radius: var(--r-xs);
    padding: 9px 22px;
    font-size: .88rem;
    cursor: pointer;
    transition: background .15s;
}
.btn-confirm-danger:hover { background: #c0392b; }
.btn-cancel {
    background: transparent;
    color: var(--t-muted);
    font-weight: 500;
    border: 1.5px solid var(--s-border);
    border-radius: var(--r-xs);
    padding: 9px 18px;
    font-size: .88rem;
    cursor: pointer;
    transition: border .15s;
}
.btn-cancel:hover { border-color: var(--c-navy); color: var(--c-navy); }

/* ── Delete Confirm Box ── */
.del-confirm-box {
    background: var(--c-danger-bg);
    border: 1px solid var(--c-danger-bdr);
    border-radius: var(--r-md);
    padding: 16px 18px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
}
.del-confirm-box i { color: var(--c-danger); font-size: 1.3rem; margin-top: 2px; flex-shrink: 0; }
.del-confirm-box p { margin: 0; color: var(--t-main); font-size: .88rem; line-height: 1.5; }

/* ── DataTable Overrides ── */
.dataTables_wrapper .dataTables_filter input {
    border: 1.5px solid var(--s-border-dark);
    border-radius: var(--r-xs);
    padding: 6px 12px;
    font-size: .85rem;
    outline: none;
    transition: border .18s;
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color: var(--c-gold);
}
.dataTables_wrapper .dataTables_length select {
    border: 1.5px solid var(--s-border-dark);
    border-radius: var(--r-xs);
    padding: 5px 8px;
}
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_length { color: var(--t-muted); font-size: .82rem; }
.dataTables_wrapper .paginate_button {
    border-radius: var(--r-xs) !important;
    font-size: .82rem !important;
}
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover {
    background: var(--c-navy) !important;
    color: var(--c-gold) !important;
    border-color: var(--c-navy) !important;
}
.dataTables_wrapper .paginate_button:hover:not(.current) {
    background: var(--c-gold-soft) !important;
    color: var(--c-navy) !important;
    border-color: var(--c-gold-soft) !important;
}

@media (max-width: 576px) {
    .cat-table td, .cat-table th { padding: 10px; }
}
</style>

<!-- ── Top Bar ── -->
<?php
$topbarTitle = 'Categories';
$topbarIcon  = 'bi-tags-fill';
$topbarSub   = 'Organize products by category';
include 'topbar.php';
?>

<?php
$alerts = [
    'catAdded'      => ['success', 'Category Added',   'New category has been added successfully.'],
    'catUpdated'    => ['success', 'Category Updated',  'The category name has been updated.'],
    'catDeleted'    => ['success', 'Category Deleted',  'The category has been removed.'],
    'catDuplicate'  => ['error',   'Duplicate Name',    'A category with that name already exists.'],
    'catHasProducts'=> ['error',   'Cannot Delete',     'This category still has active products linked to it.'],
];
foreach($alerts as $k => [$i, $t, $tx])
    if(isset($_GET[$k]))
        echo "<script>Swal.fire({icon:'$i',title:'$t',text:'$tx',timer:2500,showConfirmButton:false}).then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";
?>

<div class="page-body">
    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-9 col-md-11">

            <!-- Section Header -->
            <div class="section-header">
                <h5 class="section-title">Product Categories</h5>
                <?php if($_SESSION['roleName'] === 'Admin'): ?>
                <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addCatModal">
                    <i class="bi bi-plus-lg"></i> Add Category
                </button>
                <?php endif; ?>
            </div>

            <!-- Card -->
            <div class="cat-card">
                <div class="p-3">
                    <table id="catTable" class="cat-table">
                        <thead>
                            <tr>
                                <th style="width:70px;">#</th>
                                <th style="text-align:left;">Category Name</th>
                                <th style="width:130px;">Products</th>
                                <?php if($_SESSION['roleName'] === 'Admin'): ?>
                                <th style="width:110px;">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $cats = $conn->query("SELECT c.*, COUNT(p.productID) AS prodCount FROM category c LEFT JOIN product p ON c.categoryID=p.categoryID AND p.status='Active' GROUP BY c.categoryID ORDER BY c.categoryName ASC");
                        $rowCount = 0;
                        while($r = $cats->fetch_assoc()):
                        $rowCount++;
                        ?>
                        <tr>
                            <td><span class="id-badge"><?php echo $r['categoryID']; ?></span></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="cat-icon"><i class="bi bi-tag-fill"></i></div>
                                    <span style="font-weight:600;color:var(--t-main);"><?php echo htmlspecialchars($r['categoryName']); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="count-badge">
                                    <i class="bi bi-box-seam-fill"></i>
                                    <?php echo $r['prodCount']; ?> item<?php echo $r['prodCount'] != 1 ? 's' : ''; ?>
                                </span>
                            </td>
                            <?php if($_SESSION['roleName'] === 'Admin'): ?>
                            <td>
                                <div style="display:flex;gap:6px;justify-content:center;">
                                    <button class="btn-icon btn-edit" data-bs-toggle="modal" data-bs-target="#editCat<?php echo $r['categoryID']; ?>" title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button class="btn-icon btn-del" data-bs-toggle="modal" data-bs-target="#delCat<?php echo $r['categoryID']; ?>" title="Delete">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>

                        <!-- Edit Category Modal -->
                        <div class="modal fade" id="editCat<?php echo $r['categoryID']; ?>" tabindex="-1">
                          <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                              <form method="POST" action="../backend/categoryAuth.php">
                                <?php csrf_field(); ?>
                                <div class="modal-header modal-header-gold">
                                    <h5 class="modal-title"><i class="bi bi-pencil-fill me-2"></i>Edit Category</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="categoryID" value="<?php echo $r['categoryID']; ?>">
                                    <label class="form-label">Category Name</label>
                                    <input type="text" name="categoryName" value="<?php echo htmlspecialchars($r['categoryName']); ?>" class="form-control" required placeholder="Enter category name">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="catUpdate" class="btn-confirm-gold">Save Changes</button>
                                </div>
                              </form>
                            </div>
                          </div>
                        </div>

                        <!-- Delete Category Modal -->
                        <div class="modal fade" id="delCat<?php echo $r['categoryID']; ?>" tabindex="-1">
                          <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                              <form method="POST" action="../backend/categoryAuth.php">
                                <?php csrf_field(); ?>
                                <div class="modal-header modal-header-danger">
                                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Category</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="categoryID" value="<?php echo $r['categoryID']; ?>">
                                    <div class="del-confirm-box">
                                        <i class="bi bi-exclamation-circle-fill"></i>
                                        <p>You're about to permanently delete <strong><?php echo htmlspecialchars($r['categoryName']); ?></strong>. This action cannot be undone.</p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="catDelete" class="btn-confirm-danger"><i class="bi bi-trash3 me-1"></i>Delete</button>
                                </div>
                              </form>
                            </div>
                          </div>
                        </div>

                        <?php endwhile; ?>

                        <?php if($rowCount === 0): ?>
                        <tr><td colspan="4">
                            <div class="empty-state">
                                <i class="bi bi-tags"></i>
                                <p>No categories found. Add your first category to get started.</p>
                            </div>
                        </td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Add Category Modal -->
<?php if($_SESSION['roleName'] === 'Admin'): ?>
<div class="modal fade" id="addCatModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="../backend/categoryAuth.php">
        <?php csrf_field(); ?>
        <div class="modal-header modal-header-gold">
            <h5 class="modal-title"><i class="bi bi-plus-circle-fill me-2"></i>Add New Category</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <label class="form-label">Category Name <span style="color:var(--c-danger);">*</span></label>
            <input type="text" name="categoryName" class="form-control" required placeholder="e.g. Beverages, Snacks, Meals…" autofocus>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="catSave" class="btn-confirm-gold"><i class="bi bi-check-lg me-1"></i>Add Category</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

</div></div>

<script>
$(document).ready(function(){
    $('#catTable').DataTable({
        pageLength: 20,
        language: {
            search: '',
            searchPlaceholder: 'Search categories…',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_–_END_ of _TOTAL_ categories',
            paginate: { previous: '‹', next: '›' }
        },
        columnDefs: [{ orderable: false, targets: -1 }]
    });
});
</script>

<!-- ── Pusher Real-time ── -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
    const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>
</body></html>