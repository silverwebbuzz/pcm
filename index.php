<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$user = current_user();
if ($user) {
    redirect('dashboard.php');
}
redirect('login.php');
