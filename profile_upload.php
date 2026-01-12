<?php
session_start();
require "db.php";

$user_id = $_SESSION['user_id'];

if (!isset($_FILES['avatar'])) {
    header("Location: profile.php?error=No file selected");
    exit;
}

$fileTmp = $_FILES['avatar']['tmp_name'];
$fileName = $_FILES['avatar']['name'];

$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowed = ['jpg','jpeg','png','gif'];

if (!in_array($ext, $allowed)) {
    header("Location: profile.php?error=Invalid file type");
    exit;
}

$targetDir = "uploads/";
$newName = "avatar_" . $user_id . "_" . time() . "." . $ext;
$targetFile = $targetDir . $newName;

if (!is_dir($targetDir)) mkdir($targetDir);

if (move_uploaded_file($fileTmp, $targetFile)) {

    $stmt = $conn->prepare("UPDATE users SET avatar_path=? WHERE user_id=?");
    $stmt->bind_param("ss", $targetFile, $user_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['avatar_path'] = $targetFile;

    header("Location: profile.php?success=Profile picture updated!");
} else {
    header("Location: profile.php?error=Upload failed");
}
?>
