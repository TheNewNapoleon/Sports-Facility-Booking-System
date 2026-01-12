<?php
require_once 'db.php';

header('Content-Type: application/json');

try {
    // Get all venues (including closed ones) so status changes from manage_facilities are reflected
    $stmt = $conn->prepare("SELECT venue_id, name, image, status FROM venues ORDER BY name");
    $stmt->execute();
    $result = $stmt->get_result();

    $venues = [];
    while ($row = $result->fetch_assoc()) {
        $venues[] = [
            'venue_id' => $row['venue_id'],
            'title' => $row['name'], // Map name to title for frontend
            'image' => $row['image'] ?: 'fac2.jpg',
            'status' => $row['status'] ?: 'open' // Default to 'open' if status is null
        ];
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'data' => $venues
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>