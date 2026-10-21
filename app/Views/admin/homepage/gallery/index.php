<?php
/** @var array $images */
$activeTab = 'gallery';
?>
<div class="admin-page-header">
  <div>
    <h1>Homepage Builder</h1>
    <p>A handful of photos shown in the homepage's gallery strip.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.homepage.gallery.create')) ?>"><?= icon('plus-lg') ?> Add Image</a>
</div>

<?php require VIEWS_PATH . '/partials/admin/_homepage-tabs.php'; ?>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<?php if ($images === []): ?>
  <div class="admin-card">
    <div class="empty-state">
      <?= icon('grid-1x2') ?>
      <h3>No images yet</h3>
      <p>Add a few photos to feature in the homepage gallery.</p>
    </div>
  </div>
<?php else: ?>
  <div class="quick-actions" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr))">
    <?php foreach ($images as $img): ?>
      <div class="admin-card" style="padding: var(--space-3)">
        <div class="upload-preview" style="width:100%;height:120px;margin-bottom:var(--space-3)">
          <img src="<?= e(base_url(ltrim($img['image_path'], '/'))) ?>" alt="<?= e((string) $img['caption']) ?>">
        </div>
        <p style="font-size:13px;margin:0 0 4px;font-weight:600"><?= e((string) ($img['caption'] ?: 'Untitled')) ?></p>
        <p style="font-size:12px;color:var(--text-muted);margin:0 0 var(--space-3)">
          Order <?= (int) $img['sort_order'] ?> &middot;
          <?= $img['is_active'] ? '<span style="color:var(--success-700)">Active</span>' : '<span style="color:var(--text-muted)">Inactive</span>' ?>
        </p>
        <div class="btn-row" style="display:flex;gap:8px">
          <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.homepage.gallery.edit', ['id' => $img['id']])) ?>" style="flex:1"><?= icon('pencil-square') ?> Edit</a>
          <form method="post" action="<?= e(route('admin.homepage.gallery.delete', ['id' => $img['id']])) ?>"
                data-confirm="Delete this image?">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger-700)"><?= icon('trash') ?></button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
