<?php
declare(strict_types=1);

global $auth;

if (!$auth->isLoggedIn()) {
    header('Location: /login');
    exit;
}

$rawUri = strtok($_SERVER['REQUEST_URI'], '?') ?: '/';
$route = trim($rawUri, '/');
$currentPage = strtolower(basename(explode('/', $route)[0]));
$gameNavigationItems = [
    ['href' => '/dashboard', 'label' => t('game.nav.dashboard'), 'icon' => 'dashboard', 'symbol' => 'D'],
    ['href' => '/fleet', 'label' => t('game.nav.fleet'), 'icon' => 'rocket_launch', 'symbol' => 'F'],
    ['href' => '/starbase', 'label' => t('game.nav.starbase'), 'icon' => 'domain', 'symbol' => 'S'],
    ['href' => '/comms', 'label' => t('game.nav.comms'), 'icon' => 'forum', 'symbol' => 'C'],
    ['href' => '/map', 'label' => t('game.nav.map'), 'icon' => 'map', 'symbol' => 'M'],
    ['href' => '/markets', 'label' => t('game.nav.markets'), 'icon' => 'storefront', 'symbol' => '$'],
];
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars(get_language(), ENT_QUOTES, 'UTF-8'); ?>" style="background-color: #141316; color: #e6e1e6;">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <style>
      :root,
      html,
      body {
        --primary: #ffb95c;
        --on-primary: #432b00;
        --primary-container: #643f00;
        --on-primary-container: #ffddb4;
        --secondary: #dcc3a2;
        --on-secondary: #3f2d16;
        --secondary-container: #58442a;
        --on-secondary-container: #f9dfbd;
        --surface: #141316;
        --on-surface: #e6e1e6;
        --surface-container: #201f22;
        --surface-container-high: #2b292d;
        --surface-container-highest: #363438;
        --outline-variant: #51453a;
        background-color: #141316;
        color: #e6e1e6;
      }

      .game-nav-tabs,
      .game-nav-tabs > a,
      .desktop-account-link,
      .desktop-logout-form > button {
        background-color: #141316;
        color: #e6e1e6;
      }

      .game-nav-tabs > a.active {
        color: #ffb95c;
      }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/beercss@4.0.21/dist/cdn/beer.min.css" rel="stylesheet">
    <script type="module" src="https://cdn.jsdelivr.net/npm/beercss@4.0.21/dist/cdn/beer.min.js"></script>
    <script type="module" src="https://cdn.jsdelivr.net/npm/material-dynamic-colors@1.1.4/dist/cdn/material-dynamic-colors.min.js"></script>
    <style>
      .game-nav-tabs,
      .desktop-account-link,
      .desktop-logout-form {
        display: none;
      }

      .mobile-game-menu-content {
        display: none;
      }
    </style>
    <link rel="stylesheet" href="/css/custom.css">
    <title><?php echo t('index.title'); ?> :: <?php echo defined('ENVIRONMENT') ? ENVIRONMENT : 'Dev'; ?></title>
  </head>
  <body class="dark game-theme-orange" style="background-color: #141316; color: #e6e1e6;">
    <header class="responsive">
      <nav class="game-top-nav">
        <a class="brand" href="/">
          <span>Starship Corps</span>
        </a>
        <div class="max"></div>
        <button
          type="button"
          class="mobile-menu-trigger"
          aria-label="<?php echo e(t('nav.menu')); ?>"
          aria-controls="mobile-game-menu"
          aria-expanded="false"
          style="all: unset; align-items: center; border: 1px solid var(--outline-variant); border-radius: 6px; box-sizing: border-box; color: var(--on-surface); cursor: pointer; display: inline-flex; height: 44px; justify-content: center; width: 44px;"
        >
          <span class="hamburger-icon" aria-hidden="true"></span>
        </button>
        <a class="button border desktop-account-link" href="/account"><?php echo e(t('nav.account')); ?></a>
        <form method="post" action="/logout" class="nav-form desktop-logout-form">
          <?php echo csrf_field(); ?>
          <button type="submit"><?php echo e(t('nav.logout')); ?></button>
        </form>
      </nav>
      <div id="mobile-game-menu" class="mobile-game-menu-content" aria-label="<?php echo e(t('nav.menu')); ?>" aria-hidden="true">
        <?php foreach ($gameNavigationItems as $item) { ?>
          <?php
            $itemPage = ltrim($item['href'], '/');
            $isActive = $currentPage === $itemPage;
          ?>
          <a
            href="<?php echo e($item['href']); ?>"
            class="<?php echo $isActive ? 'active' : ''; ?>"
            <?php echo $isActive ? 'aria-current="page"' : ''; ?>
          >
            <span class="mobile-menu-symbol" aria-hidden="true"><?php echo e($item['symbol']); ?></span>
            <span><?php echo e($item['label']); ?></span>
          </a>
        <?php } ?>
        <a href="/account" class="<?php echo $currentPage === 'account' ? 'active' : ''; ?>">
          <span class="mobile-menu-symbol" aria-hidden="true">A</span>
          <span><?php echo e(t('nav.account')); ?></span>
        </a>
        <form method="post" action="/logout" class="mobile-logout-form">
          <?php echo csrf_field(); ?>
          <button type="submit">
            <span class="mobile-menu-symbol" aria-hidden="true">L</span>
            <span><?php echo e(t('nav.logout')); ?></span>
          </button>
        </form>
      </div>
      <script>
        (() => {
          const trigger = document.querySelector('.mobile-menu-trigger');
          const menu = document.querySelector('#mobile-game-menu');

          if (!trigger || !menu) {
            return;
          }

          const setOpen = (isOpen) => {
            trigger.setAttribute('aria-expanded', String(isOpen));
            menu.setAttribute('aria-hidden', String(!isOpen));
            menu.style.display = isOpen ? 'grid' : 'none';
          };

          trigger.addEventListener('click', (event) => {
            event.stopPropagation();
            setOpen(trigger.getAttribute('aria-expanded') !== 'true');
          });

          menu.addEventListener('click', (event) => {
            event.stopPropagation();
          });

          document.addEventListener('click', () => {
            setOpen(false);
          });

          document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
              setOpen(false);
              trigger.focus();
            }
          });
        })();
      </script>
      <nav class="tabbed game-nav-tabs" aria-label="Game navigation">
        <?php foreach ($gameNavigationItems as $item) { ?>
          <?php
            $itemPage = ltrim($item['href'], '/');
            $isActive = $currentPage === $itemPage;
          ?>
          <a
            href="<?php echo e($item['href']); ?>"
            class="<?php echo $isActive ? 'active' : ''; ?>"
            <?php echo $isActive ? 'aria-current="page"' : ''; ?>
          >
            <i><?php echo e($item['icon']); ?></i>
            <div><?php echo e($item['label']); ?></div>
          </a>
        <?php } ?>
      </nav>
    </header>
    <main class="responsive auth-shell">
