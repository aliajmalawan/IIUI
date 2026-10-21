<?php
/** @var array $departments */
$activeTab = 'departments';
?>
<div class="admin-page-header">
  <div>
    <h1>Faculty</h1>
    <p>Departments used to group faculty on the public website.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.faculty.departments.create')) ?>"><?= icon('plus-lg') ?> Add Department</a>
</div>

<?php require VIEWS_PATH . '/partials/admin/_faculty-tabs.php'; ?>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <?php if ($departments === []): ?>
    <div class="empty-state">
      <?= icon('building') ?>
      <h3>No departments yet</h3>
      <p>Create departments like "Computer Science" or "Mathematics" to group faculty.</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Code</th><th>Faculty</th><th>Status</th><th>Order</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($departments as $dept): ?>
          <tr>
            <td><strong><?= e($dept['name']) ?></strong></td>
            <td class="mono"><?= e($dept['code']) ?></td>
            <td><?= (int) $dept['staff_count'] ?></td>
            <td>
              <?php if ($dept['is_active']): ?>
                <span class="badge badge-success">Active</span>
              <?php else: ?>
                <span class="badge badge-mute">Inactive</span>
              <?php endif; ?>
            </td>
            <td class="mono"><?= (int) $dept['sort_order'] ?></td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.faculty.departments.edit', ['id' => $dept['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.faculty.departments.delete', ['id' => $dept['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete department "' . $dept['name'] . '"? Its faculty will become uncategorized.') ?>">
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
