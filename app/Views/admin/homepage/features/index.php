<?php
/** @var array $features */
$activeTab = 'features';
?>
<div class="admin-page-header">
  <div>
    <h1>Homepage Builder</h1>
    <p>Feature cards shown in the "Why Choose Us" section &mdash; e.g. "Qualified Faculty".</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.homepage.features.create')) ?>"><?= icon('plus-lg') ?> Add Feature</a>
</div>

<?php require VIEWS_PATH . '/partials/admin/_homepage-tabs.php'; ?>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <?php if ($features === []): ?>
    <div class="empty-state">
      <?= icon('award') ?>
      <h3>No features yet</h3>
      <p>Add a highlight to feature on the homepage, e.g. "Modern Education".</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Icon</th><th>Title</th><th>Description</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($features as $feature): ?>
          <tr>
            <td><?= icon($feature['icon'] ?: 'award', 'icon') ?></td>
            <td><strong><?= e($feature['title']) ?></strong></td>
            <td style="max-width:280px;font-size:13px;color:var(--text-secondary)">
              <?= $feature['description'] ? e(mb_strimwidth((string) $feature['description'], 0, 80, '…')) : '&mdash;' ?>
            </td>
            <td class="mono"><?= (int) $feature['sort_order'] ?></td>
            <td>
              <?php if ($feature['is_active']): ?>
                <span class="badge badge-success">Active</span>
              <?php else: ?>
                <span class="badge badge-mute">Inactive</span>
              <?php endif; ?>
            </td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.homepage.features.edit', ['id' => $feature['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.homepage.features.delete', ['id' => $feature['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete the feature "' . $feature['title'] . '"?') ?>">
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
