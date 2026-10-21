<?php
/** @var array $users */
$statusBadge = [
    'active'    => 'badge-success',
    'inactive'  => 'badge-mute',
    'suspended' => 'badge-danger',
    'pending'   => 'badge-warning',
];
?>
<div class="admin-page-header">
  <div>
    <h1>Users &amp; Roles</h1>
    <p>Admin panel accounts and their assigned roles. <a href="<?= e(route('admin.roles')) ?>">View role definitions &amp; permissions &rarr;</a></p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.users.create')) ?>"><?= icon('plus-lg') ?> Add User</a>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <?php if ($users === []): ?>
    <div class="empty-state">
      <?= icon('people') ?>
      <h3>No users found</h3>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th></th><th>Name</th><th>Username / Email</th><th>Role(s)</th><th>Status</th><th>Last Login</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td>
              <div class="avatar-circle" style="width:32px;height:32px">
                <?php if (!empty($u['avatar']) && is_file(PUBLIC_PATH . $u['avatar'])): ?>
                  <img src="<?= e(base_url(ltrim((string) $u['avatar'], '/'))) ?>" alt="">
                <?php else: ?>
                  <?= e(mb_strtoupper(mb_substr($u['first_name'], 0, 1))) ?>
                <?php endif; ?>
              </div>
            </td>
            <td><strong><?= e(trim($u['first_name'] . ' ' . (string) $u['last_name'])) ?></strong></td>
            <td>
              <span class="mono"><?= e($u['username']) ?></span>
              <br><span style="font-size:12px;color:var(--text-muted)"><?= e((string) $u['email']) ?></span>
            </td>
            <td><?= $u['role_names'] ? e((string) $u['role_names']) : '<span style="color:var(--text-muted)">No roles</span>' ?></td>
            <td><span class="badge <?= $statusBadge[$u['status']] ?? 'badge-mute' ?>"><?= e(ucfirst($u['status'])) ?></span></td>
            <td class="mono"><?= $u['last_login_at'] ? e(date('d M Y, h:i A', strtotime((string) $u['last_login_at']))) : '&mdash;' ?></td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.users.edit', ['id' => $u['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.users.delete', ['id' => $u['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete the account for "' . trim($u['first_name'] . ' ' . (string) $u['last_name']) . '"?') ?>">
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
