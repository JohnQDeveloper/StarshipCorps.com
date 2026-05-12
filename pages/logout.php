<?php declare(strict_types=1); ?>
<section class="auth-grid">
  <article class="auth-panel">
    <h1>Log out</h1>
    <form method="post" action="/logout">
      <?php echo csrf_field(); ?>
      <button type="submit" class="responsive">Log out</button>
    </form>
  </article>
</section>
