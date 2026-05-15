<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';

session_start();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if($_SESSION['roleName'] !== 'Admin'){ header("Location: dashboard.php"); exit(); }
$pageTitle = "User Management – 7Evelyn POS";
$roles = $conn->query("SELECT * FROM role WHERE dateDeleted IS NULL ORDER BY roleName");
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<div class="topbar no-print">
    <h5><i class="bi bi-person-badge me-2" style="color:var(--ev-purple);"></i>User Management</h5>
    <div class="ms-auto"><div class="user-badge"><i class="bi bi-person-circle" style="color:var(--ev-purple);"></i><span><?php echo htmlspecialchars($_SESSION['userName']); ?></span><span class="role-pill"><?php echo htmlspecialchars($_SESSION['roleName']); ?></span></div></div>
</div>

<?php
$alerts=['savedData'=>['success','Saved!','User account created.'],'updatedUser'=>['success','Updated!','User updated.'],'userDeleted'=>['success','Deleted!','User removed.'],'passwordChanged'=>['success','Password Changed','Password updated successfully.'],'emailExists'=>['error','Email Exists','This email is already in use.'],'userNoExists'=>['error','Duplicate ID','User number already exists.'],'emptyFields'=>['warning','Required Fields','Fill in all required fields.']];
foreach($alerts as $k=>[$i,$t,$tx]) if(isset($_GET[$k])) echo "<script>Swal.fire({icon:'$i',title:'$t',text:'$tx',timer:2000}).then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";
?>

<div class="page-body">
    <div class="d-flex justify-content-between mb-3 align-items-center">
        <h5 class="fw-bold mb-0" style="color:#004a38;">System Users</h5>
        <button class="btn btn-ev" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-person-plus me-1"></i>Add User</button>
    </div>

    <div class="card card-shadow">
        <div class="card-body">
            <table id="userTable" class="table table-bordered table-striped text-center">
                <thead style="background:var(--ev-gradient);color:#fff;">
                    <tr><th>User No</th><th>Name</th><th>Email</th><th>Role</th><th>Gender</th><th>Contact</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php
                $users = $conn->query("SELECT u.*, r.roleName FROM users u JOIN role r ON u.roleID=r.roleID WHERE u.dateDeleted IS NULL ORDER BY r.roleName, u.surName");
                while($u=$users->fetch_assoc()):
                    $fullName = trim("{$u['givenName']} {$u['midName']} {$u['surName']} {$u['extName']}");
                ?>
                <tr>
                    <td><small class="text-muted"><?php echo htmlspecialchars($u['userNo']); ?></small></td>
                    <td class="text-start fw-semibold"><?php echo htmlspecialchars($fullName); ?></td>
                    <td class="text-muted small"><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><span class="badge-active"><?php echo htmlspecialchars($u['roleName']); ?></span></td>
                    <td><?php echo htmlspecialchars($u['gender']??'—'); ?></td>
                    <td class="text-muted small"><?php echo htmlspecialchars($u['contactNo']??'—'); ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $u['userID']; ?>"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#pwModal<?php echo $u['userID']; ?>" title="Change Password"><i class="bi bi-key"></i></button>
                        <?php if($u['userID'] != $_SESSION['userID']): ?>
                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal<?php echo $u['userID']; ?>"><i class="bi bi-trash"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Edit User Modal -->
                <div class="modal fade" id="editUserModal<?php echo $u['userID']; ?>" tabindex="-1">
                  <div class="modal-dialog modal-lg"><div class="modal-content">
                    <form method="POST" action="../backend/userAuth.php">
            <?php csrf_field(); ?>
                      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;"><h5 class="modal-title">Edit User</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                      <div class="modal-body">
                        <input type="hidden" name="userID" value="<?php echo $u['userID']; ?>">
                        <div class="row g-2">
                          <div class="col-md-4"><label class="form-label fw-semibold small">Role <span class="text-danger">*</span></label>
                            <select name="roleID" class="form-select" required>
                              <?php $roles->data_seek(0); while($r=$roles->fetch_assoc()): ?>
                              <option value="<?php echo $r['roleID']; ?>" <?php echo $r['roleID']==$u['roleID']?'selected':''; ?>><?php echo htmlspecialchars($r['roleName']); ?></option>
                              <?php endwhile; ?>
                            </select>
                          </div>
                          <div class="col-md-4"><label class="form-label fw-semibold small">User No</label><input type="text" name="userNo" value="<?php echo htmlspecialchars($u['userNo']); ?>" class="form-control"></div>
                          <div class="col-md-4"><label class="form-label fw-semibold small">Email <span class="text-danger">*</span></label><input type="email" name="email" value="<?php echo htmlspecialchars($u['email']); ?>" class="form-control" required></div>
                          <div class="col-md-3"><label class="form-label fw-semibold small">First Name <span class="text-danger">*</span></label><input type="text" name="givenName" value="<?php echo htmlspecialchars($u['givenName']); ?>" class="form-control" required></div>
                          <div class="col-md-3"><label class="form-label fw-semibold small">Middle Name</label><input type="text" name="midName" value="<?php echo htmlspecialchars($u['midName']??''); ?>" class="form-control"></div>
                          <div class="col-md-3"><label class="form-label fw-semibold small">Last Name <span class="text-danger">*</span></label><input type="text" name="surName" value="<?php echo htmlspecialchars($u['surName']); ?>" class="form-control" required></div>
                          <div class="col-md-3"><label class="form-label fw-semibold small">Ext (Jr/Sr)</label><input type="text" name="extName" value="<?php echo htmlspecialchars($u['extName']??''); ?>" class="form-control"></div>
                          <div class="col-md-4"><label class="form-label fw-semibold small">Gender</label>
                            <select name="gender" class="form-select">
                              <option value="Male" <?php echo $u['gender']==='Male'?'selected':''; ?>>Male</option>
                              <option value="Female" <?php echo $u['gender']==='Female'?'selected':''; ?>>Female</option>
                              <option value="Other" <?php echo $u['gender']==='Other'?'selected':''; ?>>Other</option>
                            </select>
                          </div>
                          <div class="col-md-4"><label class="form-label fw-semibold small">Birthdate</label><input type="date" name="birthdate" value="<?php echo $u['birthdate']??''; ?>" class="form-control"></div>
                          <div class="col-md-4"><label class="form-label fw-semibold small">Contact No</label><input type="text" name="contactNo" value="<?php echo htmlspecialchars($u['contactNo']??''); ?>" class="form-control"></div>
                        </div>
                      </div>
                      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="userUpdate" class="btn btn-ev">Update</button></div>
                    </form>
                  </div></div>
                </div>

                <!-- Change Password Modal -->
                <div class="modal fade" id="pwModal<?php echo $u['userID']; ?>" tabindex="-1">
                  <div class="modal-dialog modal-sm"><div class="modal-content">
                    <form method="POST" action="../backend/userAuth.php">
            <?php csrf_field(); ?>
                      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;"><h5 class="modal-title"><i class="bi bi-key me-1"></i>Change Password</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                      <div class="modal-body">
                        <input type="hidden" name="userID" value="<?php echo $u['userID']; ?>">
                        <p class="text-muted small">Setting new password for <strong><?php echo htmlspecialchars($u['givenName']); ?></strong></p>
                        <div class="mb-2"><label class="form-label fw-semibold small">New Password</label><input type="password" name="newPassword" class="form-control" minlength="6" required></div>
                      </div>
                      <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" name="changePassword" class="btn btn-ev btn-sm">Update Password</button></div>
                    </form>
                  </div></div>
                </div>

                <!-- Delete Modal -->
                <div class="modal fade" id="deleteUserModal<?php echo $u['userID']; ?>" tabindex="-1">
                  <div class="modal-dialog modal-sm"><div class="modal-content">
                    <form method="POST" action="../backend/userAuth.php">
            <?php csrf_field(); ?>
                      <div class="modal-header bg-danger text-white"><h5 class="modal-title">Delete User</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                      <div class="modal-body"><input type="hidden" name="userID" value="<?php echo $u['userID']; ?>"><p>Remove <strong><?php echo htmlspecialchars($fullName); ?></strong>?</p></div>
                      <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" name="userDelete" class="btn btn-danger btn-sm">Delete</button></div>
                    </form>
                  </div></div>
                </div>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" action="../backend/userAuth.php">
            <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;"><h5 class="modal-title"><i class="bi bi-person-plus me-1"></i>Add User</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-2">
          <div class="col-md-4"><label class="form-label fw-semibold small">Role <span class="text-danger">*</span></label>
            <select name="roleID" class="form-select" required>
              <option value="">— Select Role —</option>
              <?php $roles->data_seek(0); while($r=$roles->fetch_assoc()): ?>
              <option value="<?php echo $r['roleID']; ?>"><?php echo htmlspecialchars($r['roleName']); ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-4"><label class="form-label fw-semibold small">User No</label><input type="text" name="userNo" class="form-control" placeholder="EMP-001"></div>
          <div class="col-md-4"><label class="form-label fw-semibold small">Email <span class="text-danger">*</span></label><input type="email" name="email" class="form-control" required></div>
          <div class="col-md-3"><label class="form-label fw-semibold small">First Name <span class="text-danger">*</span></label><input type="text" name="givenName" class="form-control" required></div>
          <div class="col-md-3"><label class="form-label fw-semibold small">Middle Name</label><input type="text" name="midName" class="form-control"></div>
          <div class="col-md-3"><label class="form-label fw-semibold small">Last Name <span class="text-danger">*</span></label><input type="text" name="surName" class="form-control" required></div>
          <div class="col-md-3"><label class="form-label fw-semibold small">Ext</label><input type="text" name="extName" class="form-control" placeholder="Jr/Sr/III"></div>
          <div class="col-md-4"><label class="form-label fw-semibold small">Gender</label>
            <select name="gender" class="form-select"><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option></select>
          </div>
          <div class="col-md-4"><label class="form-label fw-semibold small">Birthdate</label><input type="date" name="birthdate" class="form-control"></div>
          <div class="col-md-4"><label class="form-label fw-semibold small">Contact No</label><input type="text" name="contactNo" class="form-control"></div>
          <div class="col-md-6"><label class="form-label fw-semibold small">Password <span class="text-danger">*</span></label><input type="password" name="password" class="form-control" minlength="6" required></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="userSave" class="btn btn-ev">Create Account</button></div>
    </form>
  </div></div>
</div>

<script>$(document).ready(function(){ $('#userTable').DataTable({pageLength:15,order:[[3,'asc'],[1,'asc']]}); });</script>
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
