<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if (!is_patient_active($user)) {
            $error = 'Your account is not active. Please contact the clinic.';
        } else {
            login_user($user);
            redirect('dashboard.php');
        }
    } else {
        $error = 'Invalid email or password.';
    }
}
require __DIR__ . '/layout/header.php';
?>
<h2>Login</h2>
<?php if ($error): ?><div class="error"><?php echo e($error); ?></div><?php endif; ?>
<form method="post">
    <label>Email
        <input type="email" name="email" required>
    </label>
    <label>Password
        <input type="password" name="password" required>
    </label>
    <button class="btn" type="submit">Login</button>
    <a class="btn secondary" href="forgot_password.php">Forgot Password</a>
</form>
<?php require __DIR__ . '/layout/footer.php'; ?>
