<?php
$alerts = [
    'savedData'          => ['success', 'Saved!',         'Customer added successfully.'],
    'updatedCustomer'    => ['success', 'Updated!',       'Customer updated successfully.'],
    'customerDeleted'    => ['success', 'Deleted!',       'Customer removed successfully.'],
    'creditAdded'        => ['success', 'Credit Added',   'Utang recorded successfully.'],
    'creditPaid'         => ['success', 'Paid!',          'Credit payment recorded.'],
    'emailExists'        => ['error',   'Email Exists',   'Email already in use.'],
    'insufficientBalance'=> ['error',   'Insufficient',   'Payment exceeds current balance.'],
    'emptyFields'        => ['warning', 'Required Fields','Please fill in all required fields.'],
];
foreach ($alerts as $key => [$icon, $title, $text]):
    if (isset($_GET[$key])): ?>
<script>
Swal.fire({icon:'<?php echo $icon; ?>',title:'<?php echo $title; ?>',text:'<?php echo $text; ?>',timer:2000}).then(() => {
    window.history.replaceState({}, document.title, window.location.pathname);
});
</script>
<?php endif; endforeach; ?>
