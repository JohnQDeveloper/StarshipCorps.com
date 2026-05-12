<?php declare(strict_types=1); ?>
<?php
$formError = (string)($formError ?? '');
$formSuccess = (string)($formSuccess ?? '');
$email = (string)($email ?? '');
?>
<section class="auth-grid">
  <article class="auth-panel">
    <h1>Resend verification</h1>

    <?php if ($formError !== '') { ?>
      <div class="snackbar error active"><?php echo e($formError); ?></div>
    <?php } ?>

    <?php if ($formSuccess !== '') { ?>
      <div class="snackbar active"><?php echo e($formSuccess); ?></div>
    <?php } ?>

    <form method="post" action="/resend-verification" autocomplete="on">
      <?php echo csrf_field(); ?>

      <div class="field border label">
        <input type="email" name="email" value="<?php echo e($email); ?>" required maxlength="249" autocomplete="email">
        <label>Email</label>
      </div>

      <button type="submit" class="responsive">Send verification link</button>
    </form>

    <p class="auth-link"><a href="/login">Back to login</a></p>
  </article>
</section>
