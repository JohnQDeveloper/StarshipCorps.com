<?php

declare(strict_types=1);

function init_language(): void
{
    if (!isset($_SESSION['language'])) {
        $_SESSION['language'] = 'en';
    }
}

function get_language(): string
{
    return (string)($_SESSION['language'] ?? 'en');
}

function t(string $key): string
{
    $strings = [
        'index.title' => 'Starship Corps',
    ];

    return $strings[$key] ?? $key;
}
