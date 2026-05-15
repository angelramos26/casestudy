<?php
require_once 'database.php';
require_once 'pusher-broadcast.php';
require_once __DIR__ . '/csrf.php';
session_start();
csrf_verify();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if($_SESSION['roleName'] !== 'Admin'){ header("Location: ../frontend/dashboard.php"); exit(); }

if(isset($_POST['userSave'])){
    $roleID    = intval($_POST['roleID']);
    $userNo    = sanitize($_POST['userNo']);
    $email     = sanitize($_POST['email']);
    $password  = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $givenName = sanitize($_POST['givenName']);
    $midName   = sanitize($_POST['midName'] ?? '');
    $surName   = sanitize($_POST['surName']);
    $extName   = sanitize($_POST['extName'] ?? '');
    $gender    = sanitize($_POST['gender']);
    $birthdate = $_POST['birthdate'] ?: null;
    $civil     = sanitize($_POST['civilStatus'] ?? 'Single');
    $contactNo = sanitize($_POST['contactNo'] ?? '');
    if(!$roleID || !$email || !$givenName || !$surName){ header("Location: ../frontend/user.php?emptyFields"); exit(); }
    $stmt = $conn->prepare("CALL AddUser(?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("isssssssssss", $roleID,$userNo,$email,$password,$givenName,$midName,$surName,$extName,$gender,$birthdate,$civil,$contactNo);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if($r['result'] === 'duplicate_email'){ header("Location: ../frontend/user.php?emailExists"); exit(); }
    if($r['result'] === 'duplicate_userNo'){ header("Location: ../frontend/user.php?userNoExists"); exit(); }
    pusherBroadcast('user-changed', ['action'=>'added','name'=>"$givenName $surName",'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/user.php?savedData"); exit();
}

if(isset($_POST['userUpdate'])){
    $id        = intval($_POST['userID']);
    $roleID    = intval($_POST['roleID']);
    $userNo    = sanitize($_POST['userNo']);
    $email     = sanitize($_POST['email']);
    $givenName = sanitize($_POST['givenName']);
    $midName   = sanitize($_POST['midName'] ?? '');
    $surName   = sanitize($_POST['surName']);
    $extName   = sanitize($_POST['extName'] ?? '');
    $gender    = sanitize($_POST['gender']);
    $birthdate = $_POST['birthdate'] ?: null;
    $civil     = sanitize($_POST['civilStatus'] ?? 'Single');
    $contactNo = sanitize($_POST['contactNo'] ?? '');
    $stmt = $conn->prepare("CALL UpdateUser(?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("iissssssssss", $id,$roleID,$userNo,$email,$givenName,$midName,$surName,$extName,$gender,$birthdate,$civil,$contactNo);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if($r['result'] === 'email_duplicate'){ header("Location: ../frontend/user.php?emailExists"); exit(); }
    pusherBroadcast('user-changed', ['action'=>'updated','userID'=>$id,'name'=>"$givenName $surName",'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/user.php?updatedUser"); exit();
}

if(isset($_POST['userDelete'])){
    $id   = intval($_POST['userID']);
    $date = date('Y-m-d');
    $stmt = $conn->prepare("CALL DeleteUser(?,?)");
    $stmt->bind_param("is", $id, $date);
    $stmt->execute();
    pusherBroadcast('user-changed', ['action'=>'deleted','userID'=>$id,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/user.php?userDeleted"); exit();
}

if(isset($_POST['changePassword'])){
    $id      = intval($_POST['userID']);
    $newPass = password_hash($_POST['newPassword'], PASSWORD_BCRYPT);
    $stmt    = $conn->prepare("UPDATE users SET password=? WHERE userID=?");
    $stmt->bind_param("si", $newPass, $id);
    $stmt->execute();
    pusherBroadcast('user-changed', ['action'=>'password-changed','userID'=>$id,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/user.php?passwordChanged"); exit();
}
?>
