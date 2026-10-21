<?php
/**
 * @var array|null $targetUser
 * @var array $roles
 * @var array $assignedIds
 * @var array $statuses
 */
use App\Core\Auth;

$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($targetUser[$key] ?? $default);
$oldRoles = $old['roles'] ?? null;
$checkedRoleIds = $oldRoles !== null ? array_map('intval', (array) $oldRoles) : $assignedIds;
$isSelf = $targetUser !== null && (int) $targetUser['id'] === (int) Auth::id();
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($targetUser === null ? 'Add User' : 'Edit User') ?></h1>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.users')) ?>"><?= icon('chevron-left') ?> Back to Users</a>
</div>

<?php if (has_error('roles')): ?>
  <div class="alert-box alert-error"><?= e((string) error('roles')) ?></div>
<?php endif; ?>

<form method="post"
      action="<?= e($targetUser === null ? route('admin.users.store') : route('admin.users.update', ['id' => $targetUser['id']])) ?>">
  <?= csrf_field() ?>

  <div class="news-form-grid">
    <div>
      <div class="admin-card">
        <h3 class="card-subhead">Account</h3>
        <div class="field-grid">
          <div class="field">
            <label for="f-first-name">First Name</label>
            <input class="inp" type="text" id="f-first-name" name="first_name" value="<?= e($field('first_name')) ?>" required>
            <?php if (has_error('first_name')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('first_name')) ?></p><?php endif; ?>
          </div>
          <div class="field">
            <label for="f-last-name">Last Name (optional)</label>
            <input class="inp" type="text" id="f-last-name" name="last_name" value="<?= e($field('last_name')) ?>">
          </div>
        </div>
        <div class="field-grid">
          <div class="field">
            <label for="f-username">Username</label>
            <input class="inp" type="text" id="f-username" name="username" value="<?= e($field('username')) ?>" required autocomplete="off">
            <?php if (has_error('username')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('username')) ?></p><?php endif; ?>
          </div>
          <div class="field">
            <label for="f-email">Email</label>
            <input class="inp" type="email" id="f-email" name="email" value="<?= e($field('email')) ?>" required>
            <?php if (has_error('email')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('email')) ?></p><?php endif; ?>
          </div>
        </div>
        <div class="field">
          <label for="f-status">Status</label>
          <select class="inp" id="f-status" name="status" style="max-width:220px" <?= $isSelf ? 'disabled' : '' ?>>
            <?php foreach ($statuses as $st): ?>
              <option value="<?= e($st) ?>" <?= $field('status', 'active') === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if ($isSelf): ?>
            <input type="hidden" name="status" value="<?= e($field('status', 'active')) ?>">
            <p class="help">You cannot change your own account's status.</p>
          <?php endif; ?>
          <?php if (has_error('status')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('status')) ?></p><?php endif; ?>
        </div>
      </div>

      <div class="admin-card">
        <h3 class="card-subhead"><?= $targetUser === null ? 'Password' : 'Change Password (optional)' ?></h3>
        <div class="field-grid">
          <div class="field">
            <label for="f-password">Password</label>
            <input class="inp" type="password" id="f-password" name="password" autocomplete="new-password">
            <p class="help">At least 10 characters, with a mix of upper/lowercase, numbers, and symbols.</p>
            <?php if (has_error('password')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('password')) ?></p><?php endif; ?>
          </div>
          <div class="field">
            <label for="f-password-confirm">Confirm Password</label>
            <input class="inp" type="password" id="f-password-confirm" name="password_confirmation" autocomplete="new-password">
          </div>
        </div>
        <?php if ($targetUser !== null): ?><p class="help">Leave both fields blank to keep the current password.</p><?php endif; ?>
      </div>

      <button type="submit" class="btn btn-primary"><?= $targetUser === null ? 'Create User' : 'Save Changes' ?></button>
    </div>

    <aside>
      <div class="admin-card">
        <h3 class="card-subhead">Roles</h3>
        <p class="help">A user can hold more than one role &mdash; permissions are the union of all assigned roles.</p>
        <?php foreach ($roles as $role): ?>
          <div class="field checkbox-row">
            <input type="checkbox" id="f-role-<?= (int) $role['id'] ?>" name="roles[]" value="<?= (int) $role['id'] ?>"
                   <?= in_array((int) $role['id'], $checkedRoleIds, true) ? 'checked' : '' ?>
                   <?= $isSelf && $role['slug'] === 'super_admin' ? 'disabled' : '' ?>>
            <label for="f-role-<?= (int) $role['id'] ?>" style="margin:0;font-weight:400">
              <?= e($role['name']) ?>
              <?php if ($isSelf && $role['slug'] === 'super_admin'): ?><span style="color:var(--text-muted)">(your own account)</span><?php endif; ?>
            </label>
          </div>
          <?php if ($isSelf && $role['slug'] === 'super_admin'): ?>
            <input type="hidden" name="roles[]" value="<?= (int) $role['id'] ?>">
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </aside>
  </div>
</form>
