<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Missing booking_id']);
    exit;
}

try {
    // Verify the booking belongs to the user and get current status
    $stmt = $conn->prepare("SELECT booking_id, status FROM bookings WHERE booking_id = ? AND user_id = ?");
    $stmt->bind_param("is", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Booking not found or you do not have permission to cancel it");
    }

    $booking = $result->fetch_assoc();
    $stmt->close();

    // Only allow cancelling bookings with certain statuses
    $cancellable_statuses = ['booked', 'pending', 'approved'];
    if (!in_array($booking['status'], $cancellable_statuses)) {
        throw new Exception("Cannot cancel a booking with status: " . ucfirst($booking['status']));
    }

    // Update booking status to cancelled
    $upd = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?");
    $upd->bind_param("i", $booking_id);
    
    if (!$upd->execute()) {
        throw new Exception("Database error: " . $upd->error);
    }
    
    $upd->close();

    // Return success with old status for reference
    echo json_encode([
        'success' => true,
        'message' => 'Booking cancelled successfully! You can now rebook this time slot.',
        'old_status' => $booking['status'],
        'new_status' => 'cancelled'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
