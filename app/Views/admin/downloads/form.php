<?php
/** @var array|null $download */
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($download[$key] ?? $default);
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($download === null ? 'Add Download' : 'Edit Download') ?></h1>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.downloads')) ?>"><?= icon('chevron-left') ?> Back to Downloads</a>
</div>

<div class="admin-card" style="max-width: 640px">
  <form method="post"
        action="<?= e($download === null ? route('admin.downloads.store') : route('admin.downloads.update', ['id' => $download['id']])) ?>"
        enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="field">
      <label for="f-title">Title</label>
      <input class="inp" type="text" id="f-title" name="title" value="<?= e($field('title')) ?>" required>
      <?php if (has_error('title')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('title')) ?></p><?php endif; ?>
    </div>

    <div class="field">
      <label for="f-description">Description (optional)</label>
      <textarea class="inp" style="height:auto;padding:10px 12px" rows="3" id="f-description" name="description"><?= e($field('description')) ?></textarea>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-category">Category (optional)</label>
        <input class="inp" type="text" id="f-category" name="category" value="<?= e($field('category')) ?>" placeholder="e.g. Admissions, Fee, Syllabus">
      </div>
      <div class="field">
        <label for="f-audience">Audience</label>
        <select class="inp" id="f-audience" name="audience">
          <?php foreach (['public' => 'Public (anyone)', 'students' => 'Students', 'parents' => 'Parents', 'staff' => 'Staff'] as $val => $label): ?>
            <option value="<?= e($val) ?>" <?= $field('audience', 'public') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($field('audience', 'public') !== 'public'): ?>
          <p class="help">Only "Public" files currently appear on the website &mdash; the student/parent/staff portals aren't built yet.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="field">
      <label for="f-file"><?= $download === null ? 'File' : 'Replace File (optional)' ?></label>
      <?php if ($download !== null): ?>
        <p class="help">
          Current: <strong><?= e($download['file_name']) ?></strong>
          (<?= e(strtoupper((string) pathinfo($download['file_name'], PATHINFO_EXTENSION))) ?>, <?= round((int) $download['file_size'] / 1024) ?> KB)
        </p>
      <?php endif; ?>
      <input type="file" id="f-file" name="file" accept=".pdf,.docx,.xlsx">
      <p class="upload-hint">PDF, DOCX, or XLSX &mdash; max 5 MB.</p>
      <?php if (has_error('file')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('file')) ?></p><?php endif; ?>
    </div>

    <div class="field checkbox-row">
      <input type="checkbox" id="f-active" name="is_active" value="1" <?= ($download === null || (int) ($download['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
      <label for="f-active" style="margin:0;font-weight:400">Active &mdash; visible on the website</label>
    </div>

    <button type="submit" class="btn btn-primary"><?= $download === null ? 'Upload Download' : 'Save Changes' ?></button>
  </form>
</div>
