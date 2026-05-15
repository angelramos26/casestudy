<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';
session_start();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if($_SESSION['roleName'] !== 'Admin'){ header("Location: dashboard.php"); exit(); }
$pageTitle = "Settings – 7Evelyn POS";
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<div class="topbar no-print">
    <h5><i class="bi bi-gear me-2" style="color:var(--ev-purple);"></i>Settings</h5>
    <div class="ms-auto">
        <div class="user-badge">
            <i class="bi bi-person-circle" style="color:var(--ev-purple);"></i>
            <span><?php echo htmlspecialchars($_SESSION['userName']); ?></span>
            <span class="role-pill"><?php echo htmlspecialchars($_SESSION['roleName']); ?></span>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="row g-4">

        <!-- Store Branding -->
        <div class="col-lg-6">
            <div class="card card-shadow h-100">
                <div class="card-header d-flex align-items-center gap-2" style="background:var(--ev-gradient);color:#fff;border-radius:14px 14px 0 0;">
                    <i class="bi bi-shop-window fs-5"></i>
                    <span class="fw-bold">Store Branding</span>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Customize how your store appears across the POS system. Changes are saved locally on this device.</p>

                    <!-- Logo Upload -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Store Logo</label>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div id="logoPreviewWrap" style="width:80px;height:80px;border-radius:14px;overflow:hidden;border:2px dashed #ccc;display:flex;align-items:center;justify-content:center;background:#f8f8f8;flex-shrink:0;">
                                <i class="bi bi-image text-muted fs-4" id="logoPlaceholderIcon"></i>
                                <img id="logoPreview" src="" alt="Logo" style="width:100%;height:100%;object-fit:cover;display:none;border-radius:12px;">
                            </div>
                            <div class="flex-grow-1">
                                <input type="file" id="logoFileInput" accept="image/*" class="form-control form-control-sm mb-2">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-ev btn-sm" onclick="saveLogo()"><i class="bi bi-check-lg me-1"></i>Save Logo</button>
                                    <button class="btn btn-outline-danger btn-sm" onclick="removeLogo()"><i class="bi bi-trash me-1"></i>Remove</button>
                                </div>
                            </div>
                        </div>
                        <div class="text-muted" style="font-size:11px;">Recommended: square image, max 2MB. Shown in sidebar and on receipts.</div>
                    </div>

                    <!-- Store Name -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Store / Business Name</label>
                        <input type="text" id="storeNameInput" class="form-control" placeholder="e.g. 7Evelyn Store" maxlength="60">
                        <div class="form-text">Displayed in the sidebar and on printed receipts.</div>
                    </div>

                    <!-- Store Tagline -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Tagline / Address (Receipt)</label>
                        <input type="text" id="storeTaglineInput" class="form-control" placeholder="e.g. Brgy. Poblacion, Angat, Bulacan" maxlength="80">
                        <div class="form-text">Appears below the store name on receipts.</div>
                    </div>

                    <button class="btn btn-ev w-100" onclick="saveBranding()">
                        <i class="bi bi-floppy me-1"></i>Save Branding
                    </button>
                </div>
            </div>
        </div>

        <!-- Receipt Preferences -->
        <div class="col-lg-6">
            <div class="card card-shadow mb-4">
                <div class="card-header d-flex align-items-center gap-2" style="background:var(--ev-gradient);color:#fff;border-radius:14px 14px 0 0;">
                    <i class="bi bi-receipt fs-5"></i>
                    <span class="fw-bold">Receipt Preferences</span>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Configure what appears on your printed receipts.</p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Footer Message</label>
                        <input type="text" id="receiptFooterInput" class="form-control" placeholder="e.g. Thank you for shopping with us!" maxlength="100">
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="showTaglineToggle" role="switch">
                            <label class="form-check-label small fw-semibold" for="showTaglineToggle">Show tagline/address on receipt</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="showLogoReceiptToggle" role="switch" checked>
                            <label class="form-check-label small fw-semibold" for="showLogoReceiptToggle">Show logo on receipt</label>
                        </div>
                    </div>

                    <button class="btn btn-ev w-100" onclick="saveReceiptPrefs()">
                        <i class="bi bi-floppy me-1"></i>Save Receipt Settings
                    </button>
                </div>
            </div>

            <!-- System Info -->
            <div class="card card-shadow">
                <div class="card-header d-flex align-items-center gap-2" style="background:linear-gradient(135deg,#003d2e,#005e47);color:#fff;border-radius:14px 14px 0 0;">
                    <i class="bi bi-info-circle fs-5"></i>
                    <span class="fw-bold">System Information</span>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><td class="text-muted small">System</td><td class="fw-semibold small">7Evelyn POS</td></tr>
                            <tr><td class="text-muted small">Logged In As</td><td class="fw-semibold small"><?php echo htmlspecialchars($_SESSION['userName']); ?></td></tr>
                            <tr><td class="text-muted small">Role</td><td><span class="role-pill"><?php echo htmlspecialchars($_SESSION['roleName']); ?></span></td></tr>
                            <tr><td class="text-muted small">Server Date</td><td class="fw-semibold small"><?php echo date('F d, Y'); ?></td></tr>
                            <tr><td class="text-muted small">PHP Version</td><td class="fw-semibold small"><?php echo phpversion(); ?></td></tr>
                            <tr><td class="text-muted small">Database</td><td class="fw-semibold small">MySQL (connected)</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="col-12">
            <div class="card card-shadow border border-danger">
                <div class="card-header d-flex align-items-center gap-2 bg-danger text-white" style="border-radius:14px 14px 0 0;">
                    <i class="bi bi-exclamation-triangle fs-5"></i>
                    <span class="fw-bold">Danger Zone</span>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <div class="fw-semibold small">Clear Local Settings</div>
                            <div class="text-muted" style="font-size:12px;">Removes logo, store name, and all local preferences from this browser. Cannot be undone.</div>
                        </div>
                        <button class="btn btn-outline-danger btn-sm" onclick="clearAllSettings()">
                            <i class="bi bi-trash me-1"></i>Clear All Local Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// ── Load saved settings on page load ──────────────────────────────────────
document.addEventListener('DOMContentLoaded', function(){
    // Logo
    const logo = localStorage.getItem('ev_store_logo');
    if(logo) showLogoPreview(logo);

    // Store name
    const name = localStorage.getItem('ev_store_name');
    if(name) document.getElementById('storeNameInput').value = name;

    // Tagline
    const tagline = localStorage.getItem('ev_store_tagline');
    if(tagline) document.getElementById('storeTaglineInput').value = tagline;

    // Receipt prefs
    const footer = localStorage.getItem('ev_receipt_footer');
    if(footer) document.getElementById('receiptFooterInput').value = footer;

    document.getElementById('showTaglineToggle').checked =
        localStorage.getItem('ev_show_tagline') !== 'false';
    document.getElementById('showLogoReceiptToggle').checked =
        localStorage.getItem('ev_show_logo_receipt') !== 'false';
});

// ── Logo handling ──────────────────────────────────────────────────────────
document.getElementById('logoFileInput').addEventListener('change', function(){
    const file = this.files[0];
    if(!file) return;
    if(file.size > 2 * 1024 * 1024){
        Swal.fire({icon:'warning',title:'File too large',text:'Please use an image under 2MB.',timer:2500});
        this.value = ''; return;
    }
    const reader = new FileReader();
    reader.onload = e => showLogoPreview(e.target.result);
    reader.readAsDataURL(file);
});

function showLogoPreview(src){
    document.getElementById('logoPreview').src = src;
    document.getElementById('logoPreview').style.display = 'block';
    document.getElementById('logoPlaceholderIcon').style.display = 'none';
}

function saveLogo(){
    const src = document.getElementById('logoPreview').src;
    if(!src || document.getElementById('logoPreview').style.display === 'none'){
        Swal.fire({icon:'warning',title:'No logo selected',text:'Please choose an image first.',timer:2000});
        return;
    }
    localStorage.setItem('ev_store_logo', src);
    // Update sidebar logo immediately
    const sidebarImg = document.getElementById('sidebarLogoImg');
    const sidebarIcon = document.getElementById('sidebarLogoIcon');
    if(sidebarImg){ sidebarImg.src = src; sidebarImg.style.display='block'; }
    if(sidebarIcon){ sidebarIcon.style.display='none'; }
    Swal.fire({icon:'success',title:'Logo Saved!',text:'Your logo has been saved.',timer:1500,showConfirmButton:false});
}

function removeLogo(){
    localStorage.removeItem('ev_store_logo');
    document.getElementById('logoPreview').style.display = 'none';
    document.getElementById('logoPreview').src = '';
    document.getElementById('logoPlaceholderIcon').style.display = '';
    document.getElementById('logoFileInput').value = '';
    // Reset sidebar logo
    const sidebarImg = document.getElementById('sidebarLogoImg');
    const sidebarIcon = document.getElementById('sidebarLogoIcon');
    if(sidebarImg){ sidebarImg.style.display='none'; }
    if(sidebarIcon){ sidebarIcon.style.display=''; }
    Swal.fire({icon:'info',title:'Logo Removed',timer:1500,showConfirmButton:false});
}

// ── Save branding ──────────────────────────────────────────────────────────
function saveBranding(){
    const name    = document.getElementById('storeNameInput').value.trim();
    const tagline = document.getElementById('storeTaglineInput').value.trim();

    if(name) localStorage.setItem('ev_store_name', name);
    else localStorage.removeItem('ev_store_name');

    if(tagline) localStorage.setItem('ev_store_tagline', tagline);
    else localStorage.removeItem('ev_store_tagline');

    // Update sidebar brand name immediately
    const brandEl = document.getElementById('sidebarBrandName');
    if(brandEl) brandEl.textContent = name || '7Evelyn';

    Swal.fire({icon:'success',title:'Branding Saved!',timer:1500,showConfirmButton:false});
}

// ── Save receipt prefs ─────────────────────────────────────────────────────
function saveReceiptPrefs(){
    const footer      = document.getElementById('receiptFooterInput').value.trim();
    const showTagline = document.getElementById('showTaglineToggle').checked;
    const showLogo    = document.getElementById('showLogoReceiptToggle').checked;

    if(footer) localStorage.setItem('ev_receipt_footer', footer);
    else localStorage.removeItem('ev_receipt_footer');

    localStorage.setItem('ev_show_tagline', showTagline);
    localStorage.setItem('ev_show_logo_receipt', showLogo);

    Swal.fire({icon:'success',title:'Receipt Settings Saved!',timer:1500,showConfirmButton:false});
}

// ── Clear all ──────────────────────────────────────────────────────────────
function clearAllSettings(){
    Swal.fire({
        icon:'warning',
        title:'Clear all local settings?',
        text:'This will remove your logo, store name, and all preferences from this browser.',
        showCancelButton:true,
        confirmButtonColor:'#f01b2d',
        confirmButtonText:'Yes, clear all',
        cancelButtonText:'Cancel'
    }).then(r => {
        if(!r.isConfirmed) return;
        ['ev_store_logo','ev_store_name','ev_store_tagline','ev_receipt_footer','ev_show_tagline','ev_show_logo_receipt']
            .forEach(k => localStorage.removeItem(k));
        Swal.fire({icon:'success',title:'Cleared!',timer:1500,showConfirmButton:false})
            .then(() => location.reload());
    });
}
</script>

<!-- ── Pusher Real-time ───────────────────────────────────────────────────── -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
    const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>
</body></html>
