<?php
require_once __DIR__ . '/db.php';
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}

function current_date(): string
{
    return date('Y-m-d');
}

function format_money($amount): string
{
    return number_format((float) $amount, 2);
}

function latest_visit_id(int $patientId): ?int
{
    $stmt = db()->prepare('SELECT id FROM patient_visits WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$patientId]);
    $id = $stmt->fetchColumn();
    return $id ? (int) $id : null;
}
