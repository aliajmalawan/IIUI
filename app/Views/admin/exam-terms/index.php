<?php
/** @var array $terms */
$activeTab = 'terms';
$typeLabel = ['monthly' => 'Monthly Test', 'mid' => 'Mid Term', 'final' => 'Final Term', 'test' => 'Class Test', 'pre_board' => 'Pre-Board'];
$statusBadge = ['planned' => 'badge-mute', 'ongoing' => 'badge-info', 'marking' => 'badge-warning', 'completed' => 'badge-success', 'cancelled' => 'badge-danger'];
?>
<div class="admin-page-header">
  <div>
    <h1>Academic Calendar</h1>
    <p>Exam terms and holidays shown on the public Academic Calendar page.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.exam-terms.create')) ?>"><?= icon('plus-lg') ?> Add Exam Term</a>
</div>

<?php require VIEWS_PATH . '/partials/admin/_academic-calendar-tabs.php'; ?>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <?php if ($terms === []): ?>
    <div class="empty-state">
      <?= icon('calendar-event') ?>
      <h3>No exam terms yet</h3>
      <p>Add the first exam term to start building the academic calendar.</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Session</th><th>Type</th><th>Dates</th><th>Status</th><th>Results</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($terms as $term): ?>
          <tr>
            <td><strong><?= e($term['name']) ?></strong></td>
            <td class="mono"><?= e($term['session_name']) ?></td>
            <td><span class="badge badge-mute"><?= e($typeLabel[$term['type']] ?? ucfirst($term['type'])) ?></span></td>
            <td class="mono">
              <?= e(date('d M Y', strtotime((string) $term['start_date']))) ?>
              &ndash; <?= e(date('d M Y', strtotime((string) $term['end_date']))) ?>
            </td>
            <td><span class="badge <?= $statusBadge[$term['status']] ?? 'badge-mute' ?>"><?= e(ucfirst($term['status'])) ?></span></td>
            <td>
              <?php if ((int) $term['is_result_published'] === 1): ?>
                <span class="badge badge-success"><?= icon('check-circle-fill') ?> Published</span>
                <form method="post" action="<?= e(route('admin.exam-terms.unpublish', ['id' => $term['id']])) ?>" style="display:inline">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-secondary btn-sm">Unpublish</button>
                </form>
              <?php else: ?>
                <span class="badge badge-mute">Not Published</span>
                <?php if ($term['status'] === 'completed'): ?>
                  <form method="post" action="<?= e(route('admin.exam-terms.publish', ['id' => $term['id']])) ?>" style="display:inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-secondary btn-sm">Publish</button>
                  </form>
                <?php endif; ?>
              <?php endif; ?>
            </td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.exam-terms.edit', ['id' => $term['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.exam-terms.delete', ['id' => $term['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete exam term "' . $term['name'] . '"?') ?>">
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
