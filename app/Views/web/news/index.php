<?php
/**
 * @var array $posts
 * @var array $categories
 * @var array|null $activeCategory
 * @var int $page
 * @var int $lastPage
 */
?>
<section class="hero-minimal">
  <div class="hero-khatam" aria-hidden="true"></div>
  <div class="container">
    <p class="hero-eyebrow">Mardan Campus</p>
    <h1 class="hero-title"><?= $activeCategory !== null ? e($activeCategory['name']) : 'News &amp; Announcements' ?></h1>
    <p class="hero-lead">Updates, achievements, and announcements from campus.</p>
  </div>
</section>

<div class="container section-block">
  <?php if ($categories !== []): ?>
    <div class="filter-pills">
      <a class="filter-pill<?= $activeCategory === null ? ' is-active' : '' ?>" href="<?= e(route('news.index')) ?>">All</a>
      <?php foreach ($categories as $cat): ?>
        <a class="filter-pill<?= ($activeCategory !== null && $activeCategory['id'] === $cat['id']) ? ' is-active' : '' ?>"
           href="<?= e(route('news.category', ['slug' => $cat['slug']])) ?>"><?= e($cat['name']) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($posts === []): ?>
    <div class="empty-block">
      <p>No articles published yet. Please check back soon.</p>
    </div>
  <?php else: ?>
    <h2 class="visually-hidden">Latest news and events</h2>
    <div class="news-grid">
      <?php foreach ($posts as $post): ?>
        <a class="news-card" href="<?= e(route('news.show', ['slug' => $post['slug']])) ?>">
          <div class="news-card-cover">
            <?php if ($post['featured_image'] && is_file(PUBLIC_PATH . $post['featured_image'])): ?>
              <img src="<?= e(thumb_url($post['featured_image'])) ?>" alt="<?= e($post['title']) ?>" loading="lazy" decoding="async">
            <?php else: ?>
              <div class="news-card-placeholder"><?= icon('newspaper') ?></div>
            <?php endif; ?>
            <?php if ($post['is_pinned']): ?><span class="news-card-pin"><?= icon('pin-angle-fill') ?></span><?php endif; ?>
          </div>
          <div class="news-card-body">
            <?php if ($post['category_name']): ?><span class="news-card-tag"><?= e($post['category_name']) ?></span><?php endif; ?>
            <h3><?= e($post['title']) ?></h3>
            <?php if ($post['excerpt']): ?><p><?= e(mb_strimwidth((string) $post['excerpt'], 0, 110, '…')) ?></p><?php endif; ?>
            <span class="news-card-meta"><?= e(date('d M Y', strtotime((string) $post['published_at']))) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($lastPage > 1): ?>
      <nav class="pagination" aria-label="Pagination">
        <span>Page <?= (int) $page ?> of <?= (int) $lastPage ?></span>
        <div class="pagination-links">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" aria-label="Previous page" rel="prev"><?= icon('chevron-left') ?></a>
          <?php endif; ?>
          <?php for ($p = max(1, $page - 2); $p <= min($lastPage, $page + 2); $p++): ?>
            <?php if ($p === $page): ?>
              <span class="is-current" aria-current="page"><?= $p ?></span>
            <?php else: ?>
              <a href="?page=<?= $p ?>"><?= $p ?></a>
            <?php endif; ?>
          <?php endfor; ?>
          <?php if ($page < $lastPage): ?>
            <a href="?page=<?= $page + 1 ?>" aria-label="Next page" rel="next"><?= icon('chevron-right') ?></a>
          <?php endif; ?>
        </div>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</div>
