<?php

declare(strict_types=1);

function init_language(): void
{
    if (!isset($_SESSION['language']) || !is_supported_language((string)$_SESSION['language'])) {
        $_SESSION['language'] = 'en';
    }
}

function get_language(): string
{
    $language = (string)($_SESSION['language'] ?? 'en');

    if (!is_supported_language($language)) {
        return 'en';
    }

    return $language;
}

function set_language(string $language): bool
{
    if (!is_supported_language($language)) {
        return false;
    }

    $_SESSION['language'] = $language;

    return true;
}

function is_supported_language(string $language): bool
{
    return array_key_exists($language, supported_languages());
}

/**
 * @return array<string, string>
 */
function supported_languages(): array
{
    return [
        'en' => 'English',
        'es' => 'Español',
        'pt-br' => 'Português do Brasil',
        'zh-cn' => '简体中文',
    ];
}

function t(string $key): string
{
    $languageStrings = load_language_strings(get_language());
    $fallbackStrings = load_language_strings('en');

    return $languageStrings[$key] ?? $fallbackStrings[$key] ?? $key;
}

/**
 * @return array<string, string>
 */
function load_language_strings(string $language): array
{
    static $stringsByLanguage = [];

    if (!is_supported_language($language)) {
        $language = 'en';
    }

    if (isset($stringsByLanguage[$language])) {
        return $stringsByLanguage[$language];
    }

    $languagePath = __DIR__ . '/../lang/' . $language . '.php';
    $strings = require $languagePath;

    if (!is_array($strings)) {
        return [];
    }

    $stringsByLanguage[$language] = array_filter(
        $strings,
        static fn (mixed $value, int|string $key): bool => is_string($key) && is_string($value),
        ARRAY_FILTER_USE_BOTH
    );

    return $stringsByLanguage[$language];
}
