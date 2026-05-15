<?php
$currentPage = basename($_SERVER['PHP_SELF']);
function navActive($page, $current) {
    return $page === $current ? ' active' : '';
}
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo" id="sidebarLogoWrap">
            <img id="sidebarLogoImg" src="" alt="" style="width:100%;height:100%;object-fit:cover;display:none;border-radius:14px;">
            <i class="bi bi-shop-window" id="sidebarLogoIcon"></i>
        </div>
        <div class="brand-name" id="sidebarBrandName">7Evelyn</div>
        <div class="brand-sub">POS System</div>
    </div>

    <div class="mt-2 pb-3">

        <a href="dashboard.php" class="<?php echo navActive('dashboard.php',$currentPage); ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <?php if(in_array($_SESSION['roleName'], ['Admin','Owner'])): ?>
        <div class="nav-section-label">Inventory</div>
        <a href="product.php"  class="<?php echo navActive('product.php',$currentPage); ?>"><i class="bi bi-box-seam"></i> Products</a>
        <a href="category.php" class="<?php echo navActive('category.php',$currentPage); ?>"><i class="bi bi-tags"></i> Categories</a>
        <a href="stocks.php"   class="<?php echo navActive('stocks.php',$currentPage); ?>"><i class="bi bi-archive"></i> Inventory</a>
        <?php elseif($_SESSION['roleName']==='Cashier'): ?>
        <div class="nav-section-label">Inventory</div>
        <a href="stocks.php"   class="<?php echo navActive('stocks.php',$currentPage); ?>"><i class="bi bi-archive"></i> Stock Viewer</a>
        <?php endif; ?>

        <div class="nav-section-label">Sales</div>
        <?php if(in_array($_SESSION['roleName'], ['Admin','Cashier'])): ?>
        <a href="pos.php"      class="<?php echo navActive('pos.php',$currentPage); ?>"><i class="bi bi-cart3"></i> Point of Sale</a>
        <?php endif; ?>
        <a href="sales.php"    class="<?php echo navActive('sales.php',$currentPage); ?>"><i class="bi bi-receipt"></i> Sales Records</a>

        <?php if(in_array($_SESSION['roleName'], ['Admin','Owner'])): ?>
        <div class="nav-section-label">People</div>
        <a href="customer.php" class="<?php echo navActive('customer.php',$currentPage); ?>"><i class="bi bi-people"></i> Customers</a>
        <a href="supplier.php" class="<?php echo navActive('supplier.php',$currentPage); ?>"><i class="bi bi-truck"></i> Suppliers</a>
        <?php elseif($_SESSION['roleName']==='Cashier'): ?>
        <div class="nav-section-label">People</div>
        <a href="customer.php" class="<?php echo navActive('customer.php',$currentPage); ?>"><i class="bi bi-people"></i> Customers</a>
        <?php endif; ?>

        <?php if(in_array($_SESSION['roleName'], ['Admin','Owner'])): ?>
        <div class="nav-section-label">Procurement</div>
        <a href="purchase.php" class="<?php echo navActive('purchase.php',$currentPage); ?>"><i class="bi bi-bag-check"></i> Purchase Orders</a>

        <div class="nav-section-label">Finance</div>
        <a href="expense.php"  class="<?php echo navActive('expense.php',$currentPage); ?>"><i class="bi bi-wallet2"></i> Expenses</a>
        <a href="reports.php"  class="<?php echo navActive('reports.php',$currentPage); ?>"><i class="bi bi-bar-chart-line"></i> Reports</a>
        <?php endif; ?>

        <?php if($_SESSION['roleName'] === 'Admin'): ?>
        <div class="nav-section-label">System</div>
        <a href="user.php"     class="<?php echo navActive('user.php',$currentPage); ?>"><i class="bi bi-person-badge"></i> Users</a>
        <a href="role.php"     class="<?php echo navActive('role.php',$currentPage); ?>"><i class="bi bi-shield-check"></i> Roles</a>
        <a href="settings.php" class="<?php echo navActive('settings.php',$currentPage); ?>"><i class="bi bi-gear"></i> Settings</a>
        <?php endif; ?>

        <div class="nav-section-label">Account</div>
        <a href="profile.php" class="<?php echo navActive('profile.php',$currentPage); ?>"><i class="bi bi-person-circle"></i> My Profile</a>
        <a href="#" onclick="confirmLogout(event)"><i class="bi bi-box-arrow-right"></i> Logout</a>

    </div>
</div>
<div class="main-content">

<script>
function confirmLogout(e){
    e.preventDefault();
    Swal.fire({
        title:'Sign Out?',
        text:'You will be logged out of 7Evelyn POS.',
        icon:'warning',
        showCancelButton:true,
        confirmButtonColor:'#f01b2d',
        confirmButtonText:'Yes, sign out',
        cancelButtonText:'Cancel'
    }).then((r)=>{ if(r.isConfirmed) window.location.href='logout.php'; });
}

// Load logo/store name from localStorage (set in Settings)
(function(){
    const logo = localStorage.getItem('ev_store_logo');
    const name = localStorage.getItem('ev_store_name');
    if(logo){
        const img = document.getElementById('sidebarLogoImg');
        const icon = document.getElementById('sidebarLogoIcon');
        if(img && icon){ img.src = logo; img.style.display='block'; icon.style.display='none'; }
    }
    if(name){
        const el = document.getElementById('sidebarBrandName');
        if(el) el.textContent = name;
    }
})();
</script>
