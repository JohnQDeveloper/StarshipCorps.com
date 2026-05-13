<?php declare(strict_types=1);

$fleetError = (string)($fleetError ?? '');
$fleetSuccess = (string)($fleetSuccess ?? '');
$fleetAssignments = is_array($fleetAssignments ?? null) ? $fleetAssignments : default_fleet_assignments();
$fleetPersistenceAvailable = (bool)($fleetPersistenceAvailable ?? false);
$captainPersistenceAvailable = (bool)($captainPersistenceAvailable ?? false);
$fleetShips = fleet_ships();
$fleetCaptains = is_array($fleetCaptains ?? null) ? $fleetCaptains : [];
$fleetFormsAvailable = $fleetPersistenceAvailable && $captainPersistenceAvailable;
$canCreateCaptain = $captainPersistenceAvailable && count($fleetCaptains) < 4;
?>

<section class="game-page">
  <div class="fleet-heading">
    <div>
      <p class="fleet-kicker"><?php echo e(t('fleet.kicker')); ?></p>
      <h1><?php echo e(t('game.nav.fleet')); ?></h1>
    </div>
  </div>

  <?php if ($fleetError !== '') { ?>
    <div class="snackbar error active"><?php echo e($fleetError); ?></div>
  <?php } ?>

  <?php if ($fleetSuccess !== '') { ?>
    <div class="snackbar active"><?php echo e($fleetSuccess); ?></div>
  <?php } ?>

  <?php if (!$fleetFormsAvailable) { ?>
    <div class="fleet-alert" role="status">
      <strong><?php echo e(t('fleet.setup_required_title')); ?></strong>
      <span><?php echo e(t('fleet.setup_required_body')); ?></span>
    </div>
  <?php } ?>

  <form method="post" action="/fleet" class="fleet-grid">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="fleet_action" value="save_assignments">

    <?php foreach ($fleetShips as $shipSlot => $ship) { ?>
      <?php $assignedCaptain = (int)($fleetAssignments[$shipSlot] ?? 0); ?>
      <article class="fleet-ship-card">
        <div class="fleet-ship-header">
          <div>
            <p><?php echo e($ship['callsign']); ?></p>
            <h2><?php echo e($ship['name']); ?></h2>
          </div>
          <i aria-hidden="true">rocket_launch</i>
        </div>

        <dl class="fleet-ship-specs">
          <div>
            <dt><?php echo e(t('fleet.ship_class')); ?></dt>
            <dd><?php echo e($ship['class']); ?></dd>
          </div>
        </dl>

        <div class="field border label fleet-captain-field">
          <select
            id="ship-<?php echo e($shipSlot); ?>-captain"
            name="captain[<?php echo e($shipSlot); ?>]"
            <?php echo !$fleetFormsAvailable ? 'disabled' : ''; ?>
          >
            <option value="0" <?php echo $assignedCaptain === 0 ? 'selected' : ''; ?>>
              <?php echo e(t('fleet.unassigned')); ?>
            </option>
            <?php foreach ($fleetCaptains as $captainId => $captain) { ?>
              <option value="<?php echo e($captainId); ?>" <?php echo $assignedCaptain === $captainId ? 'selected' : ''; ?>>
                <?php echo e($captain['name']); ?>
              </option>
            <?php } ?>
          </select>
          <label for="ship-<?php echo e($shipSlot); ?>-captain"><?php echo e(t('fleet.assign_captain')); ?></label>
        </div>
      </article>
    <?php } ?>

    <div class="fleet-actions">
      <button type="submit" class="responsive" <?php echo !$fleetFormsAvailable ? 'disabled' : ''; ?>>
        <?php echo e(t('fleet.save_assignments')); ?>
      </button>
    </div>
  </form>

  <section class="fleet-captain-create" aria-labelledby="create-captain-heading">
    <div>
      <p class="fleet-kicker"><?php echo e(t('fleet.captains_kicker')); ?></p>
      <h2 id="create-captain-heading"><?php echo e(t('fleet.create_captain')); ?></h2>
      <?php if ($fleetCaptains === []) { ?>
        <p><?php echo e(t('fleet.no_captains')); ?></p>
      <?php } elseif (!$canCreateCaptain) { ?>
        <p><?php echo e(t('fleet.captain_limit_reached')); ?></p>
      <?php } ?>
    </div>

    <form method="post" action="/fleet" class="fleet-captain-form">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="fleet_action" value="create_captain">

      <div class="field border label">
        <input
          id="captain-name"
          name="captain_name"
          type="text"
          maxlength="40"
          required
          <?php echo !$canCreateCaptain ? 'disabled' : ''; ?>
        >
        <label for="captain-name"><?php echo e(t('fleet.captain_name')); ?></label>
      </div>

      <button type="submit" class="responsive" <?php echo !$canCreateCaptain ? 'disabled' : ''; ?>>
        <?php echo e(t('fleet.create_captain_button')); ?>
      </button>
    </form>
  </section>
</section>
