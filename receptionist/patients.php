<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['receptionist']);

$pdo = db();
$editId = (int) ($_GET['edit'] ?? 0);
$patient = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM patients WHERE id = ?');
    $stmt->execute([$editId]);
    $patient = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'gender' => trim($_POST['gender'] ?? ''),
        'dob' => $_POST['dob'] ?? null,
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
    ];
    if (!empty($_POST['patient_id'])) {
        $stmt = $pdo->prepare('UPDATE patients SET first_name = ?, last_name = ?, gender = ?, dob = ?, phone = ?, address = ? WHERE id = ?');
        $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['gender'],
            $data['dob'],
            $data['phone'],
            $data['address'],
            (int) $_POST['patient_id']
        ]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO patients (first_name, last_name, gender, dob, phone, address, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['gender'],
            $data['dob'],
            $data['phone'],
            $data['address'],
            current_user()['id']
        ]);
    }
    redirect('receptionist/patients.php');
}

$patients = $pdo->query('SELECT id, first_name, last_name, phone FROM patients ORDER BY created_at DESC')->fetchAll();

require __DIR__ . '/../layout/header.php';
?>
<h2>Register Patients</h2>
<form method="post">
    <input type="hidden" name="patient_id" value="<?php echo $patient ? (int) $patient['id'] : 0; ?>">
    <div class="grid">
        <label>First Name
            <input name="first_name" required value="<?php echo e($patient['first_name'] ?? ''); ?>">
        </label>
        <label>Last Name
            <input name="last_name" required value="<?php echo e($patient['last_name'] ?? ''); ?>">
        </label>
        <label>Gender
            <select name="gender">
                <option value="">Select</option>
                <?php foreach (['Male','Female','Other'] as $g): ?>
                    <option value="<?php echo $g; ?>" <?php if (($patient['gender'] ?? '') === $g) echo 'selected'; ?>><?php echo $g; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Date of Birth
            <input type="date" name="dob" value="<?php echo e($patient['dob'] ?? ''); ?>">
        </label>
        <label>Phone
            <input name="phone" value="<?php echo e($patient['phone'] ?? ''); ?>">
        </label>
        <label>Address
            <input name="address" value="<?php echo e($patient['address'] ?? ''); ?>">
        </label>
    </div>
    <button class="btn" type="submit"><?php echo $patient ? 'Update' : 'Register'; ?></button>
</form>

<table class="data-table" data-page-size="7">
    <thead><tr><th>Name</th><th>Phone</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($patients as $p): ?>
        <tr>
            <td><?php echo e($p['first_name'] . ' ' . $p['last_name']); ?></td>
            <td><?php echo e($p['phone']); ?></td>
            <td><a class="btn secondary" href="patients.php?edit=<?php echo $p['id']; ?>">Edit</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../layout/footer.php'; ?>
