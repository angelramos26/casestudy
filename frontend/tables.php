<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
$pageTitle = "Table Map – Restaurant POS";

// Load all tables with active order info
$tablesResult = $conn->query("
    SELECT dt.tableID, dt.tableNo, dt.capacity, dt.section, dt.status,
           o.orderID, o.orderType, o.pax, o.status AS orderStatus, o.dateCreated AS created_at,
           COUNT(oi.orderItemID) AS item_count
    FROM dining_table dt
    LEFT JOIN orders o ON dt.tableID = o.tableID AND o.status IN ('Open','Active')
    LEFT JOIN order_items oi ON o.orderID = oi.orderID AND oi.status != 'Void'
    GROUP BY dt.tableID, o.orderID
    ORDER BY dt.section, dt.tableNo ASC
");
$tables = [];
$sections = [];
while($row = $tablesResult->fetch_assoc()) {
    $tables[] = $row;
    if(!in_array($row['section'], $sections)) $sections[] = $row['section'];
}
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<style>
.tables-body { padding: 24px 28px; }

.section-label-strip {
    font-size: 10px; text-transform: uppercase; letter-spacing: 2px;
    color: var(--t-muted); font-weight: 800;
    margin: 24px 0 12px; display: flex; align-items: center; gap: 10px;
}
.section-label-strip::after { content: ''; flex: 1; height: 1.5px; background: var(--s-border); }

.table-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 14px;
    margin-bottom: 10px;
}

.table-card {
    background: var(--c-white);
    border-radius: var(--r-md);
    border: 2px solid var(--s-border);
    padding: 18px 14px;
    text-align: center;
    cursor: pointer;
    transition: all .2s;
    box-shadow: var(--sh-sm);
    position: relative;
    display: flex; flex-direction: column; align-items: center; gap: 6px;
}
.table-card:hover { transform: translateY(-3px); box-shadow: var(--sh-md); }

/* Statuses */
.table-card.status-Available { border-color: var(--c-success); background: var(--c-success-bg); }
.table-card.status-Occupied  { border-color: var(--orange); background: var(--orange-soft); }
.table-card.status-Reserved  { border-color: var(--c-warning); background: var(--c-warning-bg); }
.table-card.status-Maintenance { border-color: var(--c-danger); background: var(--c-danger-bg); }

.table-icon {
    width: 52px; height: 52px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
}
.status-Available .table-icon  { background: rgba(34,200,122,.15); color: var(--c-success); }
.status-Occupied  .table-icon  { background: rgba(239,130,13,.15); color: var(--orange); }
.status-Reserved  .table-icon  { background: rgba(245,156,42,.15); color: var(--c-warning); }
.status-Maintenance .table-icon { background: var(--c-danger-bg); color: var(--c-danger); }

.table-no   { font-size: 1.1rem; font-weight: 900; color: var(--t-main); }
.table-cap  { font-size: 11px; color: var(--t-muted); font-weight: 600; }
.table-status-pill {
    font-size: 10px; font-weight: 800; padding: 3px 10px;
    border-radius: 20px; letter-spacing: .3px;
}
.status-Available  .table-status-pill { background: var(--c-success); color: #fff; }
.status-Occupied   .table-status-pill { background: var(--orange); color: #fff; }
.status-Reserved   .table-status-pill { background: var(--c-warning); color: #fff; }
.status-Maintenance .table-status-pill { background: var(--c-danger); color: #fff; }

.table-order-info {
    font-size: 10.5px; color: var(--t-muted); font-weight: 600;
    line-height: 1.4;
}

.btn-open-order {
    position: absolute; top: 8px; right: 8px;
    background: var(--charcoal); color: var(--orange);
    border: none; border-radius: 6px;
    padding: 3px 8px; font-size: 10.5px; font-weight: 700;
    cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 3px;
}
.btn-open-order:hover { opacity: .85; color: var(--orange); }

/* Admin action buttons on card */
.card-admin-actions {
    position: absolute; top: 8px; left: 8px;
    display: flex; gap: 4px;
}
.btn-card-edit,
.btn-card-delete {
    border: none; border-radius: 5px;
    padding: 3px 7px; font-size: 10px; font-weight: 700;
    cursor: pointer; display: inline-flex; align-items: center; gap: 2px;
    line-height: 1;
}
.btn-card-edit   { background: var(--charcoal); color: #fff; }
.btn-card-delete { background: var(--c-danger);  color: #fff; }
.btn-card-edit:hover   { opacity: .85; }
.btn-card-delete:hover { opacity: .85; }

/* Legend */
.legend { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 16px; }
.legend-item { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; color: var(--t-mid); }
.legend-dot { width: 12px; height: 12px; border-radius: 3px; }
.ld-avail { background: var(--c-success); }
.ld-occ   { background: var(--orange); }
.ld-res   { background: var(--c-warning); }
.ld-maint { background: var(--c-danger); }

/* Admin controls */
.admin-strip { display: flex; gap: 10px; align-items: center; margin-bottom: 16px; flex-wrap: wrap; }
</style>

<?php
$topbarTitle  = 'Table Map';
$topbarIcon   = 'bi-grid-3x3';
$topbarSub    = 'Dining room overview & table status';
$canManage    = in_array($_SESSION['roleName'], ['Admin']);
$canSetStatus = in_array($_SESSION['roleName'], ['Admin', 'Cashier']);
$topbarExtra  = $canManage ? '<button class="btn-rpos-primary" onclick="openAddTableModal()" style="font-size:12px;padding:6px 14px;"><i class="bi bi-plus-circle me-1"></i>Add Table</button>' : '';
include 'topbar.php';
?>

<?php
$alerts = [
    'savedData'      => ['success', 'Table Added',     'New table has been added successfully.'],
    'updatedTable'   => ['success', 'Table Updated',   'Table details have been updated.'],
    'tableDeleted'   => ['success', 'Table Deleted',   'The table has been removed.'],
    'tableExists'    => ['error',   'Duplicate',       'A table with that number already exists.'],
    'tableHasOrders' => ['error',   'Cannot Delete',   'This table still has open orders linked to it.'],
    'emptyFields'    => ['warning', 'Required Fields', 'Please fill in all required fields.'],
];
foreach($alerts as $k => [$i, $t, $tx])
    if(isset($_GET[$k]))
        echo "<script>Swal.fire({icon:'$i',title:'$t',text:'$tx',timer:2500,showConfirmButton:false}).then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";
?>

<div class="page-body tables-body">

    <!-- Legend -->
    <div class="legend">
        <div class="legend-item"><span class="legend-dot ld-avail"></span>Available</div>
        <div class="legend-item"><span class="legend-dot ld-occ"></span>Occupied</div>
        <div class="legend-item"><span class="legend-dot ld-res"></span>Reserved</div>
        <div class="legend-item"><span class="legend-dot ld-maint"></span>Maintenance</div>
        <span style="margin-left:auto;font-size:12px;color:var(--t-muted);">
            <i class="bi bi-arrow-clockwise me-1"></i>
            <a href="" style="color:var(--charcoal);font-weight:700;">Refresh</a>
        </span>
    </div>

    <?php
    $bySection = [];
    foreach($tables as $t) { $bySection[$t['section']][] = $t; }

    foreach($bySection as $section => $sectionTables):
    $status = $t['orderID'] ? 'Occupied' : $t['status'];
    ?>
    <div class="section-label-strip"><?= htmlspecialchars($section) ?></div>
    <div class="table-grid">
        <?php foreach($sectionTables as $t):
            $occupied = !empty($t['orderID']);
            $cardStatus = $occupied ? 'Occupied' : $t['status'];
            $icon = match($cardStatus) {
                'Available'   => 'bi-check-circle',
                'Occupied'    => 'bi-people-fill',
                'Reserved'    => 'bi-calendar-check',
                'Maintenance' => 'bi-tools',
                default       => 'bi-circle'
            };
        ?>
        <div class="table-card status-<?= htmlspecialchars($cardStatus) ?>"
             onclick="<?= $occupied ? "goToPOS({$t['orderID']})" : ($t['status'] === 'Available' ? "quickOpenOrder({$t['tableID']},'{$t['tableNo']}')" : '') ?>">

            <?php if($canManage): ?>
            <!-- Edit & Delete buttons — Admin only -->
            <div class="card-admin-actions" onclick="event.stopPropagation()">
                <button class="btn-card-edit"
                        onclick="openEditTableModal(<?= $t['tableID'] ?>, '<?= htmlspecialchars($t['tableNo'], ENT_QUOTES) ?>', <?= $t['capacity'] ?>, '<?= htmlspecialchars($t['section'], ENT_QUOTES) ?>')">
                    <i class="bi bi-pencil-fill"></i>
                </button>
                <?php if(!$occupied): ?>
                <button class="btn-card-delete"
                        onclick="confirmDeleteTable(<?= $t['tableID'] ?>, '<?= htmlspecialchars($t['tableNo'], ENT_QUOTES) ?>')">
                    <i class="bi bi-trash-fill"></i>
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if($occupied): ?>
            <a class="btn-open-order" href="pos.php?loadOrder=<?= $t['orderID'] ?>" onclick="event.stopPropagation()">
                <i class="bi bi-arrow-up-right-square"></i> Open
            </a>
            <?php if($canSetStatus): ?>
            <button class="btn-open-order" style="background:var(--c-success);color:#fff;border:none;bottom:8px;top:auto;"
                    onclick="event.stopPropagation();clearTable(<?= $t['tableID'] ?>)">
                <i class="bi bi-check2"></i> Clear
            </button>
            <?php endif; ?>
            <?php elseif($canSetStatus): ?>
            <button class="btn-open-order" style="background:var(--s-bg);color:var(--t-muted);border:1px solid var(--s-border);"
                    onclick="event.stopPropagation();changeTableStatus(<?= $t['tableID'] ?>, '<?= $t['status'] ?>')">
                <i class="bi bi-three-dots"></i>
            </button>
            <?php endif; ?>

            <?php if(!$occupied && $t['status'] === 'Occupied' && $canSetStatus): ?>
            <button style="position:absolute;bottom:8px;right:8px;background:var(--c-success);color:#fff;border:none;border-radius:5px;padding:2px 8px;font-size:10px;font-weight:700;cursor:pointer;"
                    onclick="event.stopPropagation();changeTableStatus(<?= $t['tableID'] ?>, 'Occupied')">
                Clear
            </button>
            <?php endif; ?>

            <div class="table-icon"><i class="bi <?= $icon ?>"></i></div>
            <div class="table-no">Table <?= htmlspecialchars($t['tableNo']) ?></div>
            <div class="table-cap"><i class="bi bi-person-fill me-1"></i><?= $t['capacity'] ?> pax</div>
            <div class="table-status-pill"><?= htmlspecialchars($cardStatus) ?></div>

            <?php if($occupied): ?>
            <div class="table-order-info">
                Order #<?= $t['orderID'] ?> · <?= $t['item_count'] ?> item(s)<br>
                <?= $t['orderType'] ?> · <?= $t['pax'] ?> pax<br>
                <span style="color:var(--text-light);font-size:10px;">
                    <?= date('H:i', strtotime($t['created_at'])) ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <?php if(empty($tables)): ?>
    <div style="text-align:center;padding:60px 20px;color:var(--t-muted);">
        <i class="bi bi-grid-3x3" style="font-size:3rem;opacity:.2;"></i>
        <p style="margin-top:12px;font-size:14px;">No tables configured. <a href="#" onclick="openAddTableModal()" style="color:var(--charcoal);font-weight:700;">Add your first table</a>.</p>
    </div>
    <?php endif; ?>
</div>

<?php if($canManage): ?>

<!-- ══════════════════════════════════════════════
     ADD TABLE MODAL
══════════════════════════════════════════════ -->
<div class="modal fade" id="addTableModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;">
            <form action="../backend/tableAuth.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <div class="modal-header" style="background:var(--charcoal);border:none;padding:14px 20px;">
                    <h6 class="modal-title" style="font-weight:800;color:#fff;"><i class="bi bi-plus-circle me-2" style="color:var(--orange);"></i>Add Table</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:22px;">
                    <div style="margin-bottom:12px;">
                        <label class="form-label" style="font-size:12px;font-weight:700;color:var(--t-mid);">Table No / Name</label>
                        <input type="text" name="tableNo" class="form-control" placeholder="e.g. 1, A2, VIP-1" required style="border:1.5px solid var(--s-border);border-radius:8px;font-size:13px;padding:8px 12px;">
                    </div>
                    <div style="margin-bottom:12px;">
                        <label class="form-label" style="font-size:12px;font-weight:700;color:var(--t-mid);">Capacity (pax)</label>
                        <input type="number" name="capacity" class="form-control" value="4" min="1" max="50" required style="border:1.5px solid var(--s-border);border-radius:8px;font-size:13px;padding:8px 12px;">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:12px;font-weight:700;color:var(--t-mid);">Section</label>
                        <input type="text" name="section" class="form-control" value="Main" placeholder="e.g. Main, Outdoor, VIP" style="border:1.5px solid var(--s-border);border-radius:8px;font-size:13px;padding:8px 12px;">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1.5px solid var(--s-border);padding:12px 20px;">
                    <button type="button" class="btn-rpos-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="tableSave" class="btn-rpos-primary"><i class="bi bi-check2-circle me-1"></i>Save Table</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════
     EDIT TABLE MODAL
══════════════════════════════════════════════ -->
<div class="modal fade" id="editTableModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;">
            <form action="../backend/tableAuth.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="tableID" id="editTableID">
                <div class="modal-header" style="background:var(--charcoal);border:none;padding:14px 20px;">
                    <h6 class="modal-title" style="font-weight:800;color:#fff;"><i class="bi bi-pencil-fill me-2" style="color:var(--orange);"></i>Edit Table</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:22px;">
                    <div style="margin-bottom:12px;">
                        <label class="form-label" style="font-size:12px;font-weight:700;color:var(--t-mid);">Table No / Name</label>
                        <input type="text" name="tableNo" id="editTableNo" class="form-control" required
                               style="border:1.5px solid var(--s-border);border-radius:8px;font-size:13px;padding:8px 12px;">
                    </div>
                    <div style="margin-bottom:12px;">
                        <label class="form-label" style="font-size:12px;font-weight:700;color:var(--t-mid);">Capacity (pax)</label>
                        <input type="number" name="capacity" id="editCapacity" class="form-control" min="1" max="50" required
                               style="border:1.5px solid var(--s-border);border-radius:8px;font-size:13px;padding:8px 12px;">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:12px;font-weight:700;color:var(--t-mid);">Section</label>
                        <input type="text" name="section" id="editSection" class="form-control" placeholder="e.g. Main, Outdoor, VIP"
                               style="border:1.5px solid var(--s-border);border-radius:8px;font-size:13px;padding:8px 12px;">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1.5px solid var(--s-border);padding:12px 20px;">
                    <button type="button" class="btn-rpos-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="tableUpdate" class="btn-rpos-primary"><i class="bi bi-check2-circle me-1"></i>Update Table</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════
     DELETE TABLE HIDDEN FORM
══════════════════════════════════════════════ -->
<form id="deleteTableForm" action="../backend/tableAuth.php" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
    <input type="hidden" name="tableID"    id="deleteTableID">
    <input type="hidden" name="tableDelete" value="1">
</form>

<?php endif; ?>

<script>
/* ── Add ─────────────────────────────────────── */
function openAddTableModal() {
    new bootstrap.Modal(document.getElementById('addTableModal')).show();
}

/* ── Edit ────────────────────────────────────── */
function openEditTableModal(id, tableNo, capacity, section) {
    document.getElementById('editTableID').value  = id;
    document.getElementById('editTableNo').value  = tableNo;
    document.getElementById('editCapacity').value = capacity;
    document.getElementById('editSection').value  = section;
    new bootstrap.Modal(document.getElementById('editTableModal')).show();
}

/* ── Delete ──────────────────────────────────── */
function confirmDeleteTable(id, tableNo) {
    Swal.fire({
        title: 'Delete Table ' + tableNo + '?',
        text: 'This cannot be undone. Make sure there are no open orders on this table.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--c-danger)',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel'
    }).then(r => {
        if(r.isConfirmed) {
            document.getElementById('deleteTableID').value = id;
            document.getElementById('deleteTableForm').submit();
        }
    });
}

/* ── POS navigation ──────────────────────────── */
function goToPOS(orderID) {
    window.location.href = 'pos.php?loadOrder=' + orderID;
}

function quickOpenOrder(tableID, tableNo) {
    Swal.fire({
        title: 'Open order for Table ' + tableNo + '?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: 'var(--charcoal)',
        confirmButtonText: 'Open POS',
        cancelButtonText: 'Cancel'
    }).then(r => {
        if(r.isConfirmed) window.location.href = 'pos.php?newOrder=dine&tableID=' + tableID;
    });
}

/* ── Status helpers ──────────────────────────── */
function postTableStatus(tableID, status) {
    const fd = new FormData();
    fd.append('tableStatus', '1');
    fd.append('tableID', tableID);
    fd.append('status', status);
    fd.append('csrf_token', '<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>');
    fetch('../backend/tableAuth.php', { method: 'POST', body: fd })
        .then(res => {
            if(!res.ok) {
                Swal.fire({icon:'error', title:'Error', text:'Failed to update table status. ('+res.status+')', timer:2500});
                return;
            }
            location.reload();
        })
        .catch(() => {
            Swal.fire({icon:'error', title:'Network Error', text:'Could not reach server.', timer:2500});
        });
}

function clearTable(tableID) {
    Swal.fire({
        title: 'Clear this table?',
        text: 'This will set the table back to Available.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#22c87a',
        confirmButtonText: 'Yes, clear it',
        cancelButtonText: 'Cancel'
    }).then(r => {
        if(r.isConfirmed) postTableStatus(tableID, 'Available');
    });
}

function changeTableStatus(tableID, currentStatus) {
    const allStatuses = ['Available', 'Occupied', 'Reserved', 'Maintenance'];
    const inputOptions = {};
    allStatuses.filter(s => s !== currentStatus).forEach(s => inputOptions[s] = s);

    Swal.fire({
        title: 'Set Table Status',
        html: `<p style="font-size:13px;color:#666;margin-bottom:4px;">Current: <strong>${currentStatus}</strong></p>
               <p style="font-size:12px;color:#999;">Use <em>Available</em> if guests already paid and left.</p>`,
        input: 'select',
        inputOptions: inputOptions,
        inputPlaceholder: '— choose status —',
        showCancelButton: true,
        confirmButtonColor: 'var(--charcoal)',
        confirmButtonText: 'Update',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => { if (!value) return 'Please choose a status.'; }
    }).then(r => {
        if(r.isConfirmed && r.value) postTableStatus(tableID, r.value);
    });
}
</script>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
    const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>
<?php include 'footer.php'; ?>
</body></html>
