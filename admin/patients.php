<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['admin_doctor']);

$stmt = db()->query('SELECT id, first_name, last_name, phone, created_at FROM patients ORDER BY created_at DESC');
$patients = $stmt->fetchAll();

require __DIR__ . '/../layout/header.php';
?>
<h2>Patients</h2>
<p><a class="btn" href="patient_add.php">Add New Patient</a></p>
<table>
    <thead>
    <tr>
        <th>Name</th>
        <th>Phone</th>
        <th>Created</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($patients as $p): ?>
        <tr>
            <td><?php echo e($p['first_name'] . ' ' . $p['last_name']); ?></td>
            <td><?php echo e($p['phone']); ?></td>
            <td><?php echo e($p['created_at']); ?></td>
            <td>
                <a class="btn" href="patient_view.php?id=<?php echo $p['id']; ?>">View</a>
                <a class="btn secondary" href="patient_edit.php?id=<?php echo $p['id']; ?>">Edit</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../layout/footer.php'; ?>
