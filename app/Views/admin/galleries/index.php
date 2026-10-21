<?php
/** @var array $galleries */
$statusBadge = ['published' => 'badge-success', 'draft' => 'badge-mute', 'archived' => 'badge-warning'];
?>
<div class="admin-page-header">
  <div>
    <h1>Gallery</h1>
    <p>Photo albums shown on the public website's Gallery page.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.galleries.create')) ?>"><?= icon('plus-lg') ?> Add Gallery</a>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<?php if ($galleries === []): ?>
  <div class="admin-card">
    <div class="empty-state">
      <?= icon('images') ?>
      <h3>No galleries yet</h3>
      <p>Create your first photo album &mdash; e.g. "Annual Sports Day 2026".</p>
    </div>
  </div>
<?php else: ?>
  <div class="quick-actions" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr))">
    <?php foreach ($galleries as $gallery): ?>
      <div class="admin-card" style="padding: var(--space-3)">
        <div class="upload-preview" style="width:100%;height:140px;margin-bottom:var(--space-3)">
          <?php if ($gallery['cover_image'] && is_file(PUBLIC_PATH . $gallery['cover_image'])): ?>
            <img src="<?= e(base_url(ltrim($gallery['cover_image'], '/'))) ?>" alt="">
          <?php else: ?>
            <?= icon('images') ?>
          <?php endif; ?>
        </div>
        <p style="font-size:14px;margin:0 0 4px;font-weight:600"><?= e($gallery['title']) ?></p>
        <p style="font-size:12px;color:var(--text-muted);margin:0 0 var(--space-3)">
          <?= (int) $gallery['image_count'] ?> photo<?= (int) $gallery['image_count'] === 1 ? '' : 's' ?>
          <?php if ($gallery['category']): ?> &middot; <?= e($gallery['category']) ?><?php endif; ?>
          &middot; <span class="badge <?= $statusBadge[$gallery['status']] ?? 'badge-mute' ?>"><?= e(ucfirst($gallery['status'])) ?></span>
        </p>
        <div class="btn-row" style="display:flex;gap:8px;flex-wrap:wrap">
          <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.galleries.images', ['id' => $gallery['id']])) ?>" style="flex:1"><?= icon('images') ?> Photos</a>
          <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.galleries.edit', ['id' => $gallery['id']])) ?>"><?= icon('pencil-square') ?></a>
          <form method="post" action="<?= e(route('admin.galleries.delete', ['id' => $gallery['id']])) ?>"
                data-confirm="<?= e('Delete "' . $gallery['title'] . '" and all its photos? This cannot be undone.') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger-700)"><?= icon('trash') ?></button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
