<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';
session_start();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }

$pageTitle = "My Profile – 7Evelyn POS";

$userID = intval($_SESSION['userID']);
$stmt = $conn->prepare("SELECT u.*, r.roleName, r.roleDesc FROM users u JOIN role r ON u.roleID=r.roleID WHERE u.userID=?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$fullName = trim("{$u['givenName']} {$u['midName']} {$u['surName']} {$u['extName']}");
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<div class="topbar no-print">
    <h5><i class="bi bi-person-circle me-2" style="color:var(--ev-purple);"></i>My Profile</h5>
    <div class="ms-auto"><div class="user-badge">
        <i class="bi bi-person-circle" style="color:var(--ev-purple);"></i>
        <span><?php echo htmlspecialchars($_SESSION['userName']); ?></span>
        <span class="role-pill"><?php echo htmlspecialchars($_SESSION['roleName']); ?></span>
    </div></div>
</div>

<?php
$alerts = [
    'updated'    => ['success','Profile Updated','Your profile has been saved.'],
    'emptyFields'=> ['warning','Required Fields','Please fill in all required fields.'],
    'pwChanged'  => ['success','Password Changed','Your password has been updated.'],
    'pwWrong'    => ['error','Wrong Password','Current password is incorrect.'],
    'pwMismatch' => ['error','Password Mismatch','New passwords do not match.'],
    'pwShort'    => ['warning','Too Short','Password must be at least 6 characters.'],
];
foreach($alerts as $k=>[$i,$t,$tx]) if(isset($_GET[$k])) echo "<script>Swal.fire({icon:'$i',title:'$t',text:'$tx',timer:2500}).then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";
?>

<style>
.profile-avatar {
    width: 110px; height: 110px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid white;
    box-shadow: 0 4px 18px rgba(0,129,97,0.22);
}
.profile-avatar-placeholder {
    width: 110px; height: 110px;
    border-radius: 50%;
    background: var(--ev-gradient);
    display: flex; align-items: center; justify-content: center;
    font-size: 2.8rem; color: white;
    border: 4px solid white;
    box-shadow: 0 4px 18px rgba(0,129,97,0.22);
    flex-shrink: 0;
}
.profile-hero {
    background: var(--ev-gradient);
    border-radius: 18px;
    color: white;
    padding: 28px 32px;
    display: flex;
    align-items: center;
    gap: 24px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.profile-hero::after {
    content: '';
    position: absolute; right: -40px; top: -40px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,0.08);
}
.profile-hero-info h3 { font-weight: 800; margin: 0 0 4px; }
.profile-hero-info .role-badge {
    background: rgba(255,255,255,0.22);
    backdrop-filter: blur(4px);
    border-radius: 20px;
    padding: 3px 14px;
    font-size: 13px;
    font-weight: 600;
    display: inline-block;
}
.profile-hero-info .role-desc { font-size: 13px; opacity: 0.8; margin-top: 4px; }
.section-card { background: white; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,129,97,0.08); padding: 24px; margin-bottom: 20px; }
.section-card h6 { font-weight: 700; color: var(--ev-purple); border-bottom: 2px solid #b3ddd2; padding-bottom: 10px; margin-bottom: 18px; }
.avatar-upload-wrap { position: relative; display: inline-block; cursor: pointer; }
.avatar-upload-wrap input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.avatar-upload-overlay {
    position: absolute; bottom: 4px; right: 4px;
    background: var(--ev-purple);
    color: white;
    border-radius: 50%;
    width: 28px; height: 28px;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.18);
    pointer-events: none;
}
</style>

<div class="page-body">

    <!-- Hero Card -->
    <div class="profile-hero">
        <div class="avatar-upload-wrap" title="Click to change photo (save to apply)">
            <?php if(!empty($u['profile_image'])): ?>
            <img src="../<?php echo htmlspecialchars($u['profile_image']); ?>" alt="Avatar" class="profile-avatar" id="heroAvatar">
            <?php else: ?>
            <div class="profile-avatar-placeholder" id="heroAvatarPlaceholder">
                <i class="bi bi-person-fill"></i>
            </div>
            <img src="" alt="" class="profile-avatar" id="heroAvatar" style="display:none;">
            <?php endif; ?>
            <input type="file" accept="image/*" id="avatarFileInput" onchange="previewHeroAvatar(this)" title="Upload profile photo">
            <div class="avatar-upload-overlay"><i class="bi bi-camera-fill"></i></div>
        </div>
        <div class="profile-hero-info">
            <h3><?php echo htmlspecialchars($fullName); ?></h3>
            <span class="role-badge"><i class="bi bi-shield-check me-1"></i><?php echo htmlspecialchars($u['roleName']); ?></span>
            <?php if(!empty($u['roleDesc'])): ?>
            <div class="role-desc"><?php echo htmlspecialchars($u['roleDesc']); ?></div>
            <?php endif; ?>
            <div style="font-size:13px;opacity:0.85;margin-top:6px;">
                <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($u['email']); ?>
                &nbsp;|&nbsp;
                <i class="bi bi-calendar me-1"></i>Member since <?php echo date('M Y', strtotime($u['dateCreated'])); ?>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Edit Profile Form -->
        <div class="col-lg-7">
            <div class="section-card">
                <h6><i class="bi bi-person-lines-fill me-2"></i>Personal Information</h6>
                <form method="POST" action="../backend/profileAuth.php" enctype="multipart/form-data" id="profileForm">
            <?php csrf_field(); ?>
                    <input type="hidden" name="avatarFromPreview" id="avatarFromPreview">
                    <!-- Hidden file that carries the selected avatar into this form -->
                    <input type="file" name="profile_image" id="profileImageInput" accept="image/*" style="display:none;">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="givenName" value="<?php echo htmlspecialchars($u['givenName']); ?>" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Middle Name</label>
                            <input type="text" name="midName" value="<?php echo htmlspecialchars($u['midName']??''); ?>" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="surName" value="<?php echo htmlspecialchars($u['surName']); ?>" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Ext (Jr/Sr)</label>
                            <input type="text" name="extName" value="<?php echo htmlspecialchars($u['extName']??''); ?>" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="Male"   <?php echo $u['gender']==='Male'  ?'selected':''; ?>>Male</option>
                                <option value="Female" <?php echo $u['gender']==='Female'?'selected':''; ?>>Female</option>
                                <option value="Other"  <?php echo $u['gender']==='Other' ?'selected':''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Birthdate</label>
                            <input type="date" name="birthdate" value="<?php echo $u['birthdate']??''; ?>" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Civil Status</label>
                            <select name="civilStatus" class="form-select">
                                <?php foreach(['Single','Married','Widowed','Separated'] as $cs): ?>
                                <option <?php echo ($u['civilStatus']??'')===$cs?'selected':''; ?>><?php echo $cs; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Contact No</label>
                            <input type="text" name="contactNo" value="<?php echo htmlspecialchars($u['contactNo']??''); ?>" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($u['email']); ?>" class="form-control" disabled title="Contact Admin to change email">
                            <small class="text-muted">Contact Admin to change email.</small>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" name="profileUpdate" class="btn btn-ev"><i class="bi bi-check-circle me-1"></i>Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Role Info + Change Password -->
        <div class="col-lg-5">
            <!-- Role Info Card -->
            <div class="section-card">
                <h6><i class="bi bi-shield-check me-2"></i>Role & Access</h6>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#e8f5f1,#c8efe4);display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-person-badge" style="font-size:1.4rem;color:var(--ev-purple);"></i>
                    </div>
                    <div>
                        <div class="fw-bold"><?php echo htmlspecialchars($u['roleName']); ?></div>
                        <div class="text-muted small"><?php echo htmlspecialchars($u['roleDesc'] ?: 'No description'); ?></div>
                    </div>
                </div>
                <table class="table table-sm table-borderless mb-0 small">
                    <tr><td class="text-muted">User No</td><td class="fw-semibold"><?php echo htmlspecialchars($u['userNo']); ?></td></tr>
                    <tr><td class="text-muted">Date Joined</td><td class="fw-semibold"><?php echo date('F d, Y', strtotime($u['dateCreated'])); ?></td></tr>
                    <tr><td class="text-muted">Status</td><td><span class="badge bg-success">Active</span></td></tr>
                </table>
            </div>

            <!-- Change Password Card -->
            <div class="section-card">
                <h6><i class="bi bi-key me-2"></i>Change Password</h6>
                <form method="POST" action="../backend/profileAuth.php">
            <?php csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Current Password</label>
                        <input type="password" name="currentPassword" class="form-control" required autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">New Password</label>
                        <input type="password" name="newPassword" class="form-control" required autocomplete="new-password" minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Confirm New Password</label>
                        <input type="password" name="confirmPassword" class="form-control" required autocomplete="new-password" minlength="6">
                    </div>
                    <button type="submit" name="changeOwnPassword" class="btn btn-warning w-100"><i class="bi bi-lock-fill me-1"></i>Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// When user picks image from hero, sync to the hidden file input in the form
function previewHeroAvatar(input){
    if(!input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function(e){
        // Show preview in hero
        var ph = document.getElementById('heroAvatarPlaceholder');
        var img = document.getElementById('heroAvatar');
        if(ph) ph.style.display = 'none';
        img.src = e.target.result;
        img.style.display = 'block';
    }
    reader.readAsDataURL(input.files[0]);

    // Transfer the file to the hidden form input using DataTransfer
    try {
        var dt = new DataTransfer();
        dt.items.add(input.files[0]);
        document.getElementById('profileImageInput').files = dt.files;
    } catch(e) {
        // Fallback: won't work on all browsers; user must use Save Changes after picking
    }
}
</script>

<?php include 'footer.php' ?? null; ?>
