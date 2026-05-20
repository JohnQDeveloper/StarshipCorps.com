<?php

declare(strict_types=1);

$starbaseUserId = (int)($_SESSION['auth_user_id'] ?? 0);
$starbaseState = load_user_starbase($starbaseUserId);
$starbasePersistenceAvailable = $starbaseState['available'];
$starbase = $starbaseState['starbase'];
$starbaseModules = is_array($starbase) && is_array($starbase['modules'] ?? null) ? $starbase['modules'] : [];
$starbaseModuleId = '';
$starbaseSelectedModule = null;
$rawStarbaseUri = strtok($_SERVER['REQUEST_URI'], '?') ?: '/';
$starbaseRouteSegments = array_values(array_filter(explode('/', trim($rawStarbaseUri, '/'))));

if (isset($starbaseRouteSegments[1])) {
    $requestedModuleId = preg_replace('/[^a-z0-9_-]/', '', strtolower($starbaseRouteSegments[1])) ?? '';
    $starbaseModuleId = $requestedModuleId;
}

foreach ($starbaseModules as $module) {
    if (!is_array($module)) {
        continue;
    }

    $moduleId = (string)($module['id'] ?? '');

    if ($starbaseSelectedModule === null) {
        $starbaseSelectedModule = $module;
    }

    if ($moduleId !== '' && $moduleId === $starbaseModuleId) {
        $starbaseSelectedModule = $module;
        break;
    }
}
