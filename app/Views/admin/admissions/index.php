<?php
/**
 * @var array $applications
 * @var array $filters
 * @var array $statusCounts
 * @var array $statusLabels
 * @var int $total
 * @var int $page
 * @var int $lastPage
 */
$statusBadge = [
    'submitted'      => 'badge-info',
    'under_review'   => 'badge-mute',
    'shortlisted'    => 'badge-info',
    'test_scheduled' => 'badge-mute',
    'test_taken'     => 'badge-mute',
    'interview'      => 'badge-mute',
    'admitted'       => 'badge-success',
    'rejected'       => 'badge-danger',
    'waitlisted'     => 'badge-mute',
    'withdrawn'      => 'badge-mute',
];
$statusLabel = static fn (string $s) => ucwords(str_replace('_', ' ', $s));
?>
<div class="admin-page-header">
  <div>
    <h1>Admission Applications</h1>
    <p>Applications submitted through the public website's "Apply Now" form.</p>
  </div>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<?php
$searchPlaceholder = 'Search reference, applicant, parent, phone, email or B-Form';
require VIEWS_PATH . '/partials/admin/_inbox-toolbar.php';
?>

<div class="admin-table-wrap">
  <?php if ($applications === []): ?>
    <div class="empty-state">
      <?= icon('mortarboard') ?>
      <?php if ($filters['q'] !== '' || $filters['status'] !== ''): ?>
        <h3>No matching applications</h3>
        <p>Try a different search or clear the filters.</p>
      <?php else: ?>
        <h3>No applications yet</h3>
        <p>Submissions from the website's Admissions page will appear here.</p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Reference</th><th>Applicant</th><th>Class</th><th>Phone</th><th>Received</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($applications as $app): ?>
          <tr>
            <td class="mono"><?= e($app['application_no']) ?></td>
            <td>
              <strong><?= e($app['applicant_name']) ?></strong>
              <br><span style="font-size:12px;color:var(--text-muted)">Father: <?= e($app['father_name']) ?></span>
            </td>
            <td><?= e($app['class_name']) ?><?= $app['stream_name'] ? ' &middot; ' . e($app['stream_name']) : '' ?></td>
            <td class="mono"><?= e($app['phone']) ?></td>
            <td class="mono"><?= e(date('d M Y, h:i A', strtotime((string) $app['created_at']))) ?></td>
            <td><span class="badge <?= $statusBadge[$app['status']] ?? 'badge-mute' ?>"><?= e($statusLabel((string) $app['status'])) ?></span></td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.admissions.show', ['id' => $app['id']])) ?>"><?= icon('eye') ?> View</a>
              <form method="post" action="<?= e(route('admin.admissions.delete', ['id' => $app['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete the application from "' . $app['applicant_name'] . '"?') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger-700)"><?= icon('trash') ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php require VIEWS_PATH . '/partials/admin/_pager.php'; ?>
  <?php endif; ?>
</div>
