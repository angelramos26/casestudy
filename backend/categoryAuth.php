<?php
require_once 'database.php';
require_once 'pusher-broadcast.php';
require_once __DIR__ . '/csrf.php';
session_start();
csrf_verify();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }

if(isset($_POST['catSave'])){
    $name = sanitize($_POST['categoryName']);
    $stmt = $conn->prepare("CALL AddCategory(?)");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if($r['result']==='success'){
        pusherBroadcast('category-changed', ['action'=>'added','categoryName'=>$name,'by'=>$_SESSION['userName']??'']);
        header("Location: ../frontend/category.php?catAdded"); exit();
    }
    header("Location: ../frontend/category.php?catDuplicate"); exit();
}
if(isset($_POST['catUpdate'])){
    $id   = intval($_POST['categoryID']);
    $name = sanitize($_POST['categoryName']);
    $stmt = $conn->prepare("CALL UpdateCategory(?,?)");
    $stmt->bind_param("is", $id, $name);
    $stmt->execute();
    pusherBroadcast('category-changed', ['action'=>'updated','categoryID'=>$id,'categoryName'=>$name,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/category.php?catUpdated"); exit();
}
if(isset($_POST['catDelete'])){
    $id = intval($_POST['categoryID']);
    $chk = $conn->prepare("SELECT COUNT(*) AS c FROM product WHERE categoryID=? AND status='Active'");
    $chk->bind_param("i", $id);
    $chk->execute();
    $cnt = $chk->get_result()->fetch_assoc()['c'];
    if($cnt > 0){ header("Location: ../frontend/category.php?catHasProducts"); exit(); }
    $stmt = $conn->prepare("CALL DeleteCategory(?)");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    pusherBroadcast('category-changed', ['action'=>'deleted','categoryID'=>$id,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/category.php?catDeleted"); exit();
}
?>
