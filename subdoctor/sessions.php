<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['sub_doctor']);

$pdo = db();
$patientId = (int) ($_GET['patient_id'] ?? 0);
$editSessionId = (int) ($_GET['edit'] ?? 0);
$editSession = null;
$userId = current_user()['id'];

$assignedPatients = $pdo->prepare('
    SELECT p.id, p.first_name, p.last_name
    FROM patient_assignments pa
    JOIN patients p ON p.id = pa.patient_id
    WHERE pa.sub_doctor_id = ?
    ORDER BY p.first_name
');
$assignedPatients->execute([$userId]);
$patients = $assignedPatients->fetchAll();

if ($editSessionId) {
    $stmt = $pdo->prepare('SELECT * FROM sessions WHERE id = ? AND created_by = ?');
    $stmt->execute([$editSessionId, $userId]);
    $editSession = $stmt->fetch();
    if ($editSession) {
        $patientId = (int) $editSession['patient_id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sessionId = (int) ($_POST['session_id'] ?? 0);
    $patientId = (int) ($_POST['patient_id'] ?? 0);
    $planId = (int) ($_POST['treatment_plan_id'] ?? 0);
    $date = $_POST['session_date'] ?? current_date();
    $attendance = $_POST['attendance'] ?? 'attended';
    $notes = trim($_POST['notes'] ?? '');

    $check = $pdo->prepare('SELECT 1 FROM patient_assignments WHERE sub_doctor_id = ? AND patient_id = ?');
    $check->execute([$userId, $patientId]);
    if ($check->fetchColumn()) {
        if ($sessionId) {
            $pdo->prepare('UPDATE sessions SET treatment_plan_id = ?, session_date = ?, attendance = ?, notes = ? WHERE id = ? AND created_by = ?')
                ->execute([$planId, $date, $attendance, $notes, $sessionId, $userId]);
        } else {
            $visitId = $patientId ? latest_visit_id($patientId) : null;
            $pdo->prepare('INSERT INTO sessions (patient_id, treatment_plan_id, visit_id, session_date, attendance, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$patientId, $planId, $visitId, $date, $attendance, $notes, $userId]);
        }
    }
}

$plans = [];
$sessions = [];
if ($patientId) {
    $planStmt = $pdo->prepare('SELECT id, total_sessions FROM treatment_plans WHERE patient_id = ? ORDER BY created_at DESC');
    $planStmt->execute([$patientId]);
    $plans = $planStmt->fetchAll();

    $sessionStmt = $pdo->prepare('SELECT * FROM sessions WHERE patient_id = ? ORDER BY session_date DESC');
    $sessionStmt->execute([$patientId]);
    $sessions = $sessionStmt->fetchAll();
}

require __DIR__ . '/../layout/header.php';
?>
<h2>Session Notes</h2>
<form method="post">
    <input type="hidden" name="session_id" value="<?php echo $editSession ? (int) $editSession['id'] : 0; ?>">
    <div class="grid">
        <label>Patient
            <select name="patient_id" required>
                <option value="">Select</option>
                <?php foreach ($patients as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php if ($patientId === (int) $p['id']) echo 'selected'; ?>>
                        <?php echo e($p['first_name'] . ' ' . $p['last_name']); ?>
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
</form>

<?php if ($patientId): ?>
    <h3>Session History</h3>
    <table>
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
