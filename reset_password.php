<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

if (empty($_SESSION['reset_user_id'])) {
    redirect('forgot_password.php');
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $_SESSION['reset_user_id']]);
        unset($_SESSION['reset_user_id']);
        $success = 'Password updated. You can now login.';
    }
}

require __DIR__ . '/layout/header.php';
?>
<h2>Reset Password</h2>
<?php if ($error): ?><div class="error"><?php echo e($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="success"><?php echo e($success); ?></div><?php endif; ?>
<form method="post">
    <label>New Password
        <input type="password" name="password" required>
    </label>
    <label>Confirm Password
        <input type="password" name="confirm_password" required>
    </label>
    <button class="btn" type="submit">Update Password</button>
</form>
<?php require __DIR__ . '/layout/footer.php'; ?>
