<?php
$alerts = [
    'savedData'       => ['success', 'Saved!',          'Supplier added successfully.'],
    'updatedSupplier' => ['success', 'Updated!',        'Supplier information updated.'],
    'supplierDeleted' => ['success', 'Deleted!',        'Supplier removed successfully.'],
    'emailExists'     => ['error',   'Email Exists',    'This email is already registered.'],
    'emptyFields'     => ['warning', 'Required Fields', 'Please fill in all required fields.'],
];
foreach ($alerts as $key => [$icon, $title, $text]):
    if (isset($_GET[$key])): ?>
<script>
Swal.fire({icon:'<?php echo $icon; ?>',title:'<?php echo $title; ?>',text:'<?php echo $text; ?>',timer:2000}).then(() => {
    window.history.replaceState({}, document.title, window.location.pathname);
});
</script>
<?php endif; endforeach; ?>
