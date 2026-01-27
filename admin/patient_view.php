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
    redirect('patients.php');
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

require __DIR__ . '/../layout/header.php';
?>
<h2>Patient Profile</h2>
<p><strong><?php echo e($patient['first_name'] . ' ' . $patient['last_name']); ?></strong></p>
<p>Phone: <?php echo e($patient['phone']); ?> | DOB: <?php echo e($patient['dob']); ?> | Age: <?php echo e($patient['age']); ?></p>
<p>Gender: <?php echo e($patient['gender']); ?> | Occupation: <?php echo e($patient['occupation']); ?></p>
<p>Assessment Date: <?php echo e($patient['assessment_date']); ?> | Dominance: <?php echo e($patient['dominance']); ?></p>
<p>Duration of Condition: <?php echo e($patient['condition_duration']); ?></p>
<p>Address: <?php echo e($patient['address']); ?> | Emergency: <?php echo e($patient['emergency_contact']); ?></p>
<p><strong>Chief Complain:</strong> <?php echo nl2br(e($patient['chief_complain'])); ?></p>
<p><strong>Diagnosis:</strong> <?php echo nl2br(e($patient['diagnosis'])); ?></p>
<p><strong>Treatment Goals:</strong> <?php echo nl2br(e($patient['treatment_goals'])); ?></p>

<h3>History</h3>
<p><strong>History of Present Illness:</strong> <?php echo nl2br(e($patient['history_present_illness'])); ?></p>
<p><strong>Past Medical History:</strong> <?php echo nl2br(e($patient['past_medical_history'])); ?></p>
<p><strong>Surgical History:</strong> <?php echo nl2br(e($patient['surgical_history'])); ?></p>
<p><strong>Family History:</strong> <?php echo nl2br(e($patient['family_history'])); ?></p>
<p><strong>Socio Economic Status:</strong> <?php echo nl2br(e($patient['socio_economic_status'])); ?></p>

<h3>Observation</h3>
<p><strong>Built of Patient:</strong> <?php echo nl2br(e($patient['observation_built'])); ?></p>
<p><strong>Attitude of Limb:</strong> <?php echo nl2br(e($patient['observation_attitude_limb'])); ?></p>
<p><strong>Posture:</strong> <?php echo nl2br(e($patient['observation_posture'])); ?></p>
<p><strong>Deformity:</strong> <?php echo nl2br(e($patient['observation_deformity'])); ?></p>
<p><strong>Aids &amp; Applications:</strong> <?php echo nl2br(e($patient['aids_applications'])); ?></p>
<p><strong>Gait:</strong> <?php echo nl2br(e($patient['gait'])); ?></p>

<h3>On Palpation</h3>
<p><strong>Tenderness:</strong> <?php echo nl2br(e($patient['palpation_tenderness'])); ?></p>
<p><strong>Oedema:</strong> <?php echo e($patient['palpation_oedema']); ?></p>
<p><strong>Warmth:</strong> <?php echo nl2br(e($patient['palpation_warmth'])); ?></p>
<p><strong>Crepitus:</strong> <?php echo nl2br(e($patient['palpation_crepitus'])); ?></p>

<h3>Examination</h3>
<p><strong>ROM:</strong> <?php echo nl2br(e($patient['examination_rom'])); ?></p>
<p><strong>Muscle Power:</strong> <?php echo nl2br(e($patient['muscle_power'])); ?></p>
<p><strong>Muscle Bulk:</strong> <?php echo nl2br(e($patient['muscle_bulk'])); ?></p>
<p><strong>Ligament Instability:</strong> <?php echo nl2br(e($patient['ligament_instability'])); ?></p>

<h3>Pain Assessment</h3>
<p><strong>Type of Pain:</strong> <?php echo nl2br(e($patient['pain_type'])); ?></p>
<p><strong>Sight/Site of Pain:</strong> <?php echo nl2br(e($patient['pain_site'])); ?></p>
<p><strong>Nature of Pain:</strong> <?php echo nl2br(e($patient['pain_nature'])); ?></p>
<p><strong>Aggravating Factor:</strong> <?php echo nl2br(e($patient['pain_aggravating_factor'])); ?></p>
<p><strong>Relieving Factor:</strong> <?php echo nl2br(e($patient['pain_relieving_factor'])); ?></p>
<p><strong>Measurement of Pain:</strong> <?php echo e($patient['pain_measurement']); ?></p>
<p><strong>Gait Assessment:</strong> <?php echo nl2br(e($patient['gait_assessment'])); ?></p>

<h3>Treatment Plans</h3>
<p><a class="btn" href="treatment_plans.php?patient_id=<?php echo $id; ?>">Manage Treatment Plans</a></p>
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

<h3>Quick Add Session</h3>
<?php if (count($plans) === 0): ?>
    <p>No treatment plan found. Create a plan first.</p>
<?php else: ?>
    <form method="post">
        <input type="hidden" name="quick_session" value="1">
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
        <label>Notes
            <textarea name="notes" rows="3"></textarea>
        </label>
        <button class="btn" type="submit">Add Session</button>
    </form>
<?php endif; ?>

<h3>Sessions</h3>
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

<h3>Payments</h3>
<p><a class="btn" href="payments.php?patient_id=<?php echo $id; ?>">Manage Payments</a></p>
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

<h3>Documents</h3>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="upload_document" value="1">
    <input type="file" name="document" required>
    <button class="btn" type="submit">Upload</button>
</form>
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

<?php require __DIR__ . '/../layout/footer.php'; ?>
