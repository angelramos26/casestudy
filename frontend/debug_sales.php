<?php
require_once '../backend/database.php';
if(!isset($_SESSION['userID'])){ die('Not logged in'); }
if($_SESSION['roleName'] !== 'Admin'){ die('Admin only'); }

echo "<pre style='font-family:monospace;padding:20px;'>";

// Count all orders by status
$r = $conn->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status");
echo "=== Orders by status ===\n";
while($row = $r->fetch_assoc()) echo $row['status'] . ': ' . $row['cnt'] . "\n";

// Latest 5 orders regardless of status
echo "\n=== Latest 5 orders ===\n";
$r = $conn->query("SELECT orderID, orderNo, status, datePaid, dateCreated, total_amount, payment_method FROM orders ORDER BY dateCreated DESC LIMIT 5");
while($row = $r->fetch_assoc()) print_r($row);

// Today's paid orders
$today = date('Y-m-d');
echo "\n=== Today's paid orders (" . $today . ") ===\n";
$r = $conn->query("SELECT orderID, orderNo, datePaid, total_amount FROM orders WHERE status='Paid' AND DATE(datePaid)='$today'");
echo "Count: " . $r->num_rows . "\n";
while($row = $r->fetch_assoc()) print_r($row);

// DB time vs PHP time
echo "\n=== Time check ===\n";
echo "PHP date: " . date('Y-m-d H:i:s') . "\n";
$r = $conn->query("SELECT NOW() as db_time");
echo "DB time: " . $r->fetch_assoc()['db_time'] . "\n";

echo "</pre>";
