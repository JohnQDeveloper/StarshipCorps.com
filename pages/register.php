<?php declare(strict_types=1); ?>
<?php
$formError = (string)($formError ?? '');
$formSuccess = (string)($formSuccess ?? '');
$email = (string)($email ?? '');
$username = (string)($username ?? '');
?>
<section class="auth-grid">
  <article class="auth-panel">
    <h1>Create account</h1>

    <?php if ($formError !== '') { ?>
      <div class="snackbar error active"><?php echo e($formError); ?></div>
    <?php } ?>

    <?php if ($formSuccess !== '') { ?>
      <div class="snackbar active"><?php echo e($formSuccess); ?></div>
    <?php } ?>

    <form method="post" action="/register" autocomplete="on">
      <?php echo csrf_field(); ?>

      <div class="field border label">
        <input type="email" name="email" value="<?php echo e($email); ?>" required maxlength="249" autocomplete="email">
        <label>Email</label>
      </div>

      <div class="field border label">
        <input type="text" name="username" value="<?php echo e($username); ?>" required maxlength="100" autocomplete="username">
        <label>Username</label>
      </div>

      <div class="field border label">
        <input type="password" name="password" required minlength="8" autocomplete="new-password">
        <label>Password</label>
      </div>

      <div class="field border label">
        <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
        <label>Confirm password</label>
      </div>

      <button type="submit" class="responsive">Register</button>
    </form>

    <p class="auth-link">Already have an account? <a href="/login">Log in</a></p>
  </article>
</section>
