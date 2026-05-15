<?php
// userModalAuth.php
// Renders per-row Edit, Change Password, and Delete modals for each $u (user row).
// Must be included inside the while($u = $users->fetch_assoc()) loop.
// Requires: $u (current user row array), $roles (MySQLi result, will be rewound).
$fullName = trim("{$u['givenName']} {$u['midName']} {$u['surName']} {$u['extName']}");
?>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal<?php echo $u['userID']; ?>" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" action="../backend/userAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title">Edit User – <?php echo htmlspecialchars($fullName); ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
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
              <option value="Male"   <?php echo $u['gender']==='Male'  ?'selected':''; ?>>Male</option>
              <option value="Female" <?php echo $u['gender']==='Female'?'selected':''; ?>>Female</option>
              <option value="Other"  <?php echo $u['gender']==='Other' ?'selected':''; ?>>Other</option>
            </select>
          </div>
          <div class="col-md-4"><label class="form-label fw-semibold small">Birthdate</label><input type="date" name="birthdate" value="<?php echo $u['birthdate']??''; ?>" class="form-control"></div>
          <div class="col-md-4"><label class="form-label fw-semibold small">Contact No</label><input type="text" name="contactNo" value="<?php echo htmlspecialchars($u['contactNo']??''); ?>" class="form-control"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="userUpdate" class="btn btn-ev">Update</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="pwModal<?php echo $u['userID']; ?>" tabindex="-1">
  <div class="modal-dialog modal-sm"><div class="modal-content">
    <form method="POST" action="../backend/userAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title"><i class="bi bi-key me-1"></i>Change Password</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="userID" value="<?php echo $u['userID']; ?>">
        <p class="text-muted small">Setting new password for <strong><?php echo htmlspecialchars($u['givenName']); ?></strong></p>
        <div class="mb-2"><label class="form-label fw-semibold small">New Password</label><input type="password" name="newPassword" class="form-control" minlength="6" required></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="changePassword" class="btn btn-ev btn-sm">Update Password</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal<?php echo $u['userID']; ?>" tabindex="-1">
  <div class="modal-dialog modal-sm"><div class="modal-content">
    <form method="POST" action="../backend/userAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Delete User</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="userID" value="<?php echo $u['userID']; ?>">
        <p>Remove <strong><?php echo htmlspecialchars($fullName); ?></strong>? This cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="userDelete" class="btn btn-danger btn-sm">Delete</button>
      </div>
    </form>
  </div></div>
</div>
