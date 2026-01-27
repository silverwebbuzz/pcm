<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_login();

$user = current_user();

switch ($user['role']) {
    case 'admin_doctor':
        redirect('admin/dashboard.php');
        break;
    case 'sub_doctor':
        redirect('subdoctor/dashboard.php');
        break;
    case 'receptionist':
        redirect('receptionist/dashboard.php');
        break;
    case 'patient':
        redirect('patient/dashboard.php');
        break;
    default:
        redirect('login.php');
}
