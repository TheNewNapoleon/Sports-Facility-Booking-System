<?php
session_start();
require_once "db.php";

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) exit('Unauthorized');

$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 5;
$offset = ($page - 1) * $limit;
$like_search = "%$search%";

// Count total feedbacks
$stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM feedback WHERE user_id=? AND (subject LIKE ? OR category LIKE ? OR status LIKE ? OR message LIKE ?)");
$stmt_count->bind_param("sssss", $user_id, $like_search, $like_search, $like_search, $like_search);
$stmt_count->execute();
$total_result = $stmt_count->get_result()->fetch_assoc();
$total_feedbacks = $total_result['total'];
$stmt_count->close();
$total_pages = ceil($total_feedbacks / $limit);

// Fetch feedbacks
$stmt = $conn->prepare("SELECT * FROM feedback WHERE user_id=? AND (subject LIKE ? OR category LIKE ? OR status LIKE ? OR message LIKE ?) ORDER BY submitted_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ssssiii", $user_id, $like_search, $like_search, $like_search, $like_search, $limit, $offset);
$stmt->execute();
$feedbacks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Render feedback items + pagination
if (count($feedbacks) > 0):
    foreach($feedbacks as $fb): ?>
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
        <?php if($page > 1): ?>
            <a href="#" data-page="<?= $page-1 ?>" class="prev"><i class="fas fa-chevron-left"></i> Previous</a>
        <?php endif; ?>

        <?php for($i=1; $i<=$total_pages; $i++): ?>
            <a href="#" data-page="<?= $i ?>" class="<?= $i==$page?'current':'' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if($page < $total_pages): ?>
            <a href="#" data-page="<?= $page+1 ?>" class="next">Next <i class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
    </div>

<?php else: ?>
    <div class="no-feedback"><p>No feedback found.</p></div>
<?php endif; ?>
