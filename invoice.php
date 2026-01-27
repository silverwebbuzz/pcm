<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('
    SELECT pay.*, p.first_name, p.last_name
    FROM payments pay
    JOIN patients p ON p.id = pay.patient_id
    WHERE pay.id = ?
');
$stmt->execute([$id]);
$payment = $stmt->fetch();

require __DIR__ . '/layout/header.php';
?>
<h2>Invoice</h2>
<?php if (!$payment): ?>
    <p>Invoice not found.</p>
<?php else: ?>
    <p><strong>Invoice Ref:</strong> <?php echo e($payment['receipt_no']); ?></p>
    <p><strong>Patient:</strong> <?php echo e($payment['first_name'] . ' ' . $payment['last_name']); ?></p>
    <p><strong>Date:</strong> <?php echo e($payment['payment_date']); ?></p>
    <p><strong>Amount Due:</strong> <?php echo format_money($payment['amount']); ?></p>
    <p><strong>Payment Method:</strong> <?php echo e($payment['method']); ?></p>
    <p><strong>Notes:</strong> <?php echo e($payment['notes']); ?></p>
<?php endif; ?>
<?php require __DIR__ . '/layout/footer.php'; ?>
