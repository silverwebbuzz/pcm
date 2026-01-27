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
<div class="auth-container">
    <div class="auth-card">
        <div class="page-header">
            <div>
                <h2>Login</h2>
                <div class="page-subtitle">Access your dashboard securely</div>
            </div>
        </div>
        <?php if ($error): ?><div class="error"><?php echo e($error); ?></div><?php endif; ?>
        <form method="post">
            <label>Email
                <input type="email" name="email" required>
            </label>
            <label>Password
                <input type="password" name="password" required>
            </label>
            <div class="form-actions">
                <button class="btn" type="submit">Login</button>
                <a class="btn ghost" href="forgot_password.php">Forgot Password</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/layout/footer.php'; ?>
