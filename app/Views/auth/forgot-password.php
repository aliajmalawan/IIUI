<?php
/** @var string $title */
?>
<h1><?= e($title) ?></h1>
<p class="lead">Enter your email address and we&rsquo;ll send you a link to reset your password.</p>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>
<?php if (has_error('email')): ?>
  <div class="alert-box alert-error"><?= e((string) error('email')) ?></div>
<?php endif; ?>
<?php // 'token' errors land here after a failed /reset-password submission
      // (invalid, expired, or already-used link) redirects back to this page. ?>
<?php if (has_error('token')): ?>
  <div class="alert-box alert-error"><?= e((string) error('token')) ?></div>
<?php endif; ?>

<form method="post" action="<?= e(route('password.email')) ?>">
  <?= csrf_field() ?>
  <div class="field">
    <label for="email">Email Address</label>
    <input class="inp" type="email" id="email" name="email" value="<?= old('email') ?>" required autofocus>
  </div>
  <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
  <p class="auth-links">
    <a href="<?= e(route('login')) ?>">Back to login</a>
  </p>
</form>
