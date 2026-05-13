<?php

declare(strict_types=1);

/**
 * @return array<int, array{id: int, name: string, class: string, callsign: string}>
 */
function fleet_ships(): array
{
    return [
        1 => ['id' => 1, 'name' => 'ISS Resolute', 'class' => 'Command Cruiser', 'callsign' => 'RSL-01'],
        2 => ['id' => 2, 'name' => 'ISS Meridian', 'class' => 'Survey Frigate', 'callsign' => 'MRD-02'],
        3 => ['id' => 3, 'name' => 'ISS Valiant', 'class' => 'Escort Corvette', 'callsign' => 'VLT-03'],
        4 => ['id' => 4, 'name' => 'ISS Horizon', 'class' => 'Logistics Carrier', 'callsign' => 'HRZ-04'],
    ];
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
 * @return array{available: bool, assignments: array<int, int>}
 */
function load_fleet_assignments(int $userId, array $captains): array
{
    global $DAL;

    $assignments = default_fleet_assignments();

    if ($userId <= 0) {
        return ['available' => false, 'assignments' => $assignments];
    }

    $rows = $DAL->r(
        'SELECT ship_slot, captain_id FROM fleet_assignments WHERE user_id=:user_id ORDER BY ship_slot',
        [':user_id' => $userId]
    );

    if ($rows === false) {
        return ['available' => false, 'assignments' => $assignments];
    }

    foreach ($rows as $row) {
        $shipSlot = (int)($row['ship_slot'] ?? 0);
        $captainId = (int)($row['captain_id'] ?? 0);

        if (array_key_exists($shipSlot, $assignments) && is_valid_fleet_captain_id($captainId, $captains, true)) {
            $assignments[$shipSlot] = $captainId;
        }
    }

    return ['available' => true, 'assignments' => $assignments];
}

/**
 * @param array<int, int> $assignments
 */
function save_fleet_assignments(int $userId, array $assignments): bool
{
    global $DAL;

    if ($userId <= 0) {
        return false;
    }

    foreach (fleet_ships() as $shipSlot => $ship) {
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
