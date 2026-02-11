<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['admin_doctor']);

$pdo = db();
$caseId = (int) ($_GET['case_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_case'])) {
    $pdo->prepare('UPDATE patient_cases SET status = "closed", closed_at = NOW(), closed_notes = ? WHERE id = ?')
        ->execute([trim($_POST['closed_notes'] ?? ''), $caseId]);
    redirect('admin/case_view.php?case_id=' . $caseId);
}

$stmt = $pdo->prepare('
    SELECT pc.*, p.first_name, p.last_name, p.phone, p.gender, p.age
    FROM patient_cases pc
    JOIN patients p ON p.id = pc.patient_id
    WHERE pc.id = ?
');
$stmt->execute([$caseId]);
$case = $stmt->fetch();
if (!$case) {
    redirect('admin/cases.php');
}

$painStmt = $pdo->prepare('
    SELECT pm.category, pm.subcategory
    FROM patient_pain pp
    JOIN pain_master pm ON pm.id = pp.pain_master_id
    WHERE pp.case_id = ?
');
$painStmt->execute([$caseId]);
$painAreas = $painStmt->fetchAll();

$plansStmt = $pdo->prepare('SELECT * FROM treatment_plans WHERE case_id = ? ORDER BY created_at DESC');
$plansStmt->execute([$caseId]);
$plans = $plansStmt->fetchAll();

$sessionsStmt = $pdo->prepare('SELECT * FROM sessions WHERE case_id = ? ORDER BY session_date DESC');
$sessionsStmt->execute([$caseId]);
$sessions = $sessionsStmt->fetchAll();

$paymentsStmt = $pdo->prepare('SELECT * FROM payments WHERE case_id = ? ORDER BY payment_date DESC');
$paymentsStmt->execute([$caseId]);
$payments = $paymentsStmt->fetchAll();

require __DIR__ . '/../layout/header.php';
?>
<div class="page-header">
    <div>
        <h2>Case Workspace</h2>
        <div class="page-subtitle"><?php echo e($case['first_name'] . ' ' . $case['last_name']); ?> • Case #<?php echo $caseId; ?></div>
    </div>
    <div class="form-actions">
        <a class="btn ghost" href="cases.php">Back to Cases</a>
        <a class="btn secondary" href="patient_view.php?id=<?php echo $case['patient_id']; ?>">Patient Profile</a>
    </div>
</div>

<div class="section-card section-title">
    <h3>Patient Summary</h3>
</div>
<div class="card-grid two-col">
    <div class="section-card soft">
        <div class="info-grid">
            <div class="info-item"><div class="info-label">Name</div><div class="info-value"><?php echo e($case['first_name'] . ' ' . $case['last_name']); ?></div></div>
            <div class="info-item"><div class="info-label">Age</div><div class="info-value"><?php echo e($case['age']); ?></div></div>
            <div class="info-item"><div class="info-label">Gender</div><div class="info-value"><?php echo e($case['gender']); ?></div></div>
            <div class="info-item"><div class="info-label">Phone</div><div class="info-value"><?php echo e($case['phone']); ?></div></div>
        </div>
    </div>
    <div class="section-card soft">
        <div class="info-grid">
            <div class="info-item"><div class="info-label">Visit Date</div><div class="info-value"><?php echo e($case['visit_date']); ?></div></div>
            <div class="info-item"><div class="info-label">Status</div><div class="info-value"><?php echo e($case['status']); ?></div></div>
            <div class="info-item"><div class="info-label">Duration</div><div class="info-value"><?php echo e($case['condition_duration']); ?></div></div>
        </div>
    </div>
</div>

<div class="callout">
    <div class="callout-title">Chief Complaint</div>
    <div class="callout-body"><?php echo nl2br(e($case['chief_complain'])); ?></div>
</div>

<div class="section-card section-title">
    <h3>Pain Areas</h3>
</div>
<div class="chip-group">
    <?php foreach ($painAreas as $pain): ?>
        <span class="chip"><?php echo e($pain['category'] . ' - ' . $pain['subcategory']); ?></span>
    <?php endforeach; ?>
    <?php if (!$painAreas): ?>
        <span class="chip muted">No pain areas selected</span>
    <?php endif; ?>
</div>

<div class="card-grid two-col">
    <div class="section-card soft">
        <div class="info-label">Diagnosis</div>
        <div><?php echo nl2br(e($case['diagnosis'])); ?></div>
    </div>
    <div class="section-card soft">
        <div class="info-label">Treatment Goals</div>
        <div><?php echo nl2br(e($case['treatment_goals'])); ?></div>
    </div>
</div>

<div class="section-card section-title">
    <h3>History &amp; Observation</h3>
</div>
<div class="card-grid two-col">
    <div class="section-card soft">
        <div class="info-label">History of Present Illness</div>
        <div><?php echo nl2br(e($case['history_present_illness'])); ?></div>
    </div>
    <div class="section-card soft">
        <div class="info-label">Past Medical History</div>
        <div><?php echo nl2br(e($case['past_medical_history'])); ?></div>
    </div>
    <div class="section-card soft">
        <div class="info-label">Surgical History</div>
        <div><?php echo nl2br(e($case['surgical_history'])); ?></div>
    </div>
    <div class="section-card soft">
        <div class="info-label">Family History</div>
        <div><?php echo nl2br(e($case['family_history'])); ?></div>
    </div>
    <div class="section-card soft">
        <div class="info-label">Socio Economic Status</div>
        <div><?php echo nl2br(e($case['socio_economic_status'])); ?></div>
    </div>
    <div class="section-card soft">
        <div class="info-label">Observation</div>
        <div><?php echo nl2br(e(trim($case['observation_built'] . "\n" . $case['observation_attitude_limb'] . "\n" . $case['observation_posture'] . "\n" . $case['observation_deformity']))); ?></div>
    </div>
</div>

<div class="section-card section-title">
    <h3>Examination</h3>
</div>
<div class="card-grid two-col">
    <div class="section-card soft">
        <div class="info-label">Palpation</div>
        <div><?php echo nl2br(e(trim($case['palpation_tenderness'] . "\n" . $case['palpation_oedema'] . "\n" . $case['palpation_warmth'] . "\n" . $case['palpation_crepitus']))); ?></div>
    </div>
    <div class="section-card soft">
        <div class="info-label">ROM &amp; Muscle</div>
        <div><?php echo nl2br(e(trim($case['examination_rom'] . "\n" . $case['muscle_power'] . "\n" . $case['muscle_bulk'] . "\n" . $case['ligament_instability']))); ?></div>
    </div>
    <div class="section-card soft">
        <div class="info-label">Gait Assessment</div>
        <div><?php echo nl2br(e($case['gait_assessment'])); ?></div>
    </div>
</div>

<div class="section-card section-title">
    <h3>Treatment Plans</h3>
</div>
<div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Cycle</th>
            <th>Sessions</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$plans): ?>
            <tr><td colspan="3">No treatment plans found for this case.</td></tr>
        <?php else: ?>
            <?php foreach ($plans as $plan): ?>
                <tr>
                    <td><?php echo e($plan['plan_name'] ?: ('Plan #' . $plan['id'])); ?></td>
                    <td><?php echo e($plan['total_sessions']); ?></td>
                    <td><?php echo e($plan['status']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    <a class="btn" href="treatment_plans.php?patient_id=<?php echo $case['patient_id']; ?>&case_id=<?php echo $caseId; ?>">Manage Plans</a>
</div>

<div class="section-card section-title">
    <h3>Sessions</h3>
</div>
<div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Date</th>
            <th>Notes</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$sessions): ?>
            <tr><td colspan="3">No sessions recorded for this case.</td></tr>
        <?php else: ?>
            <?php foreach ($sessions as $session): ?>
                <tr>
                    <td><?php echo e($session['session_date']); ?></td>
                    <td><?php echo e($session['notes']); ?></td>
                    <td><?php echo e($session['attendance']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    <a class="btn" href="sessions.php?patient_id=<?php echo $case['patient_id']; ?>&case_id=<?php echo $caseId; ?>">Add Session</a>
</div>

<div class="section-card section-title">
    <h3>Payments</h3>
</div>
<div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Date</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Notes</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$payments): ?>
            <tr><td colspan="4">No payments recorded for this case.</td></tr>
        <?php else: ?>
            <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?php echo e($payment['payment_date']); ?></td>
                    <td><?php echo e($payment['amount']); ?></td>
                    <td><?php echo e($payment['method']); ?></td>
                    <td><?php echo e($payment['notes']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    <a class="btn" href="payments.php?patient_id=<?php echo $case['patient_id']; ?>&case_id=<?php echo $caseId; ?>">Add Payment</a>
</div>

<?php if ($case['status'] === 'open'): ?>
    <div class="section-card">
        <h3>Close Case</h3>
        <form method="post">
            <input type="hidden" name="close_case" value="1">
            <label>Closing Notes
                <textarea name="closed_notes" rows="3"></textarea>
            </label>
            <button class="btn ghost" type="submit">Close Case</button>
        </form>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
