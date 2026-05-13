<?php declare(strict_types=1);
global $auth;

$accountError = (string)($accountError ?? '');
$accountSuccess = (string)($accountSuccess ?? '');
$currentLanguage = get_language();
?>

<section class="auth-grid">
  <article class="auth-panel">
    <h1><?php echo e(t('account.title')); ?></h1>
    <p><?php echo e(t('account.signed_in_as')); ?> <?php echo e($auth->getEmail()); ?>.</p>

    <?php if ($accountError !== '') { ?>
      <div class="snackbar error active"><?php echo e($accountError); ?></div>
    <?php } ?>

    <?php if ($accountSuccess !== '') { ?>
      <div class="snackbar active"><?php echo e($accountSuccess); ?></div>
    <?php } ?>

    <form method="post" action="/account" class="account-language-form">
      <?php echo csrf_field(); ?>

      <h2><?php echo e(t('account.language.title')); ?></h2>
      <div class="field border label">
        <select name="language" required>
          <?php foreach (supported_languages() as $languageCode => $languageName) { ?>
            <option value="<?php echo e($languageCode); ?>" <?php echo $languageCode === $currentLanguage ? 'selected' : ''; ?>>
              <?php echo e($languageName); ?>
            </option>
          <?php } ?>
        </select>
        <label><?php echo e(t('account.language.label')); ?></label>
      </div>

      <button type="submit" class="responsive"><?php echo e(t('account.language.save')); ?></button>
    </form>

    <div class="row wrap">
      <a class="button border" href="/"><?php echo e(t('account.home')); ?></a>
      <form method="post" action="/logout">
        <?php echo csrf_field(); ?>
        <button type="submit"><?php echo e(t('nav.logout')); ?></button>
      </form>
    </div>
  </article>
</section>
