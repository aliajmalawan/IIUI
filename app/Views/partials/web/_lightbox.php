<?php
/**
 * Shared gallery viewer for photos and YouTube videos.
 *
 * @var array $lightboxItems rows from gallery_images (media_type, image_path, video_id, caption)
 * @var string $lightboxFallbackCaptionKey optional column to use when an item has no caption
 */
$fallbackKey = $lightboxFallbackCaptionKey ?? null;
$lightboxData = array_map(static function (array $item) use ($fallbackKey): array {
    $caption = trim((string) ($item['caption'] ?? ''));
    if ($caption === '' && $fallbackKey !== null) {
        $caption = (string) ($item[$fallbackKey] ?? '');
    }

    if (($item['media_type'] ?? 'image') === 'video') {
        return ['type' => 'video', 'id' => (string) $item['video_id'], 'caption' => $caption];
    }

    return ['type' => 'image', 'src' => base_url(ltrim((string) $item['image_path'], '/')), 'caption' => $caption];
}, $lightboxItems);
?>
<div class="lightbox-overlay" id="lightbox-overlay" role="dialog" aria-modal="true" aria-label="Gallery viewer" aria-hidden="true">
  <button type="button" class="lightbox-close" id="lightbox-close" aria-label="Close viewer"><?= icon('x-lg') ?></button>
  <button type="button" class="lightbox-nav lightbox-prev" id="lightbox-prev" aria-label="Previous"><?= icon('chevron-left') ?></button>
  <div class="lightbox-content">
    <div class="lightbox-stage">
      <span class="lightbox-spinner" aria-hidden="true"></span>
      <img src="" alt="" id="lightbox-image" decoding="async">
      <div class="lightbox-video" id="lightbox-video" hidden></div>
    </div>
    <p class="lightbox-caption" id="lightbox-caption" hidden></p>
    <p class="lightbox-counter" id="lightbox-counter" aria-live="polite"></p>
  </div>
  <button type="button" class="lightbox-nav lightbox-next" id="lightbox-next" aria-label="Next"><?= icon('chevron-right') ?></button>
</div>

<script type="application/json" id="lightbox-data">
<?= json_encode($lightboxData, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>
</script>
<script src="<?= e(asset('js/lightbox.js')) ?>" defer></script>
