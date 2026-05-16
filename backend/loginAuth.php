<?php
require_once 'database.php';
require_once __DIR__ . '/csrf.php';

if(isset($_POST['loginAuth'])){
    csrf_verify(); // validate token only on actual form submission
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT u.*, r.roleName FROM users u JOIN role r ON u.roleID = r.roleID WHERE u.email = ? AND u.dateDeleted IS NULL");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row    = $result->fetch_assoc();

    if($row && password_verify($password, $row['password'])){
        $_SESSION['userID']   = $row['userID'];
        $_SESSION['roleID']   = $row['roleID'];
        $_SESSION['roleName'] = $row['roleName'];
        $_SESSION['userName'] = $row['givenName'] . ' ' . $row['surName'];
        csrf_regenerate();
        header("Location: ../frontend/dashboard.php");
        exit();
    } else {
        header("Location: ../index.php?invalid");
        exit();
    }
}
?>
