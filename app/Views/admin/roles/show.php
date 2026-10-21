<?php
/**
 * @var array $role
 * @var array $permissionGroups
 */
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($role['name']) ?></h1>
    <p><?= $role['description'] ? e((string) $role['description']) : 'No description.' ?></p>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.roles')) ?>"><?= icon('chevron-left') ?> Back to Roles</a>
</div>

<?php if ($permissionGroups === []): ?>
  <div class="admin-card">
    <div class="empty-state">
      <?= icon('shield-check') ?>
      <h3>No permissions assigned</h3>
      <p>This role currently has no permissions in the system.</p>
    </div>
  </div>
<?php else: ?>
  <?php foreach ($permissionGroups as $groupName => $permissions): ?>
    <div class="admin-card">
      <h3 class="card-subhead"><?= e($groupName) ?></h3>
      <div class="quick-actions" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr))">
        <?php foreach ($permissions as $perm): ?>
          <div style="display:flex;align-items:center;gap:8px;padding:8px 0">
            <?= icon('check-circle-fill', 'icon') ?>
            <span><?= e($perm['name']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
