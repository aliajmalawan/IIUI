<?php
/** @var array|null $slide */
$activeTab = 'hero';
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($slide[$key] ?? $default);
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($slide === null ? 'Add Slide' : 'Edit Slide') ?></h1>
    <p>Hero slides appear on the homepage in the order set below.</p>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.homepage.hero')) ?>"><?= icon('chevron-left') ?> Back to Slides</a>
</div>

<div class="admin-card" style="max-width: 720px">
  <form method="post"
        action="<?= e($slide === null ? route('admin.homepage.hero.store') : route('admin.homepage.hero.update', ['id' => $slide['id']])) ?>"
        enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="field">
      <label for="f-image">Slide Image <?= $slide === null ? '<span class="req" style="color:var(--danger-500)">*</span>' : '' ?></label>
      <div class="upload-row">
        <div class="upload-preview" style="width:96px;height:56px">
          <?php if ($slide !== null && is_file(PUBLIC_PATH . $slide['image'])): ?>
            <img src="<?= e(base_url(ltrim($slide['image'], '/'))) ?>" alt="">
          <?php else: ?>
            <?= icon('grid-1x2') ?>
          <?php endif; ?>
        </div>
        <div>
          <input type="file" id="f-image" name="image" accept="image/png,image/jpeg,image/webp">
          <p class="upload-hint">JPG, PNG, or WEBP &middot; max 15&nbsp;MB. Recommended size: <strong>1920&times;820px</strong> (16:9 widescreen) &mdash; the image is stretched full browser width and cropped to fit, so keep important content centered.</p>
        </div>
      </div>
      <?php if (has_error('image')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('image')) ?></p><?php endif; ?>
    </div>

    <div class="field">
      <label for="f-title">Title</label>
      <input class="inp" type="text" id="f-title" name="title" value="<?= e($field('title')) ?>" required>
      <?php if (has_error('title')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('title')) ?></p><?php endif; ?>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-subtitle">Subtitle</label>
        <input class="inp" type="text" id="f-subtitle" name="subtitle" value="<?= e($field('subtitle')) ?>">
      </div>
      <div class="field">
        <label for="f-text-position">Text Position</label>
        <select class="inp" id="f-text-position" name="text_position">
          <?php foreach (['left', 'center', 'right'] as $pos): ?>
            <option value="<?= $pos ?>" <?= $field('text_position', 'left') === $pos ? 'selected' : '' ?>><?= ucfirst($pos) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="field">
      <label for="f-description">Description</label>
      <textarea class="inp" style="height:auto;padding:10px 12px" rows="3" id="f-description" name="description"><?= e($field('description')) ?></textarea>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-button-text">Button Text</label>
        <input class="inp" type="text" id="f-button-text" name="button_text" value="<?= e($field('button_text')) ?>" placeholder="e.g. Apply Now">
      </div>
      <div class="field">
        <label for="f-button-url">Button Link</label>
        <input class="inp" type="text" id="f-button-url" name="button_url" value="<?= e($field('button_url')) ?>" placeholder="/admission-form">
      </div>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-overlay">Overlay Opacity (0&ndash;100)</label>
        <input class="inp" type="number" min="0" max="100" id="f-overlay" name="overlay_opacity" value="<?= e($field('overlay_opacity', '70')) ?>">
        <?php if (has_error('overlay_opacity')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('overlay_opacity')) ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label for="f-sort">Display Order</label>
        <input class="inp" type="number" id="f-sort" name="sort_order" value="<?= e($field('sort_order', '0')) ?>">
      </div>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-starts">Show From (optional)</label>
        <input class="inp" type="datetime-local" id="f-starts" name="starts_at" value="<?= e($field('starts_at')) ?>">
      </div>
      <div class="field">
        <label for="f-ends">Show Until (optional)</label>
        <input class="inp" type="datetime-local" id="f-ends" name="ends_at" value="<?= e($field('ends_at')) ?>">
      </div>
    </div>

    <div class="field checkbox-row">
      <input type="checkbox" id="f-active" name="is_active" value="1" <?= ($slide === null || (int) ($slide['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
      <label for="f-active" style="margin:0;font-weight:400">Active &mdash; visible on the homepage</label>
    </div>

    <button type="submit" class="btn btn-primary"><?= $slide === null ? 'Add Slide' : 'Save Changes' ?></button>
  </form>
</div>
