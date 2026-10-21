<?php
/** @var array $application */
$a = $application;
$statusBadge = [
    'submitted'      => 'badge-info',
    'under_review'   => 'badge-mute',
    'shortlisted'    => 'badge-info',
    'test_scheduled' => 'badge-mute',
    'test_taken'     => 'badge-mute',
    'interview'      => 'badge-mute',
    'admitted'       => 'badge-success',
    'rejected'       => 'badge-danger',
    'waitlisted'     => 'badge-mute',
    'withdrawn'      => 'badge-mute',
];
$statusLabel = static fn (string $s) => ucwords(str_replace('_', ' ', $s));
$transitions = [
    'under_review' => 'Mark Under Review',
    'shortlisted'  => 'Shortlist',
    'interview'    => 'Call for Interview',
    'admitted'     => 'Mark Admitted',
    'waitlisted'   => 'Waitlist',
    'rejected'     => 'Reject',
    'withdrawn'    => 'Mark Withdrawn',
];
$ip = null;
if ($a['submitted_ip'] !== null) {
    $decoded = @inet_ntop((string) $a['submitted_ip']);
    $ip = $decoded !== false ? $decoded : null;
}
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($a['applicant_name']) ?></h1>
    <p class="mono"><?= e($a['application_no']) ?> &middot; <?= e($a['class_name']) ?><?= $a['stream_name'] ? ' &middot; ' . e($a['stream_name']) : '' ?></p>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.admissions')) ?>"><?= icon('chevron-left') ?> Back to Applications</a>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<div class="news-form-grid">
  <div>
    <div class="admin-card">
      <h3 class="card-subhead">Student Details</h3>
      <p><strong>Full Name:</strong> <?= e($a['applicant_name']) ?></p>
      <p><strong>Father's Name:</strong> <?= e($a['father_name']) ?><?= $a['father_cnic'] ? ' (CNIC: ' . e($a['father_cnic']) . ')' : '' ?></p>
      <p><strong>B-Form / CNIC:</strong> <?= e((string) ($a['b_form_no'] ?? '&mdash;')) ?></p>
      <p><strong>Date of Birth:</strong> <?= e(date('d M Y', strtotime((string) $a['date_of_birth']))) ?></p>
      <p><strong>Gender:</strong> <?= e(ucfirst((string) $a['gender'])) ?></p>
    </div>

    <div class="admin-card">
      <h3 class="card-subhead">Class &amp; Academic Background</h3>
      <p><strong>Applying For:</strong> <?= e($a['class_name']) ?><?= $a['stream_name'] ? ' &middot; ' . e($a['stream_name']) : '' ?></p>
      <?php if ($a['previous_school']): ?><p><strong>Previous School:</strong> <?= e($a['previous_school']) ?></p><?php endif; ?>
    </div>

    <div class="admin-card">
      <h3 class="card-subhead">Entrance Test</h3>
      <?php if ($a['test_date']): ?>
        <p><strong>Scheduled:</strong> <?= e(date('d M Y, h:i A', strtotime((string) $a['test_date']))) ?><?= $a['test_roll_no'] ? ' &middot; Roll No. ' . e((string) $a['test_roll_no']) : '' ?></p>
        <?php if ($a['test_score'] !== null): ?><p><strong>Score:</strong> <?= e((string) $a['test_score']) ?></p><?php endif; ?>
      <?php else: ?>
        <p class="help">No entrance test scheduled yet.</p>
      <?php endif; ?>

      <form method="post" action="<?= e(route('admin.admissions.test.schedule', ['id' => $a['id']])) ?>" style="margin-top:var(--space-3)">
        <?= csrf_field() ?>
        <div class="field-grid">
          <div class="field">
            <label for="f-test-date"><?= $a['test_date'] ? 'Reschedule Test' : 'Schedule Test' ?></label>
            <input class="inp" type="datetime-local" id="f-test-date" name="test_date"
                   value="<?= $a['test_date'] ? e(date('Y-m-d\TH:i', strtotime((string) $a['test_date']))) : '' ?>">
            <?php if (has_error('test_date')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('test_date')) ?></p><?php endif; ?>
          </div>
          <div class="field">
            <label for="f-test-roll">Roll No. (optional)</label>
            <input class="inp" type="text" id="f-test-roll" name="test_roll_no" value="<?= e((string) ($a['test_roll_no'] ?? '')) ?>">
          </div>
        </div>
        <button type="submit" class="btn btn-secondary"><?= icon('calendar-event') ?> Save Test Schedule</button>
      </form>

      <form method="post" action="<?= e(route('admin.admissions.test.score', ['id' => $a['id']])) ?>" style="margin-top:var(--space-3)">
        <?= csrf_field() ?>
        <div class="field">
          <label for="f-test-score">Test Score</label>
          <input class="inp" type="number" step="0.01" min="0" id="f-test-score" name="test_score" value="<?= e((string) ($a['test_score'] ?? '')) ?>" style="max-width:160px">
          <?php if (has_error('test_score')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('test_score')) ?></p><?php endif; ?>
        </div>
        <button type="submit" class="btn btn-secondary"><?= icon('check-circle') ?> Save Score</button>
      </form>
    </div>

    <div class="admin-card">
      <h3 class="card-subhead">Merit &amp; Interview</h3>
      <form method="post" action="<?= e(route('admin.admissions.merit', ['id' => $a['id']])) ?>">
        <?= csrf_field() ?>
        <div class="field">
          <label for="f-merit">Merit Position (optional)</label>
          <input class="inp" type="number" min="1" id="f-merit" name="merit_position" value="<?= e((string) ($a['merit_position'] ?? '')) ?>" style="max-width:160px">
        </div>
        <div class="field">
          <label for="f-interview-remarks">Interview Remarks (optional)</label>
          <textarea class="inp" style="height:auto;padding:10px 12px" rows="3" id="f-interview-remarks" name="interview_remarks"><?= e((string) ($a['interview_remarks'] ?? '')) ?></textarea>
          <?php if (has_error('interview_remarks')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('interview_remarks')) ?></p><?php endif; ?>
        </div>
        <button type="submit" class="btn btn-secondary"><?= icon('check-circle') ?> Save</button>
      </form>
    </div>

    <div class="admin-card">
      <h3 class="card-subhead">Guardian &amp; Contact</h3>
      <p><strong>Phone:</strong> <a href="tel:<?= e(preg_replace('/[^+0-9]/', '', (string) $a['phone'])) ?>"><?= e($a['phone']) ?></a></p>
      <?php if ($a['email']): ?><p><strong>Email:</strong> <a href="mailto:<?= e($a['email']) ?>"><?= e($a['email']) ?></a></p><?php endif; ?>
      <?php if ($a['address']): ?><p><strong>Address:</strong> <?= e($a['address']) ?></p><?php endif; ?>
      <p class="help">Received <?= e(date('d M Y, h:i A', strtotime((string) $a['created_at']))) ?><?= $ip ? ' from ' . e($ip) : '' ?></p>
    </div>

    <div class="admin-card">
      <h3 class="card-subhead">Review Remarks</h3>
      <form method="post" action="<?= e(route('admin.admissions.remarks', ['id' => $a['id']])) ?>">
        <?= csrf_field() ?>
        <div class="field">
          <label for="f-remarks">Internal notes (not visible to the applicant)</label>
          <textarea class="inp" style="height:auto;padding:10px 12px" rows="4" id="f-remarks" name="remarks"><?= e((string) ($a['remarks'] ?? '')) ?></textarea>
          <?php if (has_error('remarks')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('remarks')) ?></p><?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary"><?= icon('check-circle') ?> Save Remarks</button>
      </form>
    </div>
  </div>

  <aside>
    <div class="admin-card">
      <h3 class="card-subhead">Status</h3>
      <p><span class="badge <?= $statusBadge[$a['status']] ?? 'badge-mute' ?>"><?= e($statusLabel((string) $a['status'])) ?></span></p>
      <?php if ($a['reviewed_at']): ?><p class="help">Last reviewed <?= e(date('d M Y, h:i A', strtotime((string) $a['reviewed_at']))) ?></p><?php endif; ?>
      <div class="btn-row" style="display:flex;flex-direction:column;gap:8px;margin-top:var(--space-3)">
        <?php foreach ($transitions as $val => $label): ?>
          <?php if ($a['status'] !== $val): ?>
            <form method="post" action="<?= e(route('admin.admissions.status', ['id' => $a['id']])) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="status" value="<?= e($val) ?>">
              <button type="submit" class="btn btn-secondary btn-sm" style="width:100%"><?= e($label) ?></button>
            </form>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="admin-card">
      <h3 class="card-subhead">Enrollment</h3>
      <?php if ($a['converted_student_id']): ?>
        <p class="help">This application has already been converted to a student record.</p>
        <a class="btn btn-secondary" style="width:100%" href="<?= e(route('admin.students.edit', ['id' => $a['converted_student_id']])) ?>"><?= icon('mortarboard') ?> View Student Record</a>
      <?php elseif ($a['status'] === 'admitted'): ?>
        <p class="help">Create the official student record from this application's details. You'll complete the remaining fields (section, mother's info, etc.) afterward.</p>
        <form method="post" action="<?= e(route('admin.admissions.convert', ['id' => $a['id']])) ?>">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-primary" style="width:100%"><?= icon('mortarboard') ?> Convert to Student</button>
        </form>
      <?php else: ?>
        <p class="help">Mark this application "Admitted" to enable converting it into a student record.</p>
      <?php endif; ?>
    </div>
  </aside>
</div>
