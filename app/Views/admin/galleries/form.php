<?php
/** @var array|null $gallery */
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($gallery[$key] ?? $default);
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($gallery === null ? 'Add Gallery' : 'Edit Gallery') ?></h1>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.galleries')) ?>"><?= icon('chevron-left') ?> Back to Galleries</a>
</div>

<div class="admin-card" style="max-width: 640px">
  <form method="post"
        action="<?= e($gallery === null ? route('admin.galleries.store') : route('admin.galleries.update', ['id' => $gallery['id']])) ?>"
        enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="field">
      <label for="f-title">Title</label>
      <input class="inp" type="text" id="f-title" name="title" value="<?= e($field('title')) ?>" required>
      <?php if (has_error('title')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('title')) ?></p><?php endif; ?>
    </div>

    <div class="field">
      <label for="f-slug">URL Slug (optional)</label>
      <input class="inp" type="text" id="f-slug" name="slug" value="<?= e($field('slug')) ?>" placeholder="auto-generated from the title">
      <p class="help">Leave blank to auto-generate from the title. Shown as <code>/gallery/your-slug</code>.</p>
    </div>

    <div class="field">
      <label for="f-description">Description (optional)</label>
      <textarea class="inp" style="height:auto;padding:10px 12px" rows="3" id="f-description" name="description"><?= e($field('description')) ?></textarea>
    </div>

    <div class="field">
      <label for="f-cover">Cover Photo (optional)</label>
      <div class="upload-row">
        <div class="upload-preview" style="width:96px;height:64px">
          <?php if ($gallery !== null && $gallery['cover_image'] && is_file(PUBLIC_PATH . $gallery['cover_image'])): ?>
            <img src="<?= e(base_url(ltrim($gallery['cover_image'], '/'))) ?>" alt="">
          <?php else: ?>
            <?= icon('images') ?>
          <?php endif; ?>
        </div>
        <div>
          <input type="file" id="f-cover" name="cover_image" accept="image/png,image/jpeg,image/webp">
          <p class="upload-hint">Optional here &mdash; you can also set any uploaded photo as the cover from the Photos page.</p>
        </div>
      </div>
      <?php if (has_error('cover_image')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('cover_image')) ?></p><?php endif; ?>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-category">Category (optional)</label>
        <input class="inp" type="text" id="f-category" name="category" value="<?= e($field('category')) ?>" placeholder="e.g. Sports, Events, Campus">
      </div>
      <div class="field">
        <label for="f-date">Gallery Date (optional)</label>
        <input class="inp" type="date" id="f-date" name="gallery_date" value="<?= e($field('gallery_date')) ?>">
        <?php if (has_error('gallery_date')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('gallery_date')) ?></p><?php endif; ?>
      </div>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-status">Status</label>
        <select class="inp" id="f-status" name="status">
          <option value="draft" <?= $field('status', 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
          <option value="published" <?= $field('status') === 'published' ? 'selected' : '' ?>>Published</option>
          <option value="archived" <?= $field('status') === 'archived' ? 'selected' : '' ?>>Archived</option>
        </select>
      </div>
      <div class="field">
        <label for="f-sort">Display Order</label>
        <input class="inp" type="number" id="f-sort" name="sort_order" value="<?= e($field('sort_order', '0')) ?>">
      </div>
    </div>

    <button type="submit" class="btn btn-primary"><?= $gallery === null ? 'Create Gallery' : 'Save Changes' ?></button>
    <?php if ($gallery !== null): ?>
      <a class="btn btn-secondary" href="<?= e(route('admin.galleries.images', ['id' => $gallery['id']])) ?>"><?= icon('images') ?> Manage Photos</a>
    <?php endif; ?>
  </form>
</div>
