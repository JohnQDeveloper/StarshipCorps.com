<?php declare(strict_types=1); ?>
<?php global $auth; ?>
<!doctype html>
<html lang="<?php echo htmlspecialchars(get_language(), ENT_QUOTES, 'UTF-8'); ?>">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <link href="https://cdn.jsdelivr.net/npm/beercss@4.0.21/dist/cdn/beer.min.css" rel="stylesheet">
    <script type="module" src="https://cdn.jsdelivr.net/npm/beercss@4.0.21/dist/cdn/beer.min.js"></script>
    <script type="module" src="https://cdn.jsdelivr.net/npm/material-dynamic-colors@1.1.4/dist/cdn/material-dynamic-colors.min.js"></script>
    <link rel="stylesheet" href="./css/custom.css">
    <title><?php echo t('index.title'); ?> :: <?php echo defined('ENVIRONMENT') ? ENVIRONMENT : 'Dev'; ?></title>
  </head>
  <body>
    <header class="responsive">
      <nav>
        <a class="brand" href="/">
          <span>Starship Corps</span>
        </a>
        <div class="max"></div>
        <?php if ($auth->isLoggedIn()) { ?>
          <a class="button border" href="/account"><?php echo e(t('nav.account')); ?></a>
          <form method="post" action="/logout" class="nav-form">
            <?php echo csrf_field(); ?>
            <button type="submit"><?php echo e(t('nav.logout')); ?></button>
          </form>
        <?php } else { ?>
          <a class="button border" href="/login">Log in</a>
          <a class="button" href="/register">Register</a>
        <?php } ?>
      </nav>
    </header>
    <main class="responsive auth-shell">
