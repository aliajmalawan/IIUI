<?php
/** @var array $content */
$images = is_array($content['images'] ?? null) ? $content['images'] : [];
$images = array_values(array_filter($images, static fn (array $img) => !empty($img['image']) && is_file(PUBLIC_PATH . $img['image'])));
?>
<?php if ($images !== []): ?>
<section class="section-block ps-section ps-gallery">
  <div class="container">
    <?php if (!empty($content['heading'])): ?><h2 class="ps-heading"><?= e($content['heading']) ?></h2><?php endif; ?>
    <div class="ps-gallery-grid">
      <?php foreach ($images as $img): ?>
        <div>
          <a class="ps-gallery-item" href="<?= e(base_url(ltrim($img['image'], '/'))) ?>" target="_blank" rel="noopener" aria-label="<?= e(trim((string) ($img['caption'] ?? '')) !== '' ? $img['caption'] . ' (opens the full photo in a new tab)' : 'View photo (opens the full photo in a new tab)') ?>">
            <img src="<?= e(thumb_url($img['image'])) ?>" alt="<?= e((string) ($img['caption'] ?? '')) ?>" loading="lazy" decoding="async">
          </a>
          <?php if (!empty($img['caption'])): ?><span class="ps-gallery-caption"><?= e($img['caption']) ?></span><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
