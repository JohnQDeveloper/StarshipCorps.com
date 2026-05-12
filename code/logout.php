<?php

declare(strict_types=1);

global $auth;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth->logOut();
    redirect_to('/login');
}
