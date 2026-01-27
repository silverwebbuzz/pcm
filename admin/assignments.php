<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['admin_doctor']);

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId = (int) ($_POST['patient_id'] ?? 0);
    $subDoctorId = (int) ($_POST['sub_doctor_id'] ?? 0);
    $stmt = $pdo->prepare('INSERT INTO patient_assignments (patient_id, sub_doctor_id, assigned_by) VALUES (?, ?, ?)');
    $stmt->execute([$patientId, $subDoctorId, current_user()['id']]);
}

$patients = $pdo->query('SELECT id, first_name, last_name FROM patients ORDER BY first_name')->fetchAll();
$subDoctors = $pdo->query("SELECT id, name FROM users WHERE role = 'sub_doctor' ORDER BY name")->fetchAll();

$assignments = $pdo->query('
    SELECT pa.id, p.first_name, p.last_name, u.name AS doctor_name, pa.assigned_at
    FROM patient_assignments pa
    JOIN patients p ON p.id = pa.patient_id
    JOIN users u ON u.id = pa.sub_doctor_id
    ORDER BY pa.assigned_at DESC
')->fetchAll();

require __DIR__ . '/../layout/header.php';
?>
<h2>Assign Patients to Sub-Doctors</h2>
<form method="post">
    <div class="grid">
        <label>Patient
            <select name="patient_id" required>
                <option value="">Select</option>
                <?php foreach ($patients as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo e($p['first_name'] . ' ' . $p['last_name']); ?></option>
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
</form>

<table>
    <thead><tr><th>Patient</th><th>Sub-Doctor</th><th>Assigned At</th></tr></thead>
    <tbody>
    <?php foreach ($assignments as $a): ?>
        <tr>
            <td><?php echo e($a['first_name'] . ' ' . $a['last_name']); ?></td>
            <td><?php echo e($a['doctor_name']); ?></td>
            <td><?php echo e($a['assigned_at']); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../layout/footer.php'; ?>
