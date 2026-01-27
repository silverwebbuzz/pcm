<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['patient']);

require __DIR__ . '/../layout/header.php';
?>
<div class="page-header">
    <div>
        <h2>Patient Dashboard</h2>
        <div class="page-subtitle">View your profile, sessions, and payments</div>
    </div>
</div>
<div class="card-grid">
    <a class="card-link" href="profile.php">
        <div class="card-link-title">My Profile</div>
        <div class="card-link-desc">Personal and assessment details</div>
    </a>
    <a class="card-link" href="sessions.php">
        <div class="card-link-title">Session History</div>
        <div class="card-link-desc">Track attendance and notes</div>
    </a>
    <a class="card-link" href="payments.php">
        <div class="card-link-title">Payments</div>
        <div class="card-link-desc">View invoices and receipts</div>
    </a>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
