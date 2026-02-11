<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['admin_doctor']);

$pdo = db();
$patientId = (int) ($_GET['patient_id'] ?? 0);
$status = trim($_GET['status'] ?? '');
$search = trim($_GET['q'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_case_id'])) {
    $caseId = (int) $_POST['close_case_id'];
    $pdo->prepare('UPDATE patient_cases SET status = "closed", closed_at = NOW() WHERE id = ?')->execute([$caseId]);
    redirect('admin/cases.php');
}

$query = '
    SELECT pc.*, p.first_name, p.last_name
    FROM patient_cases pc
    JOIN patients p ON p.id = pc.patient_id
    WHERE 1=1
';
$params = [];
if ($patientId) {
    $query .= ' AND pc.patient_id = ?';
    $params[] = $patientId;
}
if ($status !== '') {
    $query .= ' AND pc.status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $query .= ' AND (p.first_name LIKE ? OR p.last_name LIKE ? OR pc.chief_complain LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$query .= ' ORDER BY pc.created_at DESC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$cases = $stmt->fetchAll();

require __DIR__ . '/../layout/header.php';
?>
<div class="page-header">
    <div>
        <h2>Case Dashboard</h2>
        <div class="page-subtitle">Active and completed cases across all patients</div>
    </div>
    <div class="form-actions">
        <a class="btn" href="case_add.php<?php echo $patientId ? ('?patient_id=' . $patientId) : ''; ?>">Add New Case</a>
    </div>
</div>

<form class="toolbar" method="get">
    <div class="toolbar-row">
        <div>
            <input type="text" name="q" placeholder="Search by patient or complaint..." value="<?php echo e($search); ?>">
        </div>
        <div>
            <select name="status">
                <option value="">All Status</option>
                <option value="open" <?php if ($status === 'open') echo 'selected'; ?>>Open</option>
                <option value="closed" <?php if ($status === 'closed') echo 'selected'; ?>>Closed</option>
            </select>
        </div>
    </div>
    <div class="actions">
        <button class="btn" type="submit">Filter</button>
        <a class="btn ghost" href="cases.php">Reset</a>
    </div>
</form>

<div class="table-wrap">
<table>
    <thead>
    <tr>
        <th>Patient</th>
        <th>Visit Date</th>
        <th>Chief Complaint</th>
        <th>Diagnosis</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($cases as $case): ?>
        <tr>
            <td><?php echo e($case['first_name'] . ' ' . $case['last_name']); ?></td>
            <td><?php echo e($case['visit_date']); ?></td>
            <td><?php echo e($case['chief_complain']); ?></td>
            <td><?php echo e($case['diagnosis']); ?></td>
            <td><span class="badge <?php echo $case['status'] === 'open' ? 'success' : 'muted'; ?>"><?php echo e($case['status']); ?></span></td>
            <td>
                <a class="btn" href="case_view.php?case_id=<?php echo $case['id']; ?>">Open</a>
                <?php if ($case['status'] === 'open'): ?>
                    <form method="post" style="display:inline-block">
                        <input type="hidden" name="close_case_id" value="<?php echo $case['id']; ?>">
                        <button class="btn ghost" type="submit">Close</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
