<?php
require_once 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Flash helper: store and retrieve flash messages in session
function flash($type, $message) {
    if (!isset($_SESSION['flash_messages'])) $_SESSION['flash_messages'] = [];
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check if admin is blacklisted
$stmt = $conn->prepare("SELECT status FROM users WHERE user_id=?");
$stmt->bind_param("s", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $admin_user = $result->fetch_assoc();
    if ($admin_user['status'] === 'blacklisted') {
        session_destroy();
        header("Location: login.php?error=blacklisted");
        exit();
    }
}
$stmt->close();

$admin_id = $_SESSION['user_id'];
$admin_avatar = $_SESSION['avatar_path'] ?? "images/avatar/test.png";
$admin_name = $_SESSION['name'] ?? "Admin";

// --- User CRUD ---
if(isset($_GET['delete_user'])){
    $id = $_GET['delete_user'];
    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("s", $id);
        if(!$stmt->execute()) throw new Exception('Database error: ' . $stmt->error);
        $stmt->close();
        flash('success', 'User deleted successfully');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=users");
    exit();
}

if(isset($_GET['suspend_user'])){
    $user_id = $_GET['suspend_user'];
    
    try {
        $stmt = $conn->prepare("UPDATE users SET status='blacklisted' WHERE user_id=?");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("s", $user_id);
        if(!$stmt->execute()) throw new Exception('Database error: ' . $stmt->error);
        $stmt->close();
        flash('success', 'User suspended successfully');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=users");
    exit();
}

if(isset($_POST['suspend_user'])){
    $user_id = $_POST['user_id'];
    
    try {
        $stmt = $conn->prepare("UPDATE users SET status='blacklisted' WHERE user_id=?");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("s", $user_id);
        if(!$stmt->execute()) throw new Exception('Database error: ' . $stmt->error);
        $stmt->close();
        flash('success', 'User suspended successfully');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=users");
    exit();
}

if(isset($_GET['suspend_multiple'])){
    $user_ids = explode(',', $_GET['suspend_multiple']);
    $success_count = 0;
    $error_messages = [];
    
    try {
        $stmt = $conn->prepare("UPDATE users SET status='blacklisted' WHERE user_id=?");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        
        foreach($user_ids as $user_id) {
            $user_id = trim($user_id);
            if(!empty($user_id)) {
                $stmt->bind_param("s", $user_id);
                if($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_messages[] = "Failed to suspend user: $user_id";
                }
            }
        }
        $stmt->close();
        
        if($success_count > 0) {
            flash('success', "$success_count user" . ($success_count > 1 ? 's' : '') . " suspended successfully");
        }
        if(!empty($error_messages)) {
            flash('error', implode('<br>', $error_messages));
        }
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=users");
    exit();
}

if(isset($_POST['activate_user'])){
    $user_id = $_POST['user_id'];
    
    try {
        $stmt = $conn->prepare("UPDATE users SET status='active' WHERE user_id=?");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("s", $user_id);
        if(!$stmt->execute()) throw new Exception('Database error: ' . $stmt->error);
        $stmt->close();
        flash('success', 'User activated successfully');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=users");
    exit();
}

if(isset($_GET['activate_multiple'])){
    $user_ids = explode(',', $_GET['activate_multiple']);
    $success_count = 0;
    $error_messages = [];
    
    try {
        $stmt = $conn->prepare("UPDATE users SET status='active' WHERE user_id=?");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        
        foreach($user_ids as $user_id) {
            $user_id = trim($user_id);
            if(!empty($user_id)) {
                $stmt->bind_param("s", $user_id);
                if($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_messages[] = "Failed to activate user: $user_id";
                }
            }
        }
        $stmt->close();
        
        if($success_count > 0) {
            flash('success', "$success_count user" . ($success_count > 1 ? 's' : '') . " activated successfully");
        }
        if(!empty($error_messages)) {
            flash('error', implode('<br>', $error_messages));
        }
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=users");
    exit();
}

if(isset($_POST['approve_user'])){
    $user_id = $_POST['user_id'];
    
    try {
        // Update user status
        $stmt = $conn->prepare("UPDATE users SET status='active' WHERE user_id=?");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("s", $user_id);
        if(!$stmt->execute()) throw new Exception('Database error: ' . $conn->error);
        $stmt->close();
        
        flash('success', 'User approved successfully.');
    
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=users");
    exit();
}

// --- Reject User ---
if(isset($_POST['reject_user'])){
    $user_id = $_POST['user_id'];
    
    try {
        // First, get user's email and name before updating/deleting
        $getUserStmt = $conn->prepare("SELECT email, name FROM users WHERE user_id=?");
        if(!$getUserStmt) throw new Exception('Database error: ' . $conn->error);
        $getUserStmt->bind_param("s", $user_id);
        if(!$getUserStmt->execute()) throw new Exception('Database error: ' . $getUserStmt->error);
        $userResult = $getUserStmt->get_result();
        if($userResult->num_rows === 0) throw new Exception('User not found');
        $userData = $userResult->fetch_assoc();
        $getUserStmt->close();
        
        // Delete the rejected user (or you can set status to 'rejected' if you prefer)
        // Option 1: Delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("s", $user_id);
        if(!$stmt->execute()) throw new Exception('Database error: ' . $conn->error);
        $stmt->close();
        
        // Option 2: If you want to keep the user record, uncomment below and comment out the delete above
        // $stmt = $conn->prepare("UPDATE users SET status='rejected' WHERE user_id=?");
        // if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        // $stmt->bind_param("s", $user_id);
        // if(!$stmt->execute()) throw new Exception('Database error: ' . $conn->error);
        // $stmt->close();
        
        flash('success', 'User rejected successfully.');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=users");
    exit();
}

// --- Add User ---
if(isset($_POST['add_user'])){
    $id = $_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $status = $_POST['status'] ?? 'active';
    $living_place = $_POST['living_place'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $avatar_path = "images/avatar/default.png";

    if(!empty($_FILES['avatar']['name'])){
        $allowed = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)){
            // Create unique filename to avoid conflicts
            $avatar_filename = 'avatar_' . $id . '_' . time() . '.' . $ext;
            $avatar_path = "images/avatar/" . $avatar_filename;
            // Ensure directory exists
            if(!is_dir('images/avatar')){
                mkdir('images/avatar', 0755, true);
            }
            if(!move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar_path)){
                $avatar_path = "images/avatar/default.png"; // fallback if upload fails
            }
        }
    }

    // Validate password is not empty
    if(empty($_POST['password'])){
        flash('error', 'Password is required.');
        header("Location: admin_dashboard.php?page=users");
        exit();
    }

    // Check if user_id already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE user_id = ?");
    if(!$stmt) {
        flash('error', 'Database error: ' . $conn->error);
        header("Location: admin_dashboard.php?page=users");
        exit();
    }
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    if($count > 0){
        flash('error', 'User ID already exists. Please choose a different User ID.');
        header("Location: admin_dashboard.php?page=users");
        exit();
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    if(!$stmt) {
        flash('error', 'Database error: ' . $conn->error);
        header("Location: admin_dashboard.php?page=users");
        exit();
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($email_count);
    $stmt->fetch();
    $stmt->close();
    if($email_count > 0){
        flash('error', 'Email already exists. Please use a different email address.');
        header("Location: admin_dashboard.php?page=users");
        exit();
    }

    try {
        $stmt = $conn->prepare("INSERT INTO users (user_id,name,email,password,role,avatar_path,living_place,phone_number,date_of_birth,gender,status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("sssssssssss",$id,$name,$email,$password,$role,$avatar_path,$living_place,$phone_number,$date_of_birth,$gender,$status);
        if(!$stmt->execute()) throw new Exception('Database error: ' . $stmt->error);
        $stmt->close();
        flash('success', 'User added successfully');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=users");
    exit();
}

// --- Facility CRUD ---
if(isset($_GET['toggle_facility'])){
    $id = $_GET['toggle_facility'];
    $current = $_GET['status'];
    $new = ($current=='active')?'inactive':'active';
    try {
        $stmt = $conn->prepare("UPDATE venues SET status=? WHERE venue_id=?");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("ss", $new,$id);
        if(!$stmt->execute()) throw new Exception('Database error: ' . $stmt->error);
        $stmt->close();
        flash('success', 'Facility status updated successfully');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=facilities");
    exit();
}

if(isset($_POST['add_facility'])){
    $id = $_POST['venue_id'];
    $name = $_POST['name'];
    $capacity = $_POST['capacity'];
    $location = $_POST['location'];
    $open = $_POST['open_time'];
    $close = $_POST['close_time'];
    $status = 'active';
    $image = "images/default_facility.png";

    if(!empty($_FILES['image']['name'])){
        $allowed = ['jpg','jpeg','png'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)){
            $image = "images/".basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'],$image);
        }
    }

    try {
        $stmt = $conn->prepare("INSERT INTO venues (venue_id,name,capacity,location,open_time,close_time,status,image) VALUES(?,?,?,?,?,?,?,?)");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("ssisssss",$id,$name,$capacity,$location,$open,$close,$status,$image);
        if(!$stmt->execute()) throw new Exception('Database error: ' . $stmt->error);
        $stmt->close();
        flash('success', 'Facility added successfully');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=facilities");
    exit();
}

if(isset($_POST['edit_facility'])){
    $id = $_POST['venue_id'];
    $name = $_POST['name'];
    $capacity = $_POST['capacity'];
    $location = $_POST['location'];
    $open = $_POST['open_time'];
    $close = $_POST['close_time'];
    $status = $_POST['status'];
    $image = $_POST['current_image'] ?? "images/default_facility.png";

    if(!empty($_FILES['image']['name'])){
        $allowed = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)){
            $image = "images/".basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'],$image);
        }
    }

    try {
        $stmt = $conn->prepare("UPDATE venues SET name=?, capacity=?, location=?, open_time=?, close_time=?, status=?, image=? WHERE venue_id=?");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("sissssss",$name,$capacity,$location,$open,$close,$status,$image,$id);
        if(!$stmt->execute()) throw new Exception('Database error: ' . $stmt->error);
        $stmt->close();
        flash('success', 'Facility updated successfully');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=facilities");
    exit();
}

// --- Add Facility via Modal ---
if(isset($_POST['add_facility_modal'])){
    $id = $_POST['facility_id'] ?? '';
    $name = $_POST['facility_name'] ?? '';
    $capacity = (int)($_POST['facility_capacity'] ?? 0);
    $location = $_POST['facility_location'] ?? '';
    $court = $_POST['facility_court'] ?? '';
    $status = $_POST['facility_status'] ?? 'open';
    $description = $_POST['facility_description'] ?? '';
    $image = 'images/default_facility.png';

    // Validate required fields
    if(empty($id) || empty($name) || $capacity <= 0 || empty($location)) {
        flash('error', 'Please fill in all required fields');
        header("Location: admin_dashboard.php?page=facilities");
        exit();
    }

    // Handle image upload if provided
    if(!empty($_FILES['facility_image']['name'])){
        $allowed = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($_FILES['facility_image']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)){
            $image = "images/".basename($_FILES['facility_image']['name']);
            if(!move_uploaded_file($_FILES['facility_image']['tmp_name'], $image)){
                flash('error', 'Failed to upload image');
                header("Location: admin_dashboard.php?page=facilities");
                exit();
            }
        } else {
            flash('error', 'Invalid image format. Only jpg, jpeg, png, gif allowed');
            header("Location: admin_dashboard.php?page=facilities");
            exit();
        }
    }

    try {
        // Insert new facility
        $stmt = $conn->prepare("INSERT INTO venues (venue_id, name, capacity, location, court, description, status, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("ssisssss", $id, $name, $capacity, $location, $court, $description, $status, $image);
        if(!$stmt->execute()) throw new Exception('Facility creation failed: ' . $stmt->error);
        $stmt->close();
        
        flash('success', 'Facility added successfully');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=facilities");
    exit();
}

// --- Edit Facility via Modal ---
if(isset($_POST['edit_facility_modal'])){
    $id = $_POST['facility_id'] ?? '';
    $name = $_POST['facility_name'] ?? '';
    $capacity = (int)($_POST['facility_capacity'] ?? 0);
    $location = $_POST['facility_location'] ?? '';
    $court = $_POST['facility_court'] ?? '';
    $status = $_POST['facility_status'] ?? 'closed';
    $description = $_POST['facility_description'] ?? '';
    $image = '';

    // Validate only facility_id is required; other fields are optional (can be left empty if no changes)
    if(empty($id)) {
        flash('error', 'Facility ID is missing');
        header("Location: admin_dashboard.php?page=facilities");
        exit();
    }

    // Handle image upload if provided
    if(!empty($_FILES['facility_image']['name'])){
        $allowed = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($_FILES['facility_image']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)){
            $image = "images/".basename($_FILES['facility_image']['name']);
            if(!move_uploaded_file($_FILES['facility_image']['tmp_name'], $image)){
                flash('error', 'Failed to upload image');
                header("Location: admin_dashboard.php?page=facilities");
                exit();
            }
        } else {
            flash('error', 'Invalid image format. Only jpg, jpeg, png, gif allowed');
            header("Location: admin_dashboard.php?page=facilities");
            exit();
        }
    }

    try {
        // Get current values if not being changed
        $stmt = $conn->prepare("SELECT name, capacity, location, court, description, image FROM venues WHERE venue_id=?");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("s", $id);
        if(!$stmt->execute()) throw new Exception('Database error: ' . $stmt->error);
        $result = $stmt->get_result();
        if($row = $result->fetch_assoc()){
            // Use submitted values if not empty, otherwise keep existing
            if(empty($name)) $name = $row['name'];
            if($capacity <= 0) $capacity = $row['capacity'];
            if(empty($location)) $location = $row['location'];
            if(empty($court)) $court = $row['court'];
            if(empty($description)) $description = $row['description'];
            if(empty($image)) $image = $row['image'] ?? 'images/default_facility.png';
        } else {
            throw new Exception('Facility not found');
        }
        $stmt->close();
        
        // Update venues table
        $stmt = $conn->prepare("UPDATE venues SET name=?, capacity=?, location=?, court=?, description=?, status=?, image=? WHERE venue_id=?");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        // types: s (name), i (capacity), s (location), s (court), s (description), s (status), s (image), s (id)
        $stmt->bind_param("sissssss", $name, $capacity, $location, $court, $description, $status, $image, $id);
        if(!$stmt->execute()) throw new Exception('Facility update failed: ' . $stmt->error);
        $stmt->close();
        
        flash('success', 'Facility updated successfully');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=facilities");
    exit();
}


// --- Approve / Reject Booking ---
if(isset($_POST['approve_booking']) || isset($_POST['reject_booking'])){
    $id = $_POST['booking_id'];
    $status = isset($_POST['approve_booking']) ? 'Approved' : 'Rejected';
    try {
        $stmt = $conn->prepare("UPDATE bookings SET status=? WHERE booking_id=?");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("ss",$status,$id);
        if(!$stmt->execute()) throw new Exception('Database error: ' . $stmt->error);
        $stmt->close();
        flash('success', 'Booking status updated successfully');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=bookings");
    exit();
}

// --- Bulk Reject Bookings ---
if(isset($_POST['bulk_reject_bookings']) && isset($_POST['booking_ids']) && is_array($_POST['booking_ids'])){
    $booking_ids = array_filter(array_map('intval', $_POST['booking_ids']));
    if(!empty($booking_ids)){
        $placeholders = str_repeat('?,', count($booking_ids) - 1) . '?';
        $status = 'Rejected';
        try {
            $stmt = $conn->prepare("UPDATE bookings SET status=? WHERE booking_id IN ($placeholders) AND status='booked'");
            if(!$stmt) throw new Exception('Database error: ' . $conn->error);
            $params = array_merge([$status], $booking_ids);
            $types = 's' . str_repeat('i', count($booking_ids));
            $stmt->bind_param($types, ...$params);
            if(!$stmt->execute()) throw new Exception('Database error: ' . $stmt->error);
            $affected = $stmt->affected_rows;
            $stmt->close();
            flash('success', "Successfully rejected $affected booking(s)");
        } catch(Exception $e) {
            flash('error', $e->getMessage());
        }
    } else {
        flash('error', 'No bookings selected');
    }
    header("Location: admin_dashboard.php?page=bookings");
    exit();
}

// --- Create Booking ---
if(isset($_POST['create_booking'])){
    $user_id = $_POST['booking_user_id'] ?? '';
    $venue_id = $_POST['booking_venue_id'] ?? '';
    $booking_date = $_POST['booking_date'] ?? '';
    $booking_times = isset($_POST['booking_times']) ? $_POST['booking_times'] : '';
    $status = 'Booked';

    // Validate required fields
    if(empty($user_id) || empty($venue_id) || empty($booking_date) || empty($booking_times)){
        flash('error', 'Please fill in all required fields for booking');
        header("Location: admin_dashboard.php?page=bookings");
        exit();
    }

    // Parse booking times (comma-separated string or array)
    $times = [];
    if (is_array($booking_times)) {
        $times = array_filter(array_map('trim', $booking_times));
    } else {
        $times = array_filter(array_map('trim', explode(',', $booking_times)));
    }

    if (empty($times)) {
        flash('error', 'Please select at least one time slot');
        header("Location: admin_dashboard.php?page=bookings");
        exit();
    }

    try {
        // ensure user exists
        $u = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
        if(!$u) throw new Exception('Database error: ' . $conn->error);
        $u->bind_param("s", $user_id);
        if(!$u->execute()) throw new Exception('Database error: ' . $u->error);
        $ur = $u->get_result();
        if($ur->num_rows == 0) throw new Exception('Selected user does not exist');
        $u->close();

        // ensure venue exists
        $v = $conn->prepare("SELECT venue_id FROM venues WHERE venue_id = ?");
        if(!$v) throw new Exception('Database error: ' . $conn->error);
        $v->bind_param("s", $venue_id);
        if(!$v->execute()) throw new Exception('Database error: ' . $v->error);
        $vr = $v->get_result();
        if($vr->num_rows == 0) throw new Exception('Selected facility does not exist');
        $v->close();

        // Check for court_id support
        $hasCourtColumn = false;
        $colRes = mysqli_query($conn, "SHOW COLUMNS FROM bookings LIKE 'court_id'");
        if ($colRes && mysqli_num_rows($colRes) > 0) {
            $hasCourtColumn = true;
        }

        // Get court_id from venue if available
        $court_id = null;
        if ($hasCourtColumn) {
            // Check if courts table exists before querying it
            $courtsTableExists = false;
            $checkCourtsTable = $conn->query("SHOW TABLES LIKE 'courts'");
            if ($checkCourtsTable && $checkCourtsTable->num_rows > 0) {
                $courtsTableExists = true;
            }
            
            if ($courtsTableExists) {
                $courtStmt = $conn->prepare("SELECT court_id FROM courts WHERE old_venue_id = ? LIMIT 1");
                if ($courtStmt) {
                    $courtStmt->bind_param("s", $venue_id);
                    $courtStmt->execute();
                    $courtResult = $courtStmt->get_result();
                    if ($courtResult->num_rows > 0) {
                        $courtRow = $courtResult->fetch_assoc();
                        $court_id = $courtRow['court_id'];
                    }
                    $courtStmt->close();
                }
            }
        }

        // Convert 12-hour format to 24-hour format helper
        function convertTo24Hour($time12) {
            $time12 = trim($time12);
            // If already in 24-hour format (HH:MM), return as is
            if (preg_match('/^\d{1,2}:\d{2}$/', $time12)) {
                return $time12;
            }
            // Parse 12-hour format (e.g., "7:00 AM" or "1:30 PM")
            preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)/i', $time12, $matches);
            if (count($matches) === 4) {
                $hour = (int)$matches[1];
                $minute = $matches[2];
                $period = strtoupper($matches[3]);
                
                if ($period === 'PM' && $hour !== 12) {
                    $hour += 12;
                } elseif ($period === 'AM' && $hour === 12) {
                    $hour = 0;
                }
                return sprintf('%02d:%s', $hour, $minute);
            }
            return $time12; // Return as-is if parsing fails
        }

        // Insert each booking time
        $insertedCount = 0;
        $errors = [];
        
        foreach ($times as $time) {
            $time_24 = convertTo24Hour($time);
            
            // Check if this time slot is already booked
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
                $errors[] = "Time slot $time is already booked";
                $checkStmt->close();
                continue;
            }
            $checkStmt->close();

            // Check for events that block this time
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
                        
                        // Check if booking time overlaps with event time
                        $timeOverlaps = false;
                        $bookingParts = explode(':', $time_24);
                        $eventStartParts = explode(':', $eventStartTime);
                        $eventEndParts = explode(':', $eventEndTime);
                        
                        $bookingHour = (int)$bookingParts[0];
                        $bookingMinute = isset($bookingParts[1]) ? (int)$bookingParts[1] : 0;
                        $eventStartHour = (int)$eventStartParts[0];
                        $eventStartMinute = isset($eventStartParts[1]) ? (int)$eventStartParts[1] : 0;
                        $eventEndHour = (int)$eventEndParts[0];
                        $eventEndMinute = isset($eventEndParts[1]) ? (int)$eventEndParts[1] : 0;
                        
                        $bookingStartMinutes = $bookingHour * 60 + $bookingMinute;
                        $bookingEndMinutes = $bookingStartMinutes + 60;
                        $eventStartMinutes = $eventStartHour * 60 + $eventStartMinute;
                        $eventEndMinutes = $eventEndHour * 60 + $eventEndMinute;
                        
                        if ($bookingStartMinutes <= $eventEndMinutes && $bookingEndMinutes > $eventStartMinutes) {
                            $timeOverlaps = true;
                        }
                        
                        if ($timeOverlaps) {
                            $errors[] = "Time slot $time conflicts with event '$eventName'";
                            break;
                        }
                    }
                    $eventCheckStmt->close();
                    
                    if ($timeOverlaps) {
                        continue;
                    }
                }
            }

            // Insert booking
            if ($hasCourtColumn && $court_id) {
                $stmt = $conn->prepare("INSERT INTO bookings (user_id, venue_id, court_id, booking_date, booking_time, status) VALUES (?, ?, ?, ?, ?, ?)");
                if(!$stmt) throw new Exception('Database error: ' . $conn->error);
                $stmt->bind_param("ssisss", $user_id, $venue_id, $court_id, $booking_date, $time_24, $status);
            } else {
                $stmt = $conn->prepare("INSERT INTO bookings (user_id, venue_id, booking_date, booking_time, status) VALUES (?, ?, ?, ?, ?)");
                if(!$stmt) throw new Exception('Database error: ' . $conn->error);
                $stmt->bind_param("sssss", $user_id, $venue_id, $booking_date, $time_24, $status);
            }
            
            if(!$stmt->execute()) {
                $errors[] = 'Failed to create booking for ' . $time . ': ' . $stmt->error;
                $stmt->close();
            } else {
                $insertedCount++;
                $stmt->close();
            }
        }

        if ($insertedCount > 0) {
            if (!empty($errors)) {
                flash('warning', "Created $insertedCount booking(s). Some errors: " . implode(', ', $errors));
            } else {
                flash('success', "Successfully created $insertedCount booking(s)");
            }
        } else {
            flash('error', 'Failed to create bookings: ' . implode(', ', $errors));
        }
    } catch(Exception $e){
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=bookings");
    exit();
}

// --- Add Announcement ---
if(isset($_POST['add_announcement_modal'])){
    $title = $_POST['announcement_title'] ?? '';
    $message = $_POST['announcement_message'] ?? '';
    $audience = $_POST['announcement_audience'] ?? 'all';
    $posted_by = $admin_id;

    // Validate required fields
    if(empty($title) || empty($message)) {
        flash('error', 'Please fill in all required fields');
        header("Location: admin_dashboard.php?page=announcements");
        exit();
    }

    try {
        // Generate announcement ID
        $idResult = $conn->query("SELECT MAX(CAST(SUBSTRING(announcement_id, 3) AS UNSIGNED)) AS max_id FROM announcements WHERE announcement_id LIKE 'AN%'");
        $maxId = $idResult ? ($idResult->fetch_assoc()['max_id'] ?? 0) : 0;
        $nextId = 'AN' . str_pad($maxId + 1, 3, '0', STR_PAD_LEFT);

        $stmt = $conn->prepare("INSERT INTO announcements (announcement_id, title, message, posted_by, audience) VALUES (?, ?, ?, ?, ?)");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("sssss", $nextId, $title, $message, $posted_by, $audience);
        if(!$stmt->execute()) throw new Exception('Announcement creation failed: ' . $stmt->error);
        $stmt->close();
        
        flash('success', 'Announcement added successfully');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=announcements");
    exit();
}

// --- Add Event ---
if(isset($_POST['add_event_modal'])){
    $name = $_POST['event_name'] ?? '';
    $venue_id = $_POST['event_venue_id'] ?? '';
    $start_date = $_POST['event_start_date'] ?? '';
    $end_date = $_POST['event_end_date'] ?? '';
    $start_time = $_POST['event_start_time'] ?? '';
    $end_time = $_POST['event_end_time'] ?? '';

    // Validate required fields
    if(empty($name) || empty($venue_id) || empty($start_date) || empty($end_date) || empty($start_time) || empty($end_time)) {
        flash('error', 'Please fill in all required fields');
        header("Location: admin_dashboard.php?page=events");
        exit();
    }

    // Validate date range
    if(strtotime($end_date) < strtotime($start_date)) {
        flash('error', 'End date cannot be before start date');
        header("Location: admin_dashboard.php?page=events");
        exit();
    }

    try {
        $stmt = $conn->prepare("INSERT INTO events (name, venue_id, start_date, end_date, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("ssssss", $name, $venue_id, $start_date, $end_date, $start_time, $end_time);
        if(!$stmt->execute()) throw new Exception('Event creation failed: ' . $stmt->error);
        $stmt->close();
        
        flash('success', 'Event added successfully');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=events");
    exit();
}

// --- Edit Announcement ---
if(isset($_POST['edit_announcement_modal'])){
    $announcement_id = $_POST['announcement_id'] ?? '';
    $title = $_POST['announcement_title'] ?? '';
    $message = $_POST['announcement_message'] ?? '';
    $audience = $_POST['announcement_audience'] ?? 'all';

    if(empty($announcement_id) || empty($title) || empty($message)) {
        flash('error', 'Please fill in all required fields');
        header("Location: admin_dashboard.php?page=announcements");
        exit();
    }

    // Check if announcement has expired
    try {
        $checkStmt = $conn->prepare("SELECT posted_date FROM announcements WHERE announcement_id = ?");
        if(!$checkStmt) throw new Exception('Database error: ' . $conn->error);
        $checkStmt->bind_param("s", $announcement_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if($result->num_rows > 0) {
            $annData = $result->fetch_assoc();
            $postedDate = $annData['posted_date'] ?? date('Y-m-d');
            $postedTimestamp = strtotime($postedDate);
            $todayTimestamp = strtotime(date('Y-m-d'));
            if($postedTimestamp < $todayTimestamp) {
                flash('error', 'Cannot edit expired announcements');
                header("Location: admin_dashboard.php?page=announcements");
                exit();
            }
        }
        $checkStmt->close();
    } catch(Exception $e) {
        flash('error', 'Error checking announcement: ' . $e->getMessage());
        header("Location: admin_dashboard.php?page=announcements");
        exit();
    }

    try {
        $stmt = $conn->prepare("UPDATE announcements SET title=?, message=?, audience=? WHERE announcement_id=?");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("ssss", $title, $message, $audience, $announcement_id);
        if(!$stmt->execute()) throw new Exception('Update failed: ' . $stmt->error);
        $stmt->close();
        flash('success', 'Announcement updated successfully');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=announcements");
    exit();
}

// --- Delete Announcement ---
if(isset($_GET['delete_announcement'])){
    $announcement_id = $_GET['delete_announcement'];
    
    // Check if announcement has expired
    try {
        $checkStmt = $conn->prepare("SELECT posted_date FROM announcements WHERE announcement_id = ?");
        if(!$checkStmt) throw new Exception('Database error: ' . $conn->error);
        $checkStmt->bind_param("s", $announcement_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if($result->num_rows > 0) {
            $annData = $result->fetch_assoc();
            $postedDate = $annData['posted_date'] ?? date('Y-m-d');
            $postedTimestamp = strtotime($postedDate);
            $todayTimestamp = strtotime(date('Y-m-d'));
            if($postedTimestamp < $todayTimestamp) {
                flash('error', 'Cannot delete expired announcements');
                header("Location: admin_dashboard.php?page=announcements");
                exit();
            }
        }
        $checkStmt->close();
    } catch(Exception $e) {
        flash('error', 'Error checking announcement: ' . $e->getMessage());
        header("Location: admin_dashboard.php?page=announcements");
        exit();
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM announcements WHERE announcement_id = ?");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("s", $announcement_id);
        if(!$stmt->execute()) throw new Exception('Delete failed: ' . $stmt->error);
        $stmt->close();
        flash('success', 'Announcement deleted successfully');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=announcements");
    exit();
}

// --- Edit Event ---
if(isset($_POST['edit_event_modal'])){
    $event_id = $_POST['event_id'] ?? '';
    $name = $_POST['event_name'] ?? '';
    $venue_id = $_POST['event_venue_id'] ?? '';
    $start_date = $_POST['event_start_date'] ?? '';
    $end_date = $_POST['event_end_date'] ?? '';
    $start_time = $_POST['event_start_time'] ?? '';
    $end_time = $_POST['event_end_time'] ?? '';

    if(empty($event_id) || empty($name) || empty($venue_id) || empty($start_date) || empty($end_date) || empty($start_time) || empty($end_time)) {
        flash('error', 'Please fill in all required fields');
        header("Location: admin_dashboard.php?page=events");
        exit();
    }

    // Check if event has started (start date and time have passed)
    try {
        $checkStmt = $conn->prepare("SELECT start_date, start_time FROM events WHERE event_id = ?");
        if(!$checkStmt) throw new Exception('Database error: ' . $conn->error);
        $checkStmt->bind_param("i", $event_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if($result->num_rows > 0) {
            $eventData = $result->fetch_assoc();
            $startDate = $eventData['start_date'] ?? date('Y-m-d');
            $startTime = $eventData['start_time'] ?? '00:00:00';
            $startDateTime = $startDate . ' ' . $startTime;
            $startTimestamp = strtotime($startDateTime);
            $currentTimestamp = time();
            if($startTimestamp < $currentTimestamp) {
                flash('error', 'Cannot edit events that have already started');
                header("Location: admin_dashboard.php?page=events");
                exit();
            }
        }
        $checkStmt->close();
    } catch(Exception $e) {
        flash('error', 'Error checking event: ' . $e->getMessage());
        header("Location: admin_dashboard.php?page=events");
        exit();
    }

    // Validate date range
    if(strtotime($end_date) < strtotime($start_date)) {
        flash('error', 'End date cannot be before start date');
        header("Location: admin_dashboard.php?page=events");
        exit();
    }

    try {
        $stmt = $conn->prepare("UPDATE events SET name=?, venue_id=?, start_date=?, end_date=?, start_time=?, end_time=? WHERE event_id=?");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("ssssssi", $name, $venue_id, $start_date, $end_date, $start_time, $end_time, $event_id);
        if(!$stmt->execute()) throw new Exception('Update failed: ' . $stmt->error);
        $stmt->close();
        flash('success', 'Event updated successfully');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=events");
    exit();
}

// --- Delete Event ---
if(isset($_GET['delete_event'])){
    $event_id = $_GET['delete_event'];
    
    // Check if event has started (start date and time have passed)
    try {
        $checkStmt = $conn->prepare("SELECT start_date, start_time FROM events WHERE event_id = ?");
        if(!$checkStmt) throw new Exception('Database error: ' . $conn->error);
        $checkStmt->bind_param("i", $event_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if($result->num_rows > 0) {
            $eventData = $result->fetch_assoc();
            $startDate = $eventData['start_date'] ?? date('Y-m-d');
            $startTime = $eventData['start_time'] ?? '00:00:00';
            $startDateTime = $startDate . ' ' . $startTime;
            $startTimestamp = strtotime($startDateTime);
            $currentTimestamp = time();
            if($startTimestamp < $currentTimestamp) {
                flash('error', 'Cannot delete events that have already started');
                header("Location: admin_dashboard.php?page=events");
                exit();
            }
        }
        $checkStmt->close();
    } catch(Exception $e) {
        flash('error', 'Error checking event: ' . $e->getMessage());
        header("Location: admin_dashboard.php?page=events");
        exit();
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("i", $event_id);
        if(!$stmt->execute()) throw new Exception('Delete failed: ' . $stmt->error);
        $stmt->close();
        flash('success', 'Event deleted successfully');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=events");
    exit();
}

// --- Reply to Feedback ---
if(isset($_POST['reply_feedback_modal'])){
    $feedback_id = $_POST['feedback_id'] ?? '';
    $respond = $_POST['feedback_response'] ?? '';

    if(empty($feedback_id) || empty($respond)) {
        flash('error', 'Please fill in the response');
        header("Location: admin_dashboard.php?page=feedback");
        exit();
    }

    try {
        $respond_at = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE feedback SET respond=?, respond_at=?, status='reviewed' WHERE feedback_id=?");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("ssi", $respond, $respond_at, $feedback_id);
        if(!$stmt->execute()) throw new Exception('Reply failed: ' . $stmt->error);
        $stmt->close();
        flash('success', 'Reply sent successfully');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=feedback");
    exit();
}

// --- Mark Feedback as Resolved ---
if(isset($_GET['resolve_feedback'])){
    $feedback_id = $_GET['resolve_feedback'];
    try {
        $stmt = $conn->prepare("UPDATE feedback SET status='resolved' WHERE feedback_id=?");
        if(!$stmt) throw new Exception('Database error: ' . $conn->error);
        $stmt->bind_param("i", $feedback_id);
        if(!$stmt->execute()) throw new Exception('Update failed: ' . $stmt->error);
        $stmt->close();
        flash('success', 'Feedback marked as resolved');
    } catch(Exception $e) {
        flash('error', $e->getMessage());
    }
    header("Location: admin_dashboard.php?page=feedback");
    exit();
}

// --- Fetch Data for Dashboard ---
$result = $conn->query("SELECT COUNT(*) AS total FROM users");
$totalUsers = $result ? $result->fetch_assoc()['total'] : 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM venues");
$totalVenues = $result ? $result->fetch_assoc()['total'] : 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status='Booked'");
$pendingBookings = $result ? $result->fetch_assoc()['total'] : 0;

$pendingUsers = $conn->query("
    SELECT user_id, name, email, role, status
    FROM users
    WHERE (status = 'pending' OR status IS NULL OR status = '')
    AND email IS NOT NULL 
    AND email != ''
    AND email LIKE '%@%'
    ORDER BY user_id DESC LIMIT 10
");

$users = $conn->query("SELECT * FROM users");
$facilities = $conn->query("SELECT * FROM venues");

// --- Server-side search/filter/sort for Manage Users ---
$usersLimit = 20;
$usersPage = max(1, (int)($_GET['users_page'] ?? 1));
$usersOffset = ($usersPage - 1) * $usersLimit;

$usersSearch = trim($conn->real_escape_string($_GET['users_search'] ?? ''));
$usersRole = trim($conn->real_escape_string($_GET['users_role'] ?? ''));
$usersStatus = trim($conn->real_escape_string($_GET['users_status'] ?? ''));
$usersSort = $_GET['users_sort'] ?? 'user_id';
$usersDir = strtoupper($_GET['users_dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

$allowedUserSort = ['user_id','name','email','role','status'];
if (!in_array($usersSort, $allowedUserSort)) $usersSort = 'user_id';

// Build WHERE
$userWhere = [];
$userParams = [];
$userTypes = '';
if ($usersSearch !== '') {
    $userWhere[] = "(name LIKE ? OR email LIKE ? )";
    $like = "%{$usersSearch}%";
    $userParams[] = $like; $userParams[] = $like;
    $userTypes .= 'ss';
}
if ($usersRole !== '') { $userWhere[] = 'role = ?'; $userParams[] = $usersRole; $userTypes .= 's'; }
if ($usersStatus !== '') { $userWhere[] = 'status = ?'; $userParams[] = $usersStatus; $userTypes .= 's'; }

$userWhereSql = $userWhere ? 'WHERE ' . implode(' AND ', $userWhere) : '';

// Total users count (with filters)
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM users $userWhereSql")) {
    if ($userParams) {
        $stmt->bind_param($userTypes, ...$userParams);
    }
    $stmt->execute();
    $totalUsersCount = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();
} else {
    $totalUsersCountResult = $conn->query("SELECT COUNT(*) AS total FROM users");
    $totalUsersCount = $totalUsersCountResult ? $totalUsersCountResult->fetch_assoc()['total'] : 0;
}
$totalUsersPages = ceil(max(1, $totalUsersCount) / $usersLimit);

// Fetch users with filters and pagination
$users = [];
$sql = "SELECT * FROM users $userWhereSql ORDER BY $usersSort $usersDir LIMIT ? OFFSET ?";
if ($stmt = $conn->prepare($sql)) {
    // bind params + limit/offset
    $bindTypes = $userTypes . 'ii';
    $bindParams = $userParams;
    $bindParams[] = $usersLimit;
    $bindParams[] = $usersOffset;
    $stmt->bind_param($bindTypes, ...$bindParams);
    $stmt->execute();
    $users = $stmt->get_result();
    $stmt->close();
} else {
    $users = $conn->query("SELECT * FROM users ORDER BY user_id DESC LIMIT $usersLimit OFFSET $usersOffset");
}

// --- Server-side search/filter/sort for Manage Facilities ---
$facilitiesLimit = 20;
$facilitiesPage = max(1, (int)($_GET['facilities_page'] ?? 1));
$facilitiesOffset = ($facilitiesPage - 1) * $facilitiesLimit;

$facilitiesSearch = trim($conn->real_escape_string($_GET['facilities_search'] ?? ''));
$facilitiesStatus = trim($conn->real_escape_string($_GET['facilities_status'] ?? ''));
$facilitiesSort = $_GET['facilities_sort'] ?? 'venue_id';
$facilitiesDir = strtoupper($_GET['facilities_dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$allowedFacSort = ['venue_id','name','capacity','location','status'];
if (!in_array($facilitiesSort, $allowedFacSort)) $facilitiesSort = 'venue_id';

$facWhere = [];
$facParams = [];
$facTypes = '';
if ($facilitiesSearch !== '') {
    $facWhere[] = "(name LIKE ? OR location LIKE ? )";
    $like = "%{$facilitiesSearch}%";
    $facParams[] = $like; $facParams[] = $like;
    $facTypes .= 'ss';
}
if ($facilitiesStatus !== '') { $facWhere[] = 'status = ?'; $facParams[] = $facilitiesStatus; $facTypes .= 's'; }
$facWhereSql = $facWhere ? 'WHERE ' . implode(' AND ', $facWhere) : '';

if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM venues $facWhereSql")) {
    if ($facParams) $stmt->bind_param($facTypes, ...$facParams);
    $stmt->execute();
    $totalFacilitiesCount = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();
} else {
    $totalFacilitiesCountResult = $conn->query("SELECT COUNT(*) AS total FROM venues");
    $totalFacilitiesCount = $totalFacilitiesCountResult ? $totalFacilitiesCountResult->fetch_assoc()['total'] : 0;
}
$totalFacilitiesPages = ceil(max(1, $totalFacilitiesCount) / $facilitiesLimit);

$facilities = [];
$sql = "SELECT * FROM venues $facWhereSql ORDER BY $facilitiesSort $facilitiesDir LIMIT ? OFFSET ?";
if ($stmt = $conn->prepare($sql)) {
    $bindTypes = $facTypes . 'ii';
    $bindParams = $facParams;
    $bindParams[] = $facilitiesLimit; $bindParams[] = $facilitiesOffset;
    $stmt->bind_param($bindTypes, ...$bindParams);
    $stmt->execute();
    $facilities = $stmt->get_result();
    $stmt->close();
} else {
    $facilities = $conn->query("SELECT * FROM venues ORDER BY venue_id DESC LIMIT $facilitiesLimit OFFSET $facilitiesOffset");
}

// --- Server-side search/filter/sort for Manage Bookings ---
$bookingsLimit = 20;
$bookingsPage = max(1, (int)($_GET['bookings_page'] ?? 1));
$bookingsOffset = ($bookingsPage - 1) * $bookingsLimit;

$bookingsSearch = trim($conn->real_escape_string($_GET['bookings_search'] ?? ''));
$bookingsStatus = trim($conn->real_escape_string($_GET['bookings_status'] ?? ''));
$bookingsSort = $_GET['bookings_sort'] ?? 'b.booking_id';
$bookingsDir = strtoupper($_GET['bookings_dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$allowedBookSort = ['booking_id','booking_date','booking_time','status','user_name','venue_name'];
// map friendly names to actual columns
$bookSortMap = ['booking_id'=>'b.booking_id','booking_date'=>'b.booking_date','booking_time'=>'b.booking_time','status'=>'b.status','user_name'=>'u.name','venue_name'=>'v.name'];
if (!array_key_exists($bookingsSort, $bookSortMap)) $bookingsSort = 'booking_id';
$orderBy = $bookSortMap[$bookingsSort] ?? 'b.booking_id';

$bkWhere = [];
$bkParams = [];
$bkTypes = '';
if ($bookingsSearch !== '') {
    $bkWhere[] = "(u.name LIKE ? OR v.name LIKE ? OR b.booking_date LIKE ? OR b.booking_time LIKE ? )";
    $like = "%{$bookingsSearch}%";
    $bkParams[] = $like; $bkParams[] = $like; $bkParams[] = $like; $bkParams[] = $like;
    $bkTypes .= 'ssss';
}
if ($bookingsStatus !== '') { $bkWhere[] = 'b.status = ?'; $bkParams[] = $bookingsStatus; $bkTypes .= 's'; }
$bkWhereSql = $bkWhere ? 'WHERE ' . implode(' AND ', $bkWhere) : '';

// count
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM bookings b JOIN users u ON b.user_id = u.user_id JOIN venues v ON b.venue_id = v.venue_id $bkWhereSql")) {
    if ($bkParams) $stmt->bind_param($bkTypes, ...$bkParams);
    $stmt->execute();
    $totalBookings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();
} else {
    $totalBookingsResult = $conn->query("SELECT COUNT(*) AS total FROM bookings");
    $totalBookings = $totalBookingsResult ? $totalBookingsResult->fetch_assoc()['total'] : 0;
}
$totalBookingsPages = ceil(max(1, $totalBookings) / $bookingsLimit);

// fetch
$bookings = [];
$sql = "SELECT b.booking_id,b.booking_date,b.booking_time,b.status,u.name AS user_name,v.name AS venue_name FROM bookings b JOIN users u ON b.user_id=u.user_id JOIN venues v ON b.venue_id=v.venue_id $bkWhereSql ORDER BY $orderBy $bookingsDir LIMIT ? OFFSET ?";
if ($stmt = $conn->prepare($sql)) {
    $bindTypes = $bkTypes . 'ii';
    $bindParams = $bkParams;
    $bindParams[] = $bookingsLimit; $bindParams[] = $bookingsOffset;
    $stmt->bind_param($bindTypes, ...$bindParams);
    $stmt->execute();
    $bookings = $stmt->get_result();
    $stmt->close();
} else {
    $bookings = $conn->query("SELECT b.booking_id,b.booking_date,b.booking_time,b.status,u.name AS user_name,v.name AS venue_name FROM bookings b JOIN users u ON b.user_id=u.user_id JOIN venues v ON b.venue_id=v.venue_id ORDER BY b.booking_id DESC LIMIT $bookingsLimit OFFSET $bookingsOffset");
}

// --- Server-side search/filter for Manage Announcements ---
$announcementsLimit = 50;
$announcementsPage = max(1, (int)($_GET['announcements_page'] ?? 1));
$announcementsOffset = ($announcementsPage - 1) * $announcementsLimit;

$announcementsSearch = trim($conn->real_escape_string($_GET['announcements_search'] ?? ''));

$annWhere = [];
$annParams = [];
$annTypes = '';
if ($announcementsSearch !== '') {
    $annWhere[] = "(title LIKE ? OR message LIKE ?)";
    $like = "%{$announcementsSearch}%";
    $annParams[] = $like; $annParams[] = $like;
    $annTypes .= 'ss';
}
$annWhereSql = $annWhere ? 'WHERE ' . implode(' AND ', $annWhere) : '';

// Count total announcements
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM announcements $annWhereSql")) {
    if ($annParams) $stmt->bind_param($annTypes, ...$annParams);
    $stmt->execute();
    $totalAnnouncements = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();
} else {
    $totalAnnouncementsResult = $conn->query("SELECT COUNT(*) AS total FROM announcements");
    $totalAnnouncements = $totalAnnouncementsResult ? $totalAnnouncementsResult->fetch_assoc()['total'] : 0;
}
$totalAnnouncementsPages = ceil(max(1, $totalAnnouncements) / $announcementsLimit);

$announcements = [];
$annSql = "SELECT * FROM announcements $annWhereSql ORDER BY posted_date DESC LIMIT ? OFFSET ?";
if ($stmt = $conn->prepare($annSql)) {
    $bindTypes = $annTypes . 'ii';
    $bindParams = $annParams;
    $bindParams[] = $announcementsLimit; $bindParams[] = $announcementsOffset;
    $stmt->bind_param($bindTypes, ...$bindParams);
    $stmt->execute();
    $announcements = $stmt->get_result();
    $stmt->close();
} else {
    $announcements = $conn->query("SELECT * FROM announcements ORDER BY posted_date DESC LIMIT $announcementsLimit OFFSET $announcementsOffset");
}

// --- Server-side search/filter for Manage Events ---
$eventsLimit = 50;
$eventsPage = max(1, (int)($_GET['events_page'] ?? 1));
$eventsOffset = ($eventsPage - 1) * $eventsLimit;

$eventsSearch = trim($conn->real_escape_string($_GET['events_search'] ?? ''));

$evtWhere = [];
$evtParams = [];
$evtTypes = '';
if ($eventsSearch !== '') {
    $evtWhere[] = "(e.name LIKE ? OR v.name LIKE ?)";
    $like = "%{$eventsSearch}%";
    $evtParams[] = $like; $evtParams[] = $like;
    $evtTypes .= 'ss';
}
$evtWhereSql = $evtWhere ? 'WHERE ' . implode(' AND ', $evtWhere) : '';

// Count total events
$evtCountSql = "
    SELECT COUNT(*) AS total 
    FROM events e 
    LEFT JOIN venues v ON e.venue_id = v.venue_id 
    $evtWhereSql
";
if ($stmt = $conn->prepare($evtCountSql)) {
    if ($evtParams) $stmt->bind_param($evtTypes, ...$evtParams);
    $stmt->execute();
    $totalEvents = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();
} else {
    $totalEventsResult = $conn->query("SELECT COUNT(*) AS total FROM events");
    $totalEvents = $totalEventsResult ? $totalEventsResult->fetch_assoc()['total'] : 0;
}
$totalEventsPages = ceil(max(1, $totalEvents) / $eventsLimit);

$events = [];
$evtSql = "
    SELECT e.*, v.name AS venue_name 
    FROM events e 
    LEFT JOIN venues v ON e.venue_id = v.venue_id 
    $evtWhereSql
    ORDER BY e.start_date DESC LIMIT ? OFFSET ?
";
if ($stmt = $conn->prepare($evtSql)) {
    $bindTypes = $evtTypes . 'ii';
    $bindParams = $evtParams;
    $bindParams[] = $eventsLimit; $bindParams[] = $eventsOffset;
    $stmt->bind_param($bindTypes, ...$bindParams);
    $stmt->execute();
    $events = $stmt->get_result();
    $stmt->close();
} else {
    $events = $conn->query("
        SELECT e.*, v.name AS venue_name 
        FROM events e 
        LEFT JOIN venues v ON e.venue_id = v.venue_id 
        ORDER BY e.start_date DESC LIMIT $eventsLimit OFFSET $eventsOffset
    ");
}

// --- Server-side search/filter for Manage Feedback ---
$feedbacksLimit = 20;
$feedbacksPage = max(1, (int)($_GET['feedbacks_page'] ?? 1));
$feedbacksOffset = ($feedbacksPage - 1) * $feedbacksLimit;

$feedbacksSearch = trim($conn->real_escape_string($_GET['feedbacks_search'] ?? ''));
$feedbacksStatus = trim($conn->real_escape_string($_GET['feedbacks_status'] ?? ''));

$fbWhere = [];
$fbParams = [];
$fbTypes = '';
if ($feedbacksSearch !== '') {
    $fbWhere[] = "(u.name LIKE ? OR f.subject LIKE ? OR f.message LIKE ?)";
    $like = "%{$feedbacksSearch}%";
    $fbParams[] = $like; $fbParams[] = $like; $fbParams[] = $like;
    $fbTypes .= 'sss';
}
if ($feedbacksStatus !== '') { 
    $fbWhere[] = 'f.status = ?'; 
    $fbParams[] = $feedbacksStatus; 
    $fbTypes .= 's'; 
}
$fbWhereSql = $fbWhere ? 'WHERE ' . implode(' AND ', $fbWhere) : '';

// Count total feedbacks
$fbCountSql = "
    SELECT COUNT(*) AS total 
    FROM feedback f 
    LEFT JOIN users u ON f.user_id = u.user_id 
    $fbWhereSql
";
if ($stmt = $conn->prepare($fbCountSql)) {
    if ($fbParams) $stmt->bind_param($fbTypes, ...$fbParams);
    $stmt->execute();
    $totalFeedbacks = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();
} else {
    $totalFeedbacksResult = $conn->query("SELECT COUNT(*) AS total FROM feedback");
    $totalFeedbacks = $totalFeedbacksResult ? $totalFeedbacksResult->fetch_assoc()['total'] : 0;
}
$totalFeedbacksPages = ceil(max(1, $totalFeedbacks) / $feedbacksLimit);

$feedbacks = [];
$fbSql = "
    SELECT f.*, u.name AS user_name 
    FROM feedback f 
    LEFT JOIN users u ON f.user_id = u.user_id 
    $fbWhereSql
    ORDER BY f.submitted_at DESC LIMIT ? OFFSET ?
";
if ($stmt = $conn->prepare($fbSql)) {
    $bindTypes = $fbTypes . 'ii';
    $bindParams = $fbParams;
    $bindParams[] = $feedbacksLimit; $bindParams[] = $feedbacksOffset;
    $stmt->bind_param($bindTypes, ...$bindParams);
    $stmt->execute();
    $feedbacks = $stmt->get_result();
    $stmt->close();
} else {
    $feedbacks = $conn->query("
        SELECT f.*, u.name AS user_name 
        FROM feedback f 
        LEFT JOIN users u ON f.user_id = u.user_id 
        ORDER BY f.submitted_at DESC LIMIT $feedbacksLimit OFFSET $feedbacksOffset
    ");
}

// --- Statistics ---
$statsActiveUsers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE status='active'");
$statsTotalActiveUsers = $statsActiveUsers ? $statsActiveUsers->fetch_assoc()['total'] : 0;

$statsTotalBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings");
$statsTotalBookingsCount = $statsTotalBookings ? $statsTotalBookings->fetch_assoc()['total'] : 0;

$statsApprovedBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status='approved'");
$statsApprovedCount = $statsApprovedBookings ? $statsApprovedBookings->fetch_assoc()['total'] : 0;

$statsFacilitiesInUse = $conn->query("
    SELECT COUNT(DISTINCT b.venue_id) AS total 
    FROM bookings b 
    WHERE b.booking_date = CURDATE() AND b.status IN ('approved', 'pending')
");
$statsFacilitiesCount = $statsFacilitiesInUse ? $statsFacilitiesInUse->fetch_assoc()['total'] : 0;

$statsTotalFeedback = $conn->query("SELECT COUNT(*) AS total FROM feedback");
$statsTotalFeedbackCount = $statsTotalFeedback ? $statsTotalFeedback->fetch_assoc()['total'] : 0;

$statsAvgRatingValue = 4.8; // No rating field in feedback table, using default

$page = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel</title>
<link rel="stylesheet" href="css/global.css">
<link rel="stylesheet" href="css/dashboard.css">
<link rel="stylesheet" href="css/navbar.css">
<link rel="stylesheet" href="css/filters_pagination.css">
<link rel="stylesheet" href="css/search_notification.css">
<link rel="stylesheet" href="css/bookings.css">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<style>
/* Sidebar styles - Orange background */
.sidebar {
    background-color:rgb(255, 115, 0) !important;
    border-right: 1px solid #ff7300;
    box-shadow: 2px 0 8px rgba(255, 255, 255, 0.05);
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 220px !important;
    height: 100vh !important;
    z-index: 900 !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
}

/* Ensure main content area doesn't get blocked by sidebar */
.main-area {
    margin-left: 220px !important;
}

/* Ensure topnav is positioned correctly */
.topnav {
    left: 220px !important;
}

/* Sidebar Header Styles */
.sidebar-header {
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    padding: 20px 10px !important;
    margin-bottom: 15px !important;
    border-bottom: none !important;
}

.sidebar-logo {
    width: 120px !important;
    height: 120px !important;
    border-radius: 50% !important;
    object-fit: cover;
    display: block;
    margin: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.sidebar-header-text {
    display: none !important;
}

.tarumt-acronym {
    font-size: 18px !important;
    font-weight: bold !important;
    line-height: 1.2;
    margin: 0;
    padding: 0;
}

.tar-text {
    color: #d32f2f !important; /* Red color for TAR */
}

.umt-text {
    color: #1976d2 !important; /* Blue color for UMT */
}

.tarumt-full-name {
    font-size: 9px !important;
    color: #000000 !important;
    line-height: 1.3;
    font-weight: normal;
    margin: 0;
    padding: 0;
}

.tarumt-full-name div {
    margin: 0;
    padding: 0;
}

/* Sidebar Navigation Links - Matching Image */
.sidebar a {
    color: #ffffff !important;
    text-decoration: none !important;
    padding: 12px 20px !important;
    margin: 5px 10px !important;
    border-radius: 8px !important;
    display: block !important;
    transition: all 0.3s ease;
    font-weight: normal !important;
    border-left: none !important;
}

.sidebar a:hover {
    background-color: rgba(255, 149, 0, 0.7) !important;
    color: #ffffff !important;
    transform: none;
}

.sidebar a.active {
    background-color: rgba(255, 149, 0, 0.9) !important;
    color: #ffffff !important;
    font-weight: normal !important;
    border-left: none !important;
    border-radius: 8px !important;
}

/* Admin-specific styles */
.admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.admin-stat-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.admin-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.admin-stat-card h3 {
    color: #666;
    font-size: 14px;
    font-weight: 500;
    margin: 0 0 10px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.admin-stat-card .count {
    font-size: 36px;
    font-weight: 700;
    color: #FF6600;
    display: block;
}

.admin-table-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 30px;
}

.admin-table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    border-bottom: 1px solid #eee;
    gap: 20px;
    flex-wrap: wrap;
}

.admin-table-header h2 {
    margin: 0;
    font-size: 20px;
    color: #333;
    flex-shrink: 0;
}

.header-filters {
    display: flex;
    gap: 15px;
    align-items: center;
    flex: 1;
    min-width: 300px;
}

.add-btn {
    background: #FF6600;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: background 0.3s ease;
}

.add-btn:hover {
    background: #e55a00;
}

table {
    width: 100%;
    border-collapse: collapse;
}

table thead {
    background: #f8f9fa;
}

table th {
    padding: 15px;
    text-align: center; /* center column titles */
    vertical-align: middle;
    font-weight: 600;
    color: #666;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Align table cells under centered headers */
table td {
    padding: 15px;
    border-bottom: 1px solid #eee;
    color: #333;
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word;
}

/* Center Role / Status / Action columns in Manage Users table */
.users-table th:nth-child(5),
.users-table th:nth-child(6),
.users-table th:nth-child(7) {
    text-align: center;
}
.users-table td:nth-child(5),
.users-table td:nth-child(6),
.users-table td:nth-child(7) {
    text-align: center;
    vertical-align: middle;
}

/* Make Manage Users rows more spacious and avatars larger for readability */
.users-table td {
    padding: 18px 20px; /* more breathing room */
    line-height: 1.45;
    vertical-align: middle;
}

.users-table img.avatar {
    width: 56px;
    height: 56px;
    border-radius: 8px;
    object-fit: cover;
    border: 2px solid rgba(255,102,0,0.12);
}

.users-table .action-btns button { padding: 8px 10px; }

/* Center Status / Action columns in Manage Facilities table */
.facilities-table th:nth-child(6),
.facilities-table th:nth-child(7) {
    text-align: center;
}
.facilities-table td:nth-child(6),
.facilities-table td:nth-child(7) {
    text-align: center;
    vertical-align: middle;
}

table tbody tr:hover {
    background: #f8f9fa;
}

table tbody tr:last-child td {
    border-bottom: none;
}

.avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.action-btns {
    display: flex;
    gap: 8px;
}

.view, .edit, .delete, .approve, .reject, .toggle {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.3s ease;
}

/* Icon-only action buttons */
.action-btn {
    width: 40px;
    height: 40px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 16px;
}
.action-btn i { pointer-events: none; }
.action-btn.view { background: #17a2b8; color: #fff; }
.action-btn.approve, .action-btn.activate { background: #28a745; color: #fff; }
.action-btn.suspend, .action-btn.reject { background: #dc3545; color: #fff; }
.action-btn.edit { background: #ffc107; color: #212529; }
.action-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,0.08); }

.view:hover {
    background: #138496;
}

.edit {
    background: #ffc107;
    color: #333;
}

.edit:hover {
    background: #e0a800;
}

.delete {
    background: #dc3545;
    color: white;
}

.delete:hover {
    background: #c82333;
}

.approve {
    background: #28a745;
    color: white;
}

.approve:hover {
    background: #218838;
}

.reject {
    background: #dc3545;
    color: white;
}

.reject:hover {
    background: #c82333;
}

.suspend {
    background: #dc3545;
    color: white;
}

.suspend:hover {
    background: #c82333;
}

.activate {
    background: #28a745;
    color: white;
}

.activate:hover {
    background: #218838;
}

.approve {
    background: #007bff;
    color: white;
}

.approve:hover {
    background: #0056b3;
}

.toggle {
    background: #6c757d;
    color: white;
}

.toggle:hover {
    background: #5a6268;
}

/* Select Dropdown Styling */
select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23ff6b35' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 35px;
}

select option {
    padding: 10px;
    background: white;
    color: #333;
    font-weight: 500;
}

select option:hover {
    background: #ffe5d0;
    color: #ff6b35;
}

select option:checked {
    background: linear-gradient(#ff8c42, #ff8c42);
    background-color: #ff8c42;
    color: white;
}

/* Input styling for date and number inputs */
input[type="date"],
input[type="month"],
input[type="number"] {
    appearance: none;
}

input[type="date"]:focus,
input[type="month"]:focus,
input[type="number"]:focus {
    outline: none;
    border-color: #ff6b35;
    box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    padding: 30px;
    border-radius: 12px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}

.modal-content h3 {
    margin: 0 0 20px 0;
    color: #333;
}

.close {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 28px;
    font-weight: bold;
    color: #999;
    cursor: pointer;
}

.close:hover {
    color: #333;
}

.modal-content form label {
    display: block;
    margin: 15px 0 5px 0;
    color: #666;
    font-weight: 500;
}

.modal-content form input,
.modal-content form select,
.modal-content form textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
    resize: vertical;
}

.modal-content form input:focus,
.modal-content form select:focus {
    outline: none;
    border-color: #FF6600;
}

.avatar-preview {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    border: 3px dashed #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    overflow: hidden;
    transition: border-color 0.3s ease;
}

.avatar-preview:hover {
    border-color: #FF6600;
}

.avatar-preview .placeholder {
    font-size: 48px;
    color: #ddd;
}

.facility-image-preview {
    width: 200px;
    height: 200px;
    border-radius: 8px;
    border: 3px dashed #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    overflow: hidden;
    transition: border-color 0.3s ease;
    background: #f9f9f9;
}

.facility-image-preview:hover {
    border-color: #FF6600;
}

.facility-image-preview .placeholder {
    font-size: 64px;
}

.submit {
    background: #FF6600;
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    margin-top: 20px;
    transition: backgroundd 0.3s ease;
}

.submit:hover {
    backgroundd: #e55a00;
}

.password-container {
    position: relative;
}

#togglePassword {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #FF6600;
    font-size: 13px;
}

.read-details p {
    margin: 10px 0;
    color: #666;
}

.read-details strong {
    color: #333;
    display: inline-block;
    min-width: 150px;
}

/* Search and Filter Styles */
.search-box {
    position: relative;
    flex: 1;
    max-width: 400px;
}

.search-input {
    width: 100%;
    padding: 10px 40px 10px 40px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.search-input:focus {
    outline: none;
    border-color: #FF6600;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
}

.clear-btn {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #999;
    font-size: 20px;
}

/* Sorting indicators for table headers */
th.sort-asc::after, th.sort-desc::after {
    display: inline-block;
    margin-left: 8px;
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
}
th.sort-asc::after { content: '\f062'; /* fa-chevron-up */ }
th.sort-desc::after { content: '\f063'; /* fa-chevron-down */ }
th[style*="cursor: pointer"] { cursor: pointer; }

.clear-btn:hover {
    color: #333;
}

.user-dropdown-empty {
    padding: 12px 18px;
    color: #666;
    text-align: center;
    font-weight: 600;
}

/* User Search Dropdown Styles */
.user-search-container {
    position: relative;
}

.user-dropdown {
    max-height: 250px;
    overflow-y: auto;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 6px 6px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.user-dropdown-item {
    padding: 12px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
}

.user-dropdown-item:hover {
    background-color: #f5f5f5;
}

.user-dropdown-item:last-child {
    border-bottom: none;
}

.user-dropdown-item.selected {
    background-color: #fff3e0;
    font-weight: 500;
}

.user-dropdown-item .user-name {
    font-weight: 500;
    color: #333;
    display: block;
}

.user-dropdown-item .user-id {
    font-size: 12px;
    color: #666;
    margin-top: 2px;
}

.user-dropdown-empty {
    padding: 15px;
    text-align: center;
    color: #999;
    font-style: italic;
}

#userSearchClear:hover {
    color: #333;
}

.custom-dropdown {
    position: relative;
    min-width: 150px;
}

.cd-selected {
    padding: 10px 15px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}

.cd-selected:hover {
    border-color: #FF6600;
}

.cd-list {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-top: 5px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    z-index: 100;
    list-style: none;
    padding: 0;
    margin: 5px 0 0 0;
}

.cd-list li {
    padding: 10px 15px;
    cursor: pointer;
}

.cd-list li:hover {
    background: #f0f0f0;
}

/* Flash notification (top-right) */
#flash-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 2000;
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-width: 320px;
}
.flash {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #333;
    color: #fff;
    padding: 12px 14px;
    border-radius: 8px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.15);
    animation: slideIn 0.25s ease;
}
.flash .flash-message { flex: 1; margin-right: 10px; font-size: 14px; }
.flash .flash-close { background: transparent; border: none; color: #fff; font-size: 18px; cursor: pointer; }
.flash.error { background: #c92b2b; }
.flash.success { background: #2b8a3e; }
.flash.info { background: #0366d6; }

@keyframes slideIn { from { transform: translateY(-10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

/* Status badge styles */
.status {
    display: inline-block;
    padding: 8px 14px;
    border-radius: 6px;
    font-weight: 500;
    font-size: 13px;
    white-space: nowrap;
}

.status.pending {
    background: #fff3cd;
    color: #856404;
}

.status.approved {
    background: #d4edda;
    color: #155724;
}

.status.booked {
    background: #d4edda;
    color: #155724;
}

.status.completed {
    background: #cfe2ff;
    color: #084298;
}

.status.cancelled {
    background: #f8d7da;
    color: #721c24;
}

.status.rejected {
    background: #f8d7da;
    color: #721c24;
}

/* Confirmation Modal */
.confirm-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 3000;
    justify-content: center;
    align-items: center;
}

.confirm-modal.show {
    display: flex;
}

.confirm-modal-content {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    max-width: 400px;
    text-align: center;
    animation: modalSlideIn 0.3s ease;
}

.confirm-modal-content i {
    font-size: 3rem;
    color: #ff7b00;
    margin-bottom: 15px;
    display: block;
}

.confirm-modal-content h3 {
    font-size: 1.3rem;
    color: #333;
    margin: 15px 0;
    font-weight: 600;
}

.confirm-modal-content p {
    color: #666;
    margin-bottom: 25px;
    font-size: 0.95rem;
}

.confirm-modal-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.confirm-modal-buttons button {
    padding: 10px 25px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-confirm {
    background: #ff7b00;
    color: white;
}

.btn-confirm:hover {
    background: #e56a00;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 123, 0, 0.3);
}

.btn-cancel {
    background: #e9ecef;
    color: #333;
}

.btn-cancel:hover {
    background: #dee2e6;
    transform: translateY(-2px);
}

.suspend-btn {
    background: #dc3545;
    color: white;
}

.suspend-btn:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.activate-btn {
    background: #28a745;
    color: white;
}

.activate-btn:hover {
    background: #218838;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.approve-btn {
    background: #007bff;
    color: white;
}

.approve-btn:hover {
    background: #0056b3;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.delete-btn {
    background: #dc3545;
    color: white;
}

.delete-btn:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.resolve-btn {
    background: #28a745;
    color: white;
}

.resolve-btn:hover {
    background: #218838;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

@keyframes modalSlideIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

/* Feedback status badges (match feedback.php) */
.feedback-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    white-space: nowrap;
}
.status-pending {
    background: #fff4e5;
    color: #ff9800;
}
.status-reviewed {
    background: #e3f2fd;
    color: #2196f3;
}
.status-resolved {
    background: #e8f5e9;
    color: #4caf50;
}

/* Time Picker Styles */
.time-picker-grid {
    display: grid;
    grid-template-columns: repeat(4, 2fr);
    gap: 27px;
    padding: 20px;
    max-height: 500px;
    overflow-y: auto;
}

.time-slot {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    background: #f8f8f8;
    color: #1a1a1a;
    position: relative;
    overflow: hidden;
    min-height: 50px;
}

.time-slot::before {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 122, 0, 0.2),
        transparent
    );
    transition: left 0.5s ease;
}

.time-slot:hover::before {
    left: 100%;
}

.time-slot:hover {
    background: rgba(255, 122, 0, 0.15);
    border-color: #ff7a00;
    transform: scale(1);
    box-shadow: 0 6px 20px rgba(255, 122, 0, 0.25);
}

.time-slot.selected {
    background: linear-gradient(135deg, #ff7a00, #ff8c00);
    border-color: #ff7a00;
    color: white;
    font-weight: 700;
    box-shadow: 0 8px 30px rgba(255, 122, 0, 0.4);
    transform: scale(1);
}

</style>
</head>
<body class="dashboard-page">

<!-- Confirmation Modal -->
<div id="confirmModal" class="confirm-modal">
    <div class="confirm-modal-content">
        <i class="fas fa-exclamation-circle"></i>
        <h3>Reject Booking?</h3>
        <p>Are you sure you want to reject this booking? This action cannot be undone.</p>
        <div class="confirm-modal-buttons">
            <button class="btn-cancel" onclick="closeConfirmModal()">No, Keep It</button>
            <button class="btn-confirm" onclick="confirmReject()">Yes, Reject Booking</button>
        </div>
    </div>
</div>

<!-- Suspend User Modal -->
<div id="suspendModal" class="confirm-modal">
    <div class="confirm-modal-content">
        <i class="fas fa-ban"></i>
        <h3 id="suspendModalTitle">Suspend User?</h3>
        <p id="suspendModalMessage">Are you sure you want to suspend <strong id="suspendUserName"></strong>? This will block their account access.</p>
        <div id="suspendMultipleUsers" style="display: none;">
            <p>Are you sure you want to suspend the following users? This will block their account access:</p>
            <div id="suspendUsersList" style="max-height: 200px; overflow-y: auto; margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px;"></div>
        </div>
        <div class="confirm-modal-buttons">
            <button class="btn-cancel" onclick="closeSuspendModal()">No, Cancel</button>
            <button class="btn-confirm suspend-btn" onclick="confirmSuspend()">Yes, Suspend</button>
        </div>
    </div>
</div>

<!-- Activate User Modal -->
<div id="activateModal" class="confirm-modal">
    <div class="confirm-modal-content">
        <i class="fas fa-check"></i>
        <h3 id="activateModalTitle">Activate User?</h3>
        <p id="activateModalMessage">Are you sure you want to activate <strong id="activateUserName"></strong>? This will restore their account access.</p>
        <div id="activateMultipleUsers" style="display: none;">
            <p>Are you sure you want to activate the following users? This will restore their account access:</p>
            <div id="activateUsersList" style="max-height: 200px; overflow-y: auto; margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px;"></div>
        </div>
        <div class="confirm-modal-buttons">
            <button class="btn-cancel" onclick="closeActivateModal()">No, Cancel</button>
            <button class="btn-confirm activate-btn" onclick="confirmActivate()">Yes, Activate</button>
        </div>
    </div>
</div>

<!-- Approve User Modal -->
<div id="approveModal" class="confirm-modal">
    <div class="confirm-modal-content">
        <i class="fas fa-check"></i>
        <h3 id="approveModalTitle">Approve User?</h3>
        <p id="approveModalMessage">
            Are you sure you want to approve this user's access request <strong id="approveUserName"></strong>? This will activate their account and allow them to log in.</p>
        <div class="confirm-modal-buttons">
            <button class="btn-cancel" onclick="closeApproveModal()">No, Cancel</button>
            <button class="btn-confirm approve-btn" onclick="confirmApprove()">Yes, Approve</button>
        </div>
    </div>
</div>

<!-- Reject User Modal -->
<div id="rejectUserModal" class="confirm-modal">
    <div class="confirm-modal-content">
        <i class="fas fa-times-circle"></i>
        <h3 id="rejectUserModalTitle">Reject User?</h3>
        <p id="rejectUserModalMessage">
            Are you sure you want to reject this user's access request <strong id="rejectUserName"></strong>? This action will prevent them from logging in unless they register again or are manually added.</p>
        <div class="confirm-modal-buttons">
            <button class="btn-cancel" onclick="closeRejectUserModal()">No, Cancel</button>
            <button class="btn-confirm suspend-btn" onclick="confirmRejectUser()">Yes, Reject</button>
        </div>
    </div>
</div>

<!-- Venue Selection Required Alert Modal -->
<div id="venueSelectionAlert" class="confirm-modal">
    <div class="confirm-modal-content" style="max-width: 500px;">
        <i class="fas fa-info-circle" style="color: #ff7b00; font-size: 2.5em; margin-bottom: 15px;"></i>
        <h3 style="margin-bottom: 15px;">Venue Selection Required</h3>
        <p id="venueSelectionMessage" style="white-space: pre-line; line-height: 1.6; margin-bottom: 20px; text-align: left; padding: 0 10px;">Please select a venue first before choosing a date.</p>
        <div class="confirm-modal-buttons">
            <button class="btn-confirm" onclick="closeVenueSelectionAlert()">OK</button>
        </div>
    </div>
</div>

<!-- Date/Time Availability Alert Modal -->
<div id="dateAvailabilityAlert" class="confirm-modal">
    <div class="confirm-modal-content" style="max-width: 500px;">
        <i class="fas fa-exclamation-triangle" style="color: #ff7b00; font-size: 2.5em; margin-bottom: 15px;"></i>
        <h3 style="margin-bottom: 15px;">Date Not Available</h3>
        <p id="dateAvailabilityMessage" style="white-space: pre-line; line-height: 1.6; margin-bottom: 20px; text-align: left; padding: 0 10px;">This date is fully booked or has an event. Please select a different date.</p>
        <div class="confirm-modal-buttons">
            <button class="btn-confirm" onclick="closeDateAvailabilityAlert()">OK</button>
        </div>
    </div>
</div>

<!-- Delete Announcement Modal -->
<div id="deleteAnnouncementModal" class="confirm-modal">
    <div class="confirm-modal-content">
        <i class="fas fa-trash"></i>
        <h3>Delete Announcement?</h3>
        <p>Are you sure you want to delete "<strong id="deleteAnnouncementTitle"></strong>"? This action cannot be undone.</p>
        <div class="confirm-modal-buttons">
            <button class="btn-cancel" onclick="closeDeleteAnnouncementModal()">No, Cancel</button>
            <button class="btn-confirm delete-btn" onclick="confirmDeleteAnnouncement()">Yes, Delete</button>
        </div>
    </div>
</div>

<!-- Delete Event Modal -->
<div id="deleteEventModal" class="confirm-modal">
    <div class="confirm-modal-content">
        <i class="fas fa-trash"></i>
        <h3>Delete Event?</h3>
        <p>Are you sure you want to delete "<strong id="deleteEventName"></strong>"? This action cannot be undone.</p>
        <div class="confirm-modal-buttons">
            <button class="btn-cancel" onclick="closeDeleteEventModal()">No, Cancel</button>
            <button class="btn-confirm delete-btn" onclick="confirmDeleteEvent()">Yes, Delete</button>
        </div>
    </div>
</div>

<!-- Resolve Feedback Modal -->
<div id="resolveFeedbackModal" class="confirm-modal">
    <div class="confirm-modal-content">
        <i class="fas fa-check"></i>
        <h3>Resolve Feedback?</h3>
        <p>Are you sure you want to mark this feedback as resolved? This will update its status to "Resolved".</p>
        <div class="confirm-modal-buttons">
            <button class="btn-cancel" onclick="closeResolveFeedbackModal()">No, Cancel</button>
            <button class="btn-confirm resolve-btn" onclick="confirmResolveFeedback()">Yes, Resolve</button>
        </div>
    </div>
</div>

<?php // Render flash messages (top-right popups)
if(!empty($_SESSION['flash_messages'])):
?>
<div id="flash-container">
    <?php foreach($_SESSION['flash_messages'] as $idx => $f): ?>
        <div class="flash <?php echo htmlspecialchars($f['type']); ?>" data-index="<?php echo $idx; ?>">
            <div class="flash-message"><?php echo htmlspecialchars($f['message']); ?></div>
            <button class="flash-close" aria-label="Close">&times;</button>
        </div>
    <?php endforeach; ?>
</div>
<?php unset($_SESSION['flash_messages']); endif; ?>


<div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="images/logo1.png" alt="Logo" class="sidebar-logo">
        </div>
        <a href="admin_dashboard.php?page=dashboard" <?= $page=='dashboard'?'class="active"':'' ?>>Dashboard</a>
        <a href="admin_dashboard.php?page=users" <?= $page=='users'?'class="active"':'' ?>>Manage Users</a>
        <a href="admin_dashboard.php?page=facilities" <?= $page=='facilities'?'class="active"':'' ?>>Manage Facilities</a>
        <a href="admin_dashboard.php?page=bookings" <?= $page=='bookings'?'class="active"':'' ?>>Manage Bookings</a>
        <a href="admin_dashboard.php?page=announcements" <?= $page=='announcements'?'class="active"':'' ?>>Announcements</a>
        <a href="admin_dashboard.php?page=events" <?= $page=='events'?'class="active"':'' ?>>Events</a>
        <a href="admin_dashboard.php?page=feedback" <?= $page=='feedback'?'class="active"':'' ?>>Feedback</a>
        <a href="admin_dashboard.php?page=reports" <?= $page=='reports'?'class="active"':'' ?>>Reports & Analytics</a>
    </div>

    <!-- Main Area -->
    <div class="main-area">
        <!-- Top Navbar -->
        <div class="topnav">
            <h1 style="font-size: 24px; margin: 0; color: #fff;">
                Admin Panel
            </h1>
            <div class="nav-right">
                <div class="user-info">
                    <img src="<?= htmlspecialchars($admin_avatar) ?>" class="avatar" alt="Avatar">
                    <span><?= htmlspecialchars($admin_name) ?></span>
                </div>
                <a href="logout.php" class="logout-btn" title="Logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">

<?php if($page=='dashboard'): ?>
<section>
    <h2 style="margin-bottom: 20px; color: #333;">Dashboard Overview</h2>
    <div class="admin-stats">
        <div class="admin-stat-card">
            <h3><i class="fa-solid fa-users"></i> Total Users</h3>
            <span class="count"><?= (int)$totalUsers ?></span>
        </div>
        <div class="admin-stat-card">
            <h3><i class="fa-solid fa-building"></i> Total Facilities</h3>
            <span class="count"><?= (int)$totalVenues ?></span>
        </div>
        <div class="admin-stat-card">
            <h3><i class="fa-solid fa-check-circle"></i> Current Active Bookings</h3>
            <span class="count"><?= (int)$pendingBookings ?></span>
        </div>
    </div>

    <div class="booking-table-container">
        <div class="admin-table-header header-row">
            <h2><i class="fa-solid fa-user-clock"></i> Access Requests</h2>
        </div>
        <table class="booking-table" id="pendingUsersTable">
            <thead>
                <tr>
                    <th><i class="fas fa-hashtag"></i> ID</th>
                    <th><i class="fas fa-user"></i> Name</th>
                    <th><i class="fas fa-envelope"></i> Email</th>
                    <th><i class="fas fa-user-tag"></i> Role</th>
                    <th><i class="fas fa-info-circle"></i> Status</th>
                    <th><i class="fas fa-cog"></i> Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if($pendingUsers && $pendingUsers->num_rows>0): ?>
                <?php while($row = $pendingUsers->fetch_assoc()): ?>
                <tr class="booking-row">
                    <td><strong><?= htmlspecialchars($row['user_id']) ?></strong></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td>
                        <?php if(!empty($row['email']) && filter_var($row['email'], FILTER_VALIDATE_EMAIL)): ?>
                            <a href="mailto:<?= htmlspecialchars($row['email']) ?>" style="color: #007bff; text-decoration: none;" title="Click to send email">
                                <i class="fas fa-envelope" style="margin-right: 5px;"></i><?= htmlspecialchars($row['email']) ?>
                            </a>
                        <?php else: ?>
                            <span style="color: #dc3545;" title="Invalid or missing email">
                                <i class="fas fa-exclamation-triangle" style="margin-right: 5px;"></i><?= !empty($row['email']) ? htmlspecialchars($row['email']) : 'No email' ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><span class="status <?= strtolower($row['role']) ?>" style="background: #e9ecef; color: #495057;"><i class="fas fa-user-tag" style="margin-right:5px;"></i><?= ucfirst($row['role']) ?></span></td>
                    <td><span class="status pending" style="background: #fff3cd; color: #856404;"><i class="fas fa-clock" style="margin-right:5px;"></i>Pending</span></td>
                    <td>
                        <div class="action-btns">
                            <button class="action-btn approve" onclick="approveUser('<?= htmlspecialchars($row['user_id']) ?>', '<?= htmlspecialchars(addslashes($row['name'])) ?>')" title="Approve User"><i class="fas fa-check"></i></button>
                            <button class="action-btn cancel" onclick="rejectUser('<?= htmlspecialchars($row['user_id']) ?>', '<?= htmlspecialchars(addslashes($row['name'])) ?>')" title="Reject User"><i class="fas fa-times"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
            <tr><td colspan="6" style="text-align: center; color: #999;">No users waiting for access approval.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php elseif($page=='users'): ?>
<section>
    <div class="admin-table-container">
        <div class="admin-table-header header-row">
            <div class="header-filters">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="usersSearch" class="search-input" placeholder="Search users..." value="<?= htmlspecialchars($_GET['users_search'] ?? '') ?>">
                    <span class="clear-btn" id="usersClearBtn">×</span>
                </div>
                <select id="usersFilterSelect" name="users_role" hidden>
                    <option value="">All</option>
                    <option value="staff" <?= (isset($_GET['users_role']) && $_GET['users_role'] === 'staff') ? 'selected' : '' ?>>Staff</option>
                    <option value="student" <?= (isset($_GET['users_role']) && $_GET['users_role'] === 'student') ? 'selected' : '' ?>>Student</option>
                </select>
                <div class="custom-dropdown" id="usersFilterDropdown">
                    <div class="cd-selected">All Roles</div>
                    <ul class="cd-list">
                        <li data-value="">All Roles</li>
                        <li data-value="staff">Staff</li>
                        <li data-value="student">Student</li>
                    </ul>
                </div>
                <select id="usersStatusFilterSelect" hidden>
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="blacklisted">Blacklisted</option>
                </select>
                <div class="custom-dropdown" id="usersStatusFilterDropdown">
                    <div class="cd-selected">All Status</div>
                    <ul class="cd-list">
                        <li data-value="">All Status</li>
                        <li data-value="active">Active</li>
                        <li data-value="blacklisted">Blacklisted</li>
                    </ul>
                </div>
            </div>
            <button class="add-btn" onclick="openAddUserModal()"><i class="fas fa-plus"></i> Add New User</button>
            <button class="suspend-selected-btn" onclick="suspendSelectedUsers()" style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-left: 10px;"><i class="fas fa-ban"></i> Suspend Selected</button>
            <button class="activate-selected-btn" onclick="activateSelectedUsers()" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-left: 10px;"><i class="fas fa-check"></i> Activate Selected</button>
        </div>
        <table class="users-table" id="usersTable">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAllUsers" onclick="toggleSelectAllUsers()"></th>
                    <th>Avatar</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if($users && $users->num_rows>0): ?>
                <?php while($user = $users->fetch_assoc()): ?>
                <?php
                    $user['avatar_path'] = $user['avatar_path'] ?: 'images/avatar/default.png';
                    $user['living_place'] = $user['living_place'] ?? '';
                    $user['phone_number'] = $user['phone_number'] ?? '';
                    $user['date_of_birth'] = $user['date_of_birth'] ?? '';
                    $user['gender'] = $user['gender'] ?? '';
                ?>
                <tr>
                    <td><input type="checkbox" class="user-checkbox" value="<?= htmlspecialchars($user['user_id']) ?>" data-name="<?= htmlspecialchars($user['name']) ?>" data-status="<?= htmlspecialchars($user['status']) ?>"></td>
                    <td><img src="<?= htmlspecialchars($user['avatar_path']) ?>" class="avatar" alt="avatar"></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <span style="padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500;
                            background: <?= ($user['role']=='staff')?'#fff3cd':'#d4edda' ?>;
                            color: <?= ($user['role']=='staff')?'#856404':'#155724' ?>;">
                            <?= htmlspecialchars( $user['role'] ? ucfirst(strtolower($user['role'])) : '-' ) ?>
                        </span>
                    </td>
                    <td>
                        <?php $ust = strtolower($user['status'] ?? 'active');
                              if($ust === 'active') { $bg = '#d4edda'; $col = '#155724'; }
                              elseif($ust === 'pending') { $bg = '#cce5ff'; $col = '#004085'; }
                              elseif($ust === 'inactive') { $bg = '#fff3cd'; $col = '#856404'; }
                              else { /* blacklisted or other */ $bg = '#f8d7da'; $col = '#721c24'; }
                        ?>
                        <span style="padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; background: <?= $bg ?>; color: <?= $col ?>;">
                            <?= htmlspecialchars(ucfirst($user['status'] ?? 'active')) ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-btns">
                            <button class="action-btn view" title="View" onclick='openViewUserModal(<?= json_encode($user, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)'><i class="fas fa-eye"></i></button>
                            <?php if($user['status'] === 'pending'): ?>
                                <button class="action-btn approve" title="Approve" onclick="approveUser('<?= htmlspecialchars($user['user_id']) ?>', '<?= htmlspecialchars($user['name']) ?>')"><i class="fas fa-check"></i></button>
                            <?php elseif($user['status'] === 'blacklisted'): ?>
                                <button class="action-btn activate" title="Activate" onclick="activateUser('<?= htmlspecialchars($user['user_id']) ?>', '<?= htmlspecialchars($user['name']) ?>')"><i class="fas fa-check"></i></button>
                            <?php else: ?>
                                <button class="action-btn suspend" title="Suspend" onclick="suspendUser('<?= htmlspecialchars($user['user_id']) ?>', '<?= htmlspecialchars($user['name']) ?>')"><i class="fas fa-ban"></i></button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
            <tr><td colspan="6" style="text-align: center; color: #999;">No users found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination for Manage Users -->
    <div class="pagination">
        <span style="margin-right:15px;color:#666;">Showing <?= $users && $users->num_rows > 0 ? $users->num_rows : 0 ?> of <?= $totalUsersCount ?></span>
        <?php if($totalUsersPages > 1): ?>
            <?php if($usersPage > 1): ?>
                <a href="admin_dashboard.php?page=users&users_page=<?= $usersPage-1 ?>"><i class="fas fa-chevron-left"></i> Prev</a>
            <?php endif; ?>
            <?php for($i=1;$i<=$totalUsersPages;$i++): ?>
                <?php if($i==$usersPage): ?>
                    <strong><?= $i ?></strong>
                <?php else: ?>
                    <a href="admin_dashboard.php?page=users&users_page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if($usersPage < $totalUsersPages): ?>
                <a href="admin_dashboard.php?page=users&users_page=<?= $usersPage+1 ?>">Next <i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<div id="userModal" class="modal">
<div class="modal-content user-modal">
<span class="close" onclick="closeUserModal()">&times;</span>
<div style="display:flex;gap:20px;align-items:flex-start;">
    <div>
        <div class="avatar-preview" id="avatarPreview">
            <span class="placeholder">+</span>
        </div>
    </div>
    <div style="flex:1;">
        <h3 id="modalTitle">Add/Edit User</h3>
        <form id="userForm" method="post" enctype="multipart/form-data">
            <input type="hidden" name="current_avatar_path" id="currentAvatar">
            
            <label>User ID</label>
            <input type="text" name="user_id" id="user_id" required>
            
            <label>Name</label>
            <input type="text" name="name" id="name" required>
            
            <label>Email</label>
            <input type="email" name="email" id="email" required>
            
            <label id="passwordLabel">Password <span id="passwordHint" style="font-size:0.85em;color:#666;">(Required)</span></label>
            <div class="password-container" id="passwordContainer">
                <input type="password" name="password" id="password" style="padding-right:70px;">
                <span id="togglePassword">Show</span>
            </div>
            
            <label>Role</label>
            <select hidden name="role" id="role" required>
                <option value="admin">Admin</option>
                <option value="staff">Staff</option>
                <option value="student">Student</option>
            </select>
            <div class="custom-dropdown" id="roleDropdown">
                <div class="cd-selected">Admin</div>
                <ul class="cd-list">
                    <li data-value="admin">Admin</li>
                    <li data-value="staff">Staff</li>
                    <li data-value="student">Student</li>
                </ul>
            </div>
            
            <label>Status</label>
            <select hidden name="status" id="status" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="pending">Pending</option>
                <option value="blacklisted">Blacklisted</option>
            </select>
            <div class="custom-dropdown" id="statusDropdown">
                <div class="cd-selected">Active</div>
                <ul class="cd-list">
                    <li data-value="active">Active</li>
                    <li data-value="inactive">Inactive</li>
                    <li data-value="pending">Pending</li>
                    <li data-value="blacklisted">Blacklisted</li>
                </ul>
            </div>

            <label>Living Place</label>
            <input type="text" name="living_place" id="living_place">
            
            <label>Phone</label>
            <input type="text" name="phone_number" id="phone_number">
            
            <label>Date of Birth</label>
            <input type="date" name="date_of_birth" id="date_of_birth">
            
            <label>Gender</label>
            <select hidden name="gender" id="gender">
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
            </select>
            <div class="custom-dropdown" id="genderDropdown">
                <div class="cd-selected">Select Gender</div>
                <ul class="cd-list">
                    <li data-value="">Select Gender</li>
                    <li data-value="male">Male</li>
                    <li data-value="female">Female</li>
                    <li data-value="other">Other</li>
                </ul>
            </div>
            
            <label>Avatar Image</label>
            <input type="file" id="avatarInput" name="avatar" accept="image/*">
            <small style="color:#999;">Select an image for the avatar (optional)</small>
            <br>
            <button type="submit" name="add_user" id="addBtn" class="submit"><i class="fas fa-plus"></i> Add User</button>
        </form>
    </div>
</div>
</div>
</div>

<!-- View User Modal -->
<div id="viewUserModal" class="modal">
    <div class="modal-content view-user">
        <span class="close" onclick="closeViewUserModal()">&times;</span>
        <div style="display:flex;gap:20px;">
            <div id="viewAvatar" style="width:160px;height:160px;border-radius:6px;overflow:hidden;border:1px solid #ddd;">
                <img src="images/avatar/default.png" style="width:100%;height:100%;object-fit:cover;">
            </div>
            <div class="read-details" id="viewDetails" style="max-width:400px;"></div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content" style="max-width:400px;text-align:center;">
        <h3 style="margin-top:0;color:#d32f2f;">Delete User</h3>
        <p style="margin:20px 0;">Are you sure you want to delete this user?</p>
        <p style="margin:10px 0;font-weight:bold;color:#333;" id="deleteUserName"></p>
        <p style="margin:20px 0;font-size:0.9em;color:#666;">This action cannot be undone.</p>
        <div style="display:flex;gap:10px;justify-content:center;margin-top:20px;">
            <button onclick="closeDeleteModal()" style="padding:10px 24px;background:#6c757d;color:white;border:none;border-radius:4px;cursor:pointer;">No, Cancel</button>
            <button onclick="confirmDelete()" style="padding:10px 24px;background:#d32f2f;color:white;border:none;border-radius:4px;cursor:pointer;">Yes, Delete</button>
        </div>
    </div>
</div>

<?php elseif($page=='facilities'): ?>
<section>
    <div class="admin-table-container">
        <div class="admin-table-header header-row">
            <h2>Manage Facilities</h2>
            <div class="header-filters">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="facilitiesSearch" class="search-input" placeholder="Search facilities..." value="<?= htmlspecialchars($_GET['facilities_search'] ?? '') ?>">
                    <span class="clear-btn" id="facilitiesClearBtn">×</span>
                </div>
                <select id="facilitiesStatusFilterSelect" hidden>
                    <option value="">All</option>
                    <option value="open">Open</option>
                    <option value="closed">Closed</option>
                </select>
                <div class="custom-dropdown" id="facilitiesStatusFilterDropdown">
                    <div class="cd-selected">All Status</div>
                    <ul class="cd-list">
                        <li data-value="">All Status</li>
                        <li data-value="open">Open</li>
                        <li data-value="closed">Closed</li>
                    </ul>
                </div>
            </div>
            <button class="add-btn" onclick="openAddFacilityModal()"><i class="fas fa-plus"></i> Add New Facility</button>
        </div>
        <table class="facilities-table" id="facilitiesTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Capacity</th>
                    <th>Location</th>
                    <th>Court</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if($facilities && $facilities->num_rows>0): ?>
                <?php while($fac = $facilities->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($fac['name']) ?></td>
                    <td><?= htmlspecialchars($fac['capacity']) ?></td>
                    <td><?= htmlspecialchars($fac['location']) ?></td>
                    <td><?= htmlspecialchars($fac['court']) ?></td>
                    <td><?= htmlspecialchars($fac['description'] ?? '') ?></td>
                    <td>
                        <?php
                            // Determine open/closed status based on open_time/close_time when available
                            $now = date('H:i');
                            $open_time = $fac['open_time'] ?? '';
                            $close_time = $fac['close_time'] ?? '';
                            $status_label = '';
                            $is_open = false;

                            if(!empty($open_time) && !empty($close_time)){
                                // Normalize times (HH:MM)
                                $ot = substr($open_time,0,5);
                                $ct = substr($close_time,0,5);
                                if($ot <= $ct){
                                    $is_open = ($now >= $ot && $now <= $ct);
                                } else {
                                    // Overnight range (e.g., 20:00 - 06:00)
                                    $is_open = ($now >= $ot || $now <= $ct);
                                }
                            } else {
                                // Fallback to status field values (support both active/inactive and open/closed)
                                $st = strtolower($fac['status'] ?? '');
                                $is_open = ($st === 'open' || $st === 'active');
                            }

                            $status_label = $is_open ? 'Open' : 'Closed';
                            $bg = $is_open ? '#d4edda' : '#f8d7da';
                            $col = $is_open ? '#155724' : '#721c24';
                        ?>
                        <span style="padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; background: <?= $bg ?>; color: <?= $col ?>;">
                            <?= htmlspecialchars($status_label) ?>
                        </span>
                        <?php if(!empty($open_time) || !empty($close_time)): ?>
                            <div style="font-size:11px;color:#666;margin-top:6px;">Hours: <?= htmlspecialchars($open_time ?: '-') ?> - <?= htmlspecialchars($close_time ?: '-') ?></div>
                        <?php endif; ?>
                    </td>
       
                    <td>
                        <div class="action-btns">
                            <button class="action-btn edit" title="Edit" onclick='openEditFacilityModal(<?= json_encode($fac, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)'><i class="fas fa-edit"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
            <tr><td colspan="7" style="text-align: center; color: #999;">No facilities found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination for Manage Facilities -->
    <div class="pagination">
        <span style="margin-right:15px;color:#666;">Showing <?= $facilities && $facilities->num_rows > 0 ? $facilities->num_rows : 0 ?> of <?= $totalFacilitiesCount ?></span>
        <?php if($totalFacilitiesPages > 1): ?>
            <?php if($facilitiesPage > 1): ?>
                <a href="admin_dashboard.php?page=facilities&facilities_page=<?= $facilitiesPage-1 ?>"><i class="fas fa-chevron-left"></i> Prev</a>
            <?php endif; ?>
            <?php for($i=1;$i<=$totalFacilitiesPages;$i++): ?>
                <?php if($i==$facilitiesPage): ?>
                    <strong><?= $i ?></strong>
                <?php else: ?>
                    <a href="admin_dashboard.php?page=facilities&facilities_page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if($facilitiesPage < $totalFacilitiesPages): ?>
                <a href="admin_dashboard.php?page=facilities&facilities_page=<?= $facilitiesPage+1 ?>">Next <i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<div id="addFacilityModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:700px;">
        <span class="close" onclick="closeAddFacilityModal()">&times;</span>
        <div style="display:flex;gap:20px;align-items:flex-start;">
            <div>
                <div class="facility-image-preview" id="addFacilityImagePreview">
                    <span class="placeholder">🏛️</span>
                </div>
            </div>
            <div style="flex:1;">
                <h3 style="margin-top:0;color:#f57c00;">Add New Facility</h3>
                <form method="POST" enctype="multipart/form-data" action="admin_dashboard.php?page=facilities">
                    <input type="hidden" name="add_facility_modal" value="1">
                    
                    <label>Venue ID</label>
                    <input type="text" name="facility_id" id="add_fac_id" required>
                    
                    <label>Name</label>
                    <input type="text" name="facility_name" id="add_fac_name" required>
                    
                    <label>Capacity</label>
                    <input type="number" name="facility_capacity" id="add_fac_capacity" min="1" required>
                    
                    <label>Location</label>
                    <input type="text" name="facility_location" id="add_fac_location" required>
                    
                    <label>Court</label>
                    <input type="text" name="facility_court" id="add_fac_court">
                    
                    <label>Description</label>
                    <textarea name="facility_description" id="add_fac_description" style="height:80px;"></textarea>
                    
                    <label>Status</label>
                    <select name="facility_status" id="add_fac_status_select" hidden>
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                    </select>
                    <div class="custom-dropdown" id="addFacStatusDropdown">
                        <div class="cd-selected">Open</div>
                        <ul class="cd-list">
                            <li data-value="open">Open</li>
                            <li data-value="closed">Closed</li>
                        </ul>
                    </div>
                    
                    <label>Image</label>
                    <input type="file" name="facility_image" id="add_fac_image" accept="image/*" onchange="previewAddFacilityImage(event)">
                    <small style="color:#999;">Select an image for the facility</small>
                    
                    <div style="display:flex;gap:10px;justify-content:center;margin-top:20px;">
                        <button type="submit" style="padding:10px 24px;background:#f57c00;color:white;border:none;border-radius:4px;cursor:pointer;"><i class="fas fa-plus"></i> Add Facility</button>
                        <button type="button" onclick="closeAddFacilityModal()" style="padding:10px 24px;background:#6c757d;color:white;border:none;border-radius:4px;cursor:pointer;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Facility Modal -->
<div id="editFacilityModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:700px;">
        <span class="close" onclick="closeEditFacilityModal()">&times;</span>
        <div style="display:flex;gap:20px;align-items:flex-start;">
            <div>
                <div class="facility-image-preview" id="facilityImagePreview">
                    <span class="placeholder">🏛️</span>
                </div>
            </div>
            <div style="flex:1;">
                <h3 style="margin-top:0;color:#f57c00;">Edit Facility</h3>
                <form method="POST" enctype="multipart/form-data" action="admin_dashboard.php?page=facilities">
                    <input type="hidden" name="edit_facility_modal" value="1">
                    <input type="hidden" name="facility_id" id="fac_id">
                    
                    <label>Venue ID (Read-only)</label>
                    <input type="text" id="fac_venue_id" readonly style="background:#f5f5f5;cursor:not-allowed;">
                    
                    <label>Name</label>
                    <input type="text" name="facility_name" id="fac_name" placeholder="Facility name">
                    
                    <label>Capacity</label>
                    <input type="number" name="facility_capacity" id="fac_capacity" min="1" placeholder="Capacity">
                    
                    <label>Location</label>
                    <input type="text" name="facility_location" id="fac_location" placeholder="Location">
                    
                    <label>Court</label>
                    <input type="text" name="facility_court" id="fac_court" placeholder="Court">
                    
                    <label>Description</label>
                    <textarea name="facility_description" id="fac_description" style="height:80px;"></textarea>
                    
                    <label>Status</label>
                    <select name="facility_status" id="fac_status_select" hidden>
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                    </select>
                    <div class="custom-dropdown" id="facStatusDropdown">
                        <div class="cd-selected">Open</div>
                        <ul class="cd-list">
                            <li data-value="open">Open</li>
                            <li data-value="closed">Closed</li>
                        </ul>
                    </div>
                    
                    <label>Image</label>
                    <input type="file" name="facility_image" id="fac_image" accept="image/*" onchange="previewFacilityImage(event)">
                    <small style="color:#999;">Leave empty to keep current image</small>
                    
                    <div style="display:flex;gap:10px;justify-content:center;margin-top:20px;">
                        <button type="submit" style="padding:10px 24px;background:#f57c00;color:white;border:none;border-radius:4px;cursor:pointer;"><i class="fas fa-save"></i> Save Changes</button>
                        <button type="button" onclick="closeEditFacilityModal()" style="padding:10px 24px;background:#6c757d;color:white;border:none;border-radius:4px;cursor:pointer;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php elseif($page=='bookings'): ?>
<section>
    <div class="booking-table-container">
        <div class="admin-table-header header-row">
            <h2>Manage Bookings</h2>
            <!-- Filter and Search in Header Row -->
            <div class="header-filters">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="manageBookingsSearch" class="search-input" placeholder="Search bookings..." value="<?= htmlspecialchars($_GET['bookings_search'] ?? '') ?>">
                    <span class="clear-btn" id="manageClearBtn">×</span>
                </div>
                <select id="manageFilterSelect" hidden>
                    <option value="">All</option>
                    <option value="booked">Booked</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="rejected">Rejected</option>
                </select>
                <div class="custom-dropdown" id="manageFilterDropdown">
                    <div class="cd-selected">All Status</div>
                    <ul class="cd-list">
                        <li data-value="">All Status</li>
                        <li data-value="booked">Booked</li>
                        <li data-value="completed">Completed</li>
                        <li data-value="cancelled">Cancelled</li>
                        <li data-value="rejected">Rejected</li>
                    </ul>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <button class="add-btn" id="rejectSelectedBtn" onclick="rejectSelectedBookings()" style="display: none; background: #dc3545;"><i class="fas fa-times"></i> Reject Selected</button>
                <button class="add-btn" onclick="openAddBookingModal()"><i class="fas fa-plus"></i> Create Booking</button>
            </div>
        </div>
        <table class="booking-table" id="manageBookingsTable">
            <thead>
                <tr>
                    <th style="width: 50px;">
                        <input type="checkbox" id="selectAllBookings" onchange="toggleAllBookings(this)">
                    </th>
                    <th><i class="fas fa-user"></i> User</th>
                    <th><i class="fas fa-map-marker-alt"></i> Facility</th>
                    <th><i class="far fa-calendar-alt"></i> Date</th>
                    <th><i class="far fa-clock"></i> Time</th>
                    <th><i class="fas fa-info-circle"></i> Status</th>
                    <th><i class="fas fa-cog"></i> Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if($bookings && $bookings->num_rows>0): ?>
                <?php while($b = $bookings->fetch_assoc()): ?>
                <?php $icons = ['booked'=>'fa-circle-check','completed'=>'fa-flag-checkered','cancelled'=>'fa-times-circle','rejected'=>'fa-ban']; ?>
                <?php $bst = strtolower($b['status']); ?>
                <tr class="booking-row">
                    <td>
                        <?php if ($bst === 'booked'): ?>
                            <input type="checkbox" class="booking-checkbox" value="<?= htmlspecialchars($b['booking_id']) ?>" onchange="updateRejectButton()">
                        <?php else: ?>
                            <span style="color:#999;">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($b['user_name']) ?></td>
                    <td><?= htmlspecialchars($b['venue_name']) ?></td>
                    <td><?= htmlspecialchars($b['booking_date']) ?></td>
                    <td><?= htmlspecialchars($b['booking_time']) ?></td>
                    <td><span class="status <?= strtolower($b['status']) ?>"><i class="<?= $icons[strtolower($b['status'])] ?? 'fa-question-circle' ?> fas" style="margin-right:5px;"></i><?= ucfirst($b['status']) ?></span></td>
                    <td>
                        <?php if ($bst === 'booked'): ?>
                        <div class="action-btns">
                            <button class="action-btn cancel" onclick="rejectBooking(<?= htmlspecialchars($b['booking_id']) ?>)" title="Reject Booking"><i class="fas fa-times"></i></button>
                        </div>
                        <?php else: ?><span style="color:#999;font-style:italic;">-</span><?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
            <tr><td colspan="7" style="text-align: center; color: #999;">No bookings found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination for Manage Bookings -->
    <div class="pagination">
        <span style="margin-right:15px;color:#666;">Showing <?= $bookings && $bookings->num_rows > 0 ? $bookings->num_rows : 0 ?> of <?= $totalBookings ?></span>
        <?php if($totalBookingsPages > 1): ?>
            <?php 
            $bkSearchParam = isset($_GET['bookings_search']) && $_GET['bookings_search'] !== '' ? '&bookings_search=' . urlencode($_GET['bookings_search']) : '';
            $bkStatusParam = isset($_GET['bookings_status']) && $_GET['bookings_status'] !== '' ? '&bookings_status=' . urlencode($_GET['bookings_status']) : '';
            $bkParams = $bkSearchParam . $bkStatusParam;
            ?>
            <?php if($bookingsPage > 1): ?>
                <a href="admin_dashboard.php?page=bookings&bookings_page=<?= $bookingsPage-1 ?><?= $bkParams ?>"><i class="fas fa-chevron-left"></i> Prev</a>
            <?php endif; ?>
            <?php for($i=1;$i<=$totalBookingsPages;$i++): ?>
                <?php if($i==$bookingsPage): ?>
                    <strong><?= $i ?></strong>
                <?php else: ?>
                    <a href="admin_dashboard.php?page=bookings&bookings_page=<?= $i ?><?= $bkParams ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if($bookingsPage < $totalBookingsPages): ?>
                <a href="admin_dashboard.php?page=bookings&bookings_page=<?= $bookingsPage+1 ?><?= $bkParams ?>">Next <i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Create Booking Modal -->
    <div id="addBookingModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width:600px;">
            <span class="close" onclick="closeAddBookingModal()">&times;</span>
            <h3 style="margin-top:0;color:#f57c00;">Create New Booking</h3>
            <form method="POST" action="admin_dashboard.php?page=bookings" onsubmit="return validateBookingForm()">
                <input type="hidden" name="create_booking" value="1">

                <label>Select User <span style="color:red;">*</span></label>
                <div class="user-search-container" style="position: relative; width: 100%;">
                    <div class="user-search-box" style="position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999; pointer-events: none;"></i>
                        <input type="text" id="bookingUserSearch" placeholder="Search user by name or ID..." autocomplete="off" 
                               style="width:100%;padding:10px 40px 10px 40px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;"
                               onkeyup="searchUsers(this.value)" 
                               onfocus="showUserDropdown()"
                               onclick="showUserDropdown()">
                        <span id="userSearchClear" onclick="clearUserSearch()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #999; display: none; font-size: 18px; font-weight: bold;">×</span>
                    </div>
                    <div id="userDropdown" class="user-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-top: none; border-radius: 0 0 6px 6px; max-height: 250px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <!-- User list will be populated here -->
                    </div>
                    <input type="hidden" name="booking_user_id" id="bookingUserSelect" required>
                </div>

                <label style="margin-top:15px;">Select Facility <span style="color:red;">*</span></label>
                <select name="booking_venue_id" id="bookingVenueSelect" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;font-size:14px;">
                    <option value="">-- Select Facility --</option>
                    <?php
                    $allFacilities = $conn->query("SELECT venue_id, name, court FROM venues ORDER BY name ASC");
                    if($allFacilities && $allFacilities->num_rows > 0):
                        while($f = $allFacilities->fetch_assoc()):
                    ?>
                    <option value="<?= htmlspecialchars($f['venue_id']) ?>"><?= htmlspecialchars($f['name']) ?> - <?= htmlspecialchars($f['court']) ?></option>
                    <?php endwhile; endif; ?>
                </select>

                <label style="margin-top:15px;">Booking Date <span style="color:red;">*</span></label>
                <div class="date-input-display" id="bookingDateDisplay" onclick="openCalendar('booking')" style="width:100%; margin-top: 5px;">
                    <span id="bookingSelectedDateText">Choose booking date</span>
                    <span class="calendar-icon">📅</span>
                </div>
                <input type="hidden" name="booking_date" id="booking_date" required>

                <label style="margin-top:15px;">Booking Time <span style="color:red;">*</span> (Select multiple)</label>
                <div class="date-input-display" id="bookingTimeDisplay" onclick="openTimePicker('booking')" style="width:100%; margin-top: 5px;">
                    <span id="bookingSelectedTimeText">Choose booking time(s)</span>
                    <span class="calendar-icon">🕐</span>
                </div>
                <input type="hidden" name="booking_times" id="booking_times" required>

                <div style="display:flex;gap:10px;justify-content:center;margin-top:20px;">
                    <button type="submit" style="padding:10px 24px;background:#f57c00;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:500;"><i class="fas fa-plus"></i> Create Booking</button>
                    <button type="button" onclick="closeAddBookingModal()" style="padding:10px 24px;background:#6c757d;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:500;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Shared Calendar & Time Picker for Create Booking (admin) -->
    <div class="calendar-popup" id="calendarPopup">
        <div class="calendar-container">
            <div class="calendar-header">
                <h3 id="calendarMonthYear">December 2025</h3>
                <button class="calendar-close" onclick="closeCalendar()">×</button>
            </div>

            <div class="month-nav">
                <button id="prevMonth">‹</button>
                <button id="nextMonth">›</button>
            </div>

            <div class="calendar-weekdays">
                <div class="calendar-weekday">Sun</div>
                <div class="calendar-weekday">Mon</div>
                <div class="calendar-weekday">Tue</div>
                <div class="calendar-weekday">Wed</div>
                <div class="calendar-weekday">Thu</div>
                <div class="calendar-weekday">Fri</div>
                <div class="calendar-weekday">Sat</div>
            </div>

            <div class="calendar-days" id="calendarDays"></div>
        </div>
    </div>

    <div class="calendar-popup" id="timePickerPopup">
        <div class="calendar-container">
            <div class="calendar-header">
                <h3>Select Time</h3>
                <button class="calendar-close" onclick="closeTimePicker()">×</button>
            </div>

            <div class="time-picker-grid" id="timePickerGrid">
                <!-- Time slots will be populated by JavaScript -->
            </div>
        </div>
    </div>
</section>
<?php elseif($page=='announcements'): ?>
<section>
    <div class="admin-table-container">
        <div class="admin-table-header header-row">
            <h2><i class="fa-solid fa-bullhorn"></i> Manage Announcements</h2>
            <div class="header-filters">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="announcementSearch" class="search-input" placeholder="Search announcements..." value="<?= htmlspecialchars($_GET['announcements_search'] ?? '') ?>">
                    <span class="clear-btn" id="announcementClearBtn">×</span>
                </div>
            </div>
            <button class="add-btn" onclick="openAddAnnouncementModal()"><i class="fas fa-plus"></i> Add Announcement</button>
        </div>
        <table class="admin-table" id="announcementsTable">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Content</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if($announcements && $announcements->num_rows > 0): ?>
                <?php while($ann = $announcements->fetch_assoc()): ?>
                <?php 
                // Check if announcement date has passed
                $postedDate = $ann['posted_date'] ?? date('Y-m-d');
                $postedTimestamp = strtotime($postedDate);
                $todayTimestamp = strtotime(date('Y-m-d'));
                $isExpired = $postedTimestamp < $todayTimestamp;
                ?>
                <tr>
                    <td><?= htmlspecialchars($ann['title'] ?? 'N/A') ?></td>
                    <td><?= substr(htmlspecialchars($ann['message'] ?? 'N/A'), 0, 50) ?>...</td>
                    <td><?= htmlspecialchars(date('Y-m-d', strtotime($postedDate))) ?></td>
                    <td>
                        <?php if (!$isExpired): ?>
                            <button class="edit" onclick="openEditAnnouncementModal({announcement_id: '<?= htmlspecialchars($ann['announcement_id']) ?>', title: '<?= htmlspecialchars(addslashes($ann['title'])) ?>', message: '<?= htmlspecialchars(addslashes($ann['message'])) ?>', audience: '<?= htmlspecialchars($ann['audience']) ?>'})"><i class="fas fa-edit"></i></button>
                            <button class="delete" onclick="deleteAnnouncement('<?= htmlspecialchars($ann['announcement_id']) ?>', '<?= htmlspecialchars(addslashes($ann['title'])) ?>')"><i class="fas fa-trash"></i></button>
                        <?php else: ?>
                            <span style="color:#999;font-style:italic;" title="Cannot edit/delete expired announcements">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align: center; color: #999;">No announcements found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination for Manage Announcements -->
    <div class="pagination">
        <span style="margin-right:15px;color:#666;">Showing <?= $announcements && $announcements->num_rows > 0 ? $announcements->num_rows : 0 ?> of <?= $totalAnnouncements ?></span>
        <?php if($totalAnnouncementsPages > 1): ?>
            <?php 
            $annSearchParam = isset($_GET['announcements_search']) && $_GET['announcements_search'] !== '' ? '&announcements_search=' . urlencode($_GET['announcements_search']) : '';
            ?>
            <?php if($announcementsPage > 1): ?>
                <a href="admin_dashboard.php?page=announcements&announcements_page=<?= $announcementsPage-1 ?><?= $annSearchParam ?>"><i class="fas fa-chevron-left"></i> Prev</a>
            <?php endif; ?>
            <?php for($i=1;$i<=$totalAnnouncementsPages;$i++): ?>
                <?php if($i==$announcementsPage): ?>
                    <strong><?= $i ?></strong>
                <?php else: ?>
                    <a href="admin_dashboard.php?page=announcements&announcements_page=<?= $i ?><?= $annSearchParam ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if($announcementsPage < $totalAnnouncementsPages): ?>
                <a href="admin_dashboard.php?page=announcements&announcements_page=<?= $announcementsPage+1 ?><?= $annSearchParam ?>">Next <i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Add Announcement Modal -->
<div id="addAnnouncementModal" class="modal" style="display:none;">
    <div class="modal-content" style="width: 500px;">
        <span class="close" onclick="closeAddAnnouncementModal()">&times;</span>
        <h2 style="margin-bottom: 20px;"><i class="fa-solid fa-bullhorn"></i> Add New Announcement</h2>
        <form method="POST">
            <div class="form-group">
                <label for="announcement_title">Title *</label>
                <input type="text" id="announcement_title" name="announcement_title" class="form-control" required placeholder="Enter announcement title">
            </div>
            <div class="form-group">
                <label for="announcement_message">Message *</label>
                <textarea id="announcement_message" name="announcement_message" class="form-control" rows="6" required placeholder="Enter announcement message"></textarea>
            </div>
            <div class="form-group">
                <label for="announcement_audience">Audience</label>
                <select id="announcement_audience" name="announcement_audience" class="form-control">
                    <option value="all">All Users</option>
                    <option value="student">Students</option>
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="submit" name="add_announcement_modal" value="1" style="padding:10px 24px;background:#28a745;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:500;">Add Announcement</button>
                <button type="button" onclick="closeAddAnnouncementModal()" style="padding:10px 24px;background:#6c757d;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:500;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Announcement Modal -->
<div id="editAnnouncementModal" class="modal" style="display:none;">
    <div class="modal-content" style="width: 500px;">
        <span class="close" onclick="closeEditAnnouncementModal()">&times;</span>
        <h2 style="margin-bottom: 20px;"><i class="fa-solid fa-bullhorn"></i> Edit Announcement</h2>
        <form method="POST">
            <input type="hidden" id="edit_announcement_id" name="announcement_id">
            <div class="form-group">
                <label for="edit_announcement_title">Title *</label>
                <input type="text" id="edit_announcement_title" name="announcement_title" class="form-control" required placeholder="Enter announcement title">
            </div>
            <div class="form-group">
                <label for="edit_announcement_message">Message *</label>
                <textarea id="edit_announcement_message" name="announcement_message" class="form-control" rows="6" required placeholder="Enter announcement message"></textarea>
            </div>
            <div class="form-group">
                <label for="edit_announcement_audience">Audience</label>
                <select id="edit_announcement_audience" name="announcement_audience" class="form-control">
                    <option value="all">All Users</option>
                    <option value="student">Students</option>
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="submit" name="edit_announcement_modal" value="1" style="padding:10px 24px;background:#ff7b00;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:500;">Update Announcement</button>
                <button type="button" onclick="closeEditAnnouncementModal()" style="padding:10px 24px;background:#6c757d;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:500;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Announcement Modal -->
<div id="editAnnouncementModal" class="modal" style="display:none;">
    <div class="modal-content" style="width: 500px;">
        <span class="close" onclick="closeEditAnnouncementModal()">&times;</span>
        <h2 style="margin-bottom: 20px;"><i class="fa-solid fa-bullhorn"></i> Edit Announcement</h2>
        <form method="POST">
            <input type="hidden" id="edit_announcement_id" name="announcement_id">
            <div class="form-group">
                <label for="edit_announcement_title">Title *</label>
                <input type="text" id="edit_announcement_title" name="announcement_title" class="form-control" required placeholder="Enter announcement title">
            </div>
            <div class="form-group">
                <label for="edit_announcement_message">Message *</label>
                <textarea id="edit_announcement_message" name="announcement_message" class="form-control" rows="6" required placeholder="Enter announcement message"></textarea>
            </div>
            <div class="form-group">
                <label for="edit_announcement_audience">Audience</label>
                <select id="edit_announcement_audience" name="announcement_audience" class="form-control">
                    <option value="all">All Users</option>
                    <option value="student">Students</option>
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="submit" name="edit_announcement_modal" value="1" style="padding:10px 24px;background:#ff7b00;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:500;">Update Announcement</button>
                <button type="button" onclick="closeEditAnnouncementModal()" style="padding:10px 24px;background:#6c757d;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:500;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php elseif($page=='events'): ?>
<section>
    <div class="admin-table-container">
        <div class="admin-table-header header-row">
            <h2><i class="fa-solid fa-calendar-days"></i> Manage Events</h2>
            <div class="header-filters">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="eventSearch" class="search-input" placeholder="Search events..." value="<?= htmlspecialchars($_GET['events_search'] ?? '') ?>">
                    <span class="clear-btn" id="eventClearBtn">×</span>
                </div>
            </div>
            <button class="add-btn" onclick="openAddEventModal()"><i class="fas fa-plus"></i> Add Event</button>
        </div>
        <table class="admin-table" id="eventsTable">
            <thead>
                <tr>
                    <th>Event Name</th>
                    <th>Facility</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if($events && $events->num_rows > 0): ?>
                <?php while($evt = $events->fetch_assoc()): ?>
                <?php 
                // Check if event start date and time have passed
                $startDate = $evt['start_date'] ?? date('Y-m-d');
                $startTime = $evt['start_time'] ?? '00:00:00';
                // Combine start_date and start_time to create datetime
                $startDateTime = $startDate . ' ' . $startTime;
                $startTimestamp = strtotime($startDateTime);
                $currentTimestamp = time();
                $isExpired = $startTimestamp < $currentTimestamp;
                ?>
                <tr>
                    <td><?= htmlspecialchars($evt['name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($evt['venue_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($evt['start_date'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($evt['end_date'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars(($evt['start_time'] ?? 'N/A') . ' - ' . ($evt['end_time'] ?? 'N/A')) ?></td>
                    <td>
                        <?php if (!$isExpired): ?>
                            <button class="edit" onclick="openEditEventModal({event_id: <?= (int)$evt['event_id'] ?>, name: '<?= htmlspecialchars(addslashes($evt['name'])) ?>', venue_id: '<?= htmlspecialchars($evt['venue_id']) ?>', start_date: '<?= htmlspecialchars($evt['start_date']) ?>', end_date: '<?= htmlspecialchars($evt['end_date']) ?>', start_time: '<?= htmlspecialchars($evt['start_time']) ?>', end_time: '<?= htmlspecialchars($evt['end_time']) ?>'})"><i class="fas fa-edit"></i></button>
                            <button class="delete" onclick="deleteEvent(<?= (int)$evt['event_id'] ?>, '<?= htmlspecialchars(addslashes($evt['name'])) ?>')"><i class="fas fa-trash"></i></button>
                        <?php else: ?>
                            <span style="color:#999;font-style:italic;" title="Cannot edit/delete events that have already started">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align: center; color: #999;">No events found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination for Manage Events -->
    <div class="pagination">
        <span style="margin-right:15px;color:#666;">Showing <?= $events && $events->num_rows > 0 ? $events->num_rows : 0 ?> of <?= $totalEvents ?></span>
        <?php if($totalEventsPages > 1): ?>
            <?php 
            $evtSearchParam = isset($_GET['events_search']) && $_GET['events_search'] !== '' ? '&events_search=' . urlencode($_GET['events_search']) : '';
            ?>
            <?php if($eventsPage > 1): ?>
                <a href="admin_dashboard.php?page=events&events_page=<?= $eventsPage-1 ?><?= $evtSearchParam ?>"><i class="fas fa-chevron-left"></i> Prev</a>
            <?php endif; ?>
            <?php for($i=1;$i<=$totalEventsPages;$i++): ?>
                <?php if($i==$eventsPage): ?>
                    <strong><?= $i ?></strong>
                <?php else: ?>
                    <a href="admin_dashboard.php?page=events&events_page=<?= $i ?><?= $evtSearchParam ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if($eventsPage < $totalEventsPages): ?>
                <a href="admin_dashboard.php?page=events&events_page=<?= $eventsPage+1 ?><?= $evtSearchParam ?>">Next <i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Add Event Modal -->
<div id="addEventModal" class="modal" style="display:none;">
    <div class="modal-content" style="width: 500px;">
        <span class="close" onclick="closeAddEventModal()">&times;</span>
        <h2 style="margin-bottom: 20px;"><i class="fa-solid fa-calendar-days"></i> Add New Event</h2>
        <form method="POST">
            <div class="form-group">
                <label for="event_name">Event Name *</label>
                <input type="text" id="event_name" name="event_name" class="form-control" required placeholder="Enter event name">
            </div>
            <div class="form-group">
                <label for="event_venue_id">Venue *</label>
                <select id="event_venue_id" name="event_venue_id" class="form-control" required>
                    <option value="">-- Select Venue --</option>
                    <?php
                    // Show all available venues with court information
                    $venuesResult = $conn->query("
                        SELECT venue_id, name, court 
                        FROM venues 
                        WHERE status = 'open' OR status = 'active'
                        ORDER BY name, court
                    ");
                    if($venuesResult && $venuesResult->num_rows > 0):
                        while($venue = $venuesResult->fetch_assoc()):
                            $displayText = $venue['name'];
                            if(!empty($venue['court'])) {
                                $displayText .= " - " . htmlspecialchars($venue['court']);
                            }
                    ?>
                    <option value="<?= htmlspecialchars($venue['venue_id']) ?>"><?= htmlspecialchars($displayText) ?></option>
                    <?php
                        endwhile;
                    endif;
                    ?>
                </select>
            </div>
            <div class="form-row" style="display: flex; gap: 10px;">
                <div class="form-group" style="flex: 1;">
                    <label for="event_start_date">Start Date *</label>
                    <div class="date-input-display" id="startDateDisplay" onclick="openCalendar('start')" style="width: 100%; margin-top: 5px;">
                        <span id="startSelectedDateText">Choose start date</span>
                        <span class="calendar-icon">📅</span>
                    </div>
                    <input type="hidden" id="event_start_date" name="event_start_date" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="event_end_date">End Date *</label>
                    <div class="date-input-display" id="endDateDisplay" onclick="openCalendar('end')" style="width: 100%; margin-top: 5px;">
                        <span id="endSelectedDateText">Choose end date</span>
                        <span class="calendar-icon">📅</span>
                    </div>
                    <input type="hidden" id="event_end_date" name="event_end_date" required>
                </div>
            </div>
            <div class="form-row" style="display: flex; gap: 10px;">
                <div class="form-group" style="flex: 1;">
                    <label for="event_start_time">Start Time *</label>
                    <div class="date-input-display" id="startTimeDisplay" onclick="openTimePicker('start')" style="width: 100%; margin-top: 5px;">
                        <span id="startSelectedTimeText">Choose start time</span>
                        <span class="calendar-icon">🕐</span>
                    </div>
                    <input type="hidden" id="event_start_time" name="event_start_time" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="event_end_time">End Time *</label>
                    <div class="date-input-display" id="endTimeDisplay" onclick="openTimePicker('end')" style="width: 100%; margin-top: 5px;">
                        <span id="endSelectedTimeText">Choose end time</span>
                        <span class="calendar-icon">🕐</span>
                    </div>
                    <input type="hidden" id="event_end_time" name="event_end_time" required>
                </div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="submit" name="add_event_modal" value="1" style="padding:10px 24px;background:#28a745;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:500;">Add Event</button>
                <button type="button" onclick="closeAddEventModal()" style="padding:10px 24px;background:#6c757d;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:500;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Event Modal -->
<div id="editEventModal" class="modal" style="display:none;">
    <div class="modal-content" style="width: 500px;">
        <span class="close" onclick="closeEditEventModal()">&times;</span>
        <h2 style="margin-bottom: 20px;"><i class="fa-solid fa-calendar-days"></i> Edit Event</h2>
        <form method="POST">
            <input type="hidden" id="edit_event_id" name="event_id">
            <div class="form-group">
                <label for="edit_event_name">Event Name *</label>
                <input type="text" id="edit_event_name" name="event_name" class="form-control" required placeholder="Enter event name">
            </div>
            <div class="form-group">
                <label for="edit_event_venue_id">Venue *</label>
                <select id="edit_event_venue_id" name="event_venue_id" class="form-control" required>
                    <option value="">-- Select Venue --</option>
                    <?php 
                    // Get current event venue
                    $currentVenueId = '';
                    if(isset($_GET['edit_event'])) {
                        $editEventId = $_GET['edit_event'];
                        $eventQuery = $conn->prepare("SELECT e.venue_id FROM events e WHERE e.event_id = ?");
                        $eventQuery->bind_param("i", $editEventId);
                        $eventQuery->execute();
                        $eventResult = $eventQuery->get_result();
                        if($eventRow = $eventResult->fetch_assoc()) {
                            $currentVenueId = $eventRow['venue_id'];
                        }
                        $eventQuery->close();
                    }
                    
                    // Show all available venues with court information
                    $venuesResult = $conn->query("
                        SELECT venue_id, name, court 
                        FROM venues 
                        WHERE status = 'open' OR status = 'active'
                        ORDER BY name, court
                    ");
                    if($venuesResult && $venuesResult->num_rows > 0):
                        while($venue = $venuesResult->fetch_assoc()):
                            $selected = ($currentVenueId == $venue['venue_id']) ? 'selected' : '';
                            $displayText = $venue['name'];
                            if(!empty($venue['court'])) {
                                $displayText .= ' - Court ' . htmlspecialchars($venue['court']);
                            }
                    ?>
                    <option value="<?= htmlspecialchars($venue['venue_id']) ?>" <?= $selected ?>><?= htmlspecialchars($displayText) ?></option>
                    <?php 
                        endwhile;
                    endif;
                    ?>
                </select>
            </div>
            <div class="form-row" style="display: flex; gap: 10px;">
                <div class="form-group" style="flex: 1;">
                    <label for="edit_event_start_date">Start Date *</label>
                    <div class="date-input-display" id="editStartDateDisplay" onclick="openCalendar('editStart')" style="width: 100%; margin-top: 5px;">
                        <span id="editStartSelectedDateText">Choose start date</span>
                        <span class="calendar-icon">📅</span>
                    </div>
                    <input type="hidden" id="edit_event_start_date" name="event_start_date" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="edit_event_end_date">End Date *</label>
                    <div class="date-input-display" id="editEndDateDisplay" onclick="openCalendar('editEnd')" style="width: 100%; margin-top: 5px;">
                        <span id="editEndSelectedDateText">Choose end date</span>
                        <span class="calendar-icon">📅</span>
                    </div>
                    <input type="hidden" id="edit_event_end_date" name="event_end_date" required>
                </div>
            </div>
            <div class="form-row" style="display: flex; gap: 10px;">
                <div class="form-group" style="flex: 1;">
                    <label for="edit_event_start_time">Start Time *</label>
                    <div class="date-input-display" id="editStartTimeDisplay" onclick="openTimePicker('editStart')" style="width: 100%; margin-top: 5px;">
                        <span id="editStartSelectedTimeText">Choose start time</span>
                        <span class="calendar-icon">🕐</span>
                    </div>
                    <input type="hidden" id="edit_event_start_time" name="event_start_time" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="edit_event_end_time">End Time *</label>
                    <div class="date-input-display" id="editEndTimeDisplay" onclick="openTimePicker('editEnd')" style="width: 100%; margin-top: 5px;">
                        <span id="editEndSelectedTimeText">Choose end time</span>
                        <span class="calendar-icon">🕐</span>
                    </div>
                    <input type="hidden" id="edit_event_end_time" name="event_end_time" required>
                </div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="submit" name="edit_event_modal" value="1" style="padding:10px 24px;background:#ff7b00;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:500;">Update Event</button>
                <button type="button" onclick="closeEditEventModal()" style="padding:10px 24px;background:#6c757d;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:500;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Calendar Popup -->
<div class="calendar-popup" id="calendarPopup">
    <div class="calendar-container">
        <div class="calendar-header">
            <h3 id="calendarMonthYear">December 2025</h3>
            <button class="calendar-close" onclick="closeCalendar()">×</button>
        </div>

        <div class="month-nav">
            <button id="prevMonth">‹</button>
            <button id="nextMonth">›</button>
        </div>

        <div class="calendar-weekdays">
            <div class="calendar-weekday">Sun</div>
            <div class="calendar-weekday">Mon</div>
            <div class="calendar-weekday">Tue</div>
            <div class="calendar-weekday">Wed</div>
            <div class="calendar-weekday">Thu</div>
            <div class="calendar-weekday">Fri</div>
            <div class="calendar-weekday">Sat</div>
        </div>

        <div class="calendar-days" id="calendarDays"></div>
    </div>
</div>

<!-- Time Picker Popup -->
<div class="calendar-popup" id="timePickerPopup">
    <div class="calendar-container">
        <div class="calendar-header">
            <h3>Select Time</h3>
            <button class="calendar-close" onclick="closeTimePicker()">×</button>
        </div>

        <div class="time-picker-grid" id="timePickerGrid">
            <!-- Time slots will be populated by JavaScript -->
        </div>
    </div>
</div>

<?php elseif($page=='feedback'): ?>
<section>
    <div class="admin-table-container">
        <div class="admin-table-header header-row">
            <h2><i class="fa-solid fa-comments"></i> Feedback Management</h2>
            <div class="header-filters">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="feedbackSearch" class="search-input" placeholder="Search feedback..." value="<?= htmlspecialchars($_GET['feedbacks_search'] ?? '') ?>">
                    <span class="clear-btn" id="feedbackClearBtn">×</span>
                </div>
                <select id="feedbackStatusFilter" hidden>
                    <option value="">All Status</option>
                    <option value="pending" <?= (isset($_GET['feedbacks_status']) && $_GET['feedbacks_status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="reviewed" <?= (isset($_GET['feedbacks_status']) && $_GET['feedbacks_status'] === 'reviewed') ? 'selected' : '' ?>>Reviewed</option>
                    <option value="resolved" <?= (isset($_GET['feedbacks_status']) && $_GET['feedbacks_status'] === 'resolved') ? 'selected' : '' ?>>Resolved</option>
                </select>
                <div class="custom-dropdown" id="feedbackStatusDropdown">
                    <div class="cd-selected"><?= isset($_GET['feedbacks_status']) && $_GET['feedbacks_status'] !== '' ? ucfirst($_GET['feedbacks_status']) : 'All Status' ?></div>
                    <ul class="cd-list">
                        <li data-value="">All Status</li>
                        <li data-value="pending">Pending</li>
                        <li data-value="reviewed">Reviewed</li>
                        <li data-value="resolved">Resolved</li>
                    </ul>
                </div>
            </div>
        </div>
        <table class="admin-table" id="feedbackTable">
            <thead>
                <tr>
                    <th>From User</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if($feedbacks && $feedbacks->num_rows > 0): ?>
                <?php while($fb = $feedbacks->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($fb['user_name'] ?? 'Anonymous') ?></td>
                    <td><?= htmlspecialchars($fb['subject'] ?? 'N/A') ?></td>
                    <td><?= substr(htmlspecialchars($fb['message'] ?? 'N/A'), 0, 50) ?>...</td>
                    <td><?= htmlspecialchars(date('Y-m-d', strtotime($fb['submitted_at'] ?? date('Y-m-d')))) ?></td>
                      <?php $st = strtolower($fb['status'] ?? '');
                          $badgeClass = 'status-pending';
                          if ($st === 'reviewed') $badgeClass = 'status-reviewed';
                          elseif ($st === 'resolved') $badgeClass = 'status-resolved'; ?>
                      <td><span class="feedback-status <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($fb['status']) ?? 'New') ?></span></td>
                    <td>
                        <button class="view" type="button" data-feedback-id="<?= (int)$fb['feedback_id'] ?>" data-subject="<?= htmlspecialchars($fb['subject'] ?? 'N/A') ?>" data-message="<?= htmlspecialchars($fb['message'] ?? 'N/A') ?>" data-respond="<?= htmlspecialchars($fb['respond'] ?? '') ?>" onclick="openReplyFeedbackModalFromButton(this)" style="margin-right: 5px;"><i class="fas fa-reply"></i></button>
                        <?php if($fb['status'] !== 'resolved'): ?>
                            <button class="resolve" type="button" data-feedback-id="<?= (int)$fb['feedback_id'] ?>" onclick="resolveFeedbackFromButton(this)" style="margin: 0 5px; padding: 6px 12px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;"><i class="fas fa-check"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align: center; color: #999;">No feedback found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination for Manage Feedback -->
    <div class="pagination">
        <span style="margin-right:15px;color:#666;">Showing <?= $feedbacks && $feedbacks->num_rows > 0 ? $feedbacks->num_rows : 0 ?> of <?= $totalFeedbacks ?></span>
        <?php if($totalFeedbacksPages > 1): ?>
            <?php 
            $fbSearchParam = isset($_GET['feedbacks_search']) && $_GET['feedbacks_search'] !== '' ? '&feedbacks_search=' . urlencode($_GET['feedbacks_search']) : '';
            $fbStatusParam = isset($_GET['feedbacks_status']) && $_GET['feedbacks_status'] !== '' ? '&feedbacks_status=' . urlencode($_GET['feedbacks_status']) : '';
            $fbParams = $fbSearchParam . $fbStatusParam;
            ?>
            <?php if($feedbacksPage > 1): ?>
                <a href="admin_dashboard.php?page=feedback&feedbacks_page=<?= $feedbacksPage-1 ?><?= $fbParams ?>"><i class="fas fa-chevron-left"></i> Prev</a>
            <?php endif; ?>
            <?php for($i=1;$i<=$totalFeedbacksPages;$i++): ?>
                <?php if($i==$feedbacksPage): ?>
                    <strong><?= $i ?></strong>
                <?php else: ?>
                    <a href="admin_dashboard.php?page=feedback&feedbacks_page=<?= $i ?><?= $fbParams ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if($feedbacksPage < $totalFeedbacksPages): ?>
                <a href="admin_dashboard.php?page=feedback&feedbacks_page=<?= $feedbacksPage+1 ?><?= $fbParams ?>">Next <i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Reply to Feedback Modal -->
<div id="replyFeedbackModal" class="modal" style="display:none;">
    <div class="modal-content" style="width: 600px; max-height: 80vh; overflow-y: auto;">
        <span class="close" onclick="closeReplyFeedbackModal()">&times;</span>
        <h2 style="margin-bottom: 20px;"><i class="fas fa-reply"></i> Reply to Feedback</h2>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <div style="margin-bottom: 10px;">
                <strong>Subject:</strong>
                <p id="replySubject" style="margin: 5px 0; color: #333;"></p>
            </div>
            <div>
                <strong>Message:</strong>
                <p id="replyMessage" style="margin: 5px 0; color: #333; white-space: pre-wrap;"></p>
            </div>
            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;">
                <strong>Previous Response:</strong>
                <p id="previousResponse" style="margin: 5px 0; color: #666; font-style: italic;"></p>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" id="reply_feedback_id" name="feedback_id">
            <div class="form-group">
                <label for="feedbackResponse">Your Response</label>
                <textarea id="feedbackResponse" name="feedback_response" class="form-control" rows="6" required placeholder="Enter your response to the feedback..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Arial, sans-serif;"></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="submit" name="reply_feedback_modal" value="1" style="padding:10px 24px;background:#ff7b00;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:500;">Send Reply</button>
                <button type="button" onclick="closeReplyFeedbackModal()" style="padding:10px 24px;background:#6c757d;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:500;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php elseif($page=='reports'): ?>
<section>
    <div class="admin-table-container" style="background: #f8f9fa; padding: 0;">
        
        <!-- Hero Section -->
        <div style="background: linear-gradient(135deg, #ff8c42 0%, #ff6b35 100%); padding: 60px 40px; border-radius: 0; color: white; margin-bottom: 50px;">
            <h1 style="margin: 0 0 15px 0; font-size: 3em; font-weight: 700;">Dashboard Analytics</h1>
            <p style="margin: 0; font-size: 1.2em; opacity: 0.95;">Real-time metrics and insights of your facility operations</p>
        </div>

        <div style="padding: 0 40px;">
            <!-- Analytics & Statistics Section -->
            <div style="margin-bottom: 80px;">
                <h2 style="margin-bottom: 40px; color: #ff6b35; font-size: 2.2em; font-weight: 700; padding-bottom: 15px; border-bottom: 4px solid #ff8c42;">
                    <i class="fa-solid fa-gauge"></i> Key Metrics
                </h2>
                <div class="admin-stats" style="margin-bottom: 0; gap: 25px;">
                    
                    <!-- Total Active Users Card -->
                    <div class="admin-stat-card" style="background: white; padding: 40px; border-radius: 16px; box-shadow: 0 6px 20px rgba(255, 107, 53, 0.1); border: none; border-top: 6px solid #ff8c42; transition: all 0.3s ease; min-height: 200px; display: flex; flex-direction: column; justify-content: space-between;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div style="flex: 1;">
                                <h3 style="margin: 0 0 15px 0; font-size: 1.05em; color: #666; font-weight: 600;">Total Active Users</h3>
                                <span class="count" style="font-size: 3.2em; font-weight: 800; color: #ff6b35;"><?= (int)$statsTotalActiveUsers ?></span>
                            </div>
                            <i class="fa-solid fa-users" style="font-size: 3.5em; color: #ffe5d0; opacity: 1;"></i>
                        </div>
                        <small style="color: #999; font-size: 1.05em;">Active</small>
                    </div>

                    <!-- Total Bookings Card -->
                    <div class="admin-stat-card" style="background: white; padding: 40px; border-radius: 16px; box-shadow: 0 6px 20px rgba(255, 107, 53, 0.1); border: none; border-top: 6px solid #ff8c42; transition: all 0.3s ease; min-height: 200px; display: flex; flex-direction: column; justify-content: space-between;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div style="flex: 1;">
                                <h3 style="margin: 0 0 15px 0; font-size: 1.05em; color: #666; font-weight: 600;">Total Bookings</h3>
                                <span class="count" style="font-size: 3.2em; font-weight: 800; color: #ff6b35;"><?= (int)$statsTotalBookingsCount ?></span>
                            </div>
                            <i class="fa-solid fa-calendar-check" style="font-size: 3.5em; color: #ffe5d0; opacity: 1;"></i>
                        </div>
                        <small style="color: #999; font-size: 1.05em;">All time</small>
                    </div>

                    <!-- Facilities Available Card -->
                    <div class="admin-stat-card" style="background: white; padding: 40px; border-radius: 16px; box-shadow: 0 6px 20px rgba(255, 107, 53, 0.1); border: none; border-top: 6px solid #ff8c42; transition: all 0.3s ease; min-height: 200px; display: flex; flex-direction: column; justify-content: space-between;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div style="flex: 1;">
                                <h3 style="margin: 0 0 15px 0; font-size: 1.05em; color: #666; font-weight: 600;">Active Bookings</h3>
                                <span class="count" style="font-size: 3.2em; font-weight: 800; color: #ff6b35;"><?= (int)$pendingBookings ?></span>
                            </div>
                            <i class="fa-solid fa-building" style="font-size: 3.5em; color: #ffe5d0; opacity: 1;"></i>
                        </div>
                        <small style="color: #999; font-size: 1.05em;">Active</small>
                    </div>

                    <!-- Total Feedbacks Card -->
                    <div class="admin-stat-card" style="background: white; padding: 40px; border-radius: 16px; box-shadow: 0 6px 20px rgba(255, 107, 53, 0.1); border: none; border-top: 6px solid #ff8c42; transition: all 0.3s ease; min-height: 200px; display: flex; flex-direction: column; justify-content: space-between;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div style="flex: 1;">
                                <h3 style="margin: 0 0 15px 0; font-size: 1.05em; color: #666; font-weight: 600;">Total Feedbacks</h3>
                                <span class="count" style="font-size: 3.2em; font-weight: 800; color: #ff6b35;"><?= (int)$statsTotalFeedbackCount ?></span>
                            </div>
                            <i class="fa-solid fa-comments" style="font-size: 3.5em; color: #ffe5d0; opacity: 1;"></i>
                        </div>
                        <small style="color: #999; font-size: 1.05em;">Received</small>
                    </div>
                </div>
            </div>

            <!-- Separator -->
            <div style="height: 4px; background: linear-gradient(to right, transparent, #ff8c42, transparent); margin: 80px 0; border-radius: 2px;"></div>

            <!-- Reports Generation Section -->
            <div style="margin-bottom: 80px;">
                <h2 style="margin-bottom: 40px; color: #ff6b35; font-size: 2.2em; font-weight: 700; padding-bottom: 15px; border-bottom: 4px solid #ff8c42;">
                    <i class="fa-solid fa-file-pdf"></i> Generate Reports
                </h2>
                
                <!-- Report Buttons -->
                <div style="display: grid; grid-template-columns: 1fr; gap: 40px; margin-bottom: 40px;">
                    
                    <!-- Bookings Report Card -->
                    <div style="padding: 40px; background: white; border-radius: 16px; box-shadow: 0 6px 20px rgba(255, 107, 53, 0.1); border-left: 6px solid #ff8c42;">
                        <div style="display: flex; align-items: center; margin-bottom: 30px;">
                            <div style="width: 70px; height: 70px; background: #ffe5d0; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                                <i class="fas fa-chart-bar" style="font-size: 2.5em; color: #ff6b35;"></i>
                            </div>
                            <h3 style="margin: 0; color: #ff6b35; font-size: 1.4em; font-weight: 700;">Bookings Report</h3>
                        </div>

                        <!-- Filters Section -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; padding: 25px; background: linear-gradient(135deg, #fff9f6 0%, #fffbf8 100%); border-radius: 14px; border: 1px solid #ffe5d0;">
                            <div>
                                <label style="display: block; font-weight: 700; color: #ff6b35; margin-bottom: 10px; font-size: 0.95em; text-transform: uppercase; letter-spacing: 0.5px;">Filter Type</label>
                                <select id="bookingsFilterType" style="width: 100%; padding: 12px 15px; border: 2px solid #ff8c42; border-radius: 10px; font-size: 0.95em; background: white; color: #333; font-weight: 500; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(255, 107, 53, 0.08);" onchange="toggleBookingsFilterInput()">
                                    <option value="month">By Month</option>
                                    <option value="day">By Day</option>
                                    <option value="year">By Year</option>
                                </select>
                            </div>
                            <div id="bookingsFilterInputDiv">
                                <label style="display: block; font-weight: 700; color: #ff6b35; margin-bottom: 10px; font-size: 0.95em; text-transform: uppercase; letter-spacing: 0.5px;">Select Month</label>
                                <input type="month" id="bookingsFilterInput" style="width: 100%; padding: 12px 15px; border: 2px solid #ff8c42; border-radius: 10px; font-size: 0.95em; background: white; color: #333; font-weight: 500; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(255, 107, 53, 0.08);" onchange="updateBookingsReport()">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 700; color: #ff6b35; margin-bottom: 10px; font-size: 0.95em; text-transform: uppercase; letter-spacing: 0.5px;">Sort By</label>
                                <select id="bookingsSortBy" style="width: 100%; padding: 12px 15px; border: 2px solid #ff8c42; border-radius: 10px; font-size: 0.95em; background: white; color: #333; font-weight: 500; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(255, 107, 53, 0.08);" onchange="updateBookingsReport()">
                                    <option value="most">Most Bookings</option>
                                    <option value="least">Least Bookings</option>
                                    <option value="recent">Recent First</option>
                                    <option value="oldest">Oldest First</option>
                                </select>
                            </div>
                        </div>

                        <!-- Bookings Chart -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; margin-bottom: 30px; height: 400px;">
                            <canvas id="bookingsChart"></canvas>
                        </div>

                        <!-- Bookings Table -->
                        <div style="margin-bottom: 20px;">
                            <h4 style="color: #333; font-weight: 700; margin-bottom: 15px;">Bookings by Time & Day</h4>
                            <div style="overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse; background: white;">
                                    <thead>
                                        <tr style="background: #ffe5d0; border-bottom: 2px solid #ff8c42;">
                                            <th style="padding: 12px; text-align: left; color: #ff6b35; font-weight: 700;">Day</th>
                                            <th style="padding: 12px; text-align: left; color: #ff6b35; font-weight: 700;">Time Slot</th>
                                            <th style="padding: 12px; text-align: center; color: #ff6b35; font-weight: 700;">Number of Bookings</th>
                                            <th style="padding: 12px; text-align: center; color: #ff6b35; font-weight: 700;">Status Distribution</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bookingsTableBody">
                                        <tr style="border-bottom: 1px solid #eee; background: #f8f9fa;">
                                            <td colspan="4" style="padding: 20px; text-align: center; color: #999;">Select filters to view data</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Download Button -->
                        <button style="background: linear-gradient(135deg, #ff8c42 0%, #ff6b35 100%); color: white; width: 100%; border: none; cursor: pointer; padding: 15px; font-size: 1.05em; font-weight: 600; border-radius: 8px; transition: all 0.3s ease;" onclick="downloadBookingsReport()">
                            <i class="fas fa-download"></i> Download PDF
                        </button>
                    </div>

                    <!-- Facilities Report Card -->
                    <div style="padding: 40px; background: white; border-radius: 16px; box-shadow: 0 6px 20px rgba(255, 107, 53, 0.1); border-left: 6px solid #ff8c42;">
                        <div style="display: flex; align-items: center; margin-bottom: 30px;">
                            <div style="width: 70px; height: 70px; background: #ffe5d0; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                                <i class="fas fa-chart-line" style="font-size: 2.5em; color: #ff6b35;"></i>
                            </div>
                            <h3 style="margin: 0; color: #ff6b35; font-size: 1.4em; font-weight: 700;">Facilities Report</h3>
                        </div>

                        <!-- Filters Section -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; padding: 25px; background: linear-gradient(135deg, #fff9f6 0%, #fffbf8 100%); border-radius: 14px; border: 1px solid #ffe5d0;">
                            <div>
                                <label style="display: block; font-weight: 700; color: #ff6b35; margin-bottom: 10px; font-size: 0.95em; text-transform: uppercase; letter-spacing: 0.5px;">Filter Type</label>
                                <select id="facilitiesFilterType" style="width: 100%; padding: 12px 15px; border: 2px solid #ff8c42; border-radius: 10px; font-size: 0.95em; background: white; color: #333; font-weight: 500; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(255, 107, 53, 0.08);" onchange="toggleFacilitiesFilterInput()">
                                    <option value="month">By Month</option>
                                    <option value="day">By Day</option>
                                    <option value="year">By Year</option>
                                </select>
                            </div>
                            <div id="facilitiesFilterInputDiv">
                                <label style="display: block; font-weight: 700; color: #ff6b35; margin-bottom: 10px; font-size: 0.95em; text-transform: uppercase; letter-spacing: 0.5px;">Select Month</label>
                                <input type="month" id="facilitiesFilterInput" style="width: 100%; padding: 12px 15px; border: 2px solid #ff8c42; border-radius: 10px; font-size: 0.95em; background: white; color: #333; font-weight: 500; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(255, 107, 53, 0.08);" onchange="updateFacilitiesReport()">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 700; color: #ff6b35; margin-bottom: 10px; font-size: 0.95em; text-transform: uppercase; letter-spacing: 0.5px;">Sort By</label>
                                <select id="facilitiesSortBy" style="width: 100%; padding: 12px 15px; border: 2px solid #ff8c42; border-radius: 10px; font-size: 0.95em; background: white; color: #333; font-weight: 500; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(255, 107, 53, 0.08);" onchange="updateFacilitiesReport()">
                                    <option value="most">Most Booked</option>
                                    <option value="least">Least Booked</option>
                                </select>
                            </div>
                        </div>

                        <!-- Facilities Chart -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; margin-bottom: 30px; height: 400px;">
                            <canvas id="facilitiesChart"></canvas>
                        </div>

                        <!-- Facilities Table -->
                        <div style="margin-bottom: 20px;">
                            <h4 style="color: #333; font-weight: 700; margin-bottom: 15px;">Facility Usage by Time & Day</h4>
                            <div style="overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse; background: white;">
                                    <thead>
                                        <tr style="background: #ffe5d0; border-bottom: 2px solid #ff8c42;">
                                            <th style="padding: 12px; text-align: left; color: #ff6b35; font-weight: 700;">Facility Name</th>
                                            <th style="padding: 12px; text-align: left; color: #ff6b35; font-weight: 700;">Day</th>
                                            <th style="padding: 12px; text-align: left; color: #ff6b35; font-weight: 700;">Peak Time</th>
                                            <th style="padding: 12px; text-align: center; color: #ff6b35; font-weight: 700;">Total Bookings</th>
                                        </tr>
                                    </thead>
                                    <tbody id="facilitiesTableBody">
                                        <tr style="border-bottom: 1px solid #eee; background: #f8f9fa;">
                                            <td colspan="4" style="padding: 20px; text-align: center; color: #999;">Select filters to view data</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Download Button -->
                        <button style="background: linear-gradient(135deg, #ff8c42 0%, #ff6b35 100%); color: white; width: 100%; border: none; cursor: pointer; padding: 15px; font-size: 1.05em; font-weight: 600; border-radius: 8px; transition: all 0.3s ease;" onclick="downloadFacilitiesReport()">
                            <i class="fas fa-download"></i> Download PDF
                        </button>
                    </div>
                </div>

                <!-- Info Box -->
                <div style="padding: 40px; background: white; border-radius: 16px; border-left: 6px solid #ff8c42; box-shadow: 0 6px 20px rgba(255, 107, 53, 0.1);">
                    <div style="display: flex; align-items: center; margin-bottom: 20px;">
                        <div style="width: 60px; height: 60px; background: #ffe5d0; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                            <i class="fas fa-info-circle" style="font-size: 1.8em; color: #ff6b35;"></i>
                        </div>
                        <h3 style="margin: 0; color: #ff6b35; font-size: 1.3em; font-weight: 700;">Report Information</h3>
                    </div>
                    <p style="color: #666; margin: 0 0 20px 0; font-size: 1.05em; line-height: 1.6;">
                        All reports are generated in real-time with the latest data from your system. Choose a report type above to download data in your preferred format.
                    </p>
                    <ul style="margin: 0; padding-left: 30px; color: #666; font-size: 1.05em;">
                        <li style="margin: 12px 0;"><strong>Available Formats:</strong> PDF, CSV, Excel</li>
                        <li style="margin: 12px 0;"><strong>Data Updated:</strong> Real-time from database</li>
                        <li style="margin: 12px 0;"><strong>Export:</strong> Ready for analysis and sharing</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Footer Spacing -->
        <div style="height: 40px;"></div>
    </div>
</section>
</section>
<?php endif; ?>

        </div>
    </div>
</div>

<script>
// Setup dropdown function (global for use anywhere)
function setupDropdown(id, callback) {
        const box = document.getElementById(id);
        if (!box) return;
        
        const sel = box.querySelector('.cd-selected');
        const list = box.querySelector('.cd-list');
        
        if (!sel || !list) return;
        
        sel.onclick = (e) => {
            e.stopPropagation();
            list.style.display = list.style.display === 'block' ? 'none' : 'block';
        };
        
        box.querySelectorAll('li').forEach(li => {
            li.onclick = (e) => {
                e.stopPropagation();
                sel.textContent = li.textContent;
                list.style.display = 'none';
                if (callback) callback(li.dataset.value);
            };
        });
        
        document.addEventListener('click', e => {
            if (!box.contains(e.target)) list.style.display = 'none';
        });
}

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    
    // Filter table function
    function filterTable() {
        const searchInput = document.getElementById('manageBookingsSearch');
        const filterSelect = document.getElementById('manageFilterSelect');
        const table = document.getElementById('manageBookingsTable');
        
        if (!searchInput || !filterSelect || !table) return;
        
        const query = searchInput.value.toLowerCase().trim();
        const status = filterSelect.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        let visibleCount = 0;
        
        rows.forEach(tr => {
            const cells = tr.getElementsByTagName('td');
            
            // Skip empty or "no results" rows
            if (cells.length < 7) {
                return;
            }
            
            let matchSearch = true;
            let matchStatus = true;
            
            // Search filter - check all columns except first (checkbox) and last (Action)
            if (query) {
                matchSearch = false;
                for (let i = 1; i < cells.length - 1; i++) {
                    if (cells[i].textContent.toLowerCase().includes(query)) {
                        matchSearch = true;
                        break;
                    }
                }
            }
            
            // Status filter - check status column (index 5, after checkbox column)
            if (status && cells[5]) {
                const cellStatus = (cells[5].textContent || '').toLowerCase();
                matchStatus = cellStatus.includes(status);
            }
            
            // Show/hide row
            const shouldShow = matchSearch && matchStatus;
            tr.style.display = shouldShow ? '' : 'none';
            
            if (shouldShow) visibleCount++;
        });
        
        console.log(`Filtered: ${visibleCount} visible rows`);
    }
    
    // Initialize search functionality
    const searchInput = document.getElementById('manageBookingsSearch');
    const clearBtn = document.getElementById('manageClearBtn');
    
    if (searchInput && clearBtn) {
        // Set initial clear button state
        clearBtn.style.display = searchInput.value ? 'block' : 'none';
        
        // Live client-side filtering while typing
        searchInput.addEventListener('input', function() {
            clearBtn.style.display = this.value ? 'block' : 'none';
            filterTable();
        });

        // Press Enter to perform a global server-side search (navigates)
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const q = this.value.trim();
                const url = new URL(window.location.href);
                url.searchParams.set('page', 'bookings');
                if (q) url.searchParams.set('bookings_search', q); else url.searchParams.delete('bookings_search');
                url.searchParams.set('bookings_page', '1');
                window.location = url.toString();
            }
        });
        
        // Clear button event (client-side)
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            searchInput.value = '';
            clearBtn.style.display = 'none';
            filterTable();
        });
    }
    
    // Initialize dropdown
    setupDropdown('manageFilterDropdown', function(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('page', 'bookings');
        if (value) url.searchParams.set('bookings_status', value); else url.searchParams.delete('bookings_status');
        url.searchParams.set('bookings_page', '1');
        window.location = url.toString();
    });

    // Initialize select all checkbox behavior
    const selectAllCheckbox = document.getElementById('selectAllBookings');
    if (selectAllCheckbox) {
        // Update select all when individual checkboxes change
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('booking-checkbox')) {
                const allCheckboxes = document.querySelectorAll('.booking-checkbox');
                const checkedCheckboxes = document.querySelectorAll('.booking-checkbox:checked');
                selectAllCheckbox.checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCheckboxes.length;
                updateRejectButton();
            }
        });
    }
    // Role custom dropdown in Add/Edit User modal
    setupDropdown('roleDropdown', function(value) {
        const roleSel = document.getElementById('role');
        if (roleSel) roleSel.value = value;
    });
    // Gender and Status dropdowns for user modal
    setupDropdown('genderDropdown', function(value) {
        const g = document.getElementById('gender'); if(g) g.value = value;
    });
    setupDropdown('statusDropdown', function(value) {
        const s = document.getElementById('status'); if(s) s.value = value;
    });
    
    // Initial filter application
    filterTable();

    // ===== USERS TABLE FILTER =====
    function filterUsersTable() {
        const searchInput = document.getElementById('usersSearch');
        const roleFilter = document.getElementById('usersFilterSelect');
        const statusFilter = document.getElementById('usersStatusFilterSelect');
        const table = document.getElementById('usersTable');
        
        if (!searchInput || !roleFilter || !statusFilter || !table) return;
        
        const query = searchInput.value.toLowerCase().trim();
        const role = roleFilter.value.toLowerCase();
        const status = statusFilter.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(tr => {
            const cells = tr.getElementsByTagName('td');
            // Expect at least 6 columns for users table (without ID)
            if (cells.length < 6) return;
            
            let matchSearch = true;
            let matchRole = true;
            let matchStatus = true;
            
            // Search filter - check Name (col 1), Email (col 2)
            if (query) {
                const name = (cells[1] && cells[1].textContent || '').toLowerCase();
                const email = (cells[2] && cells[2].textContent || '').toLowerCase();
                matchSearch = name.includes(query) || email.includes(query);
            }
            
            // Role filter - check role column (index 4)
            if (role && cells[4]) {
                matchRole = (cells[4].textContent || '').toLowerCase().includes(role);
            }
            
            // Status filter - check status column (index 5)
            if (status && cells[5]) {
                matchStatus = (cells[5].textContent || '').toLowerCase().includes(status);
            }
            
            const shouldShow = matchSearch && matchRole && matchStatus;
            tr.style.display = shouldShow ? '' : 'none';
        });
    }

    // ===== FACILITIES TABLE FILTER =====
    function filterFacilitiesTable() {
        const searchInput = document.getElementById('facilitiesSearch');
        const statusFilter = document.getElementById('facilitiesStatusFilterSelect');
        const table = document.getElementById('facilitiesTable');
        
        if (!searchInput || !statusFilter || !table) return;
        
        const query = searchInput.value.toLowerCase().trim();
        const status = statusFilter.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(tr => {
            const cells = tr.getElementsByTagName('td');
            // Expect at least 7 columns for facilities table (without ID)
            if (cells.length < 6) return;
            
            let matchSearch = true;
            let matchStatus = true;
            
            // Search filter - check name, location
            if (query) {
                const name = (cells[0] && cells[0].textContent || '').toLowerCase();
                const location = (cells[2] && cells[2].textContent || '').toLowerCase();
                matchSearch = name.includes(query) || location.includes(query);
            }
            
            // Status filter - check status column (index 5)
            if (status && cells[5]) {
                matchStatus = (cells[5].textContent || '').toLowerCase().includes(status);
            }
            
            const shouldShow = matchSearch && matchStatus;
            tr.style.display = shouldShow ? '' : 'none';
        });
    }

    // ===== USERS TABLE SEARCH/FILTER SETUP =====
    const usersSearchInput = document.getElementById('usersSearch');
    const usersClearBtn = document.getElementById('usersClearBtn');
    
    if (usersSearchInput && usersClearBtn) {
        usersClearBtn.style.display = usersSearchInput.value ? 'block' : 'none';
        
        usersSearchInput.addEventListener('input', function() {
            usersClearBtn.style.display = this.value ? 'block' : 'none';
            filterUsersTable();
        });

        usersSearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const q = this.value.trim();
                const url = new URL(window.location.href);
                url.searchParams.set('page', 'users');
                if (q) url.searchParams.set('users_search', q); else url.searchParams.delete('users_search');
                url.searchParams.set('users_page', '1');
                window.location = url.toString();
            }
        });

        usersClearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            usersSearchInput.value = '';
            usersClearBtn.style.display = 'none';
            filterUsersTable();
        });

        // Apply initial client-side filter
        filterUsersTable();
    }

    // ===== FACILITIES TABLE SEARCH/FILTER SETUP =====
    const facilitiesSearchInput = document.getElementById('facilitiesSearch');
    const facilitiesClearBtn = document.getElementById('facilitiesClearBtn');
    
    if (facilitiesSearchInput && facilitiesClearBtn) {
        facilitiesClearBtn.style.display = facilitiesSearchInput.value ? 'block' : 'none';
        
        facilitiesSearchInput.addEventListener('input', function() {
            facilitiesClearBtn.style.display = this.value ? 'block' : 'none';
            filterFacilitiesTable();
        });

        facilitiesSearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const q = this.value.trim();
                const url = new URL(window.location.href);
                url.searchParams.set('page', 'facilities');
                if (q) url.searchParams.set('facilities_search', q); else url.searchParams.delete('facilities_search');
                url.searchParams.set('facilities_page', '1');
                window.location = url.toString();
            }
        });

        facilitiesClearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            facilitiesSearchInput.value = '';
            facilitiesClearBtn.style.display = 'none';
            filterFacilitiesTable();
        });

        // Apply initial client-side filter
        filterFacilitiesTable();
    }

    // ===== ANNOUNCEMENTS TABLE SEARCH/FILTER SETUP =====
    const announcementSearchInput = document.getElementById('announcementSearch');
    const announcementClearBtn = document.getElementById('announcementClearBtn');
    
    if (announcementSearchInput && announcementClearBtn) {
        announcementClearBtn.style.display = announcementSearchInput.value ? 'block' : 'none';
        
        announcementSearchInput.addEventListener('input', function() {
            announcementClearBtn.style.display = this.value ? 'block' : 'none';
            filterAnnouncementsTable();
        });

        announcementSearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const q = this.value.trim();
                const url = new URL(window.location.href);
                url.searchParams.set('page', 'announcements');
                if (q) url.searchParams.set('announcements_search', q); else url.searchParams.delete('announcements_search');
                url.searchParams.set('announcements_page', '1');
                window.location = url.toString();
            }
        });

        announcementClearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            announcementSearchInput.value = '';
            announcementClearBtn.style.display = 'none';
            filterAnnouncementsTable();
        });

        // Apply initial client-side filter
        filterAnnouncementsTable();
    }

    // ===== EVENTS TABLE SEARCH/FILTER SETUP =====
    const eventSearchInput = document.getElementById('eventSearch');
    const eventClearBtn = document.getElementById('eventClearBtn');
    
    if (eventSearchInput && eventClearBtn) {
        eventClearBtn.style.display = eventSearchInput.value ? 'block' : 'none';
        
        eventSearchInput.addEventListener('input', function() {
            eventClearBtn.style.display = this.value ? 'block' : 'none';
            filterEventsTable();
        });

        eventSearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const q = this.value.trim();
                const url = new URL(window.location.href);
                url.searchParams.set('page', 'events');
                if (q) url.searchParams.set('events_search', q); else url.searchParams.delete('events_search');
                url.searchParams.set('events_page', '1');
                window.location = url.toString();
            }
        });

        eventClearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            eventSearchInput.value = '';
            eventClearBtn.style.display = 'none';
            filterEventsTable();
        });

        // Apply initial client-side filter
        filterEventsTable();
    }

    // ===== FEEDBACK TABLE SEARCH/FILTER SETUP =====
    const feedbackSearchInput = document.getElementById('feedbackSearch');
    const feedbackClearBtn = document.getElementById('feedbackClearBtn');
    
    if (feedbackSearchInput && feedbackClearBtn) {
        feedbackClearBtn.style.display = feedbackSearchInput.value ? 'block' : 'none';
        
        feedbackSearchInput.addEventListener('input', function() {
            feedbackClearBtn.style.display = this.value ? 'block' : 'none';
            filterFeedbackTable();
        });

        feedbackSearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const q = this.value.trim();
                const url = new URL(window.location.href);
                url.searchParams.set('page', 'feedback');
                if (q) url.searchParams.set('feedbacks_search', q); else url.searchParams.delete('feedbacks_search');
                url.searchParams.set('feedbacks_page', '1');
                window.location = url.toString();
            }
        });

        feedbackClearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            feedbackSearchInput.value = '';
            feedbackClearBtn.style.display = 'none';
            filterFeedbackTable();
        });

        // Apply initial client-side filter
        filterFeedbackTable();
    }

    // Setup dropdowns for users
    setupDropdown('usersFilterDropdown', function(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('page', 'users');
        if (value) url.searchParams.set('users_role', value); else url.searchParams.delete('users_role');
        url.searchParams.set('users_page', '1');
        window.location = url.toString();
    });

    setupDropdown('usersStatusFilterDropdown', function(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('page', 'users');
        if (value) url.searchParams.set('users_status', value); else url.searchParams.delete('users_status');
        url.searchParams.set('users_page', '1');
        window.location = url.toString();
    });

    // Setup dropdowns for facilities
    setupDropdown('facilitiesStatusFilterDropdown', function(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('page', 'facilities');
        if (value) url.searchParams.set('facilities_status', value); else url.searchParams.delete('facilities_status');
        url.searchParams.set('facilities_page', '1');
        window.location = url.toString();
    });

    // Setup dropdowns for feedback
    setupDropdown('feedbackStatusDropdown', function(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('page', 'feedback');
        if (value) url.searchParams.set('feedbacks_status', value); else url.searchParams.delete('feedbacks_status');
        url.searchParams.set('feedbacks_page', '1');
        window.location = url.toString();
    });

    // ===== TABLE SORTING HELPERS =====
    function parseTimeForSort(t) {
        if (!t) return 0;
        const s = String(t).trim();
        // Accept formats: 'HH:MM:SS' or 'HH:MM' or '01:00 PM' etc.
        const m12 = s.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
        if (m12) {
            let hh = parseInt(m12[1],10);
            const mm = parseInt(m12[2],10);
            const p = m12[3].toUpperCase();
            if (p === 'PM' && hh !== 12) hh += 12;
            if (p === 'AM' && hh === 12) hh = 0;
            return hh * 60 + mm;
        }
        const m24 = s.match(/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/);
        if (m24) {
            const hh = parseInt(m24[1],10);
            const mm = parseInt(m24[2],10);
            return hh * 60 + mm;
        }
        return 0;
    }

    function sortTable(tableId, colIndex, type='auto') {
        const table = document.getElementById(tableId);
        if (!table) return;
        const tbody = table.tBodies[0];
        if (!tbody) return;
        const rows = Array.from(tbody.querySelectorAll('tr'));

        // Determine type if auto
        if (type === 'auto') {
            const firstCell = rows.find(r => r.cells[colIndex]);
            const sample = firstCell ? firstCell.cells[colIndex].textContent.trim() : '';
            if (/^\d+$/.test(sample)) type = 'num';
            else if (/^\d{4}-\d{2}-\d{2}$/.test(sample)) type = 'date';
            else if (/\b(AM|PM)\b/i.test(sample) || /^\d{1,2}:\d{2}(:\d{2})?$/.test(sample)) type = 'time';
            else type = 'string';
        }

        // Toggle sort direction stored on table header cell
        const th = table.querySelectorAll('th')[colIndex];
        if (!th) return;
        const current = th.dataset.sort === 'asc' ? 'asc' : (th.dataset.sort === 'desc' ? 'desc' : null);
        const dir = current === 'asc' ? -1 : 1; // if asc then next click should be descending

        rows.sort((a, b) => {
            const A = (a.cells[colIndex] && a.cells[colIndex].textContent.trim()) || '';
            const B = (b.cells[colIndex] && b.cells[colIndex].textContent.trim()) || '';
            let res = 0;
            if (type === 'num') {
                res = (parseFloat(A.replace(/[^0-9.-]+/g, '')) || 0) - (parseFloat(B.replace(/[^0-9.-]+/g, '')) || 0);
            } else if (type === 'date') {
                res = new Date(A) - new Date(B);
            } else if (type === 'time') {
                res = parseTimeForSort(A) - parseTimeForSort(B);
            } else {
                res = A.localeCompare(B, undefined, {numeric:true, sensitivity:'base'});
            }
            return res * dir;
        });

        // Clear tbody and append sorted rows
        rows.forEach(r => tbody.appendChild(r));

        // Update th dataset
        table.querySelectorAll('th').forEach(th2 => { delete th2.dataset.sort; th2.classList.remove('sort-asc','sort-desc'); });
        th.dataset.sort = dir === 1 ? 'asc' : 'desc';
        th.classList.add(dir === 1 ? 'sort-asc' : 'sort-desc');
    }

    function attachTableSorting(tableId, columns) {
        const table = document.getElementById(tableId);
        if (!table) return;
        const ths = table.querySelectorAll('th');
        ths.forEach((th, idx) => {
            if (columns[idx]) {
                th.style.cursor = 'pointer';
                th.addEventListener('click', () => sortTable(tableId, idx, columns[idx]));
            }
        });
    }

    // Attach sorting to tables
    attachTableSorting('usersTable', {1: 'string', 3: 'string', 4: 'string', 5: 'string', 6: 'string'});
    attachTableSorting('facilitiesTable', {0: 'string', 1: 'string', 2: 'num', 3: 'string', 6: 'string'});
    attachTableSorting('manageBookingsTable', {0: 'num', 1: 'string', 2: 'string', 3: 'date', 4: 'time', 5: 'string'});



    (function(){
        const container = document.getElementById('flash-container');
        if(!container) return;
        const flashes = Array.from(container.querySelectorAll('.flash'));
        flashes.forEach(f => {
            // auto hide after 5s
            const t = setTimeout(() => {
                f.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                f.style.opacity = '0';
                f.style.transform = 'translateY(-6px)';
                setTimeout(() => { if(f.parentNode) f.parentNode.removeChild(f); }, 350);
            }, 5000);
            const close = f.querySelector('.flash-close');
            if(close) close.addEventListener('click', () => { clearTimeout(t); if(f.parentNode) f.parentNode.removeChild(f); });
        });
    })();
    
    // Avatar preview and password toggle - setup after DOM loads
    const avatarInput = document.getElementById('avatarInput');
    const togglePasswordBtn = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    
    if (avatarInput) {
        avatarInput.addEventListener('change', function() {
            const file = this.files[0];
            const avatarPreview = document.getElementById('avatarPreview');
            
            if (file) {
                const allowed = ['jpg','jpeg','png','gif'];
                const ext = file.name.split('.').pop().toLowerCase();
                if (!allowed.includes(ext)) {
                    alert('Only jpg, jpeg, png, gif formats allowed');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    if (togglePasswordBtn && passwordField) {
        togglePasswordBtn.addEventListener('click', function() {
            if (passwordField.getAttribute('type') === 'password') {
                passwordField.setAttribute('type', 'text');
                togglePasswordBtn.innerText = 'Hide';
            } else {
                passwordField.setAttribute('type', 'password');
                togglePasswordBtn.innerText = 'Show';
            }
        });
    }

    // Protect facility description fields from being overwritten to '0' unexpectedly
    (function() {
        function guardTextarea(id) {
            const el = document.getElementById(id);
            if (!el) return;
            let lastVal = el.value || '';
            // remember last value before input
            el.addEventListener('beforeinput', function() { lastVal = this.value; }, true);
            // on input, if it's been clobbered to a single '0', restore previous value
            el.addEventListener('input', function(e) {
                try {
                    if (this.value === '0' && lastVal !== '' && lastVal !== '0') {
                        this.value = lastVal;
                        if (e && e.stopImmediatePropagation) e.stopImmediatePropagation();
                    } else {
                        lastVal = this.value;
                    }
                } catch (err) {
                    // fail silently
                    console.error('guardian error', err);
                }
            }, true);
        }
        guardTextarea('fac_description');
        guardTextarea('add_fac_description');
    })();

});

// User modal functions (keep these global for onclick attributes)
function openAddUserModal() {
    const userModal = document.getElementById('userModal');
    console.log('userModal:', userModal);
    const avatarPreview = document.getElementById('avatarPreview');
    
    // Reset form
    const userForm = document.getElementById('userForm');
    if(userForm) userForm.reset();
    
    document.getElementById('modalTitle').innerText = 'Add User';
    
    // Reset user_id field
    const userIdField = document.getElementById('user_id');
    if(userIdField) {
        userIdField.readOnly = false;
        userIdField.value = '';
    }
    
    // Setup password field
    const passwordField = document.getElementById('password');
    if(passwordField) {
        passwordField.required = true;
        passwordField.value = '';
    }
    
    const passwordHint = document.getElementById('passwordHint');
    if(passwordHint) {
        passwordHint.innerText = '(Required)';
        passwordHint.style.color = '#666';
    }
    
    // Show password field for Add
    const pwdCont = document.getElementById('passwordContainer');
    const pwdLabel = document.getElementById('passwordLabel');
    if(pwdCont) pwdCont.style.display = 'block';
    if(pwdLabel) pwdLabel.style.display = 'block';
    
    // Set default values
    const roleSel = document.getElementById('role');
    if(roleSel) {
        roleSel.value = 'staff';
        // Trigger dropdown update
        const roleDropdown = document.getElementById('roleDropdown');
        if(roleDropdown) {
            const rd = roleDropdown.querySelector('.cd-selected');
            if(rd) rd.textContent = 'Staff';
        }
    }
    
    const statusSel = document.getElementById('status');
    if(statusSel) {
        statusSel.value = 'active';
        // Trigger dropdown update
        const statusDropdown = document.getElementById('statusDropdown');
        if(statusDropdown) {
            const sd = statusDropdown.querySelector('.cd-selected');
            if(sd) sd.textContent = 'Active';
        }
    }
    
    const genderSel = document.getElementById('gender');
    if(genderSel) {
        genderSel.value = '';
        // Trigger dropdown update
        const genderDropdown = document.getElementById('genderDropdown');
        if(genderDropdown) {
            const gd = genderDropdown.querySelector('.cd-selected');
            if(gd) gd.textContent = 'Select Gender';
        }
    }
    
    // Show/hide buttons safely
    const addBtn = document.getElementById('addBtn');
    const editBtn = document.getElementById('editBtn');
    if(addBtn) addBtn.style.display = 'inline-block';
    if(editBtn) editBtn.style.display = 'none';
    
    // Reset avatar preview
    if(avatarPreview) avatarPreview.innerHTML = '<span class="placeholder">+</span>';
    
    const currentAvatar = document.getElementById('currentAvatar');
    if(currentAvatar) currentAvatar.value = '';
    
    // Reset avatar file input
    const avatarInput = document.getElementById('avatarInput');
    if(avatarInput) avatarInput.value = '';
    
    console.log('Setting modal display to flex');
    if(userModal) userModal.style.display = 'flex';
    console.log('Modal display set');
}

function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
}

// Booking modal functions
function openAddBookingModal() {
    const bookingModal = document.getElementById('addBookingModal');
    if (!bookingModal) return;
    // reset calendar to current month for fresh selection
    currentCalendarDate = new Date();
    // reset form fields
    const userSel = document.getElementById('bookingUserSelect'); if(userSel) userSel.value = '';
    const userSearch = document.getElementById('bookingUserSearch'); if(userSearch) userSearch.value = '';
    const venueSel = document.getElementById('bookingVenueSelect'); if(venueSel) venueSel.value = '';
    const bookingDateHidden = document.getElementById('booking_date');
    const bookingDateDisplay = document.getElementById('bookingDateDisplay');
    const bookingDateText = document.getElementById('bookingSelectedDateText');
    if (bookingDateHidden) bookingDateHidden.value = '';
    if (bookingDateDisplay) bookingDateDisplay.classList.remove('selected');
    if (bookingDateText) bookingDateText.textContent = 'Choose booking date';

    const bookingTimeHidden = document.getElementById('booking_time');
    const bookingTimeDisplay = document.getElementById('bookingTimeDisplay');
    const bookingTimeText = document.getElementById('bookingSelectedTimeText');
    if (bookingTimeHidden) bookingTimeHidden.value = '';
    if (bookingTimeDisplay) bookingTimeDisplay.classList.remove('selected');
    if (bookingTimeText) bookingTimeText.textContent = 'Choose booking time';

    hideUserDropdown();
    bookingModal.style.display = 'flex';
}

function closeAddBookingModal() {
    const bookingModal = document.getElementById('addBookingModal');
    if (bookingModal) bookingModal.style.display = 'none';
    hideUserDropdown();
}

// Store users data for search (populated from PHP)
<?php
$allUsers = $conn->query("SELECT user_id, name FROM users ORDER BY name ASC");
?>
const allUsersData = [
    <?php
    if($allUsers && $allUsers->num_rows > 0):
        $userArray = [];
        while($u = $allUsers->fetch_assoc()):
            $userArray[] = "{id: " . json_encode($u['user_id']) . ", name: " . json_encode($u['name']) . "}";
        endwhile;
        echo implode(",\n    ", $userArray);
    endif;
    ?>
];

// User search functions
function searchUsers(query) {
    const dropdown = document.getElementById('userDropdown');
    const clearBtn = document.getElementById('userSearchClear');
    const searchInput = document.getElementById('bookingUserSearch');
    
    if (!dropdown || !searchInput) return;
    
    // Ensure allUsersData is available
    if (typeof allUsersData === 'undefined') {
        console.error('allUsersData is not defined');
        return;
    }
    
    query = query.toLowerCase().trim();
    clearBtn.style.display = query.length > 0 ? 'block' : 'none';
    
    // Filter users based on search query
    const filteredUsers = allUsersData.filter(user => {
        const nameMatch = user.name.toLowerCase().includes(query);
        const idMatch = user.id.toLowerCase().includes(query);
        return nameMatch || idMatch;
    });
    
    // Clear dropdown
    dropdown.innerHTML = '';
    
    if (filteredUsers.length === 0 && query.length > 0) {
        dropdown.innerHTML = '<div class="user-dropdown-empty">No users found</div>';
        dropdown.style.display = 'block';
        return;
    }
    
    if (filteredUsers.length === 0) {
        dropdown.style.display = 'none';
        return;
    }
    
    // Display filtered users
    filteredUsers.forEach(user => {
        const item = document.createElement('div');
        item.className = 'user-dropdown-item';
        item.innerHTML = `
            <span class="user-name">${escapeHtml(user.name)}</span>
            <span class="user-id">${escapeHtml(user.id)}</span>
        `;
        item.onclick = () => selectUser(user.id, user.name);
        dropdown.appendChild(item);
    });
    
    dropdown.style.display = 'block';
}

function selectUser(userId, userName) {
    const searchInput = document.getElementById('bookingUserSearch');
    const hiddenInput = document.getElementById('bookingUserSelect');
    const clearBtn = document.getElementById('userSearchClear');
    
    if (searchInput) {
        searchInput.value = `${userName} (${userId})`;
    }
    if (hiddenInput) {
        hiddenInput.value = userId;
    }
    if (clearBtn) {
        clearBtn.style.display = 'block';
    }
    
    hideUserDropdown();
}

function showUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    const searchInput = document.getElementById('bookingUserSearch');
    
    if (!dropdown || !searchInput) return;
    
    const query = searchInput.value.trim();
    
    if (query.length === 0) {
        // Show all users if search is empty
        const allItems = allUsersData.map(user => {
            const item = document.createElement('div');
            item.className = 'user-dropdown-item';
            item.innerHTML = `
                <span class="user-name">${escapeHtml(user.name)}</span>
                <span class="user-id">${escapeHtml(user.id)}</span>
            `;
            item.onclick = () => selectUser(user.id, user.name);
            return item;
        });
        
        dropdown.innerHTML = '';
        allItems.forEach(item => dropdown.appendChild(item));
    }
    
    if (dropdown.children.length > 0) {
        dropdown.style.display = 'block';
    }
}

function hideUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.style.display = 'none';
    }
}

function clearUserSearch() {
    const searchInput = document.getElementById('bookingUserSearch');
    const hiddenInput = document.getElementById('bookingUserSelect');
    const clearBtn = document.getElementById('userSearchClear');
    
    if (searchInput) searchInput.value = '';
    if (hiddenInput) hiddenInput.value = '';
    if (clearBtn) clearBtn.style.display = 'none';
    
    hideUserDropdown();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close user dropdown when clicking outside
document.addEventListener('click', function(event) {
    const userSearchContainer = document.querySelector('.user-search-container');
    const dropdown = document.getElementById('userDropdown');
    
    if (userSearchContainer && dropdown && !userSearchContainer.contains(event.target)) {
        hideUserDropdown();
    }
});

// Validate booking form before submission
function validateBookingForm() {
    const userId = document.getElementById('bookingUserSelect');
    if (!userId || !userId.value || userId.value.trim() === '') {
        alert('Please select a user before creating the booking.');
        const searchInput = document.getElementById('bookingUserSearch');
        if (searchInput) {
            searchInput.focus();
            showUserDropdown();
        }
        return false;
    }
    
    const venueId = document.getElementById('bookingVenueSelect');
    if (!venueId || !venueId.value || venueId.value.trim() === '') {
        alert('Please select a facility before creating the booking.');
        return false;
    }
    
    const bookingDate = document.getElementById('booking_date');
    if (!bookingDate || !bookingDate.value || bookingDate.value.trim() === '') {
        alert('Please select a booking date.');
        return false;
    }
    
    const bookingTimes = document.getElementById('booking_times');
    if (!bookingTimes || !bookingTimes.value || bookingTimes.value.trim() === '') {
        alert('Please select at least one booking time.');
        return false;
    }
    
    return true;
}

function openAddAnnouncementModal() {
    const modal = document.getElementById('addAnnouncementModal');
    if (modal) {
        modal.style.display = 'flex';
        document.getElementById('announcement_title').focus();
    }
}

function closeAddAnnouncementModal() {
    const modal = document.getElementById('addAnnouncementModal');
    if (modal) modal.style.display = 'none';
}

function openAddEventModal() {
    const modal = document.getElementById('addEventModal');
    if (modal) {
        // Clear form
        document.getElementById('event_name').value = '';
        document.getElementById('event_venue_id').value = '';
        document.getElementById('event_start_date').value = '';
        document.getElementById('event_end_date').value = '';
        document.getElementById('event_start_time').value = '';
        document.getElementById('event_end_time').value = '';

        modal.style.display = 'flex';
        document.getElementById('event_name').focus();

        // Add validation listeners
        setupEventValidation();
    }
}

function closeAddEventModal() {
    const modal = document.getElementById('addEventModal');
    if (modal) modal.style.display = 'none';
    removeEventValidation();
}

function openEditAnnouncementModal(data) {
    const modal = document.getElementById('editAnnouncementModal');
    if (modal) {
        document.getElementById('edit_announcement_id').value = data.announcement_id;
        document.getElementById('edit_announcement_title').value = data.title;
        document.getElementById('edit_announcement_message').value = data.message;
        document.getElementById('edit_announcement_audience').value = data.audience;
        modal.style.display = 'flex';
        document.getElementById('edit_announcement_title').focus();
    }
}

function closeEditAnnouncementModal() {
    const modal = document.getElementById('editAnnouncementModal');
    if (modal) modal.style.display = 'none';
}

function openEditEventModal(data) {
    // Check if event has already started
    if (data.start_date && data.start_time) {
        const startDateTime = data.start_date + ' ' + data.start_time;
        const startTimestamp = new Date(startDateTime).getTime();
        const currentTimestamp = new Date().getTime();
        
        if (startTimestamp < currentTimestamp) {
            alert('Cannot edit events that have already started');
            return;
        }
    }
    
    const modal = document.getElementById('editEventModal');
    if (modal) {
        document.getElementById('edit_event_id').value = data.event_id;
        document.getElementById('edit_event_name').value = data.name;

        // Set start date display
        if (data.start_date) {
            const startDate = parseIsoDateToLocal(data.start_date);
            const startFormatted = startDate.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.getElementById('editStartSelectedDateText').textContent = startFormatted;
            document.getElementById('edit_event_start_date').value = data.start_date;
            document.getElementById('editStartDateDisplay').classList.add('selected');
        }

        // Set end date display
        if (data.end_date) {
            const endDate = parseIsoDateToLocal(data.end_date);
            const endFormatted = endDate.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.getElementById('editEndSelectedDateText').textContent = endFormatted;
            document.getElementById('edit_event_end_date').value = data.end_date;
            document.getElementById('editEndDateDisplay').classList.add('selected');
        }

        // Set start time display
        if (data.start_time) {
            const startTimeDisplay = convertTo12Hour(data.start_time);
            document.getElementById('editStartSelectedTimeText').textContent = startTimeDisplay;
            document.getElementById('edit_event_start_time').value = data.start_time;
            document.getElementById('editStartTimeDisplay').classList.add('selected');
        }

        // Set end time display
        if (data.end_time) {
            const endTimeDisplay = convertTo12Hour(data.end_time);
            document.getElementById('editEndSelectedTimeText').textContent = endTimeDisplay;
            document.getElementById('edit_event_end_time').value = data.end_time;
            document.getElementById('editEndTimeDisplay').classList.add('selected');
        }

        // Set venue
        if (data.venue_id) {
            document.getElementById('edit_event_venue_id').value = data.venue_id;
        }

        modal.style.display = 'flex';
        document.getElementById('edit_event_name').focus();

        // Add validation listeners
        setupEventValidation();
    }
}

function closeEditEventModal() {
    const modal = document.getElementById('editEventModal');
    if (modal) modal.style.display = 'none';
    removeEventValidation();
}


// Event validation functions
function setupEventValidation() {
    // Validate date ranges
    const startDateInputs = document.querySelectorAll('[id$="_start_date"]');
    const endDateInputs = document.querySelectorAll('[id$="_end_date"]');

    startDateInputs.forEach(input => {
        input.addEventListener('change', validateEventDates);
    });

    endDateInputs.forEach(input => {
        input.addEventListener('change', validateEventDates);
    });
}

function removeEventValidation() {
    const startDateInputs = document.querySelectorAll('[id$="_start_date"]');
    const endDateInputs = document.querySelectorAll('[id$="_end_date"]');

    startDateInputs.forEach(input => {
        input.removeEventListener('change', validateEventDates);
    });

    endDateInputs.forEach(input => {
        input.removeEventListener('change', validateEventDates);
    });
}

function validateEventDates() {
    const modal = document.querySelector('.modal[style*="flex"]');
    if (!modal) return;

    const isEdit = modal.id === 'editEventModal';
    const prefix = isEdit ? 'edit_event' : 'event';

    const startDate = document.getElementById(`${prefix}_start_date`).value;
    const endDate = document.getElementById(`${prefix}_end_date`).value;

    if (startDate && endDate) {
        if (new Date(endDate) < new Date(startDate)) {
            alert('End date cannot be before start date');
            document.getElementById(`${prefix}_end_date`).value = startDate;
        }
    }
}

function openViewUserModal(user) {
    const viewModal = document.getElementById('viewUserModal');
    viewModal.style.display = 'flex';
    
    const avatarSrc = user.avatar_path || 'images/avatar/default.png';
    document.getElementById('viewAvatar').innerHTML = `<img src="${avatarSrc}" style="width:100%;height:100%;object-fit:cover;">`;
    
    let html = '';
    const skip = ['avatar_path', 'password'];
    const fieldNames = {
        'user_id': 'User ID',
        'name': 'Name',
        'email': 'Email',
        'role': 'Role',
        'living_place': 'Living Place',
        'phone_number': 'Phone Number',
        'date_of_birth': 'Date of Birth',
        'gender': 'Gender'
    };
    
    for (const key in user) {
        if (user.hasOwnProperty(key) && !skip.includes(key)) {
            const label = fieldNames[key] || key.replace(/_/g, ' ').toUpperCase();
            const value = user[key] || 'N/A';
            html += `<p><strong>${label}:</strong> ${value}</p>`;
        }
    }
    document.getElementById('viewDetails').innerHTML = html;
}

function closeViewUserModal() {
    document.getElementById('viewUserModal').style.display = 'none';
}

let deleteUserId = '';

function openDeleteModal(userId, userName) {
    deleteUserId = userId;
    document.getElementById('deleteUserName').innerText = userName;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    deleteUserId = '';
}

function confirmDelete() {
    if (deleteUserId) {
        window.location.href = 'admin_dashboard.php?page=users&delete_user=' + encodeURIComponent(deleteUserId);
    }
}

// Modal close on outside click
window.onclick = function(e) {
    const userModal = document.getElementById('userModal');
    const viewModal = document.getElementById('viewUserModal');
    const deleteModal = document.getElementById('deleteModal');
    const editFacilityModal = document.getElementById('editFacilityModal');
    const addFacilityModal = document.getElementById('addFacilityModal');
    
    if (e.target == userModal) closeUserModal();
    if (e.target == viewModal) closeViewUserModal();
    if (e.target == deleteModal) closeDeleteModal();
    if (e.target == editFacilityModal) closeEditFacilityModal();
    if (e.target == addFacilityModal) closeAddFacilityModal();
}

// Facility modal functions
function openEditFacilityModal(facility) {
    const modal = document.getElementById('editFacilityModal');
    
    document.getElementById('fac_id').value = facility.venue_id;
    document.getElementById('fac_venue_id').value = facility.venue_id;
    document.getElementById('fac_name').value = facility.name;
    document.getElementById('fac_capacity').value = facility.capacity;
    document.getElementById('fac_location').value = facility.location;
    document.getElementById('fac_court').value = facility.court || '';
    document.getElementById('fac_description').value = facility.description || '';
    
    // Use the current facility status from database (already Open/Closed)
    var currentStatus = 'closed';
    if (facility.status) {
        const st = facility.status.toLowerCase();
        currentStatus = (st === 'open' || st === 'active') ? 'open' : 'closed';
    }
    
    document.getElementById('fac_status_select').value = currentStatus;
    const sd = document.querySelector('#facStatusDropdown .cd-selected');
    if (sd) sd.textContent = currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1);
    
    // Set image preview
    const imagePreview = document.getElementById('facilityImagePreview');
    if (facility.image && facility.image !== '' && facility.image !== 'null') {
        imagePreview.innerHTML = `<img src="${facility.image}" style="width:100%;height:100%;object-fit:cover;">`;
    } else {
        imagePreview.innerHTML = '<span class="placeholder">🏛️</span>';
    }
    
    // Setup status dropdown with callback
    setupDropdown('facStatusDropdown', function(value) {
        const statusSel = document.getElementById('fac_status_select');
        if (statusSel) statusSel.value = value;
    });
    
    // Clear file input
    document.getElementById('fac_image').value = '';
    
    modal.style.display = 'flex';
}

function closeEditFacilityModal() {
    document.getElementById('editFacilityModal').style.display = 'none';
}

function previewFacilityImage(event) {
    const file = event.target.files[0];
    const imagePreview = document.getElementById('facilityImagePreview');
    
    if (file) {
        const allowed = ['jpg','jpeg','png','gif'];
        const ext = file.name.split('.').pop().toLowerCase();
        if (!allowed.includes(ext)) {
            alert('Only jpg, jpeg, png, gif formats allowed');
            event.target.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
        };
        reader.readAsDataURL(file);
    }
}

// Add Facility modal functions
function openAddFacilityModal() {
    const modal = document.getElementById('addFacilityModal');
    
    document.getElementById('add_fac_id').value = '';
    document.getElementById('add_fac_name').value = '';
    document.getElementById('add_fac_capacity').value = '';
    document.getElementById('add_fac_location').value = '';
    document.getElementById('add_fac_court').value = '';
    document.getElementById('add_fac_description').value = '';
    document.getElementById('add_fac_status_select').value = 'open';
    document.getElementById('add_fac_image').value = '';
    
    // Reset image preview
    const imagePreview = document.getElementById('addFacilityImagePreview');
    if (imagePreview) {
        imagePreview.innerHTML = '<span class="placeholder">🏛️</span>';
    }
    
    const sd = document.querySelector('#addFacStatusDropdown .cd-selected');
    if (sd) sd.textContent = 'Open';
    
    // Setup status dropdown
    setupDropdown('addFacStatusDropdown', function(value) {
        const statusSel = document.getElementById('add_fac_status_select');
        if (statusSel) statusSel.value = value;
    });
    
    modal.style.display = 'flex';
}

function closeAddFacilityModal() {
    document.getElementById('addFacilityModal').style.display = 'none';
}

function previewAddFacilityImage(event) {
    const file = event.target.files[0];
    const imagePreview = document.getElementById('addFacilityImagePreview');
    
    if (file) {
        const allowed = ['jpg','jpeg','png','gif'];
        const ext = file.name.split('.').pop().toLowerCase();
        if (!allowed.includes(ext)) {
            alert('Only jpg, jpeg, png, gif formats allowed');
            event.target.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
        };
        reader.readAsDataURL(file);
    }
}

// Filter functions for announcements, events, and feedback (client-side)
function filterAnnouncementsTable() {
    const searchInput = document.getElementById('announcementSearch');
    const table = document.getElementById('announcementsTable');
    
    if (!searchInput || !table) return;
    
    const query = searchInput.value.toLowerCase().trim();
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(tr => {
        const cells = tr.getElementsByTagName('td');
        if (cells.length < 4) return;
        
        let matchSearch = true;
        
        if (query) {
            const title = (cells[0] && cells[0].textContent || '').toLowerCase();
            const message = (cells[1] && cells[1].textContent || '').toLowerCase();
            matchSearch = title.includes(query) || message.includes(query);
        }
        
        tr.style.display = matchSearch ? '' : 'none';
    });
}

function filterEventsTable() {
    const searchInput = document.getElementById('eventSearch');
    const table = document.getElementById('eventsTable');
    
    if (!searchInput || !table) return;
    
    const query = searchInput.value.toLowerCase().trim();
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(tr => {
        const cells = tr.getElementsByTagName('td');
        if (cells.length < 6) return;
        
        let matchSearch = true;
        
        if (query) {
            const eventName = (cells[0] && cells[0].textContent || '').toLowerCase();
            const facility = (cells[1] && cells[1].textContent || '').toLowerCase();
            matchSearch = eventName.includes(query) || facility.includes(query);
        }
        
        tr.style.display = matchSearch ? '' : 'none';
    });
}

function filterFeedbackTable() {
    const searchInput = document.getElementById('feedbackSearch');
    const statusFilter = document.getElementById('feedbackStatusFilter');
    const table = document.getElementById('feedbackTable');
    
    if (!searchInput || !statusFilter || !table) return;
    
    const query = searchInput.value.toLowerCase().trim();
    const status = statusFilter.value.toLowerCase();
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(tr => {
        const cells = tr.getElementsByTagName('td');
        if (cells.length < 6) return;
        
        let matchSearch = true;
        let matchStatus = true;
        
        if (query) {
            const userId = (cells[0] && cells[0].textContent || '').toLowerCase();
            const subject = (cells[1] && cells[1].textContent || '').toLowerCase();
            const message = (cells[2] && cells[2].textContent || '').toLowerCase();
            matchSearch = userId.includes(query) || subject.includes(query) || message.includes(query);
        }
        
        if (status && cells[4]) {
            const statusCell = (cells[4].textContent || '').toLowerCase();
            matchStatus = statusCell.includes(status);
        }
        
        tr.style.display = (matchSearch && matchStatus) ? '' : 'none';
    });
}

function openReplyFeedbackModal(data) {
    const modal = document.getElementById('replyFeedbackModal');
    if (modal) {
        document.getElementById('reply_feedback_id').value = data.feedback_id;
        document.getElementById('replySubject').textContent = data.subject;
        document.getElementById('replyMessage').textContent = data.message;
        document.getElementById('feedbackResponse').value = '';
        document.getElementById('previousResponse').textContent = data.respond || '(No previous response)';
        modal.style.display = 'flex';
        document.getElementById('feedbackResponse').focus();
    }
}

function openReplyFeedbackModalFromButton(button) {
    const data = {
        feedback_id: button.getAttribute('data-feedback-id'),
        subject: button.getAttribute('data-subject'),
        message: button.getAttribute('data-message'),
        respond: button.getAttribute('data-respond')
    };
    openReplyFeedbackModal(data);
}

function closeReplyFeedbackModal() {
    const modal = document.getElementById('replyFeedbackModal');
    if (modal) modal.style.display = 'none';
}

function resolveFeedbackFromButton(button) {
    const feedbackId = button.getAttribute('data-feedback-id');
    pendingResolveFeedbackData = { feedbackId };
    document.getElementById('resolveFeedbackModal').classList.add('show');
}


// Initialize Charts on Reports page
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on the reports page
    const params = new URLSearchParams(window.location.search);
    if (params.get('page') === 'reports') {
        initializeCharts();
    }
});

// Initialize Charts on Reports page
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on the reports page
    const params = new URLSearchParams(window.location.search);
    if (params.get('page') === 'reports') {
        // Set current month as default using local time (avoid UTC shift)
        const today = new Date();
        const currentMonth = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`; // YYYY-MM
        
        document.getElementById('bookingsFilterInput').value = currentMonth;
        document.getElementById('facilitiesFilterInput').value = currentMonth;
        
        initializeCharts();
    }
});

function toggleBookingsFilterInput() {
    const filterType = document.getElementById('bookingsFilterType').value;
    const filterDiv = document.getElementById('bookingsFilterInputDiv');
    const label = filterDiv.querySelector('label');
    const input = document.getElementById('bookingsFilterInput');
    
    if (filterType === 'month') {
        label.textContent = 'Select Month';
        input.type = 'month';
        input.placeholder = '';
        const now = new Date();
        const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
        input.value = currentMonth;
    } else if (filterType === 'day') {
        label.textContent = 'Select Day';
        input.type = 'date';
        const today = new Date();
        input.value = formatDateLocal(today);
    } else if (filterType === 'year') {
        label.textContent = 'Select Year';
        input.type = 'number';
        input.min = 2000;
        input.max = new Date().getFullYear();
        input.step = 1;
        input.value = new Date().getFullYear();
    }
    updateBookingsReport();
}

function toggleFacilitiesFilterInput() {
    const filterType = document.getElementById('facilitiesFilterType').value;
    const filterDiv = document.getElementById('facilitiesFilterInputDiv');
    const label = filterDiv.querySelector('label');
    const input = document.getElementById('facilitiesFilterInput');
    
    if (filterType === 'month') {
        label.textContent = 'Select Month';
        input.type = 'month';
        input.placeholder = '';
        const now = new Date();
        const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
        input.value = currentMonth;
    } else if (filterType === 'day') {
        label.textContent = 'Select Day';
        input.type = 'date';
        const today = new Date();
        input.value = formatDateLocal(today);
    } else if (filterType === 'year') {
        label.textContent = 'Select Year';
        input.type = 'number';
        input.min = 2000;
        input.max = new Date().getFullYear();
        input.step = 1;
        input.value = new Date().getFullYear();
    }
    updateFacilitiesReport();
}

function initializeCharts() {
    // Initialize with sample data
    updateBookingsReport();
    updateFacilitiesReport();
}

<?php
// Build live data for charts from database (current month default)
// Time slots used for bookings report (7am to 10pm, hourly)
$timeSlots = ['07:00','08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00'];
$daysOfWeek = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

$year = (int)date('Y');
$month = (int)date('m');

// Initialize bookings data structure
$bookingsData = [];
foreach ($timeSlots as $ts) {
    $bookingsData[$ts] = array_fill_keys($daysOfWeek, 0);
}

// Query booking counts grouped by time slot and weekday for current month
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
        // normalize time slot to H:00 without leading zeros if needed
        $ts = preg_replace('/^0/', '', $ts);
        // Map common formats like '8:00' and '08:00'
        if (isset($bookingsData[$ts])) {
            $bookingsData[$ts][$day] = $cnt;
        } else {
            // try zero-padded
            $tz = str_pad($ts,5,'0',STR_PAD_LEFT);
            if (isset($bookingsData[$tz])) $bookingsData[$tz][$day] = $cnt;
        }
    }
    $stmt->close();
}

// Build facilities data: totals per weekday and peak time per facility
$facilitiesData = [];
$sql2 = "SELECT v.venue_id, v.name AS venue_name, DAYNAME(b.booking_date) AS dayname, TIME_FORMAT(b.booking_time, '%H:00') AS time_slot, COUNT(*) AS cnt
         FROM bookings b
         JOIN venues v ON v.venue_id = b.venue_id
         WHERE YEAR(b.booking_date)=? AND MONTH(b.booking_date)=?
         GROUP BY v.venue_id, dayname, time_slot";
if ($stmt2 = $conn->prepare($sql2)) {
    $stmt2->bind_param('ii', $year, $month);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($r = $res2->fetch_assoc()) {
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
    $stmt2->close();
}

// Convert facilitiesData map to array with peak time and day totals
$facilitiesOutput = [];
foreach ($facilitiesData as $fid => $info) {
    $peak = '';
    if (!empty($info['times'])) {
        arsort($info['times']);
        $peak = key($info['times']);
    }
    $facilitiesOutput[] = [
        'name' => $info['name'],
        'Monday' => (int)$info['Monday'],
        'Tuesday' => (int)$info['Tuesday'],
        'Wednesday' => (int)$info['Wednesday'],
        'Thursday' => (int)$info['Thursday'],
        'Friday' => (int)$info['Friday'],
        'Saturday' => (int)$info['Saturday'],
        'Sunday' => (int)$info['Sunday'],
        'peak' => $peak ?: ''
    ];
}

// Build yearly aggregates for current year
$bookingsYearTotals = array_fill(0, 12, 0);
$sqlYear = "SELECT MONTH(booking_date) AS m, COUNT(*) AS cnt FROM bookings WHERE YEAR(booking_date)=? GROUP BY m";
if ($stmtY = $conn->prepare($sqlYear)) {
    $stmtY->bind_param('i', $year);
    $stmtY->execute();
    $resY = $stmtY->get_result();
    while ($r = $resY->fetch_assoc()) {
        $m = (int)$r['m'];
        $cnt = (int)$r['cnt'];
        $bookingsYearTotals[$m - 1] = $cnt;
    }
    $stmtY->close();
}

// Facilities yearly aggregation: per venue per month totals and peak month
$facilitiesYearAgg = [];
$sqlFY = "SELECT v.venue_id, v.name AS venue_name, MONTH(b.booking_date) AS m, COUNT(*) AS cnt
          FROM bookings b
          JOIN venues v ON v.venue_id = b.venue_id
          WHERE YEAR(b.booking_date)=?
          GROUP BY v.venue_id, m";
if ($stmtFY = $conn->prepare($sqlFY)) {
    $stmtFY->bind_param('i', $year);
    $stmtFY->execute();
    $resFY = $stmtFY->get_result();
    while ($r = $resFY->fetch_assoc()) {
        $vid = $r['venue_id'];
        $vname = $r['venue_name'];
        $m = (int)$r['m'];
        $cnt = (int)$r['cnt'];
        if (!isset($facilitiesYearAgg[$vid])) {
            $facilitiesYearAgg[$vid] = ['name' => $vname, 'months' => array_fill(1, 12, 0)];
        }
        $facilitiesYearAgg[$vid]['months'][$m] = ($facilitiesYearAgg[$vid]['months'][$m] ?? 0) + $cnt;
    }
    $stmtFY->close();
}

$facilitiesYearOutput = [];
foreach ($facilitiesYearAgg as $fid => $info) {
    $months = $info['months'];
    $total = array_sum($months);
    $peakMonth = '';
    if ($total > 0) {
        $maxVal = max($months);
        $peakIndex = array_search($maxVal, $months);
        // month index is 1..12
        $peakMonth = $peakIndex ? DateTime::createFromFormat('!m', $peakIndex)->format('F') : '';
    }
    $entry = ['name' => $info['name'], 'total' => (int)$total, 'peak_month' => $peakMonth];
    // add month fields
    for ($i = 1; $i <= 12; $i++) {
        $entry[DateTime::createFromFormat('!m', $i)->format('F')] = (int)($months[$i] ?? 0);
    }
    $facilitiesYearOutput[] = $entry;
}

// Emit JavaScript variables with the live data (month view and year view)
?>
const bookingsSampleData = <?= json_encode($bookingsData, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
const facilitiesSampleData = <?= json_encode($facilitiesOutput, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
const bookingsYearTotals = <?= json_encode($bookingsYearTotals, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
const facilitiesYearData = <?= json_encode($facilitiesYearOutput, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;

function updateBookingsReport() {
    const filterType = document.getElementById('bookingsFilterType').value;
    const filterValue = document.getElementById('bookingsFilterInput').value;
    const sortBy = document.getElementById('bookingsSortBy').value;
    
    // Fetch data from reports_data.php endpoint
    const params = new URLSearchParams({
        filterType: filterType,
        filterValue: filterValue
    });
    
    fetch(`reports_data.php?${params}`)
        .then(response => response.json())
        .then(data => {
            const bookingsSampleData = data.bookings || {};
            let chartData, tableData;
            
            if (filterType === 'day') {
                // Filter by specific day
                const dateObj = new Date(filterValue);
                const dayName = dateObj.toLocaleDateString('en-US', { weekday: 'long' });
                
                const timeSlots = Object.keys(bookingsSampleData);
                const bookingCounts = timeSlots.map(time => (bookingsSampleData[time] && bookingsSampleData[time][dayName]) ? bookingsSampleData[time][dayName] : 0);
                
                let sortedData = timeSlots.map((time, idx) => ({
                    time: time.replace(/^0/, ''),
                    count: bookingCounts[idx]
                }));
                
                if (sortBy === 'most') {
                    sortedData.sort((a, b) => b.count - a.count);
                } else if (sortBy === 'least') {
                    sortedData.sort((a, b) => a.count - b.count);
                }
                
                chartData = {
                    title: `Bookings on ${dayName} (${filterValue})`,
                    labels: sortedData.map(d => d.time),
                    data: sortedData.map(d => d.count),
                    sortedData: sortedData
                };
                tableData = { dayName, data: sortedData };
                // store last bookings response/table for export
                window.lastBookingsResponse = data;
                window.lastBookingsFilter = { filterType, filterValue, sortBy };
                window.lastBookingsTableData = tableData;
            } else if (filterType === 'year') {
                // Filter by year - show bookings per month
                const year = filterValue || new Date().getFullYear();
                const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                const counts = Object.values(bookingsSampleData);
                
                let sortedData = monthNames.map((mn, idx) => ({ month: mn, count: counts[idx] || 0 }));
                if (sortBy === 'most') {
                    sortedData.sort((a, b) => b.count - a.count);
                } else if (sortBy === 'least') {
                    sortedData.sort((a, b) => a.count - b.count);
                } else if (sortBy === 'recent') {
                    sortedData.reverse();
                }
                
                chartData = {
                    title: `Bookings per Month in ${year}`,
                    labels: sortedData.map(d => d.month),
                    data: sortedData.map(d => d.count),
                    sortedData: sortedData
                };
                tableData = { year, data: sortedData };
                // store last bookings response/table for export
                window.lastBookingsResponse = data;
                window.lastBookingsFilter = { filterType, filterValue, sortBy };
                window.lastBookingsTableData = tableData;
            } else {
                // Filter by month - show all days
                const [year, month] = filterValue.split('-');
                const monthName = new Date(year, month - 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                
                const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                const dayTotals = daysOfWeek.map(day => ({
                    day: day,
                    count: Object.values(bookingsSampleData).reduce((sum, timeData) => sum + (timeData[day] || 0), 0)
                }));
                
                let sortedData = [...dayTotals];
                if (sortBy === 'most') {
                    sortedData.sort((a, b) => b.count - a.count);
                } else if (sortBy === 'least') {
                    sortedData.sort((a, b) => a.count - b.count);
                } else if (sortBy === 'recent') {
                    sortedData.reverse();
                }
                
                chartData = {
                    title: `Bookings by Day in ${monthName}`,
                    labels: sortedData.map(d => d.day),
                    data: sortedData.map(d => d.count),
                    sortedData: sortedData
                };
                tableData = { monthName, data: sortedData };
                // store last bookings response/table for export
                window.lastBookingsResponse = data;
                window.lastBookingsFilter = { filterType, filterValue, sortBy };
                window.lastBookingsTableData = tableData;
            }
            
            updateBookingsChart(chartData);
            updateBookingsTableFromData(tableData, filterType);
        })
        .catch(error => {
            console.error('Error fetching bookings data:', error);
        });
}

function updateBookingsChart(chartData) {
    const bookingsCtx = document.getElementById('bookingsChart');
    if (bookingsCtx && bookingsCtx.chart) {
        bookingsCtx.chart.destroy();
    }
    
    // Compute dynamic y-axis max based on provided data to avoid large empty gaps
    const maxVal = chartData.data && chartData.data.length ? Math.max(...chartData.data) : 0;
    const yAxisMax = Math.max(5, Math.ceil(maxVal * 1.1)); // at least 5, add 10% buffer

    const ctx = bookingsCtx.getContext('2d');
    bookingsCtx.chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: chartData.title,
                data: chartData.data,
                backgroundColor: [
                    '#ff6b35',
                    '#ff8c42',
                    '#ffa07a',
                    '#ff7f50',
                    '#ff6347',
                    '#ff4500',
                    '#ff7f50'
                ],
                borderColor: '#ff6b35',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true, position: 'top' }
            },
            scales: {
                y: { beginAtZero: true, max: yAxisMax, ticks: { stepSize: 1 } }
            }
        }
    });
}

function updateBookingsTableFromData(tableData, filterType) {
    const tbody = document.getElementById('bookingsTableBody');
    tbody.innerHTML = '';
    
    tableData.data.forEach((item, idx) => {
        const row = document.createElement('tr');
        row.style.borderBottom = '1px solid #eee';
        row.style.background = idx % 2 === 0 ? '#f8f9fa' : 'white';
        
        let approved, pending, cancelled;
        if (filterType === 'day') {
            approved = Math.floor(item.count * 0.7);
            pending = Math.floor(item.count * 0.2);
            cancelled = item.count - approved - pending;
            
            row.innerHTML = `
                <td style="padding: 12px; color: #333;">${tableData.dayName}</td>
                <td style="padding: 12px; color: #ff6b35; font-weight: 600;">${item.time}</td>
                <td style="padding: 12px; text-align: center; color: #333; font-weight: 600;">${item.count}</td>
                <td style="padding: 12px; text-align: center;">
                    <span style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; margin-right: 4px; font-size: 0.85em;">✓ ${approved}</span>
                    <span style="background: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; margin-right: 4px; font-size: 0.85em;">⏳ ${pending}</span>
                    <span style="background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px; font-size: 0.85em;">✕ ${cancelled}</span>
                </td>
            `;
        } else {
            approved = Math.floor(item.count * 0.7);
            pending = Math.floor(item.count * 0.2);
            cancelled = item.count - approved - pending;
            
            const label = item.day || item.month || item.time || 'All';
            row.innerHTML = `
                <td style="padding: 12px; color: #333;">${label}</td>
                <td style="padding: 12px; color: #ff6b35; font-weight: 600;">All Day</td>
                <td style="padding: 12px; text-align: center; color: #333; font-weight: 600;">${item.count}</td>
                <td style="padding: 12px; text-align: center;">
                    <span style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; margin-right: 4px; font-size: 0.85em;">✓ ${approved}</span>
                    <span style="background: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; margin-right: 4px; font-size: 0.85em;">⏳ ${pending}</span>
                    <span style="background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px; font-size: 0.85em;">✕ ${cancelled}</span>
                </td>
            `;
        }
        tbody.appendChild(row);
    });
}

function updateFacilitiesReport() {
    const filterType = document.getElementById('facilitiesFilterType').value;
    const filterValue = document.getElementById('facilitiesFilterInput').value;
    const sortBy = document.getElementById('facilitiesSortBy').value;
    
    // Fetch data from reports_data.php endpoint
    const params = new URLSearchParams({
        filterType: filterType,
        filterValue: filterValue
    });
    
    fetch(`reports_data.php?${params}`)
        .then(response => response.json())
        .then(data => {
            // Debug: log facilities payload when inspecting month data
            if (filterType === 'month') {
                console.log('reports_data.php -> facilities (month):', data.facilities);
            }
            let facilityData = data.facilities || [];
            let filterLabel = '';
            
            // Build filter label
            if (filterType === 'day') {
                const dateObj = new Date(filterValue);
                const dayName = dateObj.toLocaleDateString('en-US', { weekday: 'long' });
                filterLabel = `Facility Bookings on ${dayName}`;
            } else if (filterType === 'year') {
                filterLabel = `Facility Bookings in ${filterValue}`;
            } else {
                const [year, month] = filterValue.split('-');
                const monthName = new Date(year, month - 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                filterLabel = `Facility Bookings in ${monthName}`;
            }
            
            // Apply sorting
            if (sortBy === 'most') {
                facilityData.sort((a, b) => b.dayBookings - a.dayBookings);
            } else if (sortBy === 'least') {
                facilityData.sort((a, b) => a.dayBookings - b.dayBookings);
            }
            
            // If server didn't provide a 'peak' value for some reason, compute a fallback from 'times'
            if (filterType === 'month' || filterType === 'day' || filterType === 'year') {
                facilityData = facilityData.map(f => {
                    // prefer server-provided peak_time, but compute fallback from times if missing
                    if ((!f.peak_time || f.peak_time === '') && f.times) {
                        try {
                            let maxCount = -1;
                            let maxKey = '';
                            for (const [k, v] of Object.entries(f.times)) {
                                const n = Number(v) || 0;
                                if (n > maxCount) { maxCount = n; maxKey = k; }
                            }
                            if (maxKey) {
                                f.peak_time = maxKey;
                                f.peak_count = Number(maxCount) || 0;
                                f.peak_percent = f.dayBookings > 0 ? Math.round((f.peak_count / f.dayBookings) * 10000) / 100 : 0;
                            }
                        } catch (e) {
                            // ignore and keep existing values
                        }
                    }
                    return f;
                });
            }

            // Limit chart data to top 10 for "most" and "least" sorts
            const chartData = (sortBy === 'most' || sortBy === 'least') ? facilityData.slice(0, 10) : facilityData;
            const facilityNames = chartData.map(f => f.name);
            const facilityBookings = chartData.map(f => f.dayBookings);
            
            // Update chart
            const facilitiesCtx = document.getElementById('facilitiesChart');
            if (facilitiesCtx) {
                try {
                    const facilitiesContainer = facilitiesCtx.parentElement;
                    const perBar = 60;
                    const minHeight = 220;
                    const maxHeight = 1200;
                    const desiredHeight = Math.min(maxHeight, Math.max(minHeight, chartData.length * perBar));
                    if (facilitiesContainer) {
                        facilitiesContainer.style.height = desiredHeight + 'px';
                    }
                    facilitiesCtx.style.height = desiredHeight + 'px';
                    facilitiesCtx.height = desiredHeight;
                } catch (e) {
                    console.warn('Failed to adjust facilities chart height', e);
                }
                
                if (facilitiesCtx.chart) {
                    facilitiesCtx.chart.destroy();
                }
            }
            
            // Compute dynamic axis max
            const maxVal = facilityBookings && facilityBookings.length ? Math.max(...facilityBookings) : 0;
            const xAxisMax = Math.max(5, Math.ceil(maxVal * 1.1));
            const stepSize = xAxisMax <= 20 ? 1 : Math.ceil(xAxisMax / 10);
            const longestLabel = facilityNames.length ? Math.max(...facilityNames.map(n => (n || '').length)) : 10;
            const paddingLeft = Math.min(140, 20 + longestLabel * 6);
            
            const ctx = facilitiesCtx.getContext('2d');
            facilitiesCtx.chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: facilityNames,
                    datasets: [{
                        label: filterLabel,
                        data: facilityBookings,
                        backgroundColor: '#ff6b35',
                        borderColor: '#ff6b35',
                        borderWidth: 2,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: true, position: 'top' }
                    },
                    layout: { padding: { left: paddingLeft, right: 20, top: 10, bottom: 10 } },
                    scales: {
                        x: { beginAtZero: true, max: xAxisMax, ticks: { stepSize: stepSize } }
                    }
                }
            });
            
            // Update table with ALL facilities
            // store last facilities response and table data for export
            window.lastFacilitiesResponse = data;
            window.lastFacilitiesFilter = { filterType, filterValue, sortBy };
            window.lastFacilitiesTableData = facilityData;
            updateFacilitiesTableFromData(facilityData, filterType, filterValue);
        })
        .catch(error => {
            console.error('Error fetching facilities data:', error);
        });
}

function updateFacilitiesTableFromData(facilityData, filterType, filterValue) {
    const tbody = document.getElementById('facilitiesTableBody');
    tbody.innerHTML = '';
    
    facilityData.forEach((facility, idx) => {
        const row = document.createElement('tr');
        row.style.borderBottom = '1px solid #eee';
        row.style.background = idx % 2 === 0 ? '#f8f9fa' : 'white';
        
        // capacity utilization column removed
        
        let dayInfo = '';
        if (filterType === 'day') {
            const dateObj = new Date(filterValue);
            dayInfo = dateObj.toLocaleDateString('en-US', { weekday: 'long' });
        } else if (filterType === 'year') {
            dayInfo = 'Entire Year';
        } else {
            dayInfo = 'Entire Month';
        }
        
        let peakDisplay = '';
        // Prefer server-provided peak_time with counts/percentage; fallback to legacy 'peak' or compute from times
        if (facility.peak_time) {
            peakDisplay = `${facility.peak_time} — ${facility.peak_count} (${facility.peak_percent}%)`;
        } else if (facility.peak) {
            peakDisplay = facility.peak;
        } else if (facility.times) {
            // compute best time locally as final fallback
            try {
                let maxK = '';
                let maxV = -1;
                for (const [k, v] of Object.entries(facility.times)) {
                    const n = Number(v) || 0;
                    if (n > maxV) { maxV = n; maxK = k; }
                }
                if (maxK) {
                    const pct = facility.dayBookings > 0 ? Math.round((maxV / facility.dayBookings) * 10000) / 100 : 0;
                    peakDisplay = `${maxK} — ${maxV} (${pct}%)`;
                }
            } catch (e) {
                peakDisplay = '';
            }
        }
        // Simple analysis based on concentration of bookings at peak time
        let analysis = '';
        const peakPct = Number(facility.peak_percent || 0);
        if (peakPct >= 50) {
            analysis = 'High concentration at peak time.';
        } else if (peakPct >= 30) {
            analysis = 'Moderate concentration at peak time.';
        } else if (peakPct > 0) {
            analysis = 'Bookings are fairly spread out.';
        }

        row.innerHTML = `
            <td style="padding: 12px; color: #333; font-weight: 600;">${facility.name}</td>
            <td style="padding: 12px; color: #ff6b35; font-weight: 600;">${dayInfo}</td>
            <td style="padding: 12px; color: #333;">${peakDisplay}${analysis ? `<div style="font-size:0.8em;color:#666;margin-top:6px;">${analysis}</div>` : ''}</td>
            <td style="padding: 12px; text-align: center; color: #333; font-weight: 600;">${facility.dayBookings}</td>
        `;
        tbody.appendChild(row);
    });
}

// Download Functions
function downloadBookingsReport() {
    const resp = window.lastBookingsResponse;
    const tableData = window.lastBookingsTableData;
    const filter = window.lastBookingsFilter || {};
    if (!resp || !tableData) {
        alert('No bookings data to export. Please apply filters first.');
        return;
    }

    // CSV helper
    const escape = s => '"' + String(s).replace(/"/g, '""') + '"';
    let rows = [];
    rows.push([`Bookings Report - ${filter.filterType || ''}`, `Filter: ${filter.filterValue || ''}`]);
    rows.push([]);

    if (filter.filterType === 'day') {
        rows.push(['Time', 'Count']);
        const dateObj = new Date(filter.filterValue);
        const dayName = dateObj.toLocaleDateString('en-US', { weekday: 'long' });
        Object.keys(resp.bookings || {}).forEach(ts => {
            const cnt = (resp.bookings[ts] && resp.bookings[ts][dayName]) ? resp.bookings[ts][dayName] : 0;
            rows.push([ts, cnt]);
        });
    } else if (filter.filterType === 'year') {
        rows.push(['Month', 'Count']);
        tableData.data.forEach(d => rows.push([d.month, d.count]));
    } else {
        rows.push(['Day', 'Count']);
        tableData.data.forEach(d => rows.push([d.day, d.count]));
    }

    // Render table to PDF using html2canvas + jsPDF
    const container = document.createElement('div');
    container.style.padding = '24px';
    container.style.background = '#fff';
    container.style.color = '#000';
    const title = document.createElement('h2');
    title.textContent = `Bookings Report - ${filter.filterType || ''} (${filter.filterValue || ''})`;
    container.appendChild(title);

    const table = document.createElement('table');
    table.style.borderCollapse = 'collapse';
    table.style.width = '100%';
    table.style.marginTop = '12px';
    const thead = document.createElement('thead');
    const tbodyEl = document.createElement('tbody');

    // header row
    const headerRow = document.createElement('tr');
    ['Label', 'Value'].forEach(h => {
        const th = document.createElement('th');
        th.style.border = '1px solid #ddd';
        th.style.padding = '8px';
        th.style.background = '#f8f9fa';
        th.textContent = h;
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);

    if (filter.filterType === 'day') {
        Object.keys(resp.bookings || {}).forEach(ts => {
            const dateObj = new Date(filter.filterValue);
            const dayName = dateObj.toLocaleDateString('en-US', { weekday: 'long' });
            const cnt = (resp.bookings[ts] && resp.bookings[ts][dayName]) ? resp.bookings[ts][dayName] : 0;
            const r = document.createElement('tr');
            const c1 = document.createElement('td'); c1.style.border='1px solid #ddd'; c1.style.padding='8px'; c1.textContent = ts;
            const c2 = document.createElement('td'); c2.style.border='1px solid #ddd'; c2.style.padding='8px'; c2.textContent = cnt;
            r.appendChild(c1); r.appendChild(c2);
            tbodyEl.appendChild(r);
        });
    } else if (filter.filterType === 'year') {
        tableData.data.forEach(d => {
            const r = document.createElement('tr');
            const c1 = document.createElement('td'); c1.style.border='1px solid #ddd'; c1.style.padding='8px'; c1.textContent = d.month;
            const c2 = document.createElement('td'); c2.style.border='1px solid #ddd'; c2.style.padding='8px'; c2.textContent = d.count;
            r.appendChild(c1); r.appendChild(c2);
            tbodyEl.appendChild(r);
        });
    } else {
        tableData.data.forEach(d => {
            const r = document.createElement('tr');
            const c1 = document.createElement('td'); c1.style.border='1px solid #ddd'; c1.style.padding='8px'; c1.textContent = d.day;
            const c2 = document.createElement('td'); c2.style.border='1px solid #ddd'; c2.style.padding='8px'; c2.textContent = d.count;
            r.appendChild(c1); r.appendChild(c2);
            tbodyEl.appendChild(r);
        });
    }

    table.appendChild(thead);
    table.appendChild(tbodyEl);
    container.appendChild(table);
    document.body.appendChild(container);

    html2canvas(container, { scale: 2 }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');
        const imgProps = pdf.getImageProperties(imgData);
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        const filename = `bookings_report_${filter.filterType || 'report'}_${(filter.filterValue || '').replace(/[:\/\\ ]/g,'_')}.pdf`;
        pdf.save(filename);
        document.body.removeChild(container);
    }).catch(err => {
        document.body.removeChild(container);
        alert('Failed to generate PDF: ' + err);
    });
}

function downloadFacilitiesReport() {
    const resp = window.lastFacilitiesResponse;
    const data = window.lastFacilitiesTableData;
    const filter = window.lastFacilitiesFilter || {};
    if (!resp || !data) {
        alert('No facilities data to export. Please apply filters first.');
        return;
    }

    // collect union of time slots across facilities
    const timeSet = new Set();
    data.forEach(f => {
        if (f.times) Object.keys(f.times).forEach(t => timeSet.add(t));
    });
    const times = Array.from(timeSet).sort();

    const escape = s => '"' + String(s).replace(/"/g, '""') + '"';
    const header = ['Facility Name', 'Period', 'Peak Time', 'Peak Count', 'Peak %', 'Total Bookings', ...times];
    const rows = [header];

    data.forEach(f => {
        const periodLabel = filter.filterType === 'day' ? new Date(filter.filterValue).toLocaleDateString('en-US', { weekday: 'long' }) : (filter.filterType === 'year' ? 'Entire Year' : 'Entire Month');
        const row = [];
        row.push(f.name || '');
        row.push(periodLabel);
        row.push(f.peak_time || '');
        row.push(f.peak_count != null ? f.peak_count : '');
        row.push(f.peak_percent != null ? f.peak_percent : '');
        row.push(f.dayBookings != null ? f.dayBookings : '');
        times.forEach(t => row.push((f.times && f.times[t]) ? f.times[t] : 0));
        rows.push(row);
    });

    // Render facilities data to PDF using html2canvas + jsPDF
    const container = document.createElement('div');
    container.style.padding = '24px';
    container.style.background = '#fff';
    container.style.color = '#000';
    const title = document.createElement('h2');
    title.textContent = `Facilities Report - ${filter.filterType || ''} (${filter.filterValue || ''})`;
    container.appendChild(title);

    const table = document.createElement('table');
    table.style.borderCollapse = 'collapse';
    table.style.width = '100%';
    table.style.marginTop = '12px';

    const thead = document.createElement('thead');
    const headerRow = document.createElement('tr');
    ['Facility Name','Period','Peak Time','Peak Count','Peak %','Total Bookings', ...times].forEach(h => {
        const th = document.createElement('th');
        th.style.border = '1px solid #ddd';
        th.style.padding = '6px';
        th.style.background = '#f8f9fa';
        th.textContent = h;
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);

    const tbodyEl = document.createElement('tbody');
    data.forEach(f => {
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid #eee';
        const periodLabel = filter.filterType === 'day' ? new Date(filter.filterValue).toLocaleDateString('en-US', { weekday: 'long' }) : (filter.filterType === 'year' ? 'Entire Year' : 'Entire Month');
        const cells = [f.name || '', periodLabel, f.peak_time || '', f.peak_count != null ? f.peak_count : '', f.peak_percent != null ? f.peak_percent : '', f.dayBookings != null ? f.dayBookings : ''];
        cells.forEach(c => {
            const td = document.createElement('td'); td.style.border='1px solid #ddd'; td.style.padding='6px'; td.textContent = c; tr.appendChild(td);
        });
        times.forEach(t => { const td = document.createElement('td'); td.style.border='1px solid #ddd'; td.style.padding='6px'; td.textContent = (f.times && f.times[t]) ? f.times[t] : 0; tr.appendChild(td); });
        tbodyEl.appendChild(tr);
    });

    table.appendChild(thead);
    table.appendChild(tbodyEl);
    container.appendChild(table);
    document.body.appendChild(container);

    html2canvas(container, { scale: 2 }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('l', 'mm', 'a4');
        const imgProps = pdf.getImageProperties(imgData);
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        const filename = `facilities_report_${filter.filterType || 'report'}_${(filter.filterValue || '').replace(/[:\/\\ ]/g,'_')}.pdf`;
        pdf.save(filename);
        document.body.removeChild(container);
    }).catch(err => {
        document.body.removeChild(container);
        alert('Failed to generate PDF: ' + err);
    });
}

// Confirmation Modal for Reject Booking
let pendingRejectId = null;

function rejectBooking(id) {
    pendingRejectId = id;
    document.getElementById('confirmModal').classList.add('show');
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('show');
    pendingRejectId = null;
}

function confirmReject() {
    if (pendingRejectId !== null) {
        // Create and submit hidden form
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="booking_id" value="${pendingRejectId}"><input type="hidden" name="reject_booking" value="1">`;
        document.body.appendChild(form);
        form.submit();
    }
}

// Bulk selection and rejection functions
function toggleAllBookings(checkbox) {
    // Only select visible checkboxes (not filtered out)
    const checkboxes = document.querySelectorAll('.booking-checkbox');
    checkboxes.forEach(cb => {
        const row = cb.closest('tr');
        if (row && row.style.display !== 'none') {
            cb.checked = checkbox.checked;
        }
    });
    updateRejectButton();
}

function updateRejectButton() {
    const checkboxes = document.querySelectorAll('.booking-checkbox:checked');
    const rejectBtn = document.getElementById('rejectSelectedBtn');
    if (rejectBtn) {
        rejectBtn.style.display = checkboxes.length > 0 ? 'inline-block' : 'none';
    }
}

function rejectSelectedBookings() {
    const checkboxes = document.querySelectorAll('.booking-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one booking to reject');
        return;
    }
    
    const bookingIds = Array.from(checkboxes).map(cb => cb.value);
    const count = bookingIds.length;
    
    if (confirm(`Are you sure you want to reject ${count} booking(s)? This action cannot be undone.`)) {
        // Create and submit hidden form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;
        
        // Add booking IDs as hidden inputs
        bookingIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'booking_ids[]';
            input.value = id;
            form.appendChild(input);
        });
        
        // Add bulk reject flag
        const flagInput = document.createElement('input');
        flagInput.type = 'hidden';
        flagInput.name = 'bulk_reject_bookings';
        flagInput.value = '1';
        form.appendChild(flagInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
document.addEventListener('click', (e) => {
    const modal = document.getElementById('confirmModal');
    if (e.target === modal) {
        closeConfirmModal();
    }
});

// Close suspend modal when clicking outside
document.addEventListener('click', (e) => {
    const modal = document.getElementById('suspendModal');
    if (e.target === modal) {
        closeSuspendModal();
    }
});

// Close reject user modal when clicking outside
document.addEventListener('click', (e) => {
    const modal = document.getElementById('rejectUserModal');
    if (e.target === modal) {
        closeRejectUserModal();
    }
});

// Close activate modal when clicking outside
document.addEventListener('click', (e) => {
    const modal = document.getElementById('activateModal');
    if (e.target === modal) {
        closeActivateModal();
    }
});

// Close delete announcement modal when clicking outside
document.addEventListener('click', (e) => {
    const modal = document.getElementById('deleteAnnouncementModal');
    if (e.target === modal) {
        closeDeleteAnnouncementModal();
    }
});

// Close delete event modal when clicking outside
document.addEventListener('click', (e) => {
    const modal = document.getElementById('deleteEventModal');
    if (e.target === modal) {
        closeDeleteEventModal();
    }
});

// Close resolve feedback modal when clicking outside
document.addEventListener('click', (e) => {
    const modal = document.getElementById('resolveFeedbackModal');
    if (e.target === modal) {
        closeResolveFeedbackModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeConfirmModal();
        closeSuspendModal();
        closeActivateModal();
        closeDeleteAnnouncementModal();
        closeDeleteEventModal();
        closeResolveFeedbackModal();
    }
});

// User Approval Functions (old function removed - now using modal)

// Reject User Modal Functions
let pendingRejectUserId = null;

function rejectUser(userId, userName) {
    pendingRejectUserId = userId;
    const modal = document.getElementById('rejectUserModal');
    if (modal) {
        // Update modal content with user name
        document.getElementById('rejectUserName').textContent = userName;
        modal.classList.add('show');
    }
}

function closeRejectUserModal() {
    const modal = document.getElementById('rejectUserModal');
    if (modal) {
        modal.classList.remove('show');
    }
    pendingRejectUserId = null;
}

function confirmRejectUser() {
    if (!pendingRejectUserId) {
        closeRejectUserModal();
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `<input type="hidden" name="user_id" value="${pendingRejectUserId}"><input type="hidden" name="reject_user" value="1">`;
    document.body.appendChild(form);
    form.submit();
}

// Suspend User Modal Functions
let pendingSuspendData = null;

function suspendUser(userId, userName) {
    pendingSuspendData = { users: [{ userId, userName }], isMultiple: false };
    
    // Update modal content for single user
    document.getElementById('suspendModalTitle').textContent = 'Suspend User?';
    document.getElementById('suspendModalMessage').style.display = 'block';
    document.getElementById('suspendMultipleUsers').style.display = 'none';
    document.getElementById('suspendUserName').textContent = userName;
    document.querySelector('#suspendModal .suspend-btn').textContent = 'Yes, Suspend';
    
    document.getElementById('suspendModal').classList.add('show');
}

function suspendSelectedUsers() {
    const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
    if (selectedCheckboxes.length === 0) {
        alert('Please select at least one user to suspend.');
        return;
    }
    
    const selectedUsers = Array.from(selectedCheckboxes).map(checkbox => ({
        userId: checkbox.value,
        userName: checkbox.getAttribute('data-name')
    }));
    
    pendingSuspendData = { users: selectedUsers, isMultiple: true };
    
    // Update modal content for multiple users
    document.getElementById('suspendModalTitle').textContent = `Suspend ${selectedUsers.length} User${selectedUsers.length > 1 ? 's' : ''}?`;
    document.getElementById('suspendModalMessage').style.display = 'none';
    document.getElementById('suspendMultipleUsers').style.display = 'block';
    
    // Populate the users list
    const usersList = document.getElementById('suspendUsersList');
    usersList.innerHTML = selectedUsers.map(user => `<div>• ${user.userName} (${user.userId})</div>`).join('');
    
    document.querySelector('#suspendModal .suspend-btn').textContent = `Yes, Suspend ${selectedUsers.length}`;
    
    document.getElementById('suspendModal').classList.add('show');
}

function toggleSelectAllUsers() {
    const selectAllCheckbox = document.getElementById('selectAllUsers');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    
    userCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

function closeSuspendModal() {
    document.getElementById('suspendModal').classList.remove('show');
    pendingSuspendData = null;
}

function confirmSuspend() {
    if (pendingSuspendData !== null) {
        const { users } = pendingSuspendData;
        
        if (users.length === 1) {
            // Single user suspension
            const { userId } = users[0];
            window.location.href = `admin_dashboard.php?page=users&suspend_user=${userId}`;
        } else {
            // Multiple users suspension - redirect with multiple user IDs
            const userIds = users.map(user => user.userId).join(',');
            window.location.href = `admin_dashboard.php?page=users&suspend_multiple=${userIds}`;
        }
    }
}

// Activate User Modal Functions
let pendingActivateData = null;

function activateUser(userId, userName) {
    pendingActivateData = { users: [{ userId, userName }], isMultiple: false };
    
    // Update modal content for single user
    document.getElementById('activateUserName').textContent = userName;
    document.getElementById('activateModalTitle').textContent = 'Activate User?';
    document.getElementById('activateModalMessage').style.display = 'block';
    document.getElementById('activateMultipleUsers').style.display = 'none';
    document.getElementById('activateModalMessage').innerHTML = `Are you sure you want to activate <strong>${userName}</strong>? This will restore their account access.`;
    document.querySelector('#activateModal .activate-btn').textContent = 'Yes, Activate';
    
    document.getElementById('activateModal').classList.add('show');
}

function activateSelectedUsers() {
    const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
    if (selectedCheckboxes.length === 0) {
        alert('Please select at least one user to activate.');
        return;
    }
    
    const selectedUsers = Array.from(selectedCheckboxes).map(checkbox => ({
        userId: checkbox.value,
        userName: checkbox.getAttribute('data-name')
    }));
    
    pendingActivateData = { users: selectedUsers, isMultiple: true };
    
    // Update modal content for multiple users
    document.getElementById('activateModalTitle').textContent = `Activate ${selectedUsers.length} User${selectedUsers.length > 1 ? 's' : ''}?`;
    document.getElementById('activateModalMessage').style.display = 'none';
    document.getElementById('activateMultipleUsers').style.display = 'block';
    
    // Populate the users list
    const usersList = document.getElementById('activateUsersList');
    usersList.innerHTML = selectedUsers.map(user => `<div>• ${user.userName} (${user.userId})</div>`).join('');
    
    document.querySelector('#activateModal .activate-btn').textContent = `Yes, Activate ${selectedUsers.length}`;
    
    document.getElementById('activateModal').classList.add('show');
}

function closeActivateModal() {
    document.getElementById('activateModal').classList.remove('show');
    pendingActivateData = null;
}

function confirmActivate() {
    if (pendingActivateData !== null) {
        const { users, isMultiple } = pendingActivateData;
        
        if (isMultiple && users.length > 1) {
            // Multiple users activation - redirect with multiple user IDs
            const userIds = users.map(user => user.userId).join(',');
            window.location.href = `admin_dashboard.php?page=users&activate_multiple=${userIds}`;
        } else {
            // Single user activation
            const { userId } = users[0];
            // Create and submit hidden form
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="user_id" value="${userId}"><input type="hidden" name="activate_user" value="1">`;
            document.body.appendChild(form);
            form.submit();
        }
    }
}

// Approve User Modal Functions
let pendingApproveData = null;

function approveUser(userId, userName) {
    pendingApproveData = { userId, userName };
    
    // Update modal content with user name
    document.getElementById('approveUserName').textContent = userName;
    document.getElementById('approveModal').classList.add('show');
}

function closeApproveModal() {
    document.getElementById('approveModal').classList.remove('show');
    pendingApproveData = null;
}

function confirmApprove() {
    if (pendingApproveData !== null) {
        const { userId } = pendingApproveData;
        // Create and submit hidden form
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="user_id" value="${userId}"><input type="hidden" name="approve_user" value="1">`;
        document.body.appendChild(form);
        form.submit();
    }
}

// Delete Announcement Modal Functions
let pendingDeleteAnnouncementData = null;

function deleteAnnouncement(announcementId, announcementTitle) {
    pendingDeleteAnnouncementData = { announcementId, announcementTitle };
    
    // Update modal content
    document.getElementById('deleteAnnouncementTitle').textContent = announcementTitle;
    
    document.getElementById('deleteAnnouncementModal').classList.add('show');
}

function closeDeleteAnnouncementModal() {
    document.getElementById('deleteAnnouncementModal').classList.remove('show');
    pendingDeleteAnnouncementData = null;
}

function confirmDeleteAnnouncement() {
    if (pendingDeleteAnnouncementData !== null) {
        const { announcementId } = pendingDeleteAnnouncementData;
        // Redirect to delete URL
        window.location.href = `admin_dashboard.php?page=announcements&delete_announcement=${announcementId}`;
    }
}

// Delete Event Modal Functions
let pendingDeleteEventData = null;

function deleteEvent(eventId, eventName) {
    pendingDeleteEventData = { eventId, eventName };
    
    // Update modal content
    document.getElementById('deleteEventName').textContent = eventName;
    
    document.getElementById('deleteEventModal').classList.add('show');
}

function closeDeleteEventModal() {
    document.getElementById('deleteEventModal').classList.remove('show');
    pendingDeleteEventData = null;
}

function confirmDeleteEvent() {
    if (pendingDeleteEventData !== null) {
        const { eventId } = pendingDeleteEventData;
        // Redirect to delete URL
        window.location.href = `admin_dashboard.php?page=events&delete_event=${eventId}`;
    }
}

// Resolve Feedback Modal Functions
let pendingResolveFeedbackData = null;

function closeResolveFeedbackModal() {
    document.getElementById('resolveFeedbackModal').classList.remove('show');
    pendingResolveFeedbackData = null;
}

function confirmResolveFeedback() {
    if (pendingResolveFeedbackData !== null) {
        const { feedbackId } = pendingResolveFeedbackData;
        // Redirect to resolve URL
        window.location.href = `admin_dashboard.php?page=feedback&resolve_feedback=${feedbackId}`;
    }
}


let currentCalendarDate = new Date();
let selectedCalendarType = null; // 'start', 'end', 'editStart', 'editEnd', 'booking'

// Helper: safely parse an ISO date string (YYYY-MM-DD) as a local date (no timezone shift)
function parseIsoDateToLocal(isoDate) {
    if (!isoDate) return null;
    // Expecting format "YYYY-MM-DD"
    const parts = isoDate.split('-');
    if (parts.length !== 3) {
        // Fallback to native parsing if unexpected format
        return new Date(isoDate);
    }
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10);
    const day = parseInt(parts[2], 10);
    if (isNaN(year) || isNaN(month) || isNaN(day)) {
        return new Date(isoDate);
    }
    // Use year, month-1, day to create a local Date at midnight
    return new Date(year, month - 1, day);
}

// Helper: format a Date object as YYYY-MM-DD in local time (avoids UTC shifting)
function formatDateLocal(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function openCalendar(type) {
    selectedCalendarType = type;

    // Clear cached unavailable dates whenever we open the calendar for a new context
    if (type === 'booking') {
        unavailableDates.clear();
        const bookingVenue = document.getElementById('bookingVenueSelect');
        const venueId = bookingVenue ? bookingVenue.value : '';
        if (!venueId) {
            showVenueSelectionAlert('Please select a facility first before choosing a date.');
            return;
        }
    } else if (type === 'start' || type === 'end') {
        unavailableDates.clear();
        const eventVenue = document.getElementById('event_venue_id');
        const venueId = eventVenue ? eventVenue.value : '';
        if (!venueId || venueId.trim() === '') {
            showVenueSelectionAlert('Please select a venue first before choosing an event date.');
            return;
        }
    } else if (type === 'editStart' || type === 'editEnd') {
        unavailableDates.clear();
        const editEventVenue = document.getElementById('edit_event_venue_id');
        const venueId = editEventVenue ? editEventVenue.value : '';
        if (!venueId || venueId.trim() === '') {
            showVenueSelectionAlert('Please select a venue first before choosing an event date.');
            return;
        }
    }

    document.getElementById('calendarPopup').style.display = 'flex';
    renderCalendar();
}

function closeCalendar() {
    document.getElementById('calendarPopup').style.display = 'none';
    selectedCalendarType = null;
}

let unavailableDates = new Set(); // Store dates that are fully booked or have events

async function checkDateAvailability(venueId, dateString) {
    try {
        const response = await fetch(`check_availability.php?venue_id=${encodeURIComponent(venueId)}&date=${dateString}`);
        const result = await response.json();
        
        let bookingCount = 0;
        let hasEvent = false;
        let eventName = '';
        
        if (result.success) {
            const bookedTimes = result.data.booked_times || [];
            // Get actual booking count (separate from event-blocked times)
            bookingCount = result.data.booking_count || 0;
            // If all time slots are booked, mark date as unavailable
            // Standard time slots: 7:00 AM to 9:00 PM (hourly = 16 slots)
            const totalSlots = 16;
            if (bookedTimes.length >= totalSlots) {
                unavailableDates.add(dateString);
                return { unavailable: true, fullyBooked: true, bookingCount: bookingCount, hasEvent: false, eventName: '' };
            }
            
            // For event calendars (start, end, editStart, editEnd), mark dates with ANY bookings as unavailable
            const isEventCalendar = selectedCalendarType === 'start' || selectedCalendarType === 'end' || 
                                    selectedCalendarType === 'editStart' || selectedCalendarType === 'editEnd';
            if (isEventCalendar && bookingCount > 0) {
                unavailableDates.add(dateString);
                return { unavailable: true, fullyBooked: false, bookingCount: bookingCount, hasEvent: false, eventName: '' };
            }
        }
        
        // Also check for events
        try {
            const eventResponse = await fetch(`get_events.php?venue_id=${encodeURIComponent(venueId)}`);
            const eventData = await eventResponse.json();
            if (eventData.success && Array.isArray(eventData.events)) {
                const checkDate = new Date(dateString + 'T00:00:00');
                for (const event of eventData.events) {
                    const startDate = new Date(event.start_date + 'T00:00:00');
                    const endDate = new Date((event.end_date || event.start_date) + 'T00:00:00');
                    if (checkDate >= startDate && checkDate <= endDate) {
                        hasEvent = true;
                        eventName = event.name || 'Unknown Event';
                        unavailableDates.add(dateString);
                        return { unavailable: true, fullyBooked: false, bookingCount: bookingCount, hasEvent: true, eventName: eventName };
                    }
                }
            }
        } catch (e) {
            console.error('Error checking events:', e);
        }
        
        unavailableDates.delete(dateString);
        return { unavailable: false, fullyBooked: false, bookingCount: bookingCount, hasEvent: false, eventName: '' };
    } catch (error) {
        console.error('Error checking availability:', error);
        return { unavailable: false, fullyBooked: false, bookingCount: 0, hasEvent: false, eventName: '' };
    }
}

async function renderCalendar() {
    const monthYear = document.getElementById('calendarMonthYear');
    const daysContainer = document.getElementById('calendarDays');

    const month = currentCalendarDate.getMonth();
    const year = currentCalendarDate.getFullYear();

    monthYear.textContent = new Date(year, month).toLocaleDateString('en-US', {
        month: 'long',
        year: 'numeric'
    });

    daysContainer.innerHTML = '';

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDate = firstDay.getDay();
    const totalDays = lastDay.getDate();

    // Previous month days
    const prevMonthLastDay = new Date(year, month, 0).getDate();
    for (let i = startDate - 1; i >= 0; i--) {
        const dayEl = document.createElement('div');
        dayEl.className = 'calendar-day other-month';
        dayEl.textContent = prevMonthLastDay - i;
        daysContainer.appendChild(dayEl);
    }

    // Current month days
    const today = new Date();
    // Determine which venue we are checking availability for
    let venueId = null;
    if (selectedCalendarType === 'booking') {
        const bookingVenue = document.getElementById('bookingVenueSelect');
        venueId = bookingVenue ? bookingVenue.value : null;
    } else if (selectedCalendarType === 'start' || selectedCalendarType === 'end') {
        const eventVenue = document.getElementById('event_venue_id');
        venueId = eventVenue ? eventVenue.value : null;
    } else if (selectedCalendarType === 'editStart' || selectedCalendarType === 'editEnd') {
        const editEventVenue = document.getElementById('edit_event_venue_id');
        venueId = editEventVenue ? editEventVenue.value : null;
    }

    for (let day = 1; day <= totalDays; day++) {
        const dayEl = document.createElement('div');
        dayEl.className = 'calendar-day';
        dayEl.textContent = day;

        const currentDate = new Date(year, month, day);
        // Build YYYY-MM-DD using local time to avoid timezone-related off-by-one issues
        const dateString = formatDateLocal(currentDate);

        // Check if it's today
        if (currentDate.toDateString() === today.toDateString()) {
            dayEl.classList.add('today');
        }

        // Check if date is in the past (disable past dates)
        if (currentDate < new Date(today.getFullYear(), today.getMonth(), today.getDate())) {
            dayEl.classList.add('disabled');
        } else {
            // For booking & event calendars, check availability per date
            if (venueId && (
                selectedCalendarType === 'booking' ||
                selectedCalendarType === 'start' ||
                selectedCalendarType === 'end' ||
                selectedCalendarType === 'editStart' ||
                selectedCalendarType === 'editEnd'
            )) {
                // Check if date is unavailable (fully booked or has event)
                if (unavailableDates.has(dateString)) {
                    dayEl.classList.add('disabled');
                    dayEl.title = 'This date is fully booked or has an event';
                } else {
                    // Async check availability (don't block rendering)
                    checkDateAvailability(venueId, dateString).then(result => {
                        if (result.unavailable) {
                            dayEl.classList.add('disabled');
                            // Update title based on why it's unavailable
                            if (result.hasEvent) {
                                dayEl.title = `This date has an event: "${result.eventName}"`;
                            } else if (result.bookingCount > 0) {
                                dayEl.title = `This date has ${result.bookingCount} existing booking(s)`;
                            } else if (result.fullyBooked) {
                                dayEl.title = 'This date is fully booked';
                            } else {
                                dayEl.title = 'This date is not available';
                            }
                        } else {
                            // For event calendars, add click handler that checks availability and shows popup
                            if (selectedCalendarType === 'start' || selectedCalendarType === 'end' || selectedCalendarType === 'editStart' || selectedCalendarType === 'editEnd') {
                                dayEl.onclick = async () => {
                                    const availability = await checkDateAvailability(venueId, dateString);
                                    if (availability.unavailable) {
                                        // Build detailed message with HTML for event name styling
                                        let message = 'This date is not available for events.';
                                        if (availability.hasEvent) {
                                            const escapedEventName = escapeHtml(availability.eventName);
                                            message += `\n\nThere is already an event scheduled: <strong style="color: #000; font-weight: 700;">"${escapedEventName}"</strong>`;
                                        }
                                        if (availability.bookingCount > 0) {
                                            message += `\n\n⚠️ This date has ${availability.bookingCount} existing booking(s).`;
                                        }
                                        if (availability.fullyBooked) {
                                            message += '\n\nThis date is fully booked.';
                                        }
                                        showDateAvailabilityAlert(message, true);
                                    } else {
                                        selectDate(dateString);
                                    }
                                };
                            } else {
                                dayEl.onclick = () => selectDate(dateString);
                            }
                        }
                    });
                    // Set temporary onclick for booking calendar
                    if (selectedCalendarType === 'booking') {
                        dayEl.onclick = () => selectDate(dateString);
                    } else {
                        // For event calendars, set async click handler
                        dayEl.onclick = async () => {
                            const availability = await checkDateAvailability(venueId, dateString);
                            if (availability.unavailable) {
                                // Build detailed message with HTML for event name styling
                                let message = 'This date is not available for events.';
                                if (availability.hasEvent) {
                                    const escapedEventName = escapeHtml(availability.eventName);
                                    message += `\n\nThere is already an event scheduled: <strong style="color: #000; font-weight: 700;">"${escapedEventName}"</strong>`;
                                }
                                if (availability.bookingCount > 0) {
                                    message += `\n\n⚠️ This date has ${availability.bookingCount} existing booking(s).`;
                                }
                                if (availability.fullyBooked) {
                                    message += '\n\nThis date is fully booked.';
                                }
                                showDateAvailabilityAlert(message, true);
                            } else {
                                selectDate(dateString);
                            }
                        };
                    }
                }
            } else {
                dayEl.onclick = () => selectDate(dateString);
            }
        }

        daysContainer.appendChild(dayEl);
    }

    // Next month days
    const remainingCells = 42 - (startDate + totalDays);
    for (let day = 1; day <= remainingCells; day++) {
        const dayEl = document.createElement('div');
        dayEl.className = 'calendar-day other-month';
        dayEl.textContent = day;
        daysContainer.appendChild(dayEl);
    }
}

async function selectDate(dateString) {
    // For booking calendar, check if date is unavailable
    if (selectedCalendarType === 'booking' && unavailableDates.has(dateString)) {
        showDateAvailabilityAlert('This date is fully booked or has an event. Please select a different date.');
        return;
    }
    
    // For event calendars, check availability before selecting
    if ((selectedCalendarType === 'start' || selectedCalendarType === 'end' || selectedCalendarType === 'editStart' || selectedCalendarType === 'editEnd')) {
        const venueIdForEvent = selectedCalendarType === 'start' || selectedCalendarType === 'end' 
            ? document.getElementById('event_venue_id')?.value 
            : document.getElementById('edit_event_venue_id')?.value;
        if (venueIdForEvent) {
            const availability = await checkDateAvailability(venueIdForEvent, dateString);
            if (availability.unavailable) {
                // Build detailed message with HTML for event name styling
                let message = 'This date is not available for events.';
                if (availability.hasEvent) {
                    const escapedEventName = escapeHtml(availability.eventName);
                    message += `\n\nThere is already an event scheduled: <strong style="color: #000; font-weight: 700;">"${escapedEventName}"</strong>`;
                }
                if (availability.bookingCount > 0) {
                    message += `\n\n⚠️ This date has ${availability.bookingCount} existing booking(s).`;
                }
                if (availability.fullyBooked) {
                    message += '\n\nThis date is fully booked.';
                }
                showDateAvailabilityAlert(message, true);
                return;
            }
        }
    }

    // Use local date parsing to avoid timezone shifting (off‑by‑one day issue)
    const date = parseIsoDateToLocal(dateString);
    const formattedDate = date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    let displayElement, hiddenElement, textElement;

    switch (selectedCalendarType) {
        case 'start':
            displayElement = document.getElementById('startDateDisplay');
            hiddenElement = document.getElementById('event_start_date');
            textElement = document.getElementById('startSelectedDateText');
            break;
        case 'end':
            displayElement = document.getElementById('endDateDisplay');
            hiddenElement = document.getElementById('event_end_date');
            textElement = document.getElementById('endSelectedDateText');
            break;
        case 'editStart':
            displayElement = document.getElementById('editStartDateDisplay');
            hiddenElement = document.getElementById('edit_event_start_date');
            textElement = document.getElementById('editStartSelectedDateText');
            break;
        case 'editEnd':
            displayElement = document.getElementById('editEndDateDisplay');
            hiddenElement = document.getElementById('edit_event_end_date');
            textElement = document.getElementById('editEndSelectedDateText');
            break;
        case 'booking':
            displayElement = document.getElementById('bookingDateDisplay');
            hiddenElement = document.getElementById('booking_date');
            textElement = document.getElementById('bookingSelectedDateText');
            // Clear selected times when date changes
            selectedBookingTimes = [];
            const bookingTimesInput = document.getElementById('booking_times');
            if (bookingTimesInput) bookingTimesInput.value = '';
            const bookingTimeText = document.getElementById('bookingSelectedTimeText');
            if (bookingTimeText) bookingTimeText.textContent = 'Choose booking time(s)';
            break;
    }

    if (displayElement && hiddenElement && textElement) {
        textElement.textContent = formattedDate;
        hiddenElement.value = dateString;
        displayElement.classList.add('selected');
    }

    closeCalendar();
    
    // For booking, trigger availability check for time picker
    if (selectedCalendarType === 'booking') {
        const venueId = document.getElementById('bookingVenueSelect')?.value;
        if (venueId) {
            checkBookingAvailability(venueId, dateString);
        }
    }
}

document.getElementById('prevMonth').onclick = () => {
    currentCalendarDate.setMonth(currentCalendarDate.getMonth() - 1);
    renderCalendar();
};

document.getElementById('nextMonth').onclick = () => {
    currentCalendarDate.setMonth(currentCalendarDate.getMonth() + 1);
    renderCalendar();
};

// Close calendar when clicking outside
document.addEventListener('click', (e) => {
    const calendar = document.getElementById('calendarPopup');
    if (e.target === calendar) {
        closeCalendar();
    }
});

// Close calendar on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeCalendar();
        closeTimePicker();
    }
});

// ===== TIME PICKER FUNCTIONALITY =====
let selectedTimeType = null;
let selectedBookingTimes = []; // Array to store multiple selected times for booking

const timeSlots = [
    "7:00 AM", "8:00 AM", "9:00 AM", "10:00 AM", "11:00 AM", "12:00 PM",
    "1:00 PM", "2:00 PM", "3:00 PM", "4:00 PM", "5:00 PM", "6:00 PM",
    "7:00 PM", "8:00 PM", "9:00 PM"
];

let bookedTimesForDate = []; // Store booked times for the selected date/venue

async function openTimePicker(type) {
    selectedTimeType = type;

    // If opening booking time picker, restore any previously selected times
    if (type === 'booking') {
        const hiddenInput = document.getElementById('booking_times');
        if (hiddenInput && hiddenInput.value) {
            selectedBookingTimes = hiddenInput.value
                .split(',')
                .map(t => t.trim())
                .filter(Boolean);
        } else {
            selectedBookingTimes = [];
        }

        // Load availability for selected date/venue (if chosen)
        const bookingVenueEl = document.getElementById('bookingVenueSelect');
        const bookingDateEl = document.getElementById('booking_date');
        const venueId = bookingVenueEl ? bookingVenueEl.value : '';
        const bookingDate = bookingDateEl ? bookingDateEl.value : '';
        if (venueId && bookingDate) {
            await checkBookingAvailability(venueId, bookingDate);
        } else {
            bookedTimesForDate = [];
            renderTimePicker();
        }
    } else {
        // Single-time selection (events). Load availability if we have venue & date.
        bookedTimesForDate = [];
        let venueId = null;
        let dateForCheck = null;

        if (type === 'start' || type === 'end') {
            const eventVenueEl = document.getElementById('event_venue_id');
            venueId = eventVenueEl ? eventVenueEl.value : '';
            const dateElId = type === 'start' ? 'event_start_date' : 'event_end_date';
            const dateEl = document.getElementById(dateElId);
            dateForCheck = dateEl ? dateEl.value : '';
        } else if (type === 'editStart' || type === 'editEnd') {
            const editEventVenueEl = document.getElementById('edit_event_venue_id');
            venueId = editEventVenueEl ? editEventVenueEl.value : '';
            const dateElId = type === 'editStart' ? 'edit_event_start_date' : 'edit_event_end_date';
            const dateEl = document.getElementById(dateElId);
            dateForCheck = dateEl ? dateEl.value : '';
        }

        if (venueId && dateForCheck) {
            await checkBookingAvailability(venueId, dateForCheck);
        } else {
            // If venue/date missing, just show all slots (no availability info)
            bookedTimesForDate = [];
            renderTimePicker();
        }
    }

    document.getElementById('timePickerPopup').style.display = 'flex';
    renderTimePicker();
}

function closeTimePicker() {
    document.getElementById('timePickerPopup').style.display = 'none';
    selectedTimeType = null;
}

async function checkBookingAvailability(venueId, date) {
    try {
        const response = await fetch(`check_availability.php?venue_id=${encodeURIComponent(venueId)}&date=${date}`);
        const result = await response.json();
        
        if (result.success) {
            bookedTimesForDate = result.data.booked_times || [];
        } else {
            bookedTimesForDate = [];
        }
    } catch (error) {
        console.error('Error checking availability:', error);
        bookedTimesForDate = [];
    }
    renderTimePicker();
}

function renderTimePicker() {
    const timeGrid = document.getElementById('timePickerGrid');
    timeGrid.innerHTML = '';

    // For booking type, show multiple selection with availability
    if (selectedTimeType === 'booking') {
        timeSlots.forEach(time => {
            const timeSlot = document.createElement('div');
            timeSlot.className = 'time-slot';
            
            // Normalize time format for comparison
            const normalizeTime = (t) => t.replace(/\s+/g, ' ').trim().toUpperCase();
            const normalizedTime = normalizeTime(time);
            const isBooked = bookedTimesForDate.some(bt => normalizeTime(bt) === normalizedTime);
            const isSelected = selectedBookingTimes.some(st => normalizeTime(st) === normalizeTime(time));
            
            if (isBooked) {
                timeSlot.classList.add('booked');
                timeSlot.style.opacity = '0.5';
                timeSlot.style.cursor = 'not-allowed';
                timeSlot.title = 'This time slot is already booked';
                // Add click handler to show alert when clicking unavailable time
                timeSlot.onclick = () => {
                    showTimeAvailabilityAlert(`The time slot ${time} is not available.\n\nThis time slot is already booked by another user. Please select a different time.`);
                };
            } else {
                if (isSelected) {
                    timeSlot.classList.add('selected');
                }
                timeSlot.onclick = () => toggleBookingTime(time);
            }
            
            timeSlot.textContent = time;
            timeGrid.appendChild(timeSlot);
        });
        
        // Add a "Done" button for booking type
        const doneBtn = document.createElement('button');
        doneBtn.textContent = 'Done';
        doneBtn.style.cssText = 'margin-top: 15px; padding: 10px 20px; background: #f57c00; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 500;';
        doneBtn.onclick = () => {
            updateBookingTimeDisplay();
            closeTimePicker();
        };
        timeGrid.appendChild(doneBtn);
    } else {
        // Single selection for other types (events)
        timeSlots.forEach(time => {
            const timeSlot = document.createElement('div');
            timeSlot.className = 'time-slot';

            // Normalize time format for comparison
            const normalizeTime = (t) => t.replace(/\s+/g, ' ').trim().toUpperCase();
            const normalizedTime = normalizeTime(time);
            const isBooked = bookedTimesForDate.some(bt => normalizeTime(bt) === normalizedTime);

            if (isBooked) {
                timeSlot.classList.add('booked');
                timeSlot.style.opacity = '0.5';
                timeSlot.style.cursor = 'not-allowed';
                timeSlot.title = 'This time slot is already booked or blocked by an existing event';
                // Add click handler to show alert when clicking unavailable time
                timeSlot.onclick = () => {
                    showTimeAvailabilityAlert(`The time slot ${time} is not available.\n\nThis time slot is already booked or blocked by an existing event. Please select a different time.`);
                };
            } else {
                timeSlot.onclick = () => selectTime(time);
            }

            timeSlot.textContent = time;
            timeGrid.appendChild(timeSlot);
        });
    }
}

function toggleBookingTime(timeString) {
    const normalizeTime = (t) => t.replace(/\s+/g, ' ').trim().toUpperCase();
    const normalizedTime = normalizeTime(timeString);
    const index = selectedBookingTimes.findIndex(st => normalizeTime(st) === normalizedTime);
    
    if (index > -1) {
        // Deselect
        selectedBookingTimes.splice(index, 1);
    } else {
        // Select (max 2 hours = 4 slots for 30-min intervals, but we'll allow more)
        selectedBookingTimes.push(timeString);
    }
    
    renderTimePicker();
}

function updateBookingTimeDisplay() {
    const displayElement = document.getElementById('bookingTimeDisplay');
    const hiddenElement = document.getElementById('booking_times');
    const textElement = document.getElementById('bookingSelectedTimeText');
    
    if (selectedBookingTimes.length === 0) {
        if (textElement) textElement.textContent = 'Choose booking time(s)';
        if (hiddenElement) hiddenElement.value = '';
        if (displayElement) displayElement.classList.remove('selected');
    } else {
        if (textElement) {
            // Sort times for better display (simple string sort works for 12-hour format)
            const sortedTimes = [...selectedBookingTimes].sort((a, b) => {
                // Simple comparison: AM comes before PM, then compare hours
                const aIsPM = a.includes('PM');
                const bIsPM = b.includes('PM');
                if (aIsPM !== bIsPM) {
                    return aIsPM ? 1 : -1;
                }
                // Extract hour for comparison
                const aHour = parseInt(a.split(':')[0]);
                const bHour = parseInt(b.split(':')[0]);
                return aHour - bHour;
            });
            
            // Display all selected times, comma-separated
            if (sortedTimes.length === 1) {
                textElement.textContent = sortedTimes[0];
            } else if (sortedTimes.length <= 4) {
                // Show all times if 4 or fewer
                textElement.textContent = sortedTimes.join(', ');
            } else {
                // Show first 3 times and count if more than 4
                textElement.textContent = sortedTimes.slice(0, 3).join(', ') + ` (+${sortedTimes.length - 3} more)`;
            }
        }
        if (hiddenElement) {
            // Store as comma-separated string (12-hour format)
            hiddenElement.value = selectedBookingTimes.join(',');
        }
        if (displayElement) displayElement.classList.add('selected');
    }
}

function selectTime(timeString) {
    // Convert display time to 24-hour format for form submission
    const time24 = convertTo24Hour(timeString);

    let displayElement, hiddenElement, textElement;

    switch (selectedTimeType) {
        case 'start':
            displayElement = document.getElementById('startTimeDisplay');
            hiddenElement = document.getElementById('event_start_time');
            textElement = document.getElementById('startSelectedTimeText');
            break;
        case 'end':
            displayElement = document.getElementById('endTimeDisplay');
            hiddenElement = document.getElementById('event_end_time');
            textElement = document.getElementById('endSelectedTimeText');
            break;
        case 'editStart':
            displayElement = document.getElementById('editStartTimeDisplay');
            hiddenElement = document.getElementById('edit_event_start_time');
            textElement = document.getElementById('editStartSelectedTimeText');
            break;
        case 'editEnd':
            displayElement = document.getElementById('editEndTimeDisplay');
            hiddenElement = document.getElementById('edit_event_end_time');
            textElement = document.getElementById('editEndSelectedTimeText');
            break;
        case 'booking':
            // Booking type uses toggleBookingTime instead
            return;
    }

    if (displayElement && hiddenElement && textElement) {
        textElement.textContent = timeString;
        hiddenElement.value = time24;
        displayElement.classList.add('selected');
    }

    closeTimePicker();
}

function convertTo24Hour(timeString) {
    const [time, period] = timeString.split(' ');
    let [hours, minutes] = time.split(':');
    hours = parseInt(hours);

    if (period === 'PM' && hours !== 12) {
        hours += 12;
    } else if (period === 'AM' && hours === 12) {
        hours = 0;
    }

    return `${hours.toString().padStart(2, '0')}:${minutes}`;
}

function convertTo12Hour(time24) {
    const [hours, minutes] = time24.split(':');
    let hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';

    if (hour === 0) hour = 12;
    else if (hour > 12) hour -= 12;

    return `${hour}:${minutes} ${ampm}`;
}

// Close time picker when clicking outside
document.addEventListener('click', (e) => {
    const timePicker = document.getElementById('timePickerPopup');
    if (e.target === timePicker) {
        closeTimePicker();
    }
});

// Helper function to escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Date Availability Alert Functions
function showDateAvailabilityAlert(message, isHtml = false) {
    const alertModal = document.getElementById('dateAvailabilityAlert');
    const messageElement = document.getElementById('dateAvailabilityMessage');
    if (alertModal && messageElement) {
        if (isHtml) {
            // Use innerHTML for HTML content
            messageElement.innerHTML = message;
        } else {
            // Use textContent with white-space: pre-line CSS to preserve line breaks
            messageElement.textContent = message;
        }
        alertModal.classList.add('show');
        alertModal.style.display = 'flex';
        alertModal.style.zIndex = '10000'; // Very high z-index to ensure it's on top
    } else {
        console.error('Date availability alert modal elements not found');
        alert(message); // Fallback alert
    }
}

function closeDateAvailabilityAlert() {
    const alertModal = document.getElementById('dateAvailabilityAlert');
    if (alertModal) {
        // Reset title to default
        const titleElement = alertModal.querySelector('h3');
        if (titleElement) {
            titleElement.textContent = 'Date Not Available';
        }
        alertModal.classList.remove('show');
        alertModal.style.display = 'none';
        alertModal.style.zIndex = '';
    }
}

// Time Availability Alert Functions (reuses date availability modal)
function showTimeAvailabilityAlert(message) {
    const alertModal = document.getElementById('dateAvailabilityAlert');
    const messageElement = document.getElementById('dateAvailabilityMessage');
    if (alertModal && messageElement) {
        // Update title for time alerts
        const titleElement = alertModal.querySelector('h3');
        if (titleElement) {
            titleElement.textContent = 'Time Not Available';
        }
        // Use textContent with white-space: pre-line CSS to preserve line breaks
        messageElement.textContent = message;
        alertModal.classList.add('show');
        alertModal.style.display = 'flex';
        alertModal.style.zIndex = '10000'; // Very high z-index to ensure it's on top
    } else {
        console.error('Time availability alert modal elements not found');
        alert(message); // Fallback alert
    }
}

function closeTimeAvailabilityAlert() {
    // Reuse the same close function
    closeDateAvailabilityAlert();
}

// Venue Selection Alert Functions
function showVenueSelectionAlert(message) {
    console.log('showVenueSelectionAlert called with message:', message);
    const alertModal = document.getElementById('venueSelectionAlert');
    const messageElement = document.getElementById('venueSelectionMessage');
    
    if (!alertModal) {
        console.error('venueSelectionAlert modal not found in DOM');
        alert('Please select a venue first before choosing an event date.'); // Fallback alert
        return;
    }
    
    if (!messageElement) {
        console.error('venueSelectionMessage element not found in DOM');
        alert('Please select a venue first before choosing an event date.'); // Fallback alert
        return;
    }
    
    messageElement.textContent = message;
    alertModal.classList.add('show');
    // Ensure modal is displayed with high z-index
    alertModal.style.display = 'flex';
    alertModal.style.zIndex = '10000'; // Very high z-index to ensure it's on top
    console.log('Alert modal should now be visible');
}

function closeVenueSelectionAlert() {
    const alertModal = document.getElementById('venueSelectionAlert');
    if (alertModal) {
        alertModal.classList.remove('show');
        alertModal.style.display = 'none';
        alertModal.style.zIndex = '';
    }
}

// Event listener for venue selection change - refresh calendar availability
document.addEventListener('DOMContentLoaded', function() {
    const venueSelect = document.getElementById('bookingVenueSelect');
    if (venueSelect) {
        venueSelect.addEventListener('change', function() {
            // Clear unavailable dates cache
            unavailableDates.clear();
            // If calendar is open, re-render to update availability
            const calendarPopup = document.getElementById('calendarPopup');
            if (calendarPopup && calendarPopup.style.display === 'flex' && selectedCalendarType === 'booking') {
                renderCalendar();
            }
            // Clear selected date and times when venue changes
            const bookingDate = document.getElementById('booking_date');
            if (bookingDate) bookingDate.value = '';
            const bookingDateText = document.getElementById('bookingSelectedDateText');
            if (bookingDateText) bookingDateText.textContent = 'Choose booking date';
            const bookingDateDisplay = document.getElementById('bookingDateDisplay');
            if (bookingDateDisplay) bookingDateDisplay.classList.remove('selected');
            
            selectedBookingTimes = [];
            const bookingTimes = document.getElementById('booking_times');
            if (bookingTimes) bookingTimes.value = '';
            const bookingTimeText = document.getElementById('bookingSelectedTimeText');
            if (bookingTimeText) bookingTimeText.textContent = 'Choose booking time(s)';
            const bookingTimeDisplay = document.getElementById('bookingTimeDisplay');
            if (bookingTimeDisplay) bookingTimeDisplay.classList.remove('selected');
        });
    }

    // When event venue changes, clear event dates/times and availability cache
    const eventVenueSelect = document.getElementById('event_venue_id');
    if (eventVenueSelect) {
        eventVenueSelect.addEventListener('change', function() {
            unavailableDates.clear();
            const calendarPopup = document.getElementById('calendarPopup');
            if (calendarPopup && calendarPopup.style.display === 'flex' &&
                (selectedCalendarType === 'start' || selectedCalendarType === 'end')) {
                renderCalendar();
            }

            const startDate = document.getElementById('event_start_date');
            const endDate = document.getElementById('event_end_date');
            const startDateText = document.getElementById('startSelectedDateText');
            const endDateText = document.getElementById('endSelectedDateText');
            const startDateDisplay = document.getElementById('startDateDisplay');
            const endDateDisplay = document.getElementById('endDateDisplay');

            if (startDate) startDate.value = '';
            if (endDate) endDate.value = '';
            if (startDateText) startDateText.textContent = 'Choose start date';
            if (endDateText) endDateText.textContent = 'Choose end date';
            if (startDateDisplay) startDateDisplay.classList.remove('selected');
            if (endDateDisplay) endDateDisplay.classList.remove('selected');

            const startTime = document.getElementById('event_start_time');
            const endTime = document.getElementById('event_end_time');
            const startTimeText = document.getElementById('startSelectedTimeText');
            const endTimeText = document.getElementById('endSelectedTimeText');
            const startTimeDisplay = document.getElementById('startTimeDisplay');
            const endTimeDisplay = document.getElementById('endTimeDisplay');

            if (startTime) startTime.value = '';
            if (endTime) endTime.value = '';
            if (startTimeText) startTimeText.textContent = 'Choose start time';
            if (endTimeText) endTimeText.textContent = 'Choose end time';
            if (startTimeDisplay) startTimeDisplay.classList.remove('selected');
            if (endTimeDisplay) endTimeDisplay.classList.remove('selected');
        });
    }
    
    // Close date availability alert when clicking outside
    const dateAvailabilityAlert = document.getElementById('dateAvailabilityAlert');
    if (dateAvailabilityAlert) {
        dateAvailabilityAlert.addEventListener('click', function(e) {
            if (e.target === dateAvailabilityAlert) {
                closeDateAvailabilityAlert();
            }
        });
    }
    
    // Close venue selection alert when clicking outside
    const venueSelectionAlert = document.getElementById('venueSelectionAlert');
    if (venueSelectionAlert) {
        venueSelectionAlert.addEventListener('click', function(e) {
            if (e.target === venueSelectionAlert) {
                closeVenueSelectionAlert();
            }
        });
    }
});
</script>

<style>
.time-slot.booked {
    background-color: #f5f5f5 !important;
    color: #999 !important;
    cursor: not-allowed !important;
    opacity: 0.5 !important;
}

.time-slot.booked:hover {
    background-color: #f5f5f5 !important;
    transform: none !important;
}

.time-slot.selected {
    background-color: #f57c00 !important;
    color: white !important;
    font-weight: bold !important;
}
</style>

</body>
</html>