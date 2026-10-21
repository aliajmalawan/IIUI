<?php
/**
 * @var array|null $session
 * @var array<string,array{label:string,classes:array<int,array{name:string,amounts:array<int,string>}>}> $wings
 * @var array<int,array{name:string,sort:int,frequency:string}> $columns
 * @var array<string,string> $freqLabels
 */
$hasData = $wings !== [];
?>
<section class="hero-minimal">
  <div class="hero-khatam" aria-hidden="true"></div>
  <div class="container">
    <p class="hero-eyebrow">Finances</p>
    <h1 class="hero-title">Fee Structure</h1>
    <p class="hero-lead">
      We believe quality education should be accessible. Our fee structure is designed to be clear and
      transparent<?= $session !== null ? ' for the ' . e($session['name']) . ' session' : '' ?>.
    </p>
  </div>
</section>

<div class="container section-block">
  <?php if (!$hasData): ?>
    <div class="empty-block">
      <p>The fee structure hasn't been published yet. Please contact the admission office for current fees.</p>
    </div>
  <?php else: ?>
    <?php foreach ($wings as $wing): ?>
      <div class="fee-wing">
        <h2 class="fee-wing-heading"><?= e($wing['label']) ?></h2>
        <div class="fee-table-wrap" tabindex="0" role="region" aria-label="<?= e($wing['label']) ?> fee table (scrolls sideways on small screens)">
          <table class="fee-table">
            <thead>
              <tr>
                <th>Class</th>
                <?php foreach ($columns as $col): ?>
                  <th><?= e($col['name']) ?> <span class="fee-table-freq"><?= e($freqLabels[$col['frequency']] ?? ucfirst($col['frequency'])) ?></span></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($wing['classes'] as $class): ?>
                <tr>
                  <td class="fee-table-class"><?= e($class['name']) ?></td>
                  <?php foreach ($columns as $categoryId => $col): ?>
                    <td>
                      <?php if (isset($class['amounts'][$categoryId])): ?>
                        Rs. <?= number_format((float) $class['amounts'][$categoryId]) ?>
                      <?php else: ?>
                        <span class="fee-table-na">&mdash;</span>
                      <?php endif; ?>
                    </td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="fee-notes">
      <div class="fee-note">
        <div class="fee-note-icon"><?= icon('bank') ?></div>
        <div>
          <h3>Payment Methods</h3>
          <p>Fees are payable via bank deposit, online transfer, or in person at the school office.</p>
        </div>
      </div>
      <div class="fee-note">
        <div class="fee-note-icon"><?= icon('shield-check') ?></div>
        <div>
          <h3>Security Deposit</h3>
          <p>Refundable in full when a student formally withdraws, subject to no outstanding dues.</p>
        </div>
      </div>
      <div class="fee-note">
        <div class="fee-note-icon"><?= icon('exclamation-triangle') ?></div>
        <div>
          <h3>Subject to Revision</h3>
          <p>Fees may be revised annually. Confirm current amounts with the admission office before enrolling.</p>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="section-cta">
    <p style="margin-bottom: var(--space-4); color: var(--text-secondary)">Have a question about fees or payment plans?</p>
    <div class="hero-actions" style="justify-content:center">
      <a class="btn btn-primary btn-lg" href="<?= e(route('contact.show')) ?>">Contact Us</a>
      <a class="btn btn-secondary btn-lg" href="<?= e(route('admissions')) ?>">Apply Online</a>
    </div>
  </div>
</div>
