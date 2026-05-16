<?php
/**
 * topbar.php — Unified sticky top bar for all authenticated pages.
 *
 * Usage: <?php include 'topbar.php'; ?>
 *
 * Required variables (set before including):
 *   $topbarTitle  — Main page title text, e.g. "Dashboard"
 *   $topbarIcon   — Bootstrap Icons class, e.g. "bi-speedometer2"
 *   $topbarSub    — (optional) Subtitle/description string
 *   $topbarExtra  — (optional) Raw HTML string injected before the user badge
 *                   (use for page-specific buttons like Print, Export, etc.)
 */
$topbarTitle = $topbarTitle ?? 'Page';
$topbarIcon  = $topbarIcon  ?? 'bi-house';
$topbarSub   = $topbarSub   ?? '';
$topbarExtra = $topbarExtra ?? '';
?>
<div class="ev-topbar no-print">

    <!-- Left: icon + title -->
    <div class="ev-topbar-left">
        <div class="ev-topbar-icon">
            <i class="bi <?php echo htmlspecialchars($topbarIcon); ?>"></i>
        </div>
        <div class="ev-topbar-text">
            <span class="ev-topbar-title"><?php echo htmlspecialchars($topbarTitle); ?></span>
            <?php if ($topbarSub): ?>
            <span class="ev-topbar-sub"><?php echo htmlspecialchars($topbarSub); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: optional extra actions + user badge -->
    <div class="ev-topbar-right">
        <?php if ($topbarExtra): ?>
        <div class="ev-topbar-actions"><?php echo $topbarExtra; ?></div>
        <?php endif; ?>
        <div class="ev-user-badge">
            <i class="bi bi-person-circle ev-user-icon"></i>
            <div class="ev-user-info">
                <span class="ev-user-name"><?php echo htmlspecialchars($_SESSION['userName'] ?? ''); ?></span>
                <span class="ev-role-pill"><?php echo htmlspecialchars($_SESSION['roleName'] ?? ''); ?></span>
            </div>
        </div>
    </div>

</div>
