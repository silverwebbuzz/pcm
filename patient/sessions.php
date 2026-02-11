<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['patient']);

$user = current_user();
$stmt = db()->prepare('SELECT id FROM patients WHERE user_id = ?');
$stmt->execute([$user['id']]);
$patientId = (int) $stmt->fetchColumn();

$sessions = [];
$plan = null;
$completed = 0;
if ($patientId) {
    $stmt = db()->prepare('SELECT * FROM treatment_plans WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$patientId]);
    $plan = $stmt->fetch();

    $stmt = db()->prepare('SELECT * FROM sessions WHERE patient_id = ? ORDER BY session_date DESC');
    $stmt->execute([$patientId]);
    $sessions = $stmt->fetchAll();
    $completed = count($sessions);
}

require __DIR__ . '/../layout/header.php';
?>
<h2>Session History</h2>
<?php if (!$patientId): ?>
    <p>No patient record found.</p>
<?php else: ?>
    <?php if ($plan): ?>
        <p><strong>Current Plan:</strong> <?php echo e($plan['total_sessions']); ?> sessions</p>
        <p><strong>Completed:</strong> <?php echo $completed; ?> / <?php echo e($plan['total_sessions']); ?></p>
    <?php endif; ?>
    <table class="data-table" data-page-size="7">
        <thead><tr><th>Date</th><th>Attendance</th><th>Notes</th></tr></thead>
        <tbody>
        <?php foreach ($sessions as $s): ?>
            <tr>
                <td><?php echo e($s['session_date']); ?></td>
                <td><?php echo e($s['attendance']); ?></td>
                <td><?php echo e($s['notes']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>
