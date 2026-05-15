<?php
$alerts = [
    'savedData'      => ['success', 'Product Added',   'Product has been added successfully.'],
    'updatedProduct' => ['success', 'Product Updated', 'Product information updated.'],
    'productDeleted' => ['success', 'Deactivated',     'Product set to inactive.'],
    'barcodeExists'  => ['error',   'Barcode Exists',  'This barcode is already registered.'],
    'emptyFields'    => ['warning', 'Required Fields', 'Please fill in all required fields.'],
];
foreach ($alerts as $key => [$icon, $title, $text]):
    if (isset($_GET[$key])): ?>
<script>
Swal.fire({icon:'<?php echo $icon; ?>',title:'<?php echo $title; ?>',text:'<?php echo $text; ?>',timer:2500}).then(() => {
    window.history.replaceState({}, document.title, window.location.pathname);
});
</script>
<?php endif; endforeach; ?>
