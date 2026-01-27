<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
$user = current_user();
$role = $user['role'] ?? '';
$menu = [];
if ($user) {
    if ($role === 'admin_doctor') {
        $menu = [
            'Dashboard' => 'admin/dashboard.php',
            'Patients' => 'admin/patients.php',
            'Treatment Plans' => 'admin/treatment_plans.php',
            'Sessions' => 'admin/sessions.php',
            'Payments' => 'admin/payments.php',
            'Users' => 'admin/users.php',
            'Assignments' => 'admin/assignments.php',
        ];
        if ((int) $user['can_view_reports'] === 1) {
            $menu['Reports'] = 'admin/reports.php';
        }
    } elseif ($role === 'sub_doctor') {
        $menu = [
            'Dashboard' => 'subdoctor/dashboard.php',
            'Assigned Patients' => 'subdoctor/patients.php',
            'Session Notes' => 'subdoctor/sessions.php',
        ];
    } elseif ($role === 'receptionist') {
        $menu = [
            'Dashboard' => 'receptionist/dashboard.php',
            'Register Patients' => 'receptionist/patients.php',
            'Payments' => 'receptionist/payments.php',
        ];
    } elseif ($role === 'patient') {
        $menu = [
            'Dashboard' => 'patient/dashboard.php',
            'Profile' => 'patient/profile.php',
            'Sessions' => 'patient/sessions.php',
            'Payments' => 'patient/payments.php',
        ];
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Physio Clinic</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <div class="brand">
            <div class="brand-mark">◼︎</div>
            <div>
                <div class="brand-title">PhysioTracker</div>
                <div class="brand-subtitle">Patient Management System</div>
            </div>
        </div>
        <nav class="main-nav">
            <?php if ($user): ?>
                <?php foreach ($menu as $label => $path): ?>
                    <a href="<?php echo BASE_URL . $path; ?>"><?php echo e($label); ?></a>
                <?php endforeach; ?>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>login.php">Login</a>
            <?php endif; ?>
        </nav>
        <?php if ($user): ?>
            <div class="user-chip">
                <div class="user-avatar"><?php echo e(strtoupper(substr($user['name'], 0, 1))); ?></div>
                <div>
                    <div class="user-name"><?php echo e($user['name']); ?></div>
                    <div class="user-role"><?php echo e(str_replace('_', ' ', $role)); ?></div>
                </div>
                <div class="user-actions">
                    <a href="<?php echo BASE_URL; ?>change_password.php">Change Password</a>
                    <a href="<?php echo BASE_URL; ?>logout.php">Logout</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</header>
<main class="page">
    <div class="container">
