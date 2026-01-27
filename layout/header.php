<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
$user = current_user();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Physio Clinic</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <h1>Physiotherapy Clinic</h1>
        <nav>
            <?php if ($user): ?>
                <a href="<?php echo BASE_URL; ?>dashboard.php">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>change_password.php">Change Password</a>
                <a href="<?php echo BASE_URL; ?>logout.php">Logout</a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>login.php">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
