<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['sub_doctor']);

require __DIR__ . '/../layout/header.php';
?>
<h2>Sub-Doctor Dashboard</h2>
<p>
    <a class="btn" href="patients.php">Assigned Patients</a>
    <a class="btn" href="sessions.php">Session Notes</a>
</p>
<?php require __DIR__ . '/../layout/footer.php'; ?>
