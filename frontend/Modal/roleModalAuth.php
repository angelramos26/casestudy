<?php
// roleModalAuth.php
// Renders per-row Edit and Delete modals for each $r (role row).
// Must be included inside the while($r = $roles->fetch_assoc()) loop.
?>

<!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal<?php echo $r['roleID']; ?>" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/roleAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title">Edit Role – <?php echo htmlspecialchars($r['roleName']); ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="roleID" value="<?php echo $r['roleID']; ?>">
        <div class="mb-3"><label class="form-label fw-semibold small">Role Name <span class="text-danger">*</span></label><input type="text" name="roleName" value="<?php echo htmlspecialchars($r['roleName']); ?>" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-semibold small">Description</label><input type="text" name="roleDesc" value="<?php echo htmlspecialchars($r['roleDesc']??''); ?>" class="form-control"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="roleUpdate" class="btn btn-ev">Update</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Delete Role Modal -->
<div class="modal fade" id="deleteRoleModal<?php echo $r['roleID']; ?>" tabindex="-1">
  <div class="modal-dialog modal-sm"><div class="modal-content">
    <form method="POST" action="../backend/roleAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Delete Role</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="roleID" value="<?php echo $r['roleID']; ?>">
        <p>Delete role <strong><?php echo htmlspecialchars($r['roleName']); ?></strong>? This cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="roleDelete" class="btn btn-danger btn-sm">Delete</button>
      </div>
    </form>
  </div></div>
</div>
