<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['admin_doctor']);

$error = '';
$success = '';
if (!isset($pdo)) {
    $pdo = db();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'age' => $_POST['age'] !== '' ? (int) $_POST['age'] : null,
        'gender' => trim($_POST['gender'] ?? ''),
        'dob' => $_POST['dob'] ?? null,
        'occupation' => trim($_POST['occupation'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'emergency_contact' => trim($_POST['emergency_contact'] ?? ''),
    ];

    if ($data['first_name'] === '' || $data['last_name'] === '') {
        $error = 'First and last name are required.';
    } else {
        $pdo->beginTransaction();
        try {
            $userId = null;
            if (!empty($_POST['create_login'])) {
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
                    $data['first_name'] . ' ' . $data['last_name'],
                    $email,
                    password_hash($password, PASSWORD_DEFAULT),
                    $question,
                    password_hash($answer, PASSWORD_DEFAULT),
                    $active
                ]);
                $userId = (int) $pdo->lastInsertId();
            }

            $stmt = $pdo->prepare('
                INSERT INTO patients (
                    user_id, first_name, last_name, age, gender, dob, occupation,
                    phone, address, emergency_contact, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $userId,
                $data['first_name'],
                $data['last_name'],
                $data['age'],
                $data['gender'],
                $data['dob'],
                $data['occupation'],
                $data['phone'],
                $data['address'],
                $data['emergency_contact'],
                current_user()['id'],
            ]);
            $pdo->commit();
            $patientId = (int) $pdo->lastInsertId();
            redirect('admin/case_add.php?patient_id=' . $patientId);
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

require __DIR__ . '/../layout/header.php';
?>
<h2>Add New Patient</h2>
<?php if ($error): ?><div class="error"><?php echo e($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="success"><?php echo e($success); ?></div><?php endif; ?>
<form method="post">
    <div class="form-layout">
        <div class="form-main">
            <div class="form-card">
                <div class="section-title"><h3>Patient Details</h3></div>
                <div class="grid">
                    <label>First Name
                        <input name="first_name" required>
                    </label>
                    <label>Last Name
                        <input name="last_name" required>
                    </label>
                    <label>Age
                        <input type="number" name="age" min="0">
                    </label>
                    <label>Gender
                        <select name="gender">
                            <option value="">Select</option>
                            <option>Male</option>
                            <option>Female</option>
                            <option>Other</option>
                        </select>
                    </label>
                    <label>Date of Birth
                        <input type="date" name="dob">
                    </label>
                    <label>Occupation
                        <input name="occupation">
                    </label>
                    <label>Phone
                        <input name="phone">
                    </label>
                    <label>Address
                        <input name="address">
                    </label>
                    <label>Emergency Contact
                        <input name="emergency_contact">
                    </label>
                </div>
                <p class="form-note">After saving patient, you will be redirected to open a new case.</p>
            </div>
        </div>
        <div class="form-side">
            <div class="form-card">
                <div class="section-title"><h3>Create Patient Login</h3></div>
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
            </div>
        </div>
    </div>
    <button class="btn" type="submit">Save Patient</button>
</form>
<?php require __DIR__ . '/../layout/footer.php'; ?>
