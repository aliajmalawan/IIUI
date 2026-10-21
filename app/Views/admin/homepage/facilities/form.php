<?php
/** @var array|null $facility */
$activeTab = 'facilities';
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($facility[$key] ?? $default);
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($facility === null ? 'Add Facility' : 'Edit Facility') ?></h1>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.homepage.facilities')) ?>"><?= icon('chevron-left') ?> Back</a>
</div>

<div class="admin-card" style="max-width: 560px">
  <form method="post"
        action="<?= e($facility === null ? route('admin.homepage.facilities.store') : route('admin.homepage.facilities.update', ['id' => $facility['id']])) ?>"
        enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="field">
      <label for="f-title">Title</label>
      <input class="inp" type="text" id="f-title" name="title" value="<?= e($field('title')) ?>" placeholder="e.g. Library" required>
      <?php if (has_error('title')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('title')) ?></p><?php endif; ?>
    </div>

    <div class="field">
      <label for="f-description">Description (optional)</label>
      <textarea class="inp" style="height:auto;padding:10px 12px" rows="3" id="f-description" name="description"
                 placeholder="A short line shown under the title"><?= e($field('description')) ?></textarea>
    </div>

    <div class="field">
      <label for="f-image">Photo</label>
      <div class="upload-row">
        <div class="upload-preview" style="width:120px;height:80px">
          <?php if ($facility !== null && $facility['image'] && is_file(PUBLIC_PATH . $facility['image'])): ?>
            <img src="<?= e(base_url(ltrim($facility['image'], '/'))) ?>" alt="">
          <?php else: ?>
            <?= icon($field('icon') !== '' ? $field('icon') : 'building') ?>
          <?php endif; ?>
        </div>
        <div>
          <input type="file" id="f-image" name="image" accept="image/png,image/jpeg,image/webp">
          <p class="upload-hint">JPG, PNG, or WEBP &middot; max 2&nbsp;MB. Shown as a fallback icon until uploaded.</p>
        </div>
      </div>
      <?php if (has_error('image')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('image')) ?></p><?php endif; ?>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-icon">Fallback Icon Name</label>
        <input class="inp" type="text" id="f-icon" name="icon" value="<?= e($field('icon')) ?>" placeholder="e.g. book-half">
        <p class="help">Shown until a photo is uploaded above.</p>
      </div>
      <div class="field">
        <label for="f-sort">Display Order</label>
        <input class="inp" type="number" id="f-sort" name="sort_order" value="<?= e($field('sort_order', '0')) ?>">
      </div>
    </div>

    <div class="field checkbox-row">
      <input type="checkbox" id="f-active" name="is_active" value="1" <?= ($facility === null || (int) ($facility['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
      <label for="f-active" style="margin:0;font-weight:400">Active &mdash; visible on the homepage</label>
    </div>

    <button type="submit" class="btn btn-primary"><?= $facility === null ? 'Add Facility' : 'Save Changes' ?></button>
  </form>
</div>
