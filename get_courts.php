<?php
require_once 'db.php';

header('Content-Type: application/json');

try {
    $venue_id = isset($_GET['venue_id']) ? $_GET['venue_id'] : '';
    $facility_id = isset($_GET['facility_id']) ? intval($_GET['facility_id']) : 0;

    if (!$venue_id && !$facility_id) {
        throw new Exception('Missing venue_id or facility_id');
    }

    if ($facility_id) {
        $stmt = $conn->prepare("SELECT court_id, facility_id, old_venue_id, court_number, name, status, image FROM courts WHERE facility_id = ? ORDER BY court_number");
        $stmt->bind_param('i', $facility_id);
    } else {
        $stmt = $conn->prepare("SELECT court_id, facility_id, old_venue_id, court_number, name, status, image FROM courts WHERE old_venue_id = ? ORDER BY court_number");
        $stmt->bind_param('s', $venue_id);
    }

    if (!$stmt->execute()) throw new Exception('Query failed: ' . $stmt->error);
    $res = $stmt->get_result();

    $data = [];
    while ($row = $res->fetch_assoc()) {
        $row['status'] = in_array(strtolower($row['status']), ['active','open','available','1','true','yes']) ? 'active' : 'inactive';
        $data[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
