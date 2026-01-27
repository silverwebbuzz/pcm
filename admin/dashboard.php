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
<div class="page-header">
    <div>
        <h2>Admin Doctor Dashboard</h2>
        <div class="page-subtitle">Overview of clinic activity and quick access</div>
    </div>
</div>

<div class="grid">
    <div class="stat-card">
        <div>
            <div class="stat-title">Total Patients</div>
            <div class="stat-value"><?php echo $patientCount; ?></div>
        </div>
        <div class="stat-icon">👥</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-title">Today's Sessions</div>
            <div class="stat-value"><?php echo $todaySessions; ?></div>
        </div>
        <div class="stat-icon">✅</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-title">Remaining Sessions</div>
            <div class="stat-value"><?php echo $remainingSessions; ?></div>
        </div>
        <div class="stat-icon">📅</div>
    </div>
</div>

<h3>Quick Links</h3>
<div class="card-grid">
    <a class="card-link" href="patients.php">
        <div class="card-link-title">Patients</div>
        <div class="card-link-desc">Manage patient records and assessments</div>
    </a>
    <a class="card-link" href="treatment_plans.php">
        <div class="card-link-title">Treatment Plans</div>
        <div class="card-link-desc">Create and update session cycles</div>
    </a>
    <a class="card-link" href="sessions.php">
        <div class="card-link-title">Sessions</div>
        <div class="card-link-desc">Log attendance and progress notes</div>
    </a>
    <a class="card-link" href="payments.php">
        <div class="card-link-title">Payments</div>
        <div class="card-link-desc">Record payments and issue receipts</div>
    </a>
    <a class="card-link" href="users.php">
        <div class="card-link-title">Users</div>
        <div class="card-link-desc">Manage staff accounts and roles</div>
    </a>
    <a class="card-link" href="assignments.php">
        <div class="card-link-title">Assignments</div>
        <div class="card-link-desc">Assign patients to sub-doctors</div>
    </a>
    <?php if ((int) current_user()['can_view_reports'] === 1): ?>
        <a class="card-link" href="reports.php">
            <div class="card-link-title">Reports</div>
            <div class="card-link-desc">View analytics and totals</div>
        </a>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
