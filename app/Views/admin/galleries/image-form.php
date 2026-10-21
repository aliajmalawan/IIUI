<?php
/**
 * @var array $gallery
 * @var array $image
 */
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($image[$key] ?? $default);
?>
<div class="admin-page-header">
  <div>
    <h1>Edit Photo</h1>
    <p><?= e($gallery['title']) ?></p>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.galleries.images', ['id' => $gallery['id']])) ?>"><?= icon('chevron-left') ?> Back</a>
</div>

<div class="admin-card" style="max-width: 480px">
  <div class="upload-preview" style="width:100%;height:180px;margin-bottom:var(--space-4)">
    <img src="<?= e(gallery_item_thumb($image)) ?>" alt="">
  </div>

  <form method="post" action="<?= e(route('admin.galleries.images.update', ['id' => $gallery['id'], 'imageId' => $image['id']])) ?>">
    <?= csrf_field() ?>

    <div class="field">
      <label for="f-caption">Caption (optional)</label>
      <input class="inp" type="text" id="f-caption" name="caption" value="<?= e($field('caption')) ?>">
    </div>

    <div class="field">
      <label for="f-alt">Alt Text (optional)</label>
      <input class="inp" type="text" id="f-alt" name="alt_text" value="<?= e($field('alt_text')) ?>" placeholder="Describes the photo for screen readers">
    </div>

    <div class="field">
      <label for="f-sort">Display Order</label>
      <input class="inp" type="number" id="f-sort" name="sort_order" value="<?= e($field('sort_order', '0')) ?>">
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
  </form>
</div>
