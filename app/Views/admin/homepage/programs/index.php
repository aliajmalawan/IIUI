<?php
/** @var array $programs */
$activeTab = 'programs';
?>
<div class="admin-page-header">
  <div>
    <h1>Homepage Builder</h1>
    <p>Program cards shown in the "Academic Programs" section &mdash; e.g. "Primary".</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.homepage.programs.create')) ?>"><?= icon('plus-lg') ?> Add Program</a>
</div>

<?php require VIEWS_PATH . '/partials/admin/_homepage-tabs.php'; ?>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <?php if ($programs === []): ?>
    <div class="empty-state">
      <?= icon('mortarboard') ?>
      <h3>No programs yet</h3>
      <p>Add an academic level to feature on the homepage, e.g. "Primary".</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Icon</th><th>Title</th><th>Grade Range</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($programs as $program): ?>
          <tr>
            <td><?= icon($program['icon'] ?: 'mortarboard', 'icon') ?></td>
            <td><strong><?= e($program['title']) ?></strong></td>
            <td><?= $program['grade_range'] ? e((string) $program['grade_range']) : '&mdash;' ?></td>
            <td class="mono"><?= (int) $program['sort_order'] ?></td>
            <td>
              <?php if ($program['is_active']): ?>
                <span class="badge badge-success">Active</span>
              <?php else: ?>
                <span class="badge badge-mute">Inactive</span>
              <?php endif; ?>
            </td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.homepage.programs.edit', ['id' => $program['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.homepage.programs.delete', ['id' => $program['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete the program "' . $program['title'] . '"?') ?>">
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
