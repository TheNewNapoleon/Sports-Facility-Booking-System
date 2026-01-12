<?php
// config.php
$DB_HOST = 'localhost';
$DB_NAME = 'campus_facility_booking';
$DB_USER = 'root';
$DB_PASS = ''; // set your mysql password

// Create connection
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
