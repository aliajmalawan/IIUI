<?php
/** @var array $designations */
$activeTab = 'designations';
$categoryLabel = ['teaching' => 'Teaching', 'non_teaching' => 'Non-Teaching', 'management' => 'Management', 'support' => 'Support'];
$categoryBadge = ['teaching' => 'badge-success', 'non_teaching' => 'badge-info', 'management' => 'badge-warning', 'support' => 'badge-mute'];
?>
<div class="admin-page-header">
  <div>
    <h1>Faculty</h1>
    <p>Job titles used for faculty and staff on the public website.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.faculty.designations.create')) ?>"><?= icon('plus-lg') ?> Add Designation</a>
</div>

<?php require VIEWS_PATH . '/partials/admin/_faculty-tabs.php'; ?>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <?php if ($designations === []): ?>
    <div class="empty-state">
      <?= icon('award') ?>
      <h3>No designations yet</h3>
      <p>Create designations like "Principal", "Senior Teacher", or "Lab Assistant".</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Category</th><th>Faculty</th><th>Status</th><th>Rank</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($designations as $des): ?>
          <tr>
            <td><strong><?= e($des['name']) ?></strong></td>
            <td><span class="badge <?= $categoryBadge[$des['category']] ?? 'badge-mute' ?>"><?= e($categoryLabel[$des['category']] ?? ucfirst($des['category'])) ?></span></td>
            <td><?= (int) $des['staff_count'] ?></td>
            <td>
              <?php if ($des['is_active']): ?>
                <span class="badge badge-success">Active</span>
              <?php else: ?>
                <span class="badge badge-mute">Inactive</span>
              <?php endif; ?>
            </td>
            <td class="mono"><?= (int) $des['rank'] ?></td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.faculty.designations.edit', ['id' => $des['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.faculty.designations.delete', ['id' => $des['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete designation "' . $des['name'] . '"? Faculty with this title will become unassigned.') ?>">
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
