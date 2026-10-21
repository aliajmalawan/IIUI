<?php
/** @var array $events */
$statusBadge = ['published' => 'badge-success', 'draft' => 'badge-mute', 'cancelled' => 'badge-danger', 'completed' => 'badge-info'];
?>
<div class="admin-page-header">
  <div>
    <h1>Events</h1>
    <p>School events shown on the public website's Events page.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.events.create')) ?>"><?= icon('plus-lg') ?> Add Event</a>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <?php if ($events === []): ?>
    <div class="empty-state">
      <?= icon('calendar-event') ?>
      <h3>No events yet</h3>
      <p>Add your first event &mdash; e.g. "Annual Sports Day 2026".</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th></th><th>Title</th><th>When</th><th>Venue</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($events as $event): ?>
          <tr>
            <td>
              <div class="upload-preview" style="width:56px;height:40px">
                <?php if ($event['banner'] && is_file(PUBLIC_PATH . $event['banner'])): ?>
                  <img src="<?= e(base_url(ltrim($event['banner'], '/'))) ?>" alt="">
                <?php else: ?>
                  <?= icon('calendar-event') ?>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <strong><?= e($event['title']) ?></strong>
              <?php if ($event['is_featured']): ?> <span class="badge badge-info">Featured</span><?php endif; ?>
            </td>
            <td class="mono">
              <?= e(date('d M Y, h:i A', strtotime((string) $event['start_datetime']))) ?>
              <?php if ($event['end_datetime']): ?><br>&rarr; <?= e(date('d M Y, h:i A', strtotime((string) $event['end_datetime']))) ?><?php endif; ?>
            </td>
            <td><?= $event['venue'] ? e((string) $event['venue']) : '&mdash;' ?></td>
            <td><?= $event['category'] ? e((string) $event['category']) : '&mdash;' ?></td>
            <td><span class="badge <?= $statusBadge[$event['status']] ?? 'badge-mute' ?>"><?= e(ucfirst($event['status'])) ?></span></td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.events.edit', ['id' => $event['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.events.delete', ['id' => $event['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete event "' . $event['title'] . '"?') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger-700)"><?= icon('trash') ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
