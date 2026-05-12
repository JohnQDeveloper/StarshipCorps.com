<?php

declare(strict_types=1);

use Delight\Auth\Status;

function csrf_token(): string
{
    if (empty($_SESSION['csrf-token'])) {
        $_SESSION['csrf-token'] = bin2hex(random_bytes(32));
    }

    return (string)$_SESSION['csrf-token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function posted_string(string $key, int $maxLength = 255): string
{
    $value = trim((string)($_POST[$key] ?? ''));

    if (strlen($value) > $maxLength) {
        return substr($value, 0, $maxLength);
    }

    return $value;
}

function query_string(string $key, int $maxLength = 255): string
{
    $value = trim((string)($_GET[$key] ?? ''));

    if (strlen($value) > $maxLength) {
        return substr($value, 0, $maxLength);
    }

    return $value;
}

function validate_password_confirmation(string $password, string $confirmation): ?string
{
    if ($password === '') {
        return 'Enter a password.';
    }

    if ($password !== $confirmation) {
        return 'The passwords do not match.';
    }

    return null;
}

function app_base_url(): string
{
    $host = (string)($_SERVER['HTTP_HOST'] ?? getenv('HOSTNAME') ?: 'localhost');
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $scheme = $isHttps ? 'https' : 'http';

    return $scheme . '://' . $host;
}

function redirect_to(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function getAuthUserStatusById(int $userId): ?int
{
    global $DAL;

    if ($userId <= 0) {
        return null;
    }

    $rows = $DAL->r('SELECT status FROM users WHERE id=:id', [':id' => $userId]);

    if ($rows === false || $rows === []) {
        return null;
    }

    return (int)$rows[0]['status'];
}

function isBlockedAuthStatus(int $status): bool
{
    return in_array($status, [Status::ARCHIVED, Status::BANNED, Status::LOCKED, Status::SUSPENDED], true);
}

function getBlockedAuthStatusMessage(int $status): string
{
    return match ($status) {
        Status::ARCHIVED => 'This account has been archived.',
        Status::BANNED => 'This account has been banned.',
        Status::LOCKED => 'This account is locked.',
        Status::SUSPENDED => 'This account has been suspended.',
        default => 'This account cannot sign in.',
    };
}
