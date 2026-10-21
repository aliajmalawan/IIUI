<?php
/**
 * @var array|null $session
 * @var array<string,array{label:string,items:array<int,array<string,mixed>>}> $months
 */

// One color per (kind,type) pair, reused for both the badge and the date
// accent bar so a glance at the left edge already tells you the category.
$colorMap = [
    'exam:monthly'      => 'info',
    'exam:mid'          => 'warning',
    'exam:final'        => 'danger',
    'exam:test'         => 'mute',
    'exam:pre_board'    => 'violet',
    'holiday:public'    => 'info',
    'holiday:religious' => 'success',
    'holiday:vacation'  => 'warning',
    'holiday:emergency' => 'danger',
    'holiday:weekend'   => 'mute',
];
$colorOf = static fn (array $item): string => $colorMap[$item['kind'] . ':' . $item['type']] ?? 'mute';

$hasItems = false;
foreach ($months as $m) {
    if ($m['items'] !== []) {
        $hasItems = true;
        break;
    }
}

// Legend only lists the categories actually present this session, in a
// stable order, instead of always showing all ten regardless of use.
$legendOrder = [
    'exam:mid' => 'Mid Term', 'exam:final' => 'Final Term', 'exam:monthly' => 'Monthly Test',
    'exam:test' => 'Class Test', 'exam:pre_board' => 'Pre-Board',
    'holiday:vacation' => 'Vacation', 'holiday:public' => 'Public Holiday',
    'holiday:religious' => 'Religious Holiday', 'holiday:emergency' => 'Emergency Closure', 'holiday:weekend' => 'Weekend',
];
$presentKeys = [];
foreach ($months as $m) {
    foreach ($m['items'] as $item) {
        $presentKeys[$item['kind'] . ':' . $item['type']] = true;
    }
}
?>
<section class="hero-minimal">
  <div class="hero-khatam" aria-hidden="true"></div>
  <div class="container">
    <p class="hero-eyebrow">Mardan Campus</p>
    <h1 class="hero-title">Academic Calendar</h1>
    <p class="hero-lead">
      <?= $session !== null ? 'Exam terms and holidays for the ' . e($session['name']) . ' session.' : 'Exam terms and holidays for the academic session.' ?>
    </p>
  </div>
</section>

<div class="container section-block">
  <?php if ($session !== null): ?>
    <div class="cal-session-banner">
      <span><?= icon('calendar3') ?> <strong><?= e($session['name']) ?></strong> academic session</span>
      <span class="cal-session-dates">
        <?= e(date('d M Y', strtotime((string) $session['start_date']))) ?>
        &ndash; <?= e(date('d M Y', strtotime((string) $session['end_date']))) ?>
      </span>
    </div>
  <?php endif; ?>

  <?php if (!$hasItems): ?>
    <div class="empty-block">
      <p>The academic calendar hasn't been published yet. Please check back soon.</p>
    </div>
  <?php else: ?>
    <div class="cal-legend">
      <?php foreach ($legendOrder as $key => $label): ?>
        <?php if (isset($presentKeys[$key])): ?>
          <span class="cal-legend-item"><span class="cal-dot cal-dot--<?= e($colorMap[$key]) ?>"></span> <?= e($label) ?></span>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <div class="cal-timeline">
      <?php foreach ($months as $month): ?>
        <?php if ($month['items'] === []) { continue; } ?>
        <div class="cal-month">
          <h2 class="cal-month-heading"><?= e($month['label']) ?></h2>
          <div class="cal-month-items">
            <?php foreach ($month['items'] as $item): ?>
              <?php
                $color = $colorOf($item);
                $startTs = strtotime((string) $item['start']);
                $endTs = strtotime((string) $item['end']);
                $multiDay = $item['start'] !== $item['end'];
              ?>
              <div class="cal-item cal-item--<?= e($color) ?>">
                <div class="cal-item-date">
                  <span class="cal-item-day"><?= e(date('d', $startTs)) ?></span>
                  <span class="cal-item-month"><?= e(date('M', $startTs)) ?></span>
                </div>
                <div class="cal-item-body">
                  <span class="cal-badge cal-badge--<?= e($color) ?>"><?= e($item['type_label']) ?></span>
                  <?php if (!empty($item['is_result_published'])): ?>
                    <span class="cal-badge cal-badge--success"><?= icon('check-circle') ?> Results Published</span>
                  <?php endif; ?>
                  <h3><?= e($item['title']) ?></h3>
                  <?php if (!empty($item['description'])): ?><p><?= e($item['description']) ?></p><?php endif; ?>
                  <span class="cal-item-range">
                    <?= icon('clock') ?>
                    <?= e(date('d M Y', $startTs)) ?><?= $multiDay ? ' – ' . e(date('d M Y', $endTs)) : '' ?>
                  </span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
