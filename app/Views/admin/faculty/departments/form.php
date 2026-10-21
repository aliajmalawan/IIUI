<?php
/** @var array|null $department */
$activeTab = 'departments';
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($department[$key] ?? $default);
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($department === null ? 'Add Department' : 'Edit Department') ?></h1>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.faculty.departments')) ?>"><?= icon('chevron-left') ?> Back to Departments</a>
</div>

<div class="admin-card" style="max-width: 560px">
  <form method="post"
        action="<?= e($department === null ? route('admin.faculty.departments.store') : route('admin.faculty.departments.update', ['id' => $department['id']])) ?>">
    <?= csrf_field() ?>

    <div class="field-grid">
      <div class="field">
        <label for="f-name">Name</label>
        <input class="inp" type="text" id="f-name" name="name" value="<?= e($field('name')) ?>" required>
        <?php if (has_error('name')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('name')) ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label for="f-code">Code</label>
        <input class="inp" type="text" id="f-code" name="code" value="<?= e($field('code')) ?>" placeholder="e.g. CS" maxlength="15" required style="text-transform:uppercase">
        <?php if (has_error('code')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('code')) ?></p><?php endif; ?>
      </div>
    </div>

    <div class="field">
      <label for="f-description">Description (optional)</label>
      <textarea class="inp" style="height:auto;padding:10px 12px" rows="3" id="f-description" name="description"><?= e($field('description')) ?></textarea>
    </div>

    <div class="field-grid">
      <div class="field checkbox-row">
        <input type="checkbox" id="f-active" name="is_active" value="1" <?= ($department === null || (int) ($department['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
        <label for="f-active" style="margin:0;font-weight:400">Active</label>
      </div>
      <div class="field">
        <label for="f-sort">Display Order</label>
        <input class="inp" type="number" id="f-sort" name="sort_order" value="<?= e($field('sort_order', '0')) ?>">
      </div>
    </div>

    <button type="submit" class="btn btn-primary"><?= $department === null ? 'Create Department' : 'Save Changes' ?></button>
  </form>
</div>
