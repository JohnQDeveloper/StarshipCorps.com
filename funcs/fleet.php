<?php

declare(strict_types=1);

/**
 * @return array<int, array{id: int, name: string, class: string, callsign: string, location: array{system_id: string, system_name: string, x: int, y: int}, design: array{weapon: string, mining: string}, route: array{system_id: string, system_name: string, x: int, y: int, resource: string, resource_label: string, assigned_at: string}|null}>
 */
function fleet_ships(): array
{
    $ships = [];

    foreach (default_fleet_assignments() as $shipSlot => $captainId) {
        $ships[$shipSlot] = generated_fleet_ship($shipSlot, []);
    }

    return $ships;
}

/**
 * @param array<int, string> $usedNames
 * @param array{system_id: string, system_name: string, x: int, y: int}|null $location
 * @return array{id: int, name: string, class: string, callsign: string, location: array{system_id: string, system_name: string, x: int, y: int}, design: array{weapon: string, mining: string}, route: array{system_id: string, system_name: string, x: int, y: int, resource: string, resource_label: string, assigned_at: string}|null}
 */
function generated_fleet_ship(int $shipSlot, array $usedNames, ?array $location = null): array
{
    $name = random_fleet_ship_name($usedNames);
    $location ??= default_fleet_ship_location();

    return [
        'id' => $shipSlot,
        'name' => $name,
        'class' => 'Newbie Escort Ship',
        'callsign' => fleet_ship_callsign($name, $shipSlot),
        'location' => $location,
        'design' => [
            'weapon' => 'Basic Laser',
            'mining' => 'Basic Mining Droid',
        ],
        'route' => null,
    ];
}

/**
 * @return array{system_id: string, system_name: string, x: int, y: int}
 */
function default_fleet_ship_location(): array
{
    $system = starter_starbase_system();

    return [
        'system_id' => $system['id'],
        'system_name' => $system['name'],
        'x' => $system['x'],
        'y' => $system['y'],
    ];
}

/**
 * @param array<string, mixed>|null $starbase
 * @return array{system_id: string, system_name: string, x: int, y: int}
 */
function fleet_ship_location_from_starbase(?array $starbase): array
{
    $defaultLocation = default_fleet_ship_location();
    $system = is_array($starbase['system'] ?? null) ? $starbase['system'] : [];
    $systemId = trim((string)($system['id'] ?? ''));
    $systemName = trim((string)($system['name'] ?? ''));

    return [
        'system_id' => $systemId !== '' ? $systemId : $defaultLocation['system_id'],
        'system_name' => $systemName !== '' ? $systemName : $defaultLocation['system_name'],
        'x' => (int)($system['x'] ?? $defaultLocation['x']),
        'y' => (int)($system['y'] ?? $defaultLocation['y']),
    ];
}

/**
 * @param array<int, string> $usedNames
 */
function random_fleet_ship_name(array $usedNames): string
{
    $names = [
        'ISS Aster',
        'ISS Beacon',
        'ISS Comet',
        'ISS Dawnlight',
        'ISS Ember',
        'ISS Farpoint',
        'ISS Galatea',
        'ISS Helio',
        'ISS Ionwake',
        'ISS Juniper',
        'ISS Kepler',
        'ISS Lumen',
        'ISS Meridian',
        'ISS Nova',
        'ISS Outrider',
        'ISS Peregrine',
    ];
    $availableNames = array_values(array_diff($names, $usedNames));

    if ($availableNames === []) {
        return 'ISS Wayfinder ' . random_int(100, 999);
    }

    return $availableNames[random_int(0, count($availableNames) - 1)];
}

function fleet_ship_callsign(string $name, int $shipSlot): string
{
    $letters = strtoupper(preg_replace('/[^A-Z]/', '', $name) ?? '');
    $letters = substr($letters, 0, 3);

    if (strlen($letters) < 3) {
        $letters = 'NES';
    }

    return $letters . '-' . str_pad((string)$shipSlot, 2, '0', STR_PAD_LEFT);
}

/**
 * @return array{available: bool, captains: array<int, array{id: int, name: string}>}
 */
function load_fleet_captains(int $userId): array
{
    global $DAL;

    if ($userId <= 0) {
        return ['available' => false, 'captains' => []];
    }

    $rows = $DAL->r(
        'SELECT captain_id, captain_data FROM captains WHERE user_id=:user_id ORDER BY captain_id',
        [':user_id' => $userId]
    );

    if ($rows === false) {
        return ['available' => false, 'captains' => []];
    }

    $captains = [];

    foreach ($rows as $row) {
        $captainId = (int)($row['captain_id'] ?? 0);
        $captainData = json_decode((string)($row['captain_data'] ?? ''), true);

        if ($captainId <= 0 || !is_array($captainData)) {
            continue;
        }

        $captainName = trim((string)($captainData['name'] ?? ''));

        if ($captainName === '') {
            continue;
        }

        $captains[$captainId] = ['id' => $captainId, 'name' => $captainName];
    }

    return ['available' => true, 'captains' => $captains];
}

function create_fleet_captain(int $userId, string $captainName): bool
{
    global $DAL;

    if ($userId <= 0 || !is_valid_new_captain_name($captainName)) {
        return false;
    }

    $captainData = json_encode(['name' => $captainName], JSON_THROW_ON_ERROR);

    return $DAL->w(
        'INSERT INTO captains (user_id, captain_data, updated_at)
            VALUES (:user_id, :captain_data, NOW())',
        [
            ':user_id' => $userId,
            ':captain_data' => $captainData,
        ]
    );
}

function is_valid_new_captain_name(string $captainName): bool
{
    if ($captainName === '' || strlen($captainName) > 40) {
        return false;
    }

    return preg_match('/[\x00-\x1F\x7F]/', $captainName) !== 1;
}

/**
 * @return array<int, int>
 */
function default_fleet_assignments(): array
{
    return [
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
    ];
}

/**
 * @param array<int, array{id: int, name: string}> $captains
 * @param array<string, mixed>|null $starbase
 * @return array{available: bool, assignments: array<int, int>, ships: array<int, array{id: int, name: string, class: string, callsign: string, location: array{system_id: string, system_name: string, x: int, y: int}, design: array{weapon: string, mining: string}, route: array{system_id: string, system_name: string, x: int, y: int, resource: string, resource_label: string, assigned_at: string}|null}>}
 */
function load_fleet_assignments(int $userId, array $captains, ?array $starbase = null): array
{
    global $DAL;

    $assignments = default_fleet_assignments();
    $ships = fleet_ships();
    $starterLocation = fleet_ship_location_from_starbase($starbase);

    if ($userId <= 0) {
        return ['available' => false, 'assignments' => $assignments, 'ships' => $ships];
    }

    $rows = $DAL->r(
        'SELECT ship_slot, captain_id, ship_data FROM fleet_assignments WHERE user_id=:user_id ORDER BY ship_slot',
        [':user_id' => $userId]
    );

    if ($rows === false) {
        return ['available' => false, 'assignments' => $assignments, 'ships' => $ships];
    }

    $existingShipSlots = [];
    $usedNames = [];

    foreach ($rows as $row) {
        $shipSlot = (int)($row['ship_slot'] ?? 0);
        $captainId = (int)($row['captain_id'] ?? 0);

        if (!array_key_exists($shipSlot, $ships)) {
            continue;
        }

        $existingShipSlots[$shipSlot] = true;

        if (array_key_exists($shipSlot, $assignments) && is_valid_fleet_captain_id($captainId, $captains, true)) {
            $assignments[$shipSlot] = $captainId;
        }

        $originalShipData = (string)($row['ship_data'] ?? '');
        $shipData = json_decode($originalShipData, true);

        if (is_array($shipData)) {
            $ships[$shipSlot] = normalize_fleet_ship($shipSlot, $shipData, $starterLocation);
            $usedNames[] = $ships[$shipSlot]['name'];

            if (fleet_ship_data_needs_starter_update($shipData)) {
                save_fleet_ship_data($userId, $shipSlot, $ships[$shipSlot]);
            }
        }
    }

    foreach ($ships as $shipSlot => $ship) {
        if (isset($existingShipSlots[$shipSlot])) {
            continue;
        }

        $ships[$shipSlot] = generated_fleet_ship($shipSlot, $usedNames, $starterLocation);
        $usedNames[] = $ships[$shipSlot]['name'];

        if (!insert_fleet_ship($userId, $shipSlot, $ships[$shipSlot])) {
            return ['available' => false, 'assignments' => $assignments, 'ships' => $ships];
        }
    }

    return ['available' => true, 'assignments' => $assignments, 'ships' => $ships];
}

/**
 * @param array<string, mixed> $shipData
 * @param array{system_id: string, system_name: string, x: int, y: int}|null $defaultLocation
 * @return array{id: int, name: string, class: string, callsign: string, location: array{system_id: string, system_name: string, x: int, y: int}, design: array{weapon: string, mining: string}, route: array{system_id: string, system_name: string, x: int, y: int, resource: string, resource_label: string, assigned_at: string}|null}
 */
function normalize_fleet_ship(int $shipSlot, array $shipData, ?array $defaultLocation = null): array
{
    $ship = generated_fleet_ship($shipSlot, [], $defaultLocation);
    $name = trim((string)($shipData['name'] ?? ''));
    $callsign = trim((string)($shipData['callsign'] ?? ''));
    $location = normalize_fleet_ship_location($shipData['location'] ?? null, $ship['location']);

    if ($name !== '' && strlen($name) <= 80) {
        $ship['name'] = $name;
    }

    if ($callsign !== '' && strlen($callsign) <= 20) {
        $ship['callsign'] = $callsign;
    }

    $ship['location'] = $location;
    $ship['route'] = normalize_fleet_ship_route($shipData['route'] ?? null);

    return $ship;
}

/**
 * @param array{system_id: string, system_name: string, x: int, y: int} $defaultLocation
 * @return array{system_id: string, system_name: string, x: int, y: int}
 */
function normalize_fleet_ship_location(mixed $location, array $defaultLocation): array
{
    if (!is_array($location)) {
        return $defaultLocation;
    }

    $systemId = trim((string)($location['system_id'] ?? ''));
    $systemName = trim((string)($location['system_name'] ?? ''));

    if ($systemId === '' || $systemName === '') {
        return $defaultLocation;
    }

    return [
        'system_id' => strlen($systemId) <= 40 ? $systemId : $defaultLocation['system_id'],
        'system_name' => strlen($systemName) <= 80 ? $systemName : $defaultLocation['system_name'],
        'x' => (int)($location['x'] ?? $defaultLocation['x']),
        'y' => (int)($location['y'] ?? $defaultLocation['y']),
    ];
}

/**
 * @return array{system_id: string, system_name: string, x: int, y: int, resource: string, resource_label: string, assigned_at: string}|null
 */
function normalize_fleet_ship_route(mixed $route): ?array
{
    if (!is_array($route)) {
        return null;
    }

    $systemId = trim((string)($route['system_id'] ?? ''));
    $systemName = trim((string)($route['system_name'] ?? ''));
    $resource = trim((string)($route['resource'] ?? ''));
    $resourceLabel = trim((string)($route['resource_label'] ?? ''));
    $assignedAt = trim((string)($route['assigned_at'] ?? ''));

    if ($systemId === '' || $systemName === '' || $resource === '' || $resourceLabel === '') {
        return null;
    }

    if (strlen($systemId) > 40 || strlen($systemName) > 80 || strlen($resource) > 40 || strlen($resourceLabel) > 80) {
        return null;
    }

    return [
        'system_id' => $systemId,
        'system_name' => $systemName,
        'x' => (int)($route['x'] ?? 0),
        'y' => (int)($route['y'] ?? 0),
        'resource' => $resource,
        'resource_label' => $resourceLabel,
        'assigned_at' => $assignedAt !== '' && strlen($assignedAt) <= 40 ? $assignedAt : date(DATE_ATOM),
    ];
}

/**
 * @param array<string, mixed> $shipData
 */
function fleet_ship_data_needs_starter_update(array $shipData): bool
{
    $name = trim((string)($shipData['name'] ?? ''));
    $callsign = trim((string)($shipData['callsign'] ?? ''));
    $design = $shipData['design'] ?? [];
    $location = $shipData['location'] ?? null;

    if ($name === '' || strlen($name) > 80 || $callsign === '' || strlen($callsign) > 20) {
        return true;
    }

    if (trim((string)($shipData['class'] ?? '')) !== 'Newbie Escort Ship') {
        return true;
    }

    if (!is_array($design)) {
        return true;
    }

    if (!is_array($location)
        || trim((string)($location['system_id'] ?? '')) === ''
        || trim((string)($location['system_name'] ?? '')) === ''
        || !array_key_exists('x', $location)
        || !array_key_exists('y', $location)
    ) {
        return true;
    }

    return trim((string)($design['weapon'] ?? '')) !== 'Basic Laser'
        || trim((string)($design['mining'] ?? '')) !== 'Basic Mining Droid';
}

/**
 * @param array{id: int, name: string, class: string, callsign: string, location: array{system_id: string, system_name: string, x: int, y: int}, design: array{weapon: string, mining: string}, route: array{system_id: string, system_name: string, x: int, y: int, resource: string, resource_label: string, assigned_at: string}|null} $ship
 */
function insert_fleet_ship(int $userId, int $shipSlot, array $ship): bool
{
    global $DAL;

    $shipData = json_encode($ship, JSON_THROW_ON_ERROR);

    return $DAL->w(
        'INSERT INTO fleet_assignments (user_id, ship_slot, captain_id, ship_data, updated_at)
            VALUES (:user_id, :ship_slot, 0, :ship_data, NOW())
            ON DUPLICATE KEY UPDATE ship_data=ship_data',
        [
            ':user_id' => $userId,
            ':ship_slot' => $shipSlot,
            ':ship_data' => $shipData,
        ]
    );
}

/**
 * @param array{id: int, name: string, class: string, callsign: string, location: array{system_id: string, system_name: string, x: int, y: int}, design: array{weapon: string, mining: string}, route: array{system_id: string, system_name: string, x: int, y: int, resource: string, resource_label: string, assigned_at: string}|null} $ship
 */
function save_fleet_ship_data(int $userId, int $shipSlot, array $ship): bool
{
    global $DAL;

    $shipData = json_encode($ship, JSON_THROW_ON_ERROR);

    return $DAL->w(
        'UPDATE fleet_assignments SET ship_data=:ship_data, updated_at=NOW()
            WHERE user_id=:user_id AND ship_slot=:ship_slot',
        [
            ':user_id' => $userId,
            ':ship_slot' => $shipSlot,
            ':ship_data' => $shipData,
        ]
    );
}

/**
 * @param array<int, int> $assignments
 * @param array<int, array{id: int, name: string, class: string, callsign: string, location: array{system_id: string, system_name: string, x: int, y: int}, design: array{weapon: string, mining: string}, route: array{system_id: string, system_name: string, x: int, y: int, resource: string, resource_label: string, assigned_at: string}|null}>|null $ships
 */
function save_fleet_assignments(int $userId, array $assignments, ?array $ships = null): bool
{
    global $DAL;

    if ($userId <= 0) {
        return false;
    }

    $ships ??= fleet_ships();

    foreach ($ships as $shipSlot => $ship) {
        $captainId = $assignments[$shipSlot] ?? 0;
        $shipData = json_encode($ship, JSON_THROW_ON_ERROR);
        $saved = $DAL->w(
            'INSERT INTO fleet_assignments (user_id, ship_slot, captain_id, ship_data, updated_at)
                VALUES (:user_id, :ship_slot, :captain_id, :ship_data, NOW())
                ON DUPLICATE KEY UPDATE captain_id=VALUES(captain_id), ship_data=VALUES(ship_data), updated_at=VALUES(updated_at)',
            [
                ':user_id' => $userId,
                ':ship_slot' => $shipSlot,
                ':captain_id' => $captainId,
                ':ship_data' => $shipData,
            ]
        );

        if (!$saved) {
            return false;
        }
    }

    return true;
}

/**
 * @param array<int, array{id: int, name: string}> $captains
 * @return array<int, int>|null
 */
function posted_fleet_assignments(array $captains): ?array
{
    $postedAssignments = $_POST['captain'] ?? [];

    if (!is_array($postedAssignments)) {
        return null;
    }

    $assignments = default_fleet_assignments();
    $usedCaptains = [];

    foreach (fleet_ships() as $shipSlot => $ship) {
        $captainId = (int)($postedAssignments[$shipSlot] ?? 0);

        if (!is_valid_fleet_captain_id($captainId, $captains, true)) {
            return null;
        }

        if ($captainId > 0 && in_array($captainId, $usedCaptains, true)) {
            return null;
        }

        $assignments[$shipSlot] = $captainId;
        $usedCaptains[] = $captainId;
    }

    return $assignments;
}

/**
 * @param array<int, array{id: int, name: string}> $captains
 */
function is_valid_fleet_captain_id(int $captainId, array $captains, bool $allowUnassigned): bool
{
    if ($allowUnassigned && $captainId === 0) {
        return true;
    }

    return array_key_exists($captainId, $captains);
}

/**
 * @param array<int, array{id: int, name: string, class: string, callsign: string, location: array{system_id: string, system_name: string, x: int, y: int}, design: array{weapon: string, mining: string}, route: array{system_id: string, system_name: string, x: int, y: int, resource: string, resource_label: string, assigned_at: string}|null}> $ships
 * @param array<int, int> $assignments
 * @return array<int, array{id: int, name: string, class: string, callsign: string, location: array{system_id: string, system_name: string, x: int, y: int}, design: array{weapon: string, mining: string}, route: array{system_id: string, system_name: string, x: int, y: int, resource: string, resource_label: string, assigned_at: string}|null}>
 */
function captained_fleet_ships(array $ships, array $assignments): array
{
    $captainedShips = [];

    foreach ($ships as $shipSlot => $ship) {
        if ((int)($assignments[$shipSlot] ?? 0) <= 0) {
            continue;
        }

        $captainedShips[$shipSlot] = $ship;
    }

    return $captainedShips;
}

/**
 * @return array<string, array{id: string, name: string, x: int, y: int, resources: array<string, array{abundance: int, label: string, icon: string}>}>
 */
function fleet_route_systems(): array
{
    $highSecMap = load_starbase_highsec_map();
    $resources = is_array($highSecMap['resources'] ?? null) ? $highSecMap['resources'] : [];
    $systems = is_array($highSecMap['systems'] ?? null) ? $highSecMap['systems'] : [];
    $routeSystems = [];

    foreach ($systems as $system) {
        if (!is_array($system)) {
            continue;
        }

        $systemId = trim((string)($system['id'] ?? ''));
        $systemName = trim((string)($system['name'] ?? ''));
        $systemResources = is_array($system['resources'] ?? null) ? $system['resources'] : [];
        $normalizedResources = [];

        foreach ($systemResources as $resourceKey => $resourceData) {
            if (!is_string($resourceKey) || !is_array($resourceData) || !is_array($resources[$resourceKey] ?? null)) {
                continue;
            }

            $normalizedResources[$resourceKey] = [
                'abundance' => (int)($resourceData['abundance'] ?? 0),
                'label' => (string)($resources[$resourceKey]['label'] ?? $resourceKey),
                'icon' => (string)($resources[$resourceKey]['icon'] ?? 'inventory_2'),
            ];
        }

        if ($systemId === '' || $systemName === '' || $normalizedResources === []) {
            continue;
        }

        $routeSystems[$systemId] = [
            'id' => $systemId,
            'name' => $systemName,
            'x' => (int)($system['x'] ?? 0),
            'y' => (int)($system['y'] ?? 0),
            'resources' => $normalizedResources,
        ];
    }

    return $routeSystems;
}

/**
 * @param array<int, array{id: int, name: string, class: string, callsign: string, location: array{system_id: string, system_name: string, x: int, y: int}, design: array{weapon: string, mining: string}, route: array{system_id: string, system_name: string, x: int, y: int, resource: string, resource_label: string, assigned_at: string}|null}> $ships
 * @param array<int, int> $assignments
 * @param array<string, array{id: string, name: string, x: int, y: int, resources: array<string, array{abundance: int, label: string, icon: string}>}> $routeSystems
 * @return array{ship_slot: int, system_id: string, resource: string}|null
 */
function posted_fleet_route_assignment(array $ships, array $assignments, array $routeSystems): ?array
{
    $shipSlot = (int)($_POST['ship_slot'] ?? 0);
    $systemId = posted_string('system_id', 40);
    $resource = posted_string('resource', 40);

    if (!array_key_exists($shipSlot, $ships) || (int)($assignments[$shipSlot] ?? 0) <= 0) {
        return null;
    }

    if (!array_key_exists($systemId, $routeSystems)) {
        return null;
    }

    if (!array_key_exists($resource, $routeSystems[$systemId]['resources'])) {
        return null;
    }

    return [
        'ship_slot' => $shipSlot,
        'system_id' => $systemId,
        'resource' => $resource,
    ];
}

/**
 * @param array{id: int, name: string, class: string, callsign: string, location: array{system_id: string, system_name: string, x: int, y: int}, design: array{weapon: string, mining: string}, route: array{system_id: string, system_name: string, x: int, y: int, resource: string, resource_label: string, assigned_at: string}|null} $ship
 * @param array{id: string, name: string, x: int, y: int, resources: array<string, array{abundance: int, label: string, icon: string}>} $system
 * @return array{id: int, name: string, class: string, callsign: string, location: array{system_id: string, system_name: string, x: int, y: int}, design: array{weapon: string, mining: string}, route: array{system_id: string, system_name: string, x: int, y: int, resource: string, resource_label: string, assigned_at: string}|null}
 */
function fleet_ship_with_route(array $ship, array $system, string $resource): array
{
    $ship['route'] = [
        'system_id' => $system['id'],
        'system_name' => $system['name'],
        'x' => $system['x'],
        'y' => $system['y'],
        'resource' => $resource,
        'resource_label' => $system['resources'][$resource]['label'],
        'assigned_at' => date(DATE_ATOM),
    ];

    return $ship;
}
