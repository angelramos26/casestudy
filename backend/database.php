<?php

// Load .env file
$env = parse_ini_file(__DIR__ . '/../.env');

$host = $env['DB_HOST'] ?? 'localhost';
$port = $env['DB_PORT'] ?? 3308;
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? '';
$db   = $env['DB_NAME'] ?? 'evelyn_pos';

$conn = new mysqli($host, $user, $pass, $db, (int)$port);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

function sanitize($data) {
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Get system setting helper
function getSetting($conn, $key) {
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result ? $result['setting_value'] : '';
}
