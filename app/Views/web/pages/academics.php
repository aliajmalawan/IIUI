<?php
/**
 * @var array $programs
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
    <p class="hero-eyebrow">Academics</p>
    <h1 class="hero-title"><?= e($heading) ?></h1>
    <?php if ($subheading !== ''): ?><p class="hero-lead"><?= e($subheading) ?></p><?php endif; ?>
  </div>
</section>

<?php require VIEWS_PATH . '/partials/web/_page-sections.php'; ?>

<div class="container section-block">
  <?php if ($programs === []): ?>
    <div class="empty-block">
      <p>Academic programs haven't been added yet. Manage them from the Academic Programs tab in Homepage Builder.</p>
    </div>
  <?php else: ?>
    <h2 class="visually-hidden">Programs by stage</h2>
    <div class="programs-grid">
      <?php foreach ($programs as $prog): ?>
        <div class="program-card">
          <div class="program-card-icon"><?= icon($prog['icon'] ?: 'mortarboard') ?></div>
          <h3><?= e($prog['title']) ?></h3>
          <?php if ($prog['grade_range']): ?><span class="program-card-range"><?= e($prog['grade_range']) ?></span><?php endif; ?>
          <?php if ($prog['description']): ?><p><?= e($prog['description']) ?></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="section-cta">
    <a class="btn btn-primary btn-lg" href="<?= e(route('admissions')) ?>">Apply for Admission</a>
  </div>
</div>
