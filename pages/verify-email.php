<?php declare(strict_types=1); ?>
<?php
$formError = (string)($formError ?? '');
$formSuccess = (string)($formSuccess ?? '');
?>
<section class="auth-grid">
  <article class="auth-panel">
    <h1>Email verification</h1>

    <?php if ($formError !== '') { ?>
      <div class="snackbar error active"><?php echo e($formError); ?></div>
      <p class="auth-link"><a href="/login">Return to login</a></p>
    <?php } ?>

    <?php if ($formSuccess !== '') { ?>
      <div class="snackbar active"><?php echo e($formSuccess); ?></div>
      <p class="auth-link"><a href="/account">Continue to account</a></p>
    <?php } ?>
  </article>
</section>
