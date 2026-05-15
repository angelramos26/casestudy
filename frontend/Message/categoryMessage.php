<?php
$alerts = [
    'catAdded'      => ['success', 'Category Added', 'New category has been added.'],
    'catUpdated'    => ['success', 'Updated',        'Category updated successfully.'],
    'catDeleted'    => ['success', 'Deleted',        'Category deleted successfully.'],
    'catDuplicate'  => ['error',   'Duplicate',      'Category name already exists.'],
    'catHasProducts'=> ['error',   'Cannot Delete',  'Category has existing products.'],
];
foreach ($alerts as $key => [$icon, $title, $text]):
    if (isset($_GET[$key])): ?>
<script>
Swal.fire({icon:'<?php echo $icon; ?>',title:'<?php echo $title; ?>',text:'<?php echo $text; ?>',timer:2000}).then(() => {
    window.history.replaceState({}, document.title, window.location.pathname);
});
</script>
<?php endif; endforeach; ?>
