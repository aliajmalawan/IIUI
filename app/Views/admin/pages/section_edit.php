<?php
/**
 * @var array $page
 * @var array $section
 * @var array{label:string,fields:array<string,array<string,mixed>>} $schema
 * @var array<string,mixed> $content
 *
 * Field naming convention (see Admin\PageController::sanitizeFields/
 * sanitizeRepeater for the matching read side):
 *   - text/textarea/richtext/select  -> content[{key}]  or  content[{repeaterKey}][{i}][{subkey}]
 *   - media (file input)             -> media__{key}    or  media__{repeaterKey}__{i}__{subkey}
 *   - media (remove checkbox)        -> media_remove__{key}  / media_remove__{repeaterKey}__{i}__{subkey}
 * Media uses flat names everywhere (never content[...]) specifically so a
 * repeater's image fields never end up as PHP's nested $_FILES structure.
 */
$old = flash('_old_input', []);
$oldContent = is_array($old['content'] ?? null) ? $old['content'] : [];

$renderField = static function (string $key, array $def, mixed $value, string $namePrefix, string $errorKeyPrefix) use (&$renderField): void {
    $type = $def['type'];
    $label = $def['label'] ?? ucfirst(str_replace('_', ' ', $key));
    $inputId = 'f-' . str_replace(['[', ']', '.'], '-', $namePrefix . $key);
    ?>
    <div class="field">
      <label for="<?= e($inputId) ?>"><?= e($label) ?></label>
      <?php if ($type === 'textarea'): ?>
        <textarea class="inp" style="height:auto;padding:10px 12px" rows="3" id="<?= e($inputId) ?>" name="content<?= $namePrefix ?>[<?= e($key) ?>]"><?= e((string) $value) ?></textarea>
      <?php elseif ($type === 'richtext'): ?>
        <textarea class="inp" style="height:auto;padding:10px 12px" rows="8" id="content" name="content<?= $namePrefix ?>[<?= e($key) ?>]"><?= e((string) $value) ?></textarea>
      <?php elseif ($type === 'select'): ?>
        <select class="inp" id="<?= e($inputId) ?>" name="content<?= $namePrefix ?>[<?= e($key) ?>]">
          <?php foreach ((array) ($def['options'] ?? []) as $optValue => $optLabel): ?>
            <option value="<?= e((string) $optValue) ?>" <?= (string) $value === (string) $optValue ? 'selected' : '' ?>><?= e($optLabel) ?></option>
          <?php endforeach; ?>
        </select>
      <?php elseif ($type === 'media'):
        $mediaName = 'media__' . $errorKeyPrefix . $key;
        $removeName = 'media_remove__' . $errorKeyPrefix . $key;
        $hasImage = is_string($value) && $value !== '' && is_file(PUBLIC_PATH . $value);
      ?>
        <div class="upload-row">
          <div class="upload-preview" style="width:120px;height:72px">
            <?php if ($hasImage): ?>
              <img src="<?= e(base_url(ltrim($value, '/'))) ?>" alt="">
            <?php else: ?>
              <?= icon('image') ?>
            <?php endif; ?>
          </div>
          <div>
            <input type="file" id="<?= e($inputId) ?>" name="<?= e($mediaName) ?>" accept="image/png,image/jpeg,image/webp">
            <?php if (!empty($def['recommended_size'])): ?>
              <p class="upload-hint">JPG, PNG, or WEBP &middot; max 15&nbsp;MB. Recommended size: <strong><?= e($def['recommended_size']) ?></strong>.</p>
            <?php endif; ?>
            <?php if ($hasImage): ?>
              <label class="checkbox-row" style="margin-top:8px;font-weight:400">
                <input type="checkbox" name="<?= e($removeName) ?>" value="1">
                <span>Remove current image</span>
              </label>
            <?php endif; ?>
          </div>
        </div>
        <?php if (has_error($mediaName)): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error($mediaName)) ?></p><?php endif; ?>
      <?php else: ?>
        <input class="inp" type="text" id="<?= e($inputId) ?>" name="content<?= $namePrefix ?>[<?= e($key) ?>]" value="<?= e((string) $value) ?>">
      <?php endif; ?>
    </div>
    <?php
};

$sectionRepeaterExtraRows = 3;
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($schema['label']) ?></h1>
    <p>On <?= e($page['title']) ?></p>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.pages.sections', ['id' => $page['id']])) ?>"><?= icon('chevron-left') ?> Back to Sections</a>
</div>

<div class="admin-card" style="max-width: 720px">
  <form method="post" action="<?= e(route('admin.pages.sections.update', ['id' => $page['id'], 'sectionId' => $section['id']])) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <?php foreach ($schema['fields'] as $key => $def):
      if ($def['type'] === 'repeater'):
        $existingRows = is_array($oldContent[$key] ?? ($content[$key] ?? null)) ? ($oldContent[$key] ?? $content[$key]) : [];
        $rowCount = count($existingRows) + $sectionRepeaterExtraRows;
    ?>
      <h3 class="card-subhead" style="margin-top:var(--space-6)"><?= e($def['label'] ?? ucfirst($key)) ?></h3>
      <?php for ($i = 0; $i < $rowCount; $i++): $row = $existingRows[$i] ?? []; ?>
        <div class="admin-card" style="background:var(--bg-subtle);margin-bottom:var(--space-4)">
          <p class="help" style="margin-top:0">Item <?= $i + 1 ?><?= $i >= count($existingRows) ? ' (new)' : '' ?></p>
          <?php foreach ($def['fields'] as $subKey => $subDef):
            $renderField($subKey, $subDef, $row[$subKey] ?? '', "[{$key}][{$i}]", "{$key}__{$i}__");
          endforeach; ?>
        </div>
      <?php endfor; ?>
    <?php else:
        $renderField($key, $def, $oldContent[$key] ?? ($content[$key] ?? ''), '', '');
      endif;
    endforeach; ?>

    <div class="field">
      <label for="f-sort-order">Display Order</label>
      <input class="inp" type="number" id="f-sort-order" name="sort_order" value="<?= (int) $section['sort_order'] ?>" style="max-width:120px">
      <p class="help">Lower numbers show first.</p>
    </div>

    <button type="submit" class="btn btn-primary">Save Section</button>
  </form>
</div>

<?php
$hasRichText = false;
foreach ($schema['fields'] as $def) {
    if (($def['type'] ?? '') === 'richtext') {
        $hasRichText = true;
        break;
    }
}
?>
<?php if ($hasRichText): ?>
  <script>window.TINYMCE_ASSET_BASE = <?= json_encode(asset('vendor/tinymce')) ?>;</script>
  <script src="<?= e(asset('vendor/tinymce/tinymce.min.js')) ?>"></script>
  <script src="<?= e(asset('js/news-editor.js')) ?>"></script>
<?php endif; ?>
