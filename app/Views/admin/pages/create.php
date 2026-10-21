<?php
$old = flash('_old_input', []);
?>
<div class="admin-page-header">
  <div>
    <h1>Add Page</h1>
    <p>Creates a new page at <code>/page/&lt;slug&gt;</code>. Add content sections next.</p>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.pages')) ?>"><?= icon('chevron-left') ?> Back to Pages</a>
</div>

<div class="admin-card" style="max-width: 560px">
  <form method="post" action="<?= e(route('admin.pages.store')) ?>">
    <?= csrf_field() ?>

    <div class="field">
      <label for="f-title">Title</label>
      <input class="inp" type="text" id="f-title" name="title" value="<?= e((string) ($old['title'] ?? '')) ?>" required>
      <?php if (has_error('title')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('title')) ?></p><?php endif; ?>
    </div>

    <div class="field">
      <label for="f-slug">URL Slug (optional)</label>
      <input class="inp" type="text" id="f-slug" name="slug" value="<?= e((string) ($old['slug'] ?? '')) ?>" placeholder="leave blank to generate from the title">
      <p class="help">Page will be reachable at <code>/page/&lt;slug&gt;</code>.</p>
      <?php if (has_error('slug')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('slug')) ?></p><?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary">Create Page</button>
  </form>
</div>
