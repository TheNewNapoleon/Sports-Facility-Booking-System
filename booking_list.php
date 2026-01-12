<?php
session_start();
require_once "db.php";

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { header("Location: login.php"); exit; }

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$user_avatar = $_SESSION['avatar_path'] ?? ($user['avatar_path'] ?: "images/avatar/test.png");
$user_name = $_SESSION['name'] ?? ($user['name'] ?: "Guest");

if (isset($_GET['cancel_id'])) {
    $cancel_id = intval($_GET['cancel_id']);
    $stmt = $conn->prepare("SELECT status FROM bookings WHERE booking_id = ? AND user_id = ?");
    $stmt->bind_param("is", $cancel_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Allow cancelling 'booked', 'pending', or 'approved' bookings only
        if (in_array($row['status'], ['booked', 'pending', 'approved'])) {
            $upd = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?");
            $upd->bind_param("i", $cancel_id);
            $success = $upd->execute();
            $_SESSION['message'] = $success ? "Booking cancelled successfully!" : "Error cancelling booking!";
            $_SESSION['message_type'] = $success ? "success" : "error";
            $upd->close();
        } else {
            $_SESSION['message'] = "Cannot cancel a booking with status: " . ucfirst($row['status']);
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Booking not found!";
        $_SESSION['message_type'] = "error";
    }
    $stmt->close();
    header("Location: booking_list.php"); exit;
}

$limit = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Check if courts table exists
$courtsTableExists = false;
$checkCourts = $conn->query("SHOW TABLES LIKE 'courts'");
if ($checkCourts && $checkCourts->num_rows > 0) {
    $courtsTableExists = true;
}

// Check if bookings table has court_id column
$hasCourtIdColumn = false;
$checkCourtId = mysqli_query($conn, "SHOW COLUMNS FROM bookings LIKE 'court_id'");
if ($checkCourtId && mysqli_num_rows($checkCourtId) > 0) {
    $hasCourtIdColumn = true;
}

// Get sort order from URL parameter
$sortOrder = $_GET['sort'] ?? 'date_desc';
$orderBy = ($sortOrder === 'date_asc') ? 'ASC' : 'DESC';

// Build query with proper joins for courts if available
if ($courtsTableExists && $hasCourtIdColumn) {
    $sql = "SELECT b.booking_id, b.venue_id, b.court_id, b.booking_date, b.booking_time, b.status,
                   v.name AS venue_name,
                   COALESCE(c.name, v.court, CONCAT('Court ', b.court_id)) AS court_name,
                   DATE_FORMAT(b.booking_time, '%h:%i %p') as formatted_time
            FROM bookings b
            LEFT JOIN venues v ON b.venue_id = v.venue_id
            LEFT JOIN courts c ON b.court_id = c.court_id
            WHERE b.user_id = ? AND b.status IN ('booked', 'completed', 'rejected', 'cancelled')
            ORDER BY b.booking_date $orderBy, b.booking_time $orderBy LIMIT ? OFFSET ?";
} else {
    // Fallback query without courts table
    $sql = "SELECT b.booking_id, b.venue_id, b.booking_date, b.booking_time, b.status,
                   v.name AS venue_name,
                   COALESCE(v.court, b.venue_id) AS court_name,
                   DATE_FORMAT(b.booking_time, '%h:%i %p') as formatted_time
            FROM bookings b
            LEFT JOIN venues v ON b.venue_id = v.venue_id
            WHERE b.user_id = ? AND b.status IN ('booked', 'completed', 'rejected', 'cancelled')
            ORDER BY b.booking_date $orderBy, b.booking_time $orderBy LIMIT ? OFFSET ?";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $user_id, $limit, $offset);

$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE user_id = ? AND status IN ('booked', 'completed', 'rejected', 'cancelled')");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
$total_pages = ceil($total / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking List</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/bookings.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/filters_pagination.css">
    <link rel="stylesheet" href="css/search_notification.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
    <div class="sidebar">
        <div class="sidebar-header"><img src="images/logo1.png" alt="Logo" class="sidebar-logo"></div>
        <a href="dashboard.php">Dashboard</a>
        <a href="profile.php">Profile</a>
        <a href="booking_facilities.php">Booking Facilities</a>
        <a href="booking_list.php" class="active">Booking List</a>
        <a href="feedback.php">Feedback</a>
        <a href="#" id="contactSidebarLink">Contact Us</a>
    </div>

    <div class="main-area">
        <div class="topnav">
            <div class="nav-left"><h2 style="margin:0;font-size:1.3rem;">My Bookings</h2></div>
            <div class="nav-right">
                <!-- Notification Dropdown -->
                <div class="notification-dropdown">
                    <i class="fa-solid fa-bell" id="notifBell"></i>
                    <span class="notif-badge" id="notifBadge">0</span>
                    <div class="dropdown-content" id="notifPanel">
                        <div class="notif-header">
                            <h4><i class="fas fa-bell"></i> Notifications</h4>
                            <button class="mark-all-read" id="markAllRead"><i class="fas fa-check-double"></i> Clear</button>
                        </div>
                        <div class="notif-list">
                            <?php
                            $reminders = [];
                            $rStmt = $conn->prepare("SELECT b.booking_id, v.name AS venue_name, DATE_FORMAT(b.booking_time, '%h:%i %p') as formatted_time FROM bookings b JOIN venues v ON b.venue_id = v.venue_id WHERE b.user_id = ? AND b.status = 'booked' AND b.booking_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)");
                            $rStmt->bind_param("s", $user_id);
                            $rStmt->execute();
                            $reminders = $rStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            $rStmt->close();

                            $notifBookings = [];
                            $bStmt = $conn->prepare("SELECT b.booking_id, v.name AS venue_name, b.booking_date, DATE_FORMAT(b.booking_time, '%h:%i %p') as booking_time, b.status FROM bookings b JOIN venues v ON b.venue_id = v.venue_id WHERE b.user_id = ? AND b.status IN ('booked', 'completed', 'cancelled', 'rejected') AND b.booking_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) ORDER BY b.booking_date DESC LIMIT 15");
                            $bStmt->bind_param("s", $user_id);
                            $bStmt->execute();
                            $notifBookings = $bStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            $bStmt->close();

                            $feedbacks = [];
                            $fStmt = $conn->prepare("SELECT feedback_id, subject, respond_at FROM feedback WHERE user_id = ? AND status = 'reviewed' AND respond IS NOT NULL AND respond_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) ORDER BY respond_at DESC LIMIT 10");
                            $fStmt->bind_param("s", $user_id);
                            $fStmt->execute();
                            $feedbacks = $fStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            $fStmt->close();

                            $hasNotifs = count($reminders) + count($notifBookings) + count($feedbacks) > 0;
                            if ($hasNotifs):
                                foreach ($reminders as $r): ?>
                                <div class="notif-item reminder" data-id="reminder-<?= $r['booking_id'] ?>">
                                    <div class="notif-icon reminder"><i class="fas fa-bell"></i></div>
                                    <div class="notif-content">
                                        <div class="notif-title"><?= htmlspecialchars($r['venue_name']) ?></div>
                                        <span class="notif-status-badge reminder">Tomorrow</span>
                                        <div class="notif-time"><i class="far fa-clock"></i> <?= htmlspecialchars($r['formatted_time']) ?></div>
                                    </div>
                                </div>
                                <?php endforeach;
                                foreach ($notifBookings as $b):
                                    $cfg = [
                                        'booked' => ['icon' => 'fa-calendar-check', 'text' => 'Booked'],
                                        'completed' => ['icon' => 'fa-flag-checkered', 'text' => 'Completed'],
                                        'cancelled' => ['icon' => 'fa-times-circle', 'text' => 'Cancelled'],
                                        'rejected' => ['icon' => 'fa-ban', 'text' => 'Rejected']
                                    ][$b['status']] ?? ['icon' => 'fa-question-circle', 'text' => ucfirst($b['status'])]; ?>
                                <div class="notif-item <?= $b['status'] ?>" data-id="booking-<?= $b['booking_id'] ?>">
                                    <div class="notif-icon <?= $b['status'] ?>"><i class="fas <?= $cfg['icon'] ?>"></i></div>
                                    <div class="notif-content">
                                        <div class="notif-title"><?= htmlspecialchars($b['venue_name']) ?></div>
                                        <span class="notif-status-badge <?= $b['status'] ?>"><?= $cfg['text'] ?></span>
                                        <div class="notif-time"><i class="far fa-calendar"></i> <?= date('M d', strtotime($b['booking_date'])) ?></div>
                                    </div>
                                </div>
                                <?php endforeach;
                                foreach ($feedbacks as $f): ?>
                                <div class="notif-item feedback" data-id="feedback-<?= $f['feedback_id'] ?>">
                                    <div class="notif-icon feedback"><i class="fas fa-comment-dots"></i></div>
                                    <div class="notif-content">
                                        <div class="notif-title"><?= htmlspecialchars($f['subject']) ?></div>
                                        <span class="notif-status-badge feedback">Reviewed</span>
                                        <div class="notif-time"><i class="far fa-clock"></i> <?= date('M d', strtotime($f['respond_at'])) ?></div>
                                    </div>
                                </div>
                                <?php endforeach;
                            else: ?>
                                <div class="notif-empty"><i class="fas fa-bell-slash"></i><p>No notifications</p></div>
                            <?php endif; ?>
                        </div>
                        <div class="notif-footer">
                            <a href="booking_list.php">View Bookings</a>
                            <a href="feedback.php">View Feedback</a>
                        </div>
                    </div>
                </div>

                <div class="user-info">
                    <img src="<?= htmlspecialchars($user_avatar) ?>" class="avatar" alt="Avatar">
                    <span style="font-weight:500;"><?= htmlspecialchars($user_name) ?></span>
                </div>

                <a href="logout.php" class="logout-btn" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </div>

        <div class="main-content">
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
            <?php if (isset($_SESSION['message'])): ?>
                <div class="message <?= htmlspecialchars($_SESSION['message_type']) ?>"><?= htmlspecialchars($_SESSION['message']) ?></div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>

            <div class="filter-container">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="Search...">
                    <span class="clear-btn" id="clearBtn">×</span>
                </div>
                <select id="filterSelect" hidden><option value="">All</option><option value="booked">Booked</option><option value="completed">Completed</option><option value="rejected">Rejected</option><option value="cancelled">Cancelled</option></select>
                <?php 
                $currentSort = $_GET['sort'] ?? '';
                $sortText = ($currentSort === 'date_asc') ? 'Oldest' : (($currentSort === 'date_desc') ? 'Newest' : 'Sort By');
                ?>
                <select id="sortSelect" hidden><option value="" <?= $currentSort === '' ? 'selected' : '' ?>>Sort</option><option value="date_desc" <?= $currentSort === 'date_desc' ? 'selected' : '' ?>>Newest</option><option value="date_asc" <?= $currentSort === 'date_asc' ? 'selected' : '' ?>>Oldest</option></select>
                <div class="custom-dropdown" id="filterDropdown"><div class="cd-selected">All Status</div><ul class="cd-list"><li data-value="">All Status</li><li data-value="booked">Booked</li><li data-value="completed">Completed</li><li data-value="rejected">Rejected</li><li data-value="cancelled">Cancelled</li></ul></div>
                <div class="custom-dropdown" id="sortDropdown"><div class="cd-selected"><?= htmlspecialchars($sortText) ?></div><ul class="cd-list"><li data-value="">Sort By</li><li data-value="date_desc">Newest</li><li data-value="date_asc">Oldest</li></ul></div>
            </div>

            <div class="booking-table-container">
                <table class="booking-table" id="bookingTable">
                    <thead><tr><th><i class="fas fa-map-marker-alt"></i> Venue</th><th><i class="fas fa-chair"></i> Court</th><th><i class="far fa-calendar-alt"></i> Date</th><th><i class="far fa-clock"></i> Time</th><th><i class="fas fa-info-circle"></i> Status</th><th><i class="fas fa-cog"></i> Actions</th></tr></thead>
                    <tbody>
                        <?php if($bookings): foreach($bookings as $b): $icons=['booked'=>'fa-calendar-check','completed'=>'fa-flag-checkered','rejected'=>'fa-ban','cancelled'=>'fa-times-circle']; ?>
                        <tr class="booking-row">
                            <td><?= htmlspecialchars($b['venue_name'] ?? 'Unknown Venue') ?></td>
                            <td><?= htmlspecialchars($b['court_name'] ?? 'Court 1') ?></td>
                            <td><?= htmlspecialchars($b['booking_date']) ?></td>
                            <td><?= htmlspecialchars($b['formatted_time']) ?></td>
                            <td><span class="status <?= $b['status'] ?>"><i class="<?= $icons[$b['status']] ?? 'fa-question-circle' ?> fas" style="margin-right:5px;"></i><?= ucfirst($b['status']) ?></span></td>
                            <td><?php if($b['status'] === 'booked'): ?><button class="action-btn cancel" data-id="<?= $b['booking_id'] ?>" data-status="<?= htmlspecialchars($b['status']) ?>"><i class="fas fa-times"></i> Cancel</button><?php else: ?><span style="color:#999;font-style:italic;">-</span><?php endif; ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="6" style="text-align:center;padding:40px;"><i class="fas fa-inbox" style="font-size:3rem;color:#ff9500;display:block;margin-bottom:15px;"></i><p>No bookings found.</p><a href="booking_facilities.php" style="color:#ff7b00;">Book now!</a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                <span style="margin-right:15px;color:#666;">Showing <?= count($bookings) ?> of <?= $total ?></span>
                <?php if($total_pages > 1): 
                    $sortParam = isset($_GET['sort']) ? '&sort=' . htmlspecialchars($_GET['sort']) : '';
                ?>
                    <?php if($page > 1): ?><a href="?page=<?= $page-1 ?><?= $sortParam ?>"><i class="fas fa-chevron-left"></i> Prev</a><?php endif; ?>
                    <?php for($i=1;$i<=$total_pages;$i++): ?><?php if($i==$page): ?><strong><?= $i ?></strong><?php else: ?><a href="?page=<?= $i ?><?= $sortParam ?>"><?= $i ?></a><?php endif; ?><?php endfor; ?>
                    <?php if($page < $total_pages): ?><a href="?page=<?= $page+1 ?><?= $sortParam ?>">Next <i class="fas fa-chevron-right"></i></a><?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function setupDropdown(id, cb) {
    const box = document.getElementById(id), sel = box.querySelector('.cd-selected'), list = box.querySelector('.cd-list');
    sel.onclick = () => list.style.display = list.style.display === 'block' ? 'none' : 'block';
    box.querySelectorAll('li').forEach(li => li.onclick = () => { sel.textContent = li.textContent; list.style.display = 'none'; if(cb) cb(li.dataset.value); });
    document.addEventListener('click', e => { if(!box.contains(e.target)) list.style.display = 'none'; });
}
function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const s = document.getElementById('filterSelect').value.toLowerCase();
    document.querySelectorAll('#bookingTable tbody tr').forEach(tr => {
        const tds = tr.getElementsByTagName('td');
        if (tds.length === 0) return; // Skip empty rows
        
        let show = true;
        
        // Search filter - check all columns except Actions (last column)
        if (q) {
            show = false;
            for (let i = 0; i < tds.length - 1; i++) {
                if (tds[i].textContent.toLowerCase().includes(q)) {
                    show = true;
                    break;
                }
            }
        }
        
        // Status filter - check the status column (index 4, since Booking ID was removed) and its class
        if (show && s) {
            const statusCell = tds[4];
            if (statusCell) {
                const statusText = statusCell.textContent.toLowerCase();
                const statusSpan = statusCell.querySelector('.status');
                const statusClass = statusSpan ? statusSpan.className.toLowerCase() : '';
                // Check both text content and class name
                show = statusText.includes(s) || statusClass.includes(s);
            } else {
                show = false;
            }
        }
        
        tr.style.display = show ? '' : 'none';
    });
}
document.addEventListener('DOMContentLoaded', () => {
    const search = document.getElementById('searchInput'), clear = document.getElementById('clearBtn');
    clear.style.display = search.value ? 'block' : 'none';
    search.addEventListener('input', () => { clear.style.display = search.value ? 'block' : 'none'; filterTable(); });
    clear.addEventListener('click', () => { search.value = ''; clear.style.display = 'none'; filterTable(); });
    setupDropdown('filterDropdown', v => { document.getElementById('filterSelect').value = v; filterTable(); });
    setupDropdown('sortDropdown', v => { 
        document.getElementById('sortSelect').value = v;
        if (v) {
            const url = new URL(window.location);
            url.searchParams.set('sort', v);
            url.searchParams.set('page', '1'); // Reset to first page when sorting
            window.location = url.toString();
        }
    });

    // Notification
    const bell = document.getElementById('notifBell'), panel = document.getElementById('notifPanel'), badge = document.getElementById('notifBadge'), markAll = document.getElementById('markAllRead'), items = document.querySelectorAll('.notif-item');
    const getRead = () => JSON.parse(localStorage.getItem('readNotifs') || '[]'), saveRead = a => localStorage.setItem('readNotifs', JSON.stringify(a));
    let read = getRead(), unread = 0;
    items.forEach(i => { if(read.includes(i.dataset.id)) i.classList.add('read'); else unread++; });
    const updBadge = () => { badge.textContent = unread > 9 ? '9+' : unread; badge.style.display = unread > 0 ? 'flex' : 'none'; };
    updBadge();
    bell.addEventListener('click', e => { panel.classList.toggle('show'); e.stopPropagation(); });
    document.addEventListener('click', e => { if(!panel.contains(e.target) && e.target !== bell) panel.classList.remove('show'); });
    items.forEach(i => i.addEventListener('click', function() { if(!read.includes(this.dataset.id)) { read.push(this.dataset.id); saveRead(read); this.classList.add('read'); unread--; updBadge(); } }));
    markAll.addEventListener('click', e => { e.stopPropagation(); items.forEach(i => { if(!read.includes(i.dataset.id)) { read.push(i.dataset.id); i.classList.add('read'); } }); saveRead(read); unread = 0; updBadge(); });
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

<!-- Cancel Booking Confirmation Modal -->
<div id="cancelModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Cancel Booking</h3>
        </div>
        <div class="modal-body">
            <p id="cancelMessage">Are you sure you want to cancel this booking?</p>
        </div>
        <div class="modal-footer">
            <button id="cancelYes" class="modal-btn confirm">Yes, Cancel</button>
            <button id="cancelNo" class="modal-btn cancel">No, Keep Booking</button>
        </div>
    </div>
</div>

<style>
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: white;
    border-radius: 15px;
    padding: 0;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    animation: modalFadeIn 0.3s ease-out;
}

.modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid #eee;
    text-align: center;
}

.modal-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.2rem;
    font-weight: 600;
}

.modal-body {
    padding: 25px;
    text-align: center;
}

.modal-body p {
    margin: 0;
    color: #666;
    font-size: 1rem;
    line-height: 1.5;
}

.modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 15px;
    justify-content: center;
}

.modal-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 100px;
}

.modal-btn.confirm {
    background-color: #f44336;
    color: white;
}

.modal-btn.confirm:hover {
    background-color: #d32f2f;
    transform: translateY(-1px);
}

.modal-btn.cancel {
    background-color: #4caf50;
    color: white;
}

.modal-btn.cancel:hover {
    background-color: #388e3c;
    transform: translateY(-1px);
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>

<script>
// Cancel booking modal functionality (calls cancel_booking.php via AJAX)
const cancelModal = document.getElementById('cancelModal');
const cancelMessageEl = document.getElementById('cancelMessage');
const cancelYesBtn = document.getElementById('cancelYes');
const cancelNoBtn = document.getElementById('cancelNo');
let currentCancelId = null;

function openCancelModal(id, status) {
    currentCancelId = id;
    const message = status === 'booked' 
        ? 'Are you sure you want to cancel this booking? You can rebook after cancellation.'
        : 'Cancel this booking?';
    cancelMessageEl.textContent = message;
    cancelModal.style.display = 'flex';
}

function closeCancelModal() {
    cancelModal.style.display = 'none';
    currentCancelId = null;
    cancelYesBtn.disabled = false;
    cancelYesBtn.textContent = 'Yes, Cancel';
}

async function performCancel() {
    if (!currentCancelId) return;
    cancelYesBtn.disabled = true;
    cancelYesBtn.textContent = 'Cancelling...';
    try {
        const res = await fetch('cancel_booking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ booking_id: currentCancelId })
        });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Unable to cancel booking.');
            closeCancelModal();
        }
    } catch (error) {
        console.error('Cancel booking error:', error);
        alert('Network error while cancelling. Please try again.');
        closeCancelModal();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.action-btn.cancel').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            openCancelModal(btn.dataset.id, btn.dataset.status);
        });
    });
});

cancelYesBtn.addEventListener('click', performCancel);
cancelNoBtn.addEventListener('click', closeCancelModal);
cancelModal.addEventListener('click', e => {
    if (e.target === cancelModal) {
        closeCancelModal();
    }
});
</script>
</body>
</html>