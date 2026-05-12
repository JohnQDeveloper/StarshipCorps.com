<?php declare(strict_types=1); ?>
<?php
$formError = (string)($formError ?? '');
$email = (string)($email ?? '');
?>
<section class="auth-grid">
  <article class="auth-panel">
    <h1>Log in</h1>

    <?php if ($formError !== '') { ?>
      <div class="snackbar error active"><?php echo e($formError); ?></div>
    <?php } ?>

    <form method="post" action="/login" autocomplete="on">
      <?php echo csrf_field(); ?>

      <div class="field border label">
        <input type="email" name="email" value="<?php echo e($email); ?>" required maxlength="249" autocomplete="email">
        <label>Email</label>
      </div>

      <div class="field border label">
        <input type="password" name="password" required autocomplete="current-password">
        <label>Password</label>
      </div>

      <label class="checkbox">
        <input type="checkbox" name="remember" value="1">
        <span>Remember me</span>
      </label>

      <button type="submit" class="responsive">Log in</button>
    </form>

    <p class="auth-link"><a href="/forgot-password">Forgot your password?</a></p>
    <p class="auth-link"><a href="/resend-verification">Resend verification email</a></p>
    <p class="auth-link">No account yet? <a href="/register">Register</a></p>
  </article>
</section>
