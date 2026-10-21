<?php
/**
 * @var array|null $term
 * @var array $sessions
 * @var array<string,string> $types
 * @var array<string,string> $statuses
 */
$activeTab = 'terms';
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => (string) ($old[$key] ?? ($term[$key] ?? $default));
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($term === null ? 'Add Exam Term' : 'Edit Exam Term') ?></h1>
    <p>Shown on the public Academic Calendar page.</p>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.exam-terms')) ?>"><?= icon('chevron-left') ?> Back to Exam Terms</a>
</div>

<div class="admin-card" style="max-width: 640px">
  <form method="post"
        action="<?= e($term === null ? route('admin.exam-terms.store') : route('admin.exam-terms.update', ['id' => $term['id']])) ?>">
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

    <div class="field">
      <label for="f-name">Name</label>
      <input class="inp" type="text" id="f-name" name="name" value="<?= e($field('name')) ?>" placeholder="e.g. Mid Term Examination" required>
      <?php if (has_error('name')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('name')) ?></p><?php endif; ?>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-type">Type</label>
        <select class="inp" id="f-type" name="type" required>
          <option value="">Select&hellip;</option>
          <?php foreach ($types as $val => $label): ?>
            <option value="<?= e($val) ?>" <?= $field('type', 'mid') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (has_error('type')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('type')) ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label for="f-status">Status</label>
        <select class="inp" id="f-status" name="status">
          <?php foreach ($statuses as $val => $label): ?>
            <option value="<?= e($val) ?>" <?= $field('status', 'planned') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-start">Start Date</label>
        <input class="inp" type="date" id="f-start" name="start_date" value="<?= e($field('start_date')) ?>" required>
        <?php if (has_error('start_date')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('start_date')) ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label for="f-end">End Date</label>
        <input class="inp" type="date" id="f-end" name="end_date" value="<?= e($field('end_date')) ?>" required>
        <?php if (has_error('end_date')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('end_date')) ?></p><?php endif; ?>
      </div>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-weightage">Weightage (%)</label>
        <input class="inp" type="number" step="0.01" min="0" max="100" id="f-weightage" name="weightage" value="<?= e($field('weightage', '100')) ?>">
        <p class="help">This term's share of the combined/annual result — used by the results pipeline.</p>
      </div>
      <div class="field">
        <label for="f-sort">Display Order</label>
        <input class="inp" type="number" id="f-sort" name="sort_order" value="<?= e($field('sort_order', '0')) ?>">
      </div>
    </div>

    <button type="submit" class="btn btn-primary"><?= $term === null ? 'Add Exam Term' : 'Save Changes' ?></button>
  </form>
</div>
