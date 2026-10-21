<?php
/** @var array|null $stat */
$activeTab = 'stats';
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($stat[$key] ?? $default);
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($stat === null ? 'Add Statistic' : 'Edit Statistic') ?></h1>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.homepage.stats')) ?>"><?= icon('chevron-left') ?> Back</a>
</div>

<div class="admin-card" style="max-width: 520px">
  <form method="post"
        action="<?= e($stat === null ? route('admin.homepage.stats.store') : route('admin.homepage.stats.update', ['id' => $stat['id']])) ?>">
    <?= csrf_field() ?>

    <div class="field">
      <label for="f-label">Label</label>
      <input class="inp" type="text" id="f-label" name="label" value="<?= e($field('label')) ?>" placeholder="e.g. Enrolled Students" required>
      <?php if (has_error('label')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('label')) ?></p><?php endif; ?>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-value">Value</label>
        <input class="inp" type="text" id="f-value" name="value" value="<?= e($field('value')) ?>" placeholder="e.g. 1200" required>
        <?php if (has_error('value')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('value')) ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label for="f-suffix">Suffix (optional)</label>
        <input class="inp" type="text" id="f-suffix" name="suffix" value="<?= e($field('suffix')) ?>" placeholder="e.g. + or %">
      </div>
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
      <input type="checkbox" id="f-active" name="is_active" value="1" <?= ($stat === null || (int) ($stat['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
      <label for="f-active" style="margin:0;font-weight:400">Active &mdash; visible on the homepage</label>
    </div>

    <button type="submit" class="btn btn-primary"><?= $stat === null ? 'Add Statistic' : 'Save Changes' ?></button>
  </form>
</div>
