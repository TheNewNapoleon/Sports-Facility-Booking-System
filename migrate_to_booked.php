<?php
require_once 'db.php';

echo "Starting migration: Converting 'Pending' and 'Approved' to 'Booked'...\n\n";

try {
    // Update all 'Pending' to 'Booked'
    $stmt1 = $conn->prepare("UPDATE bookings SET status = 'Booked' WHERE LOWER(status) = 'pending'");
    if (!$stmt1) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    if (!$stmt1->execute()) {
        throw new Exception("Execute failed: " . $stmt1->error);
    }
    $pending_count = $stmt1->affected_rows;
    $stmt1->close();
    echo "✓ Converted $pending_count 'Pending' records to 'Booked'\n";

    // Update all 'Approved' to 'Booked'
    $stmt2 = $conn->prepare("UPDATE bookings SET status = 'Booked' WHERE LOWER(status) = 'approved'");
    if (!$stmt2) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    if (!$stmt2->execute()) {
        throw new Exception("Execute failed: " . $stmt2->error);
    }
    $approved_count = $stmt2->affected_rows;
    $stmt2->close();
    echo "✓ Converted $approved_count 'Approved' records to 'Booked'\n";

    // Verify the conversion
    $verify = $conn->query("SELECT COUNT(*) as booked_count FROM bookings WHERE LOWER(status) = 'booked'");
    if ($verify) {
        $result = $verify->fetch_assoc();
        echo "\n✓ Total 'Booked' records in database: " . $result['booked_count'] . "\n";
    }

    // Check if any pending/approved records remain
    $check = $conn->query("SELECT COUNT(*) as remaining FROM bookings WHERE LOWER(status) IN ('pending', 'approved')");
    if ($check) {
        $result = $check->fetch_assoc();
        if ($result['remaining'] == 0) {
            echo "✓ No 'Pending' or 'Approved' records remain\n";
        } else {
            echo "⚠ Warning: " . $result['remaining'] . " 'Pending' or 'Approved' records still exist\n";
        }
    }

    echo "\n✓ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
}

$conn->close();
?>
