<?php
require_once 'db.php';

// Get filter parameters
$filterType = $_GET['filterType'] ?? 'month';
$filterValue = $_GET['filterValue'] ?? date('Y-m');
$year = (int)date('Y');
$month = (int)date('m');

// Parse filterValue based on filterType
if ($filterType === 'month') {
    $parts = explode('-', $filterValue);
    if (count($parts) === 2) {
        $year = (int)$parts[0];
        $month = (int)$parts[1];
    }
} elseif ($filterType === 'year') {
    $year = (int)$filterValue;
}

$timeSlots = ['07:00','08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00','22:00'];
$daysOfWeek = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

// Initialize response
$response = [
    'filterType' => $filterType,
    'filterValue' => $filterValue,
    'bookings' => [],
    'facilities' => []
];

// Build bookings data
if ($filterType === 'day') {
    // For day filter, we need the specific date
    $dateObj = DateTime::createFromFormat('Y-m-d', $filterValue);
    if ($dateObj) {
        $bookingsData = [];
        foreach ($timeSlots as $ts) {
            $bookingsData[$ts] = array_fill_keys($daysOfWeek, 0);
        }
        
        $sql = "SELECT TIME_FORMAT(booking_time, '%H:00') AS time_slot, DAYNAME(booking_date) AS dayname, COUNT(*) AS cnt
                FROM bookings
                WHERE DATE(booking_date)=?
                GROUP BY time_slot, dayname";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('s', $filterValue);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $ts = $r['time_slot'];
                $day = $r['dayname'];
                $cnt = (int)$r['cnt'];
                if (isset($bookingsData[$ts]) && isset($bookingsData[$ts][$day])) {
                    $bookingsData[$ts][$day] = $cnt;
                }
            }
            $stmt->close();
        }
        $response['bookings'] = $bookingsData;
    }
} elseif ($filterType === 'year') {
    // Monthly breakdown for the year
    $monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    $bookingsData = array_fill_keys($monthNames, 0);
    
    $sql = "SELECT MONTH(booking_date) AS m, COUNT(*) AS cnt FROM bookings WHERE YEAR(booking_date)=? GROUP BY m";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $year);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $m = (int)$r['m'];
            $bookingsData[$monthNames[$m - 1]] = (int)$r['cnt'];
        }
        $stmt->close();
    }
    $response['bookings'] = $bookingsData;
} else {
    // Month filter - weekday breakdown
    $bookingsData = [];
    foreach ($timeSlots as $ts) {
        $bookingsData[$ts] = array_fill_keys($daysOfWeek, 0);
    }
    
    $sql = "SELECT TIME_FORMAT(booking_time, '%H:00') AS time_slot, DAYNAME(booking_date) AS dayname, COUNT(*) AS cnt
            FROM bookings
            WHERE YEAR(booking_date)=? AND MONTH(booking_date)=?
            GROUP BY time_slot, dayname";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('ii', $year, $month);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $ts = $r['time_slot'];
            $day = $r['dayname'];
            $cnt = (int)$r['cnt'];
            if (isset($bookingsData[$ts]) && isset($bookingsData[$ts][$day])) {
                $bookingsData[$ts][$day] = $cnt;
            }
        }
        $stmt->close();
    }
    $response['bookings'] = $bookingsData;
}

// Build facilities data
if ($filterType === 'day') {
    $dateObj = DateTime::createFromFormat('Y-m-d', $filterValue);
    if ($dateObj) {
        $facilitiesData = [];
        $sql = "SELECT v.venue_id, v.name AS venue_name, TIME_FORMAT(b.booking_time, '%H:00') AS time_slot, COUNT(*) AS cnt
                FROM bookings b
                JOIN venues v ON v.venue_id = b.venue_id
                WHERE DATE(b.booking_date)=?
                GROUP BY v.venue_id, time_slot";
        if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param('s', $filterValue);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($r = $res->fetch_assoc()) {
                    $vid = $r['venue_id'];
                    $vname = $r['venue_name'];
                    $ts = $r['time_slot'];
                    $cnt = (int)$r['cnt'];
                    if (!isset($facilitiesData[$vid])) {
                        $facilitiesData[$vid] = ['name' => $vname, 'dayBookings' => 0, 'times' => []];
                    }
                    $facilitiesData[$vid]['dayBookings'] += $cnt;
                    $facilitiesData[$vid]['times'][$ts] = ($facilitiesData[$vid]['times'][$ts] ?? 0) + $cnt;
                }
                $stmt->close();
            }
            // prepare output with peak calculations
            $facilitiesOutput = [];
            foreach ($facilitiesData as $fid => $info) {
                $peak_time = '';
                $peak_count = 0;
                $peak_percent = 0;
                if (!empty($info['times'])) {
                    arsort($info['times']);
                    $peak_time = key($info['times']);
                    $peak_count = current($info['times']);
                }
                $total = $info['dayBookings'];
                if ($total > 0 && $peak_count > 0) {
                    $peak_percent = round(($peak_count / $total) * 100, 2);
                }
                $facilitiesOutput[] = [
                    'name' => $info['name'],
                    'dayBookings' => $total,
                    'peak_time' => $peak_time,
                    'peak_count' => $peak_count,
                    'peak_percent' => $peak_percent,
                    'times' => $info['times']
                ];
            }
            $response['facilities'] = $facilitiesOutput;
    }
} elseif ($filterType === 'year') {
    $facilitiesData = [];
    $sql = "SELECT v.venue_id, v.name AS venue_name, MONTH(b.booking_date) AS m, COUNT(*) AS cnt
            FROM bookings b
            JOIN venues v ON v.venue_id = b.venue_id
            WHERE YEAR(b.booking_date)=?
            GROUP BY v.venue_id, m";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $year);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $vid = $r['venue_id'];
            $vname = $r['venue_name'];
            $cnt = (int)$r['cnt'];
            if (!isset($facilitiesData[$vid])) {
                $facilitiesData[$vid] = ['name' => $vname, 'dayBookings' => 0, 'times' => []];
            }
            $facilitiesData[$vid]['dayBookings'] += $cnt;
        }
        $stmt->close();
    }
    // also aggregate time slots across the year for peak time analysis
    $sql2 = "SELECT v.venue_id, TIME_FORMAT(b.booking_time, '%H:00') AS time_slot, COUNT(*) AS cnt
             FROM bookings b
             JOIN venues v ON v.venue_id = b.venue_id
             WHERE YEAR(b.booking_date)=?
             GROUP BY v.venue_id, time_slot";
    if ($stmt = $conn->prepare($sql2)) {
        $stmt->bind_param('i', $year);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $vid = $r['venue_id'];
            $ts = $r['time_slot'];
            $cnt = (int)$r['cnt'];
            if (!isset($facilitiesData[$vid])) {
                $facilitiesData[$vid] = ['name' => '', 'dayBookings' => 0, 'times' => []];
            }
            $facilitiesData[$vid]['times'][$ts] = ($facilitiesData[$vid]['times'][$ts] ?? 0) + $cnt;
        }
        $stmt->close();
    }
    // prepare output with peak calculations
    $facilitiesOutput = [];
    foreach ($facilitiesData as $fid => $info) {
        $peak_time = '';
        $peak_count = 0;
        $peak_percent = 0;
        if (!empty($info['times'])) {
            arsort($info['times']);
            $peak_time = key($info['times']);
            $peak_count = current($info['times']);
        }
        $total = $info['dayBookings'];
        if ($total > 0 && $peak_count > 0) {
            $peak_percent = round(($peak_count / $total) * 100, 2);
        }
        $facilitiesOutput[] = [
            'name' => $info['name'],
            'dayBookings' => $total,
            'peak_time' => $peak_time,
            'peak_count' => $peak_count,
            'peak_percent' => $peak_percent,
            'times' => $info['times']
        ];
    }
    $response['facilities'] = $facilitiesOutput;
} else {
    // Month filter
    $facilitiesData = [];
    $sql = "SELECT v.venue_id, v.name AS venue_name, DAYNAME(b.booking_date) AS dayname, TIME_FORMAT(b.booking_time, '%H:00') AS time_slot, COUNT(*) AS cnt
            FROM bookings b
            JOIN venues v ON v.venue_id = b.venue_id
            WHERE YEAR(b.booking_date)=? AND MONTH(b.booking_date)=?
            GROUP BY v.venue_id, dayname, time_slot";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('ii', $year, $month);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $vid = $r['venue_id'];
            $vname = $r['venue_name'];
            $day = $r['dayname'];
            $ts = $r['time_slot'];
            $cnt = (int)$r['cnt'];
            if (!isset($facilitiesData[$vid])) {
                $facilitiesData[$vid] = ['name' => $vname, 'times' => [], 'Monday'=>0,'Tuesday'=>0,'Wednesday'=>0,'Thursday'=>0,'Friday'=>0,'Saturday'=>0,'Sunday'=>0];
            }
            $facilitiesData[$vid]['times'][$ts] = ($facilitiesData[$vid]['times'][$ts] ?? 0) + $cnt;
            $facilitiesData[$vid][$day] += $cnt;
        }
        $stmt->close();
    }
    
    $facilitiesOutput = [];
    foreach ($facilitiesData as $fid => $info) {
        $peak_time = '';
        $peak_count = 0;
        $peak_percent = 0;
        if (!empty($info['times'])) {
            arsort($info['times']);
            $peak_time = key($info['times']);
            $peak_count = current($info['times']);
        }
        $dayTotal = $info['Monday'] + $info['Tuesday'] + $info['Wednesday'] + 
                    $info['Thursday'] + $info['Friday'] + $info['Saturday'] + $info['Sunday'];
        if ($dayTotal > 0 && $peak_count > 0) {
            $peak_percent = round(($peak_count / $dayTotal) * 100, 2);
        }
        $facilitiesOutput[] = [
            'name' => $info['name'],
            'dayBookings' => $dayTotal,
            'peak_time' => $peak_time ?: '',
            'peak_count' => $peak_count,
            'peak_percent' => $peak_percent,
            // include raw per-time counts so the client can fallback/inspect if needed
            'times' => $info['times']
        ];
    }
    $response['facilities'] = $facilitiesOutput;
}

// Return JSON
header('Content-Type: application/json');
echo json_encode($response);
