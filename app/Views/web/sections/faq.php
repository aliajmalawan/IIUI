<?php
/** @var array $content */
$items = is_array($content['items'] ?? null) ? $content['items'] : [];
?>
<?php if ($items !== []): ?>
<section class="section-block ps-section ps-faq">
  <div class="container">
    <?php if (!empty($content['heading'])): ?><h2 class="ps-heading"><?= e($content['heading']) ?></h2><?php endif; ?>
    <div class="ps-faq-list">
      <?php foreach ($items as $item): ?>
        <details class="ps-faq-item">
          <summary><?= e((string) ($item['question'] ?? '')) ?></summary>
          <p><?= e((string) ($item['answer'] ?? '')) ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
