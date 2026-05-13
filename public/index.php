<?php

declare(strict_types=1);

require_once('../config.php');

global $auth;

$rawUri = strtok($_SERVER['REQUEST_URI'], '?') ?: '/';
$route = trim($rawUri, '/');
$mainPage = $route === '' ? 'index' : strtolower(basename(explode('/', $route)[0]));

if (in_array($mainPage, ['crons', 'tools'], true)) {
    http_response_code(404);
    exit;
}

if ($auth->isLoggedIn()) {
    $currentAuthStatus = getAuthUserStatusById((int)($_SESSION['auth_user_id'] ?? 0));

    if ($currentAuthStatus !== null && isBlockedAuthStatus($currentAuthStatus)) {
        $auth->logOut();
        $blockedMessage = getBlockedAuthStatusMessage($currentAuthStatus);

        if (str_starts_with($route, 'api/')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $blockedMessage]);
            exit;
        }

        $alertDanger = $blockedMessage;
        $mainPage = 'login';
    }
}

if (str_starts_with($route, 'api/')) {
    $endpoint = substr($route, 4);
    $apiFile = __DIR__ . '/../api/' . basename($endpoint) . '.php';

    if (file_exists($apiFile)) {
        require_once $apiFile;
    } else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Not found']);
    }

    exit;
}

$publicPages = [
    'login',
    'register',
    'index',
    'verify-email',
    'resend-verification',
    'forgot-password',
    'reset-password',
];

$gamePages = [
    'account',
    'dashboard',
    'missions',
    'fleet',
    'market',
    'starbase',
    'comms',
    'map',
    'markets',
    'settings',
];

$pageFile = __DIR__ . '/../pages/' . $mainPage . '.php';
$codeFile = __DIR__ . '/../code/' . $mainPage . '.php';

if (!file_exists($pageFile)) {
    $mainPage = $auth->isLoggedIn() ? 'account' : 'index';
    $pageFile = __DIR__ . '/../pages/' . $mainPage . '.php';
    $codeFile = __DIR__ . '/../code/' . $mainPage . '.php';
}

if (!$auth->isLoggedIn() && !in_array($mainPage, $publicPages, true)) {
    $mainPage = 'login';
    $pageFile = __DIR__ . '/../pages/login.php';
    $codeFile = __DIR__ . '/../code/login.php';
}

if (file_exists($codeFile)) {
    require_once $codeFile;
}

if (!$auth->isLoggedIn() && in_array($mainPage, $publicPages, true)) {
    require_once('../templates/header.php');
    require_once $pageFile;
    require_once('../templates/footer.php');
    exit;
} elseif ($auth->isLoggedIn() && in_array($mainPage, $gamePages, true)) {
    require_once('../templates/game-header.php');
    require_once $pageFile;
    require_once('../templates/footer.php');
    exit;
}
