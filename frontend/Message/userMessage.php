<?php
$alerts = [
    'savedData'      => ['success', 'Saved!',            'User account created. Default password: 12345'],
    'updatedUser'    => ['success', 'Updated!',          'User updated successfully.'],
    'userDeleted'    => ['success', 'Deleted!',          'User removed successfully.'],
    'passwordChanged'=> ['success', 'Password Changed',  'Password updated successfully.'],
    'emailExists'    => ['error',   'Email Exists',      'This email is already in use.'],
    'userNoExists'   => ['error',   'Duplicate ID',      'User number already exists.'],
    'emptyFields'    => ['warning', 'Required Fields',   'Please fill in all required fields.'],
];
foreach ($alerts as $key => [$icon, $title, $text]):
    if (isset($_GET[$key])): ?>
<script>
Swal.fire({icon:'<?php echo $icon; ?>',title:'<?php echo $title; ?>',text:'<?php echo $text; ?>'}).then(() => {
    window.history.replaceState({}, document.title, window.location.pathname);
});
</script>
<?php endif; endforeach; ?>
