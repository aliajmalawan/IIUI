<?php
/**
 * @var array $files
 * @var array $folders
 * @var string $activeFolder
 * @var int $totalCount
 * @var int $totalBytes
 */
$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }

    return $bytes . ' B';
};
?>
<div class="admin-page-header">
  <div>
    <h1>Media Library</h1>
    <p><?= (int) $totalCount ?> files &middot; <?= e($formatBytes($totalBytes)) ?> across public/uploads/. Uploaded through each module's own form (Faculty, Gallery, Students, ...) &mdash; browse and clean up from here.</p>
  </div>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<form method="get" action="<?= e(route('admin.media-library')) ?>" class="admin-filter-bar">
  <div class="field">
    <label for="f-folder">Folder</label>
    <select class="inp" id="f-folder" name="folder">
      <option value="">All Folders</option>
      <?php foreach ($folders as $folder): ?>
        <option value="<?= e($folder) ?>" <?= $activeFolder === $folder ? 'selected' : '' ?>><?= e($folder) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button type="submit" class="btn btn-secondary"><?= icon('search') ?> Filter</button>
  <?php if ($activeFolder !== ''): ?>
    <a class="btn btn-secondary" href="<?= e(route('admin.media-library')) ?>">Clear</a>
  <?php endif; ?>
</form>

<?php if ($files === []): ?>
  <div class="admin-card">
    <div class="empty-state">
      <?= icon('grid-1x2') ?>
      <h3>No files found</h3>
      <p><?= $activeFolder !== '' ? 'No files in this folder.' : 'Files uploaded through Faculty, Gallery, Students, and other modules will show up here.' ?></p>
    </div>
  </div>
<?php else: ?>
  <div class="quick-actions" style="grid-template-columns: repeat(auto-fill, minmax(180px, 1fr))">
    <?php foreach ($files as $file): ?>
      <div class="admin-card" style="padding: var(--space-3)">
        <div class="upload-preview" style="width:100%;height:120px;margin-bottom:var(--space-2)">
          <img src="<?= e(base_url(ltrim($file['url'], '/'))) ?>" alt="" loading="lazy">
        </div>
        <p class="mono" style="font-size:11px;color:var(--text-muted);margin:0 0 4px;word-break:break-all"><?= e($file['relative_path']) ?></p>
        <p style="font-size:12px;color:var(--text-muted);margin:0 0 8px">
          <?= e($formatBytes((int) $file['size_bytes'])) ?><?= $file['width'] ? ' &middot; ' . (int) $file['width'] . '&times;' . (int) $file['height'] : '' ?>
        </p>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <button type="button" class="btn btn-secondary btn-sm js-copy-url" data-url="<?= e(base_url(ltrim($file['url'], '/'))) ?>">Copy URL</button>
          <form method="post" action="<?= e(route('admin.media-library.delete')) ?>"
                data-confirm="Delete this file? If it's still used elsewhere on the site (a photo, gallery image, etc.), that reference will break.">
            <?= csrf_field() ?>
            <input type="hidden" name="path" value="<?= e($file['relative_path']) ?>">
            <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger-700)"><?= icon('trash') ?></button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<script>
document.querySelectorAll('.js-copy-url').forEach(function (btn) {
  btn.addEventListener('click', function () {
    navigator.clipboard.writeText(btn.dataset.url).then(function () {
      var original = btn.innerHTML;
      btn.textContent = 'Copied!';
      setTimeout(function () { btn.innerHTML = original; }, 1200);
    });
  });
});
</script>
