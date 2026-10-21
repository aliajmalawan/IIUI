<?php
/** @var array $holidays */
$activeTab = 'holidays';
$typeBadge = ['public' => 'badge-info', 'religious' => 'badge-success', 'vacation' => 'badge-warning', 'emergency' => 'badge-danger', 'weekend' => 'badge-mute'];
$typeLabel = ['public' => 'Public Holiday', 'religious' => 'Religious Holiday', 'vacation' => 'Vacation', 'emergency' => 'Emergency Closure', 'weekend' => 'Weekend'];
?>
<div class="admin-page-header">
  <div>
    <h1>Academic Calendar</h1>
    <p>Exam terms and holidays shown on the public Academic Calendar page.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.holidays.create')) ?>"><?= icon('plus-lg') ?> Add Holiday</a>
</div>

<?php require VIEWS_PATH . '/partials/admin/_academic-calendar-tabs.php'; ?>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <?php if ($holidays === []): ?>
    <div class="empty-state">
      <?= icon('moon-stars-fill') ?>
      <h3>No holidays yet</h3>
      <p>Add the first holiday to start building the academic calendar.</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Title</th><th>Session</th><th>Type</th><th>Dates</th><th>Applies To</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($holidays as $h): ?>
          <tr>
            <td><strong><?= e($h['title']) ?></strong></td>
            <td class="mono"><?= e($h['session_name']) ?></td>
            <td><span class="badge <?= $typeBadge[$h['type']] ?? 'badge-mute' ?>"><?= e($typeLabel[$h['type']] ?? ucfirst($h['type'])) ?></span></td>
            <td class="mono">
              <?= e(date('d M Y', strtotime((string) $h['date_from']))) ?>
              <?php if ($h['date_from'] !== $h['date_to']): ?>&ndash; <?= e(date('d M Y', strtotime((string) $h['date_to']))) ?><?php endif; ?>
            </td>
            <td><?= e(ucfirst($h['applies_to'])) ?></td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.holidays.edit', ['id' => $h['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.holidays.delete', ['id' => $h['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete holiday "' . $h['title'] . '"?') ?>">
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
