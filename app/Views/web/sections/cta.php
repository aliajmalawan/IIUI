<?php
/** @var array $content */
?>
<section class="section-block ps-section ps-cta">
  <div class="container">
    <?php if (!empty($content['heading'])): ?><h2 class="ps-heading"><?= e($content['heading']) ?></h2><?php endif; ?>
    <?php if (!empty($content['text'])): ?><p><?= e($content['text']) ?></p><?php endif; ?>
    <?php if (!empty($content['button_text']) && !empty($content['button_url'])): ?>
      <div class="hero-actions" style="justify-content:center">
        <a class="btn btn-accent btn-lg" href="<?= e(resolve_url($content['button_url'])) ?>"><?= e($content['button_text']) ?></a>
      </div>
    <?php endif; ?>
  </div>
</section>
