<?php
// faqai_backend.php
header('Content-Type: application/json; charset=utf-8');
$payload = json_decode(file_get_contents('php://input'), true);
$q = trim($payload['q'] ?? '');
$reply = "Sorry, I don't have an answer for that yet.";

// simple keyword rules
$ql = strtolower($q);
if (strpos($ql,'book') !== false || strpos($ql,'booking') !== false) {
    $reply = "To make a booking: go to Booking Facilities → pick a venue → select date/time → submit. Your booking will be confirmed immediately.";
} elseif (strpos($ql,'cancel') !== false) {
    $reply = "To cancel: open My Bookings and click Cancel on the booking (if it's pending or approved).";
} elseif (strpos($ql,'event') !== false || strpos($ql,'events') !== false) {
    $reply = "Upcoming events are listed on the Dashboard. You can click 'Details' to see more.";
} elseif (strpos($ql,'feedback') !== false) {
    $reply = "Use the Feedback page to submit ratings and comments for a venue.";
} elseif (strpos($ql,'how') !== false && strpos($ql,'login') !== false) {
    $reply = "Use the Login page to sign in with your campus account. If you don't have one, contact admin.";
} else {
    // fallback: return helpful suggestions
    $reply = "I can help with: booking, cancelling, events, feedback, or account questions. Try: 'How to book a court?'";
}

echo json_encode(['reply'=>$reply]);
