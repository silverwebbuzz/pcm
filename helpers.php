<?php
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
