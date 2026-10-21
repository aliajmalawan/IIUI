<?php
/** @var array|null $category */
$activeTab = 'categories';
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($category[$key] ?? $default);
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($category === null ? 'Add Category' : 'Edit Category') ?></h1>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.news.categories')) ?>"><?= icon('chevron-left') ?> Back to Categories</a>
</div>

<div class="admin-card" style="max-width: 560px">
  <form method="post"
        action="<?= e($category === null ? route('admin.news.categories.store') : route('admin.news.categories.update', ['id' => $category['id']])) ?>">
    <?= csrf_field() ?>

    <div class="field">
      <label for="f-name">Name</label>
      <input class="inp" type="text" id="f-name" name="name" value="<?= e($field('name')) ?>" required>
      <?php if (has_error('name')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('name')) ?></p><?php endif; ?>
    </div>

    <div class="field">
      <label for="f-slug">URL Slug (optional)</label>
      <input class="inp" type="text" id="f-slug" name="slug" value="<?= e($field('slug')) ?>" placeholder="auto-generated from the name">
      <p class="help">Leave blank to auto-generate. Shown as <code>/news/category/your-slug</code>.</p>
    </div>

    <div class="field">
      <label for="f-description">Description (optional)</label>
      <textarea class="inp" style="height:auto;padding:10px 12px" rows="3" id="f-description" name="description"><?= e($field('description')) ?></textarea>
    </div>

    <div class="field-grid">
      <div class="field checkbox-row">
        <input type="checkbox" id="f-active" name="is_active" value="1" <?= ($category === null || $category['is_active']) ? 'checked' : '' ?>>
        <label for="f-active" style="margin:0;font-weight:400">Active &mdash; visible in category filters</label>
      </div>
      <div class="field">
        <label for="f-sort">Display Order</label>
        <input class="inp" type="number" id="f-sort" name="sort_order" value="<?= e($field('sort_order', '0')) ?>">
      </div>
    </div>

    <button type="submit" class="btn btn-primary"><?= $category === null ? 'Create Category' : 'Save Changes' ?></button>
  </form>
</div>
