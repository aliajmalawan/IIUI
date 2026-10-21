<?php
/** @var array $staff */
$activeTab = 'faculty';
?>
<div class="admin-page-header">
  <div>
    <h1>Faculty</h1>
    <p>Public profiles shown on the website's Faculty page.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.faculty.create')) ?>"><?= icon('plus-lg') ?> Add Faculty Member</a>
</div>

<?php require VIEWS_PATH . '/partials/admin/_faculty-tabs.php'; ?>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <?php if ($staff === []): ?>
    <div class="empty-state">
      <?= icon('person-badge') ?>
      <h3>No faculty profiles yet</h3>
      <p>Add your first faculty member to appear on the public Faculty page.</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th></th><th>Name</th><th>Department</th><th>Designation</th><th>Public</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($staff as $member): ?>
          <tr>
            <td>
              <div class="avatar-circle" style="width:32px;height:32px;overflow:hidden">
                <?php if ($member['photo'] && is_file(PUBLIC_PATH . $member['photo'])): ?>
                  <img src="<?= e(base_url(ltrim($member['photo'], '/'))) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                  <?= e(mb_strtoupper(mb_substr($member['first_name'], 0, 1))) ?>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <strong><?= e(trim($member['first_name'] . ' ' . (string) $member['last_name'])) ?></strong>
              <br><span style="font-size:12px;color:var(--text-muted)"><?= e((string) $member['employee_no']) ?></span>
            </td>
            <td><?= $member['department_name'] ? e((string) $member['department_name']) : '&mdash;' ?></td>
            <td><?= $member['designation_name'] ? e((string) $member['designation_name']) : '&mdash;' ?></td>
            <td><?= $member['is_public_profile'] ? icon('check-circle') : '&mdash;' ?></td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.faculty.edit', ['id' => $member['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.faculty.delete', ['id' => $member['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete faculty profile for "' . trim($member['first_name'] . ' ' . (string) $member['last_name']) . '"?') ?>">
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
