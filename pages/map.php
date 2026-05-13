<?php

declare(strict_types=1);

/**
 * @var array{
 *   name: string,
 *   width: int,
 *   height: int,
 *   resources: array<string, array{label: string, icon: string}>,
 *   systems: array<int, array{id: string, name: string, x: int, y: int, tier: int, resources: array<string, array{abundance: int}>}>,
 *   connections: array<int, array{from: string, to: string}>
 * } $highSecMap
 */
$systemsByPosition = [];
$connectionKeys = [];

foreach ($highSecMap['systems'] as $system) {
    $systemsByPosition[$system['y']][$system['x']] = $system;
}

foreach ($highSecMap['connections'] as $connection) {
    $systemIds = [$connection['from'], $connection['to']];
    sort($systemIds);
    $connectionKeys[implode(':', $systemIds)] = true;
}

function hasHighSecConnection(array $connectionKeys, string $from, string $to): bool
{
    $systemIds = [$from, $to];
    sort($systemIds);

    return isset($connectionKeys[implode(':', $systemIds)]);
}
?>

<section class="game-page">
  <div class="map-heading">
    <div>
      <p class="map-kicker"><?php echo e(t('map.region_label')); ?></p>
      <h1><?php echo e($highSecMap['name']); ?></h1>
    </div>
    <span class="map-size-badge"><?php echo e((string)$highSecMap['width']); ?>x<?php echo e((string)$highSecMap['height']); ?></span>
  </div>

  <div class="highsec-map-wrap" aria-label="<?php echo e(t('map.grid_label')); ?>">
    <div class="highsec-map-grid">
      <?php for ($row = 0; $row < ($highSecMap['height'] * 2) - 1; $row++) { ?>
        <?php for ($column = 0; $column < ($highSecMap['width'] * 2) - 1; $column++) { ?>
          <?php if ($row % 2 === 0 && $column % 2 === 0) { ?>
            <?php $system = $systemsByPosition[(int)($row / 2)][(int)($column / 2)]; ?>
            <div
              class="system-node"
              style="grid-column: <?php echo e((string)($column + 1)); ?>; grid-row: <?php echo e((string)($row + 1)); ?>;"
              role="group"
              aria-label="<?php echo e($system['name']); ?>"
            >
              <div class="system-node-header">
                <span>
                  <strong><?php echo e($system['name']); ?></strong>
                  <small><?php echo e($system['id']); ?></small>
                </span>
                <span class="security-chip">T<?php echo e((string)$system['tier']); ?></span>
              </div>
              <div class="resource-list">
                <?php foreach ($system['resources'] as $resourceKey => $resource) { ?>
                  <?php $resourceMeta = $highSecMap['resources'][$resourceKey]; ?>
                  <span
                    class="resource-pill"
                    title="<?php echo e($resourceMeta['label']); ?>"
                    aria-label="<?php echo e($resourceMeta['label'] . ': ' . $resource['abundance'] . '%'); ?>"
                  >
                    <i aria-hidden="true"><?php echo e($resourceMeta['icon']); ?></i>
                    <span class="resource-percent"><?php echo e((string)$resource['abundance']); ?>%</span>
                  </span>
                <?php } ?>
              </div>
            </div>
          <?php } elseif ($row % 2 === 0) { ?>
            <?php
              $leftSystem = $systemsByPosition[(int)($row / 2)][(int)(($column - 1) / 2)];
              $rightSystem = $systemsByPosition[(int)($row / 2)][(int)(($column + 1) / 2)];
              $isConnected = hasHighSecConnection($connectionKeys, $leftSystem['id'], $rightSystem['id']);
            ?>
            <div
              class="map-connector horizontal"
              style="grid-column: <?php echo e((string)($column + 1)); ?>; grid-row: <?php echo e((string)($row + 1)); ?>;"
              aria-hidden="true"
            >
              <?php echo $isConnected ? ':' : ''; ?>
            </div>
          <?php } elseif ($column % 2 === 0) { ?>
            <?php
              $topSystem = $systemsByPosition[(int)(($row - 1) / 2)][(int)($column / 2)];
              $bottomSystem = $systemsByPosition[(int)(($row + 1) / 2)][(int)($column / 2)];
              $isConnected = hasHighSecConnection($connectionKeys, $topSystem['id'], $bottomSystem['id']);
            ?>
            <div
              class="map-connector vertical"
              style="grid-column: <?php echo e((string)($column + 1)); ?>; grid-row: <?php echo e((string)($row + 1)); ?>;"
              aria-hidden="true"
            >
              <?php echo $isConnected ? ':' : ''; ?>
            </div>
          <?php } else { ?>
            <div
              style="grid-column: <?php echo e((string)($column + 1)); ?>; grid-row: <?php echo e((string)($row + 1)); ?>;"
              aria-hidden="true"
            ></div>
          <?php } ?>
        <?php } ?>
      <?php } ?>
    </div>
  </div>
</section>
