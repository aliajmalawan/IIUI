<?php
/**
 * @var array|null $exam
 * @var array $terms
 * @var array $classes
 * @var array $streams
 * @var array $subjects
 * @var array $invigilators
 */
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($exam[$key] ?? $default);
$examDateValue = $old['exam_date'] ?? (string) ($exam['exam_date'] ?? '');
$startTimeValue = $old['start_time'] ?? substr((string) ($exam['start_time'] ?? ''), 0, 5);
$endTimeValue = $old['end_time'] ?? substr((string) ($exam['end_time'] ?? ''), 0, 5);
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($exam === null ? 'Schedule Exam' : 'Edit Exam') ?></h1>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.examinations')) ?>"><?= icon('chevron-left') ?> Back to Examinations</a>
</div>

<form method="post"
      action="<?= e($exam === null ? route('admin.examinations.store') : route('admin.examinations.update', ['id' => $exam['id']])) ?>">
  <?= csrf_field() ?>

  <div class="news-form-grid">
    <div>
      <div class="admin-card">
        <h3 class="card-subhead">Term, Class &amp; Subject</h3>
        <div class="field">
          <label for="f-term">Exam Term</label>
          <select class="inp" id="f-term" name="exam_term_id" required>
            <option value="">Select&hellip;</option>
            <?php foreach ($terms as $term): ?>
              <option value="<?= (int) $term['id'] ?>" <?= $field('exam_term_id') === (string) $term['id'] ? 'selected' : '' ?>><?= e($term['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (has_error('exam_term_id')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('exam_term_id')) ?></p><?php endif; ?>
        </div>
        <div class="field-grid">
          <div class="field">
            <label for="f-class">Class</label>
            <select class="inp" id="f-class" name="class_id" required>
              <option value="">Select&hellip;</option>
              <?php foreach ($classes as $class): ?>
                <option value="<?= (int) $class['id'] ?>" <?= $field('class_id') === (string) $class['id'] ? 'selected' : '' ?>><?= e($class['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (has_error('class_id')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('class_id')) ?></p><?php endif; ?>
          </div>
          <div class="field">
            <label for="f-stream">Stream (optional)</label>
            <select class="inp" id="f-stream" name="stream_id">
              <option value="">Not applicable</option>
              <?php foreach ($streams as $stream): ?>
                <option value="<?= (int) $stream['id'] ?>" <?= $field('stream_id') === (string) $stream['id'] ? 'selected' : '' ?>><?= e($stream['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="field">
          <label for="f-subject">Subject</label>
          <select class="inp" id="f-subject" name="subject_id" required>
            <option value="">Select&hellip;</option>
            <?php foreach ($subjects as $subject): ?>
              <option value="<?= (int) $subject['id'] ?>" <?= $field('subject_id') === (string) $subject['id'] ? 'selected' : '' ?>><?= e($subject['name']) ?> (<?= e($subject['code']) ?>)</option>
            <?php endforeach; ?>
          </select>
          <?php if (has_error('subject_id')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('subject_id')) ?></p><?php endif; ?>
        </div>
      </div>

      <div class="admin-card">
        <h3 class="card-subhead">Schedule</h3>
        <div class="field">
          <label for="f-date">Exam Date</label>
          <input class="inp" type="date" id="f-date" name="exam_date" value="<?= e($examDateValue) ?>" required style="max-width:220px">
          <?php if (has_error('exam_date')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('exam_date')) ?></p><?php endif; ?>
        </div>
        <div class="field-grid">
          <div class="field">
            <label for="f-start">Start Time (optional)</label>
            <input class="inp" type="time" id="f-start" name="start_time" value="<?= e($startTimeValue) ?>">
          </div>
          <div class="field">
            <label for="f-end">End Time (optional)</label>
            <input class="inp" type="time" id="f-end" name="end_time" value="<?= e($endTimeValue) ?>">
            <?php if (has_error('end_time')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('end_time')) ?></p><?php endif; ?>
          </div>
        </div>
        <div class="field-grid">
          <div class="field">
            <label for="f-room">Room No. (optional)</label>
            <input class="inp" type="text" id="f-room" name="room_no" value="<?= e($field('room_no')) ?>">
          </div>
          <div class="field">
            <label for="f-invigilator">Invigilator (optional)</label>
            <select class="inp" id="f-invigilator" name="invigilator_id">
              <option value="">Unassigned</option>
              <?php foreach ($invigilators as $inv): ?>
                <option value="<?= (int) $inv['id'] ?>" <?= $field('invigilator_id') === (string) $inv['id'] ? 'selected' : '' ?>><?= e(trim($inv['first_name'] . ' ' . (string) $inv['last_name'])) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="admin-card">
        <h3 class="card-subhead">Marks</h3>
        <div class="field-grid">
          <div class="field">
            <label for="f-total">Total Marks</label>
            <input class="inp" type="number" min="0" id="f-total" name="total_marks" value="<?= e($field('total_marks', '100')) ?>">
          </div>
          <div class="field">
            <label for="f-passing">Passing Marks</label>
            <input class="inp" type="number" min="0" id="f-passing" name="passing_marks" value="<?= e($field('passing_marks', '33')) ?>">
            <?php if (has_error('passing_marks')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('passing_marks')) ?></p><?php endif; ?>
          </div>
        </div>
        <div class="field-grid">
          <div class="field">
            <label for="f-practical-total">Practical Total (optional)</label>
            <input class="inp" type="number" min="0" id="f-practical-total" name="practical_total" value="<?= e($field('practical_total', '0')) ?>">
          </div>
          <div class="field">
            <label for="f-practical-passing">Practical Passing (optional)</label>
            <input class="inp" type="number" min="0" id="f-practical-passing" name="practical_passing" value="<?= e($field('practical_passing', '0')) ?>">
            <?php if (has_error('practical_passing')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('practical_passing')) ?></p><?php endif; ?>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><?= $exam === null ? 'Schedule Exam' : 'Save Changes' ?></button>
    </div>
  </div>
</form>
