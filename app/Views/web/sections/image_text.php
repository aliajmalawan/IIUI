<?php
/** @var array $content */
$position = ($content['image_position'] ?? 'right') === 'left' ? 'left' : 'right';
$hasImage = !empty($content['image']) && is_file(PUBLIC_PATH . $content['image']);
?>
<section class="section-block ps-section ps-image-text">
  <div class="container">
    <div class="ps-image-text-grid ps-image-text-grid--<?= e($position) ?><?= $hasImage ? '' : ' ps-image-text-grid--no-image' ?>">
      <?php if ($hasImage): ?>
        <div class="ps-image-text-media">
          <img src="<?= e(base_url(ltrim($content['image'], '/'))) ?>" alt="<?= e((string) ($content['heading'] ?? '')) ?>" loading="lazy" decoding="async">
        </div>
      <?php endif; ?>
      <div class="ps-image-text-body">
        <?php if (!empty($content['heading'])): ?><h2><?= e($content['heading']) ?></h2><?php endif; ?>
        <?php if (!empty($content['text'])): ?><p><?= e($content['text']) ?></p><?php endif; ?>
      </div>
    </div>
  </div>
</section>
