<?php
/** @var array $content */
$items = is_array($content['items'] ?? null) ? $content['items'] : [];
?>
<?php if ($items !== []): ?>
<section class="section-block ps-section ps-testimonials">
  <div class="container">
    <?php if (!empty($content['heading'])): ?><h2 class="ps-heading"><?= e($content['heading']) ?></h2><?php endif; ?>
    <div class="ps-testimonial-grid">
      <?php foreach ($items as $item): ?>
        <div class="ps-testimonial-card">
          <p class="ps-testimonial-quote">&ldquo;<?= e((string) ($item['quote'] ?? '')) ?>&rdquo;</p>
          <p class="ps-testimonial-name"><?= e((string) ($item['name'] ?? '')) ?></p>
          <?php if (!empty($item['role'])): ?><p class="ps-testimonial-role"><?= e($item['role']) ?></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
