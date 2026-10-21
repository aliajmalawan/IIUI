<?php
/** @var array $posts */
$statusBadge = ['published' => 'badge-success', 'draft' => 'badge-mute', 'scheduled' => 'badge-info', 'archived' => 'badge-warning'];
?>
<div class="admin-page-header">
  <div>
    <h1>Blog</h1>
    <p>Articles shown on the public website's Blog page.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.blog.create')) ?>"><?= icon('plus-lg') ?> Write Post</a>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <?php if ($posts === []): ?>
    <div class="empty-state">
      <?= icon('pencil-square') ?>
      <h3>No blog posts yet</h3>
      <p>Write your first post &mdash; e.g. "A Day in the Life of a Class 9 Student".</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th></th><th>Title</th><th>Status</th><th>Views</th><th>Published</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($posts as $post): ?>
          <tr>
            <td>
              <div class="upload-preview" style="width:56px;height:40px">
                <?php if ($post['featured_image'] && is_file(PUBLIC_PATH . $post['featured_image'])): ?>
                  <img src="<?= e(base_url(ltrim($post['featured_image'], '/'))) ?>" alt="">
                <?php else: ?>
                  <?= icon('pencil-square') ?>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <strong><?= e($post['title']) ?></strong>
              <?php if ($post['is_pinned']): ?> <?= icon('pin-angle') ?><?php endif; ?>
              <?php if ($post['is_featured']): ?> <span class="badge badge-info">Featured</span><?php endif; ?>
            </td>
            <td><span class="badge <?= $statusBadge[$post['status']] ?? 'badge-mute' ?>"><?= e(ucfirst($post['status'])) ?></span></td>
            <td class="mono"><?= number_format((int) $post['views']) ?></td>
            <td class="mono">
              <?= $post['published_at'] ? e(date('d M Y', strtotime((string) $post['published_at']))) : '&mdash;' ?>
            </td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.blog.edit', ['id' => $post['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.blog.delete', ['id' => $post['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete blog post "' . $post['title'] . '"?') ?>">
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
