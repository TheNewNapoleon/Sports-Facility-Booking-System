<?php
session_start();
require_once "db.php";

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Fetch user info
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if user is blacklisted
if ($user && $user['status'] === 'blacklisted') {
    session_destroy();
    header("Location: login.php?error=blacklisted");
    exit;
}

// Set avatar and name
$user_avatar = $_SESSION['avatar_path'] ?? ($user['avatar_path'] ?: "images/avatar/test.png");
$user_name = $_SESSION['name'] ?? ($user['name'] ?: "Guest");

// Pagination settings for announcements
$limit = 10;
$annPage = isset($_GET['ann_page']) ? (int)$_GET['ann_page'] : 1;
$start_ann = ($annPage - 1) * $limit;

// Fetch announcements
$annQuery = $conn->query("SELECT * FROM announcement ORDER BY posted_date DESC LIMIT $start_ann, $limit");
$totalAnn = $conn->query("SELECT COUNT(*) as total FROM announcement")->fetch_assoc()['total'];
$totalAnnPages = ceil($totalAnn / $limit);

// --- Events (from admin dashboard) ---
$events = $conn->query("
    SELECT e.*, v.name AS venue_name 
    FROM events e 
    LEFT JOIN venues v ON e.venue_id = v.venue_id 
    ORDER BY e.start_date DESC LIMIT 50
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard</title>
    <link rel="stylesheet" href="css/events_announcement.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/search_notification.css">
    <link rel="stylesheet" href="css/filters_pagination.css">
    <link rel="stylesheet" href="css/staff_page.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-page">

<div class="dashboard-container">

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="images/logo.jpg" alt="Logo">
        </div>
        <a href="staff_page.php">Dashboard</a>
        <a href="profile.php">Profile</a>
        <a href="booking_facilities.php">Booking Facilities</a>
        <a href="booking_list.php">Booking List</a>
        <a href="staff_page.php?section=events" class="<?= (isset($_GET['section']) && $_GET['section'] == 'events') ? 'active' : '' ?>">Events</a>
        <a href="feedback.php">Feedback</a>
        <a href="faqai.php">FAQ AI</a>
        <a href="#" id="contactSidebarLink">Contact Us</a>
    </div>

    <!-- Main Area -->
    <div class="main-area">

        <!-- Top Navbar -->
        <div class="topnav">
            <div class="nav-search">
                <input type="text" id="navbarSearch" placeholder="Search announcement/events..." autocomplete="off">
                <span id="clearSearch" class="clear-btn">&times;</span>
                <i class="fa-solid fa-magnifying-glass"></i>
                <div id="searchResults" class="search-dropdown"></div>
            </div>

            <div id="searchPopout" class="search-popout" style="display:none;">
                <div class="popout-header">
                    <span id="popoutTitle"></span>
                    <button id="popoutClose">&times;</button>
                </div>
                <div class="popout-content" id="popoutContent"></div>
            </div>

            <div class="nav-right">
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
                                WHERE b.user_id = ? AND b.status = 'approved'
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
                                AND b.status IN ('pending', 'approved', 'cancelled', 'rejected')
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
                                    'pending' => ['icon' => 'fa-clock', 'text' => 'Pending'],
                                    'approved' => ['icon' => 'fa-check-circle', 'text' => 'Approved'],
                                    'cancelled' => ['icon' => 'fa-times-circle', 'text' => 'Cancelled'],
                                    'rejected' => ['icon' => 'fa-ban', 'text' => 'Rejected']
                                ][$b['status']];
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
                    <img src="<?= htmlspecialchars($user_avatar) ?>" class="avatar" alt="Avatar">
                    <span><?= htmlspecialchars($user_name) ?></span>
                </div>

                </div>
                
                <a href="logout.php" class="logout-btn" title="Logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </div>

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

            <?php if (!isset($_GET['section']) || $_GET['section'] != 'events'): ?>
            <!-- Tab buttons for Announcements -->
            <div class="dashboard-tabs">
                <button class="tab-btn active" data-tab="announcement"><i class="fa-solid fa-bullhorn"></i> Announcements</button>
            </div>

            <!-- Announcements Section -->
            <div class="tab-content" id="announcement" style="display:grid;">
                <?php if($annQuery->num_rows > 0): ?>
                    <?php while($ann = $annQuery->fetch_assoc()): ?>
                        <div class="event-card" data-type="announcement" data-id="<?= $ann['announcement_id'] ?>">
                            <div class="card-preview">
                                <span class="card-time"><?= date('H:i, d M Y', strtotime($ann['posted_date'])) ?></span>
                                <span class="card-title"><?= htmlspecialchars($ann['title']) ?></span>
                            </div>
                            <div class="card-full" style="display:none;">
                                <p>Admin: <?= htmlspecialchars($ann['message']) ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="empty-events">No announcements yet.</p>
                <?php endif; ?>

                <div class="pagination">
                    <?php if($annPage > 1): ?>
                        <a href="?ann_page=<?= $annPage-1 ?>">Previous</a>
                    <?php endif; ?>
                    <?php for($i = 1; $i <= $totalAnnPages; $i++): ?>
                        <?php if($i == $annPage): ?>
                            <strong><?= $i ?></strong>
                        <?php else: ?>
                            <a href="?ann_page=<?= $i ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if($annPage < $totalAnnPages): ?>
                        <a href="?ann_page=<?= $annPage+1 ?>">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['section']) && $_GET['section'] == 'events'): ?>
            <!-- Events Section (from admin dashboard) -->
            <section>
                <div class="admin-table-container">
                    <div class="admin-table-header header-row">
                        <h2><i class="fa-solid fa-calendar-days"></i> View Events</h2>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <div class="search-box" style="width: 300px;">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" id="eventSearch" class="search-input" placeholder="Search events..." 
                                       onkeyup="liveSearchEvents(this.value)">
                                <span class="clear-btn" id="eventClearBtn" onclick="clearEventSearch()" style="display:none;">×</span>
                            </div>
                        </div>
                    </div>
                    <table class="admin-table" id="eventsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Event Name</th>
                                <th>Facility</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if($events && $events->num_rows > 0): ?>
                            <?php while($evt = $events->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($evt['event_id'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($evt['name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($evt['venue_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($evt['start_date'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($evt['end_date'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars(($evt['start_time'] ?? 'N/A') . ' - ' . ($evt['end_time'] ?? 'N/A')) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; color: #999;">No events found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    // CARD EXPAND
    document.querySelectorAll('.event-card').forEach(card => {
        card.addEventListener('click', () => {
            const full = card.querySelector('.card-full');
            document.querySelectorAll('.card-full').forEach(c => { if(c !== full) c.style.display = 'none'; });
            full.style.display = full.style.display === 'block' ? 'none' : 'block';
        });
    });

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

    // SEARCH
    const searchInput = document.getElementById('navbarSearch');
    const searchResults = document.getElementById('searchResults');
    const popout = document.getElementById('searchPopout');
    const popoutTitle = document.getElementById('popoutTitle');
    const popoutContent = document.getElementById('popoutContent');
    const clearBtn = document.getElementById('clearSearch');
    let debounce = null;

    function runSearch() {
        const q = searchInput.value.trim();
        if (!q) { searchResults.style.display = 'none'; return; }
        
        fetch(`dashboard_search.php?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                searchResults.innerHTML = data.length === 0 
                    ? '<div class="search-item">No results</div>'
                    : data.map(item => `<div class="search-item" data-type="${item.type}" data-item='${JSON.stringify(item)}'><strong>${item.type.toUpperCase()}</strong>: ${item.title || item.name}</div>`).join('');
                searchResults.style.display = 'block';
            });
    }

    searchInput.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(runSearch, 200);
        clearBtn.style.display = searchInput.value ? 'block' : 'none';
    });

    searchResults.addEventListener('click', e => {
        const item = e.target.closest('.search-item');
        if (item && item.dataset.item) {
            const data = JSON.parse(item.dataset.item);
            popoutTitle.textContent = data.title || data.name;
            popoutContent.innerHTML = data.type === 'announcement'
                ? `<p><strong>Posted by:</strong> ${data.posted_by}</p><p>${data.message}</p><p><strong>Date:</strong> ${data.date}</p>`
                : `<p><strong>Venue:</strong> ${data.venue_name}</p><p><strong>Start:</strong> ${data.start_date} at ${data.start_time}</p><p><strong>End:</strong> ${data.end_date} at ${data.end_time}</p>`;
            popout.style.display = 'block';
            searchResults.style.display = 'none';
        }
    });

    document.getElementById('popoutClose').addEventListener('click', () => popout.style.display = 'none');
    
    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        clearBtn.style.display = 'none';
        searchResults.style.display = 'none';
    });

    document.addEventListener('click', e => {
        if (!searchResults.contains(e.target) && e.target !== searchInput) searchResults.style.display = 'none';
    });
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
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    const centerX = rect.width / 2;
    const centerY = rect.height / 2;

    const rotateX = ((centerY - y) / centerY) * 6;
    const rotateY = ((x - centerX) / centerX) * 6;

    card.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.03)`;
    card.classList.add('hovering');
});

card.addEventListener('mouseleave', () => {
    card.style.transform = `rotateX(0deg) rotateY(0deg) scale(1)`;
    card.classList.remove('hovering');
});

// Live search for events
function liveSearchEvents(query) {
    const table = document.getElementById('eventsTable');
    const rows = table.querySelectorAll('tbody tr');
    const clearBtn = document.getElementById('eventClearBtn');
    let visibleCount = 0;

    query = query.toLowerCase().trim();
    clearBtn.style.display = query.length > 0 ? 'inline-block' : 'none';

    rows.forEach(row => {
        if (row.textContent.includes('No events found')) return;

        const eventName = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
        const facility = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';

        if (eventName.includes(query) || facility.includes(query)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    let noResultsRow = table.querySelector('tbody tr:last-child');
    if (noResultsRow && noResultsRow.textContent.includes('No events found')) {
        noResultsRow.style.display = visibleCount === 0 && query.length > 0 ? '' : 'none';
    }
}

function clearEventSearch() {
    document.getElementById('eventSearch').value = '';
    document.getElementById('eventClearBtn').style.display = 'none';
    liveSearchEvents('');
}
</script>

</body>
</html>s