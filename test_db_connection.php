<?php
/**
 * Database Connection Test Script
 * Use this to verify your XAMPP database connection is working
 * Access via: http://localhost/SportClubFacilitiesBooking/test_db_connection.php
 */

require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        tr:hover { background: #f5f5f5; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 XAMPP Database Connection Test</h1>
        
        <?php
        // Test 1: Database Connection
        echo "<h2>1. Database Connection</h2>";
        if ($conn->connect_error) {
            echo "<div class='error'>❌ Connection Failed: " . $conn->connect_error . "</div>";
            echo "<div class='info'>💡 Check:<br>";
            echo "- XAMPP MySQL is running<br>";
            echo "- Database name in db.php matches your database<br>";
            echo "- Username/password in db.php are correct</div>";
            exit;
        } else {
            echo "<div class='success'>✅ Database connection successful!</div>";
            echo "<div class='info'>Host: <code>" . $DB_HOST . "</code><br>";
            echo "Database: <code>" . $DB_NAME . "</code><br>";
            echo "User: <code>" . $DB_USER . "</code></div>";
        }
        
        // Test 2: Check if bookings table exists
        echo "<h2>2. Bookings Table Check</h2>";
        $tableCheck = $conn->query("SHOW TABLES LIKE 'bookings'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            echo "<div class='success'>✅ Bookings table exists</div>";
            
            // Show table structure
            $structure = $conn->query("DESCRIBE bookings");
            if ($structure) {
                echo "<h3>Table Structure:</h3>";
                echo "<table>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
                while ($row = $structure->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
                    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<div class='error'>❌ Bookings table does NOT exist!</div>";
            echo "<div class='info'>💡 Create the table using this SQL:<br><br>";
            echo "<code>CREATE TABLE bookings (<br>";
            echo "&nbsp;&nbsp;booking_id INT PRIMARY KEY AUTO_INCREMENT,<br>";
            echo "&nbsp;&nbsp;user_id VARCHAR(100) NOT NULL,<br>";
            echo "&nbsp;&nbsp;venue_id VARCHAR(100) NOT NULL,<br>";
            echo "&nbsp;&nbsp;court_id INT NULL,<br>";
            echo "&nbsp;&nbsp;booking_date DATE NOT NULL,<br>";
            echo "&nbsp;&nbsp;booking_time TIME NOT NULL,<br>";
            echo "&nbsp;&nbsp;status VARCHAR(20) DEFAULT 'booked',<br>";
            echo "&nbsp;&nbsp;created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP<br>";
            echo ");</code></div>";
        }
        
        // Test 3: Check if venues table exists
        echo "<h2>3. Venues Table Check</h2>";
        $venuesCheck = $conn->query("SHOW TABLES LIKE 'venues'");
        if ($venuesCheck && $venuesCheck->num_rows > 0) {
            echo "<div class='success'>✅ Venues table exists</div>";
            
            // Count venues
            $venueCount = $conn->query("SELECT COUNT(*) as count FROM venues");
            if ($venueCount) {
                $count = $venueCount->fetch_assoc()['count'];
                echo "<div class='info'>Total venues in database: <strong>" . $count . "</strong></div>";
                
                if ($count == 0) {
                    echo "<div class='error'>⚠️ No venues found! Add venues to the database first.</div>";
                }
            }
        } else {
            echo "<div class='error'>❌ Venues table does NOT exist!</div>";
        }
        
        // Test 4: Check recent bookings
        echo "<h2>4. Recent Bookings</h2>";
        $recentBookings = $conn->query("SELECT * FROM bookings ORDER BY created_at DESC LIMIT 5");
        if ($recentBookings && $recentBookings->num_rows > 0) {
            echo "<div class='success'>✅ Found " . $recentBookings->num_rows . " recent booking(s)</div>";
            echo "<table>";
            echo "<tr><th>ID</th><th>User ID</th><th>Venue ID</th><th>Date</th><th>Time</th><th>Status</th></tr>";
            while ($row = $recentBookings->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['booking_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['venue_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['booking_date']) . "</td>";
                echo "<td>" . htmlspecialchars($row['booking_time']) . "</td>";
                echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='info'>ℹ️ No bookings found yet. This is normal if you haven't made any bookings.</div>";
        }
        
        // Test 5: PHP Configuration
        echo "<h2>5. PHP Configuration</h2>";
        echo "<div class='info'>";
        echo "PHP Version: <code>" . phpversion() . "</code><br>";
        echo "MySQL Extension: <code>" . (extension_loaded('mysqli') ? '✅ Loaded' : '❌ Not Loaded') . "</code><br>";
        echo "Error Reporting: <code>" . (ini_get('display_errors') ? 'On' : 'Off') . "</code><br>";
        echo "Session Save Path: <code>" . session_save_path() . "</code>";
        echo "</div>";
        
        // Summary
        echo "<h2>📋 Summary</h2>";
        $allGood = true;
        if ($conn->connect_error) $allGood = false;
        if (!($tableCheck && $tableCheck->num_rows > 0)) $allGood = false;
        if (!($venuesCheck && $venuesCheck->num_rows > 0)) $allGood = false;
        
        if ($allGood) {
            echo "<div class='success' style='font-size: 18px; padding: 20px;'>";
            echo "✅ All checks passed! Your database is ready to receive bookings.";
            echo "</div>";
        } else {
            echo "<div class='error' style='font-size: 18px; padding: 20px;'>";
            echo "❌ Some issues found. Please fix them before making bookings.";
            echo "</div>";
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
            <p><strong>Next Steps:</strong></p>
            <ol>
                <li>If all checks passed, try making a booking at <a href="booking_facilities.php">booking_facilities.php</a></li>
                <li>Check PHP error logs if bookings fail: <code>C:\xampp\php\logs\php_error_log</code></li>
                <li>Check MySQL error logs: <code>C:\xampp\mysql\data\mysql_error.log</code></li>
            </ol>
        </div>
    </div>
</body>
</html>

