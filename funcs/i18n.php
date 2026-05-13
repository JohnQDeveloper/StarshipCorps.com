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
            'fleet.kicker' => 'Command roster',
            'fleet.ship_class' => 'Class',
            'fleet.assign_captain' => 'Assigned captain',
            'fleet.unassigned' => 'Unassigned',
            'fleet.save_assignments' => 'Save assignments',
            'fleet.saved' => 'Fleet assignments saved.',
            'fleet.captain_created' => 'Captain created.',
            'fleet.invalid_assignment' => 'Choose each captain once, or leave a ship unassigned.',
            'fleet.save_failed' => 'Fleet assignments could not be saved.',
            'fleet.persistence_unavailable' => 'Fleet persistence is not available yet.',
            'fleet.captain_persistence_unavailable' => 'Captain persistence is not available yet.',
            'fleet.invalid_captain_name' => 'Enter a captain name between 1 and 40 characters.',
            'fleet.captain_create_failed' => 'Captain could not be created.',
            'fleet.captain_limit_reached' => 'All four captain slots are filled.',
            'fleet.setup_required_title' => 'Database setup required',
            'fleet.setup_required_body' => 'Create the captains and fleet assignments tables to save this roster.',
            'fleet.captains_kicker' => 'Captain registry',
            'fleet.create_captain' => 'Create captain',
            'fleet.no_captains' => 'No captains have been created yet.',
            'fleet.captain_name' => 'Captain name',
            'fleet.create_captain_button' => 'Create captain',
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
            'fleet.kicker' => 'Lista de mando',
            'fleet.ship_class' => 'Clase',
            'fleet.assign_captain' => 'Capitán asignado',
            'fleet.unassigned' => 'Sin asignar',
            'fleet.save_assignments' => 'Guardar asignaciones',
            'fleet.saved' => 'Asignaciones de flota guardadas.',
            'fleet.captain_created' => 'Capitán creado.',
            'fleet.invalid_assignment' => 'Elige cada capitán una vez, o deja una nave sin asignar.',
            'fleet.save_failed' => 'No se pudieron guardar las asignaciones de flota.',
            'fleet.persistence_unavailable' => 'La persistencia de flota aún no está disponible.',
            'fleet.captain_persistence_unavailable' => 'La persistencia de capitanes aún no está disponible.',
            'fleet.invalid_captain_name' => 'Ingresa un nombre de capitán de entre 1 y 40 caracteres.',
            'fleet.captain_create_failed' => 'No se pudo crear el capitán.',
            'fleet.captain_limit_reached' => 'Los cuatro espacios de capitán están llenos.',
            'fleet.setup_required_title' => 'Configuración de base de datos requerida',
            'fleet.setup_required_body' => 'Crea las tablas de capitanes y asignaciones de flota para guardar esta lista.',
            'fleet.captains_kicker' => 'Registro de capitanes',
            'fleet.create_captain' => 'Crear capitán',
            'fleet.no_captains' => 'Aún no se ha creado ningún capitán.',
            'fleet.captain_name' => 'Nombre del capitán',
            'fleet.create_captain_button' => 'Crear capitán',
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
            'fleet.kicker' => 'Escala de comando',
            'fleet.ship_class' => 'Classe',
            'fleet.assign_captain' => 'Capitão designado',
            'fleet.unassigned' => 'Sem designação',
            'fleet.save_assignments' => 'Salvar designações',
            'fleet.saved' => 'Designações da frota salvas.',
            'fleet.captain_created' => 'Capitão criado.',
            'fleet.invalid_assignment' => 'Escolha cada capitão uma vez, ou deixe uma nave sem designação.',
            'fleet.save_failed' => 'Não foi possível salvar as designações da frota.',
            'fleet.persistence_unavailable' => 'A persistência da frota ainda não está disponível.',
            'fleet.captain_persistence_unavailable' => 'A persistência de capitães ainda não está disponível.',
            'fleet.invalid_captain_name' => 'Informe um nome de capitão entre 1 e 40 caracteres.',
            'fleet.captain_create_failed' => 'Não foi possível criar o capitão.',
            'fleet.captain_limit_reached' => 'Os quatro espaços de capitão estão preenchidos.',
            'fleet.setup_required_title' => 'Configuração do banco de dados necessária',
            'fleet.setup_required_body' => 'Crie as tabelas de capitães e designações da frota para salvar esta escala.',
            'fleet.captains_kicker' => 'Registro de capitães',
            'fleet.create_captain' => 'Criar capitão',
            'fleet.no_captains' => 'Nenhum capitão foi criado ainda.',
            'fleet.captain_name' => 'Nome do capitão',
            'fleet.create_captain_button' => 'Criar capitão',
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
            'fleet.kicker' => '指挥名册',
            'fleet.ship_class' => '舰级',
            'fleet.assign_captain' => '已分配舰长',
            'fleet.unassigned' => '未分配',
            'fleet.save_assignments' => '保存分配',
            'fleet.saved' => '舰队分配已保存。',
            'fleet.captain_created' => '舰长已创建。',
            'fleet.invalid_assignment' => '每位舰长只能选择一次，也可以让舰船保持未分配。',
            'fleet.save_failed' => '无法保存舰队分配。',
            'fleet.persistence_unavailable' => '舰队持久化尚不可用。',
            'fleet.captain_persistence_unavailable' => '舰长持久化尚不可用。',
            'fleet.invalid_captain_name' => '请输入 1 到 40 个字符的舰长名称。',
            'fleet.captain_create_failed' => '无法创建舰长。',
            'fleet.captain_limit_reached' => '四个舰长栏位均已填满。',
            'fleet.setup_required_title' => '需要数据库设置',
            'fleet.setup_required_body' => '创建舰长表和舰队分配表以保存此名册。',
            'fleet.captains_kicker' => '舰长登记',
            'fleet.create_captain' => '创建舰长',
            'fleet.no_captains' => '尚未创建任何舰长。',
            'fleet.captain_name' => '舰长名称',
            'fleet.create_captain_button' => '创建舰长',
            'map.region_label' => '区域',
            'map.grid_label' => 'HighSec 星系网格地图',
        ],
    ];

    return $strings[get_language()][$key] ?? $strings['en'][$key] ?? $key;
}
