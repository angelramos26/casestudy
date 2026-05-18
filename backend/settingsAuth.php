<?php
/**
 * settingsAuth.php — Restaurant POS
 * Saves restaurant system settings.
 * Extended from grocery version to include restaurant-specific settings:
 *   - service_charge rate
 *   - currency symbol
 *   - kitchen_display toggle
 *   - order types enabled (dine_in, takeout, delivery)
 *
 * Roles allowed: Admin only
 */
require_once 'database.php';
require_once __DIR__ . '/csrf.php';
csrf_verify();
if (!isset($_SESSION['userID'])) { header("Location: ../index.php"); exit(); }
if ($_SESSION['roleName'] !== 'Admin') {
    header("Location: ../frontend/dashboard.php"); exit();
}

if (isset($_POST['saveSettings'])) {
    $settings = [
        // Restaurant info
        'restaurant_name'    => sanitize($_POST['restaurant_name']    ?? ''),
        'restaurant_address' => sanitize($_POST['restaurant_address'] ?? ''),
        'restaurant_contact' => sanitize($_POST['restaurant_contact'] ?? ''),
        'restaurant_tin'     => sanitize($_POST['restaurant_tin']     ?? ''),
        'receipt_footer'     => sanitize($_POST['receipt_footer']     ?? ''),
        // Taxes & charges
        'tax_enabled'        => isset($_POST['tax_enabled'])     ? '1' : '0',
        'tax_rate'           => (string)floatval($_POST['tax_rate']         ?? 12),
        'service_charge'     => (string)floatval($_POST['service_charge']   ?? 10),
        // Discounts
        'discount_senior'    => (string)floatval($_POST['discount_senior']  ?? 20),
        'discount_pwd'       => (string)floatval($_POST['discount_pwd']     ?? 20),
        // Order types
        'dine_in_enabled'    => isset($_POST['dine_in_enabled'])   ? '1' : '0',
        'takeout_enabled'    => isset($_POST['takeout_enabled'])   ? '1' : '0',
        'delivery_enabled'   => isset($_POST['delivery_enabled'])  ? '1' : '0',
        // Kitchen display
        'kitchen_display'    => isset($_POST['kitchen_display'])   ? '1' : '0',
        // Currency
        'currency'           => sanitize($_POST['currency']        ?? '₱'),
    ];

    $stmt = $conn->prepare(
        "INSERT INTO system_settings (setting_key, setting_value)
         VALUES (?,?)
         ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"
    );
    foreach ($settings as $k => $v) {
        $stmt->bind_param("ss", $k, $v);
        $stmt->execute();
    }
    header("Location: ../frontend/settings.php?savedData"); exit();
}

// DB backup placeholder
if (isset($_POST['backupDB'])) {
    header("Location: ../frontend/settings.php?backupDone"); exit();
}
