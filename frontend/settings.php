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

<style>

/* ── Page Layout ───────────────────────────────────────────────────────── */



.user-badge {
    display: flex; align-items: center; gap: .55rem;
    background: var(--mint);
    border: 1.5px solid var(--mint-dark);
    border-radius: 50px;
    padding: .32rem .8rem .32rem .5rem;
    font-size: .78rem;
    color: var(--navy);
    font-weight: 600;
}
.user-badge i { color: var(--navy-light); font-size: 1.1rem; }
.role-pill {
    background: var(--navy);
    color: var(--gold);
    font-size: .68rem;
    font-weight: 700;
    padding: .15rem .55rem;
    border-radius: 50px;
    letter-spacing: .04em;
    text-transform: uppercase;
}

/* ── Page Body ─────────────────────────────────────────────────────────── */
.settings-body {
    padding: 2rem 1.8rem;
    max-width: 1100px;
    margin: 0 auto;
}

/* ── Section Label ─────────────────────────────────────────────────────── */
.section-label {
    display: flex;
    align-items: center;
    gap: .5rem;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 1rem;
    margin-top: .25rem;
}
.section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border);
    margin-left: .5rem;
}

/* ── Settings Card ─────────────────────────────────────────────────────── */
.s-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    border: 1.5px solid var(--border);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    transition: var(--transition);
    margin-bottom: 1.4rem;
}
.s-card:hover {
    box-shadow: var(--shadow-md);
    border-color: #d9d5f0;
}
.s-card-header {
    display: flex;
    align-items: center;
    gap: .7rem;
    padding: 1rem 1.4rem;
    border-bottom: 1.5px solid var(--border);
    background: var(--mint);
}
.s-card-header .hdr-icon {
    width: 36px; height: 36px;
    background: var(--gold);
    border-radius: var(--radius-sm);
    display: flex; align-items: center; justify-content: center;
    color: var(--navy);
    font-size: 1.05rem;
    flex-shrink: 0;
}
.s-card-header .hdr-title {
    font-size: .92rem;
    font-weight: 700;
    color: var(--navy);
    letter-spacing: -.01em;
}
.s-card-header .hdr-desc {
    font-size: .75rem;
    color: var(--text-muted);
    margin-top: .05rem;
}
.s-card-body {
    padding: 1.5rem 1.4rem;
}

/* ── Logo Upload Zone ──────────────────────────────────────────────────── */
.logo-upload-zone {
    display: flex;
    align-items: center;
    gap: 1.2rem;
    padding: 1.1rem 1.3rem;
    background: var(--mint);
    border-radius: var(--radius-md);
    border: 1.5px dashed var(--mint-dark);
    margin-bottom: 1.2rem;
    transition: var(--transition);
}
.logo-upload-zone:hover {
    border-color: var(--gold-dark);
    background: var(--gold-soft);
}
.logo-thumb {
    width: 72px; height: 72px;
    border-radius: var(--radius-md);
    border: 2px solid var(--border);
    background: var(--white);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}
.logo-thumb img { width: 100%; height: 100%; object-fit: cover; border-radius: 10px; }
.logo-thumb i { font-size: 1.6rem; color: var(--text-light); }
.logo-actions { flex: 1; }
.logo-actions .logo-hint {
    font-size: .72rem;
    color: var(--text-muted);
    margin-top: .5rem;
}

/* ── Form Controls ─────────────────────────────────────────────────────── */
.field-group { margin-bottom: 1.1rem; }
.field-group label {
    display: block;
    font-size: .78rem;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: .35rem;
    letter-spacing: -.005em;
}
.field-group .form-control {
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    padding: .58rem .85rem;
    font-size: .85rem;
    color: var(--text-main);
    background: var(--white);
    transition: var(--transition);
    box-shadow: none;
}
.field-group .form-control:focus {
    border-color: var(--gold-dark);
    box-shadow: 0 0 0 3px rgba(249,217,74,.18);
    outline: none;
}
.field-group .field-hint {
    font-size: .72rem;
    color: var(--text-muted);
    margin-top: .3rem;
}

/* ── Toggle Row ────────────────────────────────────────────────────────── */
.toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .8rem 1rem;
    border-radius: var(--radius-sm);
    background: var(--mint);
    border: 1.5px solid var(--mint-dark);
    margin-bottom: .7rem;
    gap: 1rem;
}
.toggle-row-text .toggle-title {
    font-size: .83rem;
    font-weight: 700;
    color: var(--navy);
}
.toggle-row-text .toggle-sub {
    font-size: .72rem;
    color: var(--text-muted);
    margin-top: .05rem;
}
.form-check-input[type=checkbox] {
    width: 2.2em;
    height: 1.2em;
    background-color: #ccc;
    border: none;
    cursor: pointer;
    border-radius: 50px;
    flex-shrink: 0;
}
.form-check-input[type=checkbox]:checked {
    background-color: var(--navy);
    border-color: var(--navy);
}
.form-check-input:focus { box-shadow: 0 0 0 3px rgba(38,35,65,.12); }

/* ── Buttons ───────────────────────────────────────────────────────────── */
.btn-gold {
    background: var(--gold);
    color: var(--navy);
    border: 1.5px solid var(--gold-dark);
    border-radius: var(--radius-sm);
    font-size: .82rem;
    font-weight: 700;
    padding: .55rem 1.1rem;
    transition: var(--transition);
    display: inline-flex; align-items: center; gap: .4rem;
    cursor: pointer;
    letter-spacing: -.01em;
}
.btn-gold:hover {
    background: var(--gold-dark);
    color: var(--navy);
    box-shadow: 0 4px 14px rgba(249,217,74,.4);
    transform: translateY(-1px);
}
.btn-gold-outline {
    background: transparent;
    color: var(--navy);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: .82rem;
    font-weight: 600;
    padding: .55rem 1.1rem;
    transition: var(--transition);
    display: inline-flex; align-items: center; gap: .4rem;
    cursor: pointer;
}
.btn-gold-outline:hover {
    border-color: var(--navy);
    background: var(--mint);
    color: var(--navy);
}
.btn-danger-outline {
    background: transparent;
    color: #d93025;
    border: 1.5px solid #f4c2bf;
    border-radius: var(--radius-sm);
    font-size: .82rem;
    font-weight: 600;
    padding: .55rem 1.1rem;
    transition: var(--transition);
    display: inline-flex; align-items: center; gap: .4rem;
    cursor: pointer;
}
.btn-danger-outline:hover {
    background: #fff0ef;
    border-color: #d93025;
    color: #d93025;
    box-shadow: 0 4px 12px rgba(217,48,37,.12);
}
.btn-save-full {
    width: 100%;
    justify-content: center;
    margin-top: .5rem;
}

/* ── System Info Table ─────────────────────────────────────────────────── */
.sysinfo-table { width: 100%; border-collapse: collapse; }
.sysinfo-table tr { border-bottom: 1px solid var(--border); }
.sysinfo-table tr:last-child { border-bottom: none; }
.sysinfo-table td { padding: .62rem .25rem; font-size: .82rem; vertical-align: middle; }
.sysinfo-table td:first-child { color: var(--text-muted); width: 130px; font-weight: 500; }
.sysinfo-table td:last-child { font-weight: 600; color: var(--navy); }
.status-dot {
    display: inline-flex; align-items: center; gap: .35rem;
}
.status-dot::before {
    content: '';
    width: 7px; height: 7px;
    border-radius: 50%;
    background: #1ab26b;
    display: inline-block;
}

/* ── Danger Zone ───────────────────────────────────────────────────────── */
.danger-card {
    background: #fff8f8;
    border: 1.5px solid #f4c2bf;
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}
.danger-header {
    display: flex;
    align-items: center;
    gap: .7rem;
    padding: .9rem 1.4rem;
    background: #fff0ef;
    border-bottom: 1.5px solid #f4c2bf;
}
.danger-header .hdr-icon {
    width: 34px; height: 34px;
    background: #f4c2bf;
    border-radius: var(--radius-sm);
    display: flex; align-items: center; justify-content: center;
    color: #d93025; font-size: 1rem;
}
.danger-header .hdr-title { font-size: .9rem; font-weight: 700; color: #d93025; }
.danger-body {
    padding: 1.2rem 1.4rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    flex-wrap: wrap;
}
.danger-desc .danger-title { font-size: .85rem; font-weight: 700; color: var(--navy); }
.danger-desc .danger-sub { font-size: .75rem; color: var(--text-muted); margin-top: .2rem; }

/* ── Divider ───────────────────────────────────────────────────────────── */
.settings-divider {
    border: none;
    border-top: 1.5px solid var(--border);
    margin: .5rem 0 1.2rem;
}

/* ── Two-col grid ──────────────────────────────────────────────────────── */
.settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.4rem;
}
@media(max-width:768px){
    .settings-grid { grid-template-columns: 1fr; }
    .settings-body { padding: 1.2rem 1rem; }
}

/* ── File input ────────────────────────────────────────────────────────── */
input[type="file"].form-control-sm {
    font-size: .78rem;
    padding: .3rem .6rem;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--white);
    color: var(--text-main);
    cursor: pointer;
    transition: var(--transition);
}
input[type="file"].form-control-sm:focus {
    border-color: var(--gold-dark);
    outline: none;
}
</style>

<!-- ── Topbar ──────────────────────────────────────────────────────────────── -->
<?php
$topbarTitle = 'Settings';
$topbarIcon  = 'bi-gear-fill';
$topbarSub   = 'Configure store preferences and system options';
include 'topbar.php';
?>

<!-- ── Page Body ──────────────────────────────────────────────────────────── -->
<div class="settings-body">

    <!-- Row 1: Branding + Receipt & System Info -->
    <div class="settings-grid">

        <!-- Store Branding -->
        <div>
            <div class="section-label"><i class="bi bi-palette2"></i> Appearance</div>
            <div class="s-card">
                <div class="s-card-header">
                    <div class="hdr-icon"><i class="bi bi-shop-window"></i></div>
                    <div>
                        <div class="hdr-title">Store Branding</div>
                        <div class="hdr-desc">Logo, name, and tagline</div>
                    </div>
                </div>
                <div class="s-card-body">

                    <!-- Logo upload -->
                    <div class="logo-upload-zone">
                        <div class="logo-thumb" id="logoPreviewWrap">
                            <i class="bi bi-image" id="logoPlaceholderIcon"></i>
                            <img id="logoPreview" src="" alt="Logo" style="display:none;">
                        </div>
                        <div class="logo-actions">
                            <input type="file" id="logoFileInput" accept="image/*" class="form-control-sm form-control mb-2">
                            <div style="display:flex;gap:.5rem;">
                                <button class="btn-gold btn-sm" onclick="saveLogo()"><i class="bi bi-check-lg"></i> Save Logo</button>
                                <button class="btn-gold-outline btn-sm" onclick="removeLogo()"><i class="bi bi-trash"></i> Remove</button>
                            </div>
                            <div class="logo-hint">Square image · max 2 MB · shown on sidebar &amp; receipts</div>
                        </div>
                    </div>

                    <!-- Store Name -->
                    <div class="field-group">
                        <label for="storeNameInput">Store / Business Name</label>
                        <input type="text" id="storeNameInput" class="form-control" placeholder="e.g. 7Evelyn Store" maxlength="60">
                        <div class="field-hint">Shown in the sidebar and on printed receipts.</div>
                    </div>

                    <!-- Tagline -->
                    <div class="field-group">
                        <label for="storeTaglineInput">Tagline / Address</label>
                        <input type="text" id="storeTaglineInput" class="form-control" placeholder="e.g. Brgy. Poblacion, Angat, Bulacan" maxlength="80">
                        <div class="field-hint">Appears below the store name on receipts.</div>
                    </div>

                    <button class="btn-gold btn-save-full" onclick="saveBranding()">
                        <i class="bi bi-floppy"></i> Save Branding
                    </button>
                </div>
            </div>
        </div>

        <!-- Right column -->
        <div>
            <!-- Receipt Preferences -->
            <div class="section-label"><i class="bi bi-receipt"></i> Receipts</div>
            <div class="s-card">
                <div class="s-card-header">
                    <div class="hdr-icon"><i class="bi bi-receipt"></i></div>
                    <div>
                        <div class="hdr-title">Receipt Preferences</div>
                        <div class="hdr-desc">Footer message and print options</div>
                    </div>
                </div>
                <div class="s-card-body">
                    <div class="field-group">
                        <label for="receiptFooterInput">Footer Message</label>
                        <input type="text" id="receiptFooterInput" class="form-control" placeholder="e.g. Thank you for shopping with us!" maxlength="100">
                        <div class="field-hint">Printed at the bottom of every receipt.</div>
                    </div>

                    <div class="toggle-row">
                        <div class="toggle-row-text">
                            <div class="toggle-title">Show tagline on receipt</div>
                            <div class="toggle-sub">Prints the tagline/address below store name</div>
                        </div>
                        <input class="form-check-input" type="checkbox" id="showTaglineToggle" role="switch">
                    </div>

                    <div class="toggle-row">
                        <div class="toggle-row-text">
                            <div class="toggle-title">Show logo on receipt</div>
                            <div class="toggle-sub">Includes the store logo at the top</div>
                        </div>
                        <input class="form-check-input" type="checkbox" id="showLogoReceiptToggle" role="switch" checked>
                    </div>

                    <button class="btn-gold btn-save-full" onclick="saveReceiptPrefs()">
                        <i class="bi bi-floppy"></i> Save Receipt Settings
                    </button>
                </div>
            </div>

            <!-- System Info -->
            <div class="section-label" style="margin-top:1.4rem;"><i class="bi bi-cpu"></i> System</div>
            <div class="s-card">
                <div class="s-card-header">
                    <div class="hdr-icon" style="background:var(--mint-dark);color:var(--navy-mid);"><i class="bi bi-info-circle"></i></div>
                    <div>
                        <div class="hdr-title">System Information</div>
                        <div class="hdr-desc">Environment and session details</div>
                    </div>
                </div>
                <div class="s-card-body" style="padding-top:1rem;padding-bottom:1rem;">
                    <table class="sysinfo-table">
                        <tbody>
                            <tr>
                                <td>System</td>
                                <td>7Evelyn POS</td>
                            </tr>
                            <tr>
                                <td>Logged in as</td>
                                <td><?php echo htmlspecialchars($_SESSION['userName']); ?></td>
                            </tr>
                            <tr>
                                <td>Role</td>
                                <td><span class="role-pill"><?php echo htmlspecialchars($_SESSION['roleName']); ?></span></td>
                            </tr>
                            <tr>
                                <td>Server date</td>
                                <td><?php echo date('F d, Y'); ?></td>
                            </tr>
                            <tr>
                                <td>PHP version</td>
                                <td><?php echo phpversion(); ?></td>
                            </tr>
                            <tr>
                                <td>Database</td>
                                <td><span class="status-dot">MySQL connected</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="section-label" style="margin-top:.6rem;"><i class="bi bi-exclamation-triangle"></i> Danger Zone</div>
    <div class="danger-card">
        <div class="danger-header">
            <div class="hdr-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="hdr-title">Irreversible Actions</div>
        </div>
        <div class="danger-body">
            <div class="danger-desc">
                <div class="danger-title">Clear Local Settings</div>
                <div class="danger-sub">Removes logo, store name, and all local preferences from this browser. This cannot be undone.</div>
            </div>
            <button class="btn-danger-outline" onclick="clearAllSettings()">
                <i class="bi bi-trash"></i> Clear All Local Settings
            </button>
        </div>
    </div>

</div><!-- end settings-body -->

<script>
// ── Load saved settings on page load ──────────────────────────────────────
document.addEventListener('DOMContentLoaded', function(){
    const logo = localStorage.getItem('ev_store_logo');
    if(logo) showLogoPreview(logo);

    const name = localStorage.getItem('ev_store_name');
    if(name) document.getElementById('storeNameInput').value = name;

    const tagline = localStorage.getItem('ev_store_tagline');
    if(tagline) document.getElementById('storeTaglineInput').value = tagline;

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
    const sidebarImg  = document.getElementById('sidebarLogoImg');
    const sidebarIcon = document.getElementById('sidebarLogoIcon');
    if(sidebarImg){ sidebarImg.src = src; sidebarImg.style.display='block'; }
    if(sidebarIcon){ sidebarIcon.style.display='none'; }
    Swal.fire({icon:'success',title:'Logo Saved!',timer:1500,showConfirmButton:false});
}

function removeLogo(){
    localStorage.removeItem('ev_store_logo');
    document.getElementById('logoPreview').style.display = 'none';
    document.getElementById('logoPreview').src = '';
    document.getElementById('logoPlaceholderIcon').style.display = '';
    document.getElementById('logoFileInput').value = '';
    const sidebarImg  = document.getElementById('sidebarLogoImg');
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
        confirmButtonColor:'#d93025',
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