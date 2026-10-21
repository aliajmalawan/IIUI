<?php
/**
 * Previous/next pager that keeps the current filters in every link.
 *
 * @var int $page
 * @var int $lastPage
 * @var int $total
 * @var array<string,string> $filters
 */
if ($lastPage <= 1) {
    return;
}
$pageUrl = static fn (int $p): string => '?' . http_build_query(array_filter(array_merge($filters, ['page' => $p]), static fn ($v) => $v !== '' && $v !== 1));
?>
<div class="pagination">
  <span>Page <?= (int) $page ?> of <?= (int) $lastPage ?> &middot; <?= number_format($total) ?> results</span>
  <div class="pagination-links">
    <?php if ($page > 1): ?><a href="<?= e($pageUrl($page - 1)) ?>" aria-label="Previous page"><?= icon('chevron-left') ?></a><?php endif; ?>
    <?php for ($p = max(1, $page - 2); $p <= min($lastPage, $page + 2); $p++): ?>
      <?php if ($p === $page): ?><span class="is-current" aria-current="page"><?= $p ?></span><?php else: ?><a href="<?= e($pageUrl($p)) ?>"><?= $p ?></a><?php endif; ?>
    <?php endfor; ?>
    <?php if ($page < $lastPage): ?><a href="<?= e($pageUrl($page + 1)) ?>" aria-label="Next page"><?= icon('chevron-right') ?></a><?php endif; ?>
  </div>
</div>