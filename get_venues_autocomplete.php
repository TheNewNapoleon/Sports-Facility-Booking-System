<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['venues' => []]);
    exit;
}

$stmt = $conn->prepare("SELECT venue_id, name FROM venues WHERE status = 'active' OR status IS NULL ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
$venues = [];
while ($row = $result->fetch_assoc()) {
    $venues[] = $row['name'];
}
$stmt->close();

echo json_encode(['venues' => $venues]);
?>

