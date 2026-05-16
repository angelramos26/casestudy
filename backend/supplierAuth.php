<?php
require_once 'database.php';
require_once 'pusher-broadcast.php';
require_once __DIR__ . '/csrf.php';
csrf_verify();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if(!in_array($_SESSION['roleName'], ['Admin','Owner'])){ header("Location: ../frontend/dashboard.php"); exit(); }

if(isset($_POST['supplierSave'])){
    $email   = sanitize($_POST['email'] ?? '');
    $company = sanitize($_POST['companyName']);
    $name    = sanitize($_POST['supplierName']);
    $contact = sanitize($_POST['contactNo'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    if(!$company || !$name){ header("Location: ../frontend/supplier.php?emptyFields"); exit(); }
    $stmt = $conn->prepare("CALL AddSupplier(?,?,?,?,?)");
    $stmt->bind_param("sssss", $email, $company, $name, $contact, $address);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if($r['result'] === 'duplicate_email'){ header("Location: ../frontend/supplier.php?emailExists"); exit(); }
    pusherBroadcast('supplier-changed', ['action'=>'added','companyName'=>$company,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/supplier.php?savedData"); exit();
}

if(isset($_POST['supplierUpdate'])){
    $id      = intval($_POST['supplierID']);
    $email   = sanitize($_POST['email'] ?? '');
    $company = sanitize($_POST['companyName']);
    $name    = sanitize($_POST['supplierName']);
    $contact = sanitize($_POST['contactNo'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $stmt = $conn->prepare("CALL UpdateSupplier(?,?,?,?,?,?)");
    $stmt->bind_param("isssss", $id, $email, $company, $name, $contact, $address);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if($r['result'] === 'email_duplicate'){ header("Location: ../frontend/supplier.php?emailExists"); exit(); }
    pusherBroadcast('supplier-changed', ['action'=>'updated','supplierID'=>$id,'companyName'=>$company,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/supplier.php?updatedSupplier"); exit();
}

if(isset($_POST['supplierDeleted'])){
    $id   = intval($_POST['supplierID']);
    $date = date('Y-m-d');
    $stmt = $conn->prepare("CALL DeleteSupplier(?,?)");
    $stmt->bind_param("is", $id, $date);
    $stmt->execute();
    pusherBroadcast('supplier-changed', ['action'=>'deleted','supplierID'=>$id,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/supplier.php?supplierDeleted"); exit();
}
?>
