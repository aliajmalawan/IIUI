<?php
/**
 * @var array $students
 * @var array $classes
 * @var int|null $activeClassId
 * @var string $activeStatus
 * @var string $search
 * @var array $statuses
 */
$statusBadge = [
    'active'      => 'badge-success',
    'graduated'   => 'badge-info',
    'left'        => 'badge-mute',
    'suspended'   => 'badge-warning',
    'expelled'    => 'badge-danger',
    'transferred' => 'badge-mute',
];
?>
<div class="admin-page-header">
  <div>
    <h1>Students</h1>
    <p>Enrolled students and their class/section placement.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.students.create')) ?>"><?= icon('plus-lg') ?> Add Student</a>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<form method="get" action="<?= e(route('admin.students')) ?>" class="admin-filter-bar">
  <div class="field is-search">
    <label for="f-search">Search</label>
    <input class="inp" type="text" id="f-search" name="q" value="<?= e($search) ?>" placeholder="Name, father's name, reg/admission no.">
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
  <div class="field">
    <label for="f-status">Status</label>
    <select class="inp" id="f-status" name="status">
      <option value="">All Statuses</option>
      <?php foreach ($statuses as $st): ?>
        <option value="<?= e($st) ?>" <?= $activeStatus === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button type="submit" class="btn btn-secondary"><?= icon('search') ?> Filter</button>
  <?php if ($activeClassId !== null || $activeStatus !== '' || $search !== ''): ?>
    <a class="btn btn-secondary" href="<?= e(route('admin.students')) ?>">Clear</a>
  <?php endif; ?>
</form>

<div class="admin-table-wrap">
  <?php if ($students === []): ?>
    <div class="empty-state">
      <?= icon('mortarboard') ?>
      <h3>No students found</h3>
      <p>Add your first student, or adjust the filters above.</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th></th><th>Name</th><th>Reg. / Admission No.</th><th>Class &amp; Section</th><th>Father's Name</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($students as $s): ?>
          <tr>
            <td>
              <div class="avatar-circle" style="width:32px;height:32px;overflow:hidden">
                <?php if ($s['photo'] && is_file(PUBLIC_PATH . $s['photo'])): ?>
                  <img src="<?= e(base_url(ltrim($s['photo'], '/'))) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                  <?= e(mb_strtoupper(mb_substr($s['first_name'], 0, 1))) ?>
                <?php endif; ?>
              </div>
            </td>
            <td><strong><?= e(trim($s['first_name'] . ' ' . (string) $s['last_name'])) ?></strong></td>
            <td class="mono">
              <?= e($s['registration_no']) ?><br>
              <span style="font-size:12px;color:var(--text-muted)"><?= e($s['admission_no']) ?></span>
            </td>
            <td><?= $s['class_name'] ? e((string) $s['class_name']) . ($s['section_name'] ? ' &middot; ' . e((string) $s['section_name']) : '') : '&mdash;' ?></td>
            <td><?= e($s['father_name']) ?></td>
            <td><span class="badge <?= $statusBadge[$s['status']] ?? 'badge-mute' ?>"><?= e(ucfirst($s['status'])) ?></span></td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.students.edit', ['id' => $s['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.students.delete', ['id' => $s['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete the student record for "' . trim($s['first_name'] . ' ' . (string) $s['last_name']) . '"?') ?>">
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
