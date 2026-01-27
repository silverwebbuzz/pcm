<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['admin_doctor']);

$pdo = db();
$search = trim($_GET['q'] ?? '');
$condition = trim($_GET['condition'] ?? '');
$date = trim($_GET['date'] ?? '');

$statsTotal = (int) $pdo->query('SELECT COUNT(*) FROM patients')->fetchColumn();
$statsPending = (int) $pdo->query('SELECT COUNT(*) FROM patients WHERE assessment_date IS NULL')->fetchColumn();
$statsTodayStmt = $pdo->prepare('SELECT COUNT(*) FROM sessions WHERE session_date = ?');
$statsTodayStmt->execute([current_date()]);
$statsToday = (int) $statsTodayStmt->fetchColumn();
$statsWeekStmt = $pdo->prepare('SELECT COUNT(*) FROM sessions WHERE session_date >= DATE_SUB(?, INTERVAL 7 DAY)');
$statsWeekStmt->execute([current_date()]);
$statsWeek = (int) $statsWeekStmt->fetchColumn();

$query = '
    SELECT p.*, tp.status AS plan_status
    FROM patients p
    LEFT JOIN (
        SELECT t1.*
        FROM treatment_plans t1
        INNER JOIN (
            SELECT patient_id, MAX(created_at) AS max_created
            FROM treatment_plans
            GROUP BY patient_id
        ) t2 ON t1.patient_id = t2.patient_id AND t1.created_at = t2.max_created
    ) tp ON tp.patient_id = p.id
    WHERE 1=1
';
$params = [];
if ($search !== '') {
    $query .= ' AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.chief_complain LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($condition !== '') {
    $query .= ' AND p.diagnosis LIKE ?';
    $params[] = '%' . $condition . '%';
}
if ($date !== '') {
    $query .= ' AND p.assessment_date = ?';
    $params[] = $date;
}
$query .= ' ORDER BY p.created_at DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$patients = $stmt->fetchAll();

require __DIR__ . '/../layout/header.php';
?>
<h2>Patient Dashboard</h2>
<p>Manage your patient roster and access assessment records</p>

<div class="grid">
    <div class="stat-card">
        <div>
            <div class="stat-title">Total Patients</div>
            <div class="stat-value"><?php echo $statsTotal; ?></div>
        </div>
        <div class="stat-icon">👥</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-title">Pending Assessments</div>
            <div class="stat-value"><?php echo $statsPending; ?></div>
        </div>
        <div class="stat-icon">⏱</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-title">Completed Today</div>
            <div class="stat-value"><?php echo $statsToday; ?></div>
        </div>
        <div class="stat-icon">✅</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-title">This Week</div>
            <div class="stat-value"><?php echo $statsWeek; ?></div>
        </div>
        <div class="stat-icon">📅</div>
    </div>
</div>

<form class="toolbar" method="get">
    <div class="field">
        <input type="text" name="q" placeholder="Search by patient name or complaint..." value="<?php echo e($search); ?>">
    </div>
    <div class="field">
        <input type="text" name="condition" placeholder="Condition (diagnosis)" value="<?php echo e($condition); ?>">
    </div>
    <div class="field">
        <input type="date" name="date" value="<?php echo e($date); ?>">
    </div>
    <div class="actions">
        <button class="btn" type="submit">Filter</button>
        <a class="btn ghost" href="patients.php">Reset</a>
        <a class="btn" href="patient_add.php">Add New Patient</a>
    </div>
</form>
<table>
    <thead>
    <tr>
        <th>Patient Name</th>
        <th>Last Assessment</th>
        <th>Chief Complaint</th>
        <th>Duration</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($patients as $p): ?>
        <?php
            $status = 'pending';
            if ($p['plan_status'] === 'completed') {
                $status = 'completed';
            } elseif ($p['plan_status'] === 'active') {
                $status = 'progress';
            }
        ?>
        <tr>
            <td>
                <strong><?php echo e($p['first_name'] . ' ' . $p['last_name']); ?></strong><br>
                <span>ID: PT-<?php echo str_pad((string) $p['id'], 3, '0', STR_PAD_LEFT); ?></span>
            </td>
            <td><?php echo e($p['assessment_date'] ?: 'N/A'); ?></td>
            <td><?php echo e($p['chief_complain']); ?></td>
            <td><?php echo e($p['condition_duration']); ?></td>
            <td><span class="badge <?php echo $status; ?>"><?php echo ucfirst($status === 'progress' ? 'In Progress' : $status); ?></span></td>
            <td>
                <a class="btn" href="patient_view.php?id=<?php echo $p['id']; ?>">View</a>
                <a class="btn secondary" href="patient_edit.php?id=<?php echo $p['id']; ?>">Edit</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../layout/footer.php'; ?>
