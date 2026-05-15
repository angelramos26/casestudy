<?php
// header.php — shared <head> for all authenticated pages.
// Requires: session_start() + require_once '../backend/database.php'
//           already called by the parent page.
require_once __DIR__ . '/../backend/csrf.php';
require_once __DIR__ . '/../backend/pusher.php';  // loads PUSHER_APP_KEY / CLUSTER from .env
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- CSRF token exposed to JS (read-only meta tag) -->
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
<title><?php echo $pageTitle ?? '7Evelyn POS'; ?></title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<!-- Scripts loaded early -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<style>
:root {
    /* ── Brand Palette ── */
    --ev-primary:     #008161;   /* teal-green */
    --ev-primary-dk:  #005e47;
    --ev-accent:      #f01b2d;   /* red */
    --ev-orange:      #f4821f;   /* orange */
    --ev-white:       #ffffff;

    /* aliases kept for compatibility */
    --ev-purple:      #008161;
    --ev-purple-dark: #005e47;
    --ev-blue:        #005e47;

    /* gradients */
    --ev-gradient:        linear-gradient(135deg, #008161 0%, #005e47 100%);
    --ev-gradient-accent: linear-gradient(135deg, #f01b2d 0%, #f4821f 100%);
    --ev-gradient-warm:   linear-gradient(135deg, #f4821f 0%, #f01b2d 100%);

    --ev-bg:       #f0f7f5;
    --sidebar-w:   240px;
}

body { background: var(--ev-bg); }

/* ════════════════════════════════
   SIDEBAR
════════════════════════════════ */
.sidebar {
    width: var(--sidebar-w);
    min-width: var(--sidebar-w);
    height: 100vh;
    background: linear-gradient(180deg, #002e22 0%, #004a38 50%, #003d2e 100%);
    color: white;
    position: sticky;
    top: 0;
    overflow-y: auto;
    overflow-x: hidden;
    flex-shrink: 0;
    border-right: 3px solid #f4821f;
}
.sidebar-brand {
    padding: 20px 16px 14px;
    border-bottom: 1px solid rgba(244,130,31,0.25);
    text-align: center;
}
.sidebar-logo {
    width: 52px; height: 52px;
    background: linear-gradient(135deg, #008161, #f4821f);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; color: white; margin: 0 auto 8px;
    box-shadow: 0 4px 15px rgba(244,130,31,0.45);
    overflow: hidden;
}
.sidebar-brand .brand-name { font-size: 1.1rem; font-weight: 800; color: #fff; }
.sidebar-brand .brand-sub  { font-size: 10px; color: rgba(255,255,255,0.4); letter-spacing: 1px; text-transform: uppercase; }

.nav-section-label {
    font-size: 9.5px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: rgba(244,130,31,0.6);
    padding: 14px 18px 4px;
    font-weight: 700;
}
.sidebar a {
    color: rgba(255,255,255,0.72);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 18px;
    font-size: 13.5px;
    transition: all 0.2s;
    margin: 1px 8px;
    border-radius: 8px;
}
.sidebar a:hover {
    background: rgba(0,129,97,0.35);
    color: #fff;
    padding-left: 22px;
}
.sidebar a.active {
    background: var(--ev-gradient);
    color: #fff;
    box-shadow: 0 3px 12px rgba(0,129,97,0.5);
    border-left: 3px solid #f4821f;
}
.sidebar a i { font-size: 1rem; width: 20px; text-align: center; }

/* Sidebar scrollbar */
.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(244,130,31,0.3); border-radius: 4px; }

/* ════════════════════════════════
   TOPBAR
════════════════════════════════ */
.topbar {
    background: #ffffff;
    border-bottom: 3px solid #008161;
    padding: 10px 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 8px rgba(0,129,97,0.1);
    position: sticky;
    top: 0;
    z-index: 100;
}
.topbar h5 { margin: 0; font-weight: 800; color: #004a38; font-size: 1rem; }
.user-badge {
    display: flex; align-items: center; gap: 10px;
    background: #f0f7f5; padding: 6px 14px;
    border-radius: 25px; font-size: 13px;
    border: 1px solid rgba(0,129,97,0.2);
}
.role-pill {
    background: var(--ev-gradient);
    color: white; padding: 2px 10px;
    border-radius: 12px; font-size: 11px; font-weight: 700;
    letter-spacing: 0.3px;
}

/* ════════════════════════════════
   BUTTONS
════════════════════════════════ */
.btn-ev {
    background: var(--ev-gradient);
    color: white; border: none; font-weight: 700;
    border-radius: 8px;
    transition: all 0.25s;
    box-shadow: 0 2px 8px rgba(0,129,97,0.25);
}
.btn-ev:hover {
    background: linear-gradient(135deg, #005e47, #008161);
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(0,129,97,0.4);
}
.btn-ev:active { transform: translateY(0); }

/* Accent / danger-flavored button */
.btn-ev-accent {
    background: var(--ev-gradient-accent);
    color: white; border: none; font-weight: 700;
    border-radius: 8px;
    transition: all 0.25s;
    box-shadow: 0 2px 8px rgba(240,27,45,0.25);
}
.btn-ev-accent:hover {
    opacity: 0.88; color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(240,27,45,0.35);
}

/* Override Bootstrap outline buttons to match palette */
.btn-outline-primary {
    color: #008161; border-color: #008161;
}
.btn-outline-primary:hover {
    background: #008161; border-color: #008161; color: #fff;
}
.btn-outline-danger {
    color: #f01b2d; border-color: #f01b2d;
}
.btn-outline-danger:hover {
    background: #f01b2d; border-color: #f01b2d; color: #fff;
}
.btn-outline-success {
    color: #008161; border-color: #008161;
}
.btn-outline-success:hover {
    background: #008161; border-color: #008161; color: #fff;
}
.btn-outline-warning {
    color: #f4821f; border-color: #f4821f;
}
.btn-outline-warning:hover {
    background: #f4821f; border-color: #f4821f; color: #fff;
}
.btn-danger {
    background-color: #f01b2d; border-color: #f01b2d;
}
.btn-danger:hover {
    background-color: #c9151f; border-color: #c9151f;
}
.btn-success {
    background-color: #008161; border-color: #008161;
}
.btn-success:hover {
    background-color: #005e47; border-color: #005e47;
}
.btn-warning {
    background-color: #f4821f; border-color: #f4821f; color: #fff;
}
.btn-warning:hover {
    background-color: #d96d10; border-color: #d96d10; color: #fff;
}

/* ════════════════════════════════
   CARDS
════════════════════════════════ */
.card { border-radius: 14px; border: none; }
.card-shadow { box-shadow: 0 4px 20px rgba(0,129,97,0.1); }
.stat-card {
    border-radius: 14px;
    padding: 20px;
    color: white;
    position: relative;
    overflow: hidden;
}
.stat-card::after {
    content: '';
    position: absolute;
    top: -20px; right: -20px;
    width: 100px; height: 100px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}
.stat-card .stat-icon { font-size: 2rem; opacity: 0.85; }
.stat-card .stat-val  { font-size: 1.8rem; font-weight: 800; line-height: 1; }
.stat-card .stat-lbl  { font-size: 12px; opacity: 0.85; margin-top: 4px; }

/* Gradient card variants */
.bg-grad-purple  { background: linear-gradient(135deg, #008161, #005e47); }
.bg-grad-blue    { background: linear-gradient(135deg, #005e47, #003d2e); }
.bg-grad-green   { background: linear-gradient(135deg, #008161, #00b47a); }
.bg-grad-orange  { background: linear-gradient(135deg, #f4821f, #f9a84d); }
.bg-grad-red     { background: linear-gradient(135deg, #f01b2d, #f4821f); }
.bg-grad-teal    { background: linear-gradient(135deg, #008161, #005e47); }

/* ════════════════════════════════
   MODAL HEADERS
════════════════════════════════ */
.modal-header.bg-danger   { background: var(--ev-gradient-accent) !important; border: none; }
.modal-header.bg-success  { background: var(--ev-gradient) !important; border: none; }
.modal-header.bg-warning  { background: var(--ev-gradient-warm) !important; border: none; color: #fff !important; }

/* ════════════════════════════════
   TABLES
════════════════════════════════ */
.table th { font-size: 12.5px; white-space: nowrap; }
.table td { font-size: 13px; vertical-align: middle; }
.table thead[style*="ev-gradient"] th,
thead[style*="ev-gradient"] th { color: #fff; }

/* ════════════════════════════════
   BADGES / STATUS PILLS
════════════════════════════════ */
.badge-active   { background: #d6f0e8; color: #004a38; padding: 3px 10px; border-radius: 12px; font-size: 11.5px; font-weight: 700; }
.badge-inactive { background: #fde8ea; color: #a50010; padding: 3px 10px; border-radius: 12px; font-size: 11.5px; font-weight: 700; }
.badge-low      { background: #fdecd8; color: #a8510a; padding: 3px 10px; border-radius: 12px; font-size: 11.5px; font-weight: 700; }
.badge-pending  { background: #fdecd8; color: #a8510a; padding: 3px 10px; border-radius: 12px; font-size: 11.5px; font-weight: 700; }
.badge-received { background: #d6f0e8; color: #004a38; padding: 3px 10px; border-radius: 12px; font-size: 11.5px; font-weight: 700; }

/* Bootstrap badge overrides */
.badge.bg-success  { background-color: #008161 !important; }
.badge.bg-danger   { background-color: #f01b2d !important; }
.badge.bg-warning  { background-color: #f4821f !important; color: #fff !important; }
.badge.bg-secondary{ background-color: #5a6070 !important; }

/* ════════════════════════════════
   FORM FOCUS COLORS
════════════════════════════════ */
.form-control:focus, .form-select:focus {
    border-color: #008161;
    box-shadow: 0 0 0 0.2rem rgba(0,129,97,0.18);
}

/* ════════════════════════════════
   ALERTS
════════════════════════════════ */
.alert-warning { background: #fef3e2; border-color: #f4821f; color: #7a3d00; }
.alert-danger  { background: #fde8ea; border-color: #f01b2d; color: #7a0010; }
.alert-success { background: #d6f0e8; border-color: #008161; color: #003d2e; }

/* ════════════════════════════════
   LAYOUT
════════════════════════════════ */
.main-wrapper { display: flex; min-height: 100vh; }
.main-content { flex: 1; min-width: 0; overflow-y: auto; }
.page-body { padding: 24px; }

/* ════════════════════════════════
   PRINT
════════════════════════════════ */
@media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .main-content { margin: 0 !important; }
}
</style>
<!-- Pusher credentials injected server-side from .env — never hardcoded in JS -->
<script>
    const PUSHER_KEY     = '<?= htmlspecialchars(PUSHER_APP_KEY,     ENT_QUOTES, 'UTF-8') ?>';
    const PUSHER_CLUSTER = '<?= htmlspecialchars(PUSHER_APP_CLUSTER, ENT_QUOTES, 'UTF-8') ?>';
    // CSRF helper — attach to every fetch/XHR request
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    function csrfFormData(fd) { fd.append('csrf_token', CSRF_TOKEN); return fd; }
</script>
</head>
<body>
<div class="main-wrapper">
