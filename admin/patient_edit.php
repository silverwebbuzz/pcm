<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['admin_doctor']);

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM patients WHERE id = ?');
$stmt->execute([$id]);
$patient = $stmt->fetch();
if (!$patient) {
    redirect('admin/patients.php');
}

$user = null;
if ($patient['user_id']) {
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$patient['user_id']]);
    $user = $stmt->fetch();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'age' => $_POST['age'] !== '' ? (int) $_POST['age'] : null,
        'gender' => trim($_POST['gender'] ?? ''),
        'dob' => $_POST['dob'] ?? null,
        'occupation' => trim($_POST['occupation'] ?? ''),
        'assessment_date' => $_POST['assessment_date'] ?? null,
        'dominance' => trim($_POST['dominance'] ?? ''),
        'condition_duration' => trim($_POST['condition_duration'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'emergency_contact' => trim($_POST['emergency_contact'] ?? ''),
        'chief_complain' => trim($_POST['chief_complain'] ?? ''),
        'history_present_illness' => trim($_POST['history_present_illness'] ?? ''),
        'past_medical_history' => trim($_POST['past_medical_history'] ?? ''),
        'surgical_history' => trim($_POST['surgical_history'] ?? ''),
        'family_history' => trim($_POST['family_history'] ?? ''),
        'socio_economic_status' => trim($_POST['socio_economic_status'] ?? ''),
        'observation_built' => trim($_POST['observation_built'] ?? ''),
        'observation_attitude_limb' => trim($_POST['observation_attitude_limb'] ?? ''),
        'observation_posture' => trim($_POST['observation_posture'] ?? ''),
        'observation_deformity' => trim($_POST['observation_deformity'] ?? ''),
        'aids_applications' => trim($_POST['aids_applications'] ?? ''),
        'gait' => trim($_POST['gait'] ?? ''),
        'palpation_tenderness' => trim($_POST['palpation_tenderness'] ?? ''),
        'palpation_oedema' => trim($_POST['palpation_oedema'] ?? ''),
        'palpation_warmth' => trim($_POST['palpation_warmth'] ?? ''),
        'palpation_crepitus' => trim($_POST['palpation_crepitus'] ?? ''),
        'examination_rom' => trim($_POST['examination_rom'] ?? ''),
        'muscle_power' => trim($_POST['muscle_power'] ?? ''),
        'muscle_bulk' => trim($_POST['muscle_bulk'] ?? ''),
        'ligament_instability' => trim($_POST['ligament_instability'] ?? ''),
        'pain_type' => trim($_POST['pain_type'] ?? ''),
        'pain_site' => trim($_POST['pain_site'] ?? ''),
        'pain_nature' => trim($_POST['pain_nature'] ?? ''),
        'pain_aggravating_factor' => trim($_POST['pain_aggravating_factor'] ?? ''),
        'pain_relieving_factor' => trim($_POST['pain_relieving_factor'] ?? ''),
        'pain_measurement' => $_POST['pain_measurement'] !== '' ? (int) $_POST['pain_measurement'] : null,
        'gait_assessment' => trim($_POST['gait_assessment'] ?? ''),
        'diagnosis' => trim($_POST['diagnosis'] ?? ''),
        'treatment_goals' => trim($_POST['treatment_goals'] ?? ''),
    ];

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('
            UPDATE patients SET
                first_name = ?, last_name = ?, age = ?, gender = ?, dob = ?, occupation = ?, assessment_date = ?,
                dominance = ?, condition_duration = ?, phone = ?, address = ?, emergency_contact = ?,
                chief_complain = ?, history_present_illness = ?, past_medical_history = ?, surgical_history = ?,
                family_history = ?, socio_economic_status = ?, observation_built = ?, observation_attitude_limb = ?,
                observation_posture = ?, observation_deformity = ?, aids_applications = ?, gait = ?, palpation_tenderness = ?,
                palpation_oedema = ?, palpation_warmth = ?, palpation_crepitus = ?, examination_rom = ?, muscle_power = ?,
                muscle_bulk = ?, ligament_instability = ?, pain_type = ?, pain_site = ?, pain_nature = ?,
                pain_aggravating_factor = ?, pain_relieving_factor = ?, pain_measurement = ?, gait_assessment = ?,
                diagnosis = ?, treatment_goals = ?
            WHERE id = ?
        ');
        $stmt->execute([
            $fields['first_name'],
            $fields['last_name'],
            $fields['age'],
            $fields['gender'],
            $fields['dob'],
            $fields['occupation'],
            $fields['assessment_date'],
            $fields['dominance'],
            $fields['condition_duration'],
            $fields['phone'],
            $fields['address'],
            $fields['emergency_contact'],
            $fields['chief_complain'],
            $fields['history_present_illness'],
            $fields['past_medical_history'],
            $fields['surgical_history'],
            $fields['family_history'],
            $fields['socio_economic_status'],
            $fields['observation_built'],
            $fields['observation_attitude_limb'],
            $fields['observation_posture'],
            $fields['observation_deformity'],
            $fields['aids_applications'],
            $fields['gait'],
            $fields['palpation_tenderness'],
            $fields['palpation_oedema'],
            $fields['palpation_warmth'],
            $fields['palpation_crepitus'],
            $fields['examination_rom'],
            $fields['muscle_power'],
            $fields['muscle_bulk'],
            $fields['ligament_instability'],
            $fields['pain_type'],
            $fields['pain_site'],
            $fields['pain_nature'],
            $fields['pain_aggravating_factor'],
            $fields['pain_relieving_factor'],
            $fields['pain_measurement'],
            $fields['gait_assessment'],
            $fields['diagnosis'],
            $fields['treatment_goals'],
            $id,
        ]);

        if (!empty($_POST['create_login']) && !$user) {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $question = trim($_POST['security_question'] ?? '');
            $answer = trim($_POST['security_answer'] ?? '');
            $active = !empty($_POST['active']) ? 1 : 0;

            if ($email === '' || $password === '' || $question === '' || $answer === '') {
                throw new Exception('All login fields are required.');
            }
            $stmt = $pdo->prepare('INSERT INTO users (role, name, email, password_hash, security_question, security_answer_hash, active) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                'patient',
                $fields['first_name'] . ' ' . $fields['last_name'],
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $question,
                password_hash($answer, PASSWORD_DEFAULT),
                $active
            ]);
            $userId = (int) $pdo->lastInsertId();
            $pdo->prepare('UPDATE patients SET user_id = ? WHERE id = ?')->execute([$userId, $id]);
        } elseif ($user) {
            $active = !empty($_POST['active']) ? 1 : 0;
            $pdo->prepare('UPDATE users SET active = ? WHERE id = ?')->execute([$active, $user['id']]);
        }

        $pdo->commit();
        $success = 'Patient updated.';
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Refresh patient data after update
$stmt = db()->prepare('SELECT * FROM patients WHERE id = ?');
$stmt->execute([$id]);
$patient = $stmt->fetch();
if ($patient['user_id']) {
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$patient['user_id']]);
    $user = $stmt->fetch();
}

require __DIR__ . '/../layout/header.php';
?>
<h2>Edit Patient</h2>
<?php if ($error): ?><div class="error"><?php echo e($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="success"><?php echo e($success); ?></div><?php endif; ?>
<form method="post">
    <h3>Assessment</h3>
    <div class="grid">
        <label>First Name
            <input name="first_name" value="<?php echo e($patient['first_name']); ?>" required>
        </label>
        <label>Last Name
            <input name="last_name" value="<?php echo e($patient['last_name']); ?>" required>
        </label>
        <label>Age
            <input type="number" name="age" min="0" value="<?php echo e($patient['age']); ?>">
        </label>
        <label>Gender
            <select name="gender">
                <option value="">Select</option>
                <?php foreach (['Male','Female','Other'] as $g): ?>
                    <option value="<?php echo $g; ?>" <?php if ($patient['gender'] === $g) echo 'selected'; ?>><?php echo $g; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Date of Birth
            <input type="date" name="dob" value="<?php echo e($patient['dob']); ?>">
        </label>
        <label>Occupation
            <input name="occupation" value="<?php echo e($patient['occupation']); ?>">
        </label>
        <label>Date of Assessment
            <input type="date" name="assessment_date" value="<?php echo e($patient['assessment_date']); ?>">
        </label>
        <label>Dominance
            <input name="dominance" value="<?php echo e($patient['dominance']); ?>">
        </label>
        <label>Duration of Condition
            <input name="condition_duration" value="<?php echo e($patient['condition_duration']); ?>">
        </label>
        <label>Phone
            <input name="phone" value="<?php echo e($patient['phone']); ?>">
        </label>
        <label>Address
            <input name="address" value="<?php echo e($patient['address']); ?>">
        </label>
        <label>Emergency Contact
            <input name="emergency_contact" value="<?php echo e($patient['emergency_contact']); ?>">
        </label>
    </div>
    <label>Chief Complain
        <textarea name="chief_complain" rows="2"><?php echo e($patient['chief_complain']); ?></textarea>
    </label>

    <h3>History</h3>
    <label>History of Present Illness
        <textarea name="history_present_illness" rows="3"><?php echo e($patient['history_present_illness']); ?></textarea>
    </label>
    <label>Past Medical History
        <textarea name="past_medical_history" rows="3"><?php echo e($patient['past_medical_history']); ?></textarea>
    </label>
    <label>Surgical History
        <textarea name="surgical_history" rows="3"><?php echo e($patient['surgical_history']); ?></textarea>
    </label>
    <label>Family History
        <textarea name="family_history" rows="3"><?php echo e($patient['family_history']); ?></textarea>
    </label>
    <label>Socio Economic Status
        <textarea name="socio_economic_status" rows="2"><?php echo e($patient['socio_economic_status']); ?></textarea>
    </label>

    <h3>Observation</h3>
    <label>Built of Patient
        <textarea name="observation_built" rows="2"><?php echo e($patient['observation_built']); ?></textarea>
    </label>
    <label>Attitude of Limb
        <textarea name="observation_attitude_limb" rows="2"><?php echo e($patient['observation_attitude_limb']); ?></textarea>
    </label>
    <label>Posture
        <textarea name="observation_posture" rows="2"><?php echo e($patient['observation_posture']); ?></textarea>
    </label>
    <label>Deformity
        <textarea name="observation_deformity" rows="2"><?php echo e($patient['observation_deformity']); ?></textarea>
    </label>
    <label>Aids &amp; Applications
        <textarea name="aids_applications" rows="2"><?php echo e($patient['aids_applications']); ?></textarea>
    </label>
    <label>Gait
        <textarea name="gait" rows="2"><?php echo e($patient['gait']); ?></textarea>
    </label>

    <h3>On Palpation</h3>
    <label>Tenderness
        <textarea name="palpation_tenderness" rows="2"><?php echo e($patient['palpation_tenderness']); ?></textarea>
    </label>
    <label>Oedema
        <select name="palpation_oedema">
            <option value="">Select</option>
            <option value="pitting" <?php if ($patient['palpation_oedema'] === 'pitting') echo 'selected'; ?>>Pitting</option>
            <option value="non_pitting" <?php if ($patient['palpation_oedema'] === 'non_pitting') echo 'selected'; ?>>Non Pitting</option>
        </select>
    </label>
    <label>Warmth
        <textarea name="palpation_warmth" rows="2"><?php echo e($patient['palpation_warmth']); ?></textarea>
    </label>
    <label>Crepitus
        <textarea name="palpation_crepitus" rows="2"><?php echo e($patient['palpation_crepitus']); ?></textarea>
    </label>

    <h3>Examination</h3>
    <label>ROM
        <textarea name="examination_rom" rows="3"><?php echo e($patient['examination_rom']); ?></textarea>
    </label>
    <label>Muscle Power
        <textarea name="muscle_power" rows="2"><?php echo e($patient['muscle_power']); ?></textarea>
    </label>
    <label>Muscle Bulk
        <textarea name="muscle_bulk" rows="2"><?php echo e($patient['muscle_bulk']); ?></textarea>
    </label>
    <label>Ligament Instability
        <textarea name="ligament_instability" rows="2"><?php echo e($patient['ligament_instability']); ?></textarea>
    </label>

    <h3>Pain Assessment</h3>
    <label>Type of Pain
        <textarea name="pain_type" rows="2"><?php echo e($patient['pain_type']); ?></textarea>
    </label>
    <label>Sight/Site of Pain
        <textarea name="pain_site" rows="2"><?php echo e($patient['pain_site']); ?></textarea>
    </label>
    <label>Nature of Pain
        <textarea name="pain_nature" rows="2"><?php echo e($patient['pain_nature']); ?></textarea>
    </label>
    <label>Aggravating Factor
        <textarea name="pain_aggravating_factor" rows="2"><?php echo e($patient['pain_aggravating_factor']); ?></textarea>
    </label>
    <label>Relieving Factor
        <textarea name="pain_relieving_factor" rows="2"><?php echo e($patient['pain_relieving_factor']); ?></textarea>
    </label>
    <label>Measurement of Pain (0-10)
        <input type="number" name="pain_measurement" min="0" max="10" value="<?php echo e($patient['pain_measurement']); ?>">
    </label>
    <label>Gait Assessment
        <textarea name="gait_assessment" rows="2"><?php echo e($patient['gait_assessment']); ?></textarea>
    </label>

    <h3>Diagnosis &amp; Goals</h3>
    <label>Diagnosis
        <textarea name="diagnosis" rows="3"><?php echo e($patient['diagnosis']); ?></textarea>
    </label>
    <label>Treatment Goals
        <textarea name="treatment_goals" rows="3"><?php echo e($patient['treatment_goals']); ?></textarea>
    </label>

    <h3>Patient Login</h3>
    <?php if ($user): ?>
        <p>Login Email: <?php echo e($user['email']); ?></p>
        <label><input type="checkbox" name="active" value="1" <?php if ((int) $user['active'] === 1) echo 'checked'; ?>> Active Account</label>
    <?php else: ?>
        <label><input type="checkbox" name="create_login" value="1"> Create login for patient</label>
        <div class="grid">
            <label>Email
                <input type="email" name="email">
            </label>
            <label>Temporary Password
                <input type="password" name="password">
            </label>
            <label>Security Question
                <input name="security_question">
            </label>
            <label>Security Answer
                <input name="security_answer">
            </label>
            <label><input type="checkbox" name="active" value="1"> Active Account</label>
        </div>
    <?php endif; ?>

    <button class="btn" type="submit">Update Patient</button>
</form>
<?php require __DIR__ . '/../layout/footer.php'; ?>
