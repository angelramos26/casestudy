<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';

if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if($_SESSION['roleName'] !== 'Admin'){ header("Location: dashboard.php"); exit(); }
$pageTitle = "Role Management – Restaurant POS";
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<style>

/* ── CSS bridge for legacy brand-* variables ── */
:root {
    --brand-navy:     var(--charcoal);
    --brand-yellow:   var(--orange);
    --brand-yellow-d: var(--orange-dark);
    --brand-mint:     var(--s-bg);
    --card-bg:        var(--c-white);
    --text-main:      var(--t-main);
    --border:         var(--s-border);
    --shadow-card:    var(--sh-sm);
    --shadow-btn:     0 2px 6px rgba(26,26,26,0.15);
}

/* ── Page ─────────────────────────────────── */
.rm-page { padding: 28px 32px; background: var(--brand-mint); min-height: 100vh; }

.user-chip {
  display: flex; align-items: center; gap: 8px;
  background: #fff; border: 1.5px solid var(--border);
  border-radius: 50px; padding: 6px 14px 6px 8px;
}
.user-chip i { font-size: 1.1rem; color: var(--brand-navy); }
.user-chip .uname { font-size: .82rem; font-weight: 600; color: var(--text-main); }
.user-chip .role-tag {
  background: var(--brand-yellow); color: var(--brand-navy);
  font-size: .68rem; font-weight: 700; border-radius: 50px;
  padding: 2px 9px; text-transform: uppercase; letter-spacing: .04em;
}

/* ── Two-column layout ────────────────────── */
.rm-grid { display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }

/* ── Shared card ──────────────────────────── */
.rm-card {
  background: var(--card-bg); border-radius: var(--r-lg);
  box-shadow: var(--shadow-card); border: 1.5px solid var(--border);
  overflow: hidden;
}
.rm-card-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 18px 22px; border-bottom: 1.5px solid var(--border);
  flex-wrap: wrap; gap: 10px;
}
.rm-card-header-title {
  display: flex; align-items: center; gap: 8px;
  font-size: .95rem; font-weight: 700; color: var(--brand-navy);
}
.rm-card-header-title i { color: var(--brand-yellow); }

/* ── Add Role button ──────────────────────── */
.btn-add {
  display: inline-flex; align-items: center; gap: 7px;
  background: var(--charcoal); color: var(--orange);
  font-weight: 700; font-size: .84rem; border: none;
  border-radius: var(--r-sm); padding: 9px 18px; cursor: pointer;
  box-shadow: var(--shadow-btn); transition: background .15s, transform .1s;
}
.btn-add:hover { background: var(--charcoal-mid); color: var(--orange); transform: translateY(-1px); }

/* ── Roles table ──────────────────────────── */
#roleTable { width: 100%; border-collapse: collapse; margin: 0; }
#roleTable thead th {
  background: var(--brand-navy); color: #fff;
  font-size: .72rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: .06em; padding: 12px 16px; border: none; white-space: nowrap;
}
#roleTable tbody tr { border-bottom: 1px solid #f0f0f5; transition: background .12s; }
#roleTable tbody tr:hover { background: #f8f8fc; }
#roleTable tbody tr:last-child { border-bottom: none; }
#roleTable tbody td { padding: 13px 16px; font-size: .875rem; color: var(--text-main); vertical-align: middle; border: none; }

/* ── Role name pill ───────────────────────── */
.role-name-pill {
  display: inline-flex; align-items: center; gap: 7px;
}
.role-dot {
  width: 9px; height: 9px; border-radius: 50%;
  background: var(--brand-yellow); flex-shrink: 0;
  box-shadow: 0 0 0 2px rgba(239,130,13,.3);
}
.role-name-text { font-weight: 700; color: var(--brand-navy); }

/* ── User count badge ─────────────────────── */
.user-count-badge {
  display: inline-flex; align-items: center; gap: 5px;
  background: var(--brand-mint); color: var(--brand-navy);
  border-radius: 50px; padding: 4px 12px; font-size: .78rem; font-weight: 700;
  border: 1px solid var(--border);
}

/* ── Action buttons ───────────────────────── */
.action-group { display: flex; gap: 6px; justify-content: center; }
.btn-action {
  width: 32px; height: 32px; border-radius: var(--r-sm);
  border: none; cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: .88rem; transition: opacity .15s, transform .1s;
}
.btn-action:hover { opacity: .82; transform: scale(1.08); }
.btn-edit { background: #ebf4ff; color: var(--info); }
.btn-del  { background: #fff5f5; color: var(--c-danger); }

/* ── DataTable overrides ──────────────────── */
div.dataTables_wrapper div.dataTables_filter label,
div.dataTables_wrapper div.dataTables_length label { color: var(--t-muted); font-size: .83rem; }
div.dataTables_wrapper div.dataTables_filter input,
div.dataTables_wrapper div.dataTables_length select {
  border: 1.5px solid var(--border); border-radius: var(--r-sm);
  padding: 5px 10px; font-size: .83rem; color: var(--text-main);
  background: var(--card-bg); outline: none;
}
div.dataTables_wrapper div.dataTables_filter input:focus { border-color: var(--orange); }
div.dataTables_wrapper div.dataTables_paginate .paginate_button {
  border-radius: var(--r-sm) !important; font-size: .8rem !important;
  border: 1.5px solid var(--border) !important;
  background: var(--card-bg) !important; color: var(--t-mid) !important;
  margin: 0 2px !important;
}
div.dataTables_wrapper div.dataTables_paginate .paginate_button.current,
div.dataTables_wrapper div.dataTables_paginate .paginate_button.current:hover {
  background: var(--charcoal) !important; color: var(--orange) !important;
  border-color: var(--charcoal) !important; font-weight: 700 !important;
}
div.dataTables_wrapper div.dataTables_paginate .paginate_button:hover:not(.current):not(.disabled) {
  background: var(--orange-soft) !important; color: var(--orange-dark) !important;
  border-color: var(--orange) !important;
}
div.dataTables_wrapper div.dataTables_paginate .paginate_button.disabled,
div.dataTables_wrapper div.dataTables_paginate .paginate_button.disabled:hover {
  background: transparent !important; color: var(--t-muted) !important;
  border-color: var(--border) !important; opacity: .4 !important;
}
div.dataTables_wrapper .dataTables_info { color: var(--t-muted); font-size: .8rem; }
.dt-controls { padding: 14px 22px 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }

/* ── Access Matrix (right panel) ─────────── */
.matrix-module {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 20px; border-bottom: 1px solid #f0f0f5;
  transition: background .12s;
}
.matrix-module:hover { background: #f8f8fc; }
.matrix-module:last-child { border-bottom: none; }
.module-name { font-size: .82rem; font-weight: 600; color: var(--text-main); flex: 1; }
.matrix-checks { display: flex; gap: 18px; align-items: center; }
.matrix-role-label {
  font-size: .65rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .06em; color: var(--t-muted); text-align: center; min-width: 42px;
}
.check-yes { color: var(--c-success); font-size: 1rem; }
.check-no  { color: #d0cfe0; font-size: 1rem; }

.matrix-legend {
  display: flex; align-items: center; justify-content: flex-end;
  gap: 12px; padding: 10px 20px 14px;
  border-top: 1.5px solid var(--border); background: #fafafa;
}
.legend-item { display: flex; align-items: center; gap: 5px; font-size: .72rem; color: var(--t-muted); font-weight: 500; }

/* ── Matrix column headers ────────────────── */
.matrix-col-headers {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 20px 8px; border-bottom: 1.5px solid var(--border);
  background: #f8f8fc;
}
.matrix-col-headers .module-name { font-size: .7rem; font-weight: 700; color: var(--t-muted); text-transform: uppercase; letter-spacing: .05em; }

/* ── Modal ────────────────────────────────── */
.modal-content { border-radius: var(--r-lg); border: none; box-shadow: 0 8px 40px rgba(26,26,26,.18); overflow: hidden; }
.modal-header-brand { background: var(--brand-navy); color: #fff; padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; }
.modal-header-brand .modal-title { font-size: 1rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px; }
.modal-header-brand .btn-close { filter: brightness(0) invert(1); opacity: .7; }
.modal-header-danger { background: var(--c-danger); color: #fff; padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; }
.modal-header-danger .modal-title { font-size: 1rem; font-weight: 700; margin: 0; }
.modal-header-danger .btn-close { filter: brightness(0) invert(1); opacity: .7; }
.modal-body  { padding: 22px 24px; }
.modal-footer{ padding: 14px 24px; border-top: 1.5px solid var(--border); background: #fafafa; }
.form-label-styled { font-size: .75rem; font-weight: 600; color: var(--t-muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 5px; display: block; }
.form-control-styled {
  border: 1.5px solid var(--border); border-radius: var(--r-sm);
  padding: 9px 13px; font-size: .88rem; color: var(--text-main);
  transition: border-color .15s, box-shadow .15s; width: 100%;
}
.form-control-styled:focus { outline: none; border-color: var(--brand-yellow); box-shadow: 0 0 0 3px rgba(239,130,13,.2); }
.btn-submit {
  background: var(--charcoal); color: var(--orange);
  font-weight: 700; border: none; border-radius: var(--r-sm);
  padding: 9px 22px; font-size: .88rem; cursor: pointer; transition: background .15s;
}
.btn-submit:hover { background: var(--charcoal-mid); color: var(--orange); }
.btn-cancel-m {
  background: transparent; color: var(--t-muted);
  font-weight: 600; border: 1.5px solid var(--border);
  border-radius: var(--r-sm); padding: 9px 18px; font-size: .88rem; cursor: pointer;
  transition: border-color .15s;
}
.btn-cancel-m:hover { border-color: #aaa; color: var(--text-main); }
.btn-danger-solid {
  background: var(--c-danger); color: #fff; font-weight: 700;
  border: none; border-radius: var(--r-sm);
  padding: 9px 20px; font-size: .88rem; cursor: pointer; transition: opacity .15s;
}
.btn-danger-solid:hover { opacity: .88; }

@media(max-width:960px){ .rm-grid { grid-template-columns: 1fr; } }
@media(max-width:480px){ .rm-page { padding: 14px; } }
</style>

<?php
$alerts=['savedData'=>['success','Saved!','Role created.'],'updatedRole'=>['success','Updated!','Role updated.'],'roleDeleted'=>['success','Deleted!','Role removed.'],'nameDuplicate'=>['error','Duplicate Name','This role name already exists.'],'emptyFields'=>['warning','Required Fields','Fill in role name.']];
foreach($alerts as $k=>[$i,$t,$tx]) if(isset($_GET[$k])) echo "<script>Swal.fire({icon:'$i',title:'$t',text:'$tx',timer:2000}).then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";

// Columns: Admin | Owner | Cashier | Kitchen Staff
$access = [
  'Dashboard'        => [true,  true,  true,  true ],
  'POS / Take Order' => [true,  false, true,  false],
  'Table Map'        => [true,  false, true,  false],
  'Kitchen Display'  => [true,  false, true,  true ],
  'Menu Items'       => [true,  false, false, false],
  'Categories'       => [true,  false, false, false],
  'Order History'    => [true,  false, true,  false],
  'Sales Records'    => [true,  true,  false, false],
  'Expenses'         => [true,  true,  false, false],
  'Reports'          => [true,  true,  false, false],
  'User Management'  => [true,  false, false, false],
  'Role Management'  => [true,  false, false, false],
  'System Settings'  => [true,  false, false, false],
  'My Profile'       => [true,  true,  true,  true ],
];

$roles = $conn->query("SELECT r.*, COUNT(u.userID) AS userCount FROM role r LEFT JOIN users u ON r.roleID=u.roleID AND u.dateDeleted IS NULL WHERE r.dateDeleted IS NULL GROUP BY r.roleID ORDER BY r.roleName");
$roleRows = [];
while($r=$roles->fetch_assoc()) $roleRows[] = $r;
?>

<?php
  $topbarTitle = 'Role Management';
  $topbarIcon  = 'bi-shield-fill-check';
  $topbarSub   = 'Define system roles and control module access';
  include 'topbar.php';
  ?>
<div class="rm-page no-print">

  <!-- TWO-COLUMN GRID -->
  <div class="rm-grid">

    <!-- LEFT: Roles Table -->
    <div class="rm-card">
      <div class="rm-card-header">
        <div class="rm-card-header-title">
          <i class="bi bi-table"></i> System Roles
        </div>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addRoleModal">
          <i class="bi bi-shield-plus"></i> Add Role
        </button>
      </div>

      <div class="dt-controls" id="dt-search-area"></div>

      <div style="overflow-x:auto;">
        <table id="roleTable">
          <thead>
            <tr>
              <th>Role Name</th>
              <th>Description</th>
              <th style="text-align:center;">Users</th>
              <th style="text-align:center;">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($roleRows as $r): ?>
          <tr>
            <td>
              <div class="role-name-pill">
                <span class="role-dot"></span>
                <span class="role-name-text"><?php echo htmlspecialchars($r['roleName']); ?></span>
              </div>
            </td>
            <td style="font-size:.83rem; color:var(--t-muted);"><?php echo htmlspecialchars($r['roleDesc'] ?? '—'); ?></td>
            <td style="text-align:center;">
              <span class="user-count-badge">
                <i class="bi bi-people-fill" style="font-size:.72rem;"></i>
                <?php echo $r['userCount']; ?>
              </span>
            </td>
            <td>
              <div class="action-group">
                <button class="btn-action btn-edit"
                  data-bs-toggle="modal"
                  data-bs-target="#editRoleModal<?php echo $r['roleID']; ?>"
                  title="Edit Role">
                  <i class="bi bi-pencil-fill"></i>
                </button>
                <?php if($r['userCount'] == 0): ?>
                <button class="btn-action btn-del"
                  data-bs-toggle="modal"
                  data-bs-target="#deleteRoleModal<?php echo $r['roleID']; ?>"
                  title="Delete Role">
                  <i class="bi bi-trash-fill"></i>
                </button>
                <?php else: ?>
                <button class="btn-action btn-del" disabled title="Role in use" style="opacity:.3; cursor:not-allowed;">
                  <i class="bi bi-trash-fill"></i>
                </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div><!-- /left -->

    <!-- RIGHT: Access Matrix -->
    <div class="rm-card">
      <div class="rm-card-header" style="border-bottom: 1.5px solid var(--border);">
        <div class="rm-card-header-title">
          <i class="bi bi-grid-3x3-gap-fill"></i> Access Matrix
        </div>
      </div>

      <!-- Column headers -->
      <div class="matrix-col-headers">
        <span class="module-name">Module</span>
        <div class="matrix-checks">
          <span class="matrix-role-label">Admin</span>
          <span class="matrix-role-label">Owner</span>
          <span class="matrix-role-label">Cashier</span>
          <span class="matrix-role-label">Kitchen</span>
        </div>
      </div>

      <!-- Rows -->
      <?php foreach($access as $module => $perms): ?>
      <div class="matrix-module">
        <span class="module-name"><?php echo $module; ?></span>
        <div class="matrix-checks">
          <?php foreach($perms as $p): ?>
          <div style="min-width:42px; text-align:center;">
            <?php if($p): ?>
              <i class="bi bi-check-circle-fill check-yes"></i>
            <?php else: ?>
              <i class="bi bi-dash-circle check-no"></i>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- Legend -->
      <div class="matrix-legend">
        <div class="legend-item"><i class="bi bi-check-circle-fill" style="color:var(--c-success);"></i> Has Access</div>
        <div class="legend-item"><i class="bi bi-dash-circle" style="color:#d0cfe0;"></i> No Access</div>
      </div>
    </div><!-- /right -->

  </div><!-- /rm-grid -->

</div><!-- /rm-page -->


<!-- ═════════════════════ MODALS ═════════════════════ -->

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="../backend/roleAuth.php">
        <?php csrf_field(); ?>
        <div class="modal-header-brand">
          <span class="modal-title"><i class="bi bi-shield-plus"></i> Add Role</span>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label-styled">Role Name <span style="color:var(--c-danger);">*</span></label>
            <input type="text" name="roleName" class="form-control-styled" required placeholder="e.g. Supervisor">
          </div>
          <div class="mb-0">
            <label class="form-label-styled">Description</label>
            <input type="text" name="roleDesc" class="form-control-styled" placeholder="e.g. Can only process sales">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel-m" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="roleSave" class="btn-submit">Save Role</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit & Delete Modals (per role) -->
<?php foreach($roleRows as $r): ?>

<!-- Edit -->
<div class="modal fade" id="editRoleModal<?php echo $r['roleID']; ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="../backend/roleAuth.php">
        <?php csrf_field(); ?>
        <div class="modal-header-brand">
          <span class="modal-title"><i class="bi bi-pencil-fill"></i> Edit Role</span>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="roleID" value="<?php echo $r['roleID']; ?>">
          <div class="mb-3">
            <label class="form-label-styled">Role Name <span style="color:var(--c-danger);">*</span></label>
            <input type="text" name="roleName" value="<?php echo htmlspecialchars($r['roleName']); ?>" class="form-control-styled" required>
          </div>
          <div class="mb-0">
            <label class="form-label-styled">Description</label>
            <input type="text" name="roleDesc" value="<?php echo htmlspecialchars($r['roleDesc'] ?? ''); ?>" class="form-control-styled">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel-m" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="roleUpdate" class="btn-submit">Update Role</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete -->
<?php if($r['userCount'] == 0): ?>
<div class="modal fade" id="deleteRoleModal<?php echo $r['roleID']; ?>" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="../backend/roleAuth.php">
        <?php csrf_field(); ?>
        <div class="modal-header-danger">
          <span class="modal-title"><i class="bi bi-exclamation-triangle-fill me-1"></i> Delete Role</span>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" style="text-align:center; padding:28px 24px;">
          <input type="hidden" name="roleID" value="<?php echo $r['roleID']; ?>">
          <div style="width:52px;height:52px;border-radius:50%;background:#fff5f5;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:1.5rem;color:var(--c-danger);">
            <i class="bi bi-shield-x"></i>
          </div>
          <p style="font-weight:700;color:var(--text-main);margin-bottom:6px;">Delete <strong><?php echo htmlspecialchars($r['roleName']); ?></strong>?</p>
          <p style="font-size:.82rem;color:var(--t-muted);margin:0;">This action cannot be undone.</p>
        </div>
        <div class="modal-footer" style="justify-content:center; gap:10px;">
          <button type="button" class="btn-cancel-m" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="roleDelete" class="btn-danger-solid">Yes, Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php endforeach; ?>

<script>
$(document).ready(function(){
  $('#roleTable').DataTable({
    pageLength: 10,
    order: [[0,'asc']],
    dom: '<"dt-wrap"lf>rtip',
    language: {
      search: '',
      searchPlaceholder: '🔍  Search roles…',
      lengthMenu: 'Show _MENU_ entries',
      info: 'Showing _START_–_END_ of _TOTAL_ roles',
      paginate: { previous: '‹', next: '›' }
    }
  });
  $('.dataTables_filter').appendTo('#dt-search-area');
  $('.dataTables_length').appendTo('#dt-search-area');
});
</script>

<!-- ── Pusher Real-time ─────────────────────────────────────────────────── -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
  const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
  const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>
<?php include 'footer.php'; ?>
</body></html>