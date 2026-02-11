<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['admin_doctor']);

$id = (int) ($_GET['id'] ?? 0);
$pdo = db();

$stmt = $pdo->prepare('SELECT * FROM patients WHERE id = ?');
$stmt->execute([$id]);
$patient = $stmt->fetch();
if (!$patient) {
    redirect('admin/patients.php');
}

$casesStmt = $pdo->prepare('
    SELECT id, visit_date, chief_complain, diagnosis, status, closed_at
    FROM patient_cases
    WHERE patient_id = ?
    ORDER BY created_at DESC
');
$casesStmt->execute([$id]);
$cases = $casesStmt->fetchAll();

require __DIR__ . '/../layout/header.php';
?>
<div class="page-header">
    <div class="row-user">
        <div class="row-avatar"><?php echo e(strtoupper(substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1))); ?></div>
        <div>
            <h2><?php echo e($patient['first_name'] . ' ' . $patient['last_name']); ?></h2>
            <div class="row-sub">Patient ID: PT-<?php echo str_pad((string) $patient['id'], 3, '0', STR_PAD_LEFT); ?></div>
        </div>
    </div>
    <div class="form-actions">
        <a class="btn ghost" href="patients.php">Back to Patients</a>
        <a class="btn secondary" href="patient_edit.php?id=<?php echo $patient['id']; ?>">Edit Patient</a>
        <a class="btn" href="case_add.php?patient_id=<?php echo $patient['id']; ?>">Add New Case</a>
    </div>
</div>

<div class="section-card section-title">
    <h3>Patient Profile</h3>
</div>
<div class="card-grid two-col">
    <div class="section-card soft">
        <div class="info-grid">
            <div class="info-item"><div class="info-label">Age</div><div class="info-value"><?php echo e($patient['age']); ?></div></div>
            <div class="info-item"><div class="info-label">Gender</div><div class="info-value"><?php echo e($patient['gender']); ?></div></div>
            <div class="info-item"><div class="info-label">DOB</div><div class="info-value"><?php echo e($patient['dob']); ?></div></div>
            <div class="info-item"><div class="info-label">Occupation</div><div class="info-value"><?php echo e($patient['occupation']); ?></div></div>
        </div>
    </div>
    <div class="section-card soft">
        <div class="info-grid">
            <div class="info-item"><div class="info-label">Phone</div><div class="info-value"><?php echo e($patient['phone']); ?></div></div>
            <div class="info-item"><div class="info-label">Address</div><div class="info-value"><?php echo e($patient['address']); ?></div></div>
            <div class="info-item"><div class="info-label">Emergency Contact</div><div class="info-value"><?php echo e($patient['emergency_contact']); ?></div></div>
        </div>
    </div>
</div>

<div class="section-card section-title">
    <h3>Cases</h3>
</div>
<div class="table-wrap">
    <table class="data-table" data-page-size="7">
        <thead>
        <tr>
            <th>Date</th>
            <th>Chief Complaint</th>
            <th>Diagnosis</th>
            <th>Status</th>
            <th>Closed At</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($cases as $case): ?>
            <tr>
                <td><?php echo e($case['visit_date']); ?></td>
                <td><?php echo e($case['chief_complain']); ?></td>
                <td><?php echo e($case['diagnosis']); ?></td>
                <td><?php echo e($case['status']); ?></td>
                <td><?php echo e($case['closed_at']); ?></td>
                <td><a class="btn" href="case_view.php?case_id=<?php echo $case['id']; ?>">Open Case</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
