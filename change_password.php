<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_login();

$user = current_user();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password_hash'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $user['id']]);
        $success = 'Password updated successfully.';
    }
}

require __DIR__ . '/layout/header.php';
?>
<h2>Change Password</h2>
<?php if ($error): ?><div class="error"><?php echo e($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="success"><?php echo e($success); ?></div><?php endif; ?>
<form method="post">
    <label>Current Password
        <input type="password" name="current_password" required>
    </label>
    <label>New Password
        <input type="password" name="password" required>
    </label>
    <label>Confirm Password
        <input type="password" name="confirm_password" required>
    </label>
    <button class="btn" type="submit">Update</button>
</form>
<?php require __DIR__ . '/layout/footer.php'; ?>
