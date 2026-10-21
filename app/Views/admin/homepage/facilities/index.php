<?php
/** @var array $facilities */
$activeTab = 'facilities';
?>
<div class="admin-page-header">
  <div>
    <h1>Homepage Builder</h1>
    <p>Large image cards shown in the "Facilities" section &mdash; e.g. "Library".</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.homepage.facilities.create')) ?>"><?= icon('plus-lg') ?> Add Facility</a>
</div>

<?php require VIEWS_PATH . '/partials/admin/_homepage-tabs.php'; ?>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<?php if ($facilities === []): ?>
  <div class="admin-card">
    <div class="empty-state">
      <?= icon('building') ?>
      <h3>No facilities yet</h3>
      <p>Add a campus facility to feature on the homepage, e.g. "Library".</p>
    </div>
  </div>
<?php else: ?>
  <div class="quick-actions" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr))">
    <?php foreach ($facilities as $facility): ?>
      <div class="admin-card" style="padding: var(--space-3)">
        <div class="upload-preview" style="width:100%;height:140px;margin-bottom:var(--space-3)">
          <?php if ($facility['image'] && is_file(PUBLIC_PATH . $facility['image'])): ?>
            <img src="<?= e(base_url(ltrim($facility['image'], '/'))) ?>" alt="">
          <?php else: ?>
            <?= icon($facility['icon'] ?: 'building') ?>
          <?php endif; ?>
        </div>
        <p style="font-size:14px;margin:0 0 4px;font-weight:600"><?= e($facility['title']) ?></p>
        <p style="font-size:12px;color:var(--text-muted);margin:0 0 var(--space-3)">
          Order <?= (int) $facility['sort_order'] ?>
          &middot; <span class="badge <?= $facility['is_active'] ? 'badge-success' : 'badge-mute' ?>"><?= $facility['is_active'] ? 'Active' : 'Inactive' ?></span>
        </p>
        <div class="btn-row" style="display:flex;gap:8px;flex-wrap:wrap">
          <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.homepage.facilities.edit', ['id' => $facility['id']])) ?>" style="flex:1"><?= icon('pencil-square') ?> Edit</a>
          <form method="post" action="<?= e(route('admin.homepage.facilities.delete', ['id' => $facility['id']])) ?>"
                data-confirm="<?= e('Delete the facility "' . $facility['title'] . '"?') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger-700)"><?= icon('trash') ?></button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
