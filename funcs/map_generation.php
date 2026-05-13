<?php

declare(strict_types=1);

/**
 * @return array{
 *   name: string,
 *   security: string,
 *   width: int,
 *   height: int,
 *   generatedAt: string,
 *   resources: array<string, array{label: string, icon: string}>,
 *   systems: array<int, array{
 *     id: string,
 *     name: string,
 *     x: int,
 *     y: int,
 *     tier: int,
 *     resources: array<string, array{abundance: int}>
 *   }>,
 *   connections: array<int, array{from: string, to: string}>
 * }
 */
function generateHighSecMap(int $width = 5, int $height = 5): array
{
    $resourceTypes = [
        'metal' => ['label' => 'Metal', 'icon' => 'hardware'],
        'minerals' => ['label' => 'Minerals', 'icon' => 'diamond'],
        'liquids' => ['label' => 'Liquids', 'icon' => 'water_drop'],
        'chemicals' => ['label' => 'Chemicals', 'icon' => 'science'],
        'fuel' => ['label' => 'Fuel', 'icon' => 'local_gas_station'],
    ];
    $systemNames = [
        'Aster',
        'Beacon',
        'Cinder',
        'Dawn',
        'Ember',
        'Foundry',
        'Grove',
        'Haven',
        'Ion',
        'Junction',
        'Kepler',
        'Lumen',
        'Meridian',
        'Nova',
        'Orison',
        'Pioneer',
        'Quarry',
        'Relay',
        'Summit',
        'Talon',
        'Unity',
        'Vesta',
        'Warden',
        'Xenon',
        'Yield',
    ];

    $systems = [];
    $connections = [];
    $candidateConnections = [];

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $id = highSecSystemId($x, $y);
            $resources = [];
            $availableResourceTypes = array_keys($resourceTypes);
            shuffle($availableResourceTypes);
            $availableResourceTypes = array_slice($availableResourceTypes, 0, 3);

            foreach ($availableResourceTypes as $resourceType) {
                $resources[$resourceType] = [
                    'abundance' => random_int(0, 100),
                ];
            }

            $systems[] = [
                'id' => $id,
                'name' => $systemNames[($y * $width) + $x] ?? $id,
                'x' => $x,
                'y' => $y,
                'tier' => random_int(1, 3),
                'resources' => $resources,
            ];

            if ($x + 1 < $width) {
                $candidateConnections[] = ['from' => $id, 'to' => highSecSystemId($x + 1, $y)];
            }

            if ($y + 1 < $height) {
                $candidateConnections[] = ['from' => $id, 'to' => highSecSystemId($x, $y + 1)];
            }
        }
    }

    $connections = generateConnectedHighSecRoutes($systems, $candidateConnections);

    return [
        'name' => 'HighSec',
        'security' => 'HighSec',
        'width' => $width,
        'height' => $height,
        'generatedAt' => gmdate(DATE_ATOM),
        'resources' => $resourceTypes,
        'systems' => $systems,
        'connections' => $connections,
    ];
}

function writeHighSecMapDataFile(string $filePath, array $map): bool
{
    $contents = "<?php\n\n";
    $contents .= "declare(strict_types=1);\n\n";
    $contents .= 'return ' . var_export($map, true) . ";\n";

    return file_put_contents($filePath, $contents, LOCK_EX) !== false;
}

function highSecSystemId(int $x, int $y): string
{
    return 'HS-' . str_pad((string)($x + 1), 2, '0', STR_PAD_LEFT)
        . str_pad((string)($y + 1), 2, '0', STR_PAD_LEFT);
}

/**
 * @param array<int, array{id: string}> $systems
 * @param array<int, array{from: string, to: string}> $candidateConnections
 * @return array<int, array{from: string, to: string}>
 */
function generateConnectedHighSecRoutes(array $systems, array $candidateConnections): array
{
    $connectedSystemIds = [];
    $remainingSystemIds = [];
    $selectedConnections = [];

    foreach ($systems as $system) {
        $remainingSystemIds[$system['id']] = true;
    }

    $firstSystem = $systems[0]['id'] ?? null;

    if ($firstSystem === null) {
        return [];
    }

    $connectedSystemIds[$firstSystem] = true;
    unset($remainingSystemIds[$firstSystem]);

    while ($remainingSystemIds !== []) {
        $frontierConnections = array_values(array_filter(
            $candidateConnections,
            static function (array $connection) use ($connectedSystemIds, $remainingSystemIds): bool {
                return (isset($connectedSystemIds[$connection['from']]) && isset($remainingSystemIds[$connection['to']]))
                    || (isset($connectedSystemIds[$connection['to']]) && isset($remainingSystemIds[$connection['from']]));
            }
        ));

        if ($frontierConnections === []) {
            break;
        }

        $connection = $frontierConnections[array_rand($frontierConnections)];
        $selectedConnections[] = $connection;
        $connectedSystemIds[$connection['from']] = true;
        $connectedSystemIds[$connection['to']] = true;
        unset($remainingSystemIds[$connection['from']], $remainingSystemIds[$connection['to']]);
    }

    $selectedConnectionKeys = [];

    foreach ($selectedConnections as $connection) {
        $selectedConnectionKeys[highSecConnectionKey($connection['from'], $connection['to'])] = true;
    }

    $extraConnectionTarget = max(0, (int)floor(count($candidateConnections) * 0.25));
    $extraConnectionCount = random_int(0, $extraConnectionTarget);
    shuffle($candidateConnections);

    foreach ($candidateConnections as $connection) {
        if ($extraConnectionCount <= 0) {
            break;
        }

        $connectionKey = highSecConnectionKey($connection['from'], $connection['to']);

        if (isset($selectedConnectionKeys[$connectionKey])) {
            continue;
        }

        $selectedConnections[] = $connection;
        $selectedConnectionKeys[$connectionKey] = true;
        $extraConnectionCount--;
    }

    return $selectedConnections;
}

function highSecConnectionKey(string $from, string $to): string
{
    $systemIds = [$from, $to];
    sort($systemIds);

    return implode(':', $systemIds);
}
