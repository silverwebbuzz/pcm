<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['admin_doctor']);

$pdo = db();

$patientCount = (int) $pdo->query('SELECT COUNT(*) FROM patients')->fetchColumn();

$todaySessionsStmt = $pdo->prepare('SELECT COUNT(*) FROM sessions WHERE session_date = ?');
$todaySessionsStmt->execute([current_date()]);
$todaySessions = (int) $todaySessionsStmt->fetchColumn();

$remainingStmt = $pdo->query('
    SELECT COALESCE(SUM(tp.total_sessions - IFNULL(s.session_count, 0)), 0) AS remaining
    FROM treatment_plans tp
    LEFT JOIN (
        SELECT treatment_plan_id, COUNT(*) AS session_count
        FROM sessions
        GROUP BY treatment_plan_id
    ) s ON s.treatment_plan_id = tp.id
');
$remainingSessions = (int) $remainingStmt->fetchColumn();

require __DIR__ . '/../layout/header.php';
?>
<h2>Admin Doctor Dashboard</h2>
<div class="grid">
    <div>
        <h3>Total Patients</h3>
        <p><?php echo $patientCount; ?></p>
    </div>
    <div>
        <h3>Today's Sessions</h3>
        <p><?php echo $todaySessions; ?></p>
    </div>
    <div>
        <h3>Remaining Sessions</h3>
        <p><?php echo $remainingSessions; ?></p>
    </div>
</div>

<h3>Quick Links</h3>
<p>
    <a class="btn" href="patients.php">Patients</a>
    <a class="btn" href="treatment_plans.php">Treatment Plans</a>
    <a class="btn" href="sessions.php">Sessions</a>
    <a class="btn" href="payments.php">Payments</a>
    <a class="btn" href="users.php">Users</a>
    <a class="btn" href="assignments.php">Assign Patients</a>
    <?php if ((int) current_user()['can_view_reports'] === 1): ?>
        <a class="btn" href="reports.php">Reports</a>
    <?php endif; ?>
</p>
<?php require __DIR__ . '/../layout/footer.php'; ?>
