<?php

declare(strict_types=1);

require_once __DIR__ . '/../funcs/map_generation.php';

$mapPath = __DIR__ . '/../data/highsec_map.php';
$map = generateHighSecMap();

if (!writeHighSecMapDataFile($mapPath, $map)) {
    fwrite(STDERR, "Unable to write HighSec map data.\n");
    exit(1);
}

echo "Generated HighSec map data at {$mapPath}\n";
