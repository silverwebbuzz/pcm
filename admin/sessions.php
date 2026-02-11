<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['admin_doctor']);

$pdo = db();
$caseId = (int) ($_GET['case_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId = (int) ($_POST['patient_id'] ?? 0);
    $planId = (int) ($_POST['treatment_plan_id'] ?? 0);
    $date = $_POST['session_date'] ?? current_date();
    $attendance = $_POST['attendance'] ?? 'attended';
    $notes = trim($_POST['notes'] ?? '');
    $caseId = (int) ($_POST['case_id'] ?? 0) ?: ($patientId ? latest_case_id($patientId) : null);
    $pdo->prepare('INSERT INTO sessions (patient_id, treatment_plan_id, case_id, session_date, attendance, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$patientId, $planId, $caseId, $date, $attendance, $notes, current_user()['id']]);
}

$patients = $pdo->query('SELECT id, first_name, last_name FROM patients ORDER BY first_name')->fetchAll();
$plansQuery = 'SELECT id, patient_id, total_sessions FROM treatment_plans';
$planParams = [];
if ($caseId) {
    $plansQuery .= ' WHERE case_id = ?';
    $planParams[] = $caseId;
}
$plansQuery .= ' ORDER BY created_at DESC';
$plansStmt = $pdo->prepare($plansQuery);
$plansStmt->execute($planParams);
$plans = $plansStmt->fetchAll();
$planDisabled = $caseId && !$plans;

$sessionsQuery = '
    SELECT s.*, p.first_name, p.last_name
    FROM sessions s
    JOIN patients p ON p.id = s.patient_id
';
$sessionParams = [];
if ($caseId) {
    $sessionsQuery .= ' WHERE s.case_id = ?';
    $sessionParams[] = $caseId;
}
$sessionsQuery .= ' ORDER BY s.session_date DESC';
$sessionsStmt = $pdo->prepare($sessionsQuery);
$sessionsStmt->execute($sessionParams);
$sessions = $sessionsStmt->fetchAll();

require __DIR__ . '/../layout/header.php';
?>
<h2>Sessions</h2>
<?php if ($planDisabled): ?>
    <div class="callout">
        <div class="callout-title">Create a plan first</div>
        <div class="callout-body">This case has no treatment plan yet. Create a plan to add sessions.</div>
        <a class="btn" href="treatment_plans.php?case_id=<?php echo $caseId; ?>">Create Treatment Plan</a>
    </div>
<?php endif; ?>
<form method="post">
    <input type="hidden" name="case_id" value="<?php echo $caseId; ?>">
    <div class="grid">
        <label>Patient
            <select name="patient_id" required>
                <option value="">Select</option>
                <?php foreach ($patients as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo e($p['first_name'] . ' ' . $p['last_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Treatment Plan
            <select name="treatment_plan_id" required <?php if ($planDisabled) echo 'disabled'; ?>>
                <option value="">Select</option>
                <?php foreach ($plans as $plan): ?>
                    <option value="<?php echo $plan['id']; ?>">Plan #<?php echo $plan['id']; ?> (<?php echo $plan['total_sessions']; ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Date
            <input type="date" name="session_date" value="<?php echo current_date(); ?>">
        </label>
        <label>Attendance
            <select name="attendance">
                <option value="attended">Attended</option>
                <option value="missed">Missed</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </label>
    </div>
    <label>Notes
        <textarea name="notes" rows="2"></textarea>
    </label>
    <button class="btn" type="submit" <?php if ($planDisabled) echo 'disabled'; ?>>Add Session</button>
</form>

<table>
    <thead><tr><th>Date</th><th>Patient</th><th>Attendance</th><th>Notes</th></tr></thead>
    <tbody>
    <?php foreach ($sessions as $s): ?>
        <tr>
            <td><?php echo e($s['session_date']); ?></td>
            <td><?php echo e($s['first_name'] . ' ' . $s['last_name']); ?></td>
            <td><?php echo e($s['attendance']); ?></td>
            <td><?php echo e($s['notes']); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../layout/footer.php'; ?>
