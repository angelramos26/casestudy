<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if(!in_array($_SESSION['roleName'], ['Admin'])){
    header("Location: dashboard.php"); exit();
}
$pageTitle = "Menu Items – Restaurant POS";

$catResult = $conn->query("SELECT categoryID, categoryName FROM category ORDER BY categoryName");
$categories = [];
while($row = $catResult->fetch_assoc()) $categories[] = $row;

$menuResult = $conn->query("
    SELECT mi.itemID, mi.itemName, mi.price, mi.cost, mi.description,
           mi.is_available, mi.status, mi.item_image, c.categoryName, c.categoryID
    FROM menu_item mi
    JOIN category c ON mi.categoryID = c.categoryID
    ORDER BY c.categoryName, mi.itemName
");
$menuItems = [];
while($row = $menuResult->fetch_assoc()) $menuItems[] = $row;
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<style>
.menu-mgr-body { padding: 24px 28px; }

.toolbar { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.toolbar .search-field {
    flex: 1; min-width: 200px; max-width: 320px;
    border: 1.5px solid var(--s-border); border-radius: var(--r-sm);
    padding: 8px 14px; font-size: 13px; font-family: var(--font-ui);
    outline: none; background: var(--c-white); color: var(--t-main);
}
.toolbar .search-field:focus { border-color: var(--charcoal); }

.filter-sel {
    border: 1.5px solid var(--s-border); border-radius: var(--r-sm);
    padding: 8px 12px; font-size: 13px; font-family: var(--font-ui);
    outline: none; background: var(--c-white); color: var(--t-main);
    min-width: 140px;
}

/* ── Menu Cards Grid ── */
.menu-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
}

.menu-mgr-card {
    background: var(--c-white);
    border-radius: var(--r-md);
    border: 1.5px solid var(--s-border);
    box-shadow: var(--sh-sm);
    overflow: hidden;
    transition: all .2s;
}
.menu-mgr-card:hover { transform: translateY(-2px); box-shadow: var(--sh-md); }
.menu-mgr-card.inactive { opacity: .55; }

.card-img-wrap {
    height: 130px; background: var(--s-bg);
    display: flex; align-items: center; justify-content: center;
    position: relative; overflow: hidden;
}
.card-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
.card-img-wrap .no-img { font-size: 3rem; color: var(--s-border); }

.avail-badge {
    position: absolute; top: 8px; left: 8px;
    font-size: 9.5px; font-weight: 800; padding: 3px 8px;
    border-radius: 6px; letter-spacing: .5px;
    text-transform: uppercase;
}
.avail-badge.yes { background: var(--c-success); color: #fff; }
.avail-badge.no  { background: var(--c-danger); color: #fff; }

/* Status badge (top-right) */
.status-badge {
    position: absolute; top: 8px; right: 8px;
    font-size: 9.5px; font-weight: 800; padding: 3px 8px;
    border-radius: 6px; letter-spacing: .5px;
    text-transform: uppercase;
}
.status-badge.active   { background: #d1fae5; color: #065f46; }
.status-badge.inactive { background: #fff3cd; color: #92400e; }

.card-body { padding: 12px 14px 10px; }
.card-name { font-size: 14px; font-weight: 800; color: var(--t-main); margin-bottom: 2px; }
.card-cat  { font-size: 10.5px; color: var(--t-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
.card-desc { font-size: 11.5px; color: var(--t-mid); margin-bottom: 8px; line-height: 1.4; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.card-price-row { display: flex; align-items: center; justify-content: space-between; }
.card-price { font-size: 15px; font-weight: 900; color: var(--charcoal); font-family: var(--font-mono); }
.card-cost  { font-size: 11px; color: var(--t-muted); font-weight: 600; }

.card-footer { display: flex; gap: 6px; padding: 10px 14px; border-top: 1px solid var(--s-border); flex-wrap: nowrap; }
.btn-card-action {
    flex: 1; border: none; border-radius: 6px; padding: 7px 4px;
    font-size: 10.5px; font-weight: 700; cursor: pointer; transition: all .15s;
    display: flex; align-items: center; justify-content: center; gap: 4px;
    min-width: 0; white-space: nowrap;
}
.btn-card-edit   { background: var(--charcoal); color: var(--orange); }
.btn-card-edit:hover { background: var(--charcoal-mid); }
.btn-card-toggle { background: var(--s-bg); color: var(--t-mid); border: 1.5px solid var(--s-border); }
.btn-card-toggle:hover { border-color: var(--charcoal); color: var(--charcoal); }
.btn-card-delete { background: var(--c-danger-bg); color: var(--c-danger); border: 1.5px solid var(--c-danger-bg); }
.btn-card-delete:hover { background: var(--c-danger); color: #fff; }

/* Status toggle button */
.btn-status-active {
    background: #d1fae5; color: #065f46;
    border: 1.5px solid #a7f3d0;
}
.btn-status-active:hover { background: #059669; color: #fff; }
.btn-status-inactive {
    background: #fff3cd; color: #92400e;
    border: 1.5px solid #fcd34d;
}
.btn-status-inactive:hover { background: #d97706; color: #fff; }
</style>

<?php
$topbarTitle = 'Menu Items';
$topbarIcon  = 'bi-journal-text';
$topbarSub   = 'Manage your restaurant menu';
$canAddItem  = $_SESSION['roleName'] === 'Admin';
$topbarExtra = $canAddItem
    ? '<button class="btn-rpos-primary" onclick="openAddModal()" style="font-size:12px;padding:6px 14px;"><i class="bi bi-plus-circle me-1"></i>Add Item</button>'
    : '';
include 'topbar.php';
?>

<div class="page-body menu-mgr-body">

    <!-- Toolbar -->
    <div class="toolbar">
        <input type="text" class="search-field" id="menuSearch" placeholder="Search menu items…" oninput="filterCards()">
        <select class="filter-sel" id="catFilter" onchange="filterCards()">
            <option value="">All Categories</option>
            <?php foreach($categories as $c): ?>
            <option value="<?= htmlspecialchars($c['categoryName']) ?>"><?= htmlspecialchars($c['categoryName']) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="filter-sel" id="availFilter" onchange="filterCards()">
            <option value="">All Status</option>
            <option value="1">Available</option>
            <option value="0">86'd</option>
        </select>
        <select class="filter-sel" id="statusFilter" onchange="filterCards()">
            <option value="">Active & Inactive</option>
            <option value="Active">Active Only</option>
            <option value="Inactive">Inactive Only</option>
        </select>
        <span id="countBadge" style="font-size:12px;color:var(--t-muted);font-weight:700;"></span>
    </div>

    <!-- Cards -->
    <div class="menu-cards-grid" id="menuCardsGrid">
        <?php foreach($menuItems as $item): ?>
        <div class="menu-mgr-card <?= $item['status'] !== 'Active' ? 'inactive' : '' ?>"
             data-name="<?= strtolower(htmlspecialchars($item['itemName'])) ?>"
             data-cat="<?= htmlspecialchars($item['categoryName']) ?>"
             data-avail="<?= $item['is_available'] ?>"
             data-status="<?= $item['status'] ?>">

            <div class="card-img-wrap">
                <?php if($item['item_image'] && file_exists('../' . $item['item_image'])): ?>
                <img src="../<?= htmlspecialchars($item['item_image']) ?>" alt="<?= htmlspecialchars($item['itemName']) ?>">
                <?php else: ?>
                <span class="no-img">🍽️</span>
                <?php endif; ?>

                <!-- Availability badge (left) -->
                <span class="avail-badge <?= $item['is_available'] ? 'yes' : 'no' ?>">
                    <?= $item['is_available'] ? 'Available' : "86'd" ?>
                </span>

                <!-- Status badge (right) -->
                <span class="status-badge <?= $item['status'] === 'Active' ? 'active' : 'inactive' ?>">
                    <?= $item['status'] ?>
                </span>
            </div>

            <div class="card-body">
                <div class="card-name"><?= htmlspecialchars($item['itemName']) ?></div>
                <div class="card-cat"><?= htmlspecialchars($item['categoryName']) ?></div>
                <?php if($item['description']): ?>
                <div class="card-desc"><?= htmlspecialchars($item['description']) ?></div>
                <?php endif; ?>
                <div class="card-price-row">
                    <span class="card-price">₱<?= number_format($item['price'], 2) ?></span>
                    <?php if($item['cost']): ?>
                    <span class="card-cost">Cost: ₱<?= number_format($item['cost'], 2) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-footer">
                <!-- Edit -->
                <button class="btn-card-action btn-card-edit" onclick='openEditModal(<?= json_encode($item) ?>)'>
                    <i class="bi bi-pencil"></i> Edit
                </button>

                <!-- 86'd toggle -->
                <button class="btn-card-action btn-card-toggle"
                        onclick="toggleAvailability(<?= $item['itemID'] ?>, <?= $item['is_available'] ?>)"
                        title="<?= $item['is_available'] ? "Mark as 86'd" : 'Mark Available' ?>">
                    <i class="bi <?= $item['is_available'] ? 'bi-x-circle' : 'bi-check-circle' ?>"></i>
                    <?= $item['is_available'] ? "86'd" : 'Avail' ?>
                </button>

                <!-- Active / Inactive toggle -->
                <button class="btn-card-action <?= $item['status'] === 'Active' ? 'btn-status-active' : 'btn-status-inactive' ?>"
                        onclick="toggleStatus(<?= $item['itemID'] ?>, '<?= $item['status'] ?>')"
                        title="<?= $item['status'] === 'Active' ? 'Deactivate item' : 'Reactivate item' ?>">
                    <i class="bi <?= $item['status'] === 'Active' ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i>
                    <?= $item['status'] === 'Active' ? 'Active' : 'Inactive' ?>
                </button>

                <!-- Delete -->
                <button class="btn-card-action btn-card-delete"
                        onclick="deleteItem(<?= $item['itemID'] ?>, '<?= addslashes(htmlspecialchars($item['itemName'])) ?>')"
                        style="flex:0.4;">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
        <div class="modal-content" style="border:none;border-radius:18px;overflow:hidden;">
            <div class="modal-header" style="background:var(--charcoal);border:none;padding:16px 22px;">
                <h5 class="modal-title" style="font-weight:800;color:#fff;" id="itemModalTitle">
                    <i class="bi bi-journal-text me-2" style="color:var(--orange);"></i>Menu Item
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="itemForm" method="POST" action="../backend/menuItemAuth.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="itemID" id="formItemID">
                <input type="hidden" name="itemSave" id="formActionField" value="1">
                <div class="modal-body" style="padding:22px;">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" style="font-size:12px;font-weight:700;color:var(--t-mid);">Item Name *</label>
                            <input type="text" name="itemName" id="formItemName" class="form-control" required placeholder="e.g. Grilled Chicken">
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-size:12px;font-weight:700;color:var(--t-mid);">Category *</label>
                            <select name="categoryID" id="formCategoryID" class="form-select" required>
                                <option value="">Select…</option>
                                <?php foreach($categories as $c): ?>
                                <option value="<?= $c['categoryID'] ?>"><?= htmlspecialchars($c['categoryName']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-size:12px;font-weight:700;color:var(--t-mid);">Status</label>
                            <select name="status" id="formStatus" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-size:12px;font-weight:700;color:var(--t-mid);">Price (₱) *</label>
                            <input type="number" name="price" id="formPrice" class="form-control" step="0.01" min="0" required placeholder="0.00">
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-size:12px;font-weight:700;color:var(--t-mid);">Cost (₱)</label>
                            <input type="number" name="cost" id="formCost" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="col-12">
                            <label class="form-label" style="font-size:12px;font-weight:700;color:var(--t-mid);">Description</label>
                            <textarea name="description" id="formDescription" class="form-control" rows="2" placeholder="Brief description…"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="is_available" id="formAvailable" class="form-check-input" value="1" checked>
                                <label class="form-check-label" for="formAvailable" style="font-size:13px;font-weight:700;">
                                    Available (show in POS)
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" style="font-size:12px;font-weight:700;color:var(--t-mid);">Item Photo</label>
                            <input type="file" name="item_image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1.5px solid var(--s-border);padding:14px 22px;">
                    <button type="button" class="btn-rpos-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="formSubmitBtn" class="btn-rpos-primary">
                        <i class="bi bi-check2-circle me-1"></i>Save Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

// ── Filter ──────────────────────────────────────────────────
function filterCards() {
    const q      = document.getElementById('menuSearch').value.toLowerCase();
    const cat    = document.getElementById('catFilter').value;
    const avail  = document.getElementById('availFilter').value;
    const status = document.getElementById('statusFilter').value;
    let count = 0;
    document.querySelectorAll('.menu-mgr-card').forEach(card => {
        const matchQ  = !q      || card.dataset.name.includes(q);
        const matchC  = !cat    || card.dataset.cat === cat;
        const matchA  = avail  === '' || card.dataset.avail === avail;
        const matchS  = status === '' || card.dataset.status === status;
        const show    = matchQ && matchC && matchA && matchS;
        card.style.display = show ? '' : 'none';
        if(show) count++;
    });
    document.getElementById('countBadge').textContent = count + ' item(s)';
}
filterCards();

// ── Add Modal ───────────────────────────────────────────────
function openAddModal() {
    document.getElementById('itemModalTitle').innerHTML = '<i class="bi bi-plus-circle me-2" style="color:var(--orange);"></i>Add Menu Item';
    document.getElementById('itemForm').reset();
    document.getElementById('formItemID').value = '';
    document.getElementById('formSubmitBtn').innerHTML = '<i class="bi bi-check2-circle me-1"></i>Add Item';
    document.getElementById('formActionField').name  = 'itemSave';
    document.getElementById('formActionField').value = '1';
    new bootstrap.Modal(document.getElementById('itemModal')).show();
}

// ── Edit Modal ──────────────────────────────────────────────
function openEditModal(item) {
    document.getElementById('itemModalTitle').innerHTML = '<i class="bi bi-pencil me-2" style="color:var(--orange);"></i>Edit Menu Item';
    document.getElementById('formItemID').value       = item.itemID;
    document.getElementById('formItemName').value     = item.itemName;
    document.getElementById('formCategoryID').value   = item.categoryID;
    document.getElementById('formPrice').value        = item.price;
    document.getElementById('formCost').value         = item.cost || '';
    document.getElementById('formDescription').value  = item.description || '';
    document.getElementById('formStatus').value       = item.status;
    document.getElementById('formAvailable').checked  = item.is_available == 1;
    document.getElementById('formSubmitBtn').innerHTML = '<i class="bi bi-check2-circle me-1"></i>Update Item';
    document.getElementById('formActionField').name   = 'itemUpdate';
    document.getElementById('formActionField').value  = '1';
    new bootstrap.Modal(document.getElementById('itemModal')).show();
}

// ── Toggle Availability (86'd) ──────────────────────────────
function toggleAvailability(itemID, current) {
    const newAvail = current == 1 ? 0 : 1;
    const fd = new FormData();
    fd.append('itemToggleAvailable', 1);
    fd.append('itemID', itemID);
    fd.append('is_available', newAvail);
    fd.append('csrf_token', csrf());
    fetch('../backend/menuItemAuth.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.success) location.reload();
        else Swal.fire({ icon: 'error', title: 'Error', text: d.message });
    });
}

// ── Toggle Status (Active ↔ Inactive) ──────────────────────
function toggleStatus(itemID, currentStatus) {
    const newStatus = currentStatus === 'Active' ? 'Inactive' : 'Active';
    const isReactivating = newStatus === 'Active';

    Swal.fire({
        title: isReactivating ? 'Reactivate this item?' : 'Deactivate this item?',
        text: isReactivating
            ? 'This item will be marked Active and available in the POS.'
            : 'This item will be hidden from the POS menu.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: isReactivating ? '#059669' : '#d97706',
        confirmButtonText: isReactivating ? 'Reactivate' : 'Deactivate'
    }).then(r => {
        if (r.isConfirmed) {
            const fd = new FormData();
            fd.append('itemToggleStatus', 1);
            fd.append('itemID', itemID);
            fd.append('status', newStatus);
            fd.append('csrf_token', csrf());
            fetch('../backend/menuItemAuth.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    Swal.fire({
                        icon: 'success',
                        title: isReactivating ? 'Item Reactivated!' : 'Item Deactivated!',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: d.message });
                }
            });
        }
    });
}

// ── Delete ──────────────────────────────────────────────────
function deleteItem(itemID, name) {
    Swal.fire({
        title: 'Remove "' + name + '"?',
        text: 'This will deactivate the item from the menu.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: 'var(--c-danger)',
        confirmButtonText: 'Remove Item'
    }).then(r => {
        if (r.isConfirmed) {
            const fd = new FormData();
            fd.append('itemDelete', 1);
            fd.append('itemID', itemID);
            fd.append('csrf_token', csrf());
            fetch('../backend/menuItemAuth.php', { method: 'POST', body: fd })
            .then(() => location.reload());
        }
    });
}

// ── URL message handlers ────────────────────────────────────
const params = new URLSearchParams(location.search);
if(params.has('savedData'))          Swal.fire({ icon:'success', title:'Item saved!',        timer:1500, showConfirmButton:false });
if(params.has('updatedItem'))        Swal.fire({ icon:'success', title:'Item updated!',      timer:1500, showConfirmButton:false });
if(params.has('itemDeleted'))        Swal.fire({ icon:'success', title:'Item removed!',      timer:1500, showConfirmButton:false });
if(params.has('availabilityUpdated'))Swal.fire({ icon:'success', title:'Availability updated!', timer:1500, showConfirmButton:false });
if(params.has('emptyFields'))        Swal.fire({ icon:'warning', title:'Fill all required fields.' });
</script>
<?php include 'footer.php'; ?>
</body></html>