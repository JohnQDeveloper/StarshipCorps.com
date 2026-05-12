<?php

declare(strict_types=1);

use Delight\Auth\InvalidSelectorTokenPairException;
use Delight\Auth\TokenExpiredException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UserAlreadyExistsException;

global $auth;

$formError = '';
$formSuccess = '';

$selector = query_string('selector', 64);
$token = query_string('token', 255);

if ($selector === '' || $token === '') {
    $formError = 'The verification link is missing required information.';
} else {
    try {
        $auth->confirmEmailAndSignIn($selector, $token);
        $formSuccess = 'Your email address has been verified. You are signed in.';
    } catch (InvalidSelectorTokenPairException) {
        $formError = 'This verification link is invalid.';
    } catch (TokenExpiredException) {
        $formError = 'This verification link has expired.';
    } catch (UserAlreadyExistsException) {
        $formError = 'An account already exists for this email address.';
    } catch (TooManyRequestsException) {
        $formError = 'Too many attempts. Please wait a moment and try again.';
    }
}
