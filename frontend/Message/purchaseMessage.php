<?php
$alerts = [
    'poCreated'   => ['success', 'PO Created',  'Purchase order created successfully.'],
    'poReceived'  => ['success', 'Received!',   'Inventory updated from purchase order.'],
    'poCancelled' => ['info',    'Cancelled',   'Purchase order has been cancelled.'],
    'emptyFields' => ['warning', 'Required Fields', 'Please fill in all required fields.'],
    'error'       => ['error',   'Error',        'An error occurred. Please try again.'],
];
foreach ($alerts as $key => [$icon, $title, $text]):
    if (isset($_GET[$key])): ?>
<script>
Swal.fire({icon:'<?php echo $icon; ?>',title:'<?php echo $title; ?>',text:'<?php echo $text; ?>',timer:2000}).then(() => {
    window.history.replaceState({}, document.title, window.location.pathname);
});
</script>
<?php endif; endforeach; ?>
