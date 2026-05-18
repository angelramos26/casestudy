<?php
$currentYear = date('Y');
$storeName   = 'Beng\'s Unli Lugaw';
?>
<footer class="smpos-footer no-print">
    <div class="smpos-footer-inner">
        <div class="smpos-footer-brand">
            <i class="bi bi-cup-hot-fill smpos-footer-icon"></i>
            <span class="smpos-footer-name" id="footerStoreName"><?php echo htmlspecialchars($storeName); ?></span>
        </div>
        <div class="smpos-footer-center">
            <span class="smpos-footer-tagline">Management System</span>
        </div>
        <div class="smpos-footer-right">
            <span class="smpos-footer-copy">&copy; <?php echo $currentYear; ?> All rights reserved.</span>
        </div>
    </div>
</footer>
</div><!-- /.main-content -->
</div><!-- /.smpos-shell -->

<script>
(function(){
    const name = localStorage.getItem('ev_store_name');
    if(name){
        const el = document.getElementById('footerStoreName');
        if(el) el.textContent = name;
    }
})();
</script>
