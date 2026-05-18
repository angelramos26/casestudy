<?php
$topbarTitle = $topbarTitle ?? 'Page';
$topbarIcon  = $topbarIcon  ?? 'bi-house';
$topbarSub   = $topbarSub   ?? '';
$topbarExtra = $topbarExtra ?? '';
?>
<div class="smpos-topbar no-print">
    <div class="smpos-topbar-left">
        <div class="smpos-topbar-icon">
            <i class="bi <?php echo htmlspecialchars($topbarIcon); ?>"></i>
        </div>
        <div class="smpos-topbar-text">
            <span class="smpos-topbar-title"><?php echo htmlspecialchars($topbarTitle); ?></span>
            <?php if($topbarSub): ?>
            <span class="smpos-topbar-sub">— <?php echo htmlspecialchars($topbarSub); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="smpos-topbar-right">
        <?php if($topbarExtra): ?>
        <div class="smpos-topbar-actions"><?php echo $topbarExtra; ?></div>
        <?php endif; ?>
        <div class="smpos-user-badge">
            <i class="bi bi-person-circle smpos-user-badge-icon"></i>
            <div class="smpos-user-info">
                <span class="smpos-user-name"><?php echo htmlspecialchars($_SESSION['userName'] ?? ''); ?></span>
                <span class="smpos-role-pill"><?php echo htmlspecialchars($_SESSION['roleName'] ?? ''); ?></span>
            </div>
        </div>
    </div>
</div>
