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

$user_name = $_SESSION['name'] ?? ($user['name'] ?? "Guest");
$user_avatar = $_SESSION['avatar_path'] ?? ($user['avatar_path'] ?? "images/avatar/test.png");

$msg_success = '';
$msg_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $subject = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($subject) || empty($category) || empty($message)) {
        $msg_error = "All fields are required!";
    } elseif (strlen($message) < 10) {
        $msg_error = "Feedback must be at least 10 characters.";
    } else {
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, subject, category, message, status, submitted_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("ssss", $user_id, $subject, $category, $message);
        if ($stmt->execute()) {
            $_SESSION['msg_success'] = "Feedback submitted successfully!";
            header("Location: feedback.php"); exit;
        } else {
            $msg_error = "Failed to submit feedback.";
        }
        $stmt->close();
    }
}

if (isset($_SESSION['msg_success'])) { $msg_success = $_SESSION['msg_success']; unset($_SESSION['msg_success']); }

$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 5;
$offset = ($page - 1) * $limit;
$like = "%$search%";

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM feedback WHERE user_id=? AND (subject LIKE ? OR category LIKE ? OR status LIKE ? OR message LIKE ?)");
$stmt->bind_param("sssss", $user_id, $like, $like, $like, $like);
$stmt->execute();
$total_feedbacks = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
$total_pages = ceil($total_feedbacks / $limit);

$stmt = $conn->prepare("SELECT * FROM feedback WHERE user_id=? AND (subject LIKE ? OR category LIKE ? OR status LIKE ? OR message LIKE ?) ORDER BY submitted_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("sssssii", $user_id, $like, $like, $like, $like, $limit, $offset);
$stmt->execute();
$feedbacks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Feedback</title>
<link rel="stylesheet" href="css/global.css">
<link rel="stylesheet" href="css/dashboard.css">
<link rel="stylesheet" href="css/feedback.css">
<link rel="stylesheet" href="css/filters_pagination.css">
<link rel="stylesheet" href="css/search_notification.css">
<link rel="stylesheet" href="css/navbar.css">
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
        <a href="booking_list.php">Booking List</a>
        <a href="feedback.php" class="active">Feedback</a>
        <a href="#" id="contactSidebarLink">Contact Us</a>
    </div>

    <div class="main-area">
        <div class="topnav">
            <div class="nav-left"></div>
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
                            $rStmt = $conn->prepare("SELECT b.booking_id, v.name AS venue_name, b.booking_time FROM bookings b JOIN venues v ON b.venue_id = v.venue_id WHERE b.user_id = ? AND b.status = 'booked' AND b.booking_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)");
                            $rStmt->bind_param("s", $user_id);
                            $rStmt->execute();
                            $reminders = $rStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            $rStmt->close();

                            $bookings = [];
                            $bStmt = $conn->prepare("SELECT b.booking_id, v.name AS venue_name, b.booking_date, b.booking_time, b.status FROM bookings b JOIN venues v ON b.venue_id = v.venue_id WHERE b.user_id = ? AND b.status IN ('booked', 'completed', 'cancelled', 'rejected') AND b.booking_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) ORDER BY b.booking_date DESC LIMIT 15");
                            $bStmt->bind_param("s", $user_id);
                            $bStmt->execute();
                            $bookings = $bStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            $bStmt->close();

                            $notifFeedbacks = [];
                            $fStmt = $conn->prepare("SELECT feedback_id, subject, respond_at FROM feedback WHERE user_id = ? AND status = 'reviewed' AND respond IS NOT NULL AND respond_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) ORDER BY respond_at DESC LIMIT 10");
                            $fStmt->bind_param("s", $user_id);
                            $fStmt->execute();
                            $notifFeedbacks = $fStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            $fStmt->close();

                            $hasNotifs = count($reminders) + count($bookings) + count($notifFeedbacks) > 0;
                            if ($hasNotifs):
                                foreach ($reminders as $r): ?>
                                <div class="notif-item reminder" data-id="reminder-<?= $r['booking_id'] ?>">
                                    <div class="notif-icon reminder"><i class="fas fa-bell"></i></div>
                                    <div class="notif-content">
                                        <div class="notif-title"><?= htmlspecialchars($r['venue_name']) ?></div>
                                        <span class="notif-status-badge reminder">Tomorrow</span>
                                        <div class="notif-time"><i class="far fa-clock"></i> <?= date('h:i A', strtotime($r['booking_time'])) ?></div>
                                    </div>
                                </div>
                                <?php endforeach;
                                foreach ($bookings as $b):
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
                                        <div class="notif-time">
                                            <i class="far fa-calendar"></i> <?= date('M d', strtotime($b['booking_date'])) ?>
                                            &nbsp;<i class="far fa-clock"></i> <?= date('h:i A', strtotime($b['booking_time'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach;
                                foreach ($notifFeedbacks as $f): ?>
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
                    <span><?= htmlspecialchars($user_name) ?></span>
                </div>

                <a href="logout.php" class="logout-btn" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </div>

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
            <?php if($msg_success || $msg_error): ?>
            <div class="feedback-popout" style="display:flex;">
                <span><?= htmlspecialchars($msg_success ?: $msg_error) ?></span>
                <button onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
            <script>setTimeout(() => document.querySelector('.feedback-popout').style.display = 'none', 5000);</script>
            <?php endif; ?>

            <div class="feedback-form-card">
                <h2><i class="fas fa-edit"></i> Submit Your Feedback</h2>
                <form method="POST" id="feedbackForm">
                    <input type="hidden" name="submit_feedback" value="1">
                    <div class="form-group">
                        <label><i class="fa-solid fa-receipt"></i> Subject</label>
                        <input type="text" name="subject" placeholder="Brief title" required maxlength="200">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Category</label>
                        <input type="hidden" name="category" id="categoryInput" required>
                        <div class="custom-dropdown" id="categoryDropdown">
                            <div class="cd-selected">Select a category</div>
                            <ul class="cd-list">
                                <li data-value="">Select a category</li>
                                <li data-value="General">General</li>
                                <li data-value="Booking">Booking</li>
                                <li data-value="Facility">Facility</li>
                                <li data-value="Service">Service</li>
                                <li data-value="Suggestion">Suggestion</li>
                                <li data-value="Complaint">Complaint</li>
                                <li data-value="Other">Other</li>
                            </ul>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-comment-dots"></i> Your Feedback</label>
                        <textarea name="message" placeholder="Share your thoughts..." required minlength="10"></textarea>
                        <small style="color:#999;font-size:12px;">Min 10 characters</small>
                    </div>
                    <button type="submit" class="submit-btn"><i class="fas fa-paper-plane"></i> Submit</button>
                </form>
            </div>

            <div id="feedbackHistory" class="feedback-history-card">
                <div class="feedback-header-top" style="display:flex;justify-content:space-between;align-items:center;">
                    <h2><i class="fas fa-history"></i> Feedback History</h2>
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="search-input" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                        <span class="clear-btn" id="clearBtn">×</span>
                    </div>
                </div>
                <div id="feedbackList">
                    <?php if(count($feedbacks) > 0): foreach($feedbacks as $fb): ?>
                    <div class="feedback-item">
                        <div class="feedback-header">
                            <div class="feedback-title"><?= htmlspecialchars($fb['subject']) ?> (<?= htmlspecialchars($fb['category']) ?>)</div>
                            <span class="feedback-status status-<?= strtolower($fb['status']) ?>"><?= htmlspecialchars($fb['status']) ?></span>
                        </div>
                        <div class="feedback-meta">
                            <span><i class="far fa-calendar"></i> <?= date('M d, Y', strtotime($fb['submitted_at'])) ?></span>
                            <span><i class="far fa-clock"></i> <?= date('h:i A', strtotime($fb['submitted_at'])) ?></span>
                        </div>
                        <div class="feedback-message"><?= nl2br(htmlspecialchars($fb['message'])) ?></div>
                        <?php if(!empty($fb['respond'])): ?>
                        <div class="admin-response">
                            <strong>Admin Response:</strong>
                            <span class="response-date"><?= $fb['respond_at'] ? date('M d, Y h:i A', strtotime($fb['respond_at'])) : '' ?></span>
                            <div class="response-message"><?= nl2br(htmlspecialchars($fb['respond'])) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <div class="pagination">
                        <span style="margin-right:15px;color:#666;">Showing <?= count($feedbacks) ?> of <?= $total_feedbacks ?></span>
                        <?php if($total_pages > 1): ?>
                            <?php if($page > 1): ?><a href="#" data-page="<?= $page-1 ?>"><i class="fas fa-chevron-left"></i> Prev</a><?php endif; ?>
                            <?php for($i=1;$i<=$total_pages;$i++): ?><a href="#" data-page="<?= $i ?>" class="<?= $i==$page?'current':'' ?>"><?= $i ?></a><?php endfor; ?>
                            <?php if($page < $total_pages): ?><a href="#" data-page="<?= $page+1 ?>">Next <i class="fas fa-chevron-right"></i></a><?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="no-feedback"><p>No feedback found.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('feedbackForm').addEventListener('submit', function(e) {
    if(this.querySelector('textarea[name="message"]').value.length < 10) { e.preventDefault(); alert('Min 10 characters.'); }
});

function setupDropdown(id, cb) {
    const box = document.getElementById(id), sel = box.querySelector('.cd-selected'), list = box.querySelector('.cd-list');
    sel.onclick = () => list.style.display = list.style.display === 'block' ? 'none' : 'block';
    box.querySelectorAll('li').forEach(li => li.onclick = () => { sel.textContent = li.textContent; list.style.display = 'none'; if(cb) cb(li.dataset.value); });
    document.addEventListener('click', e => { if(!box.contains(e.target)) list.style.display = 'none'; });
}

document.addEventListener('DOMContentLoaded', () => {
    setupDropdown('categoryDropdown', v => document.getElementById('categoryInput').value = v);

    const search = document.getElementById('searchInput'), clear = document.getElementById('clearBtn'), list = document.getElementById('feedbackList');
    clear.style.display = search.value ? 'block' : 'none';
    const fetchFb = (q='', p=1) => fetch(`feedback_search.php?search=${encodeURIComponent(q)}&page=${p}`).then(r => r.text()).then(h => list.innerHTML = h);
    search.addEventListener('input', () => { fetchFb(search.value, 1); clear.style.display = search.value ? 'block' : 'none'; });
    clear.addEventListener('click', () => { search.value = ''; clear.style.display = 'none'; fetchFb('', 1); });
    list.addEventListener('click', e => { if(e.target.tagName === 'A' && e.target.closest('.pagination')) { e.preventDefault(); fetchFb(search.value, e.target.dataset.page); } });

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
</body>
</html>