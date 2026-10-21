<?php
/** @var array|null $post */
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($post[$key] ?? $default);
$publishedAtValue = $old['published_at'] ?? (
    !empty($post['published_at']) ? date('Y-m-d\TH:i', strtotime((string) $post['published_at'])) : ''
);
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($post === null ? 'Write Blog Post' : 'Edit Blog Post') ?></h1>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.blog')) ?>"><?= icon('chevron-left') ?> Back to Blog</a>
</div>

<form method="post"
      action="<?= e($post === null ? route('admin.blog.store') : route('admin.blog.update', ['id' => $post['id']])) ?>"
      enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="news-form-grid">
    <div>
      <div class="admin-card">
        <div class="field">
          <label for="f-title">Title</label>
          <input class="inp" type="text" id="f-title" name="title" value="<?= e($field('title')) ?>" required>
          <?php if (has_error('title')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('title')) ?></p><?php endif; ?>
        </div>

        <div class="field">
          <label for="f-slug">URL Slug (optional)</label>
          <input class="inp" type="text" id="f-slug" name="slug" value="<?= e($field('slug')) ?>" placeholder="auto-generated from the title">
          <p class="help">Leave blank to auto-generate. Shown as <code>/blog/your-slug</code>.</p>
        </div>

        <div class="field">
          <label for="f-excerpt">Excerpt (optional)</label>
          <textarea class="inp" style="height:auto;padding:10px 12px" rows="2" id="f-excerpt" name="excerpt"
                     placeholder="Short summary shown on listing cards"><?= e($field('excerpt')) ?></textarea>
        </div>

        <div class="field">
          <label for="content">Content</label>
          <textarea id="content" name="content"><?= e($field('content')) ?></textarea>
          <?php if (has_error('content')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('content')) ?></p><?php endif; ?>
        </div>
      </div>

      <div class="admin-card">
        <h3 class="card-subhead">SEO (optional)</h3>
        <div class="field">
          <label for="f-meta-title">Meta Title</label>
          <input class="inp" type="text" id="f-meta-title" name="meta_title" value="<?= e($field('meta_title')) ?>">
        </div>
        <div class="field">
          <label for="f-meta-desc">Meta Description</label>
          <textarea class="inp" style="height:auto;padding:10px 12px" rows="2" id="f-meta-desc" name="meta_description"><?= e($field('meta_description')) ?></textarea>
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><?= $post === null ? 'Create Post' : 'Save Changes' ?></button>
    </div>

    <aside>
      <div class="admin-card">
        <h3 class="card-subhead">Publishing</h3>
        <div class="field">
          <label for="f-status">Status</label>
          <select class="inp" id="f-status" name="status">
            <?php foreach (['draft' => 'Draft', 'published' => 'Published', 'scheduled' => 'Scheduled', 'archived' => 'Archived'] as $val => $label): ?>
              <option value="<?= e($val) ?>" <?= $field('status', 'draft') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="f-published-at">Publish Date/Time</label>
          <input class="inp" type="datetime-local" id="f-published-at" name="published_at" value="<?= e($publishedAtValue) ?>">
          <p class="help">Required (future date) when status is Scheduled. Defaults to now when Published.</p>
          <?php if (has_error('published_at')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('published_at')) ?></p><?php endif; ?>
        </div>
        <div class="field checkbox-row">
          <input type="checkbox" id="f-featured" name="is_featured" value="1" <?= (int) $field('is_featured', '0') === 1 ? 'checked' : '' ?>>
          <label for="f-featured" style="margin:0;font-weight:400">Featured</label>
        </div>
        <div class="field checkbox-row">
          <input type="checkbox" id="f-pinned" name="is_pinned" value="1" <?= (int) $field('is_pinned', '0') === 1 ? 'checked' : '' ?>>
          <label for="f-pinned" style="margin:0;font-weight:400">Pinned to top</label>
        </div>
      </div>

      <div class="admin-card">
        <h3 class="card-subhead">Featured Image</h3>
        <div class="upload-preview" style="width:100%;height:140px;margin-bottom:var(--space-3)">
          <?php if ($post !== null && $post['featured_image'] && is_file(PUBLIC_PATH . $post['featured_image'])): ?>
            <img src="<?= e(base_url(ltrim($post['featured_image'], '/'))) ?>" alt="">
          <?php else: ?>
            <?= icon('pencil-square') ?>
          <?php endif; ?>
        </div>
        <input type="file" id="f-image" name="featured_image" accept="image/png,image/jpeg,image/webp">
        <?php if (has_error('featured_image')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('featured_image')) ?></p><?php endif; ?>
      </div>
    </aside>
  </div>
</form>

<script>window.TINYMCE_ASSET_BASE = <?= json_encode(asset('vendor/tinymce')) ?>;</script>
<script src="<?= e(asset('vendor/tinymce/tinymce.min.js')) ?>"></script>
<script src="<?= e(asset('js/news-editor.js')) ?>"></script>
