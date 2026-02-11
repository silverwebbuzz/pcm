<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['sub_doctor']);

$pdo = db();
$caseId = (int) ($_GET['case_id'] ?? 0);
$editSessionId = (int) ($_GET['edit'] ?? 0);
$editSession = null;
$userId = current_user()['id'];

$assignedCases = $pdo->prepare('
    SELECT pa.case_id, p.id AS patient_id, p.first_name, p.last_name, pc.visit_date, pc.chief_complain
    FROM patient_assignments pa
    JOIN patients p ON p.id = pa.patient_id
    JOIN patient_cases pc ON pc.id = pa.case_id
    WHERE pa.sub_doctor_id = ? AND pc.status = "open"
    ORDER BY pc.visit_date DESC
');
$assignedCases->execute([$userId]);
$cases = $assignedCases->fetchAll();

if ($editSessionId) {
    $stmt = $pdo->prepare('SELECT * FROM sessions WHERE id = ? AND created_by = ?');
    $stmt->execute([$editSessionId, $userId]);
    $editSession = $stmt->fetch();
    if ($editSession) {
        $caseId = (int) $editSession['case_id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sessionId = (int) ($_POST['session_id'] ?? 0);
    $caseId = (int) ($_POST['case_id'] ?? 0);
    $planId = (int) ($_POST['treatment_plan_id'] ?? 0);
    $date = $_POST['session_date'] ?? current_date();
    $attendance = $_POST['attendance'] ?? 'attended';
    $notes = trim($_POST['notes'] ?? '');

    $check = $pdo->prepare('SELECT patient_id FROM patient_assignments WHERE sub_doctor_id = ? AND case_id = ?');
    $check->execute([$userId, $caseId]);
    $patientId = (int) $check->fetchColumn();
    if ($patientId) {
        if ($sessionId) {
            $pdo->prepare('UPDATE sessions SET treatment_plan_id = ?, case_id = ?, session_date = ?, attendance = ?, notes = ? WHERE id = ? AND created_by = ?')
                ->execute([$planId, $caseId, $date, $attendance, $notes, $sessionId, $userId]);
        } else {
            $pdo->prepare('INSERT INTO sessions (patient_id, treatment_plan_id, case_id, session_date, attendance, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$patientId, $planId, $caseId, $date, $attendance, $notes, $userId]);
        }
    }
}

$plans = [];
$sessions = [];
if ($caseId) {
    $planStmt = $pdo->prepare('SELECT id, total_sessions FROM treatment_plans WHERE case_id = ? ORDER BY created_at DESC');
    $planStmt->execute([$caseId]);
    $plans = $planStmt->fetchAll();

    $sessionStmt = $pdo->prepare('SELECT * FROM sessions WHERE case_id = ? ORDER BY session_date DESC');
    $sessionStmt->execute([$caseId]);
    $sessions = $sessionStmt->fetchAll();
}

require __DIR__ . '/../layout/header.php';
?>
<h2>Session Notes</h2>
<form method="post">
    <div class="form-card">
        <input type="hidden" name="session_id" value="<?php echo $editSession ? (int) $editSession['id'] : 0; ?>">
        <div class="grid">
            <label>Case
                <select name="case_id" required>
                    <option value="">Select</option>
                    <?php foreach ($cases as $c): ?>
                        <option value="<?php echo $c['case_id']; ?>" <?php if ($caseId === (int) $c['case_id']) echo 'selected'; ?>>
                            <?php echo e($c['first_name'] . ' ' . $c['last_name']); ?> - <?php echo e($c['visit_date']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Treatment Plan
                <select name="treatment_plan_id" required>
                    <?php foreach ($plans as $plan): ?>
                        <option value="<?php echo $plan['id']; ?>" <?php if (($editSession['treatment_plan_id'] ?? 0) == $plan['id']) echo 'selected'; ?>>
                            Plan #<?php echo $plan['id']; ?> (<?php echo $plan['total_sessions']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Date
                <input type="date" name="session_date" value="<?php echo e($editSession['session_date'] ?? current_date()); ?>">
            </label>
            <label>Attendance
                <select name="attendance">
                    <?php foreach (['attended','missed','cancelled'] as $att): ?>
                        <option value="<?php echo $att; ?>" <?php if (($editSession['attendance'] ?? 'attended') === $att) echo 'selected'; ?>>
                            <?php echo ucfirst($att); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Notes
            <textarea name="notes" rows="3"><?php echo e($editSession['notes'] ?? ''); ?></textarea>
        </label>
        <button class="btn" type="submit"><?php echo $editSession ? 'Update Notes' : 'Save Notes'; ?></button>
    </div>
</form>

<?php if ($caseId): ?>
    <h3>Session History</h3>
    <table class="data-table" data-page-size="7">
        <thead><tr><th>Date</th><th>Attendance</th><th>Notes</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($sessions as $s): ?>
            <tr>
                <td><?php echo e($s['session_date']); ?></td>
                <td><?php echo e($s['attendance']); ?></td>
                <td><?php echo e($s['notes']); ?></td>
                <td>
                    <?php if ((int) $s['created_by'] === $userId): ?>
                        <a class="btn secondary" href="sessions.php?patient_id=<?php echo $patientId; ?>&edit=<?php echo $s['id']; ?>">Edit</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>
