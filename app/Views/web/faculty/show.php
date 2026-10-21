<?php
/** @var array $member */
$fullName = trim($member['first_name'] . ' ' . (string) $member['last_name']);
?>
<section class="hero-minimal">
  <div class="hero-khatam" aria-hidden="true"></div>
  <div class="container">
    <p class="hero-eyebrow">
      <?php if ($member['department_name']): ?><?= e($member['department_name']) ?><?php endif; ?>
    </p>
    <h1 class="hero-title"><?= e($fullName) ?></h1>
    <?php if ($member['designation_name']): ?><p class="hero-lead"><?= e($member['designation_name']) ?></p><?php endif; ?>
  </div>
</section>

<div class="container section-block">
  <p><a class="back-link" href="<?= e(route('faculty.index')) ?>">&larr; Back to Faculty</a></p>

  <article class="faculty-profile">
    <div class="faculty-profile-photo">
      <?php if ($member['photo'] && is_file(PUBLIC_PATH . $member['photo'])): ?>
        <img src="<?= e(base_url(ltrim($member['photo'], '/'))) ?>" alt="<?= e($fullName) ?>">
      <?php else: ?>
        <?= icon('person-badge') ?>
      <?php endif; ?>
    </div>

    <div class="faculty-profile-body">
      <?php if ($member['qualification']): ?>
        <div class="faculty-fact"><?= icon('mortarboard') ?> <span><?= e($member['qualification']) ?></span></div>
      <?php endif; ?>
      <?php if ($member['specialization']): ?>
        <div class="faculty-fact"><?= icon('award') ?> <span><?= e($member['specialization']) ?></span></div>
      <?php endif; ?>
      <?php if ((int) $member['experience_years'] > 0): ?>
        <div class="faculty-fact"><?= icon('clock') ?> <span><?= (int) $member['experience_years'] ?> years of experience</span></div>
      <?php endif; ?>

      <?php if ($member['bio']): ?>
        <div class="faculty-bio">
          <?php foreach (explode("\n", (string) $member['bio']) as $paragraph): ?>
            <?php if (trim($paragraph) !== ''): ?><p><?= e($paragraph) ?></p><?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </article>
</div>
