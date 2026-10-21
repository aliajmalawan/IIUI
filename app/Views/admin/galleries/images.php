<?php
/**
 * @var array $gallery
 * @var array $images
 */
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($gallery['title']) ?></h1>
    <p>Manage the photos in this gallery.</p>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.galleries')) ?>"><?= icon('chevron-left') ?> Back to Galleries</a>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>
<?php if (has_error('images')): ?>
  <?php foreach ((array) error('images') as $msg): ?>
    <div class="alert-box alert-error"><?= e((string) $msg) ?></div>
  <?php endforeach; ?>
<?php endif; ?>

<div class="admin-card" style="margin-bottom: var(--space-4)">
  <form method="post" action="<?= e(route('admin.galleries.images.store', ['id' => $gallery['id']])) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="field" style="margin-bottom: var(--space-3)">
      <label for="f-images">Add Photos</label>
      <input type="file" id="f-images" name="images[]" accept="image/png,image/jpeg,image/webp" multiple>
      <p class="upload-hint">Select multiple files at once &middot; JPG, PNG, or WEBP &middot; max 2&nbsp;MB each.</p>
    </div>
    <button type="submit" class="btn btn-primary"><?= icon('cloud-upload') ?> Upload</button>
  </form>
</div>

<div class="admin-card" style="margin-bottom: var(--space-4)">
  <form method="post" action="<?= e(route('admin.galleries.videos.store', ['id' => $gallery['id']])) ?>">
    <?= csrf_field() ?>
    <div class="field" style="margin-bottom: var(--space-3)">
      <label for="f-video">Add Video (YouTube)</label>
      <input class="inp" type="url" id="f-video" name="video_url" placeholder="https://www.youtube.com/watch?v=..." required>
      <p class="upload-hint">Paste a YouTube link. It plays inside the gallery viewer &mdash; nothing is uploaded to this site.</p>
    </div>
    <div class="field" style="margin-bottom: var(--space-3)">
      <label for="f-video-caption">Caption (optional)</label>
      <input class="inp" type="text" id="f-video-caption" name="caption" maxlength="255">
    </div>
    <button type="submit" class="btn btn-primary"><?= icon('play-fill') ?> Add Video</button>
  </form>
</div>

<?php if ($images === []): ?>
  <div class="admin-card">
    <div class="empty-state">
      <?= icon('image') ?>
      <h3>No photos yet</h3>
      <p>Upload a few photos above to get started.</p>
    </div>
  </div>
<?php else: ?>
  <div class="quick-actions" style="grid-template-columns: repeat(auto-fill, minmax(180px, 1fr))">
    <?php foreach ($images as $img): ?>
      <div class="admin-card" style="padding: var(--space-3)">
        <div class="upload-preview" style="width:100%;height:120px;margin-bottom:var(--space-2);position:relative">
          <img src="<?= e(gallery_item_thumb($img)) ?>" alt="<?= e((string) $img['alt_text']) ?>">
          <?php if (($img['media_type'] ?? 'image') === 'video'): ?>
            <span class="badge badge-info" style="position:absolute;top:6px;left:6px"><?= icon('play-fill') ?> Video</span>
          <?php elseif ($gallery['cover_image'] === $img['image_path']): ?>
            <span class="badge badge-warning" style="position:absolute;top:6px;left:6px"><?= icon('star-fill') ?> Cover</span>
          <?php endif; ?>
        </div>
        <p style="font-size:12px;color:var(--text-muted);margin:0 0 8px;min-height:16px"><?= e((string) ($img['caption'] ?: '')) ?></p>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.galleries.images.edit', ['id' => $gallery['id'], 'imageId' => $img['id']])) ?>"><?= icon('pencil-square') ?></a>
          <?php if (($img['media_type'] ?? 'image') !== 'video' && $gallery['cover_image'] !== $img['image_path']): ?>
            <form method="post" action="<?= e(route('admin.galleries.images.cover', ['id' => $gallery['id'], 'imageId' => $img['id']])) ?>">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-secondary btn-sm" title="Set as cover"><?= icon('star') ?></button>
            </form>
          <?php endif; ?>
          <form method="post" action="<?= e(route('admin.galleries.images.delete', ['id' => $gallery['id'], 'imageId' => $img['id']])) ?>"
                data-confirm="Delete this photo?">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger-700)"><?= icon('trash') ?></button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
