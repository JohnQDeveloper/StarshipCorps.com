<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

$processedUsers = process_fleet_gathering_for_all_users();

echo 'Processed fleet gathering for ' . $processedUsers . " users.\n";
