<?php

declare(strict_types=1);

$accountError = '';
$accountSuccess = '';

if (query_string('updated', 32) === 'language') {
    $accountSuccess = t('account.language.updated');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$requestedLanguage = posted_string('language', 16);

if (!set_language($requestedLanguage)) {
    $accountError = t('account.language.invalid');
    return;
}

redirect_to('/account?updated=language');
