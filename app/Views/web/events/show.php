<?php
/** @var array $event */
use App\Core\HtmlSanitizer;

$statusLabel = ['cancelled' => 'Cancelled', 'completed' => 'Completed'];
$isMultiDay = $event['end_datetime']
    && date('Y-m-d', strtotime((string) $event['start_datetime'])) !== date('Y-m-d', strtotime((string) $event['end_datetime']));
?>
<section class="hero-minimal">
  <div class="hero-khatam" aria-hidden="true"></div>
  <div class="container">
    <p class="hero-eyebrow">
      <?php if ($event['category']): ?><?= e($event['category']) ?> &middot; <?php endif; ?>
      <?= e(date('d M Y', strtotime((string) $event['start_datetime']))) ?>
      <?php if (isset($statusLabel[$event['status']])): ?>
        &middot; <span class="event-status-tag event-status-<?= e($event['status']) ?>"><?= e($statusLabel[$event['status']]) ?></span>
      <?php endif; ?>
    </p>
    <h1 class="hero-title"><?= e($event['title']) ?></h1>
    <?php if ($event['description']): ?><p class="hero-lead"><?= e($event['description']) ?></p><?php endif; ?>
  </div>
</section>

<div class="container section-block">
  <p><a class="back-link" href="<?= e(route('events.index')) ?>">&larr; Back to Events</a></p>

  <article class="news-article event-article">
    <?php if ($event['banner'] && is_file(PUBLIC_PATH . $event['banner'])): ?>
      <img class="news-article-cover" src="<?= e(base_url(ltrim($event['banner'], '/'))) ?>" alt="<?= e($event['title']) ?>" decoding="async" fetchpriority="high">
    <?php endif; ?>

    <div class="event-facts">
      <div class="event-fact">
        <?= icon('calendar-event') ?>
        <div>
          <strong><?= e(date('l, d F Y', strtotime((string) $event['start_datetime']))) ?></strong>
          <?php if ($isMultiDay): ?>
            <span>to <?= e(date('l, d F Y', strtotime((string) $event['end_datetime']))) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <div class="event-fact">
        <?= icon('clock') ?>
        <div>
          <strong><?= e(date('h:i A', strtotime((string) $event['start_datetime']))) ?><?php if ($event['end_datetime'] && !$isMultiDay): ?> &ndash; <?= e(date('h:i A', strtotime((string) $event['end_datetime']))) ?><?php endif; ?></strong>
        </div>
      </div>
      <?php if ($event['venue']): ?>
        <div class="event-fact">
          <?= icon('geo-alt') ?>
          <div><strong><?= e($event['venue']) ?></strong></div>
        </div>
      <?php endif; ?>
      <?php if ($event['organizer']): ?>
        <div class="event-fact">
          <?= icon('people') ?>
          <div><strong><?= e($event['organizer']) ?></strong></div>
        </div>
      <?php endif; ?>
      <?php if ($event['capacity']): ?>
        <div class="event-fact">
          <?= icon('person-badge') ?>
          <div><strong><?= number_format((int) $event['capacity']) ?> seats</strong></div>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($event['registration_required'] && $event['status'] === 'published'): ?>
      <div class="event-registration">
        <p>Registration is required to attend this event.</p>
        <?php if ($event['registration_url']): ?>
          <a class="btn btn-accent" href="<?= e(resolve_url((string) $event['registration_url'])) ?>" target="_blank" rel="noopener noreferrer">Register Now</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($event['content']): ?>
      <div class="news-article-body">
        <?php
          // Defense in depth: re-sanitize on render even though content was
          // already sanitized on save, so this is the only unescaped echo of
          // admin-authored HTML on the whole public site.
          echo HtmlSanitizer::clean((string) $event['content']);
        ?>
      </div>
    <?php endif; ?>
  </article>
</div>
