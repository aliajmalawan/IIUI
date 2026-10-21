<?php
/**
 * @var string $heading
 * @var string $subheading
 * @var array $departments
 * @var string $pageHeroImage
 * @var array $sections
 */
$hasPageHero = $pageHeroImage !== '' && is_file(PUBLIC_PATH . $pageHeroImage);

// Subject-appropriate icon + accent color per department code — a sensible
// default covers any department added later without an explicit mapping.
$deptStyle = [
    'SCI'  => ['icon' => 'flask', 'color' => 'info'],
    'MATH' => ['icon' => 'graph-up', 'color' => 'violet'],
    'ISL'  => ['icon' => 'moon-stars-fill', 'color' => 'brand'],
    'ENG'  => ['icon' => 'book-half', 'color' => 'gold'],
];
$defaultStyle = ['icon' => 'mortarboard', 'color' => 'success'];
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

<?php if ($departments !== []): ?>
  <div class="container section-block">
    <h2 class="visually-hidden">Academic departments</h2>
    <div class="department-grid">
      <?php foreach ($departments as $dept): ?>
        <?php $style = $deptStyle[$dept['code']] ?? $defaultStyle; ?>
        <div class="department-card department-card--<?= e($style['color']) ?>">
          <div class="department-card-icon"><?= icon($style['icon']) ?></div>
          <h3><?= e($dept['name']) ?></h3>
          <?php if (!empty($dept['description'])): ?>
            <p class="department-card-desc"><?= e($dept['description']) ?></p>
          <?php endif; ?>
          <?php if (!empty($dept['head_first_name'])): ?>
            <p class="department-card-head">
              <?= icon('person-badge', 'icon') ?>
              <?= e(trim($dept['head_first_name'] . ' ' . (string) $dept['head_last_name'])) ?>
              <span>Head of Department</span>
            </p>
          <?php endif; ?>
          <div class="department-card-footer">
            <span class="department-card-count">
              <?= (int) $dept['staff_count'] ?> Faculty Member<?= (int) $dept['staff_count'] === 1 ? '' : 's' ?>
            </span>
            <a class="department-card-link" href="<?= e(route('faculty.index')) ?>?department=<?= (int) $dept['id'] ?>">
              View Faculty <?= icon('arrow-right', 'icon') ?>
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php if ($sections === [] && $departments === []): ?>
  <div class="container section-block">
    <div class="empty-block">
      <p>No departments have been published yet. Add them from Admin &rarr; Faculty &rarr; Departments.</p>
    </div>
  </div>
<?php endif; ?>
