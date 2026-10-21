<?php
/** @var array $page */
$hasHeroImage = $page['featured_image'] && is_file(PUBLIC_PATH . $page['featured_image']);
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => (string) ($old[$key] ?? $page[$key] ?? $default);
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($page['title']) ?></h1>
    <p><code>/<?= e($page['slug'] === 'about' || $page['slug'] === 'academics' || $page['slug'] === 'admissions' || $page['slug'] === 'contact' ? $page['slug'] : 'page/' . $page['slug']) ?></code></p>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.pages')) ?>"><?= icon('chevron-left') ?> Back to Pages</a>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-card" style="max-width: 640px">
  <div class="btn-row" style="margin-bottom:var(--space-5)">
    <a class="btn btn-primary" href="<?= e(route('admin.pages.sections', ['id' => $page['id']])) ?>"><?= icon('grid-1x2') ?> Manage Content Sections</a>
  </div>

  <form method="post" action="<?= e(route('admin.pages.update', ['id' => $page['id']])) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <h3 class="card-subhead">Header</h3>

    <div class="field">
      <label for="f-title">Heading</label>
      <input class="inp" type="text" id="f-title" name="title" value="<?= e($field('title')) ?>" required>
      <?php if (has_error('title')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('title')) ?></p><?php endif; ?>
    </div>

    <div class="field">
      <label for="f-excerpt">Subheading</label>
      <input class="inp" type="text" id="f-excerpt" name="excerpt" value="<?= e($field('excerpt')) ?>">
      <?php if (has_error('excerpt')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('excerpt')) ?></p><?php endif; ?>
    </div>

    <div class="field">
      <label for="f-hero-image">Banner Image</label>
      <div class="upload-row">
        <div class="upload-preview" style="width:160px;height:60px">
          <?php if ($hasHeroImage): ?>
            <img src="<?= e(base_url(ltrim($page['featured_image'], '/'))) ?>" alt="">
          <?php else: ?>
            <?= icon('image') ?>
          <?php endif; ?>
        </div>
        <div>
          <input type="file" id="f-hero-image" name="hero_image" accept="image/png,image/jpeg,image/webp">
          <p class="upload-hint">JPG, PNG, or WEBP &middot; max 15&nbsp;MB. Recommended size: <strong>1920&times;600px</strong> (wide banner) &mdash; a dark overlay is applied automatically so the white heading text stays readable.</p>
          <?php if ($hasHeroImage): ?>
            <label class="checkbox-row" style="margin-top:8px;font-weight:400">
              <input type="checkbox" name="hero_image_remove" value="1">
              <span>Remove current banner</span>
            </label>
          <?php endif; ?>
        </div>
      </div>
      <?php if (has_error('hero_image')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('hero_image')) ?></p><?php endif; ?>
      <?php if (!$hasHeroImage): ?><p class="help">No banner set &mdash; the page currently shows the plain colour header instead.</p><?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary" style="margin-top:var(--space-2)">Save Header</button>
  </form>
</div>
