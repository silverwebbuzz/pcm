<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['sub_doctor']);

require __DIR__ . '/../layout/header.php';
?>
<div class="page-header">
    <div>
        <h2>Sub-Doctor Dashboard</h2>
        <div class="page-subtitle">Access assigned patients and session notes</div>
    </div>
</div>
<div class="card-grid">
    <a class="card-link" href="patients.php">
        <div class="card-link-title">Assigned Patients</div>
        <div class="card-link-desc">View your assigned patient list</div>
    </a>
    <a class="card-link" href="sessions.php">
        <div class="card-link-title">Session Notes</div>
        <div class="card-link-desc">Record attendance and progress</div>
    </a>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
