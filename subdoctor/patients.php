<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['sub_doctor']);

$stmt = db()->prepare('
    SELECT p.id, p.first_name, p.last_name, p.phone
    FROM patient_assignments pa
    JOIN patients p ON p.id = pa.patient_id
    WHERE pa.sub_doctor_id = ?
    ORDER BY p.first_name
');
$stmt->execute([current_user()['id']]);
$patients = $stmt->fetchAll();

require __DIR__ . '/../layout/header.php';
?>
<h2>Assigned Patients</h2>
<table>
    <thead><tr><th>Name</th><th>Phone</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($patients as $p): ?>
        <tr>
            <td><?php echo e($p['first_name'] . ' ' . $p['last_name']); ?></td>
            <td><?php echo e($p['phone']); ?></td>
            <td><a class="btn" href="sessions.php?patient_id=<?php echo $p['id']; ?>">Add Notes</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../layout/footer.php'; ?>
