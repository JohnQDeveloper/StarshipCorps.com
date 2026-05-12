<?php

declare(strict_types=1);

use Delight\Auth\ConfirmationRequestNotFound;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\TooManyRequestsException;

global $auth;

$email = '';
$formError = '';
$formSuccess = '';

if ($auth->isLoggedIn()) {
    redirect_to('/account');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = posted_string('email', 249);

    try {
        $auth->resendConfirmationForEmail($email, function (string $selector, string $token) use ($email): void {
            send_verification_email($email, $selector, $token);
        });

        $formSuccess = 'If a verification is pending, a new link has been sent.';
        $email = '';
    } catch (InvalidEmailException | ConfirmationRequestNotFound) {
        $formSuccess = 'If a verification is pending, a new link has been sent.';
    } catch (TooManyRequestsException) {
        $formError = 'Too many attempts. Please wait a moment and try again.';
    } catch (Throwable $e) {
        error_log('Verification resend failed: ' . $e->getMessage());
        $formError = 'The verification email could not be sent. Please try again later.';

        if (DEBUG) {
            $formError .= ' Mail error: ' . $e->getMessage();
        }
    }
}
