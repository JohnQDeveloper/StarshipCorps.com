<?php

declare(strict_types=1);

use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\InvalidPasswordException;
use Delight\Auth\TooManyRequestsException;

global $auth;

$email = '';
$formError = $alertDanger ?? '';

if ($auth->isLoggedIn()) {
    redirect_to('/account');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = posted_string('email', 249);
    $password = (string)($_POST['password'] ?? '');
    $rememberDuration = isset($_POST['remember']) ? (int)(60 * 60 * 24 * 30) : null;

    try {
        $auth->login($email, $password, $rememberDuration);
        redirect_to('/account');
    } catch (InvalidEmailException | InvalidPasswordException) {
        $formError = 'The email address or password is incorrect.';
    } catch (EmailNotVerifiedException) {
        $formError = 'Verify your email address before signing in. You can request a fresh verification link below.';
    } catch (TooManyRequestsException) {
        $formError = 'Too many attempts. Please wait a moment and try again.';
    }
}
