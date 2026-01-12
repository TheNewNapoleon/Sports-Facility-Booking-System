<?php
session_start();
include_once 'db.php';

$uid = $_SESSION['user_id'] ?? null;
if (!$uid) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $venue = $_POST['venue_id'] ?? '';
    $rating = intval($_POST['rating'] ?? 0);
    $comments = $_POST['comments'] ?? '';

    if (!$venue || $rating < 1 || $rating > 5) {
        $_SESSION['flash'] = "Please select venue and rating (1-5).";
        header("Location: feedback.php");
        exit;
    }

    // Insert (feedbacks.user_id is VARCHAR in your schema recommended to be varchar)
    $stmt = $conn->prepare("INSERT INTO feedbacks (user_id, venue_id, rating, comments) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $uid, $venue, $rating, $comments);
    if ($stmt->execute()) {
        $_SESSION['flash'] = "Thanks — feedback saved.";
    } else {
        $_SESSION['flash'] = "Error saving feedback.";
    }
    $stmt->close();
}
header("Location: feedback.php");
exit;
