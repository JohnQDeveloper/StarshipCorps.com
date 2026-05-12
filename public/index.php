<?php
    require_once('../config.php');

    $rawUri = strtok($_SERVER['REQUEST_URI'], '?');

    if ($auth->isLoggedIn()) {
        $currentAuthStatus = getAuthUserStatusById((int)($_SESSION['auth_user_id'] ?? 0));

        if ($currentAuthStatus !== null && isBlockedAuthStatus($currentAuthStatus)) {
            $auth->logOut();
            $blockedMessage = getBlockedAuthStatusMessage($currentAuthStatus);

            if (str_starts_with(ltrim($rawUri, '/'), 'api/')) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $blockedMessage]);
                exit;
            }

            $alert_danger = $blockedMessage;

            require_once('../templates/header.php');
            require_once('../pages/login.php');
            require_once('../templates/footer.php');
            exit;
        }
    }

    // Route API requests before any HTML output
    if (str_starts_with(ltrim($rawUri, '/'), 'api/')) {
        $endpoint = substr(ltrim($rawUri, '/'), 4); // strip 'api/'
        $apiFile  = __DIR__ . '/../api/' . basename($endpoint) . '.php';
        if (file_exists($apiFile)) {
            require_once $apiFile;
        } else {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Not found']);
        }
        exit;
    }

    require_once('../templates/header.php');

    // Sanitize user input
    $unsafe_main_page = strtok(strtok($_SERVER["REQUEST_URI"], '?'), '/');
    $unsafe_qs = $_SERVER['QUERY_STRING'];

    $unsafe_second_page = explode('/', strtok($_SERVER["REQUEST_URI"], '?'));
    $unsafe_second_page = $unsafe_second_page[2] ?? '';

    # find valid pages
    $pages = scandir("../pages");
    #print_r($_SESSION);die();

    $isAuthenticated = $isLoggedIn || $isGuest;
    $publicPages = ['login', 'register', 'index', 'verify-email', 'forgot-password', 'reset-password', 'guest'];
    $registeredOnlyPages = ['market', 'store'];

    if (in_array(ltrim(strtolower($unsafe_main_page).".php","/"), $pages)) {
        if(!$isAuthenticated && !in_array(ltrim(strtolower($unsafe_main_page),"/"), $publicPages)) {
            require_once("../pages/login.php");
        }
        elseif ($isGuest && in_array(ltrim(strtolower($unsafe_main_page), '/'), $registeredOnlyPages, true)) {
            $alert_danger = t('router.guest_feature');
            require_once("../pages/play-now.php");
        }
        else {
            if(file_exists("../code/" . ltrim($unsafe_main_page, "/") . ".php")) {
                require_once("../code/" . ltrim($unsafe_main_page, "/") . ".php");
            }
            if(file_exists("../pages/" . ltrim($unsafe_main_page, "/") . ".php")) {
                require_once("../pages/" . ltrim($unsafe_main_page, "/") . ".php");
            }
        }
    }
    else {
        if(!$isAuthenticated) {
            require_once("../pages/index.php");
        }
        else {
            require_once("../pages/play-now.php");
        }
    }


    require_once('../templates/footer.php');

    if($CharacterDataCache != $Character->Data) {
        $Character->Data['last_seen'] = date('Y-m-d H:i:s');
        $Character->SaveByUserId();
    }
    else {
        $Character->ActivityCheck();
    }
