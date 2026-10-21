<?php
/**
 * @var array $messages
 * @var array $filters
 * @var array $statusCounts
 * @var array $statusLabels
 * @var int $total
 * @var int $page
 * @var int $lastPage
 */
$statusBadge = ['new' => 'badge-info', 'read' => 'badge-mute', 'replied' => 'badge-success', 'spam' => 'badge-danger', 'archived' => 'badge-mute'];
?>
<div class="admin-page-header">
  <div>
    <h1>Contact Messages</h1>
    <p>Inquiries submitted through the public website's contact form.</p>
  </div>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<?php
$searchPlaceholder = 'Search name, email, phone, subject or message';
require VIEWS_PATH . '/partials/admin/_inbox-toolbar.php';
?>

<div class="admin-table-wrap">
  <?php if ($messages === []): ?>
    <div class="empty-state">
      <?= icon('envelope') ?>
      <?php if ($filters['q'] !== '' || $filters['status'] !== ''): ?>
        <h3>No matching messages</h3>
        <p>Try a different search or clear the filters.</p>
      <?php else: ?>
        <h3>No messages yet</h3>
        <p>Submissions from the website's Contact section will appear here.</p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>From</th><th>Subject</th><th>Received</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($messages as $msg): ?>
          <tr>
            <td>
              <strong><?= e($msg['name']) ?></strong>
              <br><span style="font-size:12px;color:var(--text-muted)"><?= e($msg['email']) ?></span>
            </td>
            <td><?= $msg['subject'] ? e($msg['subject']) : '&mdash;' ?></td>
            <td class="mono"><?= e(date('d M Y, h:i A', strtotime((string) $msg['created_at']))) ?></td>
            <td><span class="badge <?= $statusBadge[$msg['status']] ?? 'badge-mute' ?>"><?= e(ucfirst($msg['status'])) ?></span></td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.contact-messages.show', ['id' => $msg['id']])) ?>"><?= icon('eye') ?> View</a>
              <form method="post" action="<?= e(route('admin.contact-messages.delete', ['id' => $msg['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete the message from "' . $msg['name'] . '"?') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger-700)"><?= icon('trash') ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php require VIEWS_PATH . '/partials/admin/_pager.php'; ?>
  <?php endif; ?>
</div>
