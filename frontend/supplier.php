<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';

session_start();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if(!in_array($_SESSION['roleName'], ['Admin','Owner'])){ header("Location: dashboard.php"); exit(); }
$pageTitle = "Suppliers – 7Evelyn POS";
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<style>
/* ── Body ── */
.sp-body { padding: 24px 28px; background: var(--ice); min-height: calc(100vh - 52px); }

/* ── Page header row ── */
.page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px;
}
.page-header h5 { margin: 0; font-size: 1.1rem; font-weight: 900; color: var(--text-dark); display: flex; align-items: center; gap: 8px; }
.page-header h5 i { color: var(--navy-light); }

.btn-add {
    background: var(--navy); color: var(--gold);
    border: none; border-radius: var(--radius-sm);
    padding: 9px 20px; font-size: 13px; font-weight: 800;
    cursor: pointer; transition: all .2s;
    display: inline-flex; align-items: center; gap: 7px;
    box-shadow: 0 3px 12px rgba(38,35,65,.18);
}
.btn-add:hover { background: var(--navy-mid); box-shadow: var(--shadow-md); }

/* ── Table card ── */
.table-card {
    background: var(--white);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--ice-dark);
    overflow: hidden;
}
.table-card-head {
    padding: 14px 20px;
    border-bottom: 1px solid var(--ice-dark);
    display: flex; align-items: center; justify-content: space-between;
    background: #fafbff;
}
.table-card-head .tc-title { font-size: 13px; font-weight: 800; color: var(--text-dark); display: flex; align-items: center; gap: 7px; }
.table-card-head .tc-title i { color: var(--navy-light); }
.table-card-head .tc-count { background: var(--navy); color: var(--gold); border-radius: 20px; font-size: 11px; font-weight: 800; padding: 2px 10px; }

.table-wrap { padding: 16px 20px; overflow-x: auto; }

table#supplierTable { width: 100%; border-collapse: collapse; }
table#supplierTable thead tr th {
    background: var(--navy); color: var(--gold);
    font-size: 11px; font-weight: 800; letter-spacing: .6px;
    text-transform: uppercase; padding: 11px 14px;
    white-space: nowrap; border: none;
}
table#supplierTable tbody tr { transition: background .12s; }
table#supplierTable tbody tr:hover { background: #f5f8ff; }
table#supplierTable tbody tr:nth-child(even) { background: #fafbff; }
table#supplierTable tbody tr:nth-child(even):hover { background: #f0f4ff; }
table#supplierTable tbody td {
    padding: 11px 14px; font-size: 12.5px; color: var(--text-dark);
    border-bottom: 1px solid var(--ice); vertical-align: middle;
}

.company-name { font-weight: 800; color: var(--text-dark); }
.company-initial {
    width: 34px; height: 34px; border-radius: 10px;
    background: var(--navy); color: var(--gold);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 900; flex-shrink: 0;
    margin-right: 10px;
}
.contact-person { font-weight: 600; color: var(--text-dark); }
.email-cell { color: var(--navy-light); font-size: 12px; }
.contact-no { font-family: monospace; font-size: 12.5px; }
.address-cell { color: var(--text-muted); font-size: 12px; max-width: 180px; }

.btn-edit {
    background: var(--ice); color: var(--navy);
    border: 1.5px solid var(--ice-dark);
    border-radius: var(--radius-sm);
    width: 30px; height: 30px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 13px; cursor: pointer; transition: all .15s;
    margin-right: 4px;
}
.btn-edit:hover { background: var(--navy); color: var(--gold); border-color: var(--navy); }

.btn-del {
    background: #fde8ea; color: var(--danger);
    border: 1.5px solid #f7c0c6;
    border-radius: var(--radius-sm);
    width: 30px; height: 30px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 13px; cursor: pointer; transition: all .15s;
}
.btn-del:hover { background: var(--danger); color: #fff; border-color: var(--danger); }

.view-only-badge { font-size: 11px; color: var(--text-muted); font-weight: 600; }

/* DataTables overrides */
.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select {
    border: 1.5px solid var(--ice-dark) !important; border-radius: var(--radius-sm) !important;
    padding: 5px 10px !important; font-size: 12.5px !important; outline: none !important;
    background: var(--ice) !important; color: var(--text-dark) !important;
}
.dataTables_wrapper .dataTables_filter input:focus,
.dataTables_wrapper .dataTables_length select:focus { border-color: var(--navy) !important; background: var(--white) !important; box-shadow: none !important; }
.dataTables_wrapper .dataTables_filter label,
.dataTables_wrapper .dataTables_length label,
.dataTables_wrapper .dataTables_info { font-size: 12px; color: var(--text-muted); }
.dataTables_wrapper .dataTables_paginate .paginate_button { border-radius: var(--radius-sm) !important; font-size: 12px !important; color: var(--text-mid) !important; }
.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover { background: var(--navy) !important; color: var(--gold) !important; border-color: var(--navy) !important; }
.dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: var(--ice) !important; color: var(--navy) !important; border-color: var(--ice-dark) !important; }

/* ── Modals ── */
.modal-navy .modal-header { background: var(--navy); color: var(--white); border: none; }
.modal-navy .modal-title  { font-weight: 800; }
.modal-navy .modal-title i { color: var(--gold); }
.modal-navy .btn-close-white { filter: brightness(0) invert(1); opacity: .7; }
.modal-navy .modal-content { border: none; border-radius: 16px; overflow: hidden; }

.modal-danger .modal-header { background: var(--danger); color: var(--white); border: none; }
.modal-danger .modal-content { border: none; border-radius: 16px; overflow: hidden; }
.modal-danger .btn-close-white { filter: brightness(0) invert(1); opacity: .7; }

.form-label-sm { font-size: 12px; font-weight: 700; color: var(--text-mid); margin-bottom: 4px; }
.form-field {
    width: 100%; padding: 8px 12px;
    border: 1.5px solid var(--ice-dark); border-radius: var(--radius-sm);
    font-size: 13px; color: var(--text-dark); background: var(--ice);
    outline: none; transition: border-color .2s;
}
.form-field:focus { border-color: var(--navy); background: var(--white); box-shadow: none; }

.btn-save {
    background: var(--navy); color: var(--gold);
    border: none; border-radius: var(--radius-sm);
    padding: 9px 22px; font-size: 13px; font-weight: 800;
    cursor: pointer; transition: all .2s;
}
.btn-save:hover { background: var(--navy-mid); }

.btn-cancel {
    background: var(--ice); color: var(--text-mid);
    border: 1.5px solid var(--ice-dark); border-radius: var(--radius-sm);
    padding: 8px 18px; font-size: 13px; font-weight: 700;
    cursor: pointer; transition: all .2s;
}
.btn-cancel:hover { background: var(--ice-dark); }

.btn-delete-confirm {
    background: var(--danger); color: #fff;
    border: none; border-radius: var(--radius-sm);
    padding: 8px 18px; font-size: 13px; font-weight: 800;
    cursor: pointer; transition: all .2s;
}
.btn-delete-confirm:hover { opacity: .85; }
</style>

<?php
$alerts = [
    'savedData'        => ['success', 'Saved!',         'Supplier added successfully.'],
    'updatedSupplier'  => ['success', 'Updated!',        'Supplier information updated.'],
    'supplierDeleted'  => ['success', 'Deleted!',        'Supplier removed.'],
    'emailExists'      => ['error',   'Email Exists',    'This email is already registered.'],
    'emptyFields'      => ['warning', 'Required Fields', 'Please fill in all required fields.'],
];
foreach($alerts as $k => [$i,$t,$tx])
    if(isset($_GET[$k]))
        echo "<script>Swal.fire({icon:'$i',title:'$t',text:'$tx',timer:2000}).then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";
?>

<!-- Topbar -->
<?php
$topbarTitle = 'Supplier Management';
$topbarIcon  = 'bi-truck-front-fill';
$topbarSub   = 'Manage supplier contacts and information';
include 'topbar.php';
?>

<div class="sp-body">

    <div class="page-header">
        <h5>
            <i class="bi bi-building"></i>
            Suppliers
        </h5>
        <?php if($_SESSION['roleName'] === 'Admin'): ?>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
            <i class="bi bi-plus-circle-fill"></i> Add Supplier
        </button>
        <?php endif; ?>
    </div>

    <?php
    $result = $conn->query("SELECT * FROM supplier WHERE dateDeleted IS NULL ORDER BY companyName ASC");
    $suppliers = [];
    while($row = $result->fetch_assoc()) $suppliers[] = $row;
    $totalCount = count($suppliers);
    ?>

    <div class="table-card">
        <div class="table-card-head">
            <div class="tc-title">
                <i class="bi bi-table"></i>
                Supplier Directory
            </div>
            <span class="tc-count"><?php echo $totalCount; ?> suppliers</span>
        </div>
        <div class="table-wrap">
            <table id="supplierTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Contact Person</th>
                        <th>Email</th>
                        <th>Contact No</th>
                        <th>Address</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($suppliers as $row): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;">
                            <span class="company-initial"><?php echo mb_strtoupper(mb_substr($row['companyName'],0,1)); ?></span>
                            <span class="company-name"><?php echo htmlspecialchars($row['companyName']); ?></span>
                        </div>
                    </td>
                    <td><span class="contact-person"><?php echo htmlspecialchars($row['supplierName']); ?></span></td>
                    <td><span class="email-cell"><?php echo htmlspecialchars($row['email']); ?></span></td>
                    <td><span class="contact-no"><?php echo htmlspecialchars($row['contactNo']); ?></span></td>
                    <td><span class="address-cell"><?php echo htmlspecialchars($row['address'] ?? '—'); ?></span></td>
                    <td style="white-space:nowrap;">
                        <?php if($_SESSION['roleName'] === 'Admin'): ?>
                        <button class="btn-edit" data-bs-toggle="modal" data-bs-target="#editSupplierModal<?php echo $row['supplierID']; ?>" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-del" data-bs-toggle="modal" data-bs-target="#deleteSupplierModal<?php echo $row['supplierID']; ?>" title="Delete">
                            <i class="bi bi-trash3"></i>
                        </button>
                        <?php else: ?>
                        <span class="view-only-badge"><i class="bi bi-eye me-1"></i>View Only</span>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade modal-navy" id="editSupplierModal<?php echo $row['supplierID']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="../backend/supplierAuth.php">
                                <?php csrf_field(); ?>
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Supplier</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body" style="padding:20px 24px;">
                                    <input type="hidden" name="supplierID" value="<?php echo $row['supplierID']; ?>">
                                    <div class="mb-3">
                                        <div class="form-label-sm">Company Name <span style="color:var(--danger);">*</span></div>
                                        <input type="text" name="companyName" value="<?php echo htmlspecialchars($row['companyName']); ?>" class="form-field" required>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-label-sm">Contact Person <span style="color:var(--danger);">*</span></div>
                                        <input type="text" name="supplierName" value="<?php echo htmlspecialchars($row['supplierName']); ?>" class="form-field" required>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-label-sm">Email</div>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" class="form-field">
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-label-sm">Contact No</div>
                                        <input type="text" name="contactNo" value="<?php echo htmlspecialchars($row['contactNo']); ?>" class="form-field">
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-label-sm">Address</div>
                                        <textarea name="address" class="form-field" rows="2"><?php echo htmlspecialchars($row['address'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer" style="border-top:1px solid var(--ice-dark);padding:12px 24px;gap:8px;">
                                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="supplierUpdate" class="btn-save">Update Supplier</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Delete Modal -->
                <div class="modal fade modal-danger" id="deleteSupplierModal<?php echo $row['supplierID']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content">
                            <form method="POST" action="../backend/supplierAuth.php">
                                <?php csrf_field(); ?>
                                <div class="modal-header">
                                    <h5 class="modal-title" style="font-weight:800;"><i class="bi bi-trash3 me-2"></i>Delete Supplier</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body" style="padding:20px 24px;">
                                    <input type="hidden" name="supplierID" value="<?php echo $row['supplierID']; ?>">
                                    <p style="font-size:13.5px;color:var(--text-dark);margin:0;">
                                        Remove <strong><?php echo htmlspecialchars($row['companyName']); ?></strong> from your supplier list? This action cannot be undone.
                                    </p>
                                </div>
                                <div class="modal-footer" style="border-top:1px solid #f7c0c6;padding:12px 20px;gap:8px;">
                                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="supplierDeleted" class="btn-delete-confirm">
                                        <i class="bi bi-trash3 me-1"></i>Delete
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Supplier Modal -->
<?php if($_SESSION['roleName'] === 'Admin'): ?>
<div class="modal fade modal-navy" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="../backend/supplierAuth.php">
                <?php csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-truck-front-fill me-2"></i>Add Supplier</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:20px 24px;">
                    <div class="mb-3">
                        <div class="form-label-sm">Company Name <span style="color:var(--danger);">*</span></div>
                        <input type="text" name="companyName" class="form-field" required placeholder="e.g. ABC Trading Co.">
                    </div>
                    <div class="mb-3">
                        <div class="form-label-sm">Contact Person <span style="color:var(--danger);">*</span></div>
                        <input type="text" name="supplierName" class="form-field" required placeholder="Full name">
                    </div>
                    <div class="mb-3">
                        <div class="form-label-sm">Email</div>
                        <input type="email" name="email" class="form-field" placeholder="supplier@email.com">
                    </div>
                    <div class="mb-3">
                        <div class="form-label-sm">Contact No</div>
                        <input type="text" name="contactNo" class="form-field" placeholder="09XX XXX XXXX">
                    </div>
                    <div class="mb-3">
                        <div class="form-label-sm">Address</div>
                        <textarea name="address" class="form-field" rows="2" placeholder="Street, City, Province"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--ice-dark);padding:12px 24px;gap:8px;">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="supplierSave" class="btn-save">
                        <i class="bi bi-plus-circle me-1"></i>Save Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
$(document).ready(function(){
    $('#supplierTable').DataTable({
        pageLength: 10,
        order: [[0, 'asc']],
        columnDefs: [{ orderable: false, targets: 5 }],
        language: {
            search: '',
            searchPlaceholder: 'Search suppliers…',
            lengthMenu: 'Show _MENU_ rows',
            info: 'Showing _START_–_END_ of _TOTAL_ suppliers',
            paginate: { previous: '‹', next: '›' }
        }
    });
});
</script>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
    const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>
</body></html>