<?php
require_once 'database.php';
require_once __DIR__ . '/csrf.php';
csrf_verify();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }

$userID = intval($_SESSION['userID']);

// Update profile info
if(isset($_POST['profileUpdate'])){
    $givenName = sanitize($_POST['givenName']);
    $midName   = sanitize($_POST['midName'] ?? '');
    $surName   = sanitize($_POST['surName']);
    $extName   = sanitize($_POST['extName'] ?? '');
    $gender    = sanitize($_POST['gender']);
    $birthdate = $_POST['birthdate'] ?: null;
    $civil     = sanitize($_POST['civilStatus'] ?? 'Single');
    $contactNo = sanitize($_POST['contactNo'] ?? '');

    if(!$givenName || !$surName){ header("Location: ../frontend/profile.php?emptyFields"); exit(); }

    $stmt = $conn->prepare("UPDATE users SET givenName=?, midName=?, surName=?, extName=?, gender=?, birthdate=?, civilStatus=?, contactNo=? WHERE userID=?");
    $stmt->bind_param("ssssssssi", $givenName, $midName, $surName, $extName, $gender, $birthdate, $civil, $contactNo, $userID);
    $stmt->execute();

    // Handle profile image upload
    if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK){
        $allowedTypes = ['image/jpeg','image/png','image/gif','image/webp'];
        $file = $_FILES['profile_image'];
        if(in_array($file['type'], $allowedTypes) && $file['size'] <= 2 * 1024 * 1024){
            $uploadDir = __DIR__ . '/../uploads/profiles/';
            if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            // Delete old
            $res = $conn->prepare("SELECT profile_image FROM users WHERE userID=?");
            $res->bind_param("i", $userID); $res->execute();
            $old = $res->get_result()->fetch_assoc()['profile_image'] ?? null;
            if($old && file_exists(__DIR__ . '/../' . $old)) @unlink(__DIR__ . '/../' . $old);
            // Save new
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'user_' . $userID . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($file['tmp_name'], $uploadDir . $filename);
            $imgPath = 'uploads/profiles/' . $filename;
            $stmt2 = $conn->prepare("UPDATE users SET profile_image=? WHERE userID=?");
            $stmt2->bind_param("si", $imgPath, $userID);
            $stmt2->execute();
            $_SESSION['profileImage'] = $imgPath;
        }
    }

    // Update session name
    $_SESSION['userName'] = trim("$givenName $surName");
    header("Location: ../frontend/profile.php?updated"); exit();
}

// Change own password
if(isset($_POST['changeOwnPassword'])){
    $current = $_POST['currentPassword'];
    $new     = $_POST['newPassword'];
    $confirm = $_POST['confirmPassword'];
    if($new !== $confirm){ header("Location: ../frontend/profile.php?pwMismatch"); exit(); }
    if(strlen($new) < 6){ header("Location: ../frontend/profile.php?pwShort"); exit(); }

    $stmt = $conn->prepare("SELECT password FROM users WHERE userID=?");
    $stmt->bind_param("i", $userID); $stmt->execute();
    $hash = $stmt->get_result()->fetch_assoc()['password'];
    if(!password_verify($current, $hash)){ header("Location: ../frontend/profile.php?pwWrong"); exit(); }

    $newHash = password_hash($new, PASSWORD_BCRYPT);
    $stmt2 = $conn->prepare("UPDATE users SET password=? WHERE userID=?");
    $stmt2->bind_param("si", $newHash, $userID);
    $stmt2->execute();
    header("Location: ../frontend/profile.php?pwChanged"); exit();
}
?>
