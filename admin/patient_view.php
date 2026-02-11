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

// Quick add session notes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_session'])) {
    $planId = (int) ($_POST['treatment_plan_id'] ?? 0);
    $date = $_POST['session_date'] ?? current_date();
    $attendance = $_POST['attendance'] ?? 'attended';
    $notes = trim($_POST['notes'] ?? '');
    $stmt = $pdo->prepare('INSERT INTO sessions (patient_id, treatment_plan_id, session_date, attendance, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$id, $planId, $date, $attendance, $notes, current_user()['id']]);
}

// Document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    if (!empty($_FILES['document']['name'])) {
        $file = $_FILES['document'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        if (in_array($ext, $allowed, true)) {
            $patientDir = UPLOAD_DIR . '/patient_' . $id;
            if (!is_dir($patientDir)) {
                mkdir($patientDir, 0775, true);
            }
            $filename = uniqid('doc_', true) . '.' . $ext;
            $target = $patientDir . '/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $target)) {
                $stmt = $pdo->prepare('INSERT INTO patient_documents (patient_id, file_name, file_path, uploaded_by) VALUES (?, ?, ?, ?)');
                $stmt->execute([$id, $file['name'], 'uploads/patient_' . $id . '/' . $filename, current_user()['id']]);
            }
        }
    }
}

$plans = $pdo->prepare('SELECT * FROM treatment_plans WHERE patient_id = ? ORDER BY created_at DESC');
$plans->execute([$id]);
$plans = $plans->fetchAll();

$sessions = $pdo->prepare('SELECT * FROM sessions WHERE patient_id = ? ORDER BY session_date DESC');
$sessions->execute([$id]);
$sessions = $sessions->fetchAll();

$payments = $pdo->prepare('SELECT * FROM payments WHERE patient_id = ? ORDER BY payment_date DESC');
$payments->execute([$id]);
$payments = $payments->fetchAll();

$documents = $pdo->prepare('SELECT * FROM patient_documents WHERE patient_id = ? ORDER BY uploaded_at DESC');
$documents->execute([$id]);
$documents = $documents->fetchAll();

$patientEmail = '';
if (!empty($patient['user_id'])) {
    $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
    $stmt->execute([$patient['user_id']]);
    $patientEmail = (string) $stmt->fetchColumn();
}

$painStmt = $pdo->prepare('
    SELECT pm.category, pm.subcategory
    FROM patient_pain pp
    JOIN pain_master pm ON pm.id = pp.pain_master_id
    WHERE pp.patient_id = ?
    ORDER BY pm.category, pm.subcategory
');
$painStmt->execute([$id]);
$painRows = $painStmt->fetchAll();
$painByCategory = [];
foreach ($painRows as $row) {
    $painByCategory[$row['category']][] = $row['subcategory'];
}

require __DIR__ . '/../layout/header.php';
?>
<?php
    $initials = strtoupper(substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1));
    $completedSessions = count($sessions);
    $totalPlanSessions = $plans[0]['total_sessions'] ?? 0;
    $painValue = $patient['pain_measurement'] !== null ? (int) $patient['pain_measurement'] : null;
?>

<div class="page-header">
    <div class="row-user">
        <div class="row-avatar"><?php echo e($initials); ?></div>
        <div>
            <h2><?php echo e($patient['first_name'] . ' ' . $patient['last_name']); ?></h2>
            <div class="row-sub">Patient ID: PT-<?php echo str_pad((string) $patient['id'], 3, '0', STR_PAD_LEFT); ?></div>
        </div>
    </div>
    <div class="form-actions">
        <a class="btn ghost" href="patients.php">Back to Patients</a>
        <a class="btn" href="patient_edit.php?id=<?php echo $patient['id']; ?>">Edit Profile</a>
    </div>
</div>

<div class="grid">
    <div class="stat-card">
        <div>
            <div class="stat-title">Assessment Date</div>
            <div class="stat-value"><?php echo e($patient['assessment_date'] ?: 'N/A'); ?></div>
        </div>
        <div class="stat-icon">📝</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-title">Sessions Completed</div>
            <div class="stat-value"><?php echo $completedSessions; ?></div>
        </div>
        <div class="stat-icon">✅</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-title">Plan Sessions</div>
            <div class="stat-value"><?php echo $totalPlanSessions ?: 'N/A'; ?></div>
        </div>
        <div class="stat-icon">📅</div>
    </div>
</div>

<div class="section-card section-title">
    <h3>Patient Demographics</h3>
</div>
<div class="card-grid two-col">
    <div class="section-card soft">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Patient Name</div>
                <div class="info-value"><?php echo e($patient['first_name'] . ' ' . $patient['last_name']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Patient ID</div>
                <div class="info-value">PT-<?php echo str_pad((string) $patient['id'], 3, '0', STR_PAD_LEFT); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Age</div>
                <div class="info-value"><?php echo e($patient['age']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Gender</div>
                <div class="info-value"><?php echo e($patient['gender']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Occupation</div>
                <div class="info-value"><?php echo e($patient['occupation']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Dominance</div>
                <div class="info-value"><?php echo e($patient['dominance']); ?></div>
            </div>
        </div>
    </div>
    <div class="section-card soft">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Contact Number</div>
                <div class="info-value"><?php echo e($patient['phone']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Email Address</div>
                <div class="info-value"><?php echo e($patientEmail ?: 'N/A'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Address</div>
                <div class="info-value"><?php echo e($patient['address']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Assessment Date</div>
                <div class="info-value"><?php echo e($patient['assessment_date'] ?: 'N/A'); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="section-card section-title">
    <h3>Chief Complaint</h3>
</div>
<div class="callout warning">
    <div class="callout-body"><?php echo nl2br(e($patient['chief_complain'])); ?></div>
    <div class="callout-meta">
        <span>Duration: <?php echo e($patient['condition_duration'] ?: 'N/A'); ?></span>
        <span>Pain Scale: <?php echo e($patient['pain_measurement'] ?? 'N/A'); ?></span>
    </div>
</div>
<?php if (!empty($painByCategory)): ?>
    <div class="section-card" style="margin-top:12px;">
        <div class="info-label">Pain Areas &amp; Subcategories</div>
        <?php foreach ($painByCategory as $category => $subs): ?>
            <div style="margin-top:8px;">
                <strong><?php echo e($category); ?></strong>
                <div class="chip-group">
                    <?php foreach ($subs as $sub): ?>
                        <span class="chip"><?php echo e($sub); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="section-card section-title">
    <h3>Medical History</h3>
</div>
<div class="card-grid">
    <div class="section-card">
        <div class="info-label">History of Present Illness</div>
        <div class="info-value"><?php echo nl2br(e($patient['history_present_illness'])); ?></div>
    </div>
    <div class="section-card">
        <div class="info-label">Past Medical History</div>
        <div class="info-value"><?php echo nl2br(e($patient['past_medical_history'])); ?></div>
    </div>
    <div class="section-card">
        <div class="info-label">Surgical History</div>
        <div class="info-value"><?php echo nl2br(e($patient['surgical_history'])); ?></div>
    </div>
    <div class="section-card">
        <div class="info-label">Family History</div>
        <div class="info-value"><?php echo nl2br(e($patient['family_history'])); ?></div>
    </div>
</div>

<div class="section-card section-title">
    <h3>Physical Examination</h3>
</div>
<div class="section-card soft">
    <h4 class="subhead">Observation</h4>
    <div class="info-grid">
        <div class="info-item"><div class="info-label">Build</div><div class="info-value"><?php echo nl2br(e($patient['observation_built'])); ?></div></div>
        <div class="info-item"><div class="info-label">Posture</div><div class="info-value"><?php echo nl2br(e($patient['observation_posture'])); ?></div></div>
        <div class="info-item"><div class="info-label">Attitude of Limb</div><div class="info-value"><?php echo nl2br(e($patient['observation_attitude_limb'])); ?></div></div>
        <div class="info-item"><div class="info-label">Deformity</div><div class="info-value"><?php echo nl2br(e($patient['observation_deformity'])); ?></div></div>
    </div>
    <div class="info-grid" style="margin-top:10px;">
        <div class="info-item"><div class="info-label">Aids &amp; Applications</div><div class="info-value"><?php echo nl2br(e($patient['aids_applications'])); ?></div></div>
        <div class="info-item"><div class="info-label">Gait</div><div class="info-value"><?php echo nl2br(e($patient['gait'])); ?></div></div>
    </div>
</div>

<div class="section-card">
    <h4 class="subhead">Palpation Findings</h4>
    <div class="info-grid">
        <div class="info-item"><div class="info-label">Tenderness</div><div class="info-value"><?php echo nl2br(e($patient['palpation_tenderness'])); ?></div></div>
        <div class="info-item"><div class="info-label">Edema</div><div class="info-value"><?php echo e($patient['palpation_oedema'] ?: 'N/A'); ?></div></div>
        <div class="info-item"><div class="info-label">Warmth</div><div class="info-value"><?php echo nl2br(e($patient['palpation_warmth'])); ?></div></div>
        <div class="info-item"><div class="info-label">Crepitus</div><div class="info-value"><?php echo nl2br(e($patient['palpation_crepitus'])); ?></div></div>
    </div>
</div>

<div class="section-card">
    <h4 class="subhead">Examination</h4>
    <div class="info-grid">
        <div class="info-item"><div class="info-label">ROM</div><div class="info-value"><?php echo nl2br(e($patient['examination_rom'])); ?></div></div>
        <div class="info-item"><div class="info-label">Muscle Power</div><div class="info-value"><?php echo nl2br(e($patient['muscle_power'])); ?></div></div>
        <div class="info-item"><div class="info-label">Muscle Bulk</div><div class="info-value"><?php echo nl2br(e($patient['muscle_bulk'])); ?></div></div>
        <div class="info-item"><div class="info-label">Ligament Instability</div><div class="info-value"><?php echo nl2br(e($patient['ligament_instability'])); ?></div></div>
    </div>
</div>

<div class="section-card section-title">
    <h3>Pain Assessment</h3>
</div>
<div class="section-card soft">
    <div class="pain-scale">
        <div class="pain-scale-labels">
            <span>No Pain</span>
            <span>Worst Pain</span>
        </div>
        <div class="pain-scale-bar">
            <?php for ($i = 0; $i <= 10; $i++): ?>
                <span class="pain-step <?php echo ($painValue !== null && $painValue === $i) ? 'active' : ''; ?>"><?php echo $i; ?></span>
            <?php endfor; ?>
        </div>
        <div class="pain-scale-value">Current Pain Level: <?php echo $painValue !== null ? $painValue . '/10' : 'N/A'; ?></div>
    </div>
</div>
<div class="card-grid">
    <div class="section-card"><div class="info-label">Pain Type</div><div class="info-value"><?php echo nl2br(e($patient['pain_type'])); ?></div></div>
    <div class="section-card"><div class="info-label">Pain Site</div><div class="info-value"><?php echo nl2br(e($patient['pain_site'])); ?></div></div>
    <div class="section-card"><div class="info-label">Pain Nature</div><div class="info-value"><?php echo nl2br(e($patient['pain_nature'])); ?></div></div>
    <div class="section-card"><div class="info-label">Aggravating Factors</div><div class="info-value"><?php echo nl2br(e($patient['pain_aggravating_factor'])); ?></div></div>
    <div class="section-card"><div class="info-label">Relieving Factors</div><div class="info-value"><?php echo nl2br(e($patient['pain_relieving_factor'])); ?></div></div>
    <div class="section-card"><div class="info-label">Gait Assessment</div><div class="info-value"><?php echo nl2br(e($patient['gait_assessment'])); ?></div></div>
</div>

<div class="section-card section-title">
    <h3>Treatment Recommendations</h3>
</div>
<div class="section-card">
    <div class="info-label">Treatment Goals</div>
    <div class="info-value"><?php echo nl2br(e($patient['treatment_goals'])); ?></div>
</div>

<div class="section-card" style="margin-top:16px;">
    <div class="page-header">
        <h3>Treatment Plans</h3>
        <a class="btn" href="treatment_plans.php?patient_id=<?php echo $id; ?>">Manage Treatment Plans</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Start</th><th>Total Sessions</th><th>Status</th><th>Notes</th></tr></thead>
            <tbody>
            <?php foreach ($plans as $plan): ?>
                <tr>
                    <td><?php echo e($plan['start_date']); ?></td>
                    <td><?php echo e($plan['total_sessions']); ?></td>
                    <td><?php echo e($plan['status']); ?></td>
                    <td><?php echo e($plan['notes']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="section-card" style="margin-top:16px;">
    <h3>Quick Add Session</h3>
    <?php if (count($plans) === 0): ?>
        <p>No treatment plan found. Create a plan first.</p>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="quick_session" value="1">
            <div class="card-grid">
                <label>Plan
                    <select name="treatment_plan_id">
                        <?php foreach ($plans as $plan): ?>
                            <option value="<?php echo $plan['id']; ?>">Plan #<?php echo $plan['id']; ?> (<?php echo $plan['total_sessions']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Date
                    <input type="date" name="session_date" value="<?php echo current_date(); ?>">
                </label>
                <label>Attendance
                    <select name="attendance">
                        <option value="attended">Attended</option>
                        <option value="missed">Missed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </label>
            </div>
            <label>Notes
                <textarea name="notes" rows="3"></textarea>
            </label>
            <div class="form-actions">
                <button class="btn" type="submit">Add Session</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<div class="section-card" style="margin-top:16px;">
    <h3>Sessions</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Attendance</th><th>Notes</th></tr></thead>
            <tbody>
            <?php foreach ($sessions as $s): ?>
                <tr>
                    <td><?php echo e($s['session_date']); ?></td>
                    <td><?php echo e($s['attendance']); ?></td>
                    <td><?php echo e($s['notes']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="section-card" style="margin-top:16px;">
    <div class="page-header">
        <h3>Payments</h3>
        <a class="btn" href="payments.php?patient_id=<?php echo $id; ?>">Manage Payments</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Receipt</th></tr></thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?php echo e($p['payment_date']); ?></td>
                    <td><?php echo format_money($p['amount']); ?></td>
                    <td><?php echo e($p['method']); ?></td>
                    <td><a class="btn secondary" href="../receipt.php?id=<?php echo $p['id']; ?>">Receipt</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="section-card" style="margin-top:16px;">
    <h3>Documents</h3>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="upload_document" value="1">
        <div class="form-actions">
            <input type="file" name="document" required>
            <button class="btn" type="submit">Upload</button>
        </div>
    </form>
    <div class="table-wrap">
        <table>
            <thead><tr><th>File</th><th>Uploaded</th></tr></thead>
            <tbody>
            <?php foreach ($documents as $doc): ?>
                <tr>
                    <td><a href="../<?php echo e($doc['file_path']); ?>" target="_blank"><?php echo e($doc['file_name']); ?></a></td>
                    <td><?php echo e($doc['uploaded_at']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
