<?php
/**
 * @var array $page
 * @var array $sections
 * @var array<string,array<string,mixed>> $sectionTypes
 */
?>
<div class="admin-page-header">
  <div>
    <h1>Sections — <?= e($page['title']) ?></h1>
    <p>Content shown below the header on this page. Order is the display order, top to bottom.</p>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.pages.edit', ['id' => $page['id']])) ?>"><?= icon('chevron-left') ?> Back to Header</a>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-card" style="margin-bottom:var(--space-6)">
  <h3 class="card-subhead">Add a Section</h3>
  <form method="post" action="<?= e(route('admin.pages.sections.add', ['id' => $page['id']])) ?>" style="display:flex;gap:var(--space-3);align-items:flex-end;flex-wrap:wrap">
    <?= csrf_field() ?>
    <div class="field" style="margin:0;flex:1;min-width:220px">
      <label for="f-section-type">Section Type</label>
      <select class="inp" id="f-section-type" name="section_type" required>
        <option value="">Choose a type&hellip;</option>
        <?php foreach ($sectionTypes as $typeKey => $type): ?>
          <option value="<?= e($typeKey) ?>"><?= e($type['label']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary"><?= icon('plus-lg') ?> Add Section</button>
  </form>
</div>

<div class="admin-table-wrap">
  <?php if ($sections === []): ?>
    <div class="empty-state">
      <?= icon('grid-1x2') ?>
      <h3>No sections yet</h3>
      <p>Add one above to start building this page's content.</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Order</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($sections as $section): ?>
          <tr>
            <td class="mono"><?= (int) $section['sort_order'] ?></td>
            <td><?= e($section['type_label']) ?></td>
            <td>
              <?php if ((int) $section['is_active'] === 1): ?>
                <span class="badge badge-success"><?= icon('check-circle') ?> Visible</span>
              <?php else: ?>
                <span class="badge badge-mute">Hidden</span>
              <?php endif; ?>
            </td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.pages.sections.edit', ['id' => $page['id'], 'sectionId' => $section['id']])) ?>"><?= icon('pencil-square') ?> Edit</a>
              <form method="post" action="<?= e(route('admin.pages.sections.toggle', ['id' => $page['id'], 'sectionId' => $section['id']])) ?>" style="display:inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-secondary btn-sm"><?= icon((int) $section['is_active'] === 1 ? 'eye' : 'eye') ?> <?= (int) $section['is_active'] === 1 ? 'Hide' : 'Show' ?></button>
              </form>
              <form method="post" action="<?= e(route('admin.pages.sections.delete', ['id' => $page['id'], 'sectionId' => $section['id']])) ?>" style="display:inline"
                    data-confirm="Delete this section? This cannot be undone.">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger-700)"><?= icon('trash') ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
