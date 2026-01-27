<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['receptionist']);

require __DIR__ . '/../layout/header.php';
?>
<div class="page-header">
    <div>
        <h2>Receptionist Dashboard</h2>
        <div class="page-subtitle">Register patients and manage payments</div>
    </div>
</div>
<div class="card-grid">
    <a class="card-link" href="patients.php">
        <div class="card-link-title">Register Patients</div>
        <div class="card-link-desc">Add or update patient details</div>
    </a>
    <a class="card-link" href="payments.php">
        <div class="card-link-title">Payments</div>
        <div class="card-link-desc">Record and review payment entries</div>
    </a>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
