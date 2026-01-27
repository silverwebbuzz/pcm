<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$error = '';
$step = 1;
$question = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $answer = trim($_POST['answer'] ?? '');

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = 'Email not found.';
    } else {
        $question = $user['security_question'];
        $step = 2;
        if ($answer !== '') {
            if (password_verify($answer, $user['security_answer_hash'])) {
                $_SESSION['reset_user_id'] = $user['id'];
                redirect('reset_password.php');
            } else {
                $error = 'Incorrect security answer.';
            }
        }
    }
}

require __DIR__ . '/layout/header.php';
?>
<div class="auth-container">
    <div class="auth-card">
        <div class="page-header">
            <div>
                <h2>Forgot Password</h2>
                <div class="page-subtitle">Verify your account to reset password</div>
            </div>
        </div>
        <?php if ($error): ?><div class="error"><?php echo e($error); ?></div><?php endif; ?>
        <form method="post">
            <label>Email
                <input type="email" name="email" value="<?php echo e($email); ?>" required>
            </label>
            <?php if ($step === 2): ?>
                <label>Security Question
                    <input type="text" value="<?php echo e($question); ?>" disabled>
                </label>
                <label>Answer
                    <input type="text" name="answer" required>
                </label>
            <?php endif; ?>
            <div class="form-actions">
                <button class="btn" type="submit"><?php echo $step === 2 ? 'Verify' : 'Next'; ?></button>
                <a class="btn ghost" href="login.php">Back to Login</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/layout/footer.php'; ?>
