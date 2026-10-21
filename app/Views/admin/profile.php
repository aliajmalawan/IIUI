<?php
/** @var array $user */
?>
<div class="admin-page-header">
  <div>
    <h1>My Profile</h1>
    <p>Manage your account details and password.</p>
  </div>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="dash-grid-2">
  <div class="admin-card">
    <div class="admin-card-header"><h2>Account Details</h2></div>

    <?php if (has_error('email')): ?><div class="alert-box alert-error"><?= e((string) error('email')) ?></div><?php endif; ?>
    <?php if (has_error('first_name')): ?><div class="alert-box alert-error"><?= e((string) error('first_name')) ?></div><?php endif; ?>
    <?php if (has_error('avatar')): ?><div class="alert-box alert-error"><?= e((string) error('avatar')) ?></div><?php endif; ?>

    <form method="post" action="<?= e(route('admin.profile.update')) ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="field">
        <label>Photo</label>
        <div class="upload-row">
          <div class="upload-preview" style="width:72px;height:72px;border-radius:50%">
            <?php if (!empty($user['avatar']) && is_file(PUBLIC_PATH . $user['avatar'])): ?>
              <img src="<?= e(base_url(ltrim((string) $user['avatar'], '/'))) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <?= icon('person-circle') ?>
            <?php endif; ?>
          </div>
          <div>
            <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp">
            <p class="upload-hint">JPG, PNG, or WEBP &middot; max 2&nbsp;MB.</p>
            <?php if (!empty($user['avatar']) && is_file(PUBLIC_PATH . $user['avatar'])): ?>
              <label class="checkbox-row" style="margin-top:8px;font-weight:400">
                <input type="checkbox" name="avatar_remove" value="1">
                <span>Remove current photo</span>
              </label>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="field-grid">
        <div class="field">
          <label for="first_name">First Name</label>
          <input class="inp" type="text" id="first_name" name="first_name"
                 value="<?= e(flash('_old_input', [])['first_name'] ?? (string) $user['first_name']) ?>" required>
        </div>
        <div class="field">
          <label for="last_name">Last Name</label>
          <input class="inp" type="text" id="last_name" name="last_name"
                 value="<?= e(flash('_old_input', [])['last_name'] ?? (string) ($user['last_name'] ?? '')) ?>">
        </div>
        <div class="field">
          <label for="email">Email Address</label>
          <input class="inp" type="email" id="email" name="email"
                 value="<?= e(flash('_old_input', [])['email'] ?? (string) $user['email']) ?>" required>
        </div>
        <div class="field">
          <label for="phone">Phone</label>
          <input class="inp" type="text" id="phone" name="phone"
                 value="<?= e(flash('_old_input', [])['phone'] ?? (string) ($user['phone'] ?? '')) ?>">
        </div>
      </div>
      <div class="field">
        <label>Username</label>
        <input class="inp" type="text" value="<?= e((string) $user['username']) ?>" disabled>
        <p class="help">Usernames cannot be changed. Contact a super admin if this needs to change.</p>
      </div>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
  </div>

  <div class="admin-card">
    <div class="admin-card-header"><h2>Change Password</h2></div>

    <?php if (has_error('current_password')): ?><div class="alert-box alert-error"><?= e((string) error('current_password')) ?></div><?php endif; ?>
    <?php if (has_error('password')): ?><div class="alert-box alert-error"><?= e((string) error('password')) ?></div><?php endif; ?>

    <form method="post" action="<?= e(route('admin.profile.password')) ?>">
      <?= csrf_field() ?>
      <div class="field">
        <label for="current_password">Current Password</label>
        <input class="inp" type="password" id="current_password" name="current_password" required>
      </div>
      <div class="field">
        <label for="new_password">New Password</label>
        <input class="inp" type="password" id="new_password" name="password" required minlength="10">
        <p class="help">At least 10 characters, with 3 of: uppercase, lowercase, numbers, symbols.</p>
      </div>
      <div class="field">
        <label for="password_confirmation">Confirm New Password</label>
        <input class="inp" type="password" id="password_confirmation" name="password_confirmation" required minlength="10">
      </div>
      <button type="submit" class="btn btn-primary">Update Password</button>
    </form>
  </div>
</div>
