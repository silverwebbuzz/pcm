<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['admin_doctor']);

$pdo = db();

$cases = $pdo->query("
    SELECT pc.id AS case_id, p.id AS patient_id, p.first_name, p.last_name, pc.visit_date, pc.chief_complain
    FROM patient_cases pc
    JOIN patients p ON p.id = pc.patient_id
    WHERE pc.status = 'open'
    ORDER BY pc.visit_date DESC
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caseId = (int) ($_POST['case_id'] ?? 0);
    $subDoctorId = (int) ($_POST['sub_doctor_id'] ?? 0);
    $patientIdStmt = $pdo->prepare('SELECT patient_id FROM patient_cases WHERE id = ?');
    $patientIdStmt->execute([$caseId]);
    $patientId = (int) $patientIdStmt->fetchColumn();
    if ($patientId && $caseId) {
        $stmt = $pdo->prepare('INSERT INTO patient_assignments (patient_id, case_id, sub_doctor_id, assigned_by) VALUES (?, ?, ?, ?)');
        $stmt->execute([$patientId, $caseId, $subDoctorId, current_user()['id']]);
    }
}
$subDoctors = $pdo->query("SELECT id, name FROM users WHERE role = 'sub_doctor' ORDER BY name")->fetchAll();

$assignments = $pdo->query('
    SELECT pa.id, p.first_name, p.last_name, u.name AS doctor_name, pa.assigned_at, pc.visit_date, pc.chief_complain
    FROM patient_assignments pa
    JOIN patients p ON p.id = pa.patient_id
    JOIN patient_cases pc ON pc.id = pa.case_id
    JOIN users u ON u.id = pa.sub_doctor_id
    ORDER BY pa.assigned_at DESC
')->fetchAll();

require __DIR__ . '/../layout/header.php';
?>
<h2>Assign Patients to Sub-Doctors</h2>
<form method="post">
    <div class="form-card">
        <div class="grid">
            <label>Open Case
                <select name="case_id" required>
                    <option value="">Select</option>
                    <?php foreach ($cases as $c): ?>
                        <option value="<?php echo $c['case_id']; ?>">
                            <?php echo e($c['first_name'] . ' ' . $c['last_name']); ?> - <?php echo e($c['visit_date']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Sub-Doctor
                <select name="sub_doctor_id" required>
                    <option value="">Select</option>
                    <?php foreach ($subDoctors as $d): ?>
                        <option value="<?php echo $d['id']; ?>"><?php echo e($d['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <button class="btn" type="submit">Assign</button>
    </div>
</form>

<table class="data-table" data-page-size="7">
    <thead><tr><th>Patient</th><th>Case</th><th>Sub-Doctor</th><th>Assigned At</th></tr></thead>
    <tbody>
    <?php foreach ($assignments as $a): ?>
        <tr>
            <td><?php echo e($a['first_name'] . ' ' . $a['last_name']); ?></td>
            <td><?php echo e($a['visit_date']); ?> - <?php echo e($a['chief_complain']); ?></td>
            <td><?php echo e($a['doctor_name']); ?></td>
            <td><?php echo e($a['assigned_at']); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../layout/footer.php'; ?>
