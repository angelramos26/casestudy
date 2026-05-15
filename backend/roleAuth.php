<?php
require_once 'database.php';
require_once 'pusher-broadcast.php';
require_once __DIR__ . '/csrf.php';
session_start();
csrf_verify();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if($_SESSION['roleName'] !== 'Admin'){ header("Location: ../frontend/dashboard.php"); exit(); }

if(isset($_POST['roleSave'])){
    $name = sanitize($_POST['roleName']);
    $desc = sanitize($_POST['roleDesc'] ?? '');
    if(!$name){ header("Location: ../frontend/role.php?emptyFields"); exit(); }
    $stmt = $conn->prepare("CALL AddRole(?,?)");
    $stmt->bind_param("ss", $name, $desc);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if($r['result'] === 'duplicate_name'){ header("Location: ../frontend/role.php?nameDuplicate"); exit(); }
    pusherBroadcast('role-changed', ['action'=>'added','roleName'=>$name,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/role.php?savedData"); exit();
}

if(isset($_POST['roleUpdate'])){
    $id   = intval($_POST['roleID']);
    $name = sanitize($_POST['roleName']);
    $desc = sanitize($_POST['roleDesc'] ?? '');
    $stmt = $conn->prepare("CALL UpdateRole(?,?,?)");
    $stmt->bind_param("iss", $id, $name, $desc);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if($r['result'] === 'name_duplicate'){ header("Location: ../frontend/role.php?nameDuplicate"); exit(); }
    pusherBroadcast('role-changed', ['action'=>'updated','roleID'=>$id,'roleName'=>$name,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/role.php?updatedRole"); exit();
}

if(isset($_POST['roleDelete'])){
    $id   = intval($_POST['roleID']);
    $date = date('Y-m-d');
    $stmt = $conn->prepare("CALL DeleteRole(?,?)");
    $stmt->bind_param("is", $id, $date);
    $stmt->execute();
    pusherBroadcast('role-changed', ['action'=>'deleted','roleID'=>$id,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/role.php?roleDeleted"); exit();
}
?>
