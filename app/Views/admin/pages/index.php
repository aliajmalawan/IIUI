<?php
/** @var array $pages */
$systemSlugs = ['about', 'academics', 'admissions', 'contact'];
?>
<div class="admin-page-header">
  <div>
    <h1>Pages</h1>
    <p>The site's pages. Each has a banner header and a stack of editable content sections.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.pages.create')) ?>"><?= icon('plus-lg') ?> Add Page</a>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th>Banner</th><th>Page</th><th>URL</th><th>Sections</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($pages as $page): ?>
        <?php $isSystem = in_array($page['slug'], $systemSlugs, true); ?>
        <tr>
          <td>
            <div class="upload-preview" style="width:96px;height:56px">
              <?php if ($page['featured_image'] && is_file(PUBLIC_PATH . $page['featured_image'])): ?>
                <img src="<?= e(base_url(ltrim($page['featured_image'], '/'))) ?>" alt="">
              <?php else: ?>
                <?= icon('image') ?>
              <?php endif; ?>
            </div>
          </td>
          <td>
            <strong><?= e($page['title']) ?></strong>
            <?php if ($isSystem): ?><br><span style="font-size:11px;color:var(--text-muted)">System page</span><?php endif; ?>
          </td>
          <td class="mono">/<?= e($isSystem ? $page['slug'] : 'page/' . $page['slug']) ?></td>
          <td class="mono"><?= (int) $page['section_count'] ?></td>
          <td>
            <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.pages.edit', ['id' => $page['id']])) ?>"><?= icon('pencil-square') ?> Edit</a>
            <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.pages.sections', ['id' => $page['id']])) ?>"><?= icon('grid-1x2') ?> Sections</a>
            <?php if (!$isSystem): ?>
              <form method="post" action="<?= e(route('admin.pages.delete', ['id' => $page['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete the page "' . $page['title'] . '"? This cannot be undone.') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger-700)"><?= icon('trash') ?></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
