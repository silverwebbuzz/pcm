<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['admin_doctor']);

$pdo = db();
$patientId = (int) ($_GET['patient_id'] ?? 0);

$patients = $pdo->query('SELECT id, first_name, last_name FROM patients ORDER BY first_name')->fetchAll();
$painRows = $pdo->query("SELECT id, category, subcategory FROM pain_master WHERE active = 1 ORDER BY category, subcategory")->fetchAll();
$painByCategory = [];
foreach ($painRows as $row) {
    $painByCategory[$row['category']][] = $row;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId = (int) ($_POST['patient_id'] ?? 0);
    $visitDateInput = $_POST['visit_date'] ?? date('Y-m-d\TH:i');
    $visitDate = date('Y-m-d H:i:s', strtotime($visitDateInput));
    $duration = trim($_POST['condition_duration'] ?? '');
    $chief = trim($_POST['chief_complain'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $goals = trim($_POST['treatment_goals'] ?? '');
    $historyPresent = trim($_POST['history_present_illness'] ?? '');
    $pastMedical = trim($_POST['past_medical_history'] ?? '');
    $surgicalHistory = trim($_POST['surgical_history'] ?? '');
    $familyHistory = trim($_POST['family_history'] ?? '');
    $socioEconomic = trim($_POST['socio_economic_status'] ?? '');
    $observationBuilt = trim($_POST['observation_built'] ?? '');
    $observationAttitude = trim($_POST['observation_attitude_limb'] ?? '');
    $observationPosture = trim($_POST['observation_posture'] ?? '');
    $observationDeformity = trim($_POST['observation_deformity'] ?? '');
    $aidsApplications = trim($_POST['aids_applications'] ?? '');
    $gait = trim($_POST['gait'] ?? '');
    $palpationTenderness = trim($_POST['palpation_tenderness'] ?? '');
    $palpationOedema = trim($_POST['palpation_oedema'] ?? '');
    $palpationWarmth = trim($_POST['palpation_warmth'] ?? '');
    $palpationCrepitus = trim($_POST['palpation_crepitus'] ?? '');
    $examinationRom = trim($_POST['examination_rom'] ?? '');
    $musclePower = trim($_POST['muscle_power'] ?? '');
    $muscleBulk = trim($_POST['muscle_bulk'] ?? '');
    $ligamentInstability = trim($_POST['ligament_instability'] ?? '');
    $gaitAssessment = trim($_POST['gait_assessment'] ?? '');
    $painType = trim($_POST['pain_type'] ?? '');
    $painSite = trim($_POST['pain_site'] ?? '');
    $painNature = trim($_POST['pain_nature'] ?? '');
    $aggravating = trim($_POST['pain_aggravating_factor'] ?? '');
    $relieving = trim($_POST['pain_relieving_factor'] ?? '');
    $painMeasurement = $_POST['pain_measurement'] !== '' ? (int) $_POST['pain_measurement'] : null;

    if (!$patientId) {
        $error = 'Patient is required.';
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('
                INSERT INTO patient_cases (
                    patient_id, visit_date, condition_duration, chief_complain, history_present_illness, past_medical_history,
                    surgical_history, family_history, socio_economic_status, observation_built, observation_attitude_limb,
                    observation_posture, observation_deformity, aids_applications, gait, palpation_tenderness,
                    palpation_oedema, palpation_warmth, palpation_crepitus, examination_rom, muscle_power, muscle_bulk,
                    ligament_instability, pain_type, pain_site, pain_nature, gait_assessment,
                    pain_aggravating_factor, pain_relieving_factor, pain_measurement, diagnosis, treatment_goals, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $patientId,
                $visitDate,
                $duration,
                $chief,
                $historyPresent,
                $pastMedical,
                $surgicalHistory,
                $familyHistory,
                $socioEconomic,
                $observationBuilt,
                $observationAttitude,
                $observationPosture,
                $observationDeformity,
                $aidsApplications,
                $gait,
                $palpationTenderness,
                $palpationOedema,
                $palpationWarmth,
                $palpationCrepitus,
                $examinationRom,
                $musclePower,
                $muscleBulk,
                $ligamentInstability,
                $painType,
                $painSite,
                $painNature,
                $gaitAssessment,
                $aggravating,
                $relieving,
                $painMeasurement,
                $diagnosis,
                $goals,
                current_user()['id'],
            ]);
            $caseId = (int) $pdo->lastInsertId();

            $selectedPain = $_POST['pain_subcategories'] ?? [];
            if (is_array($selectedPain) && count($selectedPain) > 0) {
                $stmt = $pdo->prepare('INSERT INTO patient_pain (patient_id, case_id, pain_master_id) VALUES (?, ?, ?)');
                foreach ($selectedPain as $painId) {
                    $stmt->execute([$patientId, $caseId, (int) $painId]);
                }
            }
            $pdo->commit();
            redirect('admin/case_view.php?case_id=' . $caseId);
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

require __DIR__ . '/../layout/header.php';
$visitDefault = date('Y-m-d\TH:i');
?>
<div class="page-header">
    <div>
        <h2>Open New Case</h2>
        <div class="page-subtitle">Capture episode-specific details for the patient.</div>
    </div>
</div>
<?php if ($error): ?><div class="error"><?php echo e($error); ?></div><?php endif; ?>
<form method="post">
    <div class="section-card">
        <div class="section-title"><h3>Patient &amp; Visit</h3></div>
        <div class="grid">
            <label>Patient
                <input type="text" class="select-search" placeholder="Search patient...">
                <select name="patient_id" required>
                    <option value="">Select</option>
                    <?php foreach ($patients as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php if ($patientId === (int) $p['id']) echo 'selected'; ?>>
                            <?php echo e($p['first_name'] . ' ' . $p['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Visit Date &amp; Time
                <input type="datetime-local" name="visit_date" value="<?php echo e($visitDefault); ?>">
            </label>
        </div>
    </div>

    <div class="section-card">
        <div class="section-title"><h3>Complaint</h3></div>
        <div class="grid">
            <label>Whole Pain Areas
                <div class="multi-select">
                    <button class="multi-trigger" type="button">Select pain areas</button>
                    <div class="multi-panel">
                        <input type="text" class="multi-search" placeholder="Search pain areas...">
                        <div class="multi-options">
                            <?php foreach ($painByCategory as $category => $items): ?>
                                <div class="multi-group" data-category="<?php echo e($category); ?>">
                                    <div class="multi-group-title">
                                        <label>
                                            <input type="checkbox" class="multi-category" value="<?php echo e($category); ?>">
                                            <?php echo e($category); ?>
                                        </label>
                                    </div>
                                    <div class="multi-group-items">
                                        <?php foreach ($items as $item): ?>
                                            <label>
                                                <input type="checkbox" name="pain_subcategories[]" value="<?php echo (int) $item['id']; ?>" data-label="<?php echo e($category . ' - ' . $item['subcategory']); ?>">
                                                <?php echo e($item['subcategory']); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </label>
            <label>Duration of Condition
                <input name="condition_duration">
            </label>
        </div>
        <div class="multi-selected chip-group"></div>
        <label>Notes
            <textarea name="chief_complain" rows="2"></textarea>
        </label>
    </div>

    <div class="section-card">
        <div class="section-title"><h3>Observation</h3></div>
        <div class="grid">
            <label>Built of Patient
                <textarea name="observation_built" rows="2"></textarea>
            </label>
            <label>Attitude of Limb
                <textarea name="observation_attitude_limb" rows="2"></textarea>
            </label>
            <label>Posture
                <textarea name="observation_posture" rows="2"></textarea>
            </label>
            <label>Deformity
                <textarea name="observation_deformity" rows="2"></textarea>
            </label>
            <label>Aids &amp; Applications
                <textarea name="aids_applications" rows="2"></textarea>
            </label>
            <label>Gait
                <textarea name="gait" rows="2"></textarea>
            </label>
        </div>
    </div>

    <div class="section-card">
        <div class="section-title"><h3>On Palpation</h3></div>
        <div class="grid">
            <label>Tenderness
                <textarea name="palpation_tenderness" rows="2"></textarea>
            </label>
            <label>Oedema
                <select name="palpation_oedema">
                    <option value="">Select</option>
                    <option value="pitting">Pitting</option>
                    <option value="non_pitting">Non Pitting</option>
                </select>
            </label>
            <label>Warmth
                <textarea name="palpation_warmth" rows="2"></textarea>
            </label>
            <label>Crepitus
                <textarea name="palpation_crepitus" rows="2"></textarea>
            </label>
        </div>
    </div>

    <div class="section-card">
        <div class="section-title"><h3>History</h3></div>
        <div class="grid">
            <label>History of Present Illness
                <textarea name="history_present_illness" rows="3"></textarea>
            </label>
            <label>Past Medical History
                <textarea name="past_medical_history" rows="3"></textarea>
            </label>
            <label>Surgical History
                <textarea name="surgical_history" rows="3"></textarea>
            </label>
            <label>Family History
                <textarea name="family_history" rows="3"></textarea>
            </label>
            <label>Socio Economic Status
                <textarea name="socio_economic_status" rows="2"></textarea>
            </label>
        </div>
    </div>

    <div class="section-card">
        <div class="section-title"><h3>Pain Details</h3></div>
        <div class="grid">
            <label>Pain Type
                <input name="pain_type">
            </label>
            <label>Pain Site
                <input name="pain_site">
            </label>
            <label>Pain Nature
                <input name="pain_nature">
            </label>
            <label>Pain Scale (0-10)
                <input type="number" name="pain_measurement" min="0" max="10">
            </label>
        </div>
        <label>Aggravating Factors
            <textarea name="pain_aggravating_factor" rows="2"></textarea>
        </label>
        <label>Relieving Factors
            <textarea name="pain_relieving_factor" rows="2"></textarea>
        </label>
        <label>Gait Assessment
            <textarea name="gait_assessment" rows="2"></textarea>
        </label>
    </div>

    <div class="section-card">
        <div class="section-title"><h3>Examination</h3></div>
        <div class="grid">
            <label>ROM
                <textarea name="examination_rom" rows="3"></textarea>
            </label>
            <label>Muscle Power
                <textarea name="muscle_power" rows="2"></textarea>
            </label>
            <label>Muscle Bulk
                <textarea name="muscle_bulk" rows="2"></textarea>
            </label>
            <label>Ligament Instability
                <textarea name="ligament_instability" rows="2"></textarea>
            </label>
        </div>
    </div>

    <div class="section-card">
        <div class="section-title"><h3>Diagnosis &amp; Goals</h3></div>
        <label>Diagnosis
            <textarea name="diagnosis" rows="2"></textarea>
        </label>
        <label>Treatment Goals
            <textarea name="treatment_goals" rows="2"></textarea>
        </label>
    </div>
    <button class="btn" type="submit">Open Case</button>
</form>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var patientSearch = document.querySelector('.select-search');
    var patientSelect = document.querySelector('select[name="patient_id"]');
    if (patientSearch && patientSelect) {
        var options = Array.from(patientSelect.options);
        patientSearch.addEventListener('input', function () {
            var term = patientSearch.value.toLowerCase();
            patientSelect.innerHTML = '';
            options.forEach(function (opt) {
                if (!term || opt.text.toLowerCase().indexOf(term) !== -1 || opt.value === '') {
                    patientSelect.appendChild(opt);
                }
            });
        });
    }

    var multi = document.querySelector('.multi-select');
    if (!multi) return;
    var trigger = multi.querySelector('.multi-trigger');
    var panel = multi.querySelector('.multi-panel');
    var search = multi.querySelector('.multi-search');
    var selectedWrap = document.querySelector('.multi-selected');

    trigger.addEventListener('click', function () {
        panel.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
        if (!multi.contains(e.target)) {
            panel.classList.remove('open');
        }
    });

    function renderSelected() {
        var checked = multi.querySelectorAll('input[name="pain_subcategories[]"]:checked');
        selectedWrap.innerHTML = '';
        checked.forEach(function (input) {
            var chip = document.createElement('span');
            chip.className = 'chip';
            chip.textContent = input.dataset.label || input.value;
            selectedWrap.appendChild(chip);
        });
        trigger.textContent = checked.length ? checked.length + ' selected' : 'Select pain areas';
    }

    multi.querySelectorAll('input[name="pain_subcategories[]"]').forEach(function (input) {
        input.addEventListener('change', renderSelected);
    });

    multi.querySelectorAll('.multi-category').forEach(function (input) {
        input.addEventListener('change', function () {
            var group = input.closest('.multi-group');
            if (!group) return;
            group.querySelectorAll('input[name="pain_subcategories[]"]').forEach(function (child) {
                child.checked = input.checked;
            });
            renderSelected();
        });
    });

    if (search) {
        search.addEventListener('input', function () {
            var term = search.value.toLowerCase();
            multi.querySelectorAll('.multi-group').forEach(function (group) {
                var matchesGroup = group.dataset.category.toLowerCase().indexOf(term) !== -1;
                var anyChild = false;
                group.querySelectorAll('label').forEach(function (label) {
                    var text = label.textContent.toLowerCase();
                    var visible = term === '' || text.indexOf(term) !== -1;
                    label.style.display = visible ? 'flex' : 'none';
                    if (visible && label.querySelector('input[name="pain_subcategories[]"]')) {
                        anyChild = true;
                    }
                });
                group.style.display = matchesGroup || anyChild ? 'block' : 'none';
            });
        });
    }
    renderSelected();
});
</script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
