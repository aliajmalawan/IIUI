<?php
/**
 * @var array $staff
 * @var array $departments
 * @var int|null $activeDept
 * @var string $activeStatus
 * @var string $search
 * @var array $statuses
 */
$statusBadge = [
    'active'     => 'badge-success',
    'on_leave'   => 'badge-warning',
    'resigned'   => 'badge-mute',
    'terminated' => 'badge-danger',
    'retired'    => 'badge-mute',
];
$typeLabel = static fn (string $t) => ucfirst($t);
?>
<div class="admin-page-header">
  <div>
    <h1>Staff</h1>
    <p>Full HR record for every staff member. Public website profiles are managed under Faculty.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(route('admin.staff.create')) ?>"><?= icon('plus-lg') ?> Add Staff Member</a>
</div>

<?php if ($status = flash('status')): ?>
  <div class="alert-box alert-success"><?= e((string) $status) ?></div>
<?php endif; ?>

<form method="get" action="<?= e(route('admin.staff')) ?>" class="admin-filter-bar">
  <div class="field is-search">
    <label for="f-search">Search</label>
    <input class="inp" type="text" id="f-search" name="q" value="<?= e($search) ?>" placeholder="Name or employee no.">
  </div>
  <div class="field">
    <label for="f-department">Department</label>
    <select class="inp" id="f-department" name="department_id">
      <option value="">All Departments</option>
      <?php foreach ($departments as $dept): ?>
        <option value="<?= (int) $dept['id'] ?>" <?= $activeDept === (int) $dept['id'] ? 'selected' : '' ?>><?= e($dept['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label for="f-status">Status</label>
    <select class="inp" id="f-status" name="status">
      <option value="">All Statuses</option>
      <?php foreach ($statuses as $st): ?>
        <option value="<?= e($st) ?>" <?= $activeStatus === $st ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $st))) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button type="submit" class="btn btn-secondary"><?= icon('search') ?> Filter</button>
  <?php if ($activeDept !== null || $activeStatus !== '' || $search !== ''): ?>
    <a class="btn btn-secondary" href="<?= e(route('admin.staff')) ?>">Clear</a>
  <?php endif; ?>
</form>

<div class="admin-table-wrap">
  <?php if ($staff === []): ?>
    <div class="empty-state">
      <?= icon('person-badge') ?>
      <h3>No staff records found</h3>
      <p>Add your first staff member, or adjust the filters above.</p>
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th></th><th>Name</th><th>Department &amp; Designation</th><th>CNIC</th><th>Employment</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($staff as $member): ?>
          <tr>
            <td>
              <div class="avatar-circle" style="width:32px;height:32px;overflow:hidden">
                <?= e(mb_strtoupper(mb_substr($member['first_name'], 0, 1))) ?>
              </div>
            </td>
            <td>
              <strong><?= e(trim($member['first_name'] . ' ' . (string) $member['last_name'])) ?></strong>
              <br><span style="font-size:12px;color:var(--text-muted)" class="mono"><?= e((string) $member['employee_no']) ?></span>
            </td>
            <td><?= $member['department_name'] ? e((string) $member['department_name']) : '&mdash;' ?><?= $member['designation_name'] ? ' &middot; ' . e((string) $member['designation_name']) : '' ?></td>
            <td class="mono"><?= $member['cnic_masked'] ? e((string) $member['cnic_masked']) : '&mdash;' ?></td>
            <td><?= e($typeLabel((string) $member['employment_type'])) ?><?= $member['is_teaching'] ? ' &middot; Teaching' : '' ?></td>
            <td><span class="badge <?= $statusBadge[$member['status']] ?? 'badge-mute' ?>"><?= e(ucwords(str_replace('_', ' ', (string) $member['status']))) ?></span></td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= e(route('admin.staff.edit', ['id' => $member['id']])) ?>"><?= icon('pencil-square') ?></a>
              <form method="post" action="<?= e(route('admin.staff.delete', ['id' => $member['id']])) ?>" style="display:inline"
                    data-confirm="<?= e('Delete the staff record for "' . trim($member['first_name'] . ' ' . (string) $member['last_name']) . '"?') ?>">
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
