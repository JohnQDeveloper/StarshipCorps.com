<?php

declare(strict_types=1);

const FLEET_BASIC_MINING_DROID_HOURLY_RATE = 1000;
const FLEET_SHIP_CARGO_CAPACITY = 10000;
const FLEET_TRAVEL_SECONDS_PER_SECTOR = 600;

/**
 * @return array<int, array{id: int, name: string, class: string, callsign: string, location: array{system_id: string, system_name: string, x: int, y: int}, design: array{weapon: string, mining: string}, route: array<string, mixed>|null, cargo: array<string, int>, cargo_remainders: array<string, float>}>
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
 * @return array{id: int, name: string, class: string, callsign: string, location: array{system_id: string, system_name: string, x: int, y: int}, design: array{weapon: string, mining: string}, route: array<string, mixed>|null, cargo: array<string, int>, cargo_remainders: array<string, float>}
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
        'cargo' => [],
        'cargo_remainders' => [],
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
 * @return array{available: bool, assignments: array<int, int>, ships: array<int, array<string, mixed>>}
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
 * @return array{id: int, name: string, class: string, callsign: string, location: array{system_id: string, system_name: string, x: int, y: int}, design: array{weapon: string, mining: string}, route: array<string, mixed>|null, cargo: array<string, int>, cargo_remainders: array<string, float>}
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
    $ship['cargo'] = normalize_fleet_ship_cargo($shipData['cargo'] ?? []);
    $ship['cargo_remainders'] = normalize_fleet_ship_cargo_remainders($shipData['cargo_remainders'] ?? []);

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
 * @return array<string, mixed>|null
 */
function normalize_fleet_ship_route(mixed $route): ?array
{
    if (!is_array($route)) {
        return null;
    }

    $systemId = trim((string)($route['system_id'] ?? ''));
    $systemName = trim((string)($route['system_name'] ?? ''));
    $assignedAt = trim((string)($route['assigned_at'] ?? ''));
    $state = trim((string)($route['state'] ?? 'gathering'));
    $lastProcessedAt = max(0, (int)($route['last_processed_at'] ?? time()));
    $travelRemainingSeconds = max(0, (int)($route['travel_remaining_seconds'] ?? 0));

    if ($systemId === '' || $systemName === '') {
        return null;
    }

    if (strlen($systemId) > 40 || strlen($systemName) > 80) {
        return null;
    }

    if (!in_array($state, ['gathering', 'returning'], true)) {
        $state = 'gathering';
    }

    return [
        'system_id' => $systemId,
        'system_name' => $systemName,
        'x' => (int)($route['x'] ?? 0),
        'y' => (int)($route['y'] ?? 0),
        'assigned_at' => $assignedAt !== '' && strlen($assignedAt) <= 40 ? $assignedAt : date(DATE_ATOM),
        'state' => $state,
        'last_processed_at' => $lastProcessedAt,
        'travel_remaining_seconds' => $travelRemainingSeconds,
    ];
}

/**
 * @return array<string, int>
 */
function normalize_fleet_ship_cargo(mixed $cargo): array
{
    if (!is_array($cargo)) {
        return [];
    }

    $normalizedCargo = [];

    foreach ($cargo as $resourceType => $amount) {
        if (!is_string($resourceType) || strlen($resourceType) > 40) {
            continue;
        }

        $normalizedCargo[$resourceType] = max(0, (int)$amount);
    }

    return $normalizedCargo;
}

/**
 * @return array<string, float>
 */
function normalize_fleet_ship_cargo_remainders(mixed $cargoRemainders): array
{
    if (!is_array($cargoRemainders)) {
        return [];
    }

    $normalizedRemainders = [];

    foreach ($cargoRemainders as $resourceType => $amount) {
        if (!is_string($resourceType) || strlen($resourceType) > 40) {
            continue;
        }

        $normalizedRemainders[$resourceType] = max(0.0, (float)$amount);
    }

    return $normalizedRemainders;
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
 * @param array<string, mixed> $ship
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
 * @param array<string, mixed> $ship
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
 * @param array<int, array<string, mixed>>|null $ships
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
 * @param array<int, array<string, mixed>> $ships
 * @param array<int, int> $assignments
 * @return array<int, array<string, mixed>>
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
 * @param array<int, array<string, mixed>> $ships
 * @param array<int, int> $assignments
 * @param array<string, array{id: string, name: string, x: int, y: int, resources: array<string, array{abundance: int, label: string, icon: string}>}> $routeSystems
 * @return array{ship_slot: int, system_id: string}|null
 */
function posted_fleet_route_assignment(array $ships, array $assignments, array $routeSystems): ?array
{
    $shipSlot = (int)($_POST['ship_slot'] ?? 0);
    $systemId = posted_string('system_id', 40);

    if (!array_key_exists($shipSlot, $ships) || (int)($assignments[$shipSlot] ?? 0) <= 0) {
        return null;
    }

    if (!array_key_exists($systemId, $routeSystems)) {
        return null;
    }

    return [
        'ship_slot' => $shipSlot,
        'system_id' => $systemId,
    ];
}

/**
 * @param array<string, mixed> $ship
 * @param array{id: string, name: string, x: int, y: int, resources: array<string, array{abundance: int, label: string, icon: string}>} $system
 * @return array<string, mixed>
 */
function fleet_ship_with_route(array $ship, array $system): array
{
    $ship['route'] = [
        'system_id' => $system['id'],
        'system_name' => $system['name'],
        'x' => $system['x'],
        'y' => $system['y'],
        'assigned_at' => date(DATE_ATOM),
        'state' => 'gathering',
        'last_processed_at' => time(),
        'travel_remaining_seconds' => 0,
    ];
    $ship['cargo'] = [];
    $ship['cargo_remainders'] = [];

    return $ship;
}

function process_user_fleet_gathering(int $userId): bool
{
    if ($userId <= 0) {
        return false;
    }

    $starbaseState = load_user_starbase($userId);

    if (!$starbaseState['available'] || !is_array($starbaseState['starbase'])) {
        return false;
    }

    $captainState = load_fleet_captains($userId);
    $fleetState = load_fleet_assignments($userId, $captainState['captains'], $starbaseState['starbase']);

    if (!$captainState['available'] || !$fleetState['available']) {
        return false;
    }

    $starbase = $starbaseState['starbase'];
    $routeSystems = fleet_route_systems();
    $ships = $fleetState['ships'];
    $assignments = $fleetState['assignments'];
    $now = time();
    $savedAllShips = true;
    $starbaseChanged = false;

    foreach ($ships as $shipSlot => $ship) {
        if ((int)($assignments[$shipSlot] ?? 0) <= 0) {
            continue;
        }

        $processed = process_fleet_ship_gathering($ship, $starbase, $routeSystems, $now);

        if (!$processed['changed']) {
            continue;
        }

        $ships[$shipSlot] = $processed['ship'];
        $starbase = $processed['starbase'];
        $starbaseChanged = $starbaseChanged || $processed['starbase_changed'];

        if (!save_fleet_ship_data($userId, (int)$shipSlot, $processed['ship'])) {
            $savedAllShips = false;
        }
    }

    if ($starbaseChanged && !save_user_starbase_by_user_id($userId, $starbase)) {
        return false;
    }

    return $savedAllShips;
}

/**
 * @param array<string, mixed> $ship
 * @param array<string, mixed> $starbase
 * @param array<string, array{id: string, name: string, x: int, y: int, resources: array<string, array{abundance: int, label: string, icon: string}>}> $routeSystems
 * @return array{ship: array<string, mixed>, starbase: array<string, mixed>, changed: bool, starbase_changed: bool}
 */
function process_fleet_ship_gathering(array $ship, array $starbase, array $routeSystems, int $now): array
{
    $route = is_array($ship['route'] ?? null) ? normalize_fleet_ship_route($ship['route']) : null;

    if ($route === null || trim((string)($ship['design']['mining'] ?? '')) !== 'Basic Mining Droid') {
        return ['ship' => $ship, 'starbase' => $starbase, 'changed' => false, 'starbase_changed' => false];
    }

    $systemId = (string)$route['system_id'];

    if (!isset($routeSystems[$systemId])) {
        return ['ship' => $ship, 'starbase' => $starbase, 'changed' => false, 'starbase_changed' => false];
    }

    $lastProcessedAt = max(0, (int)($route['last_processed_at'] ?? $now));
    $elapsedSeconds = max(0, $now - $lastProcessedAt);

    if ($elapsedSeconds === 0) {
        return ['ship' => $ship, 'starbase' => $starbase, 'changed' => false, 'starbase_changed' => false];
    }

    $ship['cargo'] = normalize_fleet_ship_cargo($ship['cargo'] ?? []);
    $ship['cargo_remainders'] = normalize_fleet_ship_cargo_remainders($ship['cargo_remainders'] ?? []);
    $starbase['resource_storage'] = normalize_starbase_resource_storage($starbase['resource_storage'] ?? []);

    $changed = false;
    $starbaseChanged = false;
    $remainingSeconds = $elapsedSeconds;
    $guard = 0;

    while ($remainingSeconds > 0 && $guard < 20) {
        $guard++;
        $route['state'] = (string)($route['state'] ?? 'gathering');

        if ($route['state'] === 'returning') {
            $travelRemainingSeconds = max(0, (int)($route['travel_remaining_seconds'] ?? 0));
            $travelSeconds = min($remainingSeconds, $travelRemainingSeconds);
            $remainingSeconds -= $travelSeconds;
            $travelRemainingSeconds -= $travelSeconds;
            $route['travel_remaining_seconds'] = $travelRemainingSeconds;
            $changed = true;

            if ($travelRemainingSeconds > 0) {
                break;
            }

            $unloaded = unload_fleet_ship_cargo_to_starbase($ship, $starbase);
            $ship = $unloaded['ship'];
            $starbase = $unloaded['starbase'];
            $starbaseChanged = $starbaseChanged || $unloaded['starbase_changed'];
            $route['state'] = 'gathering';
            continue;
        }

        $totalCargo = fleet_ship_total_cargo($ship);

        if ($totalCargo >= FLEET_SHIP_CARGO_CAPACITY) {
            $route = fleet_ship_route_returning_to_starbase($route, $starbase);
            $changed = true;
            continue;
        }

        $processedGathering = gather_fleet_ship_resources($ship, $routeSystems[$systemId], $remainingSeconds);
        $ship = $processedGathering['ship'];
        $remainingSeconds = $processedGathering['remaining_seconds'];
        $changed = $changed || $processedGathering['changed'];

        if (fleet_ship_total_cargo($ship) >= FLEET_SHIP_CARGO_CAPACITY) {
            $route = fleet_ship_route_returning_to_starbase($route, $starbase);
            $changed = true;
            continue;
        }

        break;
    }

    $route['last_processed_at'] = $now - $remainingSeconds;
    $ship['route'] = $route;

    return [
        'ship' => $ship,
        'starbase' => $starbase,
        'changed' => $changed,
        'starbase_changed' => $starbaseChanged,
    ];
}

/**
 * @param array<string, mixed> $ship
 */
function fleet_ship_total_cargo(array $ship): int
{
    $cargo = normalize_fleet_ship_cargo($ship['cargo'] ?? []);

    return array_sum($cargo);
}

/**
 * @param array<string, mixed> $route
 * @param array<string, mixed> $starbase
 * @return array<string, mixed>
 */
function fleet_ship_route_returning_to_starbase(array $route, array $starbase): array
{
    $starbaseSystem = is_array($starbase['system'] ?? null) ? $starbase['system'] : starter_starbase_system();
    $distance = abs((int)$route['x'] - (int)($starbaseSystem['x'] ?? 0))
        + abs((int)$route['y'] - (int)($starbaseSystem['y'] ?? 0));

    $route['state'] = 'returning';
    $route['travel_remaining_seconds'] = $distance * FLEET_TRAVEL_SECONDS_PER_SECTOR;

    return $route;
}

/**
 * @param array<string, mixed> $ship
 * @param array{id: string, name: string, x: int, y: int, resources: array<string, array{abundance: int, label: string, icon: string}>} $system
 * @return array{ship: array<string, mixed>, remaining_seconds: int, changed: bool}
 */
function gather_fleet_ship_resources(array $ship, array $system, int $elapsedSeconds): array
{
    $cargo = normalize_fleet_ship_cargo($ship['cargo'] ?? []);
    $remainders = normalize_fleet_ship_cargo_remainders($ship['cargo_remainders'] ?? []);
    $capacityLeft = FLEET_SHIP_CARGO_CAPACITY - array_sum($cargo);

    if ($elapsedSeconds <= 0 || $capacityLeft <= 0) {
        return ['ship' => $ship, 'remaining_seconds' => $elapsedSeconds, 'changed' => false];
    }

    $ratesPerSecond = [];
    $totalRatePerSecond = 0.0;

    foreach ($system['resources'] as $resourceType => $resource) {
        $ratePerSecond = (FLEET_BASIC_MINING_DROID_HOURLY_RATE * ((int)$resource['abundance'] / 100)) / 3600;

        if ($ratePerSecond <= 0) {
            continue;
        }

        $ratesPerSecond[$resourceType] = $ratePerSecond;
        $totalRatePerSecond += $ratePerSecond;
    }

    if ($ratesPerSecond === [] || $totalRatePerSecond <= 0.0) {
        return ['ship' => $ship, 'remaining_seconds' => 0, 'changed' => false];
    }

    $secondsToFill = (int)ceil($capacityLeft / $totalRatePerSecond);
    $gatherSeconds = min($elapsedSeconds, max(1, $secondsToFill));
    $changed = false;

    foreach ($ratesPerSecond as $resourceType => $ratePerSecond) {
        $generated = ($ratePerSecond * $gatherSeconds) + ($remainders[$resourceType] ?? 0.0);
        $wholeUnits = (int)floor($generated);
        $remainders[$resourceType] = $generated - $wholeUnits;

        if ($wholeUnits <= 0) {
            continue;
        }

        $cargo[$resourceType] = ($cargo[$resourceType] ?? 0) + $wholeUnits;
        $changed = true;
    }

    if (array_sum($cargo) > FLEET_SHIP_CARGO_CAPACITY) {
        $cargo = clamp_fleet_ship_cargo($cargo, FLEET_SHIP_CARGO_CAPACITY);
    }

    if ($elapsedSeconds >= $secondsToFill && array_sum($cargo) < FLEET_SHIP_CARGO_CAPACITY) {
        $highestRateResource = array_key_first($ratesPerSecond);
        $cargo[$highestRateResource] = ($cargo[$highestRateResource] ?? 0)
            + (FLEET_SHIP_CARGO_CAPACITY - array_sum($cargo));
        $changed = true;
    }

    $ship['cargo'] = $cargo;
    $ship['cargo_remainders'] = $remainders;

    return [
        'ship' => $ship,
        'remaining_seconds' => max(0, $elapsedSeconds - $gatherSeconds),
        'changed' => $changed,
    ];
}

/**
 * @param array<string, int> $cargo
 * @return array<string, int>
 */
function clamp_fleet_ship_cargo(array $cargo, int $capacity): array
{
    $excess = array_sum($cargo) - $capacity;

    if ($excess <= 0) {
        return $cargo;
    }

    foreach (array_reverse(array_keys($cargo)) as $resourceType) {
        $reduction = min($cargo[$resourceType], $excess);
        $cargo[$resourceType] -= $reduction;
        $excess -= $reduction;

        if ($cargo[$resourceType] <= 0) {
            unset($cargo[$resourceType]);
        }

        if ($excess <= 0) {
            break;
        }
    }

    return $cargo;
}

/**
 * @param array<string, mixed> $ship
 * @param array<string, mixed> $starbase
 * @return array{ship: array<string, mixed>, starbase: array<string, mixed>, starbase_changed: bool}
 */
function unload_fleet_ship_cargo_to_starbase(array $ship, array $starbase): array
{
    $cargo = normalize_fleet_ship_cargo($ship['cargo'] ?? []);

    if ($cargo === []) {
        $ship['cargo'] = [];
        $ship['cargo_remainders'] = [];

        return ['ship' => $ship, 'starbase' => $starbase, 'starbase_changed' => false];
    }

    $storage = normalize_starbase_resource_storage($starbase['resource_storage'] ?? []);

    foreach ($cargo as $resourceType => $amount) {
        $storage[$resourceType] = ($storage[$resourceType] ?? 0) + $amount;
    }

    $starbase['resource_storage'] = $storage;
    $ship['cargo'] = [];
    $ship['cargo_remainders'] = [];

    return ['ship' => $ship, 'starbase' => $starbase, 'starbase_changed' => true];
}

function process_fleet_gathering_for_all_users(): int
{
    global $DAL;

    $rows = $DAL->r('SELECT DISTINCT user_id FROM fleet_assignments ORDER BY user_id');

    if ($rows === false) {
        return 0;
    }

    $processedUsers = 0;

    foreach ($rows as $row) {
        if (process_user_fleet_gathering((int)($row['user_id'] ?? 0))) {
            $processedUsers++;
        }
    }

    return $processedUsers;
}
