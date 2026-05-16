<?php
session_start();
require_once __DIR__ . '/backend/csrf.php';
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
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}

body{
    min-height:100vh;display:flex;
    font-family:'Plus Jakarta Sans',system-ui,sans-serif;
    background:#EEF1F7;
}

/* ── Left panel ── */
.login-left{
    width:44%;
    background:linear-gradient(160deg,#0D0B2B 0%,#1a1830 40%,#262341 100%);
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    padding:60px 48px;position:relative;overflow:hidden;
}
.login-left::before{
    content:'';position:absolute;top:-80px;right:-80px;
    width:340px;height:340px;border-radius:50%;
    background:rgba(249,217,74,0.06);
}
.login-left::after{
    content:'';position:absolute;bottom:-60px;left:-60px;
    width:240px;height:240px;border-radius:50%;
    background:rgba(14,165,160,0.07);
}
/* Decorative accent ring */
.login-left .deco-ring{
    position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
    width:480px;height:480px;border-radius:50%;
    border:1px solid rgba(249,217,74,0.07);pointer-events:none;
}
.brand-logo-wrap{
    width:88px;height:88px;border-radius:22px;
    background:linear-gradient(135deg,#F9D94A,#E8C832);
    border:none;display:flex;align-items:center;justify-content:center;
    font-size:2.5rem;color:#262341;margin-bottom:24px;
    position:relative;z-index:1;overflow:hidden;
    box-shadow:0 8px 32px rgba(249,217,74,0.45);
}
.brand-logo-wrap img{width:100%;height:100%;object-fit:cover;border-radius:20px;display:none}
.left-title{
    font-size:2rem;font-weight:800;color:#fff;
    letter-spacing:-0.5px;position:relative;z-index:1;
    text-align:center;margin-bottom:6px;
}
.left-sub{
    font-size:11px;color:rgba(255,255,255,0.45);text-align:center;
    letter-spacing:2px;text-transform:uppercase;font-weight:600;
    position:relative;z-index:1;margin-bottom:44px;
}
.feature-list{list-style:none;width:100%;position:relative;z-index:1}
.feature-list li{
    display:flex;align-items:center;gap:12px;
    color:rgba(255,255,255,0.72);font-size:13.5px;font-weight:500;
    padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.07);
}
.feature-list li:last-child{border-bottom:none}
.feature-list li .fi{
    width:34px;height:34px;border-radius:9px;
    background:rgba(249,217,74,0.12);
    display:flex;align-items:center;justify-content:center;
    font-size:15px;color:#F9D94A;flex-shrink:0;
}

/* ── Right panel ── */
.login-right{
    flex:1;display:flex;align-items:center;justify-content:center;
    padding:48px 40px;background:#EEF1F7;
}
.login-form-wrap{width:100%;max-width:400px}

.form-heading{font-size:1.6rem;font-weight:800;color:#262341;margin-bottom:4px;letter-spacing:-0.3px}
.form-subheading{font-size:13px;color:#6b6880;margin-bottom:32px}

.form-label{font-size:12px;font-weight:700;color:#4a4669;margin-bottom:5px;display:block}

.form-control{
    font-family:'Plus Jakarta Sans',sans-serif;
    border-radius:10px;border:1.5px solid #ddd9ee;
    padding:10px 14px;font-size:13.5px;
    background:#fff;transition:border-color .2s,box-shadow .2s;
    color:#1a1830;
}
.form-control:focus{
    border-color:#262341;
    box-shadow:0 0 0 3px rgba(38,35,65,0.1);outline:none;
}
.input-group .form-control{border-right:none;border-radius:10px 0 0 10px}
.input-group-text{
    border:1.5px solid #ddd9ee;border-left:none;
    border-radius:0 10px 10px 0;background:#fff;
    cursor:pointer;color:#4a4778;padding:0 14px;
    transition:background .2s;
}
.input-group-text:hover{background:#f5f4fb}
.input-group:focus-within .form-control,
.input-group:focus-within .input-group-text{border-color:#262341}
.input-group:focus-within .input-group-text{box-shadow:0 0 0 3px rgba(38,35,65,0.1)}

.btn-login{
    background:linear-gradient(135deg,#262341 0%,#332f5a 100%);
    border:none;color:#F9D94A;
    font-family:'Plus Jakarta Sans',sans-serif;
    font-weight:700;font-size:15px;border-radius:10px;
    padding:12px;letter-spacing:.3px;
    transition:all .25s;
    box-shadow:0 4px 16px rgba(38,35,65,0.3);
    position:relative;overflow:hidden;
}
.btn-login::after{
    content:'';position:absolute;bottom:0;left:0;right:0;height:3px;
    background:linear-gradient(90deg,#F9D94A,#E8C832);
    border-radius:0 0 10px 10px;
}
.btn-login:hover{
    background:linear-gradient(135deg,#1a1830 0%,#262341 100%);
    color:#F9D94A;transform:translateY(-1px);
    box-shadow:0 6px 20px rgba(38,35,65,0.38);
}
.btn-login:active{transform:translateY(0)}

.divider-line{
    display:flex;align-items:center;gap:12px;
    margin:24px 0;color:#a09cb8;font-size:11.5px;font-weight:500;
}
.divider-line::before,.divider-line::after{content:'';flex:1;border-top:1px solid #ddd9ee}

.creds-box{
    background:#fff;border-radius:10px;padding:12px 16px;
    border:1.5px solid #ddd9ee;
}
.creds-box .creds-role{font-size:12px;color:#262341;font-weight:700;margin-bottom:4px}
.creds-box .creds-val{font-size:12px;color:#6b6880}

.login-footer-text{text-align:center;font-size:11.5px;color:#a09cb8;margin-top:32px}
.login-footer-text strong{color:#262341}

.alert{border-radius:10px;font-size:13.5px}
.alert-danger{background:#fff0f2;border:1.5px solid #fecdd3;color:#7f1d1d}
.alert-success{background:#f0fdf4;border:1.5px solid #c6f6d5;color:#14532d}

@media(max-width:768px){
    body{flex-direction:column}
    .login-left{width:100%;padding:40px 24px 32px}
    .feature-list{display:none}
    .left-sub{margin-bottom:0}
    .login-right{padding:32px 20px}
}
</style>
</head>
<body>

<div class="login-left">
    <div class="deco-ring"></div>
    <div class="brand-logo-wrap" id="loginLogoWrap">
        <img id="loginLogoImg" src="" alt="Logo">
        <i class="bi bi-shop-window" id="loginLogoIcon"></i>
    </div>
    <div class="left-title" id="loginBrandName">7Evelyn</div>
    <div class="left-sub">Point of Sale System</div>

    <ul class="feature-list">
        <li><span class="fi"><i class="bi bi-cart3"></i></span> Fast &amp; Easy Point of Sale</li>
        <li><span class="fi"><i class="bi bi-archive"></i></span> Real-time Inventory Tracking</li>
        <li><span class="fi"><i class="bi bi-people"></i></span> Customer Credit Management</li>
        <li><span class="fi"><i class="bi bi-bar-chart-line"></i></span> Sales Reports &amp; Analytics</li>
        <li><span class="fi"><i class="bi bi-shield-check"></i></span> Role-based Access Control</li>
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

        <div class="creds-box">
            <div class="creds-role"><i class="bi bi-person-badge me-1"></i>Admin</div>
            <div class="creds-val">admin@7evelyn.com &nbsp;/&nbsp; admin123</div>
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
        const img = document.getElementById('loginLogoImg');
        const icon = document.getElementById('loginLogoIcon');
        if(img && icon){ img.src=logo; img.style.display='block'; icon.style.display='none'; }
    }
    if(name){
        const el = document.getElementById('loginBrandName');
        if(el) el.textContent = name;
    }
})();
</script>
</body>
</html>
