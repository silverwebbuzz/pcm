<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['admin_doctor']);

$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM patients WHERE id = ?');
$stmt->execute([$id]);
$patient = $stmt->fetch();
if (!$patient) {
    redirect('admin/patients.php');
}

$user = null;
if ($patient['user_id']) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
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
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'emergency_contact' => trim($_POST['emergency_contact'] ?? ''),
    ];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('
            UPDATE patients SET
                first_name = ?, last_name = ?, age = ?, gender = ?, dob = ?, occupation = ?,
                phone = ?, address = ?, emergency_contact = ?
            WHERE id = ?
        ');
        $stmt->execute([
            $fields['first_name'],
            $fields['last_name'],
            $fields['age'],
            $fields['gender'],
            $fields['dob'],
            $fields['occupation'],
            $fields['phone'],
            $fields['address'],
            $fields['emergency_contact'],
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
$stmt = $pdo->prepare('SELECT * FROM patients WHERE id = ?');
$stmt->execute([$id]);
$patient = $stmt->fetch();
if ($patient['user_id']) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$patient['user_id']]);
    $user = $stmt->fetch();
}

require __DIR__ . '/../layout/header.php';
?>
<h2>Edit Patient</h2>
<?php if ($error): ?><div class="error"><?php echo e($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="success"><?php echo e($success); ?></div><?php endif; ?>
<form method="post">
    <div class="form-layout">
        <div class="form-main">
            <div class="form-card">
                <div class="section-title"><h3>Patient Details</h3></div>
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
            </div>
        </div>
        <div class="form-side">
            <div class="form-card">
                <div class="section-title"><h3>Patient Login</h3></div>
                <?php if ($user): ?>
                    <p class="form-note">Login Email: <?php echo e($user['email']); ?></p>
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
            </div>
        </div>
    </div>
    <button class="btn" type="submit">Update Patient</button>
</form>
<?php require __DIR__ . '/../layout/footer.php'; ?>
