<?php
/** @var array $post */
use App\Core\HtmlSanitizer;
?>
<section class="hero-minimal">
  <div class="hero-khatam" aria-hidden="true"></div>
  <div class="container">
    <p class="hero-eyebrow">
      <?php if ($post['category_name']): ?>
        <a href="<?= e(route('news.category', ['slug' => $post['category_slug']])) ?>" style="color:inherit"><?= e($post['category_name']) ?></a> &middot;
      <?php endif; ?>
      <?= e(date('d M Y', strtotime((string) $post['published_at']))) ?>
    </p>
    <h1 class="hero-title"><?= e($post['title']) ?></h1>
    <?php if ($post['excerpt']): ?><p class="hero-lead"><?= e($post['excerpt']) ?></p><?php endif; ?>
  </div>
</section>

<div class="container section-block">
  <p><a class="back-link" href="<?= e(route('news.index')) ?>">&larr; Back to News</a></p>

  <article class="news-article">
    <?php if ($post['featured_image'] && is_file(PUBLIC_PATH . $post['featured_image'])): ?>
      <img class="news-article-cover" src="<?= e(base_url(ltrim($post['featured_image'], '/'))) ?>" alt="<?= e($post['title']) ?>" decoding="async" fetchpriority="high">
    <?php endif; ?>

    <div class="news-article-body">
      <?php
        // Defense in depth: re-sanitize on render even though content was
        // already sanitized on save, so this is the only unescaped echo of
        // admin-authored HTML on the whole public site.
        echo HtmlSanitizer::clean((string) $post['content']);
      ?>
    </div>

    <div class="news-article-footer">
      <span><?= icon('eye') ?> <?= number_format((int) $post['views']) ?> views</span>
    </div>
  </article>
</div>
