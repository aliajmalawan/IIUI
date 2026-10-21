<?php
/**
 * @var string $title
 * @var string|null $token
 * @var string $email
 */
?>
<h1><?= e($title) ?></h1>
<p class="lead">Choose a new password for your account.</p>

<?php if (has_error('token')): ?>
  <div class="alert-box alert-error"><?= e((string) error('token')) ?></div>
<?php endif; ?>
<?php if (has_error('password')): ?>
  <div class="alert-box alert-error"><?= e((string) error('password')) ?></div>
<?php endif; ?>

<form method="post" action="<?= e(route('password.update')) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= e((string) $token) ?>">
  <div class="field">
    <label for="password">New Password</label>
    <input class="inp" type="password" id="password" name="password" required minlength="10" autofocus>
  </div>
  <div class="field">
    <label for="password_confirmation">Confirm New Password</label>
    <input class="inp" type="password" id="password_confirmation" name="password_confirmation" required minlength="10">
  </div>
  <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
</form>
