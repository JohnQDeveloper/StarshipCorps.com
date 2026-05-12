<?php

declare(strict_types=1);

use Delight\Auth\InvalidPasswordException;
use Delight\Auth\InvalidSelectorTokenPairException;
use Delight\Auth\ResetDisabledException;
use Delight\Auth\TokenExpiredException;
use Delight\Auth\TooManyRequestsException;

global $auth;

$formError = '';
$formSuccess = '';
$selector = $_SERVER['REQUEST_METHOD'] === 'POST' ? posted_string('selector', 64) : query_string('selector', 64);
$token = $_SERVER['REQUEST_METHOD'] === 'POST' ? posted_string('token', 255) : query_string('token', 255);
$canResetPassword = false;

if ($selector === '' || $token === '') {
    $formError = 'The reset link is missing required information.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');
    $passwordError = validate_password_confirmation($password, $passwordConfirm);

    if ($passwordError !== null) {
        $formError = $passwordError;
        $canResetPassword = true;
    } else {
        try {
            $auth->resetPasswordAndSignIn($selector, $token, $password);
            $formSuccess = 'Your password has been reset. You are signed in.';
        } catch (InvalidSelectorTokenPairException) {
            $formError = 'This reset link is invalid.';
        } catch (TokenExpiredException) {
            $formError = 'This reset link has expired.';
        } catch (ResetDisabledException) {
            $formError = 'Password reset is disabled for this account.';
        } catch (InvalidPasswordException) {
            $formError = 'Choose a stronger password.';
            $canResetPassword = true;
        } catch (TooManyRequestsException) {
            $formError = 'Too many attempts. Please wait a moment and try again.';
            $canResetPassword = true;
        }
    }
} else {
    try {
        $auth->canResetPasswordOrThrow($selector, $token);
        $canResetPassword = true;
    } catch (InvalidSelectorTokenPairException) {
        $formError = 'This reset link is invalid.';
    } catch (TokenExpiredException) {
        $formError = 'This reset link has expired.';
    } catch (ResetDisabledException) {
        $formError = 'Password reset is disabled for this account.';
    } catch (TooManyRequestsException) {
        $formError = 'Too many attempts. Please wait a moment and try again.';
    }
}
