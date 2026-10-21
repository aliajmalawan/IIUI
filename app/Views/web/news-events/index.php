<?php
/**
 * @var array $items
 * @var array $categories
 * @var string $activeFilter
 * @var int $page
 * @var int $lastPage
 */
$qs = static function (array $overrides) use ($activeFilter): string {
    $params = array_filter(['filter' => $overrides['filter'] ?? $activeFilter, 'page' => $overrides['page'] ?? null], static fn ($v) => $v !== null && $v !== '');

    return $params === [] ? '' : '?' . http_build_query($params);
};
?>
<section class="hero-minimal">
  <div class="hero-khatam" aria-hidden="true"></div>
  <div class="container">
    <p class="hero-eyebrow">Mardan Campus</p>
    <h1 class="hero-title">News &amp; Events</h1>
    <p class="hero-lead">Updates, achievements, announcements, and everything happening on campus — in one place.</p>
  </div>
</section>

<div class="container section-block">
  <div class="filter-pills">
    <a class="filter-pill<?= $activeFilter === 'all' ? ' is-active' : '' ?>" href="<?= e(route('news.index') . $qs(['filter' => 'all', 'page' => null])) ?>">All</a>
    <?php foreach ($categories as $cat): ?>
      <a class="filter-pill<?= $activeFilter === $cat['slug'] ? ' is-active' : '' ?>"
         href="<?= e(route('news.index') . $qs(['filter' => $cat['slug'], 'page' => null])) ?>"><?= e($cat['name']) ?></a>
    <?php endforeach; ?>
    <a class="filter-pill<?= $activeFilter === 'events' ? ' is-active' : '' ?>" href="<?= e(route('news.index') . $qs(['filter' => 'events', 'page' => null])) ?>">Events</a>
  </div>

  <?php if ($items === []): ?>
    <div class="empty-block">
      <p>Nothing published in this category yet. Please check back soon.</p>
    </div>
  <?php else: ?>
    <h2 class="visually-hidden">Latest news and events</h2>
    <div class="news-grid">
      <?php foreach ($items as $item): ?>
        <a class="news-card" href="<?= e($item['url']) ?>">
          <div class="news-card-cover">
            <?php if ($item['image'] && is_file(PUBLIC_PATH . $item['image'])): ?>
              <img src="<?= e(thumb_url($item['image'])) ?>" alt="<?= e($item['title']) ?>" loading="lazy" decoding="async">
            <?php else: ?>
              <div class="news-card-placeholder"><?= icon($item['type'] === 'event' ? 'calendar-event' : 'newspaper') ?></div>
            <?php endif; ?>
            <?php if ($item['is_pinned']): ?><span class="news-card-pin"><?= icon('pin-angle-fill') ?></span><?php endif; ?>
          </div>
          <div class="news-card-body">
            <span class="news-card-tag<?= $item['type'] === 'event' ? ' news-card-tag--event' : '' ?>">
              <?= icon($item['type'] === 'event' ? 'calendar-event' : 'newspaper') ?> <?= e($item['tag']) ?>
            </span>
            <h3><?= e($item['title']) ?></h3>
            <?php if ($item['excerpt'] !== ''): ?><p><?= e(mb_strimwidth($item['excerpt'], 0, 110, '…')) ?></p><?php endif; ?>
            <span class="news-card-meta"><?= e($item['date']) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($lastPage > 1): ?>
      <nav class="pagination" aria-label="Pagination">
        <span>Page <?= (int) $page ?> of <?= (int) $lastPage ?></span>
        <div class="pagination-links">
          <?php if ($page > 1): ?>
            <a href="<?= e(route('news.index') . $qs(['page' => (string) ($page - 1)])) ?>" aria-label="Previous page" rel="prev"><?= icon('chevron-left') ?></a>
          <?php endif; ?>
          <?php for ($p = max(1, $page - 2); $p <= min($lastPage, $page + 2); $p++): ?>
            <?php if ($p === $page): ?>
              <span class="is-current" aria-current="page"><?= $p ?></span>
            <?php else: ?>
              <a href="<?= e(route('news.index') . $qs(['page' => (string) $p])) ?>"><?= $p ?></a>
            <?php endif; ?>
          <?php endfor; ?>
          <?php if ($page < $lastPage): ?>
            <a href="<?= e(route('news.index') . $qs(['page' => (string) ($page + 1)])) ?>" aria-label="Next page" rel="next"><?= icon('chevron-right') ?></a>
          <?php endif; ?>
        </div>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</div>
