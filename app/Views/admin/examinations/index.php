<?php
/**
 * @var array $exams
 * @var array $terms
 * @var array $classes
 * @var int|null $activeTermId
 * @var int|null $activeClassId
 */
?>
<div class="admin-page-header">
  <div>
    <h1>Examinations</h1>
    <p>Subject-wise exam schedule — date, time, room and invigilator for each paper within an exam term.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.examinations.create')) ?>"><?= icon('plus-lg') ?> Schedule Exam</a>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<form method="get" action="<?= e(route('admin.examinations')) ?>" class="admin-filter-bar">
  <div class="field">
    <label for="f-term">Exam Term</label>
    <select class="inp" id="f-term" name="exam_term_id">
      <option value="">All Terms</option>
      <?php foreach ($terms as $term): ?>
        <option value="<?= (int) $term['id'] ?>" <?= $activeTermId === (int) $term['id'] ? 'selected' : '' ?>><?= e($term['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label for="f-class">Class</label>
    <select class="inp" id="f-class" name="class_id">
      <option value="">All Classes</option>
      <?php foreach ($classes as $class): ?>
        <option value="<?= (int) $class['id'] ?>" <?= $activeClassId === (int) $class['id'] ? 'selected' : '' ?>><?= e($class['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button type="submit" class="btn btn-secondary"><?= icon('search') ?> Filter</button>
  <?php if ($activeTermId !== null || $activeClassId !== null): ?>
    <a class="btn btn-secondary" href="<?= e(route('admin.examinations')) ?>">Clear</a>
  <?php endif; ?>
</form>

<div class="admin-table-wrap">
  <?php if ($exams === []): ?>
    <div class="empty-state">
      <?= icon('bar-chart-line') ?>
      <h3>No exams scheduled</h3>
      <p>Schedule the first subject paper for an exam term.</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Date &amp; Time</th><th>Subject</th><th>Class</th><th>Term</th><th>Room</th><th>Invigilator</th><th>Marks</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($exams as $exam): ?>
          <tr>
            <td class="mono">
              <?= e(date('d M Y', strtotime((string) $exam['exam_date']))) ?>
              <?php if ($exam['start_time']): ?><br><span style="font-size:12px;color:var(--text-muted)"><?= e(date('h:i A', strtotime((string) $exam['start_time']))) ?><?= $exam['end_time'] ? ' &ndash; ' . e(date('h:i A', strtotime((string) $exam['end_time']))) : '' ?></span><?php endif; ?>
            </td>
            <td><strong><?= e($exam['subject_name']) ?></strong> <span class="mono" style="font-size:12px;color:var(--text-muted)"><?= e($exam['subject_code']) ?></span></td>
            <td><?= e($exam['class_name']) ?><?= $exam['stream_name'] ? ' &middot; ' . e((string) $exam['stream_name']) : '' ?></td>
            <td><?= e($exam['term_name']) ?></td>
            <td><?= $exam['room_no'] ? e((string) $exam['room_no']) : '&mdash;' ?></td>
            <td><?= $exam['invigilator_name'] && trim((string) $exam['invigilator_name']) !== '' ? e(trim((string) $exam['invigilator_name'])) : '&mdash;' ?></td>
            <td class="mono"><?= (int) $exam['total_marks'] ?> <span style="color:var(--text-muted)">(pass <?= (int) $exam['passing_marks'] ?>)</span></td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.examinations.edit', ['id' => $exam['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.examinations.delete', ['id' => $exam['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Remove the "' . $exam['subject_name'] . '" exam from the schedule?') ?>">
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
