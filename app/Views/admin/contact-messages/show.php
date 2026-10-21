<?php
/** @var array $message */
$statusBadge = ['new' => 'badge-info', 'read' => 'badge-mute', 'replied' => 'badge-success', 'spam' => 'badge-danger', 'archived' => 'badge-mute'];
$ip = null;
if ($message['ip_address'] !== null) {
    $decoded = @inet_ntop((string) $message['ip_address']);
    $ip = $decoded !== false ? $decoded : null;
}
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($message['name']) ?></h1>
    <p><?= e($message['email']) ?><?php if ($message['phone']): ?> &middot; <?= e($message['phone']) ?><?php endif; ?></p>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.contact-messages')) ?>"><?= icon('chevron-left') ?> Back to Messages</a>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="news-form-grid">
  <div>
    <div class="admin-card">
      <h3 class="card-subhead">Message</h3>
      <?php if ($message['subject']): ?><p><strong>Subject:</strong> <?= e($message['subject']) ?></p><?php endif; ?>
      <p style="white-space:pre-wrap"><?= e($message['message']) ?></p>
      <p class="help">Received <?= e(date('d M Y, h:i A', strtotime((string) $message['created_at']))) ?><?= $ip ? ' from ' . e($ip) : '' ?></p>
    </div>

    <?php if ($message['reply_message']): ?>
      <div class="admin-card">
        <h3 class="card-subhead">Your Reply</h3>
        <p style="white-space:pre-wrap"><?= e($message['reply_message']) ?></p>
        <p class="help">Sent <?= $message['replied_at'] ? e(date('d M Y, h:i A', strtotime((string) $message['replied_at']))) : '' ?></p>
      </div>
    <?php endif; ?>

    <div class="admin-card">
      <h3 class="card-subhead">Send a Reply</h3>
      <form method="post" action="<?= e(route('admin.contact-messages.reply', ['id' => $message['id']])) ?>">
        <?= csrf_field() ?>
        <div class="field">
          <label for="f-reply">Reply Message</label>
          <textarea class="inp" style="height:auto;padding:10px 12px" rows="5" id="f-reply" name="reply_message" required><?= old('reply_message') ?></textarea>
          <?php if (has_error('reply_message')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('reply_message')) ?></p><?php endif; ?>
          <p class="help">Sent by email to <?= e($message['email']) ?> (log-driver in this environment &mdash; see storage/logs).</p>
        </div>
        <button type="submit" class="btn btn-primary"><?= icon('send') ?> Send Reply</button>
      </form>
    </div>
  </div>

  <aside>
    <div class="admin-card">
      <h3 class="card-subhead">Status</h3>
      <p><span class="badge <?= $statusBadge[$message['status']] ?? 'badge-mute' ?>"><?= e(ucfirst($message['status'])) ?></span></p>
      <div class="btn-row" style="display:flex;flex-direction:column;gap:8px;margin-top:var(--space-3)">
        <?php foreach (['read' => 'Mark as Read', 'spam' => 'Mark as Spam', 'archived' => 'Archive'] as $val => $label): ?>
          <?php if ($message['status'] !== $val): ?>
            <form method="post" action="<?= e(route('admin.contact-messages.status', ['id' => $message['id']])) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="status" value="<?= e($val) ?>">
              <button type="submit" class="btn btn-secondary btn-sm" style="width:100%"><?= e($label) ?></button>
            </form>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
  </aside>
</div>
