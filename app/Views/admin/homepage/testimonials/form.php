<?php
/** @var array|null $testimonial */
$activeTab = 'testimonials';
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($testimonial[$key] ?? $default);
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($testimonial === null ? 'Add Testimonial' : 'Edit Testimonial') ?></h1>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.homepage.testimonials')) ?>"><?= icon('chevron-left') ?> Back</a>
</div>

<div class="admin-card" style="max-width: 640px">
  <form method="post"
        action="<?= e($testimonial === null ? route('admin.homepage.testimonials.store') : route('admin.homepage.testimonials.update', ['id' => $testimonial['id']])) ?>"
        enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="field">
      <label for="f-photo">Photo (optional)</label>
      <div class="upload-row">
        <div class="avatar-circle" style="width:56px;height:56px;overflow:hidden">
          <?php if ($testimonial !== null && $testimonial['photo'] && is_file(PUBLIC_PATH . $testimonial['photo'])): ?>
            <img src="<?= e(base_url(ltrim($testimonial['photo'], '/'))) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
          <?php else: ?>
            <?= icon('person-circle') ?>
          <?php endif; ?>
        </div>
        <div>
          <input type="file" id="f-photo" name="photo" accept="image/png,image/jpeg,image/webp">
          <p class="upload-hint">JPG, PNG, or WEBP &middot; max 2&nbsp;MB.</p>
        </div>
      </div>
      <?php if (has_error('photo')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('photo')) ?></p><?php endif; ?>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-name">Name</label>
        <input class="inp" type="text" id="f-name" name="name" value="<?= e($field('name')) ?>" required>
        <?php if (has_error('name')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('name')) ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label for="f-designation">Designation (optional)</label>
        <input class="inp" type="text" id="f-designation" name="designation" value="<?= e($field('designation')) ?>" placeholder="e.g. Parent of Class 8 student">
      </div>
    </div>

    <div class="field">
      <label for="f-content">Testimonial</label>
      <textarea class="inp" style="height:auto;padding:10px 12px" rows="4" id="f-content" name="content" required><?= e($field('content')) ?></textarea>
      <?php if (has_error('content')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('content')) ?></p><?php endif; ?>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-type">Type</label>
        <select class="inp" id="f-type" name="type">
          <?php foreach (['student' => 'Student', 'parent' => 'Parent', 'alumni' => 'Alumnus', 'staff' => 'Staff'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= $field('type', 'parent') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="f-rating">Rating (1&ndash;5, optional)</label>
        <input class="inp" type="number" min="1" max="5" id="f-rating" name="rating" value="<?= e($field('rating')) ?>">
        <?php if (has_error('rating')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('rating')) ?></p><?php endif; ?>
      </div>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-status">Status</label>
        <select class="inp" id="f-status" name="status">
          <option value="draft" <?= $field('status', 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
          <option value="published" <?= $field('status') === 'published' ? 'selected' : '' ?>>Published</option>
        </select>
      </div>
      <div class="field">
        <label for="f-sort">Display Order</label>
        <input class="inp" type="number" id="f-sort" name="sort_order" value="<?= e($field('sort_order', '0')) ?>">
      </div>
    </div>

    <div class="field checkbox-row">
      <input type="checkbox" id="f-featured" name="is_featured" value="1" <?= (int) $field('is_featured', '0') === 1 ? 'checked' : '' ?>>
      <label for="f-featured" style="margin:0;font-weight:400">Featured &mdash; prioritized on the homepage</label>
    </div>

    <button type="submit" class="btn btn-primary"><?= $testimonial === null ? 'Add Testimonial' : 'Save Changes' ?></button>
  </form>
</div>
