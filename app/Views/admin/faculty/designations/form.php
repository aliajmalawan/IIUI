<?php
/** @var array|null $designation */
$activeTab = 'designations';
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($designation[$key] ?? $default);
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($designation === null ? 'Add Designation' : 'Edit Designation') ?></h1>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.faculty.designations')) ?>"><?= icon('chevron-left') ?> Back to Designations</a>
</div>

<div class="admin-card" style="max-width: 560px">
  <form method="post"
        action="<?= e($designation === null ? route('admin.faculty.designations.store') : route('admin.faculty.designations.update', ['id' => $designation['id']])) ?>">
    <?= csrf_field() ?>

    <div class="field">
      <label for="f-name">Name</label>
      <input class="inp" type="text" id="f-name" name="name" value="<?= e($field('name')) ?>" placeholder="e.g. Senior Teacher" required>
      <?php if (has_error('name')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('name')) ?></p><?php endif; ?>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-category">Category</label>
        <select class="inp" id="f-category" name="category">
          <?php foreach (['teaching' => 'Teaching', 'non_teaching' => 'Non-Teaching', 'management' => 'Management', 'support' => 'Support'] as $val => $label): ?>
            <option value="<?= e($val) ?>" <?= $field('category', 'teaching') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="f-rank">Rank</label>
        <input class="inp" type="number" id="f-rank" name="rank" value="<?= e($field('rank', '50')) ?>">
        <p class="help">Lower numbers are shown first (e.g. Principal = 1).</p>
      </div>
    </div>

    <div class="field checkbox-row">
      <input type="checkbox" id="f-active" name="is_active" value="1" <?= ($designation === null || (int) ($designation['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
      <label for="f-active" style="margin:0;font-weight:400">Active</label>
    </div>

    <button type="submit" class="btn btn-primary"><?= $designation === null ? 'Create Designation' : 'Save Changes' ?></button>
  </form>
</div>
