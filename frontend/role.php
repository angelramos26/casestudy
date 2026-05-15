<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';

session_start();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if($_SESSION['roleName'] !== 'Admin'){ header("Location: dashboard.php"); exit(); }
$pageTitle = "Role Management – 7Evelyn POS";
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<div class="topbar no-print">
    <h5><i class="bi bi-shield-check me-2" style="color:var(--ev-purple);"></i>Role Management</h5>
    <div class="ms-auto"><div class="user-badge"><i class="bi bi-person-circle" style="color:var(--ev-purple);"></i><span><?php echo htmlspecialchars($_SESSION['userName']); ?></span><span class="role-pill"><?php echo htmlspecialchars($_SESSION['roleName']); ?></span></div></div>
</div>

<?php
$alerts=['savedData'=>['success','Saved!','Role created.'],'updatedRole'=>['success','Updated!','Role updated.'],'roleDeleted'=>['success','Deleted!','Role removed.'],'nameDuplicate'=>['error','Duplicate Name','This role name already exists.'],'emptyFields'=>['warning','Required Fields','Fill in role name.']];
foreach($alerts as $k=>[$i,$t,$tx]) if(isset($_GET[$k])) echo "<script>Swal.fire({icon:'$i',title:'$t',text:'$tx',timer:2000}).then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";
?>

<div class="page-body">
    <div class="d-flex justify-content-between mb-3 align-items-center">
        <h5 class="fw-bold mb-0" style="color:#004a38;">System Roles</h5>
        <button class="btn btn-ev" data-bs-toggle="modal" data-bs-target="#addRoleModal"><i class="bi bi-plus-circle me-1"></i>Add Role</button>
    </div>

    <!-- Role Access Matrix -->
    <div class="card card-shadow mb-3">
        <div class="card-header bg-white fw-bold py-2" style="border-bottom:1px solid #d6ede6;"><i class="bi bi-shield-shaded me-2" style="color:var(--ev-purple);"></i>Role Access Summary</div>
        <div class="card-body">
            <table class="table table-sm table-bordered text-center mb-0">
                <thead class="table-light">
                    <tr><th>Module</th><th>Admin</th><th>Owner</th><th>Cashier</th></tr>
                </thead>
                <tbody>
                    <?php
                    $access = [
                        'Dashboard'          => [true,true,true],
                        'Point of Sale'      => [true,false,true],
                        'Sales Records'      => [true,true,true],
                        'Products'           => [true,true,false],
                        'Categories'         => [true,true,false],
                        'Inventory/Stocks'   => [true,true,false],
                        'Customers'          => [true,true,false],
                        'Suppliers'          => [true,true,false],
                        'Purchase Orders'    => [true,true,false],
                        'Expenses'           => [true,true,false],
                        'Reports'            => [true,true,false],
                        'User Management'    => [true,false,false],
                        'Role Management'    => [true,false,false],
                        'System Settings'    => [true,false,false],
                    ];
                    foreach($access as $module=>$perms):
                    ?>
                    <tr>
                        <td class="text-start"><?php echo $module; ?></td>
                        <?php foreach($perms as $p): ?>
                        <td><?php echo $p ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>'; ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-shadow">
        <div class="card-body">
            <table id="roleTable" class="table table-bordered table-striped text-center">
                <thead style="background:var(--ev-gradient);color:#fff;">
                    <tr><th>Role Name</th><th>Description</th><th>Users</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php
                $roles = $conn->query("SELECT r.*, COUNT(u.userID) AS userCount FROM role r LEFT JOIN users u ON r.roleID=u.roleID AND u.dateDeleted IS NULL WHERE r.dateDeleted IS NULL GROUP BY r.roleID ORDER BY r.roleName");
                while($r=$roles->fetch_assoc()):
                ?>
                <tr>
                    <td class="fw-semibold"><span class="badge-active"><?php echo htmlspecialchars($r['roleName']); ?></span></td>
                    <td class="text-muted text-start"><?php echo htmlspecialchars($r['roleDesc']??'—'); ?></td>
                    <td><span class="badge bg-secondary"><?php echo $r['userCount']; ?> user(s)</span></td>
                    <td>
                        <button class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#editRoleModal<?php echo $r['roleID']; ?>"><i class="bi bi-pencil"></i></button>
                        <?php if($r['userCount'] == 0): ?>
                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteRoleModal<?php echo $r['roleID']; ?>"><i class="bi bi-trash"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editRoleModal<?php echo $r['roleID']; ?>" tabindex="-1">
                  <div class="modal-dialog"><div class="modal-content">
                    <form method="POST" action="../backend/roleAuth.php">
            <?php csrf_field(); ?>
                      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;"><h5 class="modal-title">Edit Role</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                      <div class="modal-body">
                        <input type="hidden" name="roleID" value="<?php echo $r['roleID']; ?>">
                        <div class="mb-3"><label class="form-label fw-semibold small">Role Name <span class="text-danger">*</span></label><input type="text" name="roleName" value="<?php echo htmlspecialchars($r['roleName']); ?>" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label fw-semibold small">Description</label><input type="text" name="roleDesc" value="<?php echo htmlspecialchars($r['roleDesc']??''); ?>" class="form-control"></div>
                      </div>
                      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="roleUpdate" class="btn btn-ev">Update</button></div>
                    </form>
                  </div></div>
                </div>

                <!-- Delete Modal -->
                <div class="modal fade" id="deleteRoleModal<?php echo $r['roleID']; ?>" tabindex="-1">
                  <div class="modal-dialog modal-sm"><div class="modal-content">
                    <form method="POST" action="../backend/roleAuth.php">
            <?php csrf_field(); ?>
                      <div class="modal-header bg-danger text-white"><h5 class="modal-title">Delete Role</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                      <div class="modal-body"><input type="hidden" name="roleID" value="<?php echo $r['roleID']; ?>"><p>Delete role <strong><?php echo htmlspecialchars($r['roleName']); ?></strong>?</p></div>
                      <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" name="roleDelete" class="btn btn-danger btn-sm">Delete</button></div>
                    </form>
                  </div></div>
                </div>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/roleAuth.php">
            <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;"><h5 class="modal-title"><i class="bi bi-shield-plus me-1"></i>Add Role</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label fw-semibold small">Role Name <span class="text-danger">*</span></label><input type="text" name="roleName" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-semibold small">Description</label><input type="text" name="roleDesc" class="form-control" placeholder="e.g. Can only process sales"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="roleSave" class="btn btn-ev">Save Role</button></div>
    </form>
  </div></div>
</div>

<script>$(document).ready(function(){ $('#roleTable').DataTable({pageLength:10,order:[[0,'asc']]}); });</script>
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
