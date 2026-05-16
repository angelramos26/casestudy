<?php
require_once 'database.php';
require_once 'pusher-broadcast.php';
require_once __DIR__ . '/csrf.php';
csrf_verify();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if(!in_array($_SESSION['roleName'], ['Admin','Owner'])){ header("Location: ../frontend/dashboard.php"); exit(); }

$userID = $_SESSION['userID'];

if(isset($_POST['addExpenseCategory'])){
    $name = sanitize($_POST['categoryName']);
    if(!$name){ header("Location: ../frontend/expense.php?emptyFields"); exit(); }
    $stmt = $conn->prepare("INSERT INTO expense_category (categoryName) VALUES (?)");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    pusherBroadcast('expense-changed', ['action'=>'category-added','categoryName'=>$name,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/expense.php?categoryAdded"); exit();
}

if(isset($_POST['expenseSave'])){
    $catID       = intval($_POST['expenseCategoryID']);
    $amount      = floatval($_POST['amount']);
    $description = sanitize($_POST['description'] ?? '');
    $date        = $_POST['expense_date'] ?: date('Y-m-d');
    if(!$catID || $amount <= 0){ header("Location: ../frontend/expense.php?emptyFields"); exit(); }
    $stmt = $conn->prepare("CALL AddExpense(?,?,?,?,?)");
    $stmt->bind_param("idssi", $catID, $amount, $description, $date, $userID);
    $stmt->execute();
    pusherBroadcast('expense-changed', [
        'action'=>'added','amount'=>$amount,
        'description'=>$description,'date'=>$date,
        'by'=>$_SESSION['userName']??'',
    ]);
    header("Location: ../frontend/expense.php?savedData"); exit();
}

if(isset($_POST['expenseDelete'])){
    $id = intval($_POST['expenseID']);
    $stmt = $conn->prepare("DELETE FROM expense WHERE expenseID=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    pusherBroadcast('expense-changed', ['action'=>'deleted','expenseID'=>$id,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/expense.php?expenseDeleted"); exit();
}
?>
