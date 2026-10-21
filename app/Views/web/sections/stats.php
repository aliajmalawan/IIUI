<?php
/** @var array $content */
$items = is_array($content['items'] ?? null) ? $content['items'] : [];
?>
<?php if ($items !== []): ?>
<section class="section-block ps-section ps-stats">
  <div class="container">
    <?php if (!empty($content['heading'])): ?><h2 class="ps-heading"><?= e($content['heading']) ?></h2><?php endif; ?>
    <?php $allowedColors = ['brand', 'gold', 'info', 'success', 'violet']; ?>
    <div class="ps-stats-items">
      <?php foreach ($items as $item): ?>
        <?php $color = in_array($item['color'] ?? '', $allowedColors, true) ? $item['color'] : 'brand'; ?>
        <div class="ps-stat-item ps-stat-item--<?= e($color) ?>">
          <p class="ps-stat-value ps-stat-value--<?= e($color) ?>"><?= e((string) ($item['value'] ?? '')) ?></p>
          <p class="ps-stat-label"><?= e((string) ($item['label'] ?? '')) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
