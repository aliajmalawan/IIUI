<?php
/**
 * @var array $gallery
 * @var array $images
 */
?>
<section class="hero-minimal">
  <div class="hero-khatam" aria-hidden="true"></div>
  <div class="container">
    <p class="hero-eyebrow"><?= $gallery['gallery_date'] ? e(date('d M Y', strtotime((string) $gallery['gallery_date']))) : 'Gallery' ?></p>
    <h1 class="hero-title"><?= e($gallery['title']) ?></h1>
    <?php if ($gallery['description']): ?><p class="hero-lead"><?= e($gallery['description']) ?></p><?php endif; ?>
  </div>
</section>

<div class="container section-block">
  <p><a class="back-link" href="<?= e(route('gallery.index')) ?>">&larr; Back to Gallery</a></p>

  <?php if ($images === []): ?>
    <div class="empty-block">
      <p>No photos or videos have been added to this album yet.</p>
    </div>
  <?php else: ?>
    <div class="photo-grid">
      <?php foreach ($images as $i => $img): ?>
        <?php $isVideo = ($img['media_type'] ?? 'image') === 'video'; ?>
        <button type="button" class="photo-item<?= $isVideo ? ' is-video' : '' ?>" data-lightbox-index="<?= $i ?>" aria-label="<?= $isVideo ? 'Play video' : 'View photo' ?>">
          <img src="<?= e(gallery_item_thumb($img)) ?>" alt="<?= e((string) ($img['alt_text'] ?: $gallery['title'])) ?>" loading="lazy" decoding="async">
          <?php if ($isVideo): ?><span class="photo-item-play" aria-hidden="true"><?= icon('play-fill') ?></span><?php endif; ?>
        </button>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php if ($images !== []): ?>
<?php
$lightboxItems = $images;
require VIEWS_PATH . '/partials/web/_lightbox.php';
?>
<?php endif; ?>
