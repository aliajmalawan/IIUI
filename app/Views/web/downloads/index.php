<?php
/**
 * @var array $downloads
 * @var array $categories
 * @var string|null $activeCategory
 */
$fileIcon = static function (string $fileName): string {
    return match (strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION))) {
        'pdf'  => 'file-earmark-pdf',
        'docx' => 'file-earmark-word',
        'xlsx' => 'file-earmark-excel',
        default => 'file-earmark-arrow-down',
    };
};
$fmtSize = static function (int $bytes): string {
    if ($bytes >= 1024 * 1024) {
        return round($bytes / (1024 * 1024), 1) . ' MB';
    }
    return round($bytes / 1024, 0) . ' KB';
};
?>
<section class="hero-minimal">
  <div class="hero-khatam" aria-hidden="true"></div>
  <div class="container">
    <p class="hero-eyebrow">Mardan Campus</p>
    <h1 class="hero-title">Downloads</h1>
    <p class="hero-lead">Forms, circulars, and documents available for download.</p>
  </div>
</section>

<div class="container section-block">
  <?php if ($categories !== []): ?>
    <div class="filter-pills">
      <a class="filter-pill<?= $activeCategory === null ? ' is-active' : '' ?>" href="<?= e(route('downloads.index')) ?>">All</a>
      <?php foreach ($categories as $cat): ?>
        <a class="filter-pill<?= $activeCategory === $cat ? ' is-active' : '' ?>"
           href="<?= e(route('downloads.index')) ?>?category=<?= e(rawurlencode($cat)) ?>"><?= e($cat) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($downloads === []): ?>
    <div class="empty-block">
      <p>No downloads available yet. Please check back soon.</p>
    </div>
  <?php else: ?>
    <div class="download-list">
      <?php foreach ($downloads as $dl): ?>
        <div class="download-row">
          <div class="download-row-icon"><?= icon($fileIcon($dl['file_name'])) ?></div>
          <div class="download-row-body">
            <h3><?= e($dl['title']) ?></h3>
            <?php if ($dl['description']): ?><p><?= e($dl['description']) ?></p><?php endif; ?>
            <span class="download-row-meta">
              <?= e(strtoupper((string) pathinfo($dl['file_name'], PATHINFO_EXTENSION))) ?>
              &middot; <?= e($fmtSize((int) $dl['file_size'])) ?>
              <?php if ($dl['category']): ?> &middot; <?= e($dl['category']) ?><?php endif; ?>
            </span>
          </div>
          <a class="btn btn-accent download-row-btn" href="<?= e(route('downloads.file', ['id' => $dl['id']])) ?>">
            <?= icon('download') ?> Download
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
