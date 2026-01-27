<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['admin_doctor']);

if ((int) current_user()['can_view_reports'] !== 1) {
    redirect('dashboard.php');
}

$pdo = db();
$totalPayments = $pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments')->fetchColumn();
$patientCount = $pdo->query('SELECT COUNT(*) FROM patients')->fetchColumn();
$sessionCount = $pdo->query('SELECT COUNT(*) FROM sessions')->fetchColumn();

require __DIR__ . '/../layout/header.php';
?>
<h2>Clinic Reports</h2>
<div class="grid">
    <div>
        <h3>Total Payments</h3>
        <p><?php echo format_money($totalPayments); ?></p>
    </div>
    <div>
        <h3>Total Patients</h3>
        <p><?php echo (int) $patientCount; ?></p>
    </div>
    <div>
        <h3>Total Sessions</h3>
        <p><?php echo (int) $sessionCount; ?></p>
    </div>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
