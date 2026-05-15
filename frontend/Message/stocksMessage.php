<?php
$alerts = [
    'stockIn'      => ['success', 'Stock In',   'Stock added successfully.'],
    'stockOut'     => ['success', 'Stock Out',  'Stock removed successfully.'],
    'stockAdjust'  => ['success', 'Adjusted',   'Stock adjusted successfully.'],
    'insufficient' => ['error',   'Insufficient Stock', 'Not enough stock for removal.'],
];
foreach ($alerts as $key => [$icon, $title, $text]):
    if (isset($_GET[$key])): ?>
<script>
Swal.fire({icon:'<?php echo $icon; ?>',title:'<?php echo $title; ?>',text:'<?php echo $text; ?>',timer:2000}).then(() => {
    window.history.replaceState({}, document.title, window.location.pathname);
});
</script>
<?php endif; endforeach; ?>
