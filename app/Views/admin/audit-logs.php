<?php
/**
 * @var array $logs
 * @var int $total
 * @var int $page
 * @var int $perPage
 * @var int $lastPage
 */
$moduleBadge = [
    'auth'    => 'badge-info',
    'account' => 'badge-success',
];
?>
<div class="admin-page-header">
  <div>
    <h1>Audit Log</h1>
    <p><?= number_format($total) ?> recorded event<?= $total === 1 ? '' : 's' ?> &mdash; append-only, never edited or deleted.</p>
  </div>
</div>

<div class="admin-table-wrap">
  <?php if ($logs === []): ?>
    <div class="empty-state">
      <?= icon('clock-history') ?>
      <h3>No audit events yet</h3>
      <p>Sensitive actions (logins, profile changes, and every module built from here on) will be recorded here.</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>When</th>
          <th>Actor</th>
          <th>Module</th>
          <th>Action</th>
          <th>Description</th>
          <th>IP Address</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
          <tr>
            <td class="mono"><?= e(date('d M Y, h:i:s A', strtotime((string) $log['created_at']))) ?></td>
            <td><?= e($log['actor_name'] ?: 'System') ?></td>
            <td><span class="badge <?= $moduleBadge[$log['module']] ?? 'badge-mute' ?>"><?= e((string) $log['module']) ?></span></td>
            <td class="mono"><?= e((string) $log['action']) ?></td>
            <td><?= e((string) ($log['description'] ?: '&mdash;')) ?></td>
            <?php
              $ipDisplay = '&mdash;';
              if ($log['ip_address'] !== null) {
                  $decoded = @inet_ntop((string) $log['ip_address']);
                  $ipDisplay = $decoded !== false ? $decoded : '&mdash;';
              }
            ?>
            <td class="mono"><?= e($ipDisplay) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($lastPage > 1): ?>
      <div class="pagination">
        <span>Page <?= (int) $page ?> of <?= (int) $lastPage ?></span>
        <div class="pagination-links">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>"><?= icon('chevron-left') ?></a>
          <?php endif; ?>
          <?php for ($p = max(1, $page - 2); $p <= min($lastPage, $page + 2); $p++): ?>
            <?php if ($p === $page): ?>
              <span class="is-current"><?= $p ?></span>
            <?php else: ?>
              <a href="?page=<?= $p ?>"><?= $p ?></a>
            <?php endif; ?>
          <?php endfor; ?>
          <?php if ($page < $lastPage): ?>
            <a href="?page=<?= $page + 1 ?>"><?= icon('chevron-right') ?></a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
