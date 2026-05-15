<?php
$alerts = [
    'savedData'     => ['success', 'Saved!',          'Role created successfully.'],
    'updatedRole'   => ['success', 'Updated!',        'Role updated successfully.'],
    'roleDeleted'   => ['success', 'Deleted!',        'Role removed successfully.'],
    'nameDuplicate' => ['error',   'Duplicate Name',  'This role name already exists.'],
    'emptyFields'   => ['warning', 'Required Fields', 'Please fill in the role name.'],
];
foreach ($alerts as $key => [$icon, $title, $text]):
    if (isset($_GET[$key])): ?>
<script>
Swal.fire({icon:'<?php echo $icon; ?>',title:'<?php echo $title; ?>',text:'<?php echo $text; ?>'}).then(() => {
    window.history.replaceState({}, document.title, window.location.pathname);
});
</script>
<?php endif; endforeach; ?>
