<?php declare(strict_types=1);

$fleetError = (string)($fleetError ?? '');
$fleetSuccess = (string)($fleetSuccess ?? '');
$fleetAssignments = is_array($fleetAssignments ?? null) ? $fleetAssignments : default_fleet_assignments();
$fleetPersistenceAvailable = (bool)($fleetPersistenceAvailable ?? false);
$captainPersistenceAvailable = (bool)($captainPersistenceAvailable ?? false);
$fleetShips = is_array($fleetShips ?? null) ? $fleetShips : fleet_ships();
$fleetCaptains = is_array($fleetCaptains ?? null) ? $fleetCaptains : [];
$fleetRouteSystems = is_array($fleetRouteSystems ?? null) ? $fleetRouteSystems : fleet_route_systems();
$fleetRoutePage = (bool)($fleetRoutePage ?? false);
$fleetFormsAvailable = $fleetPersistenceAvailable && $captainPersistenceAvailable;
$canCreateCaptain = $captainPersistenceAvailable && count($fleetCaptains) < 4;
$captainedFleetShips = captained_fleet_ships($fleetShips, $fleetAssignments);
?>

<section class="game-page">
  <div class="fleet-heading">
    <div>
      <p class="fleet-kicker"><?php echo e(t('fleet.kicker')); ?></p>
      <h1><?php echo e(t('game.nav.fleet')); ?></h1>
    </div>
  </div>

  <div class="fleet-shell">
    <aside class="fleet-side-menu" aria-label="<?php echo e(t('fleet.menu_label')); ?>">
      <a
        href="/fleet"
        class="<?php echo !$fleetRoutePage ? 'active' : ''; ?>"
        <?php echo !$fleetRoutePage ? 'aria-current="page"' : ''; ?>
      >
        <i aria-hidden="true">badge</i>
        <span><?php echo e(t('fleet.menu_roster')); ?></span>
      </a>
      <a
        href="/fleet/assign-gathering"
        class="<?php echo $fleetRoutePage ? 'active' : ''; ?>"
        <?php echo $fleetRoutePage ? 'aria-current="page"' : ''; ?>
      >
        <i aria-hidden="true">route</i>
        <span><?php echo e(t('fleet.assign_route')); ?></span>
      </a>
    </aside>

    <div class="fleet-content">
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

      <?php if ($fleetRoutePage) { ?>
        <section class="fleet-route-page" aria-labelledby="fleet-route-heading">
          <div>
            <p class="fleet-kicker"><?php echo e(t('fleet.route_kicker')); ?></p>
            <h2 id="fleet-route-heading"><?php echo e(t('fleet.assign_route')); ?></h2>
          </div>

          <?php if ($captainedFleetShips === []) { ?>
            <div class="fleet-alert" role="status">
              <strong><?php echo e(t('fleet.route_no_ships_title')); ?></strong>
              <span><?php echo e(t('fleet.route_no_ships_body')); ?></span>
            </div>
          <?php } ?>

          <form method="post" action="/fleet/assign-gathering" class="fleet-route-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="fleet_action" value="assign_gathering">

            <div class="field border label">
              <select id="route-ship" name="ship_slot" <?php echo !$fleetFormsAvailable || $captainedFleetShips === [] ? 'disabled' : ''; ?>>
                <?php foreach ($captainedFleetShips as $shipSlot => $ship) { ?>
                  <?php
                    $captainId = (int)($fleetAssignments[$shipSlot] ?? 0);
                    $captainName = (string)($fleetCaptains[$captainId]['name'] ?? '');
                  ?>
                  <option value="<?php echo e($shipSlot); ?>">
                    <?php echo e($ship['name']); ?> - <?php echo e($captainName); ?>
                  </option>
                <?php } ?>
              </select>
              <label for="route-ship"><?php echo e(t('fleet.route_ship')); ?></label>
            </div>

            <div class="field border label">
              <select id="route-system" name="system_id" <?php echo !$fleetFormsAvailable || $captainedFleetShips === [] ? 'disabled' : ''; ?>>
                <?php foreach ($fleetRouteSystems as $systemId => $system) { ?>
                  <option value="<?php echo e($systemId); ?>">
                    <?php echo e($system['name']); ?> (<?php echo e($system['id']); ?>)
                  </option>
                <?php } ?>
              </select>
              <label for="route-system"><?php echo e(t('fleet.route_sector')); ?></label>
            </div>

            <button type="submit" class="responsive" <?php echo !$fleetFormsAvailable || $captainedFleetShips === [] ? 'disabled' : ''; ?>>
              <?php echo e(t('fleet.assign_route_button')); ?>
            </button>
          </form>

          <div class="fleet-route-list">
            <?php foreach ($captainedFleetShips as $shipSlot => $ship) { ?>
              <?php
                $assignedCaptain = (int)($fleetAssignments[$shipSlot] ?? 0);
                $captainName = (string)($fleetCaptains[$assignedCaptain]['name'] ?? '');
                $route = is_array($ship['route'] ?? null) ? $ship['route'] : null;
                $cargo = is_array($ship['cargo'] ?? null) ? $ship['cargo'] : [];
                $totalCargo = array_sum(array_map('intval', $cargo));
              ?>
              <article class="fleet-route-card">
                <div>
                  <p><?php echo e($ship['callsign']); ?> - <?php echo e($captainName); ?></p>
                  <h3><?php echo e($ship['name']); ?></h3>
                </div>
                <?php if ($route === null) { ?>
                  <span class="fleet-route-empty"><?php echo e(t('fleet.route_none')); ?></span>
                <?php } else { ?>
                  <dl>
                    <div>
                      <dt><?php echo e(t('fleet.route_sector')); ?></dt>
                      <dd>
                        <?php echo e((string)$route['system_name']); ?>
                        <?php echo e(t('starbase.coordinate_separator')); ?>
                        <?php echo e((string)$route['x']); ?>,
                        <?php echo e((string)$route['y']); ?>
                      </dd>
                    </div>
                    <div>
                      <dt><?php echo e(t('fleet.gathering_status')); ?></dt>
                      <dd>
                        <?php echo e((string)t((string)($route['state'] ?? '') === 'returning' ? 'fleet.gathering_returning' : 'fleet.gathering_active')); ?>
                      </dd>
                    </div>
                    <div>
                      <dt><?php echo e(t('fleet.cargo')); ?></dt>
                      <dd><?php echo e((string)$totalCargo); ?> / 10000</dd>
                    </div>
                  </dl>
                <?php } ?>
              </article>
            <?php } ?>
          </div>
        </section>
      <?php } else { ?>
        <form method="post" action="/fleet" class="fleet-grid">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="fleet_action" value="save_assignments">

          <?php foreach ($fleetShips as $shipSlot => $ship) { ?>
            <?php
              $assignedCaptain = (int)($fleetAssignments[$shipSlot] ?? 0);
              $shipLocation = is_array($ship['location'] ?? null) ? $ship['location'] : default_fleet_ship_location();
            ?>
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
                <div>
                  <dt><?php echo e(t('fleet.weapon')); ?></dt>
                  <dd><?php echo e($ship['design']['weapon']); ?></dd>
                </div>
                <div>
                  <dt><?php echo e(t('fleet.mining')); ?></dt>
                  <dd><?php echo e($ship['design']['mining']); ?></dd>
                </div>
                <div>
                  <dt><?php echo e(t('fleet.coordinates')); ?></dt>
                  <dd>
                    <?php echo e((string)($shipLocation['system_id'] ?? '')); ?>
                    <?php echo e(t('starbase.coordinate_separator')); ?>
                    <?php echo e((string)($shipLocation['x'] ?? 0)); ?>,
                    <?php echo e((string)($shipLocation['y'] ?? 0)); ?>
                  </dd>
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
      <?php } ?>
    </div>
  </div>

</section>
