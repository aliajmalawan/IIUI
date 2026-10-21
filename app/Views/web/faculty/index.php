<?php
/**
 * @var array $staff
 * @var array $departments
 * @var int $totalCount
 * @var int|null $activeDepartment
 */
?>
<section class="hero-minimal">
  <div class="hero-khatam" aria-hidden="true"></div>
  <div class="container">
    <p class="hero-eyebrow">Mardan Campus</p>
    <h1 class="hero-title">Our Faculty</h1>
    <p class="hero-lead">Meet the educators and staff shaping our students' journey.</p>
  </div>
</section>

<div class="container section-block">
  <?php if ($departments !== []): ?>
    <div class="filter-pills">
      <a class="filter-pill<?= $activeDepartment === null ? ' is-active' : '' ?>" href="<?= e(route('faculty.index')) ?>">All Faculty <span class="filter-pill-count"><?= (int) $totalCount ?></span></a>
      <?php foreach ($departments as $dept): ?>
        <a class="filter-pill<?= $activeDepartment === (int) $dept['id'] ? ' is-active' : '' ?>"
           href="<?= e(route('faculty.index')) ?>?department=<?= (int) $dept['id'] ?>"><?= e($dept['name']) ?> <span class="filter-pill-count"><?= (int) $dept['total'] ?></span></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($staff === []): ?>
    <div class="empty-block">
      <p>No faculty profiles published yet. Please check back soon.</p>
    </div>
  <?php else: ?>
    <h2 class="visually-hidden">Faculty members</h2>
    <div class="faculty-grid reveal-stagger">
      <?php foreach ($staff as $member): ?>
        <a class="faculty-card reveal-zoom" href="<?= e(route('faculty.show', ['slug' => $member['slug']])) ?>">
          <div class="faculty-card-photo">
            <?php if ($member['photo'] && is_file(PUBLIC_PATH . $member['photo'])): ?>
              <img src="<?= e(thumb_url($member['photo'])) ?>" alt="<?= e(trim($member['first_name'] . ' ' . (string) $member['last_name'])) ?>" loading="lazy" decoding="async">
            <?php else: ?>
              <?= icon('person-badge') ?>
            <?php endif; ?>
          </div>
          <h3><?= e(trim($member['first_name'] . ' ' . (string) $member['last_name'])) ?></h3>
          <?php if ($member['designation_name']): ?><p class="faculty-card-role"><?= e($member['designation_name']) ?></p><?php endif; ?>
          <?php if ($member['department_name']): ?><p class="faculty-card-dept"><?= e($member['department_name']) ?></p><?php endif; ?>
          <?php if ($member['qualification']): ?><p class="faculty-card-qualification"><?= e($member['qualification']) ?></p><?php endif; ?>
          <?php if ((int) $member['experience_years'] > 0): ?><span class="faculty-card-exp"><?= (int) $member['experience_years'] ?>+ Yrs Experience</span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
