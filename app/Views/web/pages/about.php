<?php
/**
 * @var string $heading
 * @var string $subheading
 * @var array $messengers
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
    <p class="hero-eyebrow">About Us</p>
    <h1 class="hero-title"><?= e($heading) ?></h1>
    <?php if ($subheading !== ''): ?><p class="hero-lead"><?= e($subheading) ?></p><?php endif; ?>
  </div>
</section>

<?php require VIEWS_PATH . '/partials/web/_page-sections.php'; ?>

<?php if ($messengers !== []): ?>
  <div class="container section-block">
    <div class="welcome-messages">
      <?php foreach ($messengers as $m): ?>
        <div class="welcome-message-card">
          <div class="welcome-message-side">
            <div class="welcome-message-photo">
              <?php if ($m['photo'] !== '' && is_file(PUBLIC_PATH . $m['photo'])): ?>
                <img src="<?= e(thumb_url($m['photo'])) ?>" alt="<?= e($m['name']) ?>" loading="lazy" decoding="async">
              <?php else: ?>
                <?= icon('person-badge') ?>
              <?php endif; ?>
            </div>
            <span class="welcome-message-badge"><?= e(mb_strtoupper($m['designation'])) ?></span>
            <p class="welcome-message-name"><?= e($m['name']) ?></p>
            <p class="welcome-message-role"><?= e($m['designation']) ?></p>
          </div>
          <div class="welcome-message-main">
            <span class="welcome-message-quote-mark" aria-hidden="true">&ldquo;</span>
            <p class="welcome-message-subtitle"><?= e($m['designation']) ?> &mdash; IIUI Schools &amp; College, Mardan Campus</p>
            <blockquote class="welcome-message-quote">&ldquo;<?= nl2br(e($m['message'])) ?>&rdquo;</blockquote>
            <div class="welcome-message-footer">
              <p class="welcome-message-footer-name"><?= e($m['name']) ?></p>
              <p class="welcome-message-footer-role"><?= e($m['designation']) ?></p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php if ($sections === [] && $messengers === []): ?>
  <div class="container section-block">
    <div class="empty-block">
      <p>This page hasn't been set up yet. Add content sections from the Pages admin, and Welcome Message from Homepage Builder.</p>
    </div>
  </div>
<?php endif; ?>
