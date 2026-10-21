<?php
/** @var array|null $image */
$activeTab = 'gallery';
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($image[$key] ?? $default);
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($image === null ? 'Add Image' : 'Edit Image') ?></h1>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.homepage.gallery')) ?>"><?= icon('chevron-left') ?> Back</a>
</div>

<div class="admin-card" style="max-width: 520px">
  <form method="post"
        action="<?= e($image === null ? route('admin.homepage.gallery.store') : route('admin.homepage.gallery.update', ['id' => $image['id']])) ?>"
        enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="field">
      <label for="f-image">Image <?= $image === null ? '<span style="color:var(--danger-500)">*</span>' : '' ?></label>
      <div class="upload-row">
        <div class="upload-preview" style="width:96px;height:64px">
          <?php if ($image !== null && is_file(PUBLIC_PATH . $image['image_path'])): ?>
            <img src="<?= e(base_url(ltrim($image['image_path'], '/'))) ?>" alt="">
          <?php else: ?>
            <?= icon('grid-1x2') ?>
          <?php endif; ?>
        </div>
        <div>
          <input type="file" id="f-image" name="image" accept="image/png,image/jpeg,image/webp">
          <p class="upload-hint">JPG, PNG, or WEBP &middot; max 2&nbsp;MB.</p>
        </div>
      </div>
      <?php if (has_error('image')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('image')) ?></p><?php endif; ?>
    </div>

    <div class="field">
      <label for="f-caption">Caption (optional)</label>
      <input class="inp" type="text" id="f-caption" name="caption" value="<?= e($field('caption')) ?>">
    </div>

    <div class="field">
      <label for="f-sort">Display Order</label>
      <input class="inp" type="number" id="f-sort" name="sort_order" value="<?= e($field('sort_order', '0')) ?>">
    </div>

    <div class="field checkbox-row">
      <input type="checkbox" id="f-active" name="is_active" value="1" <?= ($image === null || (int) ($image['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
      <label for="f-active" style="margin:0;font-weight:400">Active &mdash; visible on the homepage</label>
    </div>

    <button type="submit" class="btn btn-primary"><?= $image === null ? 'Add Image' : 'Save Changes' ?></button>
  </form>
</div>
