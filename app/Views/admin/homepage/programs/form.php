<?php
/** @var array|null $program */
$activeTab = 'programs';
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($program[$key] ?? $default);
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($program === null ? 'Add Program' : 'Edit Program') ?></h1>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.homepage.programs')) ?>"><?= icon('chevron-left') ?> Back</a>
</div>

<div class="admin-card" style="max-width: 520px">
  <form method="post"
        action="<?= e($program === null ? route('admin.homepage.programs.store') : route('admin.homepage.programs.update', ['id' => $program['id']])) ?>">
    <?= csrf_field() ?>

    <div class="field-grid">
      <div class="field">
        <label for="f-title">Title</label>
        <input class="inp" type="text" id="f-title" name="title" value="<?= e($field('title')) ?>" placeholder="e.g. Primary" required>
        <?php if (has_error('title')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('title')) ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label for="f-grade-range">Grade Range (optional)</label>
        <input class="inp" type="text" id="f-grade-range" name="grade_range" value="<?= e($field('grade_range')) ?>" placeholder="e.g. Grades 1 - 5">
      </div>
    </div>

    <div class="field">
      <label for="f-description">Description (optional)</label>
      <textarea class="inp" style="height:auto;padding:10px 12px" rows="3" id="f-description" name="description"
                 placeholder="A short line shown under the title"><?= e($field('description')) ?></textarea>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-icon">Icon Name</label>
        <input class="inp" type="text" id="f-icon" name="icon" value="<?= e($field('icon')) ?>" placeholder="e.g. mortarboard">
        <p class="help">Any name from the icon sprite (see the sidebar icons for examples).</p>
      </div>
      <div class="field">
        <label for="f-sort">Display Order</label>
        <input class="inp" type="number" id="f-sort" name="sort_order" value="<?= e($field('sort_order', '0')) ?>">
      </div>
    </div>

    <div class="field checkbox-row">
      <input type="checkbox" id="f-active" name="is_active" value="1" <?= ($program === null || (int) ($program['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
      <label for="f-active" style="margin:0;font-weight:400">Active &mdash; visible on the homepage</label>
    </div>

    <button type="submit" class="btn btn-primary"><?= $program === null ? 'Add Program' : 'Save Changes' ?></button>
  </form>
</div>
