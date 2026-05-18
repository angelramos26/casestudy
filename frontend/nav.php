<?php
$currentPage = basename($_SERVER['PHP_SELF']);
function navActive($page, $current) {
    return $page === $current ? ' active' : '';
}
?>
<div class="smpos-shell">
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo" id="sidebarLogoWrap">
            <img id="sidebarLogoImg" src="" alt="" style="width:100%;height:100%;object-fit:cover;display:none;border-radius:12px;">
            <i class="bi bi-knife" id="sidebarLogoIcon"></i>
        </div>
        <div class="brand-name" id="sidebarBrandName">Restaurant POS</div>
        <div class="brand-sub">Management System</div>
    </div>

    <div class="mt-2 pb-3">

        <?php if($_SESSION['roleName'] !== 'Kitchen'): ?>
        <a href="dashboard.php" class="<?php echo navActive('dashboard.php',$currentPage); ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <?php endif; ?>

        <!-- SERVICE — Admin + Cashier: POS, Tables, Kitchen; Kitchen: Kitchen only -->
        <?php if(in_array($_SESSION['roleName'], ['Admin','Cashier','Kitchen'])): ?>
        <div class="nav-section-label">Service</div>
        <?php if(in_array($_SESSION['roleName'], ['Admin','Cashier'])): ?>
        <a href="pos.php" class="<?php echo navActive('pos.php',$currentPage); ?>">
            <i class="bi bi-layout-text-window-reverse"></i> POS / Take Order
        </a>
        <a href="tables.php" class="<?php echo navActive('tables.php',$currentPage); ?>">
            <i class="bi bi-grid-3x3"></i> Table Map
        </a>
        <?php endif; ?>
        <a href="kitchen.php" class="<?php echo navActive('kitchen.php',$currentPage); ?>">
            <i class="bi bi-fire"></i> Kitchen Display
        </a>
        <?php endif; ?>

        <!-- MENU — Admin only -->
        <?php if($_SESSION['roleName'] === 'Admin'): ?>
        <div class="nav-section-label">Menu</div>
        <a href="menu.php" class="<?php echo navActive('menu.php',$currentPage); ?>">
            <i class="bi bi-journal-text"></i> Menu Items
        </a>
        <a href="category.php" class="<?php echo navActive('category.php',$currentPage); ?>">
            <i class="bi bi-tags"></i> Categories
        </a>
        <?php endif; ?>

        <!-- ORDERS & SALES — Admin + Cashier: orders; Admin + Owner: sales -->
        <?php if(in_array($_SESSION['roleName'], ['Admin','Cashier','Owner'])): ?>
        <div class="nav-section-label">Orders & Sales</div>
        <?php if(in_array($_SESSION['roleName'], ['Admin','Cashier'])): ?>
        <a href="orders.php" class="<?php echo navActive('orders.php',$currentPage); ?>">
            <i class="bi bi-receipt"></i> Order History
        </a>
        <?php endif; ?>
        <?php if(in_array($_SESSION['roleName'], ['Admin','Owner'])): ?>
        <a href="sales.php" class="<?php echo navActive('sales.php',$currentPage); ?>">
            <i class="bi bi-bar-chart-line"></i> Sales Records
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <!-- FINANCE — Admin + Owner only -->
        <?php if(in_array($_SESSION['roleName'], ['Admin','Owner'])): ?>
        <div class="nav-section-label">Finance</div>
        <a href="expense.php" class="<?php echo navActive('expense.php',$currentPage); ?>">
            <i class="bi bi-wallet2"></i> Expenses
        </a>
        <a href="reports.php" class="<?php echo navActive('reports.php',$currentPage); ?>">
            <i class="bi bi-graph-up-arrow"></i> Reports
        </a>
        <?php endif; ?>

        <!-- System Admin -->
        <?php if($_SESSION['roleName'] === 'Admin'): ?>
        <div class="nav-section-label">System</div>
        <a href="user.php" class="<?php echo navActive('user.php',$currentPage); ?>">
            <i class="bi bi-person-badge"></i> Users
        </a>
        <a href="role.php" class="<?php echo navActive('role.php',$currentPage); ?>">
            <i class="bi bi-shield-check"></i> Roles
        </a>
        <a href="settings.php" class="<?php echo navActive('settings.php',$currentPage); ?>">
            <i class="bi bi-gear"></i> Settings
        </a>
        <?php endif; ?>

        <div class="nav-section-label">Account</div>
        <a href="profile.php" class="<?php echo navActive('profile.php',$currentPage); ?>">
            <i class="bi bi-person-circle"></i> My Profile
        </a>
        <a href="#" onclick="confirmLogout(event)">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>

    </div>
</div>
<div class="main-content">

<script>
function confirmLogout(e){
    e.preventDefault();
    Swal.fire({
        title: 'Sign Out?',
        text: 'You will be logged out of the Restaurant POS.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e8435a',
        confirmButtonText: 'Yes, sign out',
        cancelButtonText: 'Cancel'
    }).then((r) => { if(r.isConfirmed) window.location.href = 'logout.php'; });
}

(function(){
    const logo = localStorage.getItem('ev_store_logo');
    const name = localStorage.getItem('ev_store_name');
    if(logo){
        const img = document.getElementById('sidebarLogoImg');
        const icon = document.getElementById('sidebarLogoIcon');
        if(img && icon){ img.src = logo; img.style.display = 'block'; icon.style.display = 'none'; }
    }
    if(name){
        const el = document.getElementById('sidebarBrandName');
        if(el) el.textContent = name;
    }
})();
</script>