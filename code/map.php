<?php

declare(strict_types=1);

require_once __DIR__ . '/../funcs/map_generation.php';

$highSecMapPath = __DIR__ . '/../data/highsec_map.php';
$highSecMap = file_exists($highSecMapPath) ? require $highSecMapPath : null;

if (!is_array($highSecMap)) {
    $highSecMap = generateHighSecMap();
    writeHighSecMapDataFile($highSecMapPath, $highSecMap);
}
