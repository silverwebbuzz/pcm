<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['sub_doctor']);

$stmt = db()->prepare('
    SELECT pa.case_id, p.id AS patient_id, p.first_name, p.last_name, p.phone, pc.visit_date, pc.chief_complain
    FROM patient_assignments pa
    JOIN patients p ON p.id = pa.patient_id
    JOIN patient_cases pc ON pc.id = pa.case_id
    WHERE pa.sub_doctor_id = ? AND pc.status = "open"
    ORDER BY pc.visit_date DESC
');
$stmt->execute([current_user()['id']]);
$patients = $stmt->fetchAll();

require __DIR__ . '/../layout/header.php';
?>
<h2>Assigned Patients</h2>
<table>
    <thead><tr><th>Name</th><th>Case</th><th>Phone</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($patients as $p): ?>
        <tr>
            <td><?php echo e($p['first_name'] . ' ' . $p['last_name']); ?></td>
            <td><?php echo e($p['visit_date']); ?> - <?php echo e($p['chief_complain']); ?></td>
            <td><?php echo e($p['phone']); ?></td>
            <td><a class="btn" href="sessions.php?case_id=<?php echo $p['case_id']; ?>">Add Notes</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../layout/footer.php'; ?>
