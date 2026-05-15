<?php
session_start();
if(isset($_SESSION['userID'])){
    header("Location: ./frontend/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>7Evelyn POS – Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    min-height: 100vh;
    display: flex;
    background: #f2f9f7;
    font-family: 'Segoe UI', system-ui, sans-serif;
}

/* Left panel */
.login-left {
    width: 45%;
    background: linear-gradient(160deg, #003d2e 0%, #005e47 45%, #008161 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 48px;
    position: relative;
    overflow: hidden;
}
.login-left::before {
    content: '';
    position: absolute;
    top: -80px; right: -80px;
    width: 320px; height: 320px;
    border-radius: 50%;
    background: rgba(244,130,31,0.12);
}
.login-left::after {
    content: '';
    position: absolute;
    bottom: -60px; left: -60px;
    width: 240px; height: 240px;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
}
.brand-logo-wrap {
    width: 100px; height: 100px;
    border-radius: 28px;
    background: rgba(255,255,255,0.12);
    border: 2px solid rgba(255,255,255,0.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 2.8rem; color: #fff;
    margin-bottom: 28px;
    position: relative; z-index: 1;
    overflow: hidden;
    backdrop-filter: blur(4px);
}
.brand-logo-wrap img {
    width: 100%; height: 100%;
    object-fit: cover; border-radius: 26px; display: none;
}
.left-title {
    font-size: 2rem; font-weight: 800;
    color: #fff; letter-spacing: -0.5px;
    position: relative; z-index: 1;
    text-align: center;
    margin-bottom: 8px;
}
.left-sub {
    font-size: 13px; color: rgba(255,255,255,0.6);
    text-align: center; letter-spacing: 1px;
    text-transform: uppercase; font-weight: 500;
    position: relative; z-index: 1;
    margin-bottom: 48px;
}
.feature-list {
    list-style: none; width: 100%;
    position: relative; z-index: 1;
}
.feature-list li {
    display: flex; align-items: center; gap: 12px;
    color: rgba(255,255,255,0.8); font-size: 13.5px;
    padding: 9px 0;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.feature-list li:last-child { border-bottom: none; }
.feature-list li i {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: rgba(244,130,31,0.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; color: #f9a84d; flex-shrink: 0;
}

/* Right panel */
.login-right {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 32px;
}
.login-form-wrap {
    width: 100%;
    max-width: 400px;
}
.form-heading {
    font-size: 1.6rem; font-weight: 800;
    color: #003d2e; margin-bottom: 4px;
}
.form-subheading {
    font-size: 13px; color: #6b7a74;
    margin-bottom: 36px;
}
.form-label {
    font-size: 12.5px; font-weight: 700;
    color: #004a38; margin-bottom: 5px;
}
.form-control {
    border-radius: 10px;
    border: 1.5px solid #c8ddd8;
    padding: 10px 14px;
    font-size: 14px;
    background: #fff;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.form-control:focus {
    border-color: #008161;
    box-shadow: 0 0 0 3px rgba(0,129,97,0.12);
    outline: none;
}
.input-group .form-control { border-right: none; border-radius: 10px 0 0 10px; }
.input-group-text {
    border: 1.5px solid #c8ddd8; border-left: none;
    border-radius: 0 10px 10px 0;
    background: #fff; cursor: pointer;
    color: #008161; padding: 0 14px;
    transition: background 0.2s;
}
.input-group-text:hover { background: #f0faf7; }
.input-group:focus-within .form-control,
.input-group:focus-within .input-group-text {
    border-color: #008161;
}
.input-group:focus-within .input-group-text {
    box-shadow: 0 0 0 3px rgba(0,129,97,0.12);
}

.btn-login {
    background: linear-gradient(135deg, #008161 0%, #005e47 100%);
    border: none; color: #fff;
    font-weight: 700; font-size: 15px;
    border-radius: 10px;
    padding: 12px;
    letter-spacing: 0.3px;
    transition: all 0.25s;
    box-shadow: 0 4px 14px rgba(0,129,97,0.35);
    position: relative; overflow: hidden;
}
.btn-login::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, #f4821f, #f01b2d);
    border-radius: 0 0 10px 10px;
}
.btn-login:hover {
    background: linear-gradient(135deg, #005e47 0%, #003d2e 100%);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(0,129,97,0.42);
}
.btn-login:active { transform: translateY(0); }

.divider-line {
    display: flex; align-items: center; gap: 12px;
    margin: 24px 0; color: #aac4bb; font-size: 12px;
}
.divider-line::before, .divider-line::after {
    content: ''; flex: 1;
    border-top: 1px solid #d6e9e4;
}

.login-footer-text {
    text-align: center; font-size: 11.5px;
    color: #9db8b0; margin-top: 36px;
}
.login-footer-text strong { color: #008161; }

.alert { border-radius: 10px; font-size: 13.5px; }
.alert-danger  { background: #fff0f1; border: 1px solid #f9c0c4; color: #b0192a; }
.alert-success { background: #f0faf7; border: 1px solid #b3ddd2; color: #005e47; }

@media (max-width: 768px) {
    body { flex-direction: column; }
    .login-left { width: 100%; padding: 40px 24px 32px; }
    .feature-list { display: none; }
    .left-sub { margin-bottom: 0; }
    .login-right { padding: 32px 20px; }
}
</style>
</head>
<body>

<div class="login-left">
    <div class="brand-logo-wrap" id="loginLogoWrap">
        <img id="loginLogoImg" src="" alt="Logo">
        <i class="bi bi-shop-window" id="loginLogoIcon"></i>
    </div>
    <div class="left-title" id="loginBrandName">7Evelyn</div>
    <div class="left-sub">Point of Sale System</div>

    <ul class="feature-list">
        <li><i class="bi bi-cart3"></i> Fast &amp; Easy Point of Sale</li>
        <li><i class="bi bi-archive"></i> Real-time Inventory Tracking</li>
        <li><i class="bi bi-people"></i> Customer Credit Management</li>
        <li><i class="bi bi-bar-chart-line"></i> Sales Reports &amp; Analytics</li>
        <li><i class="bi bi-shield-check"></i> Role-based Access Control</li>
    </ul>
</div>

<div class="login-right">
    <div class="login-form-wrap">
        <div class="form-heading">Welcome back</div>
        <div class="form-subheading">Sign in to your account to continue</div>

        <?php if(isset($_GET['invalid'])): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i> <strong>Access Denied.</strong> Wrong email or password.
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if(isset($_GET['logout'])): ?>
        <div class="alert alert-success alert-dismissible fade show py-2 mb-3" role="alert">
            <i class="bi bi-check-circle me-1"></i> You've been signed out successfully.
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form action="./backend/loginAuth.php" method="POST">
            <?php csrf_field(); ?>
            <div class="mb-3">
                <label class="form-label"><i class="bi bi-envelope me-1"></i>Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label"><i class="bi bi-lock me-1"></i>Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Enter your password" required>
                    <span class="input-group-text" onclick="togglePw()" title="Show/Hide password">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>
            <div class="d-grid">
                <button type="submit" name="loginAuth" class="btn btn-login btn-lg">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </div>
        </form>

        <div class="divider-line">default credentials</div>

        <div style="background:#f0f7f5;border-radius:10px;padding:12px 16px;border:1px solid #b3ddd2;">
            <div style="font-size:12px;color:#005e47;font-weight:600;margin-bottom:4px;"><i class="bi bi-person-badge me-1"></i>Admin</div>
            <div style="font-size:12px;color:#4a7a6e;">admin@7evelyn.com &nbsp;/&nbsp; admin123</div>
        </div>

        <div class="login-footer-text">
            &copy; <?php echo date('Y'); ?> <strong>7Evelyn POS</strong> &nbsp;&bull;&nbsp; v1.0.0
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePw(){
    const inp = document.getElementById('passwordInput');
    const ico = document.getElementById('eyeIcon');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    ico.className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

(function(){
    const logo = localStorage.getItem('ev_store_logo');
    const name = localStorage.getItem('ev_store_name');
    if(logo){
        const img  = document.getElementById('loginLogoImg');
        const icon = document.getElementById('loginLogoIcon');
        if(img && icon){ img.src = logo; img.style.display = 'block'; icon.style.display = 'none'; }
    }
    if(name){
        const el = document.getElementById('loginBrandName');
        if(el) el.textContent = name;
    }
})();
</script>
</body>
</html>
