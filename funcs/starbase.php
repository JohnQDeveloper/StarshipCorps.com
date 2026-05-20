<?php

declare(strict_types=1);

/**
 * @return array<int, array{id: string, type: string, name: string, tier: int, resource: string|null}>
 */
function starter_starbase_modules(): array
{
    $modules = [
        [
            'id' => 'starbase_module_factory',
            'type' => 'starbase_module_factory',
            'name' => 'Starbase Module Factory',
            'tier' => 1,
            'resource' => null,
        ],
    ];

    foreach (starter_starbase_resource_types() as $resourceType => $resourceLabel) {
        $modules[] = [
            'id' => $resourceType . '_refinery',
            'type' => 'refinery',
            'name' => $resourceLabel . ' Refinery',
            'tier' => 1,
            'resource' => $resourceType,
        ];
    }

    return $modules;
}

/**
 * @return array<string, string>
 */
function starter_starbase_resource_types(): array
{
    $highSecMap = load_starbase_highsec_map();
    $resources = $highSecMap['resources'] ?? [];

    if (!is_array($resources)) {
        return [];
    }

    $resourceTypes = [];

    foreach ($resources as $resourceType => $resourceData) {
        if (!is_string($resourceType) || !is_array($resourceData)) {
            continue;
        }

        $resourceLabel = trim((string)($resourceData['label'] ?? ''));

        if ($resourceLabel === '') {
            continue;
        }

        $resourceTypes[$resourceType] = $resourceLabel;
    }

    return $resourceTypes;
}

/**
 * @return array<string, mixed>
 */
function default_starbase_data(): array
{
    $system = starter_starbase_system();

    return [
        'name' => 'HighSec Command Starbase',
        'region' => 'HighSec',
        'security' => 'HighSec',
        'system' => $system,
        'modules' => starter_starbase_modules(),
    ];
}

/**
 * @return array{id: string, name: string, x: int, y: int}
 */
function starter_starbase_system(): array
{
    $highSecMap = load_starbase_highsec_map();
    $width = max(1, (int)($highSecMap['width'] ?? 5));
    $height = max(1, (int)($highSecMap['height'] ?? 5));
    $centerX = intdiv($width, 2);
    $centerY = intdiv($height, 2);
    $systems = $highSecMap['systems'] ?? [];

    if (is_array($systems)) {
        foreach ($systems as $system) {
            if (!is_array($system)) {
                continue;
            }

            if ((int)($system['x'] ?? -1) !== $centerX || (int)($system['y'] ?? -1) !== $centerY) {
                continue;
            }

            return [
                'id' => (string)($system['id'] ?? highSecSystemId($centerX, $centerY)),
                'name' => (string)($system['name'] ?? 'Center HighSec'),
                'x' => $centerX,
                'y' => $centerY,
            ];
        }
    }

    return [
        'id' => highSecSystemId($centerX, $centerY),
        'name' => 'Center HighSec',
        'x' => $centerX,
        'y' => $centerY,
    ];
}

/**
 * @return array<string, mixed>
 */
function load_starbase_highsec_map(): array
{
    require_once __DIR__ . '/map_generation.php';

    $highSecMapPath = __DIR__ . '/../data/highsec_map.php';
    $highSecMap = file_exists($highSecMapPath) ? require $highSecMapPath : null;

    if (is_array($highSecMap)) {
        return $highSecMap;
    }

    return generateHighSecMap();
}

/**
 * @return array{available: bool, starbase: array<string, mixed>|null}
 */
function load_user_starbase(int $userId): array
{
    global $DAL;

    if ($userId <= 0 || !starbases_table_exists()) {
        return ['available' => false, 'starbase' => null];
    }

    $rows = $DAL->r(
        'SELECT starbase_id, starbase_data FROM starbases WHERE user_id=:user_id ORDER BY starbase_id LIMIT 1',
        [':user_id' => $userId]
    );

    if ($rows === false) {
        return ['available' => false, 'starbase' => null];
    }

    if ($rows === []) {
        $starbaseData = default_starbase_data();

        if (!insert_user_starbase($userId, $starbaseData)) {
            return ['available' => false, 'starbase' => null];
        }

        return ['available' => true, 'starbase' => $starbaseData];
    }

    $starbaseId = (int)($rows[0]['starbase_id'] ?? 0);
    $starbaseData = json_decode((string)($rows[0]['starbase_data'] ?? ''), true);

    if (!is_array($starbaseData)) {
        $starbaseData = [];
    }

    $normalizedStarbase = normalize_starbase_data($starbaseData);

    if ($starbaseId > 0 && $normalizedStarbase !== $starbaseData) {
        save_user_starbase($userId, $starbaseId, $normalizedStarbase);
    }

    return ['available' => true, 'starbase' => $normalizedStarbase];
}

function starbases_table_exists(): bool
{
    global $DAL;

    $rows = $DAL->r(
        'SELECT 1 FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_name = :table_name
            LIMIT 1',
        [':table_name' => 'starbases']
    );

    return is_array($rows) && $rows !== [];
}

/**
 * @param array<string, mixed> $starbaseData
 * @return array<string, mixed>
 */
function normalize_starbase_data(array $starbaseData): array
{
    $defaultStarbase = default_starbase_data();
    $starbaseData['name'] = trim((string)($starbaseData['name'] ?? '')) ?: $defaultStarbase['name'];
    $starbaseData['region'] = 'HighSec';
    $starbaseData['security'] = 'HighSec';
    $starbaseData['system'] = $defaultStarbase['system'];
    $starbaseData['modules'] = normalize_starbase_modules($starbaseData['modules'] ?? []);

    return $starbaseData;
}

/**
 * @param mixed $modules
 * @return array<int, array{id: string, type: string, name: string, tier: int, resource: string|null}>
 */
function normalize_starbase_modules(mixed $modules): array
{
    $normalizedModules = [];

    if (is_array($modules)) {
        foreach ($modules as $module) {
            if (!is_array($module)) {
                continue;
            }

            $moduleId = trim((string)($module['id'] ?? ''));

            if ($moduleId === '') {
                continue;
            }

            $normalizedModules[$moduleId] = [
                'id' => $moduleId,
                'type' => trim((string)($module['type'] ?? '')),
                'name' => trim((string)($module['name'] ?? '')),
                'tier' => max(1, (int)($module['tier'] ?? 1)),
                'resource' => isset($module['resource']) ? trim((string)$module['resource']) : null,
            ];
        }
    }

    foreach (starter_starbase_modules() as $starterModule) {
        if (!isset($normalizedModules[$starterModule['id']])) {
            $normalizedModules[$starterModule['id']] = $starterModule;
            continue;
        }

        $normalizedModules[$starterModule['id']]['type'] = $starterModule['type'];
        $normalizedModules[$starterModule['id']]['name'] = $starterModule['name'];
        $normalizedModules[$starterModule['id']]['resource'] = $starterModule['resource'];
    }

    return array_values($normalizedModules);
}

/**
 * @param array<string, mixed> $starbaseData
 */
function insert_user_starbase(int $userId, array $starbaseData): bool
{
    global $DAL;

    $encodedStarbaseData = json_encode($starbaseData, JSON_THROW_ON_ERROR);

    return $DAL->w(
        'INSERT INTO starbases (user_id, starbase_data, updated_at)
            VALUES (:user_id, :starbase_data, NOW())',
        [
            ':user_id' => $userId,
            ':starbase_data' => $encodedStarbaseData,
        ]
    );
}

/**
 * @param array<string, mixed> $starbaseData
 */
function save_user_starbase(int $userId, int $starbaseId, array $starbaseData): bool
{
    global $DAL;

    $encodedStarbaseData = json_encode($starbaseData, JSON_THROW_ON_ERROR);

    return $DAL->w(
        'UPDATE starbases SET starbase_data=:starbase_data, updated_at=NOW()
            WHERE user_id=:user_id AND starbase_id=:starbase_id',
        [
            ':user_id' => $userId,
            ':starbase_id' => $starbaseId,
            ':starbase_data' => $encodedStarbaseData,
        ]
    );
}
