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
<div class="topbar no-print">
    <h5><i class="bi bi-tags me-2" style="color:var(--ev-purple);"></i>Categories</h5>
    <div class="ms-auto"><span class="text-muted small"><?php echo htmlspecialchars($_SESSION['userName']); ?></span></div>
</div>
<?php
$alerts=['catAdded'=>['success','Category Added','New category has been added.'],'catUpdated'=>['success','Updated','Category updated.'],'catDeleted'=>['success','Deleted','Category deleted.'],'catDuplicate'=>['error','Duplicate','Category name already exists.'],'catHasProducts'=>['error','Cannot Delete','Category has existing products.']];
foreach($alerts as $k=>[$i,$t,$tx]) if(isset($_GET[$k])) echo "<script>Swal.fire({icon:'$i',title:'$t',text:'$tx',timer:2000}).then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";
?>
<div class="page-body">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold mb-0" style="color:#004a38;">Product Categories</h5>
                <?php if($_SESSION['roleName']==='Admin'): ?>
                <button class="btn btn-ev" data-bs-toggle="modal" data-bs-target="#addCatModal"><i class="bi bi-plus-circle me-1"></i>Add Category</button>
                <?php endif; ?>
            </div>
            <div class="card card-shadow">
                <div class="card-body">
                    <table id="catTable" class="table table-bordered table-striped text-center">
                        <thead style="background:var(--ev-gradient);color:#fff;"><tr><th>#</th><th>Category Name</th><th>Products</th><?php if($_SESSION['roleName']==='Admin'): ?><th width="120">Action</th><?php endif; ?></tr></thead>
                        <tbody>
                        <?php
                        $cats = $conn->query("SELECT c.*, COUNT(p.productID) AS prodCount FROM category c LEFT JOIN product p ON c.categoryID=p.categoryID AND p.status='Active' GROUP BY c.categoryID ORDER BY c.categoryName ASC");
                        while($r=$cats->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $r['categoryID']; ?></td>
                            <td class="text-start fw-semibold"><?php echo htmlspecialchars($r['categoryName']); ?></td>
                            <td><span class="badge-active"><?php echo $r['prodCount']; ?></span></td>
                            <?php if($_SESSION['roleName']==='Admin'): ?>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCat<?php echo $r['categoryID']; ?>"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delCat<?php echo $r['categoryID']; ?>"><i class="bi bi-trash"></i></button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <!-- Edit Cat Modal -->
                        <div class="modal fade" id="editCat<?php echo $r['categoryID']; ?>" tabindex="-1">
                          <div class="modal-dialog"><div class="modal-content">
                            <form method="POST" action="../backend/categoryAuth.php">
            <?php csrf_field(); ?>
                              <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
                                <h5 class="modal-title">Edit Category</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                              </div>
                              <div class="modal-body">
                                <input type="hidden" name="categoryID" value="<?php echo $r['categoryID']; ?>">
                                <label class="form-label">Category Name</label>
                                <input type="text" name="categoryName" value="<?php echo htmlspecialchars($r['categoryName']); ?>" class="form-control" required>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="catUpdate" class="btn btn-ev">Update</button>
                              </div>
                            </form>
                          </div></div>
                        </div>
                        <!-- Del Cat Modal -->
                        <div class="modal fade" id="delCat<?php echo $r['categoryID']; ?>" tabindex="-1">
                          <div class="modal-dialog"><div class="modal-content">
                            <form method="POST" action="../backend/categoryAuth.php">
            <?php csrf_field(); ?>
                              <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title">Delete Category</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                              </div>
                              <div class="modal-body">
                                <input type="hidden" name="categoryID" value="<?php echo $r['categoryID']; ?>">
                                <p>Delete <strong><?php echo htmlspecialchars($r['categoryName']); ?></strong>? This cannot be undone.</p>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="catDelete" class="btn btn-danger">Delete</button>
                              </div>
                            </form>
                          </div></div>
                        </div>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<?php if($_SESSION['roleName']==='Admin'): ?>
<div class="modal fade" id="addCatModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/categoryAuth.php">
            <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title">Add Category</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Category Name *</label>
        <input type="text" name="categoryName" class="form-control" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="catSave" class="btn btn-ev">Save</button>
      </div>
    </form>
  </div></div>
</div>
<?php endif; ?>

</div></div>
<script>$(document).ready(function(){ $('#catTable').DataTable({pageLength:20}); });</script>

<!-- ── Pusher Real-time ───────────────────────────────────────────────────── -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
    const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>
<!-- ─────────────────────────────────────────────────────────────────────── -->
</body></html>
