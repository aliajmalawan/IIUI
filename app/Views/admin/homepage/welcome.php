<?php
/** @var array $settings */
$activeTab = 'welcome';
$oldInput = flash('_old_input', []);
?>
<div class="admin-page-header">
  <div>
    <h1>Homepage Builder</h1>
    <p>Principal and Owner welcome messages shown on the public homepage. The institution introduction text lives on the <a href="<?= e(route('admin.homepage.about')) ?>">About</a> tab.</p>
  </div>
</div>

<?php require VIEWS_PATH . '/partials/admin/_homepage-tabs.php'; ?>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-card" style="max-width: 640px">
  <form method="post" action="<?= e(route('admin.homepage.welcome.update')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <?php foreach ($settings as $setting):
      $key = $setting['key_name'];
      $type = $setting['type'];
      $value = array_key_exists($key, $oldInput) ? $oldInput[$key] : (string) $setting['value'];
    ?>
      <div class="field">
        <label for="f-<?= e($key) ?>"><?= e($setting['label']) ?></label>

        <?php if ($type === 'image'): ?>
          <div class="upload-row">
            <div class="upload-preview" style="width:72px;height:72px;border-radius:50%">
              <?php if ($value !== '' && is_file(PUBLIC_PATH . $value)): ?>
                <img src="<?= e(base_url(ltrim($value, '/'))) ?>" alt="">
              <?php else: ?>
                <?= icon('person-badge') ?>
              <?php endif; ?>
            </div>
            <div>
              <input type="file" id="f-<?= e($key) ?>" name="<?= e($key) ?>" accept="image/png,image/jpeg,image/webp">
              <p class="upload-hint">JPG, PNG, or WEBP &middot; max 2&nbsp;MB.</p>
              <?php if ($value !== '' && is_file(PUBLIC_PATH . $value)): ?>
                <label class="checkbox-row" style="margin-top:8px;font-weight:400">
                  <input type="checkbox" name="<?= e($key) ?>_remove" value="1">
                  <span>Remove current image</span>
                </label>
              <?php endif; ?>
            </div>
          </div>
        <?php elseif ($type === 'textarea'): ?>
          <textarea class="inp" style="height:auto;padding:10px 12px" rows="4" id="f-<?= e($key) ?>" name="<?= e($key) ?>"><?= e($value) ?></textarea>
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

    <button type="submit" class="btn btn-primary">Save Welcome Section</button>
  </form>
</div>
