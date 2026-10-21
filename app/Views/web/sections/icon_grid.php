<?php
/** @var array $content */
$items = is_array($content['items'] ?? null) ? $content['items'] : [];
?>
<section class="section-block ps-section ps-icon-grid">
  <div class="container">
    <?php if (!empty($content['heading'])): ?><h2 class="ps-heading"><?= e($content['heading']) ?></h2><?php endif; ?>
    <?php if (!empty($content['subheading'])): ?><p class="ps-subheading"><?= e($content['subheading']) ?></p><?php endif; ?>
    <?php if ($items !== []): ?>
      <?php
        // Optional per-item "color" (brand|gold|info|success|violet) lets a
        // section stand out with more variety than the plain brand-red icon
        // used everywhere else; omitting it keeps the original look (used by
        // About/Vision & Mission's icon_grid sections).
        $allowedColors = ['brand', 'gold', 'info', 'success', 'violet'];
      ?>
      <div class="ps-icon-grid-items">
        <?php foreach ($items as $item): ?>
          <?php $color = in_array($item['color'] ?? '', $allowedColors, true) ? $item['color'] : 'brand'; ?>
          <div class="ps-icon-card ps-icon-card--<?= e($color) ?>">
            <div class="ps-icon-card-icon"><?= icon((string) ($item['icon'] ?: 'star')) ?></div>
            <?php if (!empty($item['title'])): ?><h3><?= e($item['title']) ?></h3><?php endif; ?>
            <?php if (!empty($item['description'])): ?><p><?= e($item['description']) ?></p><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
