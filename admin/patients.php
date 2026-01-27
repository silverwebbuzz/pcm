<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['admin_doctor']);

$pdo = db();
$search = trim($_GET['q'] ?? '');
$condition = trim($_GET['condition'] ?? '');
$date = trim($_GET['date'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 5;

$statsTotal = (int) $pdo->query('SELECT COUNT(*) FROM patients')->fetchColumn();
$statsPending = (int) $pdo->query('SELECT COUNT(*) FROM patients WHERE assessment_date IS NULL')->fetchColumn();
$statsTodayStmt = $pdo->prepare('SELECT COUNT(*) FROM sessions WHERE session_date = ?');
$statsTodayStmt->execute([current_date()]);
$statsToday = (int) $statsTodayStmt->fetchColumn();
$statsWeekStmt = $pdo->prepare('SELECT COUNT(*) FROM sessions WHERE session_date >= DATE_SUB(?, INTERVAL 7 DAY)');
$statsWeekStmt->execute([current_date()]);
$statsWeek = (int) $statsWeekStmt->fetchColumn();

$conditions = $pdo->query("SELECT DISTINCT diagnosis FROM patients WHERE diagnosis <> '' ORDER BY diagnosis ASC LIMIT 30")->fetchAll();

$queryBase = '
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
    $queryBase .= ' AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.chief_complain LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($condition !== '') {
    $queryBase .= ' AND p.diagnosis LIKE ?';
    $params[] = '%' . $condition . '%';
}
if ($date !== '') {
    $queryBase .= ' AND p.assessment_date = ?';
    $params[] = $date;
}
$statusMap = [
    'pending' => " AND (tp.status IS NULL OR tp.status = '')",
    'completed' => " AND tp.status = 'completed'",
    'in_progress' => " AND tp.status = 'active'",
];
if ($status !== '' && isset($statusMap[$status])) {
    $queryBase .= $statusMap[$status];
}

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM (' . $queryBase . ') AS count_table');
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$query = $queryBase . ' ORDER BY p.created_at DESC LIMIT ? OFFSET ?';
$stmt = $pdo->prepare($query);
$paramsWithPaging = array_merge($params, [$perPage, $offset]);
$stmt->execute($paramsWithPaging);
$patients = $stmt->fetchAll();

$from = $totalRows ? ($offset + 1) : 0;
$to = min($offset + $perPage, $totalRows);

$filters = array_filter([
    'q' => $search,
    'condition' => $condition,
    'date' => $date,
    'status' => $status,
]);
function page_link($page, $filters)
{
    $params = $filters;
    $params['page'] = $page;
    $query = http_build_query($params);
    return 'patients.php' . ($query ? ('?' . $query) : '');
}

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
    <div class="toolbar-row">
        <div>
            <input type="text" name="q" placeholder="Search by patient name or complaint..." value="<?php echo e($search); ?>">
        </div>
        <div>
            <select name="status">
                <option value="">All Status</option>
                <option value="pending" <?php if ($status === 'pending') echo 'selected'; ?>>Pending</option>
                <option value="in_progress" <?php if ($status === 'in_progress') echo 'selected'; ?>>In Progress</option>
                <option value="completed" <?php if ($status === 'completed') echo 'selected'; ?>>Completed</option>
            </select>
        </div>
        <div>
            <select name="condition">
                <option value="">All Conditions</option>
                <?php foreach ($conditions as $cond): ?>
                    <option value="<?php echo e($cond['diagnosis']); ?>" <?php if ($condition === $cond['diagnosis']) echo 'selected'; ?>>
                        <?php echo e($cond['diagnosis']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <input type="date" name="date" value="<?php echo e($date); ?>">
        </div>
    </div>
    <div class="actions">
        <button class="btn" type="submit">Filter</button>
        <a class="btn ghost" href="patients.php">Reset</a>
        <button class="icon-btn" type="submit" title="Refresh">⟳</button>
        <a class="btn" href="patient_add.php">Add New Patient</a>
    </div>
</form>

<div class="table-wrap">
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
            $initials = strtoupper(substr($p['first_name'], 0, 1) . substr($p['last_name'], 0, 1));
            $assessmentDate = $p['assessment_date'];
            $daysAgo = '';
            if ($assessmentDate) {
                $dt = new DateTime($assessmentDate);
                $now = new DateTime();
                $diff = (int) $now->diff($dt)->format('%a');
                $daysAgo = $diff === 0 ? 'Today' : ($diff === 1 ? '1 day ago' : $diff . ' days ago');
            }
        ?>
        <tr>
            <td>
                <div class="row-user">
                    <div class="row-avatar"><?php echo e($initials); ?></div>
                    <div>
                        <strong><?php echo e($p['first_name'] . ' ' . $p['last_name']); ?></strong><br>
                        <span class="row-sub">ID: PT-<?php echo str_pad((string) $p['id'], 3, '0', STR_PAD_LEFT); ?></span>
                    </div>
                </div>
            </td>
            <td>
                <?php echo e($assessmentDate ?: 'N/A'); ?>
                <?php if ($daysAgo): ?><div class="row-sub"><?php echo e($daysAgo); ?></div><?php endif; ?>
            </td>
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
<div class="pagination">
    <div class="row-sub">Showing <?php echo $from; ?> to <?php echo $to; ?> of <?php echo $totalRows; ?> patients</div>
    <div class="pager">
        <a class="page-btn <?php if ($page <= 1) echo 'disabled'; ?>" href="<?php echo e(page_link($page - 1, $filters)); ?>">‹</a>
        <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            if ($start > 1) {
                echo '<a class="page-btn" href="' . e(page_link(1, $filters)) . '">1</a>';
                if ($start > 2) {
                    echo '<span class="row-sub">...</span>';
                }
            }
            for ($i = $start; $i <= $end; $i++) {
                $class = $i === $page ? 'page-btn active' : 'page-btn';
                echo '<a class="' . $class . '" href="' . e(page_link($i, $filters)) . '">' . $i . '</a>';
            }
            if ($end < $totalPages) {
                if ($end < $totalPages - 1) {
                    echo '<span class="row-sub">...</span>';
                }
                echo '<a class="page-btn" href="' . e(page_link($totalPages, $filters)) . '">' . $totalPages . '</a>';
            }
        ?>
        <a class="page-btn <?php if ($page >= $totalPages) echo 'disabled'; ?>" href="<?php echo e(page_link($page + 1, $filters)); ?>">›</a>
    </div>
</div>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
