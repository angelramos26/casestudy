<?php
// userAddModal.php
// The "Add User" modal rendered once outside the data loop.
// Requires: $roles (MySQLi result, will be rewound).
?>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" action="../backend/userAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title"><i class="bi bi-person-plus me-1"></i>Add User</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
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
            <select name="gender" class="form-select">
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="col-md-4"><label class="form-label fw-semibold small">Birthdate</label><input type="date" name="birthdate" class="form-control"></div>
          <div class="col-md-4"><label class="form-label fw-semibold small">Contact No</label><input type="text" name="contactNo" class="form-control"></div>
          <div class="col-md-6"><label class="form-label fw-semibold small">Password <span class="text-danger">*</span></label><input type="password" name="password" class="form-control" minlength="6" required></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="userSave" class="btn btn-ev">Create Account</button>
      </div>
    </form>
  </div></div>
</div>
