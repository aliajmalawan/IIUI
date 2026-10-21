<?php
/**
 * @var string $group
 * @var array<string,string> $groups
 * @var array $settings
 */
$oldInput = flash('_old_input', []);
?>
<div class="admin-page-header">
  <div>
    <h1>Website Settings</h1>
    <p>Site-wide configuration &mdash; visible to every visitor of the public website.</p>
  </div>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<nav class="settings-tabs" aria-label="Settings groups">
  <?php foreach ($groups as $slug => $label): ?>
    <a class="settings-tab<?= $slug === $group ? ' is-active' : '' ?>"
       href="<?= e(route('admin.settings.group', ['group' => $slug])) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<div class="admin-card" style="max-width: 720px">
  <form method="post" action="<?= e(route('admin.settings.group.update', ['group' => $group])) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <?php if ($settings === []): ?>
      <div class="empty-state">
        <?= icon('gear') ?>
        <h3>Nothing to configure here yet</h3>
        <p>This group has no settings defined.</p>
      </div>
    <?php endif; ?>

    <?php foreach ($settings as $setting):
      $key = $setting['key_name'];
      $type = $setting['type'];
      $value = array_key_exists($key, $oldInput) ? $oldInput[$key] : (string) $setting['value'];
      $options = $setting['options'] !== null ? json_decode((string) $setting['options'], true) : null;
    ?>
      <div class="field">
        <label for="f-<?= e($key) ?>"><?= e($setting['label']) ?></label>

        <?php if ($type === 'image'): ?>
          <div class="upload-row">
            <div class="upload-preview">
              <?php if ($value !== '' && is_file(PUBLIC_PATH . $value)): ?>
                <img src="<?= e(base_url(ltrim($value, '/'))) ?>" alt="<?= e($setting['label']) ?>">
              <?php else: ?>
                <?= icon('grid-1x2') ?>
              <?php endif; ?>
            </div>
            <div>
              <input type="file" id="f-<?= e($key) ?>" name="<?= e($key) ?>" accept="image/png,image/jpeg,image/webp">
              <p class="upload-hint">JPG, PNG, or WEBP &middot; max 2&nbsp;MB. Leave empty to keep the current image.</p>
            </div>
          </div>

        <?php elseif ($type === 'textarea'): ?>
          <textarea class="inp" style="height:auto;padding:10px 12px" rows="3" id="f-<?= e($key) ?>" name="<?= e($key) ?>"><?= e($value) ?></textarea>

        <?php elseif ($type === 'select' && is_array($options)): ?>
          <select class="inp" id="f-<?= e($key) ?>" name="<?= e($key) ?>">
            <?php foreach ($options as $opt): ?>
              <option value="<?= e($opt['value']) ?>" <?= $value === $opt['value'] ? 'selected' : '' ?>><?= e($opt['label']) ?></option>
            <?php endforeach; ?>
          </select>

        <?php elseif ($type === 'boolean'): ?>
          <div class="checkbox-row">
            <input type="checkbox" id="f-<?= e($key) ?>" name="<?= e($key) ?>" value="1" <?= $value === '1' ? 'checked' : '' ?>>
            <label for="f-<?= e($key) ?>" style="margin:0;font-weight:400"><?= e((string) ($setting['description'] ?? 'Enabled')) ?></label>
          </div>

        <?php else: ?>
          <input class="inp" type="<?= e($type === 'number' ? 'number' : ($type === 'email' ? 'email' : ($type === 'url' ? 'url' : 'text'))) ?>"
                 id="f-<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($value) ?>">
        <?php endif; ?>

        <?php if (has_error($key)): ?>
          <p class="help" style="color: var(--danger-700)"><?= e((string) error($key)) ?></p>
        <?php elseif (!empty($setting['description']) && $type !== 'boolean'): ?>
          <p class="help"><?= e((string) $setting['description']) ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <?php if ($settings !== []): ?>
      <button type="submit" class="btn btn-primary">Save <?= e($groups[$group]) ?> Settings</button>
    <?php endif; ?>
  </form>
</div>
