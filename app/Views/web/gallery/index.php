<?php
/**
 * @var array $images
 * @var array $categories
 * @var string $activeCategory
 */
?>
<section class="hero-minimal">
  <div class="hero-khatam" aria-hidden="true"></div>
  <div class="container">
    <p class="hero-eyebrow">Mardan Campus</p>
    <h1 class="hero-title">Gallery</h1>
    <p class="hero-lead">Moments from campus life, events, and activities.</p>
  </div>
</section>

<div class="container section-block">
  <?php if ($categories !== []): ?>
    <div class="filter-pills filter-pills--center">
      <a class="filter-pill<?= $activeCategory === '' ? ' is-active' : '' ?>" href="<?= e(route('gallery.index')) ?>">All</a>
      <?php foreach ($categories as $cat): ?>
        <a class="filter-pill<?= $activeCategory === $cat ? ' is-active' : '' ?>"
           href="<?= e(route('gallery.index')) ?>?category=<?= e(rawurlencode($cat)) ?>"><?= e($cat) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($images === []): ?>
    <div class="empty-block">
      <p>No photos have been published yet. Please check back soon.</p>
    </div>
  <?php else: ?>
    <div class="photo-grid">
      <?php foreach ($images as $i => $img): ?>
        <?php $isVideo = ($img['media_type'] ?? 'image') === 'video'; ?>
        <button type="button" class="photo-item<?= $isVideo ? ' is-video' : '' ?>" data-lightbox-index="<?= $i ?>" aria-label="<?= $isVideo ? 'Play video' : 'View photo' ?>">
          <img src="<?= e(gallery_item_thumb($img)) ?>" alt="<?= e((string) ($img['alt_text'] ?: $img['gallery_title'])) ?>" loading="lazy" decoding="async">
          <?php if ($isVideo): ?><span class="photo-item-play" aria-hidden="true"><?= icon('play-fill') ?></span><?php endif; ?>
        </button>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php if ($images !== []): ?>
<?php
$lightboxItems = $images;
$lightboxFallbackCaptionKey = 'gallery_title';
require VIEWS_PATH . '/partials/web/_lightbox.php';
?>
<?php endif; ?>
