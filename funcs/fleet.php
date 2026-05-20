<?php

declare(strict_types=1);

/**
 * @return array<int, array{id: int, name: string, class: string, callsign: string, design: array{weapon: string, mining: string}}>
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
 * @return array{id: int, name: string, class: string, callsign: string, design: array{weapon: string, mining: string}}
 */
function generated_fleet_ship(int $shipSlot, array $usedNames): array
{
    $name = random_fleet_ship_name($usedNames);

    return [
        'id' => $shipSlot,
        'name' => $name,
        'class' => 'Newbie Escort Ship',
        'callsign' => fleet_ship_callsign($name, $shipSlot),
        'design' => [
            'weapon' => 'Basic Laser',
            'mining' => 'Basic Mining Droid',
        ],
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
 * @return array{available: bool, assignments: array<int, int>, ships: array<int, array{id: int, name: string, class: string, callsign: string, design: array{weapon: string, mining: string}}>}
 */
function load_fleet_assignments(int $userId, array $captains): array
{
    global $DAL;

    $assignments = default_fleet_assignments();
    $ships = fleet_ships();

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
            $ships[$shipSlot] = normalize_fleet_ship($shipSlot, $shipData);
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

        $ships[$shipSlot] = generated_fleet_ship($shipSlot, $usedNames);
        $usedNames[] = $ships[$shipSlot]['name'];

        if (!insert_fleet_ship($userId, $shipSlot, $ships[$shipSlot])) {
            return ['available' => false, 'assignments' => $assignments, 'ships' => $ships];
        }
    }

    return ['available' => true, 'assignments' => $assignments, 'ships' => $ships];
}

/**
 * @param array<string, mixed> $shipData
 * @return array{id: int, name: string, class: string, callsign: string, design: array{weapon: string, mining: string}}
 */
function normalize_fleet_ship(int $shipSlot, array $shipData): array
{
    $ship = generated_fleet_ship($shipSlot, []);
    $name = trim((string)($shipData['name'] ?? ''));
    $callsign = trim((string)($shipData['callsign'] ?? ''));

    if ($name !== '' && strlen($name) <= 80) {
        $ship['name'] = $name;
    }

    if ($callsign !== '' && strlen($callsign) <= 20) {
        $ship['callsign'] = $callsign;
    }

    return $ship;
}

/**
 * @param array<string, mixed> $shipData
 */
function fleet_ship_data_needs_starter_update(array $shipData): bool
{
    $name = trim((string)($shipData['name'] ?? ''));
    $callsign = trim((string)($shipData['callsign'] ?? ''));
    $design = $shipData['design'] ?? [];

    if ($name === '' || strlen($name) > 80 || $callsign === '' || strlen($callsign) > 20) {
        return true;
    }

    if (trim((string)($shipData['class'] ?? '')) !== 'Newbie Escort Ship') {
        return true;
    }

    if (!is_array($design)) {
        return true;
    }

    return trim((string)($design['weapon'] ?? '')) !== 'Basic Laser'
        || trim((string)($design['mining'] ?? '')) !== 'Basic Mining Droid';
}

/**
 * @param array{id: int, name: string, class: string, callsign: string, design: array{weapon: string, mining: string}} $ship
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
 * @param array{id: int, name: string, class: string, callsign: string, design: array{weapon: string, mining: string}} $ship
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
 * @param array<int, array{id: int, name: string, class: string, callsign: string, design: array{weapon: string, mining: string}}>|null $ships
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
