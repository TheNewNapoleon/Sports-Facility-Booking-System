<?php
require_once 'db.php';

header('Content-Type: application/json');

try {
    $venue_id = isset($_GET['venue_id']) ? $_GET['venue_id'] : '';

    if (!$venue_id) {
        throw new Exception("Missing venue_id parameter");
    }

    // Fetch upcoming events for this venue
    $stmt = $conn->prepare("
        SELECT name, start_date, start_time, end_date, end_time 
        FROM events 
        WHERE venue_id = ? AND start_date >= CURDATE() 
        ORDER BY start_date ASC, start_time ASC 
        LIMIT 10
    ");
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("s", $venue_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'events' => $events
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>