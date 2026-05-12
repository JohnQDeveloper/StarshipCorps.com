<?php

declare(strict_types=1);

use Delight\Auth\DuplicateUsernameException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\InvalidPasswordException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UserAlreadyExistsException;

global $auth;

$email = '';
$username = '';
$formError = '';
$formSuccess = '';

if ($auth->isLoggedIn()) {
    redirect_to('/account');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = posted_string('email', 249);
    $username = posted_string('username', 100);
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');
    $passwordError = validate_password_confirmation($password, $passwordConfirm);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formError = 'Enter a valid email address.';
    } elseif ($username === '') {
        $formError = 'Choose a username.';
    } elseif (preg_match('/[\x00-\x1f\x7f\/:@\\\\]/', $username) !== 0) {
        $formError = 'Usernames cannot contain control characters, slashes, colons, at signs, or backslashes.';
    } elseif ($passwordError !== null) {
        $formError = $passwordError;
    } else {
        try {
            $auth->registerWithUniqueUsername(
                $email,
                $password,
                $username,
                function (string $selector, string $token) use ($email): void {
                    send_verification_email($email, $selector, $token);
                }
            );

            $formSuccess = 'Account created. Check your email to verify your address before signing in.';
            $email = '';
            $username = '';
        } catch (InvalidEmailException) {
            $formError = 'Enter a valid email address.';
        } catch (InvalidPasswordException) {
            $formError = 'Choose a stronger password.';
        } catch (UserAlreadyExistsException) {
            $formError = 'An account already exists for that email address.';
        } catch (DuplicateUsernameException) {
            $formError = 'That username is already taken.';
        } catch (TooManyRequestsException) {
            $formError = 'Too many attempts. Please wait a moment and try again.';
        } catch (Throwable $e) {
            error_log('Verification email failed: ' . $e->getMessage());
            $formError = 'The account was created, but the verification email could not be sent. Please contact support.';

            if (DEBUG) {
                $formError .= ' Mail error: ' . $e->getMessage();
            }
        }
    }
}
