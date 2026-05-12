<?php

declare(strict_types=1);

use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\ResetDisabledException;
use Delight\Auth\TooManyRequestsException;

global $auth;

$email = '';
$formError = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = posted_string('email', 249);

    try {
        $auth->forgotPassword($email, function (string $selector, string $token) use ($email): void {
            send_password_reset_email($email, $selector, $token);
        });

        $formSuccess = 'If the account can be reset, a password reset link has been sent.';
        $email = '';
    } catch (InvalidEmailException | EmailNotVerifiedException | ResetDisabledException) {
        $formSuccess = 'If the account can be reset, a password reset link has been sent.';
    } catch (TooManyRequestsException) {
        $formError = 'Too many attempts. Please wait a moment and try again.';
    } catch (Throwable $e) {
        error_log('Password reset email failed: ' . $e->getMessage());
        $formError = 'The reset email could not be sent. Please try again later.';

        if (DEBUG) {
            $formError .= ' Mail error: ' . $e->getMessage();
        }
    }
}
