<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';

session_start();
if (!isset($_SESSION['userID'])) { header("Location: ../index.php"); exit(); }
if ($_SESSION['roleName'] !== 'Admin') { header("Location: dashboard.php"); exit(); }

$pageTitle = "User Management – 7Evelyn POS";
$roles = $conn->query("SELECT * FROM role WHERE dateDeleted IS NULL ORDER BY roleName");
$users = $conn->query(
    "SELECT u.*, r.roleName FROM users u
     JOIN role r ON u.roleID = r.roleID
     WHERE u.dateDeleted IS NULL
     ORDER BY r.roleName, u.surName"
);
$userRows = $users->fetch_all(MYSQLI_ASSOC);
$totalUsers  = count($userRows);
$adminCount  = count(array_filter($userRows, fn($u) => $u['roleName'] === 'Admin'));
$activeCount = $totalUsers;
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<style>

/* ── Reset page body ───────────────────────────────────────── */
.page-body { padding: 0; background: var(--off-white); min-height: 100vh; }

/* ══════════════════════════════════
   TOPBAR
══════════════════════════════════ */






.um-breadcrumb {
    display: flex; align-items: center; gap: 6px;
    font-size: 12px; color: var(--navy-light);
}
.um-breadcrumb span { color: var(--gold); font-weight: 700; }
.um-user-pill {
    margin-left: auto;
    display: flex; align-items: center; gap: 10px;
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 40px;
    padding: 6px 14px 6px 8px;
}
.um-user-avatar {
    width: 30px; height: 30px; border-radius: 50%;
    background: var(--gold);
    color: var(--navy);
    font-size: 11px; font-weight: 900;
    display: flex; align-items: center; justify-content: center;
}
.um-user-name { font-size: 13px; font-weight: 700; color: var(--white); }
.um-user-role { font-size: 10.5px; color: var(--gold); font-weight: 700; letter-spacing: .4px; }

/* ══════════════════════════════════
   MAIN CONTENT WRAPPER
══════════════════════════════════ */
.um-wrapper {
    max-width: 1340px;
    margin: 0 auto;
    padding: 32px 32px 56px;
}

/* ══════════════════════════════════
   PAGE HEADER
══════════════════════════════════ */
.um-page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-bottom: 28px;
    gap: 16px;
    flex-wrap: wrap;
}
.um-page-header h1 {
    font-size: 1.75rem;
    font-weight: 900;
    color: var(--navy);
    margin: 0 0 4px;
    letter-spacing: -.4px;
}
.um-page-header p {
    font-size: 13.5px;
    color: var(--text-muted);
    margin: 0;
}
.btn-add-user {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--navy);
    color: var(--gold);
    border: none;
    border-radius: var(--radius-md);
    padding: 11px 22px;
    font-size: 13.5px;
    font-weight: 800;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: var(--shadow-md);
    white-space: nowrap;
    letter-spacing: .2px;
}
.btn-add-user:hover {
    background: var(--navy-mid);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
    color: var(--gold);
}
.btn-add-user i { font-size: .95rem; }

/* ══════════════════════════════════
   STAT STRIP
══════════════════════════════════ */
.stat-strip {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.stat-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: 22px 24px;
    display: flex;
    align-items: center;
    gap: 18px;
    box-shadow: var(--shadow-sm);
    border: 1.5px solid transparent;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}
.stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}
.stat-card.gold::before  { background: var(--gold); }
.stat-card.navy::before  { background: var(--navy); }
.stat-card.frost::before { background: var(--frost-mid); }
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
    border-color: rgba(38,35,65,.06);
}
.stat-icon {
    width: 54px; height: 54px;
    border-radius: var(--radius-md);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}
.stat-icon.gold  { background: var(--gold-light); color: var(--gold-dark); }
.stat-icon.navy  { background: rgba(38,35,65,.08); color: var(--navy); }
.stat-icon.frost { background: var(--frost); color: #2D8A8A; }
.stat-value {
    font-size: 2rem;
    font-weight: 900;
    color: var(--navy);
    line-height: 1;
    letter-spacing: -1px;
}
.stat-label {
    font-size: 12px;
    color: var(--text-muted);
    font-weight: 600;
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: .5px;
}

/* ══════════════════════════════════
   TABLE CARD
══════════════════════════════════ */
.table-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    border: 1.5px solid rgba(38,35,65,.05);
    overflow: hidden;
}
.table-card-header {
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    border-bottom: 1.5px solid var(--frost);
    background: var(--white);
}
.table-card-title {
    font-size: 1rem;
    font-weight: 900;
    color: var(--navy);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.table-card-title .title-dot {
    width: 10px; height: 10px;
    background: var(--gold);
    border-radius: 50%;
    flex-shrink: 0;
}
.table-card-tools {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Search */
.um-search {
    position: relative;
    width: 260px;
}
.um-search input {
    width: 100%;
    padding: 9px 14px 9px 38px;
    border: 1.5px solid var(--frost-mid);
    border-radius: 10px;
    font-size: 13px;
    background: var(--off-white);
    color: var(--navy);
    outline: none;
    transition: var(--transition);
}
.um-search input:focus {
    border-color: var(--navy);
    background: var(--white);
    box-shadow: 0 0 0 3px rgba(38,35,65,.08);
}
.um-search i {
    position: absolute;
    left: 12px; top: 50%;
    transform: translateY(-50%);
    color: var(--navy-light);
    font-size: .85rem;
}
.um-search input:focus ~ i, .um-search:focus-within i { color: var(--navy); }

/* ── Table itself ──────────────────────────────────────── */
#userTable { width: 100%; margin: 0; border-collapse: collapse; }

#userTable thead tr th {
    background: var(--off-white);
    color: var(--navy-light);
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .8px;
    padding: 12px 18px;
    border: none;
    border-bottom: 1.5px solid var(--frost);
    white-space: nowrap;
}
#userTable thead tr th:first-child { padding-left: 24px; }
#userTable thead tr th:last-child  { text-align: center; }

#userTable tbody tr {
    border-bottom: 1px solid rgba(231,245,245,.7);
    transition: background var(--transition);
}
#userTable tbody tr:hover { background: var(--frost); }
#userTable tbody tr:last-child { border-bottom: none; }

#userTable tbody td {
    padding: 14px 18px;
    border: none;
    vertical-align: middle;
    font-size: 13.5px;
    color: var(--text-main);
}
#userTable tbody td:first-child { padding-left: 24px; }

/* Avatar */
.user-avatar {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 12.5px; font-weight: 900;
    flex-shrink: 0;
    letter-spacing: .3px;
}
.user-name { font-weight: 700; color: var(--navy); line-height: 1.3; }
.user-handle { font-size: 11.5px; color: var(--text-muted); }

/* Role chip */
.role-chip {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11.5px; font-weight: 800;
    letter-spacing: .2px;
}
.role-chip.admin   { background: rgba(38,35,65,.09); color: var(--navy); }
.role-chip.cashier { background: var(--warn-lt); color: var(--warn); }
.role-chip.other   { background: var(--frost); color: #2D8A8A; }

/* User No badge */
.userno-badge {
    display: inline-block;
    background: var(--gold-light);
    color: var(--gold-dark);
    font-size: 11.5px; font-weight: 800;
    padding: 3px 10px;
    border-radius: 6px;
    font-family: monospace;
    letter-spacing: .5px;
}

/* Action buttons */
.action-group { display: flex; gap: 6px; align-items: center; justify-content: center; }
.btn-action {
    width: 32px; height: 32px;
    border-radius: 8px;
    display: inline-flex; align-items: center; justify-content: center;
    border: none; cursor: pointer;
    font-size: .8rem;
    transition: var(--transition);
    flex-shrink: 0;
}
.btn-action.edit  { background: rgba(38,35,65,.07); color: var(--navy); }
.btn-action.key   { background: var(--warn-lt); color: var(--warn); }
.btn-action.del   { background: var(--danger-lt); color: var(--danger); }
.btn-action.edit:hover  { background: var(--navy); color: var(--gold); transform: scale(1.1); }
.btn-action.key:hover   { background: var(--warn); color: #fff; transform: scale(1.1); }
.btn-action.del:hover   { background: var(--danger); color: #fff; transform: scale(1.1); }
.btn-action:disabled, .btn-action[disabled] { opacity: .3; cursor: not-allowed; transform: none !important; }

/* Empty state */
.empty-state {
    text-align: center; padding: 64px 24px;
    color: var(--navy-light);
}
.empty-state i { font-size: 2.8rem; display: block; margin-bottom: 14px; opacity: .35; }
.empty-state p { font-size: 14px; margin: 0; }

/* DataTables pagination override */
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_length { display: none; }
.dataTables_wrapper .dataTables_info { padding: 14px 24px; font-size: 12.5px; color: var(--text-muted); }
.dataTables_wrapper .dataTables_paginate { padding: 12px 20px; }
.dataTables_wrapper .paginate_button {
    border-radius: 8px !important;
    font-size: 12.5px !important;
    font-weight: 700 !important;
    color: var(--navy) !important;
    border: none !important;
    padding: 5px 10px !important;
}
.dataTables_wrapper .paginate_button.current {
    background: var(--navy) !important;
    color: var(--gold) !important;
    border: none !important;
}
.dataTables_wrapper .paginate_button:hover:not(.current) {
    background: var(--frost) !important;
    color: var(--navy) !important;
}
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_paginate {
    border-top: 1.5px solid var(--frost);
}
.dataTables_wrapper .dt-layout-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* ══════════════════════════════════
   MODALS
══════════════════════════════════ */
.modal-content {
    border: none;
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-lg);
}
.um-modal-header {
    background: var(--navy);
    padding: 20px 24px;
    display: flex; align-items: center; justify-content: space-between;
}
.um-modal-header h5 {
    margin: 0;
    font-weight: 800;
    font-size: .95rem;
    color: var(--white);
    display: flex; align-items: center; gap: 10px;
}
.um-modal-header h5 i { color: var(--gold); }
.um-modal-header .btn-close { filter: brightness(0) invert(1); opacity: .7; }
.um-modal-header .btn-close:hover { opacity: 1; }

.um-modal-danger-header {
    background: linear-gradient(135deg, #c0392b, var(--danger));
    padding: 20px 24px;
    display: flex; align-items: center; justify-content: space-between;
}
.um-modal-danger-header h5 {
    margin: 0; color: #fff; font-weight: 800; font-size: .95rem;
    display: flex; align-items: center; gap: 10px;
}
.um-modal-danger-header .btn-close { filter: brightness(0) invert(1); opacity: .7; }

.um-modal-warn-header {
    background: linear-gradient(135deg, #d4691a, var(--warn));
    padding: 20px 24px;
    display: flex; align-items: center; justify-content: space-between;
}
.um-modal-warn-header h5 {
    margin: 0; color: #fff; font-weight: 800; font-size: .95rem;
    display: flex; align-items: center; gap: 10px;
}
.um-modal-warn-header .btn-close { filter: brightness(0) invert(1); opacity: .7; }

.modal-body { padding: 24px 26px; }
.modal-footer {
    padding: 16px 24px;
    border-top: 1.5px solid var(--frost);
    background: var(--off-white);
    gap: 10px;
}

/* Form elements in modals */
.um-section-label {
    font-size: 10.5px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--navy);
    border-bottom: 2px solid var(--gold);
    padding-bottom: 6px;
    margin: 20px 0 14px;
    display: flex; align-items: center; gap: 8px;
}
.um-section-label:first-child { margin-top: 0; }
.um-section-label::before {
    content: '';
    width: 4px; height: 14px;
    background: var(--gold);
    border-radius: 2px;
    flex-shrink: 0;
}
.form-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--navy-light);
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: .4px;
}
.required-star { color: var(--danger); margin-left: 2px; }
.form-control, .form-select {
    border: 1.5px solid var(--frost-mid);
    border-radius: var(--radius-sm);
    padding: 9px 13px;
    font-size: 13.5px;
    color: var(--navy);
    background: var(--off-white);
    transition: var(--transition);
}
.form-control:focus, .form-select:focus {
    border-color: var(--navy);
    background: var(--white);
    box-shadow: 0 0 0 3px rgba(38,35,65,.09);
    outline: none;
    color: var(--navy);
}
.info-note {
    background: var(--frost);
    border-left: 3px solid var(--frost-mid);
    border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
    padding: 10px 14px;
    font-size: 12.5px;
    color: #2D8A8A;
}
.info-note i { margin-right: 5px; }

/* Buttons */
.btn-ev {
    background: var(--navy);
    color: var(--gold);
    border: none;
    border-radius: var(--radius-sm);
    padding: 9px 18px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex; align-items: center; gap: 7px;
}
.btn-ev:hover {
    background: var(--navy-mid);
    color: var(--gold);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}
.btn-warn {
    background: var(--warn);
    color: #fff;
    border: none;
    border-radius: var(--radius-sm);
    padding: 9px 18px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex; align-items: center; gap: 7px;
}
.btn-warn:hover { background: #d4691a; color: #fff; }
.btn-secondary {
    background: transparent;
    color: var(--navy-light);
    border: 1.5px solid var(--frost-mid);
    border-radius: var(--radius-sm);
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: var(--transition);
}
.btn-secondary:hover {
    background: var(--frost);
    color: var(--navy);
    border-color: var(--frost-mid);
}

/* ── Toast ──────────────────────────────────────────────── */
.ev-toast {
    position: fixed; top: 20px; right: 24px; z-index: 9999;
    background: var(--navy);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-lg);
    padding: 14px 18px;
    display: flex; align-items: center; gap: 12px;
    min-width: 280px; max-width: 360px;
    transform: translateX(120%);
    transition: transform .35s cubic-bezier(.34,1.56,.64,1);
    border-left: 4px solid var(--gold);
}
.ev-toast.show { transform: translateX(0); }
.ev-toast-icon { font-size: 1.2rem; color: var(--gold); flex-shrink: 0; }
.ev-toast-msg  { font-size: 13.5px; font-weight: 700; color: var(--white); }
.ev-toast-sub  { font-size: 12px; color: rgba(255,255,255,.55); }
</style>

<!-- ═══════════════════════════════════════════════
     TOPBAR
═══════════════════════════════════════════════ -->
<?php
$topbarTitle = 'User Management';
$topbarIcon  = 'bi-person-badge-fill';
$topbarSub   = 'Create and manage system user accounts';
include 'topbar.php';
?>

<!-- ── Alert JS triggers ─────────────────────────────────────── -->
<?php
$alerts = [
    'savedData'       => ['success', 'User Created',         'New user account has been added.'],
    'updatedUser'     => ['success', 'User Updated',         'User information has been saved.'],
    'userDeleted'     => ['success', 'User Removed',         'The user account has been deleted.'],
    'passwordChanged' => ['success', 'Password Updated',     'The password has been changed.'],
    'emailExists'     => ['error',   'Email Already Exists', 'That email is already registered.'],
    'userNoExists'    => ['error',   'Duplicate User No',    'User number is already in use.'],
    'emptyFields'     => ['warning', 'Required Fields',      'Please fill in all required fields.'],
];
foreach ($alerts as $k => [$icon, $title, $text]) {
    if (isset($_GET[$k])) {
        echo "<script>Swal.fire({icon:'$icon',title:'$title',text:'$text',timer:2500,showConfirmButton:false})
              .then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";
    }
}
?>

<!-- ═══════════════════════════════════════════════
     MAIN WRAPPER
═══════════════════════════════════════════════ -->
<div class="page-body">
<div class="um-wrapper">

    <!-- Page header -->
    <div class="um-page-header">
        <div>
            <h1>User Management</h1>
            <p>Manage system accounts, roles, and access credentials.</p>
        </div>
        <button class="btn-add-user" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus-fill"></i> Add New User
        </button>
    </div>

    <!-- Stat strip -->
    <div class="stat-strip">
        <div class="stat-card gold">
            <div class="stat-icon gold"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="stat-value"><?= $totalUsers ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
        <div class="stat-card navy">
            <div class="stat-icon navy"><i class="bi bi-shield-check"></i></div>
            <div>
                <div class="stat-value"><?= $adminCount ?></div>
                <div class="stat-label">Admin Accounts</div>
            </div>
        </div>
        <div class="stat-card frost">
            <div class="stat-icon frost"><i class="bi bi-person-check-fill"></i></div>
            <div>
                <div class="stat-value"><?= $activeCount ?></div>
                <div class="stat-label">Active Users</div>
            </div>
        </div>
    </div>

    <!-- Table card -->
    <div class="table-card">
        <div class="table-card-header">
            <h5 class="table-card-title">
                <span class="title-dot"></span>
                System Users
            </h5>
            <div class="table-card-tools">
                <div class="um-search">
                    <i class="bi bi-search"></i>
                    <input type="text" id="liveSearch" placeholder="Search users…" autocomplete="off">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="userTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Gender</th>
                        <th>Contact</th>
                        <th>User No</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($userRows)): ?>
                <tr><td colspan="7">
                    <div class="empty-state">
                        <i class="bi bi-person-x"></i>
                        <p>No users found. Add your first user to get started.</p>
                    </div>
                </td></tr>
                <?php else: ?>
                <?php foreach ($userRows as $u):
                    $fullName = trim("{$u['givenName']} {$u['midName']} {$u['surName']} {$u['extName']}");
                    $initials = strtoupper(substr($u['givenName'],0,1) . substr($u['surName'],0,1));
                    $chipClass = match($u['roleName']) {
                        'Admin'   => 'admin',
                        'Cashier' => 'cashier',
                        default   => 'other',
                    };
                    $avStyles = [
                        'Admin'   => 'background:rgba(38,35,65,.1);color:#262341;',
                        'Cashier' => 'background:#FEF0E3;color:#d4691a;',
                    ];
                    $avStyle = $avStyles[$u['roleName']] ?? 'background:#E7F5F5;color:#2D8A8A;';
                ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2" style="gap:12px;">
                            <div class="user-avatar" style="<?= $avStyle ?>">
                                <?= htmlspecialchars($initials) ?>
                            </div>
                            <div>
                                <div class="user-name"><?= htmlspecialchars($fullName) ?></div>
                                <div class="user-handle">@<?= htmlspecialchars($u['userName'] ?? strtolower($u['givenName'])) ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <span class="role-chip <?= $chipClass ?>">
                            <i class="bi bi-<?= $u['roleName']==='Admin' ? 'shield-check' : 'person' ?>"></i>
                            <?= htmlspecialchars($u['roleName']) ?>
                        </span>
                    </td>
                    <td style="color:var(--text-muted);"><?= htmlspecialchars($u['gender'] ?? '—') ?></td>
                    <td style="color:var(--text-muted);"><?= htmlspecialchars($u['contactNo'] ?? '—') ?></td>
                    <td>
                        <span class="userno-badge"><?= htmlspecialchars($u['userNo'] ?? '—') ?></span>
                    </td>
                    <td>
                        <div class="action-group">
                            <button class="btn-action edit" title="Edit user"
                                    data-bs-toggle="modal" data-bs-target="#editUserModal<?= $u['userID'] ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn-action key" title="Change password"
                                    data-bs-toggle="modal" data-bs-target="#pwModal<?= $u['userID'] ?>">
                                <i class="bi bi-key"></i>
                            </button>
                            <?php if ($u['userID'] != $_SESSION['userID']): ?>
                            <button class="btn-action del" title="Delete user"
                                    data-bs-toggle="modal" data-bs-target="#deleteUserModal<?= $u['userID'] ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php else: ?>
                            <button class="btn-action del" disabled title="Cannot delete your own account">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>

                <!-- ── Edit User Modal ──────────────────── -->
                <div class="modal fade" id="editUserModal<?= $u['userID'] ?>" tabindex="-1">
                  <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                      <div class="um-modal-header">
                        <h5><i class="bi bi-pencil-square"></i>Edit User — <?= htmlspecialchars($u['givenName']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <form method="POST" action="../backend/userAuth.php">
                        <input type="hidden" name="userUpdate" value="1">
                        <input type="hidden" name="userID" value="<?= $u['userID'] ?>">
                        <?php if (function_exists('csrf_field')) echo csrf_field(); ?>
                        <div class="modal-body">
                          <div class="um-section-label">Account Settings</div>
                          <div class="row g-3">
                            <div class="col-md-4">
                              <label class="form-label">Role<span class="required-star">*</span></label>
                              <select name="roleID" class="form-select" required>
                                <?php $roles->data_seek(0); while ($r = $roles->fetch_assoc()): ?>
                                <option value="<?= $r['roleID'] ?>" <?= $r['roleID'] == $u['roleID'] ? 'selected' : '' ?>>
                                  <?= htmlspecialchars($r['roleName']) ?>
                                </option>
                                <?php endwhile; ?>
                              </select>
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">User No</label>
                              <input type="text" name="userNo" class="form-control" value="<?= htmlspecialchars($u['userNo'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Email<span class="required-star">*</span></label>
                              <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($u['email']) ?>">
                            </div>
                          </div>

                          <div class="um-section-label">Personal Information</div>
                          <div class="row g-3">
                            <div class="col-md-3">
                              <label class="form-label">First Name<span class="required-star">*</span></label>
                              <input type="text" name="givenName" class="form-control" required value="<?= htmlspecialchars($u['givenName']) ?>">
                            </div>
                            <div class="col-md-3">
                              <label class="form-label">Middle Name</label>
                              <input type="text" name="midName" class="form-control" value="<?= htmlspecialchars($u['midName'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                              <label class="form-label">Last Name<span class="required-star">*</span></label>
                              <input type="text" name="surName" class="form-control" required value="<?= htmlspecialchars($u['surName']) ?>">
                            </div>
                            <div class="col-md-3">
                              <label class="form-label">Suffix</label>
                              <input type="text" name="extName" class="form-control" placeholder="Jr / Sr / III" value="<?= htmlspecialchars($u['extName'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Gender</label>
                              <select name="gender" class="form-select">
                                <option value="Male"   <?= ($u['gender'] ?? '') === 'Male'   ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= ($u['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other"  <?= ($u['gender'] ?? '') === 'Other'  ? 'selected' : '' ?>>Other / Prefer not to say</option>
                              </select>
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Birthdate</label>
                              <input type="date" name="birthdate" class="form-control" value="<?= htmlspecialchars($u['birthdate'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Contact No</label>
                              <input type="text" name="contactNo" class="form-control" value="<?= htmlspecialchars($u['contactNo'] ?? '') ?>">
                            </div>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn-ev"><i class="bi bi-check-lg"></i>Save Changes</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

                <!-- ── Change Password Modal ─────────── -->
                <div class="modal fade" id="pwModal<?= $u['userID'] ?>" tabindex="-1">
                  <div class="modal-dialog modal-sm modal-dialog-centered">
                    <div class="modal-content">
                      <div class="um-modal-warn-header">
                        <h5><i class="bi bi-key-fill"></i>Change Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <form method="POST" action="../backend/userAuth.php">
                        <input type="hidden" name="changePassword" value="1">
                        <input type="hidden" name="userID" value="<?= $u['userID'] ?>">
                        <?php if (function_exists('csrf_field')) echo csrf_field(); ?>
                        <div class="modal-body">
                          <div class="info-note mb-3">
                            <i class="bi bi-person-fill"></i>
                            Setting new password for <strong><?= htmlspecialchars($u['givenName']) ?></strong>
                          </div>
                          <label class="form-label">New Password<span class="required-star">*</span></label>
                          <input type="password" name="newPassword" class="form-control" minlength="6" required placeholder="Min. 6 characters">
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn-warn"><i class="bi bi-check-lg"></i>Update Password</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

                <!-- ── Delete User Modal ─────────────── -->
                <?php if ($u['userID'] != $_SESSION['userID']): ?>
                <div class="modal fade" id="deleteUserModal<?= $u['userID'] ?>" tabindex="-1">
                  <div class="modal-dialog modal-sm modal-dialog-centered">
                    <div class="modal-content">
                      <div class="um-modal-danger-header">
                        <h5><i class="bi bi-trash3"></i>Delete User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <form method="POST" action="../backend/userAuth.php">
                        <input type="hidden" name="userDelete" value="1">
                        <input type="hidden" name="userID" value="<?= $u['userID'] ?>">
                        <?php if (function_exists('csrf_field')) echo csrf_field(); ?>
                        <div class="modal-body" style="text-align:center;padding:28px 26px;">
                            <div class="user-avatar mx-auto mb-3" style="width:52px;height:52px;border-radius:14px;font-size:1.1rem;<?= $avStyle ?>;display:flex;align-items:center;justify-content:center;">
                                <?= htmlspecialchars($initials) ?>
                            </div>
                            <p style="margin:0;font-size:14px;color:var(--text-muted);">
                                Remove <strong style="color:var(--navy);"><?= htmlspecialchars($fullName) ?></strong> from the system?
                            </p>
                            <p style="font-size:12px;color:var(--danger);margin:8px 0 0;font-weight:600;">This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer" style="justify-content:center;">
                          <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-danger btn-sm fw-bold"><i class="bi bi-trash me-1"></i>Yes, Delete</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
                <?php endif; ?>

                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div><!-- /table-card -->

</div><!-- /um-wrapper -->
</div><!-- /page-body -->

<!-- ═══════════════════════════════════════════════
     ADD USER MODAL
═══════════════════════════════════════════════ -->
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="um-modal-header">
        <h5><i class="bi bi-person-plus-fill"></i>Add New User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="../backend/userAuth.php" id="addUserForm">
        <input type="hidden" name="userSave" value="1">
        <?php if (function_exists('csrf_field')) echo csrf_field(); ?>
        <div class="modal-body">

          <div class="um-section-label">Account Settings</div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Role<span class="required-star">*</span></label>
              <select name="roleID" class="form-select" required>
                <option value="">— Select Role —</option>
                <?php $roles->data_seek(0); while ($r = $roles->fetch_assoc()): ?>
                <option value="<?= $r['roleID'] ?>"><?= htmlspecialchars($r['roleName']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">User No</label>
              <input type="text" name="userNo" class="form-control" placeholder="e.g. EMP-001">
            </div>
            <div class="col-md-4">
              <label class="form-label">Email<span class="required-star">*</span></label>
              <input type="email" name="email" class="form-control" required placeholder="user@example.com">
            </div>
          </div>

          <div class="um-section-label">Personal Information</div>
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">First Name<span class="required-star">*</span></label>
              <input type="text" name="givenName" class="form-control" required placeholder="Juan">
            </div>
            <div class="col-md-3">
              <label class="form-label">Middle Name</label>
              <input type="text" name="midName" class="form-control" placeholder="Dela">
            </div>
            <div class="col-md-3">
              <label class="form-label">Last Name<span class="required-star">*</span></label>
              <input type="text" name="surName" class="form-control" required placeholder="Cruz">
            </div>
            <div class="col-md-3">
              <label class="form-label">Suffix</label>
              <input type="text" name="extName" class="form-control" placeholder="Jr / Sr / III">
            </div>
            <div class="col-md-4">
              <label class="form-label">Gender</label>
              <select name="gender" class="form-select">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other / Prefer not to say</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Birthdate</label>
              <input type="date" name="birthdate" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Contact No</label>
              <input type="text" name="contactNo" class="form-control" placeholder="09XX XXX XXXX">
            </div>
          </div>

          <div class="um-section-label">Security</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Password<span class="required-star">*</span></label>
              <div style="position:relative;">
                <input type="password" name="password" id="addPwField" class="form-control" minlength="6" required placeholder="Minimum 6 characters">
                <button type="button" onclick="toggleAddPw()" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--navy-light);cursor:pointer;">
                  <i class="bi bi-eye" id="addPwIcon"></i>
                </button>
              </div>
            </div>
            <div class="col-md-6 d-flex align-items-end">
              <div class="info-note w-100">
                <i class="bi bi-info-circle-fill"></i>
                Password must be at least 6 characters. User can change it after first login.
              </div>
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-ev"><i class="bi bi-person-plus"></i>Create Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="ev-toast" id="evToast">
    <i class="bi bi-lightning-charge-fill ev-toast-icon"></i>
    <div>
        <div class="ev-toast-msg" id="evToastMsg">Real-time update</div>
        <div class="ev-toast-sub" id="evToastSub">User data was changed</div>
    </div>
</div>

<script>
$(document).ready(function () {
    var dt = $('#userTable').DataTable({
        pageLength: 15,
        order: [[2, 'asc'], [0, 'asc']],
        columnDefs: [{ orderable: false, targets: 6 }],
        language: {
            paginate: {
                previous: '<i class="bi bi-chevron-left"></i>',
                next:     '<i class="bi bi-chevron-right"></i>'
            },
            info: 'Showing _START_–_END_ of _TOTAL_ users',
            emptyTable: ''
        }
    });
    document.getElementById('liveSearch').addEventListener('input', function () {
        dt.search(this.value).draw();
    });
});

function toggleAddPw() {
    const f = document.getElementById('addPwField');
    const i = document.getElementById('addPwIcon');
    f.type = f.type === 'password' ? 'text' : 'password';
    i.className = f.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

function showEvToast(msg, sub) {
    const t = document.getElementById('evToast');
    document.getElementById('evToastMsg').textContent = msg || 'Update received';
    document.getElementById('evToastSub').textContent = sub || '';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3800);
}

(function () {
    const key = window.PUSHER_KEY || '';
    const cluster = window.PUSHER_CLUSTER || 'ap1';
    if (!key) return;
    const pusher = new Pusher(key, { cluster });
    const channel = pusher.subscribe('user-management');
    channel.bind('user-changed', function (data) {
        const action = data.action || 'updated';
        const name   = data.name   || 'A user';
        const by     = data.by     || '';
        const msgs = {
            added:              ['New user added',     name + ' was added' + (by ? ' by ' + by : '')],
            updated:            ['User updated',       name + ' was updated'],
            deleted:            ['User removed',       name + ' was deleted'],
            'password-changed': ['Password changed',   'Credentials were updated'],
        };
        const [msg, sub] = msgs[action] || ['User changed', ''];
        showEvToast(msg, sub);
        setTimeout(() => location.reload(), 2200);
    });
})();
</script>

<script src="pusher-content/realtime.js"></script>
</div></div>
</body>
</html>