<?php
/** @var array $content */
use App\Core\HtmlSanitizer;
?>
<section class="section-block ps-section ps-rich-text">
  <div class="container">
    <?php if (!empty($content['heading'])): ?><h2 class="ps-heading"><?= e($content['heading']) ?></h2><?php endif; ?>
    <div class="ps-richtext-body"><?= HtmlSanitizer::clean((string) ($content['content'] ?? '')) ?></div>
  </div>
</section>
