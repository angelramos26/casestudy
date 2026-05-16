<?php
require_once 'database.php';
require_once __DIR__ . '/csrf.php';
csrf_verify();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if($_SESSION['roleName'] !== 'Admin'){ header("Location: ../frontend/dashboard.php"); exit(); }

if(isset($_POST['saveSettings'])){
    $settings = [
        'store_name'       => sanitize($_POST['store_name'] ?? ''),
        'store_address'    => sanitize($_POST['store_address'] ?? ''),
        'store_contact'    => sanitize($_POST['store_contact'] ?? ''),
        'store_tin'        => sanitize($_POST['store_tin'] ?? ''),
        'receipt_footer'   => sanitize($_POST['receipt_footer'] ?? ''),
        'tax_enabled'      => isset($_POST['tax_enabled']) ? '1' : '0',
        'tax_rate'         => floatval($_POST['tax_rate'] ?? 0),
        'discount_senior'  => floatval($_POST['discount_senior'] ?? 20),
        'discount_pwd'     => floatval($_POST['discount_pwd'] ?? 20),
    ];

    $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    foreach($settings as $k => $v){
        $v = (string)$v;
        $stmt->bind_param("ss", $k, $v);
        $stmt->execute();
    }
    header("Location: ../frontend/settings.php?savedData"); exit();
}

// Backup
if(isset($_POST['backupDB'])){
    $dbName = $conn->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
    header("Location: ../frontend/settings.php?backupDone");
    exit();
}
?>
