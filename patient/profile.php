<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['patient']);

$user = current_user();
$stmt = db()->prepare('SELECT * FROM patients WHERE user_id = ?');
$stmt->execute([$user['id']]);
$patient = $stmt->fetch();

require __DIR__ . '/../layout/header.php';
?>
<h2>My Profile</h2>
<?php if (!$patient): ?>
    <p>Profile not found. Contact the clinic.</p>
<?php else: ?>
    <p><strong><?php echo e($patient['first_name'] . ' ' . $patient['last_name']); ?></strong></p>
    <p>Phone: <?php echo e($patient['phone']); ?> | DOB: <?php echo e($patient['dob']); ?> | Age: <?php echo e($patient['age']); ?></p>
    <p>Gender: <?php echo e($patient['gender']); ?> | Occupation: <?php echo e($patient['occupation']); ?></p>
    <p>Assessment Date: <?php echo e($patient['assessment_date']); ?> | Dominance: <?php echo e($patient['dominance']); ?></p>
    <p>Duration of Condition: <?php echo e($patient['condition_duration']); ?></p>
    <p><strong>Chief Complain:</strong> <?php echo nl2br(e($patient['chief_complain'])); ?></p>
    <p><strong>Diagnosis:</strong> <?php echo nl2br(e($patient['diagnosis'])); ?></p>
    <p><strong>Treatment Goals:</strong> <?php echo nl2br(e($patient['treatment_goals'])); ?></p>
<?php endif; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>
