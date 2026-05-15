<?php
session_start();
require_once __DIR__ . '/../backend/csrf.php';
session_unset();
csrf_regenerate(); // invalidate old token before destroying session
session_destroy();
header("Location: ../index.php?logout");
exit();
?>
