<?php declare(strict_types=1); ?>
<?php
$formError = (string)($formError ?? '');
$formSuccess = (string)($formSuccess ?? '');
$canResetPassword = (bool)($canResetPassword ?? false);
$selector = (string)($selector ?? '');
$token = (string)($token ?? '');
?>
<section class="auth-grid">
  <article class="auth-panel">
    <h1>Choose new password</h1>

    <?php if ($formError !== '') { ?>
      <div class="snackbar error active"><?php echo e($formError); ?></div>
    <?php } ?>

    <?php if ($formSuccess !== '') { ?>
      <div class="snackbar active"><?php echo e($formSuccess); ?></div>
      <p class="auth-link"><a href="/account">Continue to account</a></p>
    <?php } ?>

    <?php if ($canResetPassword) { ?>
      <form method="post" action="/reset-password" autocomplete="on">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="selector" value="<?php echo e($selector); ?>">
        <input type="hidden" name="token" value="<?php echo e($token); ?>">

        <div class="field border label">
          <input type="password" name="password" required minlength="8" autocomplete="new-password">
          <label>New password</label>
        </div>

        <div class="field border label">
          <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
          <label>Confirm new password</label>
        </div>

        <button type="submit" class="responsive">Reset password</button>
      </form>
    <?php } ?>
  </article>
</section>
