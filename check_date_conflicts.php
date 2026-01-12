<?php
require_once 'db.php';

header('Content-Type: application/json');

try {
    $venue_id = isset($_GET['venue_id']) ? $_GET['venue_id'] : '';

    if (!$venue_id) {
        throw new Exception("Missing venue_id parameter");
    }

    $conflict_dates = [];

    // Get current date for filtering
    $today = date('Y-m-d');

    // Check for dates with bookings
    $bookingStmt = $conn->prepare("
        SELECT DISTINCT DATE(booking_date) as conflict_date
        FROM bookings
        WHERE venue_id = ? AND booking_date >= ? AND status IN ('approved', 'booked')
    ");
    $bookingStmt->bind_param("ss", $venue_id, $today);
    $bookingStmt->execute();
    $bookingResult = $bookingStmt->get_result();

    while ($row = $bookingResult->fetch_assoc()) {
        $conflict_dates[] = $row['conflict_date'];
    }
    $bookingStmt->close();

    // Check for dates with events (including multi-day events)
    $eventStmt = $conn->prepare("
        SELECT start_date, end_date
        FROM events
        WHERE venue_id = ?
    ");
    $eventStmt->bind_param("s", $venue_id);
    $eventStmt->execute();
    $eventResult = $eventStmt->get_result();

    while ($row = $eventResult->fetch_assoc()) {
        $start_date = $row['start_date'];
        $end_date = $row['end_date'];

        // If event spans multiple days, add all dates in the range
        $current_date = $start_date;
        while ($current_date <= $end_date) {
            if ($current_date >= $today && !in_array($current_date, $conflict_dates)) {
                $conflict_dates[] = $current_date;
            }
            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
        }
    }
    $eventStmt->close();

    echo json_encode([
        'success' => true,
        'conflict_dates' => $conflict_dates
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>