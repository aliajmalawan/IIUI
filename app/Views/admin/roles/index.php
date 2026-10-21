<?php
/** @var array $roles */
?>
<div class="admin-page-header">
  <div>
    <h1>Roles</h1>
    <p>Read-only view of what each role can access. <a href="<?= e(route('admin.users')) ?>">&larr; Back to Users</a></p>
  </div>
</div>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th>Role</th><th>Description</th><th>Level</th><th>Users</th><th>Permissions</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($roles as $role): ?>
        <tr>
          <td>
            <strong><?= e($role['name']) ?></strong>
            <?php if ((int) $role['is_system'] === 1): ?><span class="badge badge-mute" style="margin-left:6px">System</span><?php endif; ?>
          </td>
          <td style="color:var(--text-muted)"><?= $role['description'] ? e((string) $role['description']) : '&mdash;' ?></td>
          <td class="mono"><?= (int) $role['level'] ?></td>
          <td class="mono"><?= (int) $role['user_count'] ?></td>
          <td class="mono"><?= (int) $role['permission_count'] ?></td>
          <td><a class="btn btn-secondary btn-sm" href="<?= e(route('admin.roles.show', ['id' => $role['id']])) ?>"><?= icon('eye') ?> View</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
