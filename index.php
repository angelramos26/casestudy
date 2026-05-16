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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body.login-page {
  min-height: 100vh;
  background: #0f172a;
  display: flex;
  font-family: 'Plus Jakarta Sans', sans-serif;
  overflow: hidden;
  position: relative;
}

/* ── Animated Background ── */
.bg-scene {
  position: fixed;
  inset: 0;
  z-index: 0;
  overflow: hidden;
  pointer-events: none;
}
.bg-gradient {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #0f172a 0%, #1a1740 40%, #0f172a 100%);
}
.bg-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
  background-size: 50px 50px;
}
.float-item {
  position: absolute;
  opacity: 0.07;
  animation: floatUp linear infinite;
  user-select: none;
}
@keyframes floatUp {
  0%   { transform: translateY(110vh) rotate(0deg); opacity: 0; }
  10%  { opacity: 0.07; }
  90%  { opacity: 0.07; }
  100% { transform: translateY(-10vh) rotate(360deg); opacity: 0; }
}
.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(90px);
  animation: pulse-orb 5s ease-in-out infinite alternate;
}
.orb-1 { width: 500px; height: 500px; background: rgba(249,217,74,0.07);  top: -150px; left: -100px; animation-delay: 0s; }
.orb-2 { width: 350px; height: 350px; background: rgba(14,165,160,0.07);  bottom: -80px; right: -80px; animation-delay: 2.5s; }
.orb-3 { width: 250px; height: 250px; background: rgba(99,102,241,0.06);  top: 40%; left: 35%; animation-delay: 1.5s; }
@keyframes pulse-orb {
  0%   { transform: scale(1);   opacity: 0.5; }
  100% { transform: scale(1.3); opacity: 1; }
}

/* ── Layout ── */
.layout {
  position: relative;
  z-index: 10;
  display: flex;
  width: 100%;
  min-height: 100vh;
}

/* ── LEFT PANEL ── */
.left-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 60px 64px;
  position: relative;
}
.left-panel::after {
  content: '';
  position: absolute;
  right: 0; top: 10%; bottom: 10%;
  width: 1px;
  background: linear-gradient(to bottom, transparent, rgba(255,255,255,0.08), transparent);
}

.brand-wrap {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 48px;
  animation: fadeInLeft 0.7s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes fadeInLeft {
  from { opacity: 0; transform: translateX(-24px); }
  to   { opacity: 1; transform: translateX(0); }
}

.logo-icon-wrap {
  width: 56px; height: 56px;
  background: linear-gradient(135deg, #F9D94A, #E8C832);
  border-radius: 16px;
  display: flex; align-items: center; justify-content: center;
  font-size: 24px; color: #1a1830;
  box-shadow: 0 6px 24px rgba(249,217,74,0.3);
  flex-shrink: 0;
  position: relative; overflow: hidden;
}
.logo-icon-wrap img {
  width: 100%; height: 100%;
  object-fit: cover; border-radius: 14px;
  display: none; position: absolute; inset: 0;
}
.brand-text h2 {
  font-size: 22px; font-weight: 800;
  color: #fff; letter-spacing: -0.4px;
  line-height: 1;
}
.brand-text span {
  font-size: 11px; color: rgba(255,255,255,0.4);
  font-weight: 600; letter-spacing: 2px; text-transform: uppercase;
}

.left-tagline {
  animation: fadeInLeft 0.7s cubic-bezier(0.16,1,0.3,1) 0.1s both;
  margin-bottom: 48px;
}
.left-tagline h1 {
  font-size: clamp(2rem, 3.2vw, 2.9rem);
  font-weight: 800;
  color: #fff;
  line-height: 1.15;
  letter-spacing: -1px;
  margin-bottom: 16px;
}
.left-tagline h1 em {
  font-style: normal;
  color: #F9D94A;
}
.left-tagline p {
  font-size: 14.5px;
  color: rgba(255,255,255,0.42);
  line-height: 1.65;
  max-width: 380px;
}

.feature-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 14px;
  animation: fadeInLeft 0.7s cubic-bezier(0.16,1,0.3,1) 0.2s both;
}
.feature-list li {
  display: flex;
  align-items: center;
  gap: 14px;
  color: rgba(255,255,255,0.62);
  font-size: 13.5px;
  font-weight: 500;
}
.feat-icon {
  width: 38px; height: 38px;
  border-radius: 10px;
  background: rgba(249,217,74,0.09);
  border: 1px solid rgba(249,217,74,0.14);
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; color: #F9D94A;
  flex-shrink: 0;
}

/* ── RIGHT PANEL ── */
.right-panel {
  width: 480px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 48px;
}

.login-card {
  width: 100%;
  background: rgba(255,255,255,0.05);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 24px;
  padding: 40px 36px;
  box-shadow:
    0 25px 60px rgba(0,0,0,0.5),
    0 0 0 1px rgba(255,255,255,0.05) inset;
  animation: slideUp 0.65s cubic-bezier(0.16,1,0.3,1) 0.15s both;
}
@keyframes slideUp {
  from { opacity: 0; transform: translateY(28px); }
  to   { opacity: 1; transform: translateY(0); }
}

.card-heading {
  margin-bottom: 28px;
}
.card-heading h3 {
  font-size: 22px; font-weight: 800;
  color: #fff; letter-spacing: -0.4px;
  margin-bottom: 4px;
}
.card-heading p {
  font-size: 13px; color: rgba(255,255,255,0.38);
}

/* Alerts */
.login-error {
  display: none;
  background: rgba(239,68,68,0.15);
  border: 1px solid rgba(239,68,68,0.3);
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 13px; color: #fca5a5;
  margin-bottom: 16px;
}
.login-error i { margin-right: 6px; }
.login-success {
  background: rgba(16,185,129,0.15);
  border: 1px solid rgba(16,185,129,0.3);
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 13px; color: #6ee7b7;
  margin-bottom: 16px;
}
.login-success i { margin-right: 6px; }

/* Form */
.form-group { margin-bottom: 16px; }
.form-group label {
  display: block;
  font-size: 11.5px; font-weight: 700;
  color: rgba(255,255,255,0.52);
  margin-bottom: 6px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}
.form-group label i { margin-right: 6px; color: #F9D94A; }

.login-input {
  width: 100%;
  padding: 12px 16px;
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.11);
  border-radius: 12px;
  font-size: 14px;
  font-family: 'Plus Jakarta Sans', sans-serif;
  color: #fff;
  outline: none;
  transition: all 0.2s;
}
.login-input::placeholder { color: rgba(255,255,255,0.2); }
.login-input:focus {
  border-color: #F9D94A;
  background: rgba(249,217,74,0.06);
  box-shadow: 0 0 0 3px rgba(249,217,74,0.12);
}

.pw-wrap { position: relative; }
.pw-wrap .login-input { padding-right: 46px; }
.pw-toggle {
  position: absolute; right: 12px; top: 50%;
  transform: translateY(-50%);
  background: none; border: none; cursor: pointer;
  color: rgba(255,255,255,0.3); font-size: 17px;
  padding: 4px; transition: color 0.2s;
}
.pw-toggle:hover { color: #fff; }

.login-btn {
  width: 100%; margin-top: 8px;
  padding: 14px;
  background: linear-gradient(135deg, #F9D94A, #E8C832);
  border: none; border-radius: 12px;
  font-size: 15px; font-weight: 700;
  font-family: 'Plus Jakarta Sans', sans-serif;
  color: #1a1830; cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 20px rgba(249,217,74,0.28);
  letter-spacing: 0.02em;
}
.login-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 28px rgba(249,217,74,0.4);
}
.login-btn:active { transform: translateY(0); }

.divider {
  display: flex; align-items: center; gap: 12px;
  margin: 20px 0 14px;
  color: rgba(255,255,255,0.18);
  font-size: 10.5px; font-weight: 700;
  letter-spacing: 0.1em; text-transform: uppercase;
}
.divider::before, .divider::after {
  content: ''; flex: 1;
  border-top: 1px solid rgba(255,255,255,0.07);
}

.creds-box {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 12px;
  padding: 12px 16px;
}
.creds-role {
  font-size: 10.5px; font-weight: 700;
  color: #F9D94A; margin-bottom: 4px;
  text-transform: uppercase; letter-spacing: 0.08em;
}
.creds-val {
  font-size: 12.5px; color: rgba(255,255,255,0.38);
  font-family: 'Courier New', monospace;
}

.login-credits {
  text-align: center; margin-top: 24px;
  font-size: 11.5px; color: rgba(255,255,255,0.16);
}
.login-credits span { color: rgba(255,255,255,0.28); }

/* ── Responsive ── */
@media (max-width: 900px) {
  body.login-page { overflow-y: auto; }
  .layout { flex-direction: column; }
  .left-panel { padding: 48px 28px 32px; flex: none; }
  .left-panel::after { display: none; }
  .left-tagline h1 { font-size: 1.8rem; }
  .feature-list { display: none; }
  .right-panel { width: 100%; padding: 16px 20px 48px; }
}
</style>
</head>
<body class="login-page">

<!-- Animated Background -->
<div class="bg-scene">
  <div class="bg-gradient"></div>
  <div class="bg-grid"></div>
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>
  <div class="orb orb-3"></div>
  <div id="floatContainer"></div>
</div>

<div class="layout">

  <!-- LEFT: Branding & Tagline -->
  <div class="left-panel">

    <div class="brand-wrap">
      <div class="logo-icon-wrap" id="loginLogoWrap">
        <img id="loginLogoImg" src="" alt="Logo">
        <i class="bi bi-shop-window" id="loginLogoIcon"></i>
      </div>
      <div class="brand-text">
        <h2 id="loginBrandName">7Evelyn</h2>
        <span>Point of Sale System</span>
      </div>
    </div>

    <div class="left-tagline">
      <h1>Manage your store<br><em>smarter & faster.</em></h1>
      <p>A complete point-of-sale solution built for sari-sari stores. Track sales, manage inventory, and grow your business — all in one place.</p>
    </div>

    <ul class="feature-list">
      <li><span class="feat-icon"><i class="bi bi-cart3"></i></span> Fast &amp; Easy Point of Sale</li>
      <li><span class="feat-icon"><i class="bi bi-archive"></i></span> Real-time Inventory Tracking</li>
      <li><span class="feat-icon"><i class="bi bi-people"></i></span> Customer Credit Management</li>
      <li><span class="feat-icon"><i class="bi bi-bar-chart-line"></i></span> Sales Reports &amp; Analytics</li>
      <li><span class="feat-icon"><i class="bi bi-shield-check"></i></span> Role-based Access Control</li>
    </ul>

  </div>

  <!-- RIGHT: Login Form -->
  <div class="right-panel">
    <div class="login-card">

      <div class="card-heading">
        <h3>Welcome back 👋</h3>
        <p>Sign in to your account to continue</p>
      </div>

      <?php if(isset($_GET['invalid'])): ?>
      <div class="login-error" style="display:block;">
        <i class="bi bi-exclamation-circle"></i>
        <strong>Access Denied.</strong> Wrong email or password.
      </div>
      <?php endif; ?>

      <?php if(isset($_GET['logout'])): ?>
      <div class="login-success">
        <i class="bi bi-check-circle"></i>
        You've been signed out successfully.
      </div>
      <?php endif; ?>

      <form action="./backend/loginAuth.php" method="POST">
        <?php csrf_field(); ?>

        <div class="form-group">
          <label><i class="bi bi-envelope"></i>Email Address</label>
          <input type="email" name="email" class="login-input"
                 placeholder="Enter your email"
                 autocomplete="email" required autofocus>
        </div>

        <div class="form-group">
          <label><i class="bi bi-lock"></i>Password</label>
          <div class="pw-wrap">
            <input type="password" name="password" id="loginPass" class="login-input"
                   placeholder="Enter your password"
                   autocomplete="current-password" required>
            <button type="button" class="pw-toggle" onclick="togglePw()">
              <i class="bi bi-eye" id="eyeIcon"></i>
            </button>
          </div>
        </div>

        <button type="submit" name="loginAuth" class="login-btn">
          <i class="bi bi-box-arrow-in-right"></i> Sign In
        </button>
      </form>

      <div class="divider">default credentials</div>

      <div class="creds-box">
        <div class="creds-role"><i class="bi bi-person-badge"></i> Admin</div>
        <div class="creds-val">admin@7evelyn.com &nbsp;/&nbsp; admin123</div>
      </div>

      <div class="login-credits">
        &copy; <?php echo date('Y'); ?> <span>7Evelyn POS</span> &nbsp;&bull;&nbsp; v1.0.0
      </div>

    </div>
  </div>

</div>

<script>
const items = ['🛒','🥫','🍜','🧴','🍪','💊','🥛','🚬','🍭','📦','🧃','🥤','🍬','🧹','💡'];
const container = document.getElementById('floatContainer');

function createFloat() {
  const el = document.createElement('div');
  el.className = 'float-item';
  el.textContent = items[Math.floor(Math.random() * items.length)];
  el.style.left = Math.random() * 100 + 'vw';
  el.style.fontSize = (1.5 + Math.random() * 2) + 'rem';
  const dur = 8 + Math.random() * 12;
  el.style.animationDuration = dur + 's';
  el.style.animationDelay = Math.random() * -dur + 's';
  container.appendChild(el);
  setTimeout(() => el.remove(), (dur + 2) * 1000);
}
for (let i = 0; i < 20; i++) createFloat();
setInterval(createFloat, 1500);

function togglePw() {
  const p = document.getElementById('loginPass');
  const i = document.getElementById('eyeIcon');
  p.type = p.type === 'password' ? 'text' : 'password';
  i.className = p.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
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