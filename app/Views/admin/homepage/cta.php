<?php
/** @var array $settings */
$activeTab = 'cta';
$oldInput = flash('_old_input', []);
?>
<div class="admin-page-header">
  <div>
    <h1>Homepage Builder</h1>
    <p>The Admission call-to-action banner shown on the homepage.</p>
  </div>
</div>

<?php require VIEWS_PATH . '/partials/admin/_homepage-tabs.php'; ?>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-card" style="max-width: 640px">
  <form method="post" action="<?= e(route('admin.homepage.cta.update')) ?>">
    <?= csrf_field() ?>

    <?php foreach ($settings as $setting):
      $key = $setting['key_name'];
      $type = $setting['type'];
      $value = array_key_exists($key, $oldInput) ? $oldInput[$key] : (string) $setting['value'];
    ?>
      <div class="field">
        <label for="f-<?= e($key) ?>"><?= e($setting['label']) ?></label>

        <?php if ($type === 'textarea'): ?>
          <textarea class="inp" style="height:auto;padding:10px 12px" rows="3" id="f-<?= e($key) ?>" name="<?= e($key) ?>"><?= e($value) ?></textarea>
        <?php else: ?>
          <input class="inp" type="text" id="f-<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($value) ?>">
        <?php endif; ?>

        <?php if (has_error($key)): ?>
          <p class="help" style="color: var(--danger-700)"><?= e((string) error($key)) ?></p>
        <?php elseif (!empty($setting['description'])): ?>
          <p class="help"><?= e((string) $setting['description']) ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary">Save Call To Action</button>
  </form>
</div>
