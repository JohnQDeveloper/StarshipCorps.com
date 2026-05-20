<?php

declare(strict_types=1);

/**
 * @var bool $starbasePersistenceAvailable
 * @var array<string, mixed>|null $starbase
 * @var array<int, mixed> $starbaseModules
 * @var array<string, mixed>|null $starbaseSelectedModule
 */
$starbaseSystem = is_array($starbase) && is_array($starbase['system'] ?? null) ? $starbase['system'] : [];
?>

<section class="game-page">
  <?php if (!$starbasePersistenceAvailable) { ?>
    <article class="fleet-setup-panel">
      <h2><?php echo e(t('starbase.setup_required_title')); ?></h2>
      <p><?php echo e(t('starbase.setup_required_body')); ?></p>
    </article>
  <?php } elseif (is_array($starbase)) { ?>
    <div class="starbase-layout">
      <article class="starbase-location-panel">
        <span class="starbase-location-icon" aria-hidden="true">
          <i>domain</i>
        </span>
        <div>
          <p><?php echo e(t('starbase.location')); ?></p>
          <h2><?php echo e((string)($starbaseSystem['name'] ?? 'HighSec')); ?></h2>
          <span>
            <?php echo e((string)($starbaseSystem['id'] ?? '')); ?>
            <?php echo e(t('starbase.coordinate_separator')); ?>
            <?php echo e((string)($starbaseSystem['x'] ?? 0)); ?>,
            <?php echo e((string)($starbaseSystem['y'] ?? 0)); ?>
          </span>
        </div>
      </article>

      <div class="starbase-module-shell">
        <aside class="starbase-module-sidebar" aria-label="<?php echo e(t('starbase.modules')); ?>">
          <?php foreach ($starbaseModules as $module) { ?>
            <?php
              if (!is_array($module)) {
                  continue;
              }

              $moduleId = (string)($module['id'] ?? '');
              $moduleName = (string)($module['name'] ?? '');
              $isSelected = is_array($starbaseSelectedModule)
                  && (string)($starbaseSelectedModule['id'] ?? '') === $moduleId;

              if ($moduleId === '' || $moduleName === '') {
                  continue;
              }
            ?>
            <a
              class="starbase-module-link <?php echo $isSelected ? 'active' : ''; ?>"
              href="/starbase/<?php echo e(rawurlencode($moduleId)); ?>"
              <?php echo $isSelected ? 'aria-current="page"' : ''; ?>
            >
              <span><?php echo e($moduleName); ?></span>
              <small><?php echo e(t('starbase.tier')); ?> <?php echo e((string)($module['tier'] ?? 1)); ?></small>
            </a>
          <?php } ?>
        </aside>

        <section class="starbase-module-page">
          <h1><?php echo e((string)($starbaseSelectedModule['name'] ?? t('game.nav.starbase'))); ?></h1>
        </section>
      </div>
    </div>
  <?php } ?>
</section>
