<?php
/** @var array $stats */
$activeTab = 'stats';
?>
<div class="admin-page-header">
  <div>
    <h1>Homepage Builder</h1>
    <p>Curated numbers shown in the Statistics section &mdash; e.g. "1,200+ Students".</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.homepage.stats.create')) ?>"><?= icon('plus-lg') ?> Add Statistic</a>
</div>

<?php require VIEWS_PATH . '/partials/admin/_homepage-tabs.php'; ?>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <?php if ($stats === []): ?>
    <div class="empty-state">
      <?= icon('bar-chart-line') ?>
      <h3>No statistics yet</h3>
      <p>Add a number to feature on the homepage, e.g. "25+ Years of Excellence".</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Icon</th><th>Value</th><th>Label</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($stats as $stat): ?>
          <tr>
            <td><?= icon($stat['icon'] ?: 'bar-chart-line', 'icon') ?></td>
            <td class="mono"><strong><?= e($stat['value']) ?><?= e((string) $stat['suffix']) ?></strong></td>
            <td><?= e($stat['label']) ?></td>
            <td class="mono"><?= (int) $stat['sort_order'] ?></td>
            <td>
              <?php if ($stat['is_active']): ?>
                <span class="badge badge-success">Active</span>
              <?php else: ?>
                <span class="badge badge-mute">Inactive</span>
              <?php endif; ?>
            </td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.homepage.stats.edit', ['id' => $stat['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.homepage.stats.delete', ['id' => $stat['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete the statistic "' . $stat['label'] . '"?') ?>">
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
