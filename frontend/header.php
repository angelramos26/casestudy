<?php
// header.php — shared <head> for all authenticated pages.
require_once __DIR__ . '/../backend/csrf.php';
require_once __DIR__ . '/../backend/pusher.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
<title><?php echo $pageTitle ?? '7Evelyn POS'; ?></title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<!-- SM-POS Unified Design System -->
<link rel="stylesheet" href="assets/css/smpos.css">

<!-- Scripts loaded early -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<style>
/* ══════════════════════════════════════════════════════════════════
   MASTER CSS VARIABLES — single source of truth for all pages
   smpos.css is loaded above; these definitions here serve as both
   the canonical values AND a guaranteed fallback if the file is slow.
   ══════════════════════════════════════════════════════════════════ */
:root {
    /* ── Brand Colors ── */
    --c-gold:        #F9D94A;
    --c-gold-dark:   #E8C832;
    --c-gold-dim:    #f0c830;
    --c-gold-soft:   #fef8d0;
    --c-gold-glow:   rgba(249,217,74,0.35);
    --c-navy:        #262341;
    --c-navy-deep:   #1a1830;
    --c-navy-mid:    #332f5a;
    --c-navy-light:  #4a4778;
    --c-navy-10:     rgba(38,35,65,0.08);
    --c-navy-20:     rgba(38,35,65,0.15);
    --c-ice:         #E7F5F5;
    --c-ice-dark:    #cde8e8;
    --c-white:       #ffffff;

    /* ── Semantic Colors ── */
    --c-success:     #22c87a;
    --c-success-bg:  #f0fdf4;
    --c-success-bdr: #c6f6d5;
    --c-danger:      #e8435a;
    --c-danger-bg:   #fff0f2;
    --c-danger-bdr:  #fecdd3;
    --c-warning:     #f59c2a;
    --c-warning-bg:  #fffbeb;
    --c-warning-bdr: #fed7aa;
    --c-info:        #3b82f6;
    --c-info-bg:     #eff6ff;
    --c-info-bdr:    #bfdbfe;

    /* ── Text ── */
    --t-main:  #1a1830;
    --t-mid:   #4a4669;
    --t-muted: #6b6880;
    --t-light: #a09cb8;

    /* ── Surface ── */
    --s-bg:          #EEF1F7;
    --s-card:        #ffffff;
    --s-border:      #e5e2f0;
    --s-border-dark: #cde8e8;

    /* ── Typography ── */
    --font-ui:   'Plus Jakarta Sans', system-ui, sans-serif;
    --font-base: 'Plus Jakarta Sans', sans-serif;
    --font-mono: 'JetBrains Mono', 'Courier New', monospace;

    /* ── Radii ── */
    --r-xs:  6px;
    --r-sm:  8px;
    --r-md:  12px;
    --r-lg:  16px;
    --r-xl:  20px;
    --r-2xl: 28px;

    /* ── Shadows ── */
    --sh-xs:   0 1px 4px rgba(38,35,65,0.06);
    --sh-sm:   0 2px 8px rgba(38,35,65,0.08);
    --sh-md:   0 6px 24px rgba(38,35,65,0.12);
    --sh-lg:   0 16px 48px rgba(38,35,65,0.16);
    --sh-gold: 0 4px 16px rgba(249,217,74,0.4);
    /* legacy shadow names */
    --shadow-sm:  0 2px 8px rgba(38,35,65,0.08);
    --shadow-md:  0 6px 24px rgba(38,35,65,0.12);
    --shadow-lg:  0 16px 48px rgba(38,35,65,0.16);
    --shadow-glow-accent: 0 4px 20px rgba(249,217,74,0.4);
    --shadow-glow-teal:   0 4px 20px rgba(14,165,160,0.3);

    /* ── Gradients ── */
    --g-navy:    linear-gradient(135deg, #1a1830 0%, #262341 60%, #332f5a 100%);
    --g-gold:    linear-gradient(135deg, #F9D94A 0%, #E8C832 100%);
    --g-teal:    linear-gradient(135deg, #0EA5A0 0%, #0D8F8A 100%);
    --g-violet:  linear-gradient(135deg, #6C63FF 0%, #4A43D9 100%);
    --g-danger:  linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
    --g-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
    --g-warning: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    /* legacy gradient names */
    --ev-gradient:         linear-gradient(135deg,#1a1830 0%,#262341 100%);
    --ev-gradient-accent:  linear-gradient(135deg,#F9D94A 0%,#E8C832 100%);
    --ev-gradient-teal:    linear-gradient(135deg,#0EA5A0 0%,#0D8F8A 100%);
    --ev-gradient-purple:  linear-gradient(135deg,#6C63FF 0%,#4A43D9 100%);
    --ev-gradient-rose:    linear-gradient(135deg,#F43F5E 0%,#E11D48 100%);
    --ev-gradient-amber:   linear-gradient(135deg,#F59E0B 0%,#D97706 100%);
    --ev-gradient-emerald: linear-gradient(135deg,#10B981 0%,#059669 100%);
    --grad-sales:    linear-gradient(135deg,#1a1830 0%,#262341 100%);
    --grad-revenue:  linear-gradient(135deg,#F9D94A 0%,#E8C832 100%);
    --grad-products: linear-gradient(135deg,#0EA5A0 0%,#0D8F8A 100%);
    --grad-customers:linear-gradient(135deg,#6C63FF 0%,#4A43D9 100%);
    --grad-warning:  linear-gradient(135deg,#F59E0B 0%,#D97706 100%);
    --grad-danger:   linear-gradient(135deg,#F43F5E 0%,#E11D48 100%);
    --grad-success:  linear-gradient(135deg,#10B981 0%,#059669 100%);
    --grad-neutral:  linear-gradient(135deg,#E7F5F5 0%,#C8E8E8 100%);

    /* ── Legacy --ev-* color aliases ── */
    --ev-primary:     #262341;
    --ev-primary-dk:  #1a1830;
    --ev-primary-mid: #332f5a;
    --ev-accent:      #F9D94A;
    --ev-accent-dk:   #E8C832;
    --ev-teal:        #0EA5A0;
    --ev-white:       #ffffff;
    --ev-bg:          #EEF1F7;

    /* ── Layout ── */
    --sidebar-w: 248px;
    --topbar-h:  60px;

    /* ══════════════════════════════════════════════════════
       LEGACY ALIASES — maps every old per-page variable name
       to the correct token so no page needs to be edited.
       ══════════════════════════════════════════════════════ */

    /* Group A: expense, pos, sales, supplier, user */
    --gold:        #F9D94A;
    --gold-dark:   #E8C832;
    --gold-dim:    #f0c830;
    --gold-light:  #fef8d0;
    --gold-soft:   #fef8d0;
    --navy:        #262341;
    --navy-mid:    #332f5a;
    --navy-light:  #4a4778;
    --ice:         #E7F5F5;
    --ice-dark:    #cde8e8;
    --white:       #ffffff;
    --text-main:   #1a1830;
    --text-dark:   #1a1830;
    --text-mid:    #4a4669;
    --text-muted:  #6b6880;
    --text-light:  #a09cb8;
    --danger:      #e8435a;
    --danger-light:#fff0f2;
    --danger-lt:   #fff0f2;
    --success:     #22c87a;
    --warn:        #f59c2a;
    --warn-lt:     #fffbeb;
    --info:        #3b82f6;
    --radius-sm:   8px;
    --radius-md:   12px;
    --radius-lg:   16px;
    --border:      #e5e2f0;
    --transition:  all 0.2s ease;

    /* Group B: customer, purchase, reports, role */
    --brand-navy:     #262341;
    --brand-yellow:   #F9D94A;
    --brand-yellow-d: #E8C832;
    --brand-mint:     #E7F5F5;
    --card-bg:        #ffffff;

    /* Group C: profile, settings */
    --mint:      #E7F5F5;
    --mint-dark: #cde8e8;

    /* Group D: product, stocks */
    --col-navy:      #262341;
    --col-navy-10:   rgba(38,35,65,0.08);
    --col-navy-80:   rgba(38,35,65,0.80);
    --col-yellow:    #F9D94A;
    --col-yellow-30: rgba(249,217,74,0.30);
    --col-mint:      #E7F5F5;
    --col-col-mint:  #E7F5F5;
    --col-danger:    #e8435a;
    --col-success:   #22c87a;
    --col-warning:   #f59c2a;
    --col-muted:     #6b6880;
    --radius-card:   16px;
    --radius-pill:   99px;

    /* Group E: user.php */
    --frost:     #EEF1F7;
    --frost-mid: #e5e2f0;
    --off-white: #fafafa;
}

/* ── Layout ── */
.main-wrapper { display:flex; min-height:100vh; }
.main-content { flex:1; min-width:0; overflow-y:auto; padding-top:60px; }
.page-body    { padding:28px 32px; }

/* ── Sidebar ── */
.sidebar{width:var(--sidebar-w);min-width:var(--sidebar-w);height:100vh;position:sticky;top:0;background:linear-gradient(180deg,#0D0B2B 0%,#1a1830 45%,#262341 100%);color:#fff;overflow-y:auto;overflow-x:hidden;flex-shrink:0;box-shadow:4px 0 28px rgba(26,24,48,0.3)}
.sidebar::-webkit-scrollbar{width:4px}
.sidebar::-webkit-scrollbar-thumb{background:rgba(249,217,74,0.25);border-radius:4px}
.sidebar-brand{padding:22px 16px 16px;border-bottom:1px solid rgba(249,217,74,0.15);text-align:center}
.sidebar-logo{width:52px;height:52px;background:linear-gradient(135deg,#F9D94A,#E8C832);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:#262341;margin:0 auto 8px;box-shadow:0 4px 20px rgba(249,217,74,0.4);overflow:hidden}
.sidebar-brand .brand-name{font-size:1.05rem;font-weight:800;color:#fff;letter-spacing:-0.3px}
.sidebar-brand .brand-sub{font-size:9px;color:rgba(255,255,255,0.35);letter-spacing:2px;text-transform:uppercase}
.nav-section-label{font-size:9px;text-transform:uppercase;letter-spacing:2px;color:rgba(249,217,74,0.5);padding:16px 20px 5px;font-weight:700}
.sidebar a{color:rgba(255,255,255,0.62);text-decoration:none;display:flex;align-items:center;gap:10px;padding:9px 16px;font-size:13px;font-weight:500;transition:all 0.2s;margin:1px 10px;border-radius:10px}
.sidebar a:hover{background:rgba(249,217,74,0.1);color:#F9D94A;padding-left:20px}
.sidebar a.active{background:linear-gradient(135deg,#F9D94A,#E8C832);color:#262341;box-shadow:0 4px 20px rgba(249,217,74,0.4);font-weight:700}
.sidebar a i{font-size:1rem;width:20px;text-align:center;flex-shrink:0}

/* ── Topbar (legacy .topbar) ── */
.topbar{background:#fff;border-bottom:1.5px solid rgba(38,35,65,0.1);padding:10px 20px;display:flex;align-items:center;box-shadow:var(--shadow-sm);position:fixed;top:0;left:var(--sidebar-w);right:0;z-index:100}
.topbar h5{margin:0;font-weight:800;color:#262341;font-size:1rem}
.user-badge{display:flex;align-items:center;gap:10px;background:#EEF1F7;padding:6px 14px;border-radius:25px;font-size:13px;border:1.5px solid rgba(38,35,65,0.12)}
.role-pill{background:linear-gradient(135deg,#1a1830,#262341);color:#F9D94A;padding:2px 10px;border-radius:12px;font-size:10px;font-weight:700}

/* ── Unified ev-topbar (used by topbar.php) ── */
.ev-topbar{background:#fff;border-bottom:1.5px solid rgba(38,35,65,0.1);padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;gap:12px;box-shadow:0 1px 12px rgba(38,35,65,0.08);position:fixed;top:0;left:var(--sidebar-w);right:0;z-index:200;flex-shrink:0}
.ev-topbar-left{display:flex;align-items:center;gap:12px;min-width:0}
.ev-topbar-icon{width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#1a1830,#262341);display:flex;align-items:center;justify-content:center;color:#F9D94A;font-size:1rem;flex-shrink:0;box-shadow:var(--shadow-sm)}
.ev-topbar-text{display:flex;flex-direction:column;justify-content:center;line-height:1.2;min-width:0}
.ev-topbar-title{font-size:.95rem;font-weight:800;color:#262341;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ev-topbar-sub{font-size:.7rem;color:#6b6880;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ev-topbar-right{display:flex;align-items:center;gap:10px;flex-shrink:0}
.ev-topbar-actions{display:flex;align-items:center;gap:8px}
.ev-user-badge{display:flex;align-items:center;gap:8px;background:#EEF1F7;padding:6px 14px 6px 10px;border-radius:50px;border:1.5px solid rgba(38,35,65,0.12)}
.ev-user-icon{font-size:1.3rem;color:#262341;flex-shrink:0}
.ev-user-info{display:flex;align-items:center;gap:7px}
.ev-user-name{font-size:13px;font-weight:600;color:#262341;max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ev-role-pill{background:linear-gradient(135deg,#1a1830,#262341);color:#F9D94A;padding:2px 10px;border-radius:12px;font-size:10px;font-weight:700;letter-spacing:.5px;white-space:nowrap}
.ev-date-chip{font-size:12px;font-weight:600;color:#262341;background:#EEF1F7;padding:4px 12px;border-radius:20px;border:1.5px solid rgba(38,35,65,0.12);white-space:nowrap}

/* ── Buttons (legacy .btn-ev / .btn-ev-accent) ── */
.btn-ev{background:linear-gradient(135deg,#1a1830,#262341);color:#F9D94A;border:none;font-weight:700;border-radius:10px;transition:all 0.2s;box-shadow:var(--shadow-sm)}
.btn-ev:hover{opacity:.9;color:#F9D94A;transform:translateY(-1px);box-shadow:var(--shadow-md)}
.btn-ev:active{transform:translateY(0)}
.btn-ev-accent{background:linear-gradient(135deg,#F9D94A,#E8C832);color:#262341;border:none;font-weight:700;border-radius:10px;transition:all 0.2s;box-shadow:0 4px 20px rgba(249,217,74,0.4)}
.btn-ev-accent:hover{opacity:.9;color:#262341;transform:translateY(-1px)}

/* Bootstrap btn overrides */
.btn-outline-primary{color:#262341!important;border-color:#262341!important}
.btn-outline-primary:hover{background:#262341!important;color:#F9D94A!important}
.btn-outline-danger{color:#e8435a!important;border-color:#e8435a!important}
.btn-outline-danger:hover{background:#e8435a!important;color:#fff!important}
.btn-outline-success{color:#059669!important;border-color:#059669!important}
.btn-outline-success:hover{background:#059669!important;color:#fff!important}
.btn-outline-warning{color:#d97706!important;border-color:#d97706!important}
.btn-outline-warning:hover{background:#d97706!important;color:#fff!important;border-color:#d97706!important}
.btn-danger{background:var(--grad-danger)!important;border:none!important;color:#fff!important;font-weight:700!important}
.btn-success{background:var(--grad-success)!important;border:none!important;color:#fff!important;font-weight:700!important}
.btn-warning{background:var(--grad-warning)!important;border:none!important;color:#fff!important;font-weight:700!important}
.btn-primary{background:#262341!important;border-color:#262341!important;color:#F9D94A!important;font-weight:700!important}
.btn-primary:hover{background:#332f5a!important;border-color:#332f5a!important}

/* ── Cards ── */
.card{border-radius:var(--r-lg)!important;border:1.5px solid rgba(38,35,65,0.1)!important;box-shadow:var(--shadow-sm)!important}
.stat-card{border-radius:var(--r-lg);padding:22px;color:#fff;position:relative;overflow:hidden;box-shadow:var(--shadow-md)}
.stat-card::before{content:'';position:absolute;top:-30px;right:-30px;width:110px;height:110px;background:rgba(255,255,255,0.08);border-radius:50%}
.stat-card::after{content:'';position:absolute;bottom:-20px;left:-20px;width:80px;height:80px;background:rgba(255,255,255,0.05);border-radius:50%}
.stat-card .stat-icon{font-size:2rem;opacity:.9;margin-bottom:12px;display:block}
.stat-card .stat-val{font-size:1.9rem;font-weight:800;line-height:1;font-family:var(--font-mono)}
.stat-card .stat-lbl{font-size:12px;opacity:.8;margin-top:5px;font-weight:500;letter-spacing:.5px;text-transform:uppercase}
.bg-grad-purple{background:var(--grad-sales)}
.bg-grad-blue{background:var(--grad-customers)}
.bg-grad-green{background:var(--grad-revenue);color:#262341!important}
.bg-grad-orange{background:var(--grad-warning)}
.bg-grad-red{background:var(--grad-danger)}
.bg-grad-teal{background:var(--grad-products)}

/* scard variants */
.scard{border-radius:var(--r-lg);padding:22px;position:relative;overflow:hidden;min-height:116px;display:flex;flex-direction:column;justify-content:space-between;box-shadow:var(--shadow-md);transition:transform .2s,box-shadow .2s}
.scard:hover{transform:translateY(-2px);box-shadow:var(--shadow-lg)}
.scard::before{content:'';position:absolute;bottom:-18px;right:-18px;width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.08)}
.scard-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;margin-bottom:14px;flex-shrink:0}
.scard-val{font-size:1.65rem;font-weight:800;line-height:1;letter-spacing:-.5px;font-family:var(--font-mono)}
.scard-lbl{font-size:11px;font-weight:600;margin-top:4px;opacity:.72;letter-spacing:.5px;text-transform:uppercase}
.scard-navy{background:linear-gradient(135deg,#1a1830 0%,#262341 60%,#332f5a 100%);color:#fff}
.scard-navy .scard-icon{background:rgba(249,217,74,0.18);color:#F9D94A}
.scard-yellow{background:linear-gradient(135deg,#F9D94A,#E8C832);color:#262341}
.scard-yellow .scard-icon{background:rgba(38,35,65,0.12);color:#262341}
.scard-yellow .scard-lbl{opacity:.55}
.scard-mint{background:linear-gradient(135deg,#0EA5A0,#0D8F8A);color:#fff}
.scard-mint .scard-icon{background:rgba(255,255,255,0.2);color:#fff}
.scard-deep{background:linear-gradient(135deg,#6C63FF,#4A43D9);color:#fff}
.scard-deep .scard-icon{background:rgba(255,255,255,0.15);color:#fff}

/* ── Modal header overrides ── */
.modal-content{border-radius:var(--r-lg)!important;border:none!important;box-shadow:var(--shadow-lg)}
.modal-header.bg-danger{background:var(--grad-danger)!important;border:none}
.modal-header.bg-success{background:var(--grad-success)!important;border:none}
.modal-header.bg-warning{background:var(--grad-warning)!important;border:none;color:#fff!important}
.modal-header.bg-primary{background:var(--ev-gradient)!important;border:none}

/* ── Tables ── */
.table th{font-size:11px;white-space:nowrap;font-weight:700;letter-spacing:.5px;text-transform:uppercase}
.table td{font-size:13px;vertical-align:middle}

/* ── Badges ── */
.badge-active{background:#dcfce7;color:#166534;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700}
.badge-inactive{background:#fee2e2;color:#991b1b;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700}
.badge-low{background:#fef3c7;color:#92400e;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700}
.badge-pending{background:#fef3c7;color:#92400e;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700}
.badge-received{background:#dcfce7;color:#166534;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700}
.badge.bg-success{background:var(--grad-success)!important}
.badge.bg-danger{background:var(--grad-danger)!important}
.badge.bg-warning{background:var(--grad-warning)!important;color:#fff!important}
.badge.bg-secondary{background-color:#5a6070!important}
.badge.bg-primary{background:var(--ev-gradient)!important;color:#F9D94A!important}

/* ── Form focus ── */
.form-control:focus,.form-select:focus{border-color:#262341;box-shadow:0 0 0 3px rgba(38,35,65,0.1)}

/* ── Alerts ── */
.alert-warning{background:#fffbeb;border-left:4px solid #F59E0B;border-top:none;border-right:none;border-bottom:none;color:#78350f}
.alert-danger{background:#fff0f2;border-left:4px solid #f43f5e;border-top:none;border-right:none;border-bottom:none;color:#7f1d1d}
.alert-success{background:#f0fdf4;border-left:4px solid #10B981;border-top:none;border-right:none;border-bottom:none;color:#14532d}
.alert{border-radius:var(--r-sm);font-size:13.5px}

/* ── Print ── */
@media print{.sidebar,.topbar,.ev-topbar,.no-print{display:none!important}.main-content{margin:0!important;padding-top:0!important}}
@media(max-width:600px){.ev-topbar{height:auto;padding:10px 14px;flex-wrap:wrap;left:0}.topbar{left:0}.ev-topbar-sub,.ev-user-name{display:none}}
</style>

<script>
    const PUSHER_KEY     = '<?= htmlspecialchars(PUSHER_APP_KEY,     ENT_QUOTES, 'UTF-8') ?>';
    const PUSHER_CLUSTER = '<?= htmlspecialchars(PUSHER_APP_CLUSTER, ENT_QUOTES, 'UTF-8') ?>';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    function csrfFormData(fd) { fd.append('csrf_token', CSRF_TOKEN); return fd; }
</script>
</head>
<body>
<div class="main-wrapper">