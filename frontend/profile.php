<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }

$pageTitle = "My Profile – Beng's Unli LugawS";

$userID = intval($_SESSION['userID']);
$stmt = $conn->prepare("SELECT u.*, r.roleName, r.roleDesc FROM users u JOIN role r ON u.roleID=r.roleID WHERE u.userID=?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$fullName = trim("{$u['givenName']} {$u['midName']} {$u['surName']} {$u['extName']}");
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<style>
/* ── Profile Page Variables ─────────────────────────────────────────────── */
.user-badge {
    display: flex; align-items: center; gap: .55rem;
    background: var(--mint);
    border: 1.5px solid var(--mint-dark);
    border-radius: 50px;
    padding: .32rem .8rem .32rem .5rem;
    font-size: .78rem; color: var(--charcoal); font-weight: 600;
}
.user-badge i { color: var(--charcoal-light); font-size: 1.1rem; }
.role-pill {
    background: var(--charcoal); color: var(--orange);
    font-size: .68rem; font-weight: 700;
    padding: .15rem .55rem; border-radius: 50px;
    letter-spacing: .04em; text-transform: uppercase;
}

/* ── Body ───────────────────────────────────────────────────────────────── */
.profile-body {
    padding: 2rem 1.8rem;
    max-width: 1100px;
    margin: 0 auto;
}

/* ── Hero Banner ────────────────────────────────────────────────────────── */
.profile-hero {
    background: var(--charcoal);
    border-radius: var(--r-lg);
    padding: 2rem 2.2rem;
    display: flex;
    align-items: center;
    gap: 1.8rem;
    margin-bottom: 1.8rem;
    position: relative;
    overflow: hidden;
}
.profile-hero::before {
    content: '';
    position: absolute; right: -60px; top: -60px;
    width: 260px; height: 260px;
    border-radius: 50%;
    background: rgba(239,130,13,.07);
    pointer-events: none;
}
.profile-hero::after {
    content: '';
    position: absolute; right: 80px; bottom: -80px;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(231,245,245,.04);
    pointer-events: none;
}

/* Avatar */
.avatar-wrap {
    position: relative; display: inline-block; flex-shrink: 0;
}
.avatar-img {
    width: 96px; height: 96px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--orange);
    box-shadow: 0 0 0 4px rgba(239,130,13,.2);
    display: block;
}
.avatar-placeholder {
    width: 96px; height: 96px;
    border-radius: 50%;
    background: var(--charcoal-mid);
    border: 3px solid var(--orange);
    box-shadow: 0 0 0 4px rgba(239,130,13,.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 2.4rem; color: var(--orange);
}
.avatar-upload-overlay {
    position: absolute; bottom: 2px; right: 2px;
    width: 26px; height: 26px;
    background: var(--orange);
    color: var(--charcoal);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .75rem;
    box-shadow: 0 2px 6px rgba(0,0,0,.2);
    cursor: pointer;
    pointer-events: none;
}
.avatar-file-input {
    position: absolute; inset: 0;
    opacity: 0; cursor: pointer; width: 100%; height: 100%;
}

/* Hero Info */
.hero-info { flex: 1; min-width: 0; }
.hero-name {
    font-size: 1.4rem; font-weight: 800;
    color: var(--c-white); letter-spacing: -.02em;
    margin: 0 0 .4rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.hero-role-badge {
    display: inline-flex; align-items: center; gap: .35rem;
    background: rgba(239,130,13,.15);
    border: 1px solid rgba(239,130,13,.3);
    color: var(--orange);
    font-size: .75rem; font-weight: 700;
    padding: .25rem .75rem;
    border-radius: 50px;
    margin-bottom: .45rem;
}
.hero-role-desc {
    font-size: .78rem; color: rgba(255,255,255,.55);
    margin-bottom: .5rem;
}
.hero-meta {
    display: flex; align-items: center; gap: 1.2rem;
    flex-wrap: wrap;
}
.hero-meta-item {
    display: flex; align-items: center; gap: .35rem;
    font-size: .78rem; color: rgba(255,255,255,.6);
}
.hero-meta-item i { color: var(--orange); font-size: .85rem; }

/* ── Section label ──────────────────────────────────────────────────────── */
.section-label {
    display: flex; align-items: center; gap: .5rem;
    font-size: .72rem; font-weight: 700;
    letter-spacing: .1em; text-transform: uppercase;
    color: var(--t-muted);
    margin-bottom: 1rem;
}
.section-label::after {
    content: ''; flex: 1; height: 1px;
    background: var(--border); margin-left: .5rem;
}

/* ── Cards ──────────────────────────────────────────────────────────────── */
.p-card {
    background: var(--c-white);
    border-radius: var(--r-lg);
    border: 1.5px solid var(--border);
    box-shadow: var(--sh-sm);
    overflow: hidden;
    transition: var(--transition);
    margin-bottom: 1.4rem;
}
.p-card:hover { box-shadow: var(--sh-md); border-color: #d9d5f0; }
.p-card-header {
    display: flex; align-items: center; gap: .7rem;
    padding: 1rem 1.4rem;
    border-bottom: 1.5px solid var(--border);
    background: var(--mint);
}
.p-card-header .hdr-icon {
    width: 36px; height: 36px;
    background: var(--orange);
    border-radius: var(--r-sm);
    display: flex; align-items: center; justify-content: center;
    color: var(--charcoal); font-size: 1.05rem; flex-shrink: 0;
}
.p-card-header .hdr-title { font-size: .92rem; font-weight: 700; color: var(--charcoal); }
.p-card-header .hdr-desc { font-size: .74rem; color: var(--t-muted); margin-top: .04rem; }
.p-card-body { padding: 1.5rem 1.4rem; }

/* ── Form Fields ────────────────────────────────────────────────────────── */
.field-group { margin-bottom: 1rem; }
.field-group label {
    display: block; font-size: .78rem; font-weight: 700;
    color: var(--charcoal); margin-bottom: .32rem;
}
.field-group label .req { color: #d93025; }
.field-group .form-control,
.field-group .form-select {
    border: 1.5px solid var(--border);
    border-radius: var(--r-sm);
    padding: .56rem .82rem;
    font-size: .84rem;
    color: var(--text-main);
    background: var(--c-white);
    transition: var(--transition);
    box-shadow: none;
}
.field-group .form-control:focus,
.field-group .form-select:focus {
    border-color: var(--orange-dark);
    box-shadow: 0 0 0 3px rgba(239,130,13,.18);
    outline: none;
}
.field-group .form-control:disabled,
.field-group .form-control[disabled] {
    background: var(--mint);
    color: var(--t-muted);
    cursor: not-allowed;
}
.field-group .field-hint { font-size: .72rem; color: var(--t-muted); margin-top: .28rem; }
.field-row { display: grid; gap: 1rem; }
.field-row-2 { grid-template-columns: 1fr 1fr; }
.field-row-3 { grid-template-columns: 1fr 1fr 1fr; }
.field-row-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }
@media(max-width:600px){
    .field-row-2,.field-row-3,.field-row-4{ grid-template-columns:1fr; }
}

/* ── Buttons ────────────────────────────────────────────────────────────── */
.btn-gold {
    background: var(--orange); color: var(--charcoal);
    border: 1.5px solid var(--orange-dark);
    border-radius: var(--r-sm);
    font-size: .83rem; font-weight: 700;
    padding: .58rem 1.3rem;
    transition: var(--transition);
    display: inline-flex; align-items: center; gap: .4rem;
    cursor: pointer; letter-spacing: -.01em;
}
.btn-gold:hover {
    background: var(--orange-dark); color: var(--charcoal);
    box-shadow: 0 4px 14px rgba(239,130,13,.38);
    transform: translateY(-1px);
}
.btn-navy {
    background: var(--charcoal); color: var(--orange);
    border: 1.5px solid var(--charcoal-mid);
    border-radius: var(--r-sm);
    font-size: .83rem; font-weight: 700;
    padding: .58rem 1.3rem;
    transition: var(--transition);
    display: inline-flex; align-items: center; gap: .4rem;
    cursor: pointer; width: 100%; justify-content: center;
}
.btn-navy:hover {
    background: var(--charcoal-mid); color: var(--orange);
    box-shadow: 0 4px 14px rgba(26,26,26,.22);
    transform: translateY(-1px);
}

/* ── Role & Access Card ─────────────────────────────────────────────────── */
.role-banner {
    display: flex; align-items: center; gap: 1rem;
    background: var(--charcoal);
    border-radius: var(--r-md);
    padding: 1rem 1.2rem;
    margin-bottom: 1.1rem;
}
.role-banner-icon {
    width: 44px; height: 44px;
    background: rgba(239,130,13,.15);
    border: 1.5px solid rgba(239,130,13,.3);
    border-radius: var(--r-sm);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; color: var(--orange); flex-shrink: 0;
}
.role-banner-title { font-weight: 700; color: var(--c-white); font-size: .9rem; }
.role-banner-desc  { font-size: .74rem; color: rgba(255,255,255,.55); margin-top: .1rem; }

.info-table { width: 100%; border-collapse: collapse; }
.info-table tr { border-bottom: 1px solid var(--border); }
.info-table tr:last-child { border-bottom: none; }
.info-table td { padding: .6rem .15rem; font-size: .82rem; vertical-align: middle; }
.info-table td:first-child { color: var(--t-muted); width: 110px; font-weight: 500; }
.info-table td:last-child { font-weight: 600; color: var(--charcoal); }
.status-active {
    display: inline-flex; align-items: center; gap: .35rem;
    background: #e6f9f0; color: #1a7a4a;
    font-size: .72rem; font-weight: 700;
    padding: .18rem .6rem; border-radius: 50px;
}
.status-active::before {
    content: ''; width: 6px; height: 6px;
    border-radius: 50%; background: #1ab26b;
}

/* ── Password fields ────────────────────────────────────────────────────── */
.pw-input-wrap { position: relative; }
.pw-input-wrap .form-control { padding-right: 2.6rem; }
.pw-toggle {
    position: absolute; right: .75rem; top: 50%;
    transform: translateY(-50%);
    color: var(--text-light); cursor: pointer; font-size: .95rem;
    background: none; border: none; padding: 0;
    transition: color .15s;
}
.pw-toggle:hover { color: var(--charcoal); }

/* ── Grid layout ────────────────────────────────────────────────────────── */
.profile-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 1.4rem;
    align-items: start;
}
@media(max-width:900px){
    .profile-grid { grid-template-columns: 1fr; }
    .profile-body { padding: 1.2rem 1rem; }
}
</style>

<!-- ── Topbar ─────────────────────────────────────────────────────────────── -->
<?php
$topbarTitle = 'My Profile';
$topbarIcon  = 'bi-person-fill';
$topbarSub   = 'Manage your account details and password';
include 'topbar.php';
?>

<?php
$alerts = [
    'updated'    => ['success','Profile Updated','Your profile has been saved.'],
    'emptyFields'=> ['warning','Required Fields','Please fill in all required fields.'],
    'pwChanged'  => ['success','Password Changed','Your password has been updated.'],
    'pwWrong'    => ['error','Wrong Password','Current password is incorrect.'],
    'pwMismatch' => ['error','Password Mismatch','New passwords do not match.'],
    'pwShort'    => ['warning','Too Short','Password must be at least 6 characters.'],
];
foreach($alerts as $k=>[$i,$t,$tx])
    if(isset($_GET[$k]))
        echo "<script>Swal.fire({icon:'$i',title:'$t',text:'$tx',timer:2500}).then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";
?>

<!-- ── Page Body ─────────────────────────────────────────────────────────── -->
<div class="profile-body">

    <!-- Hero Banner -->
    <div class="profile-hero">
        <div class="avatar-wrap" title="Click to change photo">
            <?php if(!empty($u['profile_image'])): ?>
                <img src="../<?php echo htmlspecialchars($u['profile_image']); ?>" alt="Avatar" class="avatar-img" id="heroAvatar">
            <?php else: ?>
                <div class="avatar-placeholder" id="heroAvatarPlaceholder"><i class="bi bi-person-fill"></i></div>
                <img src="" alt="" class="avatar-img" id="heroAvatar" style="display:none;">
            <?php endif; ?>
            <input type="file" accept="image/*" id="avatarFileInput" class="avatar-file-input" onchange="previewHeroAvatar(this)">
            <div class="avatar-upload-overlay"><i class="bi bi-camera-fill"></i></div>
        </div>

        <div class="hero-info">
            <h2 class="hero-name"><?php echo htmlspecialchars($fullName); ?></h2>
            <div class="hero-role-badge">
                <i class="bi bi-shield-check"></i>
                <?php echo htmlspecialchars($u['roleName']); ?>
            </div>
            <?php if(!empty($u['roleDesc'])): ?>
                <div class="hero-role-desc"><?php echo htmlspecialchars($u['roleDesc']); ?></div>
            <?php endif; ?>
            <div class="hero-meta">
                <div class="hero-meta-item"><i class="bi bi-envelope"></i><?php echo htmlspecialchars($u['email']); ?></div>
                <div class="hero-meta-item"><i class="bi bi-calendar3"></i>Member since <?php echo date('M Y', strtotime($u['dateCreated'])); ?></div>
                <div class="hero-meta-item"><i class="bi bi-hash"></i><?php echo htmlspecialchars($u['userNo']); ?></div>
            </div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="profile-grid">

        <!-- Left: Personal Info Form -->
        <div>
            <div class="section-label"><i class="bi bi-person-lines-fill"></i> Personal Information</div>
            <div class="p-card">
                <div class="p-card-header">
                    <div class="hdr-icon"><i class="bi bi-person-lines-fill"></i></div>
                    <div>
                        <div class="hdr-title">Edit Profile</div>
                        <div class="hdr-desc">Update your personal details</div>
                    </div>
                </div>
                <div class="p-card-body">
                    <form method="POST" action="../backend/profileAuth.php" enctype="multipart/form-data" id="profileForm">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="avatarFromPreview" id="avatarFromPreview">
                        <input type="file" name="profile_image" id="profileImageInput" accept="image/*" style="display:none;">

                        <!-- Name row -->
                        <div class="field-row field-row-3" style="margin-bottom:1rem;">
                            <div class="field-group" style="margin:0;">
                                <label>First Name <span class="req">*</span></label>
                                <input type="text" name="givenName" value="<?php echo htmlspecialchars($u['givenName']); ?>" class="form-control" required>
                            </div>
                            <div class="field-group" style="margin:0;">
                                <label>Middle Name</label>
                                <input type="text" name="midName" value="<?php echo htmlspecialchars($u['midName']??''); ?>" class="form-control">
                            </div>
                            <div class="field-group" style="margin:0;">
                                <label>Last Name <span class="req">*</span></label>
                                <input type="text" name="surName" value="<?php echo htmlspecialchars($u['surName']); ?>" class="form-control" required>
                            </div>
                        </div>

                        <!-- Details row -->
                        <div class="field-row field-row-4" style="margin-bottom:1rem;">
                            <div class="field-group" style="margin:0;">
                                <label>Ext (Jr/Sr)</label>
                                <input type="text" name="extName" value="<?php echo htmlspecialchars($u['extName']??''); ?>" class="form-control">
                            </div>
                            <div class="field-group" style="margin:0;">
                                <label>Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="Male"   <?php echo $u['gender']==='Male'  ?'selected':''; ?>>Male</option>
                                    <option value="Female" <?php echo $u['gender']==='Female'?'selected':''; ?>>Female</option>
                                    <option value="Other"  <?php echo $u['gender']==='Other' ?'selected':''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="field-group" style="margin:0;">
                                <label>Birthdate</label>
                                <input type="date" name="birthdate" value="<?php echo $u['birthdate']??''; ?>" class="form-control">
                            </div>
                            <div class="field-group" style="margin:0;">
                                <label>Civil Status</label>
                                <select name="civilStatus" class="form-select">
                                    <?php foreach(['Single','Married','Widowed','Separated'] as $cs): ?>
                                    <option <?php echo ($u['civilStatus']??'')===$cs?'selected':''; ?>><?php echo $cs; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Contact + Email -->
                        <div class="field-row field-row-2" style="margin-bottom:1.2rem;">
                            <div class="field-group" style="margin:0;">
                                <label>Contact No</label>
                                <input type="text" name="contactNo" value="<?php echo htmlspecialchars($u['contactNo']??''); ?>" class="form-control">
                            </div>
                            <div class="field-group" style="margin:0;">
                                <label>Email</label>
                                <input type="email" value="<?php echo htmlspecialchars($u['email']); ?>" class="form-control" disabled>
                                <div class="field-hint">Contact Admin to change email.</div>
                            </div>
                        </div>

                        <button type="submit" name="profileUpdate" class="btn-gold">
                            <i class="bi bi-check-circle"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right column -->
        <div>
            <!-- Role & Access -->
            <div class="section-label"><i class="bi bi-shield-check"></i> Role & Access</div>
            <div class="p-card">
                <div class="p-card-header">
                    <div class="hdr-icon" style="background:var(--mint-dark);color:var(--charcoal-mid);"><i class="bi bi-shield-check"></i></div>
                    <div>
                        <div class="hdr-title">Role & Access</div>
                        <div class="hdr-desc">Your permissions in the system</div>
                    </div>
                </div>
                <div class="p-card-body">
                    <div class="role-banner">
                        <div class="role-banner-icon"><i class="bi bi-person-badge"></i></div>
                        <div>
                            <div class="role-banner-title"><?php echo htmlspecialchars($u['roleName']); ?></div>
                            <div class="role-banner-desc"><?php echo htmlspecialchars($u['roleDesc'] ?: 'No description'); ?></div>
                        </div>
                    </div>
                    <table class="info-table">
                        <tr>
                            <td>User No</td>
                            <td><?php echo htmlspecialchars($u['userNo']); ?></td>
                        </tr>
                        <tr>
                            <td>Date Joined</td>
                            <td><?php echo date('F d, Y', strtotime($u['dateCreated'])); ?></td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td><span class="status-active">Active</span></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Change Password -->
            <div class="section-label" style="margin-top:.2rem;"><i class="bi bi-key"></i> Security</div>
            <div class="p-card">
                <div class="p-card-header">
                    <div class="hdr-icon"><i class="bi bi-key-fill"></i></div>
                    <div>
                        <div class="hdr-title">Change Password</div>
                        <div class="hdr-desc">Keep your account secure</div>
                    </div>
                </div>
                <div class="p-card-body">
                    <form method="POST" action="../backend/profileAuth.php">
                        <?php csrf_field(); ?>

                        <div class="field-group">
                            <label>Current Password</label>
                            <div class="pw-input-wrap">
                                <input type="password" name="currentPassword" class="form-control" required autocomplete="current-password" id="pw0">
                                <button type="button" class="pw-toggle" onclick="togglePw('pw0',this)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>

                        <div class="field-group">
                            <label>New Password</label>
                            <div class="pw-input-wrap">
                                <input type="password" name="newPassword" class="form-control" required autocomplete="new-password" minlength="6" id="pw1">
                                <button type="button" class="pw-toggle" onclick="togglePw('pw1',this)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>

                        <div class="field-group">
                            <label>Confirm New Password</label>
                            <div class="pw-input-wrap">
                                <input type="password" name="confirmPassword" class="form-control" required autocomplete="new-password" minlength="6" id="pw2">
                                <button type="button" class="pw-toggle" onclick="togglePw('pw2',this)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>

                        <button type="submit" name="changeOwnPassword" class="btn-navy">
                            <i class="bi bi-lock-fill"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewHeroAvatar(input){
    if(!input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function(e){
        var ph  = document.getElementById('heroAvatarPlaceholder');
        var img = document.getElementById('heroAvatar');
        if(ph) ph.style.display = 'none';
        img.src = e.target.result;
        img.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
    try {
        var dt = new DataTransfer();
        dt.items.add(input.files[0]);
        document.getElementById('profileImageInput').files = dt.files;
    } catch(e) {}
}

function togglePw(id, btn){
    var inp = document.getElementById(id);
    var icon = btn.querySelector('i');
    if(inp.type === 'password'){
        inp.type = 'text';
        icon.classList.replace('bi-eye','bi-eye-slash');
    } else {
        inp.type = 'password';
        icon.classList.replace('bi-eye-slash','bi-eye');
    }
}
</script>

<?php include 'footer.php'; ?>
</body></html>