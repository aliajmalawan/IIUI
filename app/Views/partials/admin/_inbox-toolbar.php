<?php
/**
 * Search box + status filter tabs + CSV export for an admin inbox list.
 *
 * @var array<string,string> $statusLabels status => label
 * @var array<string,int> $statusCounts status => count
 * @var array{status:string,q:string} $filters
 * @var string $searchPlaceholder
 */
$totalAll = array_sum($statusCounts);
$tabUrl = static fn (string $status): string => '?' . http_build_query(array_filter(['status' => $status, 'q' => $filters['q']], static fn ($v) => $v !== ''));
?>
<div class="inbox-toolbar">
  <form method="get" class="inbox-search" role="search">
    <?php if ($filters['status'] !== ''): ?><input type="hidden" name="status" value="<?= e($filters['status']) ?>"><?php endif; ?>
    <input class="inp" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="<?= e($searchPlaceholder) ?>" aria-label="Search">
    <button type="submit" class="btn btn-primary btn-sm"><?= icon('search') ?> Search</button>
    <?php if ($filters['q'] !== '' || $filters['status'] !== ''): ?>
      <a class="btn btn-secondary btn-sm" href="?">Clear</a>
    <?php endif; ?>
  </form>
  <a class="btn btn-secondary btn-sm" href="?<?= e(http_build_query(array_filter(['status' => $filters['status'], 'q' => $filters['q'], 'export' => 'csv'], static fn ($v) => $v !== ''))) ?>"><?= icon('download') ?> Export CSV</a>
</div>

<nav class="settings-tabs" aria-label="Filter by status">
  <a class="settings-tab<?= $filters['status'] === '' ? ' is-active' : '' ?>" href="<?= e($tabUrl('')) ?>">All <span class="tab-count"><?= (int) $totalAll ?></span></a>
  <?php foreach ($statusLabels as $status => $label): ?>
    <a class="settings-tab<?= $filters['status'] === $status ? ' is-active' : '' ?>" href="<?= e($tabUrl($status)) ?>"><?= e($label) ?> <span class="tab-count"><?= (int) ($statusCounts[$status] ?? 0) ?></span></a>
  <?php endforeach; ?>
</nav>