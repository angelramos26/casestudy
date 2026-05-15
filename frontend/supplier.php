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

<div class="topbar no-print">
    <h5><i class="bi bi-truck me-2" style="color:var(--ev-purple);"></i>Supplier Management</h5>
    <div class="ms-auto"><div class="user-badge"><i class="bi bi-person-circle" style="color:var(--ev-purple);"></i><span><?php echo htmlspecialchars($_SESSION['userName']); ?></span><span class="role-pill"><?php echo htmlspecialchars($_SESSION['roleName']); ?></span></div></div>
</div>

<?php
$alerts=['savedData'=>['success','Saved!','Supplier added successfully.'],'updatedSupplier'=>['success','Updated!','Supplier information updated.'],'supplierDeleted'=>['success','Deleted!','Supplier removed.'],'emailExists'=>['error','Email Exists','This email is already registered.'],'emptyFields'=>['warning','Required Fields','Please fill in all required fields.']];
foreach($alerts as $k=>[$i,$t,$tx]) if(isset($_GET[$k])) echo "<script>Swal.fire({icon:'$i',title:'$t',text:'$tx',timer:2000}).then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";
?>

<div class="page-body">
    <div class="d-flex justify-content-between mb-3 align-items-center">
        <h5 class="fw-bold mb-0" style="color:#004a38;">Suppliers</h5>
        <?php if($_SESSION['roleName']==='Admin'): ?>
        <button class="btn btn-ev" data-bs-toggle="modal" data-bs-target="#addSupplierModal"><i class="bi bi-plus-circle me-1"></i>Add Supplier</button>
        <?php endif; ?>
    </div>

    <div class="card card-shadow">
        <div class="card-body">
            <table id="supplierTable" class="table table-bordered table-striped text-center">
                <thead style="background:var(--ev-gradient);color:#fff;">
                    <tr><th>Company</th><th>Contact Person</th><th>Email</th><th>Contact No</th><th>Address</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php
                $result = $conn->query("SELECT * FROM supplier WHERE dateDeleted IS NULL ORDER BY companyName ASC");
                while($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td class="text-start fw-semibold"><?php echo htmlspecialchars($row['companyName']); ?></td>
                    <td><?php echo htmlspecialchars($row['supplierName']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['contactNo']); ?></td>
                    <td class="text-start text-muted small"><?php echo htmlspecialchars($row['address'] ?? '—'); ?></td>
                    <td>
                        <?php if($_SESSION['roleName']==='Admin'): ?>
                        <button class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#editSupplierModal<?php echo $row['supplierID']; ?>"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteSupplierModal<?php echo $row['supplierID']; ?>"><i class="bi bi-trash"></i></button>
                        <?php else: ?>
                        <span class="text-muted small">View Only</span>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editSupplierModal<?php echo $row['supplierID']; ?>" tabindex="-1">
                  <div class="modal-dialog"><div class="modal-content">
                    <form method="POST" action="../backend/supplierAuth.php">
            <?php csrf_field(); ?>
                      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
                        <h5 class="modal-title"><i class="bi bi-pencil me-1"></i>Edit Supplier</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <input type="hidden" name="supplierID" value="<?php echo $row['supplierID']; ?>">
                        <div class="mb-3"><label class="form-label fw-semibold small">Company Name <span class="text-danger">*</span></label><input type="text" name="companyName" value="<?php echo htmlspecialchars($row['companyName']); ?>" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label fw-semibold small">Contact Person <span class="text-danger">*</span></label><input type="text" name="supplierName" value="<?php echo htmlspecialchars($row['supplierName']); ?>" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label fw-semibold small">Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" class="form-control"></div>
                        <div class="mb-3"><label class="form-label fw-semibold small">Contact No</label><input type="text" name="contactNo" value="<?php echo htmlspecialchars($row['contactNo']); ?>" class="form-control"></div>
                        <div class="mb-3"><label class="form-label fw-semibold small">Address</label><textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($row['address'] ?? ''); ?></textarea></div>
                      </div>
                      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="supplierUpdate" class="btn btn-ev">Update</button></div>
                    </form>
                  </div></div>
                </div>

                <!-- Delete Modal -->
                <div class="modal fade" id="deleteSupplierModal<?php echo $row['supplierID']; ?>" tabindex="-1">
                  <div class="modal-dialog modal-sm"><div class="modal-content">
                    <form method="POST" action="../backend/supplierAuth.php">
            <?php csrf_field(); ?>
                      <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="bi bi-trash me-1"></i>Delete Supplier</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                      <div class="modal-body">
                        <input type="hidden" name="supplierID" value="<?php echo $row['supplierID']; ?>">
                        <p>Are you sure you want to remove <strong><?php echo htmlspecialchars($row['companyName']); ?></strong>?</p>
                      </div>
                      <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" name="supplierDeleted" class="btn btn-danger btn-sm">Delete</button></div>
                    </form>
                  </div></div>
                </div>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Supplier Modal -->
<?php if($_SESSION['roleName']==='Admin'): ?>
<div class="modal fade" id="addSupplierModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/supplierAuth.php">
            <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title"><i class="bi bi-truck me-1"></i>Add Supplier</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label fw-semibold small">Company Name <span class="text-danger">*</span></label><input type="text" name="companyName" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-semibold small">Contact Person <span class="text-danger">*</span></label><input type="text" name="supplierName" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-semibold small">Email</label><input type="email" name="email" class="form-control"></div>
        <div class="mb-3"><label class="form-label fw-semibold small">Contact No</label><input type="text" name="contactNo" class="form-control"></div>
        <div class="mb-3"><label class="form-label fw-semibold small">Address</label><textarea name="address" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="supplierSave" class="btn btn-ev">Save Supplier</button></div>
    </form>
  </div></div>
</div>
<?php endif; ?>

<script>
$(document).ready(function(){ $('#supplierTable').DataTable({pageLength:10,order:[[0,'asc']}); });
</script>
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
