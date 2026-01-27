<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['patient']);

$user = current_user();
$stmt = db()->prepare('SELECT id FROM patients WHERE user_id = ?');
$stmt->execute([$user['id']]);
$patientId = (int) $stmt->fetchColumn();

$payments = [];
if ($patientId) {
    $stmt = db()->prepare('SELECT * FROM payments WHERE patient_id = ? ORDER BY payment_date DESC');
    $stmt->execute([$patientId]);
    $payments = $stmt->fetchAll();
}

require __DIR__ . '/../layout/header.php';
?>
<h2>Payment History</h2>
<?php if (!$patientId): ?>
    <p>No patient record found.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Receipt</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
            <tr>
                <td><?php echo e($p['payment_date']); ?></td>
                <td><?php echo format_money($p['amount']); ?></td>
                <td><?php echo e($p['method']); ?></td>
                <td><a class="btn secondary" href="../receipt.php?id=<?php echo $p['id']; ?>">Receipt</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>
