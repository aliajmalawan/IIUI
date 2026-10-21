<?php
/** @var array $fees */
$freqLabel = ['one_time' => 'One-Time', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'bi_annual' => 'Bi-Annual', 'annual' => 'Annual'];
?>
<div class="admin-page-header">
  <div>
    <h1>Fee Structure</h1>
    <p>Per-class fee amounts shown on the public Fee Structure page.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.fee-structures.create')) ?>"><?= icon('plus-lg') ?> Add Fee</a>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <?php if ($fees === []): ?>
    <div class="empty-state">
      <?= icon('receipt') ?>
      <h3>No fee entries yet</h3>
      <p>Add the first class/fee-head amount to start building the fee structure.</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Class</th><th>Fee Head</th><th>Amount</th><th>Frequency</th><th>Session</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($fees as $fee): ?>
          <tr>
            <td><strong><?= e($fee['class_name']) ?></strong></td>
            <td><?= e($fee['category_name']) ?></td>
            <td class="mono">Rs. <?= number_format((float) $fee['amount']) ?></td>
            <td><span class="badge badge-mute"><?= e($freqLabel[$fee['frequency']] ?? ucfirst($fee['frequency'])) ?></span></td>
            <td class="mono"><?= e($fee['session_name']) ?></td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.fee-structures.edit', ['id' => $fee['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.fee-structures.delete', ['id' => $fee['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete the ' . $fee['category_name'] . ' entry for ' . $fee['class_name'] . '?') ?>">
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
