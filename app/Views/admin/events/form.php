<?php
/** @var array|null $event */
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($event[$key] ?? $default);
$dt = static function (?string $stored, array $old, string $key): string {
    if (isset($old[$key])) {
        return $old[$key];
    }
    return $stored ? date('Y-m-d\TH:i', strtotime($stored)) : '';
};
$startValue = $dt($event['start_datetime'] ?? null, $old, 'start_datetime');
$endValue = $dt($event['end_datetime'] ?? null, $old, 'end_datetime');
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($event === null ? 'Add Event' : 'Edit Event') ?></h1>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.events')) ?>"><?= icon('chevron-left') ?> Back to Events</a>
</div>

<form method="post"
      action="<?= e($event === null ? route('admin.events.store') : route('admin.events.update', ['id' => $event['id']])) ?>"
      enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="news-form-grid">
    <div>
      <div class="admin-card">
        <div class="field">
          <label for="f-title">Title</label>
          <input class="inp" type="text" id="f-title" name="title" value="<?= e($field('title')) ?>" required>
          <?php if (has_error('title')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('title')) ?></p><?php endif; ?>
        </div>

        <div class="field">
          <label for="f-slug">URL Slug (optional)</label>
          <input class="inp" type="text" id="f-slug" name="slug" value="<?= e($field('slug')) ?>" placeholder="auto-generated from the title">
          <p class="help">Leave blank to auto-generate. Shown as <code>/events/your-slug</code>.</p>
        </div>

        <div class="field">
          <label for="f-description">Short Description (optional)</label>
          <textarea class="inp" style="height:auto;padding:10px 12px" rows="2" id="f-description" name="description"
                     placeholder="Shown on listing cards"><?= e($field('description')) ?></textarea>
        </div>

        <div class="field">
          <label for="content">Details (optional)</label>
          <textarea id="content" name="content"><?= e($field('content')) ?></textarea>
        </div>
      </div>

      <div class="admin-card">
        <h3 class="card-subhead">SEO (optional)</h3>
        <div class="field">
          <label for="f-meta-title">Meta Title</label>
          <input class="inp" type="text" id="f-meta-title" name="meta_title" value="<?= e($field('meta_title')) ?>">
        </div>
        <div class="field">
          <label for="f-meta-desc">Meta Description</label>
          <textarea class="inp" style="height:auto;padding:10px 12px" rows="2" id="f-meta-desc" name="meta_description"><?= e($field('meta_description')) ?></textarea>
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><?= $event === null ? 'Create Event' : 'Save Changes' ?></button>
    </div>

    <aside>
      <div class="admin-card">
        <h3 class="card-subhead">Schedule &amp; Venue</h3>
        <div class="field">
          <label for="f-start">Starts</label>
          <input class="inp" type="datetime-local" id="f-start" name="start_datetime" value="<?= e($startValue) ?>" required>
          <?php if (has_error('start_datetime')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('start_datetime')) ?></p><?php endif; ?>
        </div>
        <div class="field">
          <label for="f-end">Ends (optional)</label>
          <input class="inp" type="datetime-local" id="f-end" name="end_datetime" value="<?= e($endValue) ?>">
          <?php if (has_error('end_datetime')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('end_datetime')) ?></p><?php endif; ?>
        </div>
        <div class="field">
          <label for="f-venue">Venue (optional)</label>
          <input class="inp" type="text" id="f-venue" name="venue" value="<?= e($field('venue')) ?>">
        </div>
        <div class="field">
          <label for="f-organizer">Organizer (optional)</label>
          <input class="inp" type="text" id="f-organizer" name="organizer" value="<?= e($field('organizer')) ?>">
        </div>
        <div class="field">
          <label for="f-category">Category (optional)</label>
          <input class="inp" type="text" id="f-category" name="category" value="<?= e($field('category')) ?>" placeholder="e.g. Sports, Academic, Cultural">
        </div>
      </div>

      <div class="admin-card">
        <h3 class="card-subhead">Publishing</h3>
        <div class="field">
          <label for="f-status">Status</label>
          <select class="inp" id="f-status" name="status">
            <?php foreach (['draft' => 'Draft', 'published' => 'Published', 'cancelled' => 'Cancelled', 'completed' => 'Completed'] as $val => $label): ?>
              <option value="<?= e($val) ?>" <?= $field('status', 'draft') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field checkbox-row">
          <input type="checkbox" id="f-featured" name="is_featured" value="1" <?= (int) $field('is_featured', '0') === 1 ? 'checked' : '' ?>>
          <label for="f-featured" style="margin:0;font-weight:400">Featured</label>
        </div>
      </div>

      <div class="admin-card">
        <h3 class="card-subhead">Registration</h3>
        <div class="field checkbox-row">
          <input type="checkbox" id="f-reg-required" name="registration_required" value="1" <?= (int) $field('registration_required', '0') === 1 ? 'checked' : '' ?>>
          <label for="f-reg-required" style="margin:0;font-weight:400">Registration required</label>
        </div>
        <div class="field">
          <label for="f-reg-url">Registration URL (optional)</label>
          <input class="inp" type="text" id="f-reg-url" name="registration_url" value="<?= e($field('registration_url')) ?>">
        </div>
        <div class="field">
          <label for="f-capacity">Capacity (optional)</label>
          <input class="inp" type="number" id="f-capacity" name="capacity" min="0" value="<?= e($field('capacity')) ?>">
        </div>
      </div>

      <div class="admin-card">
        <h3 class="card-subhead">Banner Image</h3>
        <div class="upload-preview" style="width:100%;height:140px;margin-bottom:var(--space-3)">
          <?php if ($event !== null && $event['banner'] && is_file(PUBLIC_PATH . $event['banner'])): ?>
            <img src="<?= e(base_url(ltrim($event['banner'], '/'))) ?>" alt="">
          <?php else: ?>
            <?= icon('calendar-event') ?>
          <?php endif; ?>
        </div>
        <input type="file" id="f-banner" name="banner" accept="image/png,image/jpeg,image/webp">
        <?php if (has_error('banner')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('banner')) ?></p><?php endif; ?>
      </div>
    </aside>
  </div>
</form>

<script>window.TINYMCE_ASSET_BASE = <?= json_encode(asset('vendor/tinymce')) ?>;</script>
<script src="<?= e(asset('vendor/tinymce/tinymce.min.js')) ?>"></script>
<script src="<?= e(asset('js/news-editor.js')) ?>"></script>
