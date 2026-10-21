<?php
/** @var array $posts */
$activeTab = 'posts';
$statusBadge = ['published' => 'badge-success', 'draft' => 'badge-mute', 'scheduled' => 'badge-info', 'archived' => 'badge-warning'];
?>
<div class="admin-page-header">
  <div>
    <h1>News</h1>
    <p>Articles and announcements shown on the public website's News page.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.news.create')) ?>"><?= icon('plus-lg') ?> Write Article</a>
</div>

<?php require VIEWS_PATH . '/partials/admin/_news-tabs.php'; ?>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <?php if ($posts === []): ?>
    <div class="empty-state">
      <?= icon('newspaper') ?>
      <h3>No articles yet</h3>
      <p>Write your first article &mdash; e.g. "Admissions Open for Fall 2026".</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th></th><th>Title</th><th>Category</th><th>Status</th><th>Views</th><th>Published</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($posts as $post): ?>
          <tr>
            <td>
              <div class="upload-preview" style="width:56px;height:40px">
                <?php if ($post['featured_image'] && is_file(PUBLIC_PATH . $post['featured_image'])): ?>
                  <img src="<?= e(base_url(ltrim($post['featured_image'], '/'))) ?>" alt="">
                <?php else: ?>
                  <?= icon('newspaper') ?>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <strong><?= e($post['title']) ?></strong>
              <?php if ($post['is_pinned']): ?> <?= icon('pin-angle') ?><?php endif; ?>
              <?php if ($post['is_featured']): ?> <span class="badge badge-info">Featured</span><?php endif; ?>
            </td>
            <td><?= e((string) ($post['category_name'] ?: 'Uncategorized')) ?></td>
            <td><span class="badge <?= $statusBadge[$post['status']] ?? 'badge-mute' ?>"><?= e(ucfirst($post['status'])) ?></span></td>
            <td class="mono"><?= number_format((int) $post['views']) ?></td>
            <td class="mono">
              <?= $post['published_at'] ? e(date('d M Y', strtotime((string) $post['published_at']))) : '&mdash;' ?>
            </td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.news.edit', ['id' => $post['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.news.delete', ['id' => $post['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete article "' . $post['title'] . '"?') ?>">
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
