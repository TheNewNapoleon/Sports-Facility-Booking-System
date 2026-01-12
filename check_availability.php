<?php
require_once 'db.php';

header('Content-Type: application/json');

// Get parameters
$venue_id = isset($_GET['venue_id']) ? trim($_GET['venue_id']) : '';
$court_id = isset($_GET['court_id']) ? intval($_GET['court_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';

if (empty($date)) {
    echo json_encode(['success' => false, 'message' => 'Date is required']);
    exit;
}

// Determine what to check: if court_id provided, check per court; otherwise per venue
$booked_times = [];

// Check for all active booking statuses (booked, pending, approved) - these block availability
if ($court_id > 0) {
    // Check specific court
    $stmt = $conn->prepare("SELECT booking_time FROM bookings WHERE court_id = ? AND booking_date = ? AND status IN ('booked', 'pending', 'approved')");
    $stmt->bind_param("is", $court_id, $date);
} else {
    // Check venue (legacy)
    $stmt = $conn->prepare("SELECT booking_time FROM bookings WHERE venue_id = ? AND booking_date = ? AND status IN ('booked', 'pending', 'approved')");
    $stmt->bind_param("ss", $venue_id, $date);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $time_12hr = convertTo12HourFormat($row['booking_time']);
    $booked_times[] = $time_12hr;
}

$stmt->close();

// Check for events that block this venue on this date
// Check if events table exists
$eventsTableExists = false;
$checkEventsTable = $conn->query("SHOW TABLES LIKE 'events'");
if ($checkEventsTable && $checkEventsTable->num_rows > 0) {
    $eventsTableExists = true;
}

if ($eventsTableExists && !empty($venue_id)) {
    // Find events for this venue where the selected date falls within the event date range
    $eventStmt = $conn->prepare("
        SELECT start_time, end_time 
        FROM events 
        WHERE venue_id = ? 
        AND ? BETWEEN start_date AND end_date
    ");
    $eventStmt->bind_param("ss", $venue_id, $date);
    $eventStmt->execute();
    $eventResult = $eventStmt->get_result();
    
    while ($eventRow = $eventResult->fetch_assoc()) {
        $eventStartTime = $eventRow['start_time'];
        $eventEndTime = $eventRow['end_time'];
        
        // Generate all time slots that overlap with the event time range
        $eventBlockedTimes = getTimeSlotsInRange($eventStartTime, $eventEndTime);
        
        // Add event-blocked times to booked_times (avoid duplicates)
        foreach ($eventBlockedTimes as $blockedTime) {
            if (!in_array($blockedTime, $booked_times)) {
                $booked_times[] = $blockedTime;
            }
        }
    }
    
    $eventStmt->close();
}

echo json_encode([
    'success' => true,
    'data' => [
        'booked_times' => $booked_times
    ]
]);

// Helper function to convert 24-hour to 12-hour format
// Format must match JavaScript timeSlots array: "7:00 AM", "8:00 AM", etc.
function convertTo12HourFormat($time) {
    // $time is like '14:00:00' or '07:00:00'
    $parts = explode(':', $time);
    $hour = (int)$parts[0];
    $minute = isset($parts[1]) ? (int)$parts[1] : 0;
    
    $period = $hour >= 12 ? 'PM' : 'AM';
    $hour = $hour % 12;
    if ($hour === 0) $hour = 12;
    
    // Return format matching JavaScript: "7:00 AM" (no leading zero for hour, space before AM/PM)
    return sprintf('%d:%02d %s', $hour, $minute, $period);
}

// Helper function to get all time slots that fall within an event's time range
// Standard booking time slots are hourly: 7:00 AM, 8:00 AM, 9:00 AM, ..., 10:00 PM
function getTimeSlotsInRange($startTime, $endTime) {
    $blockedSlots = [];
    
    // Parse start and end times
    $startParts = explode(':', $startTime);
    $endParts = explode(':', $endTime);
    
    $startHour = (int)$startParts[0];
    $startMinute = isset($startParts[1]) ? (int)$startParts[1] : 0;
    $endHour = (int)$endParts[0];
    $endMinute = isset($endParts[1]) ? (int)$endParts[1] : 0;
    
    // Convert to minutes for easier comparison
    $startMinutes = $startHour * 60 + $startMinute;
    $endMinutes = $endHour * 60 + $endMinute;
    
    // Standard booking slots: 7:00 AM (420 minutes) to 10:00 PM (1320 minutes), hourly
    // Check each hour slot from 7 AM to 10 PM
    for ($hour = 7; $hour <= 22; $hour++) {
        $slotMinutes = $hour * 60; // e.g., 7:00 AM = 420 minutes
        
        // Check if this time slot overlaps with the event
        // A slot overlaps if: slot start < event end AND slot end > event start
        // This means: if the slot starts before the event ends AND the slot ends after the event starts
        // We use <= for endMinutes to block slots that start exactly when event ends (event still active at that moment)
        if ($slotMinutes <= $endMinutes && ($slotMinutes + 60) > $startMinutes) {
            // This time slot is blocked by the event
            $timeStr = sprintf('%02d:00:00', $hour);
            $blockedSlots[] = convertTo12HourFormat($timeStr);
        }
    }
    
    return $blockedSlots;
}
?>