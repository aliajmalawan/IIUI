<?php
/** @var array $settings */
$activeTab = 'sections';
$oldInput = flash('_old_input', []);
?>
<div class="admin-page-header">
  <div>
    <h1>Homepage Builder</h1>
    <p>Headings and small labels for each homepage section. Leave a field empty to use the built-in wording.</p>
  </div>
</div>

<?php require VIEWS_PATH . '/partials/admin/_homepage-tabs.php'; ?>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-card" style="max-width: 640px">
  <form method="post" action="<?= e(route('admin.homepage.sections.update')) ?>">
    <?= csrf_field() ?>

    <?php foreach ($settings as $setting):
      $key = $setting['key_name'];
      $value = array_key_exists($key, $oldInput) ? $oldInput[$key] : (string) $setting['value'];
    ?>
      <div class="field">
        <label for="f-<?= e($key) ?>"><?= e($setting['label']) ?></label>
        <input class="inp" type="text" id="f-<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($value) ?>" maxlength="120">
        <?php if (!empty($setting['description'])): ?>
          <p class="help"><?= e((string) $setting['description']) ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary"><?= icon('check-circle') ?> Save Changes</button>
  </form>
</div>