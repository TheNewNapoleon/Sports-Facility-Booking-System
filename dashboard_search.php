<?php
require_once "db.php";

$q = $_GET['q'] ?? '';
$q = $conn->real_escape_string($q);

$results = [];

// Search Announcements
$annQuery = $conn->query("
    SELECT announcement_id, title, posted_by, message, posted_date
    FROM announcements
    WHERE title LIKE '%$q%' OR message LIKE '%$q%' OR posted_date LIKE '%$q%'
    ORDER BY posted_date DESC
    LIMIT 10
");

while ($ann = $annQuery->fetch_assoc()) {
    $results[] = [
        'type' => 'announcement',
        'id' => $ann['announcement_id'],
        'title' => $ann['title'],
        'posted_by' => $ann['posted_by'],
        'message' => $ann['message'],
        'date' => $ann['posted_date']
    ];
}

// Search Events
$eventQuery = $conn->query("
    SELECT e.event_id, e.name, v.name AS venue_name, e.start_date AS event_date, e.start_time AS event_time
    FROM events e
    JOIN venues v ON e.venue_id = v.venue_id
    WHERE e.name LIKE '%$q%' OR e.start_date LIKE '%$q%' OR e.start_time LIKE '%$q%'
    ORDER BY e.start_date ASC
    LIMIT 10
");

while ($event = $eventQuery->fetch_assoc()) {
    $results[] = [
        'type' => 'event',
        'id' => $event['event_id'],
        'name' => $event['name'],
        'venue_name' => $event['venue_name'],
        'event_date' => $event['event_date'],
        'event_time' => $event['event_time']
    ];
};

header('Content-Type: application/json');
echo json_encode($results);
