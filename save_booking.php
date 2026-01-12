<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON header first to prevent any output issues
header('Content-Type: application/json');

// Wrap everything in try-catch to catch fatal errors
try {
    session_start();
    
    // Check if db.php exists and can be loaded
    if (!file_exists('db.php')) {
        throw new Exception('Database configuration file (db.php) not found!');
    }
    
    require_once 'db.php';
    
    // Check if database connection was created
    if (!isset($conn) || !$conn) {
        throw new Exception('Database connection object not created. Check db.php configuration.');
    }
    
    // Check database connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error . '. Please check XAMPP MySQL is running.');
    }

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'User not logged in. Please login and try again.'
        ]);
        exit;
    }

    // Get booking data from POST request
    $user_id = $_SESSION['user_id'];
    $facility_name = isset($_POST['facility']) ? trim($_POST['facility']) : '';
    $booking_date = isset($_POST['date']) ? $_POST['date'] : '';
    $times = isset($_POST['times']) ? $_POST['times'] : [];
    // Accept either a numeric `court_id` (new courts table) or a legacy `venue_id` string
    $court_id = isset($_POST['court_id']) ? intval($_POST['court_id']) : 0;
    $posted_venue_id = isset($_POST['venue_id']) ? trim($_POST['venue_id']) : '';

    // Validate inputs
    if (empty($facility_name) || empty($booking_date) || empty($times)) {
        error_log("Missing required fields - facility: " . ($facility_name ?: 'empty') . ", date: " . ($booking_date ?: 'empty') . ", times: " . (is_array($times) ? count($times) : 'not array'));
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields. Please select facility, date, and at least one time slot.',
            'debug' => [
                'facility' => $facility_name ?: 'empty',
                'date' => $booking_date ?: 'empty',
                'times_count' => is_array($times) ? count($times) : 'not array'
            ]
        ]);
        exit;
    }

    // Resolve venue and court (if provided)
    $venue_id = '';
    if ($court_id) {
    // If courts table exists and court_id provided, get mapping
    $cstmt = $conn->prepare("SELECT court_id, facility_id, old_venue_id, status FROM courts WHERE court_id = ?");
    if ($cstmt) {
        $cstmt->bind_param('i', $court_id);
        $cstmt->execute();
        $cres = $cstmt->get_result();
        if ($cres->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Court not found']);
            $cstmt->close();
            exit;
        }
        $crow = $cres->fetch_assoc();
        $cstmt->close();

        $venue_id = $crow['old_venue_id'] ?? '';
        $raw = trim(strtolower($crow['status'] ?? ''));
        $truthy = ['active','open','available','1','true','yes'];
        $court_status = in_array($raw, $truthy, true) ? 'active' : 'inactive';
        if ($court_status !== 'active') {
            echo json_encode(['success' => false, 'message' => 'This court is currently closed and cannot be booked.']);
            exit;
        }
    } else {
        // courts table not present; fall back to venue name lookup below
        $court_id = 0;
        }
    }

    // If frontend sent a specific venue_id (legacy row id like 'B100'), prefer that
    if ($posted_venue_id) {
        $venue_id = $posted_venue_id;
    }

    if (!$venue_id) {
    // Get venue_id and check status from facility name (legacy)
    $stmt = $conn->prepare("SELECT venue_id FROM venues WHERE name = ?");
    $stmt->bind_param("s", $facility_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Facility not found'
        ]);
        $stmt->close();
        exit;
    }

        $venue = $result->fetch_assoc();
        $venue_id = $venue['venue_id'];
        $stmt->close();
    }

    // Check if venues table has status column and whether this venue is open
    // Check venue status if present (legacy venues table)
    $hasStatus = false;
    $colRes = mysqli_query($conn, "SHOW COLUMNS FROM venues LIKE 'status'");
    if ($colRes && mysqli_num_rows($colRes) > 0) {
        $hasStatus = true;
    }

    if ($hasStatus) {
    $sstmt = $conn->prepare("SELECT status FROM venues WHERE venue_id = ?");
    $sstmt->bind_param("s", $venue_id);
    $sstmt->execute();
    $sres = $sstmt->get_result();
    $row = $sres->fetch_assoc();
    $sstmt->close();

    $raw = trim(strtolower($row['status'] ?? ''));
    $truthy = ['active', 'open', 'available', '1', 'true', 'yes'];
    $status = in_array($raw, $truthy, true) ? 'active' : 'inactive';
    if ($status !== 'active') {
        echo json_encode([
            'success' => false,
            'message' => 'This facility is currently closed and cannot be booked.'
        ]);
        exit;
        }
    }

    // Save booking for each selected time
    $booking_ids = [];
    $conn->begin_transaction();

    try {
        foreach ($times as $time) {
        $time = trim($time);
        
        // Convert 12-hour time format to 24-hour format for database
        $time_24 = convertTo24HourFormat($time);
        
        // Check if this time slot is already booked
        // Prefer per-court when available otherwise per-venue
        $hasCourtColumn = false;
        $colRes2 = mysqli_query($conn, "SHOW COLUMNS FROM bookings LIKE 'court_id'");
        if ($colRes2 && mysqli_num_rows($colRes2) > 0) $hasCourtColumn = true;

        if ($hasCourtColumn && $court_id) {
            $checkStmt = $conn->prepare(
                "SELECT * FROM bookings WHERE court_id = ? AND booking_date = ? AND booking_time = ? AND status IN ('booked', 'pending', 'approved')"
            );
            $checkStmt->bind_param("iss", $court_id, $booking_date, $time_24);
        } else {
            $checkStmt = $conn->prepare(
                "SELECT * FROM bookings WHERE venue_id = ? AND booking_date = ? AND booking_time = ? AND status IN ('booked', 'pending', 'approved')"
            );
            $checkStmt->bind_param("sss", $venue_id, $booking_date, $time_24);
        }
        $checkStmt->execute();

        $existingBooking = $checkStmt->get_result();
        if ($existingBooking->num_rows > 0) {
            $bookingRow = $existingBooking->fetch_assoc();
            $checkStmt->close();
            // Check if it's the same user trying to book again
            if ($bookingRow['user_id'] === $user_id) {
                throw new Exception("You have already booked time slot $time. Please select a different time.");
            } else {
                throw new Exception("Time slot $time is already booked by another user. Please select a different time.");
            }
        }
        $checkStmt->close();
        
        // Check if there's an event scheduled at this venue that conflicts with this time slot
        // Check if booking_date falls within any event's date range (start_date to end_date)
        // and if the booking time overlaps with the event's time range
        // Only check if events table exists
        $eventsTableExists = false;
        $checkEventsTable = $conn->query("SHOW TABLES LIKE 'events'");
        if ($checkEventsTable && $checkEventsTable->num_rows > 0) {
            $eventsTableExists = true;
        }
        
        if ($eventsTableExists) {
            $eventCheckStmt = $conn->prepare(
                "SELECT start_time, end_time, name FROM events 
                 WHERE venue_id = ? 
                 AND ? BETWEEN start_date AND end_date"
            );
            if ($eventCheckStmt) {
                $eventCheckStmt->bind_param("ss", $venue_id, $booking_date);
                $eventCheckStmt->execute();
                $eventResult = $eventCheckStmt->get_result();
                
                while ($eventRow = $eventResult->fetch_assoc()) {
                    $eventStartTime = $eventRow['start_time'];
                    $eventEndTime = $eventRow['end_time'];
                    $eventName = $eventRow['name'];
                    
                    // Check if the booking time overlaps with the event time range
                    if (timeOverlaps($time_24, $eventStartTime, $eventEndTime)) {
                        $eventCheckStmt->close();
                        throw new Exception("Time slot $time conflicts with scheduled event '$eventName' ($eventStartTime - $eventEndTime) on $booking_date");
                    }
                }
                $eventCheckStmt->close();
            }
        }
        
        // Insert booking (include court_id when available)
        $hasCourtColumn = false;
        $colRes3 = mysqli_query($conn, "SHOW COLUMNS FROM bookings LIKE 'court_id'");
        if ($colRes3 && mysqli_num_rows($colRes3) > 0) $hasCourtColumn = true;

        if ($hasCourtColumn && $court_id) {
            $insertStmt = $conn->prepare(
                "INSERT INTO bookings (user_id, venue_id, court_id, booking_date, booking_time, status) 
                 VALUES (?, ?, ?, ?, ?, 'booked')"
            );
            $insertStmt->bind_param("ssiss", $user_id, $venue_id, $court_id, $booking_date, $time_24);
        } else {
            $insertStmt = $conn->prepare(
                "INSERT INTO bookings (user_id, venue_id, booking_date, booking_time, status) 
                 VALUES (?, ?, ?, ?, 'booked')"
            );
            $insertStmt->bind_param("ssss", $user_id, $venue_id, $booking_date, $time_24);
        }
        
        if (!$insertStmt->execute()) {
            $error_msg = "Failed to save booking: " . $insertStmt->error;
            error_log($error_msg);
            error_log("SQL Error: " . $conn->error);
            error_log("Attempted insert - user_id: $user_id, venue_id: $venue_id, court_id: " . ($court_id ?: 'NULL') . ", date: $booking_date, time: $time_24");
            throw new Exception($error_msg);
        }

        // Capture booking ID even if AUTO_INCREMENT is not configured
        $newId = $insertStmt->insert_id;
        if (!$newId) {
            // Fallback: fetch the just-inserted row using user/date/time (and court/venue where applicable)
            if ($hasCourtColumn && $court_id) {
                $fallback = $conn->prepare(
                    "SELECT booking_id FROM bookings WHERE user_id = ? AND booking_date = ? AND booking_time = ? AND court_id = ? ORDER BY created_at DESC, booking_id DESC LIMIT 1"
                );
                $fallback->bind_param("sssi", $user_id, $booking_date, $time_24, $court_id);
            } else {
                $fallback = $conn->prepare(
                    "SELECT booking_id FROM bookings WHERE user_id = ? AND booking_date = ? AND booking_time = ? AND venue_id = ? ORDER BY created_at DESC, booking_id DESC LIMIT 1"
                );
                $fallback->bind_param("ssss", $user_id, $booking_date, $time_24, $venue_id);
            }

            if ($fallback && $fallback->execute()) {
                $res = $fallback->get_result();
                if ($res && $res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    $newId = $row['booking_id'] ?? 0;
                }
            }
            if ($fallback) $fallback->close();
        }
        
        $booking_ids[] = $newId ? (string)$newId : '';
        error_log("Booking saved successfully - ID: " . ($newId ?: 'unknown'));
        $insertStmt->close();
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Your booking has been confirmed! You can view it in your booking list.',
        'booking_ids' => $booking_ids
    ]);
    
    } catch (Exception $e) {
        // Rollback transaction if it was started
        if (isset($conn) && $conn && !$conn->connect_error) {
            try {
                $conn->rollback();
            } catch (Exception $rollbackError) {
                // Ignore rollback errors
            }
        }
        error_log("Booking error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'error_details' => 'Check PHP error log: C:\\xampp\\php\\logs\\php_error_log'
        ]);
        exit;
    }
    
} catch (Throwable $e) {
    // Catch any fatal errors or other exceptions
    error_log("Fatal error in save_booking.php: " . $e->getMessage());
    error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred: ' . $e->getMessage(),
        'error_type' => get_class($e),
        'error_file' => basename($e->getFile()),
        'error_line' => $e->getLine()
    ]);
    exit;
}

// Helper function to convert 12-hour to 24-hour format
function convertTo24HourFormat($time) {
    // Handle formats like "7:00 AM", "7:00PM", "12:00 PM"
    $time = trim($time);
    $period = substr($time, -2); // Get last 2 chars (AM/PM)
    $time_parts = explode(':', str_replace(' AM', '', str_replace(' PM', '', $time)));
    
    $hour = (int)$time_parts[0];
    $minute = isset($time_parts[1]) ? (int)$time_parts[1] : 0;
    
    if ($period === 'PM' && $hour !== 12) {
        $hour += 12;
    } elseif ($period === 'AM' && $hour === 12) {
        $hour = 0;
    }
    
    return sprintf('%02d:%02d:00', $hour, $minute);
}

// Helper function to check if a booking time overlaps with an event's time range
// Booking slots are hourly (e.g., 14:00:00 represents 2:00 PM - 3:00 PM slot)
// Event times can be any range (e.g., 14:30:00 - 16:30:00)
function timeOverlaps($bookingTime, $eventStartTime, $eventEndTime) {
    // Parse times to minutes for comparison
    $bookingParts = explode(':', $bookingTime);
    $eventStartParts = explode(':', $eventStartTime);
    $eventEndParts = explode(':', $eventEndTime);
    
    $bookingHour = (int)$bookingParts[0];
    $bookingMinute = isset($bookingParts[1]) ? (int)$bookingParts[1] : 0;
    
    $eventStartHour = (int)$eventStartParts[0];
    $eventStartMinute = isset($eventStartParts[1]) ? (int)$eventStartParts[1] : 0;
    
    $eventEndHour = (int)$eventEndParts[0];
    $eventEndMinute = isset($eventEndParts[1]) ? (int)$eventEndParts[1] : 0;
    
    // Convert to minutes
    $bookingStartMinutes = $bookingHour * 60 + $bookingMinute;
    $bookingEndMinutes = $bookingStartMinutes + 60; // Booking slots are 1 hour long
    $eventStartMinutes = $eventStartHour * 60 + $eventStartMinute;
    $eventEndMinutes = $eventEndHour * 60 + $eventEndMinute;
    
    // Check for overlap: booking overlaps if booking start <= event end AND booking end > event start
    // We use <= for eventEndMinutes to block bookings that start exactly when event ends (event still active at that moment)
    return ($bookingStartMinutes <= $eventEndMinutes && $bookingEndMinutes > $eventStartMinutes);
}
?>
