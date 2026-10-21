<?php
/**
 * Structure (drag/indent/outdent) + inline Add/Edit form, both on one page —
 * ?edit={id} loads an item into the form on the right. Parent/child is set
 * only via the Structure list (indent/outdent + Save Structure), never via
 * the item form itself.
 * @var string $location
 * @var array<string,string> $locations
 * @var array $menu
 * @var array $items top-level items, each with a 'children' array (one level deep)
 * @var array|null $editingItem
 * @var array<string,string> $builtInRoutes path => label
 * @var array $customPages published, non-system pages: id/title/slug
 */
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($editingItem[$key] ?? $default);

// Which "Link to" option should be pre-selected, and is it a free-form URL
// that doesn't match anything in the picker (so it falls back to "Custom URL")?
$currentUrl = $field('url');
$isKnownTarget = $currentUrl === '' || array_key_exists($currentUrl, $builtInRoutes)
    || in_array($currentUrl, array_map(static fn (array $p) => '/page/' . $p['slug'], $customPages), true);
$currentTarget = $currentUrl === '' ? 'none' : ($isKnownTarget ? $currentUrl : 'custom');

/** Flatten one level deep into draggable rows, each carrying its depth. */
$rows = [];
foreach ($items as $top) {
    $rows[] = $top + ['depth' => 0];
    foreach ($top['children'] as $child) {
        $rows[] = $child + ['depth' => 1];
    }
}
?>
<div class="admin-page-header">
  <div>
    <h1>Menus</h1>
    <p>Manage the links shown in each navigation area of the public website.</p>
  </div>
</div>

<nav class="menu-location-pills" aria-label="Menu locations">
  <?php foreach ($locations as $slug => $label): ?>
    <a class="menu-location-pill<?= $slug === $location ? ' is-active' : '' ?>"
       href="<?= e(route('admin.menus.location', ['location' => $slug])) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="menu-builder-grid">
  <div class="admin-card">
    <div class="admin-card-header"><h2>Structure</h2></div>

    <?php if ($rows === []): ?>
      <div class="empty-state">
        <?= icon('list') ?>
        <h3>This menu is empty</h3>
        <p>Add the first item using the form on the right.</p>
      </div>
    <?php else: ?>
      <p class="help" style="margin-bottom: var(--space-3);">
        Drag <strong>⠿</strong> to reorder &middot; <strong>⇥</strong> makes an item a sub-item of the one above &middot;
        <strong>⇤</strong> moves it back out &middot; then press <strong>Save Structure</strong>.
      </p>

      <form method="post" action="<?= e(route('admin.menus.reorder', ['location' => $location])) ?>" id="structure-form">
        <?= csrf_field() ?>
        <div id="menu-tree">
          <?php foreach ($rows as $row): ?>
            <div class="menu-row<?= $row['is_active'] ? '' : ' is-inactive' ?>" draggable="true"
                 data-id="<?= (int) $row['id'] ?>" data-depth="<?= (int) $row['depth'] ?>"
                 style="margin-left: <?= (int) $row['depth'] * 28 ?>px;">
              <span class="menu-row-grip" title="Drag to reorder" aria-hidden="true">⠿</span>
              <span class="menu-row-info">
                <span class="menu-row-title"><?php if ($row['icon']): ?><?= icon($row['icon']) ?> <?php endif; ?><?= e($row['title']) ?></span>
                <span class="menu-row-meta">
                  <?= e($row['url'] !== '' && $row['url'] !== null ? $row['url'] : 'No link (dropdown heading)') ?>
                  <?= $row['target'] === '_blank' ? ' &middot; new tab' : '' ?>
                  <?= $row['is_active'] ? '' : ' &middot; hidden' ?>
                </span>
              </span>
              <button type="button" class="menu-row-btn menu-outdent" title="Move out a level" aria-label="Move <?= e($row['title']) ?> out one level">⇤</button>
              <button type="button" class="menu-row-btn menu-indent" title="Make sub-item" aria-label="Make <?= e($row['title']) ?> a sub-item">⇥</button>
              <a class="menu-row-btn menu-row-link" href="<?= e(route('admin.menus.location', ['location' => $location])) ?>?edit=<?= (int) $row['id'] ?>">Edit</a>
              <button type="button" class="menu-row-btn menu-row-delete" data-id="<?= (int) $row['id'] ?>" data-label="<?= e($row['title']) ?>" aria-label="Delete <?= e($row['title']) ?>">✕</button>
              <input type="hidden" name="order[]" value="<?= (int) $row['id'] ?>">
              <input type="hidden" name="depth[]" value="<?= (int) $row['depth'] ?>">
            </div>
          <?php endforeach; ?>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: var(--space-4);">Save Structure</button>
        <span id="structure-dirty" class="badge badge-warning" style="display:none; margin-left: var(--space-3);">Unsaved changes</span>
      </form>
    <?php endif; ?>
  </div>

  <div class="admin-card">
    <div class="admin-card-header">
      <h2><?= $editingItem === null ? 'Add Item' : 'Edit Item' ?></h2>
      <?php if ($editingItem !== null): ?>
        <a href="<?= e(route('admin.menus.location', ['location' => $location])) ?>">Cancel</a>
      <?php endif; ?>
    </div>

    <form method="post"
          action="<?= e($editingItem === null ? route('admin.menus.store', ['location' => $location]) : route('admin.menus.update', ['location' => $location, 'id' => $editingItem['id']])) ?>">
      <?= csrf_field() ?>

      <div class="field">
        <label for="f-title">Label</label>
        <input class="inp" type="text" id="f-title" name="title" value="<?= e($field('title')) ?>" placeholder="Admissions" required>
        <?php if (has_error('title')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('title')) ?></p><?php endif; ?>
        <p class="help">The text visitors see. Keep it to one or two words.</p>
      </div>

      <div class="field">
        <label for="link-target">Link to</label>
        <select class="inp" id="link-target" name="link_target">
          <optgroup label="Built-in pages">
            <?php foreach ($builtInRoutes as $path => $label): ?>
              <option value="<?= e($path) ?>" <?= $currentTarget === $path ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </optgroup>
          <?php if ($customPages !== []): ?>
            <optgroup label="Your pages">
              <?php foreach ($customPages as $p): ?>
                <?php $pagePath = '/page/' . $p['slug']; ?>
                <option value="<?= e($pagePath) ?>" <?= $currentTarget === $pagePath ? 'selected' : '' ?>><?= e($p['title']) ?></option>
              <?php endforeach; ?>
            </optgroup>
          <?php endif; ?>
          <optgroup label="Other">
            <option value="none" <?= $currentTarget === 'none' ? 'selected' : '' ?>>Dropdown heading (no link)</option>
            <option value="custom" <?= $currentTarget === 'custom' ? 'selected' : '' ?>>Custom URL&hellip;</option>
          </optgroup>
        </select>
        <p class="help">Choosing a page fills the label in for you when it's still blank.</p>
      </div>

      <div class="field" id="custom-url-group" <?= $currentTarget === 'custom' ? '' : 'style="display:none"' ?>>
        <label for="f-custom-url">Custom URL</label>
        <input class="inp" type="text" id="f-custom-url" name="custom_url" value="<?= $currentTarget === 'custom' ? e($currentUrl) : '' ?>" placeholder="https://&hellip; or /some/path">
        <p class="help">A full address, or a path on this site starting with a slash.</p>
      </div>

      <div class="field-grid">
        <div class="field">
          <label for="f-icon">Icon Name (optional)</label>
          <input class="inp" type="text" id="f-icon" name="icon" value="<?= e($field('icon')) ?>" placeholder="e.g. mortarboard">
        </div>
        <div class="field">
          <label for="f-target">Opens In</label>
          <select class="inp" id="f-target" name="target">
            <option value="_self" <?= $field('target', '_self') === '_self' ? 'selected' : '' ?>>Same tab</option>
            <option value="_blank" <?= $field('target') === '_blank' ? 'selected' : '' ?>>New tab</option>
          </select>
        </div>
      </div>

      <div class="field checkbox-row">
        <input type="checkbox" id="f-active" name="is_active" value="1" <?= ($editingItem === null || (int) ($editingItem['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
        <label for="f-active" style="margin:0;font-weight:400">Visible on the website</label>
      </div>

      <div class="field" style="display:flex; gap: var(--space-3); align-items:center;">
        <button type="submit" class="btn btn-primary"><?= $editingItem === null ? 'Add Item' : 'Update Item' ?></button>
        <?php if ($editingItem !== null): ?>
          <button type="submit" form="delete-item-form" class="btn btn-secondary" style="color:var(--danger-700)">Delete</button>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<?php if ($editingItem !== null): ?>
  <form id="delete-item-form" method="post" action="<?= e(route('admin.menus.delete', ['location' => $location, 'id' => $editingItem['id']])) ?>" style="display:none"
        data-confirm="<?= e('Delete "' . $editingItem['title'] . '" and any sub-items beneath it?') ?>">
    <?= csrf_field() ?>
  </form>
<?php endif; ?>

<form id="row-delete-form" method="post" action="" style="display:none">
  <?= csrf_field() ?>
</form>

<script>
(function () {
  "use strict";

  var tree = document.getElementById('menu-tree');
  var deleteForm = document.getElementById('row-delete-form');
  // A delete URL with a literal placeholder id (0), swapped for the real one per click.
  var deleteUrlTemplate = <?= json_encode(route('admin.menus.delete', ['location' => $location, 'id' => 0])) ?>;

  document.querySelectorAll('.menu-row-delete').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var label = btn.getAttribute('data-label') || 'this item';
      if (!confirm('Delete "' + label + '" and any sub-items beneath it?')) return;
      deleteForm.action = deleteUrlTemplate.replace(/\/0\/delete$/, '/' + btn.getAttribute('data-id') + '/delete');
      deleteForm.submit();
    });
  });

  if (!tree) return;

  var INDENT = 28;
  var dragging = null;
  var dirty = document.getElementById('structure-dirty');

  var rows = function () { return Array.prototype.slice.call(tree.querySelectorAll('.menu-row')); };
  var depthOf = function (row) { return parseInt(row.getAttribute('data-depth') || '0', 10); };
  var setDepth = function (row, depth) {
    row.setAttribute('data-depth', String(depth));
    row.style.marginLeft = (depth * INDENT) + 'px';
    row.querySelector('input[name="depth[]"]').value = String(depth);
  };

  // Only two levels exist on the public site (top level + one dropdown level).
  var clamp = function () {
    var previous = -1;
    rows().forEach(function (row) {
      var depth = Math.max(0, Math.min(depthOf(row), previous + 1, 1));
      setDepth(row, depth);
      previous = depth;
    });
  };

  var markDirty = function () { if (dirty) dirty.style.display = ''; };

  rows().forEach(function (row) {
    row.addEventListener('dragstart', function () {
      dragging = row;
      setTimeout(function () { row.classList.add('is-dragging'); }, 0);
    });
    row.addEventListener('dragend', function () {
      row.classList.remove('is-dragging');
      dragging = null;
      clamp();
      markDirty();
    });
    row.querySelector('.menu-indent').addEventListener('click', function () {
      setDepth(row, depthOf(row) + 1);
      clamp();
      markDirty();
    });
    row.querySelector('.menu-outdent').addEventListener('click', function () {
      setDepth(row, Math.max(0, depthOf(row) - 1));
      clamp();
      markDirty();
    });
  });

  tree.addEventListener('dragover', function (e) {
    e.preventDefault();
    if (!dragging) return;
    var before = null;
    rows().forEach(function (row) {
      if (row === dragging || before !== null) return;
      var box = row.getBoundingClientRect();
      if (e.clientY < box.top + box.height / 2) before = row;
    });
    if (before) {
      tree.insertBefore(dragging, before);
    } else {
      tree.appendChild(dragging);
    }
  });

  var form = document.getElementById('structure-form');
  if (form) form.addEventListener('submit', clamp);

  // Fill the label from the chosen page, and reveal the custom URL field.
  var select = document.getElementById('link-target');
  var customGroup = document.getElementById('custom-url-group');
  var label = document.getElementById('f-title');
  if (select && customGroup) {
    select.addEventListener('change', function () {
      customGroup.style.display = select.value === 'custom' ? '' : 'none';
      if (select.value === 'custom') {
        document.getElementById('f-custom-url').focus();
        return;
      }
      if (label && label.value.trim() === '' && select.value !== 'none') {
        label.value = select.options[select.selectedIndex].text;
      }
    });
  }
})();
</script>
