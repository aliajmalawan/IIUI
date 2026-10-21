<?php
/** @var array $slides */
$activeTab = 'hero';
?>
<div class="admin-page-header">
  <div>
    <h1>Homepage Builder</h1>
    <p>Manage the content shown on the public homepage.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.homepage.hero.create')) ?>"><?= icon('plus-lg') ?> Add Slide</a>
</div>

<?php require VIEWS_PATH . '/partials/admin/_homepage-tabs.php'; ?>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <?php if ($slides === []): ?>
    <div class="empty-state">
      <?= icon('grid-1x2') ?>
      <h3>No slides yet</h3>
      <p>Add the first hero slide to feature it on the homepage.</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Image</th><th>Title</th><th>Position</th><th>Order</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($slides as $slide): ?>
          <tr>
            <td>
              <div class="upload-preview" style="width:64px;height:40px">
                <img src="<?= e(base_url(ltrim($slide['image'], '/'))) ?>" alt="">
              </div>
            </td>
            <td>
              <strong><?= e($slide['title']) ?></strong>
              <?php if ($slide['subtitle']): ?><br><span style="color:var(--text-muted);font-size:12px"><?= e($slide['subtitle']) ?></span><?php endif; ?>
            </td>
            <td><span class="badge badge-mute"><?= e(ucfirst($slide['text_position'])) ?></span></td>
            <td class="mono"><?= (int) $slide['sort_order'] ?></td>
            <td>
              <?php if ($slide['is_active']): ?>
                <span class="badge badge-success"><?= icon('check-circle') ?> Active</span>
              <?php else: ?>
                <span class="badge badge-mute">Inactive</span>
              <?php endif; ?>
            </td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.homepage.hero.edit', ['id' => $slide['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.homepage.hero.delete', ['id' => $slide['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete slide "' . $slide['title'] . '"? This cannot be undone.') ?>">
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
