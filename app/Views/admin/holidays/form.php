<?php
/**
 * @var array|null $holiday
 * @var array $sessions
 * @var array<string,string> $types
 * @var array<string,string> $appliesTo
 */
$activeTab = 'holidays';
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => (string) ($old[$key] ?? ($holiday[$key] ?? $default));
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($holiday === null ? 'Add Holiday' : 'Edit Holiday') ?></h1>
    <p>Shown on the public Academic Calendar page.</p>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.holidays')) ?>"><?= icon('chevron-left') ?> Back to Holidays</a>
</div>

<div class="admin-card" style="max-width: 640px">
  <form method="post"
        action="<?= e($holiday === null ? route('admin.holidays.store') : route('admin.holidays.update', ['id' => $holiday['id']])) ?>">
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
      <label for="f-title">Title</label>
      <input class="inp" type="text" id="f-title" name="title" value="<?= e($field('title')) ?>" placeholder="e.g. Eid-ul-Fitr Holidays" required>
      <?php if (has_error('title')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('title')) ?></p><?php endif; ?>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-type">Type</label>
        <select class="inp" id="f-type" name="type" required>
          <option value="">Select&hellip;</option>
          <?php foreach ($types as $val => $label): ?>
            <option value="<?= e($val) ?>" <?= $field('type', 'public') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (has_error('type')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('type')) ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label for="f-applies">Applies To</label>
        <select class="inp" id="f-applies" name="applies_to">
          <?php foreach ($appliesTo as $val => $label): ?>
            <option value="<?= e($val) ?>" <?= $field('applies_to', 'all') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="field-grid">
      <div class="field">
        <label for="f-from">From</label>
        <input class="inp" type="date" id="f-from" name="date_from" value="<?= e($field('date_from')) ?>" required>
        <?php if (has_error('date_from')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('date_from')) ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label for="f-to">To</label>
        <input class="inp" type="date" id="f-to" name="date_to" value="<?= e($field('date_to')) ?>" required>
        <?php if (has_error('date_to')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('date_to')) ?></p><?php endif; ?>
      </div>
    </div>

    <div class="field">
      <label for="f-description">Description (optional)</label>
      <textarea class="inp" style="height:auto;padding:10px 12px" rows="3" id="f-description" name="description"><?= e($field('description')) ?></textarea>
      <?php if (has_error('description')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('description')) ?></p><?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary"><?= $holiday === null ? 'Add Holiday' : 'Save Changes' ?></button>
  </form>
</div>
