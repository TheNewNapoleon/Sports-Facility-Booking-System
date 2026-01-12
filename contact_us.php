<?php
session_start();
include_once 'inc_topbar.php';
include_once 'db.php';
$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';
    if ($name && $email && $message) {
        file_put_contents(__DIR__.'/contact_log.txt', "[".date('Y-m-d H:i:s')."] $name <$email>: $message\n", FILE_APPEND);
        $sent = true;
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Contact Us</title><link rel="stylesheet" href="style.css"></head>
<body class="ui-page">

<main class="ui-main">
  <section class="ui-card card-anim">
    <h2>Contact Us</h2>
    <?php if ($sent): ?><div class="ui-alert">Thanks — your message has been received.</div><?php endif; ?>
    <form method="post" class="ui-form">
      <label>Your name <input name="name" value="<?=htmlspecialchars($_SESSION['full_name'] ?? '')?>" required></label>
      <label>Your email <input name="email" type="email" value="<?=htmlspecialchars($_SESSION['email'] ?? '')?>" required></label>
      <label>Message <textarea name="message" rows="5" required></textarea></label>
      <button class="ui-btn" type="submit">Send message</button>
    </form>
  </section>
</main>

</body>
</html>
