<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['admin_doctor']);

$pdo = db();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'sub_doctor';
    $password = $_POST['password'] ?? '';
    $question = trim($_POST['security_question'] ?? '');
    $answer = trim($_POST['security_answer'] ?? '');
    $canViewReports = !empty($_POST['can_view_reports']) ? 1 : 0;

    if ($name === '' || $email === '' || $password === '' || $question === '' || $answer === '') {
        $error = 'All fields are required.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO users (role, name, email, password_hash, security_question, security_answer_hash, can_view_reports) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $role,
            $name,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $question,
            password_hash($answer, PASSWORD_DEFAULT),
            $canViewReports
        ]);
    }
}

if (isset($_GET['toggle_reports'])) {
    $userId = (int) $_GET['toggle_reports'];
    $pdo->prepare('UPDATE users SET can_view_reports = IF(can_view_reports = 1, 0, 1) WHERE id = ?')->execute([$userId]);
}

$users = $pdo->query('SELECT id, name, email, role, can_view_reports FROM users ORDER BY created_at DESC')->fetchAll();

require __DIR__ . '/../layout/header.php';
?>
<h2>Manage Users</h2>
<?php if ($error): ?><div class="error"><?php echo e($error); ?></div><?php endif; ?>
<form method="post">
    <div class="grid">
        <label>Name
            <input name="name" required>
        </label>
        <label>Email
            <input type="email" name="email" required>
        </label>
        <label>Role
            <select name="role">
                <option value="sub_doctor">Sub-Doctor</option>
                <option value="receptionist">Receptionist</option>
                <option value="admin_doctor">Admin Doctor</option>
            </select>
        </label>
        <label>Temporary Password
            <input type="password" name="password" required>
        </label>
        <label>Security Question
            <input name="security_question" required>
        </label>
        <label>Security Answer
            <input name="security_answer" required>
        </label>
        <label><input type="checkbox" name="can_view_reports" value="1"> Allow Reports</label>
    </div>
    <button class="btn" type="submit">Create User</button>
</form>

<table class="data-table" data-page-size="7">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Reports</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?php echo e($u['name']); ?></td>
            <td><?php echo e($u['email']); ?></td>
            <td><?php echo e($u['role']); ?></td>
            <td><?php echo $u['can_view_reports'] ? 'Yes' : 'No'; ?></td>
            <td>
                <a class="btn secondary" href="users.php?toggle_reports=<?php echo $u['id']; ?>">Toggle Reports</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../layout/footer.php'; ?>
