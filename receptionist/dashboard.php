<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['receptionist']);

require __DIR__ . '/../layout/header.php';
?>
<h2>Receptionist Dashboard</h2>
<p>
    <a class="btn" href="patients.php">Register Patients</a>
    <a class="btn" href="payments.php">Payments</a>
</p>
<?php require __DIR__ . '/../layout/footer.php'; ?>
