<?php
/**
 * @var array|null $fee
 * @var array $sessions
 * @var array $classes
 * @var array $categories
 * @var array<string,string> $frequencies
 */
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => (string) ($old[$key] ?? ($fee[$key] ?? $default));
$wingLabels = ['primary' => 'Primary', 'middle' => 'Middle', 'secondary' => 'Secondary', 'college' => 'College'];
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($fee === null ? 'Add Fee' : 'Edit Fee') ?></h1>
    <p>One class's amount for one fee head, for one academic session.</p>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.fee-structures')) ?>"><?= icon('chevron-left') ?> Back to Fee Structure</a>
</div>

<div class="admin-card" style="max-width: 560px">
  <form method="post"
        action="<?= e($fee === null ? route('admin.fee-structures.store') : route('admin.fee-structures.update', ['id' => $fee['id']])) ?>">
    <?= csrf_field() ?>

    <div class="field">
      <label for="f-session">Academic Session</label>
      <select class="inp" id="f-session" name="academic_session_id" required>
        <option value="">Select a session&hellip;</option>
        <?php foreach ($sessions as $s): ?>
          <option value="<?= (int) $s['id'] ?>" <?= $field('academic_session_id') === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (has_error('academic_session_id')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('academic_session_id')) ?></p><?php endif; ?>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-class">Class</label>
        <select class="inp" id="f-class" name="class_id" required>
          <option value="">Select a class&hellip;</option>
          <?php $lastWing = null; foreach ($classes as $c): ?>
            <?php if ($c['wing'] !== $lastWing): ?>
              <?php if ($lastWing !== null): ?></optgroup><?php endif; ?>
              <optgroup label="<?= e($wingLabels[$c['wing']] ?? ucfirst($c['wing'])) ?>">
              <?php $lastWing = $c['wing']; ?>
            <?php endif; ?>
            <option value="<?= (int) $c['id'] ?>" <?= $field('class_id') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
          <?php if ($lastWing !== null): ?></optgroup><?php endif; ?>
        </select>
        <?php if (has_error('class_id')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('class_id')) ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label for="f-category">Fee Head</label>
        <select class="inp" id="f-category" name="fee_category_id" required>
          <option value="">Select&hellip;</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['id'] ?>" data-type="<?= e($cat['type']) ?>" <?= $field('fee_category_id') === (string) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (has_error('fee_category_id')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('fee_category_id')) ?></p><?php endif; ?>
      </div>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-amount">Amount (Rs.)</label>
        <input class="inp" type="number" min="0" step="1" id="f-amount" name="amount" value="<?= e($field('amount')) ?>" required>
        <?php if (has_error('amount')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('amount')) ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label for="f-frequency">Frequency</label>
        <select class="inp" id="f-frequency" name="frequency" required>
          <?php foreach ($frequencies as $val => $label): ?>
            <option value="<?= e($val) ?>" <?= $field('frequency', 'monthly') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (has_error('frequency')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('frequency')) ?></p><?php endif; ?>
      </div>
    </div>

    <button type="submit" class="btn btn-primary"><?= $fee === null ? 'Add Fee' : 'Save Changes' ?></button>
  </form>
</div>

<script>
(function () {
  var categorySelect = document.getElementById('f-category');
  var frequencySelect = document.getElementById('f-frequency');
  if (!categorySelect || !frequencySelect) { return; }

  categorySelect.addEventListener('change', function () {
    var selected = categorySelect.options[categorySelect.selectedIndex];
    if (selected && selected.dataset.type === 'one_time') {
      frequencySelect.value = 'one_time';
    }
  });
})();
</script>
