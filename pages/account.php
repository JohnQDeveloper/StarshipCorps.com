<?php declare(strict_types=1); ?>
<?php global $auth; ?>
<section class="auth-grid">
  <article class="auth-panel">
    <h1>Account</h1>
    <p>You are signed in as <?php echo e($auth->getEmail()); ?>.</p>
    <div class="row wrap">
      <a class="button border" href="/">Home</a>
      <form method="post" action="/logout">
        <?php echo csrf_field(); ?>
        <button type="submit">Log out</button>
      </form>
    </div>
  </article>
</section>
