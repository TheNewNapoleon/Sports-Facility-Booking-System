<?php
require_once 'db.php';

header('Content-Type: application/json');

try {
    $venue_id = isset($_GET['venue_id']) ? $_GET['venue_id'] : '';
    $date = isset($_GET['date']) ? $_GET['date'] : '';

    if (!$venue_id || !$date) {
        throw new Exception("Missing required parameters");
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception("Invalid date format");
    }

    // Get all possible time slots
    $allTimeSlots = [
        "07:00", "08:00", "09:00", "10:00", "11:00",
        "12:00", "13:00", "14:00", "15:00", "16:00",
        "17:00", "18:00", "19:00", "20:00", "21:00"
    ];

    // Check for existing bookings on this date
    $booked_times = [];

    // Determine if venue has a shared_group
    $shared_group = null;
    $gstmt = $conn->prepare("SELECT shared_group FROM venues WHERE venue_id = ? LIMIT 1");
    if ($gstmt) {
        $gstmt->bind_param('s', $venue_id);
        $gstmt->execute();
        $gres = $gstmt->get_result();
        if ($gres && $gres->num_rows > 0) {
            $grow = $gres->fetch_assoc();
            $shared_group = trim($grow['shared_group'] ?? '');
            if ($shared_group === '') $shared_group = null;
        }
        $gstmt->close();
    }

    // Build query for booked times
    if ($shared_group) {
        $query = "SELECT DISTINCT TIME_FORMAT(booking_time, '%H:%i') as time FROM bookings
                  WHERE booking_date = ? AND status IN ('approved', 'booked') AND (venue_id IN (SELECT venue_id FROM venues WHERE shared_group = ?))";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $date, $shared_group);
    } else {
        $query = "SELECT DISTINCT TIME_FORMAT(booking_time, '%H:%i') as time FROM bookings
                  WHERE venue_id = ? AND booking_date = ? AND status IN ('approved', 'booked')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $venue_id, $date);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $booked_times[] = $row['time'];
    }
    $stmt->close();

    // Check for events on this date
    $has_event = false;
    $eventStmt = $conn->prepare(
        "SELECT COUNT(*) as event_count FROM events WHERE venue_id = ? AND ? BETWEEN start_date AND end_date"
    );
    $eventStmt->bind_param("ss", $venue_id, $date);
    $eventStmt->execute();
    $eventResult = $eventStmt->get_result();
    $eventRow = $eventResult->fetch_assoc();
    $has_event = $eventRow['event_count'] > 0;
    $eventStmt->close();

    // If there's an event, all time slots are unavailable
    if ($has_event) {
        $available_slots = [];
    } else {
        // Filter out booked times
        $available_slots = array_filter($allTimeSlots, function($slot) use ($booked_times) {
            return !in_array($slot, $booked_times);
        });
        $available_slots = array_values($available_slots); // Re-index array
    }

    echo json_encode([
        'success' => true,
        'available_slots' => $available_slots,
        'has_event' => $has_event
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>