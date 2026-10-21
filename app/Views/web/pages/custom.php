<?php
/**
 * Custom pages created from the admin "Pages" list (not one of the four
 * system pages, which each keep their own bespoke template).
 * @var string $heading
 * @var string $subheading
 * @var string $pageHeroImage
 * @var array $sections
 */
$hasPageHero = $pageHeroImage !== '' && is_file(PUBLIC_PATH . $pageHeroImage);
?>
<section class="hero-minimal<?= $hasPageHero ? ' hero-minimal--photo' : '' ?>">
  <?php if ($hasPageHero): ?>
    <div class="hero-minimal-bg" style="background-image:url('<?= e(base_url(ltrim($pageHeroImage, '/'))) ?>')"></div>
    <div class="hero-minimal-scrim" aria-hidden="true"></div>
  <?php endif; ?>
  <div class="hero-khatam" aria-hidden="true"></div>
  <div class="container">
    <h1 class="hero-title"><?= e($heading) ?></h1>
    <?php if ($subheading !== ''): ?><p class="hero-lead"><?= e($subheading) ?></p><?php endif; ?>
  </div>
</section>

<?php require VIEWS_PATH . '/partials/web/_page-sections.php'; ?>

<?php if ($sections === []): ?>
  <div class="container section-block">
    <div class="empty-block">
      <p>This page hasn't been set up yet. Add content sections from the Pages admin.</p>
    </div>
  </div>
<?php endif; ?>
