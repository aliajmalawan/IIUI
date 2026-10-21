<?php
/**
 * @var string $heading
 * @var string $body
 * @var string $buttonText
 * @var string $applyHref
 * @var array $programs
 * @var array $downloads
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
    <p class="hero-eyebrow">Admissions</p>
    <h1 class="hero-title"><?= e($heading) ?></h1>
    <?php if ($body !== ''): ?><p class="hero-lead"><?= e($body) ?></p><?php endif; ?>
    <div class="hero-actions">
      <a class="btn btn-accent btn-lg" href="<?= e($applyHref) ?>"><?= e($buttonText !== '' ? $buttonText : 'Apply Now') ?></a>
    </div>
  </div>
</section>

<?php require VIEWS_PATH . '/partials/web/_page-sections.php'; ?>

<div class="container section-block">
  <div class="section-heading reveal-fade-up">
    <p class="hero-eyebrow">How It Works</p>
    <h2>Admission Process</h2>
  </div>
  <div class="process-steps reveal-stagger">
    <div class="process-step reveal-fade-up">
      <span class="process-step-no">1</span>
      <div class="process-step-icon"><?= icon('file-earmark-text') ?></div>
      <h3>Submit Application</h3>
      <p>Fill out our online application form with the student's details and the class you're applying for.</p>
    </div>
    <div class="process-step reveal-fade-up">
      <span class="process-step-no">2</span>
      <div class="process-step-icon"><?= icon('pencil-square') ?></div>
      <h3>Assessment</h3>
      <p>Applicants for Class VI and above sit a short entrance assessment on a scheduled date.</p>
    </div>
    <div class="process-step reveal-fade-up">
      <span class="process-step-no">3</span>
      <div class="process-step-icon"><?= icon('people') ?></div>
      <h3>Interview</h3>
      <p>The student and a guardian meet the wing head to complete the admission review.</p>
    </div>
    <div class="process-step reveal-fade-up">
      <span class="process-step-no">4</span>
      <div class="process-step-icon"><?= icon('receipt') ?></div>
      <h3>Documents &amp; Fee</h3>
      <p>Submit required documents and pay the admission fee to confirm the seat.</p>
    </div>
  </div>

  <?php if ($programs !== []): ?>
    <div class="section-heading reveal-fade-up" style="margin-top: var(--space-16)">
      <p class="hero-eyebrow">Programs You Can Apply For</p>
      <h2>Choose a Level</h2>
    </div>
    <div class="programs-grid reveal-stagger">
      <?php foreach ($programs as $prog): ?>
        <div class="program-card reveal-fade-up">
          <div class="program-card-icon"><?= icon($prog['icon'] ?: 'mortarboard') ?></div>
          <h3><?= e($prog['title']) ?></h3>
          <?php if ($prog['grade_range']): ?><span class="program-card-range"><?= e($prog['grade_range']) ?></span><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<section class="section-block apply-section">
  <div class="container">
    <div class="section-cta reveal-fade-up">
      <p class="hero-eyebrow">Apply Now</p>
      <h2 style="margin-bottom: var(--space-4)">Ready to Begin?</h2>
      <p style="max-width: var(--measure); margin: 0 auto var(--space-6); color: var(--text-secondary)">
        Our online application takes about five minutes — you'll need the student's B-Form/CNIC number and a parent's contact details on hand.
      </p>
      <a class="btn btn-primary btn-lg" href="<?= e($applyHref) ?>"><?= icon('send') ?> Start Application Form</a>
    </div>
  </div>
</section>

<?php if ($downloads !== []): ?>
  <div class="container section-block" style="padding-top:0">
    <div class="section-heading reveal-fade-up">
      <p class="hero-eyebrow">Forms</p>
      <h2>Admission Downloads</h2>
    </div>
    <div class="download-list">
      <?php foreach ($downloads as $dl): ?>
        <div class="download-row">
          <div class="download-row-icon"><?= icon('file-earmark-arrow-down') ?></div>
          <div class="download-row-body">
            <h3><?= e($dl['title']) ?></h3>
            <?php if ($dl['description']): ?><p><?= e($dl['description']) ?></p><?php endif; ?>
          </div>
          <a class="btn btn-accent download-row-btn" href="<?= e(route('downloads.file', ['id' => $dl['id']])) ?>">
            <?= icon('download') ?> Download
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<div class="container section-block" style="padding-top:0">
  <div class="section-cta">
    <p style="margin-bottom: var(--space-4); color: var(--text-secondary)">Have a question before applying?</p>
    <a class="btn btn-secondary btn-lg" href="<?= e(route('contact.show')) ?>">Contact Us</a>
  </div>
</div>
