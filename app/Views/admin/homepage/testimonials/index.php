<?php
/** @var array $testimonials */
$activeTab = 'testimonials';
$typeBadge = ['student' => 'badge-info', 'parent' => 'badge-success', 'alumni' => 'badge-warning', 'staff' => 'badge-mute'];
?>
<div class="admin-page-header">
  <div>
    <h1>Homepage Builder</h1>
    <p>What students, parents, and alumni say &mdash; shown in the Testimonials section.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.homepage.testimonials.create')) ?>"><?= icon('plus-lg') ?> Add Testimonial</a>
</div>

<?php require VIEWS_PATH . '/partials/admin/_homepage-tabs.php'; ?>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <?php if ($testimonials === []): ?>
    <div class="empty-state">
      <?= icon('people') ?>
      <h3>No testimonials yet</h3>
      <p>Add a quote from a student, parent, or alumnus to feature on the homepage.</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th></th><th>Name</th><th>Quote</th><th>Type</th><th>Featured</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($testimonials as $t): ?>
          <tr>
            <td>
              <div class="avatar-circle" style="width:32px;height:32px;overflow:hidden">
                <?php if ($t['photo'] && is_file(PUBLIC_PATH . $t['photo'])): ?>
                  <img src="<?= e(base_url(ltrim($t['photo'], '/'))) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                  <?= e(mb_strtoupper(mb_substr($t['name'], 0, 1))) ?>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <strong><?= e($t['name']) ?></strong>
              <?php if ($t['designation']): ?><br><span style="font-size:12px;color:var(--text-muted)"><?= e($t['designation']) ?></span><?php endif; ?>
            </td>
            <td style="max-width:280px;font-size:13px;color:var(--text-secondary)">
              <?= e(mb_strimwidth($t['content'], 0, 90, '…')) ?>
            </td>
            <td><span class="badge <?= $typeBadge[$t['type']] ?? 'badge-mute' ?>"><?= e(ucfirst($t['type'])) ?></span></td>
            <td><?= $t['is_featured'] ? icon('check-circle') : '&mdash;' ?></td>
            <td>
              <?php if ($t['status'] === 'published'): ?>
                <span class="badge badge-success">Published</span>
              <?php else: ?>
                <span class="badge badge-mute">Draft</span>
              <?php endif; ?>
            </td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.homepage.testimonials.edit', ['id' => $t['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.homepage.testimonials.delete', ['id' => $t['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete the testimonial from "' . $t['name'] . '"?') ?>">
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
