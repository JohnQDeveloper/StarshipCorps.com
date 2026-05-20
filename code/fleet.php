<?php

declare(strict_types=1);

$fleetError = '';
$fleetSuccess = '';
$fleetUserId = (int)($_SESSION['auth_user_id'] ?? 0);

process_user_fleet_gathering($fleetUserId);

$starbaseState = load_user_starbase($fleetUserId);
$fleetStarbase = $starbaseState['starbase'];
$captainState = load_fleet_captains($fleetUserId);
$fleetCaptains = $captainState['captains'];
$fleetState = load_fleet_assignments($fleetUserId, $fleetCaptains, $fleetStarbase);
$captainPersistenceAvailable = $captainState['available'];
$fleetPersistenceAvailable = $fleetState['available'];
$fleetAssignments = $fleetState['assignments'];
$fleetShips = $fleetState['ships'];
$fleetRouteSystems = fleet_route_systems();
$fleetRoutePage = false;
$rawRoute = strtok($_SERVER['REQUEST_URI'], '?') ?: '/';
$routeParts = array_values(array_filter(explode('/', trim($rawRoute, '/'))));

if (($routeParts[1] ?? '') === 'assign-gathering') {
    $fleetRoutePage = true;
}

if (query_string('updated', 32) === 'fleet') {
    $fleetSuccess = t('fleet.saved');
}

if (query_string('updated', 32) === 'route') {
    $fleetSuccess = t('fleet.route_saved');
}

if (query_string('created', 32) === 'captain') {
    $fleetSuccess = t('fleet.captain_created');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$fleetAction = posted_string('fleet_action', 32);

if ($fleetAction === 'create_captain') {
    if (!$captainPersistenceAvailable) {
        $fleetError = t('fleet.captain_persistence_unavailable');
        return;
    }

    if (count($fleetCaptains) >= 4) {
        $fleetError = t('fleet.captain_limit_reached');
        return;
    }

    $captainName = posted_string('captain_name', 80);

    if (!is_valid_new_captain_name($captainName)) {
        $fleetError = t('fleet.invalid_captain_name');
        return;
    }

    if (!create_fleet_captain($fleetUserId, $captainName)) {
        $fleetError = t('fleet.captain_create_failed');
        return;
    }

    redirect_to('/fleet?created=captain');
}

$fleetRouteAssignment = null;

if ($fleetAction === 'assign_gathering') {
    if (!$fleetPersistenceAvailable || !$captainPersistenceAvailable) {
        $fleetError = t('fleet.persistence_unavailable');
        return;
    }

    $fleetRouteAssignment = posted_fleet_route_assignment($fleetShips, $fleetAssignments, $fleetRouteSystems);

    if ($fleetRouteAssignment === null) {
        $fleetError = t('fleet.route_invalid');
        return;
    }

    $shipSlot = $fleetRouteAssignment['ship_slot'];
    $system = $fleetRouteSystems[$fleetRouteAssignment['system_id']];
    $ship = fleet_ship_with_route($fleetShips[$shipSlot], $system);

    if (!save_fleet_ship_data($fleetUserId, $shipSlot, $ship)) {
        $fleetError = t('fleet.route_save_failed');
        return;
    }

    redirect_to('/fleet/assign-gathering?updated=route');
}

if (!$fleetPersistenceAvailable || !$captainPersistenceAvailable) {
    $fleetError = t('fleet.persistence_unavailable');
    return;
}

$postedAssignments = posted_fleet_assignments($fleetCaptains);

if ($postedAssignments === null) {
    $fleetError = t('fleet.invalid_assignment');
    return;
}

if (!save_fleet_assignments($fleetUserId, $postedAssignments, $fleetShips)) {
    $fleetError = t('fleet.save_failed');
    return;
}

redirect_to('/fleet?updated=fleet');
