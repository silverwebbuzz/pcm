<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['admin_doctor']);

$pdo = db();
$search = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$queryBase = '
    SELECT p.*,
        (SELECT COUNT(*) FROM patient_cases pc WHERE pc.patient_id = p.id AND pc.status = "open") AS open_cases,
        (SELECT MAX(visit_date) FROM patient_cases pc WHERE pc.patient_id = p.id) AS last_case_date
    FROM patients p
    WHERE 1=1
';
$params = [];
if ($search !== '') {
    $queryBase .= ' AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.phone LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM (' . $queryBase . ') AS count_table');
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$query = $queryBase . ' ORDER BY p.created_at DESC LIMIT ? OFFSET ?';
$stmt = $pdo->prepare($query);
foreach ($params as $index => $value) {
    $stmt->bindValue($index + 1, $value);
}
$stmt->bindValue(count($params) + 1, (int) $perPage, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, (int) $offset, PDO::PARAM_INT);
$stmt->execute();
$patients = $stmt->fetchAll();

$from = $totalRows ? ($offset + 1) : 0;
$to = min($offset + $perPage, $totalRows);

$filters = array_filter(['q' => $search]);
function page_link($page, $filters)
{
    $params = $filters;
    $params['page'] = $page;
    $query = http_build_query($params);
    return 'patients.php' . ($query ? ('?' . $query) : '');
}

require __DIR__ . '/../layout/header.php';
?>
<div class="page-header">
    <div>
        <h2>Patients</h2>
        <div class="page-subtitle">Master patient list (create once, cases added separately)</div>
    </div>
    <div class="form-actions">
        <a class="btn" href="patient_add.php">Add New Patient</a>
    </div>
</div>

<form class="toolbar" method="get">
    <div class="toolbar-row">
        <div>
            <input type="text" name="q" placeholder="Search by name or phone..." value="<?php echo e($search); ?>">
        </div>
    </div>
    <div class="actions">
        <button class="btn" type="submit">Search</button>
        <a class="btn ghost" href="patients.php">Reset</a>
    </div>
</form>

<div class="table-wrap">
<table>
    <thead>
    <tr>
        <th>Patient</th>
        <th>Phone</th>
        <th>Last Case</th>
        <th>Open Cases</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($patients as $p): ?>
        <tr>
            <td>
                <strong><?php echo e($p['first_name'] . ' ' . $p['last_name']); ?></strong><br>
                <span class="row-sub">ID: PT-<?php echo str_pad((string) $p['id'], 3, '0', STR_PAD_LEFT); ?></span>
            </td>
            <td><?php echo e($p['phone']); ?></td>
            <td><?php echo e($p['last_case_date'] ?: 'N/A'); ?></td>
            <td><?php echo (int) $p['open_cases']; ?></td>
            <td>
                <a class="btn" href="patient_view.php?id=<?php echo $p['id']; ?>">Profile</a>
                <a class="btn secondary" href="patient_edit.php?id=<?php echo $p['id']; ?>">Edit</a>
                <a class="btn ghost" href="cases.php?patient_id=<?php echo $p['id']; ?>">Cases</a>
                <a class="btn ghost" href="case_add.php?patient_id=<?php echo $p['id']; ?>">Add Case</a>
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
            for ($i = $start; $i <= $end; $i++) {
                $class = $i === $page ? 'page-btn active' : 'page-btn';
                echo '<a class="' . $class . '" href="' . e(page_link($i, $filters)) . '">' . $i . '</a>';
            }
        ?>
        <a class="page-btn <?php if ($page >= $totalPages) echo 'disabled'; ?>" href="<?php echo e(page_link($page + 1, $filters)); ?>">›</a>
    </div>
</div>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
