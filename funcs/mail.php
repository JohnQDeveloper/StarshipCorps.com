<?php

declare(strict_types=1);

function send_auth_email(string $to, string $subject, string $html, string $text): void
{
    $apiKey = (string)(defined('RESEND_API_KEY') ? RESEND_API_KEY : getenv('RESEND_API_KEY'));
    $from = resend_from_address();

    if ($apiKey === '') {
        throw new RuntimeException('RESEND_API_KEY is not configured.');
    }

    $client = \Resend::client($apiKey);

    $client->emails->send([
        'from' => $from,
        'to' => [$to],
        'subject' => $subject,
        'html' => $html,
        'text' => $text,
    ]);
}

function resend_from_address(): string
{
    $from = trim((string)getenv('RESEND_FROM'));

    if ($from === '') {
        throw new RuntimeException('RESEND_FROM is not configured. Use a sender at a verified Resend domain.');
    }

    return $from;
}

function send_verification_email(string $email, string $selector, string $token): void
{
    $url = app_base_url() . '/verify-email?selector=' . urlencode($selector) . '&token=' . urlencode($token);

    send_auth_email(
        $email,
        'Verify your Starship Corps account',
        '<p>Welcome to Starship Corps.</p><p><a href="' . e($url) . '">Verify your email address</a></p>',
        "Welcome to Starship Corps.\n\nVerify your email address:\n" . $url
    );
}

function send_password_reset_email(string $email, string $selector, string $token): void
{
    $url = app_base_url() . '/reset-password?selector=' . urlencode($selector) . '&token=' . urlencode($token);

    send_auth_email(
        $email,
        'Reset your Starship Corps password',
        '<p>Use the link below to reset your Starship Corps password.</p><p><a href="' . e($url) . '">Reset your password</a></p>',
        "Use the link below to reset your Starship Corps password:\n" . $url
    );
}
