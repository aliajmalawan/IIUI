<?php
/** @var string $title */
?>
<h1><?= e($title) ?></h1>
<p class="lead">Sign in to the IIUI Mardan Campus portal.</p>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<?php if (has_error('identifier')): ?>
  <div class="alert-box alert-error"><?= e((string) error('identifier')) ?></div>
<?php endif; ?>

<form method="post" action="<?= e(route('login.attempt')) ?>">
  <?= csrf_field() ?>
  <div class="field">
    <label for="identifier">Username or Email</label>
    <input class="inp" type="text" id="identifier" name="identifier" value="<?= old('identifier') ?>" required autofocus>
  </div>
  <div class="field">
    <label for="password">Password</label>
    <input class="inp" type="password" id="password" name="password" required>
  </div>
  <button type="submit" class="btn btn-primary btn-block">Log In</button>
</form>
