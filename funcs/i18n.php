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
    $strings = [
        'en' => [
            'index.title' => 'Starship Corps',
            'nav.menu' => 'Menu',
            'nav.account' => 'Account',
            'nav.logout' => 'Log out',
            'account.title' => 'Account',
            'account.signed_in_as' => 'You are signed in as',
            'account.home' => 'Home',
            'account.language.title' => 'Language',
            'account.language.label' => 'Language',
            'account.language.save' => 'Save language',
            'account.language.updated' => 'Language updated.',
            'account.language.invalid' => 'Choose a supported language.',
            'game.nav.dashboard' => 'Dashboard',
            'game.nav.fleet' => 'Fleet',
            'game.nav.starbase' => 'Starbase',
            'game.nav.comms' => 'Comms',
            'game.nav.map' => 'Map',
            'game.nav.markets' => 'Markets',
            'game.placeholder.body' => 'Systems are coming online for this command station.',
            'map.region_label' => 'Region',
            'map.grid_label' => 'HighSec system grid map',
        ],
        'es' => [
            'index.title' => 'Starship Corps',
            'nav.menu' => 'Menú',
            'nav.account' => 'Cuenta',
            'nav.logout' => 'Cerrar sesión',
            'account.title' => 'Cuenta',
            'account.signed_in_as' => 'Has iniciado sesión como',
            'account.home' => 'Inicio',
            'account.language.title' => 'Idioma',
            'account.language.label' => 'Idioma',
            'account.language.save' => 'Guardar idioma',
            'account.language.updated' => 'Idioma actualizado.',
            'account.language.invalid' => 'Elige un idioma compatible.',
            'game.nav.dashboard' => 'Panel',
            'game.nav.fleet' => 'Flota',
            'game.nav.starbase' => 'Base estelar',
            'game.nav.comms' => 'Comunicaciones',
            'game.nav.map' => 'Mapa',
            'game.nav.markets' => 'Mercados',
            'game.placeholder.body' => 'Los sistemas se están activando para esta estación de mando.',
            'map.region_label' => 'Región',
            'map.grid_label' => 'Mapa de cuadrícula de sistemas HighSec',
        ],
        'pt-br' => [
            'index.title' => 'Starship Corps',
            'nav.menu' => 'Menu',
            'nav.account' => 'Conta',
            'nav.logout' => 'Sair',
            'account.title' => 'Conta',
            'account.signed_in_as' => 'Você entrou como',
            'account.home' => 'Início',
            'account.language.title' => 'Idioma',
            'account.language.label' => 'Idioma',
            'account.language.save' => 'Salvar idioma',
            'account.language.updated' => 'Idioma atualizado.',
            'account.language.invalid' => 'Escolha um idioma compatível.',
            'game.nav.dashboard' => 'Painel',
            'game.nav.fleet' => 'Frota',
            'game.nav.starbase' => 'Base estelar',
            'game.nav.comms' => 'Comunicações',
            'game.nav.map' => 'Mapa',
            'game.nav.markets' => 'Mercados',
            'game.placeholder.body' => 'Os sistemas estão ficando online para esta estação de comando.',
            'map.region_label' => 'Região',
            'map.grid_label' => 'Mapa em grade de sistemas HighSec',
        ],
        'zh-cn' => [
            'index.title' => 'Starship Corps',
            'nav.menu' => '菜单',
            'nav.account' => '账户',
            'nav.logout' => '退出登录',
            'account.title' => '账户',
            'account.signed_in_as' => '你当前登录为',
            'account.home' => '首页',
            'account.language.title' => '语言',
            'account.language.label' => '语言',
            'account.language.save' => '保存语言',
            'account.language.updated' => '语言已更新。',
            'account.language.invalid' => '请选择支持的语言。',
            'game.nav.dashboard' => '仪表板',
            'game.nav.fleet' => '舰队',
            'game.nav.starbase' => '星际基地',
            'game.nav.comms' => '通讯',
            'game.nav.map' => '地图',
            'game.nav.markets' => '市场',
            'game.placeholder.body' => '此指挥站的系统正在上线。',
            'map.region_label' => '区域',
            'map.grid_label' => 'HighSec 星系网格地图',
        ],
    ];

    return $strings[get_language()][$key] ?? $strings['en'][$key] ?? $key;
}
