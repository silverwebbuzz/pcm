<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_login();
require_role(['receptionist']);

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId = (int) ($_POST['patient_id'] ?? 0);
    $amount = (float) ($_POST['amount'] ?? 0);
    $date = $_POST['payment_date'] ?? current_date();
    $method = trim($_POST['method'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $receiptNo = 'RCPT-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
    $caseId = $patientId ? latest_case_id($patientId) : null;
    $stmt = $pdo->prepare('INSERT INTO payments (patient_id, case_id, amount, payment_date, method, notes, receipt_no, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$patientId, $caseId, $amount, $date, $method, $notes, $receiptNo, current_user()['id']]);
}

$patients = $pdo->query('SELECT id, first_name, last_name FROM patients ORDER BY first_name')->fetchAll();
$payments = $pdo->query('
    SELECT pay.*, p.first_name, p.last_name
    FROM payments pay
    JOIN patients p ON p.id = pay.patient_id
    ORDER BY pay.payment_date DESC
')->fetchAll();

require __DIR__ . '/../layout/header.php';
?>
<h2>Payments</h2>
<form method="post">
    <div class="grid">
        <label>Patient
            <select name="patient_id" required>
                <option value="">Select</option>
                <?php foreach ($patients as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo e($p['first_name'] . ' ' . $p['last_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Amount
            <input type="number" step="0.01" name="amount" required>
        </label>
        <label>Date
            <input type="date" name="payment_date" value="<?php echo current_date(); ?>">
        </label>
        <label>Method
            <input name="method" placeholder="Cash/UPI/Card">
        </label>
    </div>
    <label>Notes
        <textarea name="notes" rows="2"></textarea>
    </label>
    <button class="btn" type="submit">Record Payment</button>
</form>

<table class="data-table" data-page-size="7">
    <thead><tr><th>Date</th><th>Patient</th><th>Amount</th><th>Method</th><th>Receipt</th></tr></thead>
    <tbody>
    <?php foreach ($payments as $pay): ?>
        <tr>
            <td><?php echo e($pay['payment_date']); ?></td>
            <td><?php echo e($pay['first_name'] . ' ' . $pay['last_name']); ?></td>
            <td><?php echo format_money($pay['amount']); ?></td>
            <td><?php echo e($pay['method']); ?></td>
            <td><a class="btn secondary" href="../receipt.php?id=<?php echo $pay['id']; ?>">Receipt</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../layout/footer.php'; ?>
