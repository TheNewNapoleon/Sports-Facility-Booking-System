<?php
session_start();
require_once "db.php";

// Ensure user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Function to fetch user data by ID
function getUser($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
    $stmt->bind_param("s", $user_id); // user_id is VARCHAR, use "s"
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $user;
}

// Fetch logged-in user
$user = getUser($conn, $user_id);

// Default values
$user['avatar_path'] = $user['avatar_path'] ?: "images/avatar/default.png";
$user['name'] = $user['name'] ?: "Guest";

$msg_success = '';
$msg_error = '';
$open_tab = 'editDetailsTab';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_type = $_POST['form_type'] ?? '';

    if ($form_type === 'details') {
        $open_tab = 'editDetailsTab';
        $living_place = trim($_POST['living_place'] ?? '');
        $phone_number = trim($_POST['phone_number'] ?? '');
        $date_of_birth = $_POST['date_of_birth'] ?? null;
        $gender = $_POST['gender'] ?? null;

        if ($phone_number && !preg_match('/^[0-9+\-\s()]{4,20}$/', $phone_number)) {
            $msg_error = "Phone number format is invalid.";
        } else {
            // Handle profile picture upload
            $avatar_path = $user['avatar_path'];
            if (isset($_FILES['avatar']) && $_FILES['avatar']['size'] > 0) {
                $fileTmp = $_FILES['avatar']['tmp_name'];
                $fileName = $_FILES['avatar']['name'];
                $fileError = $_FILES['avatar']['error'];

                if ($fileError === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

                    if (in_array($ext, $allowed)) {
                        $targetDir = "images/avatar/";
                        if (!is_dir($targetDir)) {
                            mkdir($targetDir, 0755, true);
                        }
                        $newName = "avatar_" . $user_id . "_" . time() . "." . $ext;
                        $targetFile = $targetDir . $newName;

                        if (move_uploaded_file($fileTmp, $targetFile)) {
                            $avatar_path = $targetFile;
                        } else {
                            $msg_error = "Failed to upload profile picture.";
                        }
                    } else {
                        $msg_error = "Invalid file type. Allowed: jpg, jpeg, png, gif.";
                    }
                } else {
                    $msg_error = "File upload error.";
                }
            }

            if (!$msg_error) {
                $upd = $conn->prepare("UPDATE users SET living_place=?, phone_number=?, date_of_birth=?, gender=?, avatar_path=? WHERE user_id=?");
                $upd->bind_param("ssssss", $living_place, $phone_number, $date_of_birth, $gender, $avatar_path, $user_id);
                $msg_success = $upd->execute() ? "Profile updated successfully." : "Failed to update details.";
                $upd->close();
            }
        }
    } elseif ($form_type === 'password') {
        $open_tab = 'editPasswordTab';
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $repeat_password = $_POST['repeat_password'] ?? '';

        if (!$current_password || !$new_password || !$repeat_password) {
            $msg_error = "Please fill all password fields.";
        } elseif ($new_password !== $repeat_password) {
            $msg_error = "New password and repeated password do not match.";
        } elseif (!password_verify($current_password, $user['password'])) {
            $msg_error = "Current password is incorrect.";
        } else {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
            $upd->bind_param("ss", $hashed_password, $user_id);
            $msg_success = $upd->execute() ? "Password updated successfully." : "Failed to update password.";
            $upd->close();
        }
    }

    // Re-fetch user after update
    $user = getUser($conn, $user_id);
    $user['avatar_path'] = $user['avatar_path'] ?: "images/default_avatar.png";
    $user['name'] = $user['name'] ?: "Guest";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/search_notification.css">
    <link rel="stylesheet" href="css/events_announcements.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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
        width: 80px !important;
        height: 80px !important;
        border-radius: 50% !important;
        object-fit: cover;
        display: block;
        margin: 0 auto;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .sidebar-header-text {
        display: none !important;
    }
    </style>
</head>
<body class="dashboard-page">

<div class="dashboard-container">

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="images/logo1.png" alt="Logo" class="sidebar-logo">
        </div>
        <a href="dashboard.php">Dashboard</a>
        <a href="profile.php" class="active">Profile</a>
        <a href="booking_facilities.php">Booking Facilities</a>
        <a href="booking_list.php">Booking List</a>
        <a href="feedback.php">Feedback</a>
        <a href="#" id="contactSidebarLink">Contact Us</a>
    </div>

    <!-- Main Area -->
    <div class="main-area">

        <!-- Top Navbar -->
<!-- Top Navbar for profile.php -->
<div class="topnav">
    <div class="nav-left"></div> <!-- empty for spacing -->

    <div class="nav-right">
        <!-- Notification Dropdown -->
        <div class="notification-dropdown">
            <i class="fa-solid fa-bell" id="notifBell"></i>
            <span class="notif-badge" id="notifBadge">0</span>
            <div class="dropdown-content" id="notifPanel">
                <div class="notif-header">
                    <h4><i class="fas fa-bell"></i> Notifications</h4>
                    <button class="mark-all-read" id="markAllRead">
                        <i class="fas fa-check-double"></i> Clear
                    </button>
                </div>
                
                <div class="notif-list" id="notifList">
                            <?php
                            // 1. Tomorrow's reminders
                            $reminders = [];
                            $reminderStmt = $conn->prepare("
                                SELECT b.booking_id, v.name AS venue_name, b.booking_time
                                FROM bookings b
                                JOIN venues v ON b.venue_id = v.venue_id
                                WHERE b.user_id = ? AND b.status = 'booked'
                                AND b.booking_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                            ");
                            $reminderStmt->bind_param("s", $user_id);
                            $reminderStmt->execute();
                            $reminders = $reminderStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            $reminderStmt->close();
                            
                            // 2. Bookings (within 14 days)
                            $bookings = [];
                            $bookingStmt = $conn->prepare("
                                SELECT b.booking_id, v.name AS venue_name, b.booking_date, b.booking_time, b.status
                                FROM bookings b
                                JOIN venues v ON b.venue_id = v.venue_id
                                WHERE b.user_id = ? 
                                AND b.status IN ('booked', 'completed', 'cancelled', 'rejected')
                                AND b.booking_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
                                ORDER BY b.booking_date DESC
                                LIMIT 15
                            ");
                            $bookingStmt->bind_param("s", $user_id);
                            $bookingStmt->execute();
                            $bookings = $bookingStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            $bookingStmt->close();
                            
                            // 3. Feedback (within 14 days)
                            $feedbacks = [];
                            $feedbackStmt = $conn->prepare("
                                SELECT feedback_id, subject, respond_at
                                FROM feedback
                                WHERE user_id = ? AND status = 'reviewed' AND respond IS NOT NULL
                                AND respond_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
                                ORDER BY respond_at DESC
                                LIMIT 10
                            ");
                            $feedbackStmt->bind_param("s", $user_id);
                            $feedbackStmt->execute();
                            $feedbacks = $feedbackStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            $feedbackStmt->close();
                            
                            $hasNotifs = count($reminders) + count($bookings) + count($feedbacks) > 0;
                            
                            if ($hasNotifs):
                                // Reminders first
                                foreach ($reminders as $r):
                            ?>
                                <div class="notif-item reminder" data-id="reminder-<?= $r['booking_id'] ?>">
                                    <div class="notif-icon reminder"><i class="fas fa-bell"></i></div>
                                    <div class="notif-content">
                                        <div class="notif-title"><?= htmlspecialchars($r['venue_name']) ?></div>
                                        <span class="notif-status-badge reminder">Tomorrow</span>
                                        <div class="notif-time"><i class="far fa-clock"></i> <?= date('h:i A', strtotime($r['booking_time'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php foreach ($bookings as $b):
                                $cfg = [
                                    'booked' => ['icon' => 'fa-calendar-check', 'text' => 'Booked'],
                                    'completed' => ['icon' => 'fa-flag-checkered', 'text' => 'Completed'],
                                    'cancelled' => ['icon' => 'fa-times-circle', 'text' => 'Cancelled'],
                                    'rejected' => ['icon' => 'fa-ban', 'text' => 'Rejected']
                                ][$b['status']] ?? ['icon' => 'fa-question-circle', 'text' => ucfirst($b['status'])];
                            ?>
                                <div class="notif-item <?= $b['status'] ?>" data-id="booking-<?= $b['booking_id'] ?>">
                                    <div class="notif-icon <?= $b['status'] ?>"><i class="fas <?= $cfg['icon'] ?>"></i></div>
                                    <div class="notif-content">
                                        <div class="notif-title"><?= htmlspecialchars($b['venue_name']) ?></div>
                                        <span class="notif-status-badge <?= $b['status'] ?>"><?= $cfg['text'] ?></span>
                                        <div class="notif-time">
                                            <i class="far fa-calendar"></i> <?= date('M d', strtotime($b['booking_date'])) ?>
                                            &nbsp;<i class="far fa-clock"></i> <?= date('h:i A', strtotime($b['booking_time'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php foreach ($feedbacks as $f): ?>
                                <div class="notif-item feedback" data-id="feedback-<?= $f['feedback_id'] ?>">
                                    <div class="notif-icon feedback"><i class="fas fa-comment-dots"></i></div>
                                    <div class="notif-content">
                                        <div class="notif-title"><?= htmlspecialchars($f['subject']) ?></div>
                                        <span class="notif-status-badge feedback">Reviewed</span>
                                        <div class="notif-time"><i class="far fa-clock"></i> <?= date('M d, h:i A', strtotime($f['respond_at'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php else: ?>
                                <div class="notif-empty">
                                    <i class="fas fa-bell-slash"></i>
                                    <p>No notifications</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="notif-footer">
                            <a href="booking_list.php">View Bookings</a>
                            <a href="feedback.php">View Feedback</a>
                        </div>
                    </div>
                </div>

        <div class="user-info">
            <?php
                // Ensure we have the correct variables
                $user_avatar_display = $user['avatar_path'] ?: 'images/default_avatar.png';
                $user_name_display = $user['name'] ?: 'Guest';
            ?>
            <img src="<?= htmlspecialchars($user_avatar_display) ?>" class="avatar" alt="Avatar">
            <span><?= htmlspecialchars($user_name_display) ?></span>
        </div>

        <!-- Logout button -->
        <a href="logout.php" class="logout-btn" title="Logout">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>
    </div>
</div>

<!-- JS for notification toggle -->
<script>
    // NOTIFICATION
    const bell = document.getElementById('notifBell');
    const panel = document.getElementById('notifPanel');
    const badge = document.getElementById('notifBadge');
    const markAllBtn = document.getElementById('markAllRead');
    const notifItems = document.querySelectorAll('.notif-item');
    
    const getRead = () => JSON.parse(localStorage.getItem('readNotifs') || '[]');
    const saveRead = (arr) => localStorage.setItem('readNotifs', JSON.stringify(arr));
    
    let readList = getRead();
    let unread = 0;
    
    notifItems.forEach(item => {
        if (readList.includes(item.dataset.id)) {
            item.classList.add('read');
        } else {
            unread++;
        }
    });
    
    const updateBadge = () => {
        badge.textContent = unread > 9 ? '9+' : unread;
        badge.style.display = unread > 0 ? 'flex' : 'none';
    };
    updateBadge();

    bell.addEventListener('click', e => {
        panel.classList.toggle('show');
        e.stopPropagation();
    });

    document.addEventListener('click', e => {
        if (!panel.contains(e.target) && e.target !== bell) panel.classList.remove('show');
    });
    
    notifItems.forEach(item => {
        item.addEventListener('click', function() {
            if (!readList.includes(this.dataset.id)) {
                readList.push(this.dataset.id);
                saveRead(readList);
                this.classList.add('read');
                unread--;
                updateBadge();
            }
        });
    });
    
    markAllBtn.addEventListener('click', e => {
        e.stopPropagation();
        notifItems.forEach(item => {
            if (!readList.includes(item.dataset.id)) {
                readList.push(item.dataset.id);
                item.classList.add('read');
            }
        });
        saveRead(readList);
        unread = 0;
        updateBadge();
    });
</script>


        <!-- Main Content -->
        <div class="main-content">
<section id="contactPanel" class="contact-panel">
    <div class="contact-popout">
        <button class="close-btn" id="closeContactPanel">&times;</button>
        <div class="contact-left">
            <img src="images/tarumt-logo.png" alt="Logo" class="contact-logo-large">
            <h3>Student Sport Club</h3>
            <p>Your campus facility booking system</p>
        </div>
        <div class="contact-right">
            <a href="mailto:sabah@tarc.edu.my" class="info-row">
                <i class="fa-solid fa-envelope"></i> <span>sabah@tarc.edu.my</span>
            </a>
            <a href="https://www.facebook.com/tarumtsabah" class="info-row" target="_blank" rel="noopener">
                <i class="fa-brands fa-facebook"></i> <span>TAR UMT Sabah Branch</span>
            </a>
            <a href="https://www.instagram.com/tarumtsabah" class="info-row" target="_blank" rel="noopener">
                <i class="fa-brands fa-instagram"></i> <span>tarumtsabah</span>
            </a>
            <a href="https://x.com/TARUMT_Sabah" class="info-row" target="_blank" rel="noopener">
                <i class="fa-brands fa-x-twitter"></i> <span>@TARUMT_Sabah</span>
            </a>
            <a href="#" class="info-row">
                <i class="fa-brands fa-whatsapp"></i> <span>(6)011-1082 5619</span>
            </a>
            <a href="https://maps.app.goo.gl/4K9jrb9DcXMdu68JA" class="info-row" target="_blank" rel="noopener">
                <i class="fa-solid fa-location-dot"></i> <span>Jalan Alamesra, Alamesra, 88450 Kota Kinabalu, Sabah.</span>
            </a>
        </div>
    </div>
</section>
<!-- Popout Notification -->
<div id="feedbackPopout" class="feedback-popout" style="display:none;">
    <span id="feedbackMessage"></span>
    <button id="feedbackClose">&times;</button>
</div>


            <div class="profile-sections">

<!-- PROFILE DISPLAY CARD -->
<div class="luxury-card">
    <div class="luxury-wrapper">

<!-- LEFT: Profile Picture -->
<div class="luxury-left">
    <h2 class="section-title">Profile Picture</h2>
    <div class="pfp-box">
        <?php
            // Ensure the avatar path is valid and escaped
            $avatar_display = $user['avatar_path'] ?: 'images/default_avatar.png';
        ?>
        <img src="<?= htmlspecialchars($avatar_display) ?>" alt="Profile Picture">
    </div>
</div>


        <!-- RIGHT: User Details -->
        <div class="luxury-right">
            <h2 class="section-title2">User Details</h2>
            <div class="details-grid">
    <p>
        
        <span class="label"><i class="fas fa-user"></i> Name</span>
        <span class="value"><?= htmlspecialchars($user['name'] ?? $user_name) ?></span>
    </p>
    <p>
        
        <span class="label"><i class="fas fa-id-badge"></i> User ID</span>
        <span class="value"><?= htmlspecialchars($user['user_id']) ?></span>
    </p>
    <p>
        
        <span class="label"><i class="fas fa-envelope"></i> Email</span>
        <span class="value"><?= htmlspecialchars($user['email']) ?></span>
    </p>
    <p>
        <span class="label"><i class="fas fa-phone"></i> Phone</span>
        <span class="value"><?= htmlspecialchars($user['phone_number'] ?: 'Not set') ?></span>
    </p>
    <p>
        
        <span class="label"><i class="fas fa-venus-mars"></i> Gender</span>
        <span class="value"><?= htmlspecialchars(ucfirst($user['gender'] ?: 'Unknown')) ?></span>
    </p>
    <p>
        
        <span class="label"><i class="fas fa-calendar-alt"></i> Date of Birth</span>
        <span class="value"><?= htmlspecialchars($user['date_of_birth'] ?: '-') ?></span>
    </p>
    <p>
        
        <span class="label"><i class="fas fa-home"></i> Living Place</span>
        <span class="value"><?= htmlspecialchars($user['living_place'] ?: '-') ?></span>
    </p>
        <p>
        
        <span class="label"><i class="fas fa-user-tag"></i> Role</span>
        <span class="value"><?= htmlspecialchars($user['role'] ? ucfirst(strtolower($user['role'])) : '-') ?></span>
    </p>
</div>

        </div>

    </div>
</div>


                <!-- EDIT PROFILE CARD -->
                <div class="profile-card">
                    <div class="card-content">
                        <div class="dashboard-tabs">
                            <button class="tab-btn active" data-tab="editDetailsTab">Edit Details</button>
                            <button class="tab-btn" data-tab="editPasswordTab">Change Password</button>
                        </div>

                        <!-- Edit Details Form -->
                        <div class="tab-content" id="editDetailsTab" style="display:grid;">
                            <form method="POST" class="edit-form" enctype="multipart/form-data">
                                
                                <input type="hidden" name="form_type" value="details">
                                
                                <!-- Profile Picture Upload Section -->
                                <div class="profile-upload-section">
                                    <label for="profilePicInput">Profile Picture:</label>
                                    <div class="upload-preview-container">
                                        <div class="current-avatar-preview">
                                            <img id="avatarPreview" src="<?= htmlspecialchars($user['avatar_path'] ?: 'images/avatar/default.png') ?>" alt="Avatar Preview">
                                        </div>
                                        <div class="upload-input-wrapper">
                                            <input type="file" id="profilePicInput" name="avatar" accept="image/*" class="file-input">
                                            <label for="profilePicInput" class="file-label">
                                                <i class="fas fa-cloud-upload-alt"></i> Choose Image
                                            </label>
                                            <small>Supported: JPG, PNG, GIF (Max 5MB)</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <label>Living Place: <input type="text" name="living_place" value="<?= htmlspecialchars($user['living_place'] ?? '') ?>"></label>
                                <label>Phone Number: <input type="text" name="phone_number" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>"></label>
                                <label>Date of Birth: <input type="date" name="date_of_birth" value="<?= htmlspecialchars($user['date_of_birth'] ?? '') ?>"></label>
                                <label>Gender: 
                                    <select name="gender">
                                        <option value="">Select</option>
                                        <option value="male" <?= $user['gender']=='male'?'selected':'' ?>>Male</option>
                                        <option value="female" <?= $user['gender']=='female'?'selected':'' ?>>Female</option>
                                        <option value="other" <?= $user['gender']=='other'?'selected':'' ?>>Other</option>
                                    </select>
                                </label>
                                <button type="submit" class="btn">Update Details</button>
                            </form>
                        </div>

                        <!-- Change Password Form -->
                        <div class="tab-content" id="editPasswordTab" style="display:none;">
                            <form method="POST" class="edit-form">
                                <input type="hidden" name="form_type" value="password">
                                <label>Current Password: <input type="password" name="current_password"></label>
                                <label>New Password: <input type="password" name="new_password"></label>
                                <label>Repeat New Password: <input type="password" name="repeat_password"></label>
                                <button type="submit" class="btn">Change Password</button>
                            </form>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            tabButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            tabContents.forEach(c => c.style.display = 'none');
            document.getElementById(btn.dataset.tab).style.display = 'grid';
        });
    });

    <?php if($open_tab): ?>
        document.querySelector('.tab-btn[data-tab="<?= $open_tab ?>"]').click();
    <?php endif; ?>

    // Image preview functionality
    const profilePicInput = document.getElementById('profilePicInput');
    const avatarPreview = document.getElementById('avatarPreview');
    
    if (profilePicInput) {
        profilePicInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    avatarPreview.src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
});

    const popout = document.getElementById('feedbackPopout');
    const popoutMessage = document.getElementById('feedbackMessage');
    const popoutClose = document.getElementById('feedbackClose');

    <?php if($msg_success || $msg_error): ?>
        popoutMessage.textContent = "<?= $msg_success ?: $msg_error ?>";
        popout.classList.toggle('error', <?= $msg_error ? 'true' : 'false' ?>);
        popout.style.display = 'flex';

        // Auto-hide after 5 seconds
        setTimeout(() => { popout.style.display = 'none'; }, 5000);
    <?php endif; ?>

    // Manual close
    popoutClose.addEventListener('click', () => {
        popout.style.display = 'none';
    });
document.addEventListener("DOMContentLoaded", () => {
    const contactPanel = document.getElementById('contactPanel');
    const contactSidebarLink = document.getElementById('contactSidebarLink');
    const closeBtn = document.getElementById('closeContactPanel');

    contactSidebarLink.addEventListener('click', (e) => {
        e.preventDefault();
        contactPanel.classList.add('open');
    });

    closeBtn.addEventListener('click', () => {
        contactPanel.classList.remove('open');
    });

    // close when clicking outside the popout
    contactPanel.addEventListener('click', (e) => {
        if (!e.target.closest('.contact-popout')) {
            contactPanel.classList.remove('open');
        }
    });
});

    const card = document.querySelector('.contact-popout');

card.addEventListener('mousemove', (e) => {
    const rect = card.getBoundingClientRect();
    const x = e.clientX - rect.left; // mouse X inside card
    const y = e.clientY - rect.top;  // mouse Y inside card
    const centerX = rect.width / 2;
    const centerY = rect.height / 2;

    // Subtle rotation, max ±6 degrees
    const rotateX = ((centerY - y) / centerY) * 6;
    const rotateY = ((x - centerX) / centerX) * 6;

    card.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.03)`;
    card.classList.add('hovering');
});

card.addEventListener('mouseleave', () => {
    card.style.transform = `rotateX(0deg) rotateY(0deg) scale(1)`;
    card.classList.remove('hovering');
});

</script>
</body>
</html>