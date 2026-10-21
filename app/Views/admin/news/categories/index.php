<?php
/** @var array $categories */
$activeTab = 'categories';
?>
<div class="admin-page-header">
  <div>
    <h1>News</h1>
    <p>Categories used to organize articles on the public News page.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.news.categories.create')) ?>"><?= icon('plus-lg') ?> Add Category</a>
</div>

<?php require VIEWS_PATH . '/partials/admin/_news-tabs.php'; ?>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <?php if ($categories === []): ?>
    <div class="empty-state">
      <?= icon('tags') ?>
      <h3>No categories yet</h3>
      <p>Create categories like "Announcements", "Events", or "Achievements" to organize articles.</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Slug</th><th>Articles</th><th>Status</th><th>Order</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($categories as $cat): ?>
          <tr>
            <td><strong><?= e($cat['name']) ?></strong></td>
            <td class="mono"><?= e($cat['slug']) ?></td>
            <td><?= (int) $cat['post_count'] ?></td>
            <td>
              <?php if ($cat['is_active']): ?>
                <span class="badge badge-success">Active</span>
              <?php else: ?>
                <span class="badge badge-mute">Inactive</span>
              <?php endif; ?>
            </td>
            <td class="mono"><?= (int) $cat['sort_order'] ?></td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.news.categories.edit', ['id' => $cat['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.news.categories.delete', ['id' => $cat['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete category "' . $cat['name'] . '"? Its articles will become uncategorized.') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger-700)"><?= icon('trash') ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
