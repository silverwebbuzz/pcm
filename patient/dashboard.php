<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['patient']);

require __DIR__ . '/../layout/header.php';
?>
<h2>Patient Dashboard</h2>
<p>
    <a class="btn" href="profile.php">My Profile</a>
    <a class="btn" href="sessions.php">Session History</a>
    <a class="btn" href="payments.php">Payments</a>
</p>
<?php require __DIR__ . '/../layout/footer.php'; ?>
