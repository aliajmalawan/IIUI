<?php
/** @var array $downloads */
$audienceBadge = ['public' => 'badge-success', 'students' => 'badge-info', 'parents' => 'badge-warning', 'staff' => 'badge-mute'];
$audienceLabel = ['public' => 'Public', 'students' => 'Students', 'parents' => 'Parents', 'staff' => 'Staff'];
$fmtSize = static function (int $bytes): string {
    if ($bytes >= 1024 * 1024) {
        return round($bytes / (1024 * 1024), 1) . ' MB';
    }
    return round($bytes / 1024, 0) . ' KB';
};
?>
<div class="admin-page-header">
  <div>
    <h1>Downloads</h1>
    <p>Circulars, forms, and documents available from the public website.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.downloads.create')) ?>"><?= icon('plus-lg') ?> Add Download</a>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <?php if ($downloads === []): ?>
    <div class="empty-state">
      <?= icon('file-earmark-arrow-down') ?>
      <h3>No downloads yet</h3>
      <p>Add your first document &mdash; e.g. "Admission Form 2026" or "Fee Structure".</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Title</th><th>Category</th><th>File</th><th>Audience</th><th>Downloads</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($downloads as $dl): ?>
          <tr>
            <td><strong><?= e($dl['title']) ?></strong></td>
            <td><?= $dl['category'] ? e((string) $dl['category']) : '&mdash;' ?></td>
            <td>
              <span class="mono"><?= e(strtoupper((string) pathinfo($dl['file_name'], PATHINFO_EXTENSION))) ?></span>
              &middot; <?= e($fmtSize((int) $dl['file_size'])) ?>
            </td>
            <td><span class="badge <?= $audienceBadge[$dl['audience']] ?? 'badge-mute' ?>"><?= e($audienceLabel[$dl['audience']] ?? ucfirst($dl['audience'])) ?></span></td>
            <td class="mono"><?= number_format((int) $dl['download_count']) ?></td>
            <td>
              <?php if ($dl['is_active']): ?>
                <span class="badge badge-success">Active</span>
              <?php else: ?>
                <span class="badge badge-mute">Inactive</span>
              <?php endif; ?>
            </td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.downloads.edit', ['id' => $dl['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.downloads.delete', ['id' => $dl['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete download "' . $dl['title'] . '"? This also removes the stored file.') ?>">
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
