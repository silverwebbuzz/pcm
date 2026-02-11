<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['admin_doctor']);

$pdo = db();
$patientId = (int) ($_GET['patient_id'] ?? 0);
$editId = (int) ($_GET['edit'] ?? 0);
$editPlan = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM treatment_plans WHERE id = ?');
    $stmt->execute([$editId]);
    $editPlan = $stmt->fetch();
    if ($editPlan) {
        $patientId = (int) $editPlan['patient_id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $planId = (int) ($_POST['plan_id'] ?? 0);
    $patientId = (int) ($_POST['patient_id'] ?? 0);
    $totalSessions = (int) ($_POST['total_sessions'] ?? 10);
    $startDate = $_POST['start_date'] ?? current_date();
    $notes = trim($_POST['notes'] ?? '');
    $status = trim($_POST['status'] ?? 'active');

    if ($planId) {
        $stmt = $pdo->prepare('UPDATE treatment_plans SET patient_id = ?, total_sessions = ?, start_date = ?, notes = ?, status = ? WHERE id = ?');
        $stmt->execute([$patientId, $totalSessions, $startDate, $notes, $status, $planId]);
    } else {
        $visitId = $patientId ? latest_visit_id($patientId) : null;
        $stmt = $pdo->prepare('INSERT INTO treatment_plans (patient_id, visit_id, total_sessions, start_date, notes, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$patientId, $visitId, $totalSessions, $startDate, $notes, $status, current_user()['id']]);
    }
}

if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    $pdo->prepare('DELETE FROM treatment_plans WHERE id = ?')->execute([$deleteId]);
}

$patientsStmt = $pdo->query('SELECT id, first_name, last_name FROM patients ORDER BY first_name');
$patients = $patientsStmt->fetchAll();

$query = 'SELECT tp.*, p.first_name, p.last_name FROM treatment_plans tp JOIN patients p ON p.id = tp.patient_id';
$params = [];
if ($patientId) {
    $query .= ' WHERE tp.patient_id = ?';
    $params[] = $patientId;
}
$query .= ' ORDER BY tp.created_at DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$plans = $stmt->fetchAll();

require __DIR__ . '/../layout/header.php';
?>
<h2>Treatment Plans</h2>
<form method="post">
    <input type="hidden" name="plan_id" value="<?php echo $editPlan ? (int) $editPlan['id'] : 0; ?>">
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
        <label>Total Sessions
            <select name="total_sessions">
                <?php foreach ([10,15,20] as $count): ?>
                    <option value="<?php echo $count; ?>" <?php if (($editPlan['total_sessions'] ?? 10) == $count) echo 'selected'; ?>>
                        <?php echo $count; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Start Date
            <input type="date" name="start_date" value="<?php echo e($editPlan['start_date'] ?? current_date()); ?>">
        </label>
        <label>Status
            <select name="status">
                <?php foreach (['active','completed','paused'] as $st): ?>
                    <option value="<?php echo $st; ?>" <?php if (($editPlan['status'] ?? 'active') === $st) echo 'selected'; ?>>
                        <?php echo ucfirst($st); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <label>Notes
        <textarea name="notes" rows="2"><?php echo e($editPlan['notes'] ?? ''); ?></textarea>
    </label>
    <button class="btn" type="submit"><?php echo $editPlan ? 'Update Plan' : 'Create Plan'; ?></button>
</form>

<table>
    <thead><tr><th>Patient</th><th>Start</th><th>Total</th><th>Status</th><th>Notes</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($plans as $plan): ?>
        <tr>
            <td><?php echo e($plan['first_name'] . ' ' . $plan['last_name']); ?></td>
            <td><?php echo e($plan['start_date']); ?></td>
            <td><?php echo e($plan['total_sessions']); ?></td>
            <td><?php echo e($plan['status']); ?></td>
            <td><?php echo e($plan['notes']); ?></td>
            <td>
                <a class="btn secondary" href="treatment_plans.php?edit=<?php echo $plan['id']; ?>">Edit</a>
                <a class="btn secondary" href="treatment_plans.php?delete=<?php echo $plan['id']; ?>">Delete</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../layout/footer.php'; ?>
