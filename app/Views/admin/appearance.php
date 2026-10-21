<?php
use App\Core\Auth;

$currentTheme = (string) (Auth::user()['theme'] ?? 'system');
?>
<div class="admin-page-header">
  <div>
    <h1>Appearance</h1>
    <p>Your personal light/dark preference. Site-wide settings (school name, logo, contact details) live under <a href="<?= e(route('admin.settings.group', ['group' => 'general'])) ?>">Settings</a> in the sidebar.</p>
  </div>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-card" style="max-width: 640px">
  <div class="admin-card-header"><h2>Appearance</h2></div>

  <form method="post" action="<?= e(route('admin.theme')) ?>">
    <?= csrf_field() ?>
    <div class="theme-options">
      <label class="theme-option">
        <input type="radio" name="theme" value="light" <?= $currentTheme === 'light' ? 'checked' : '' ?> onchange="this.form.submit()">
        <span class="theme-option-card"><?= icon('sun-fill') ?><span>Light</span></span>
      </label>
      <label class="theme-option">
        <input type="radio" name="theme" value="dark" <?= $currentTheme === 'dark' ? 'checked' : '' ?> onchange="this.form.submit()">
        <span class="theme-option-card"><?= icon('moon-stars-fill') ?><span>Dark</span></span>
      </label>
      <label class="theme-option">
        <input type="radio" name="theme" value="system" <?= $currentTheme === 'system' ? 'checked' : '' ?> onchange="this.form.submit()">
        <span class="theme-option-card"><?= icon('gear') ?><span>System</span></span>
      </label>
    </div>
    <p class="help" style="margin-top: var(--space-4)">
      "System" follows your device's light/dark preference automatically. You can also toggle quickly from the sun/moon icon in the top bar.
    </p>
  </form>
</div>
