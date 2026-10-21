<?php
/**
 * @var array $events
 * @var array $categories
 * @var string|null $activeCategory
 * @var string $when
 * @var int $page
 * @var int $lastPage
 */
$statusLabel = ['cancelled' => 'Cancelled', 'completed' => 'Completed'];
$qs = static function (array $overrides) use ($when, $activeCategory): string {
    $params = [
        'when'     => $overrides['when'] ?? $when,
        'category' => $overrides['category'] ?? $activeCategory,
    ];
    if (isset($overrides['page'])) {
        $params['page'] = $overrides['page'];
    }
    $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');

    return $params === [] ? '' : '?' . http_build_query($params);
};
?>
<section class="hero-minimal">
  <div class="hero-khatam" aria-hidden="true"></div>
  <div class="container">
    <p class="hero-eyebrow">Mardan Campus</p>
    <h1 class="hero-title">Events</h1>
    <p class="hero-lead">Sports days, ceremonies, academic events, and campus activities.</p>
  </div>
</section>

<div class="container section-block">
  <div class="filter-pills">
    <a class="filter-pill<?= $when === 'upcoming' ? ' is-active' : '' ?>" href="<?= e(route('events.index') . $qs(['when' => 'upcoming'])) ?>">Upcoming</a>
    <a class="filter-pill<?= $when === 'past' ? ' is-active' : '' ?>" href="<?= e(route('events.index') . $qs(['when' => 'past'])) ?>">Past Events</a>
  </div>

  <?php if ($categories !== []): ?>
    <div class="filter-pills">
      <a class="filter-pill<?= $activeCategory === null ? ' is-active' : '' ?>" href="<?= e(route('events.index') . $qs(['category' => ''])) ?>">All Categories</a>
      <?php foreach ($categories as $cat): ?>
        <a class="filter-pill<?= $activeCategory === $cat ? ' is-active' : '' ?>"
           href="<?= e(route('events.index') . $qs(['category' => $cat])) ?>"><?= e($cat) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($events === []): ?>
    <div class="empty-block">
      <p><?= $when === 'past' ? 'No past events to show.' : 'No upcoming events scheduled yet. Please check back soon.' ?></p>
    </div>
  <?php else: ?>
    <h2 class="visually-hidden">Campus events</h2>
    <div class="event-grid">
      <?php foreach ($events as $event): ?>
        <a class="event-card" href="<?= e(route('events.show', ['slug' => $event['slug']])) ?>">
          <div class="event-card-cover">
            <?php if ($event['banner'] && is_file(PUBLIC_PATH . $event['banner'])): ?>
              <img src="<?= e(thumb_url($event['banner'])) ?>" alt="<?= e($event['title']) ?>" loading="lazy" decoding="async">
            <?php else: ?>
              <div class="event-card-placeholder"><?= icon('calendar-event') ?></div>
            <?php endif; ?>
            <div class="event-date-badge">
              <span class="event-date-day"><?= e(date('d', strtotime((string) $event['start_datetime']))) ?></span>
              <span class="event-date-month"><?= e(date('M', strtotime((string) $event['start_datetime']))) ?></span>
            </div>
            <?php if (isset($statusLabel[$event['status']])): ?>
              <span class="event-status-tag event-status-<?= e($event['status']) ?>"><?= e($statusLabel[$event['status']]) ?></span>
            <?php endif; ?>
          </div>
          <div class="event-card-body">
            <?php if ($event['category']): ?><span class="event-card-tag"><?= e($event['category']) ?></span><?php endif; ?>
            <h3><?= e($event['title']) ?></h3>
            <?php if ($event['description']): ?><p><?= e(mb_strimwidth((string) $event['description'], 0, 100, '…')) ?></p><?php endif; ?>
            <span class="event-card-meta">
              <?= icon('clock') ?> <?= e(date('h:i A', strtotime((string) $event['start_datetime']))) ?>
              <?php if ($event['venue']): ?> &middot; <?= icon('geo-alt') ?> <?= e($event['venue']) ?><?php endif; ?>
            </span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($lastPage > 1): ?>
      <nav class="pagination" aria-label="Pagination">
        <span>Page <?= (int) $page ?> of <?= (int) $lastPage ?></span>
        <div class="pagination-links">
          <?php if ($page > 1): ?>
            <a href="<?= e(route('events.index') . $qs(['page' => (string) ($page - 1)])) ?>" aria-label="Previous page" rel="prev"><?= icon('chevron-left') ?></a>
          <?php endif; ?>
          <?php for ($p = max(1, $page - 2); $p <= min($lastPage, $page + 2); $p++): ?>
            <?php if ($p === $page): ?>
              <span class="is-current" aria-current="page"><?= $p ?></span>
            <?php else: ?>
              <a href="<?= e(route('events.index') . $qs(['page' => (string) $p])) ?>"><?= $p ?></a>
            <?php endif; ?>
          <?php endfor; ?>
          <?php if ($page < $lastPage): ?>
            <a href="<?= e(route('events.index') . $qs(['page' => (string) ($page + 1)])) ?>" aria-label="Next page" rel="next"><?= icon('chevron-right') ?></a>
          <?php endif; ?>
        </div>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</div>
