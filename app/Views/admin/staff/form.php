<?php
/**
 * @var array|null $member
 * @var string $suggestedNo
 * @var array $departments
 * @var array $designations
 * @var array $employmentTypes
 * @var array $statuses
 * @var array $maritalStatuses
 * @var array $bloodGroups
 */
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($member[$key] ?? $default);
$dobValue = $old['date_of_birth'] ?? (string) ($member['date_of_birth'] ?? '');
$joiningDateValue = $old['joining_date'] ?? (string) ($member['joining_date'] ?? date('Y-m-d'));
$contractEndValue = $old['contract_end_date'] ?? (string) ($member['contract_end_date'] ?? '');
$leftDateValue = $old['left_date'] ?? (string) ($member['left_date'] ?? '');
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($member === null ? 'Add Staff Member' : 'Edit Staff Member') ?></h1>
    <?php if ($member !== null): ?><p>Public website profile (photo, bio, visibility) is managed under <a href="<?= e(route('admin.faculty.edit', ['id' => $member['id']])) ?>">Faculty</a>.</p><?php endif; ?>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.staff')) ?>"><?= icon('chevron-left') ?> Back to Staff</a>
</div>

<form method="post"
      action="<?= e($member === null ? route('admin.staff.store') : route('admin.staff.update', ['id' => $member['id']])) ?>">
  <?= csrf_field() ?>

  <div class="news-form-grid">
    <div>
      <div class="admin-card">
        <h3 class="card-subhead">Personal Information</h3>
        <div class="field-grid">
          <div class="field">
            <label for="f-first-name">First Name</label>
            <input class="inp" type="text" id="f-first-name" name="first_name" value="<?= e($field('first_name')) ?>" required>
            <?php if (has_error('first_name')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('first_name')) ?></p><?php endif; ?>
          </div>
          <div class="field">
            <label for="f-last-name">Last Name (optional)</label>
            <input class="inp" type="text" id="f-last-name" name="last_name" value="<?= e($field('last_name')) ?>">
          </div>
        </div>

        <div class="field">
          <label for="f-father-name">Father's Name (optional)</label>
          <input class="inp" type="text" id="f-father-name" name="father_name" value="<?= e($field('father_name')) ?>">
        </div>

        <div class="field-grid">
          <div class="field">
            <label for="f-gender">Gender</label>
            <select class="inp" id="f-gender" name="gender" required>
              <option value="">Select&hellip;</option>
              <option value="male" <?= $field('gender') === 'male' ? 'selected' : '' ?>>Male</option>
              <option value="female" <?= $field('gender') === 'female' ? 'selected' : '' ?>>Female</option>
            </select>
            <?php if (has_error('gender')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('gender')) ?></p><?php endif; ?>
          </div>
          <div class="field">
            <label for="f-dob">Date of Birth (optional)</label>
            <input class="inp" type="date" id="f-dob" name="date_of_birth" value="<?= e($dobValue) ?>">
            <?php if (has_error('date_of_birth')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('date_of_birth')) ?></p><?php endif; ?>
          </div>
        </div>

        <div class="field">
          <label for="f-cnic">CNIC (optional)</label>
          <input class="inp" type="text" id="f-cnic" name="cnic" value="<?= e($field('cnic')) ?>" placeholder="xxxxx-xxxxxxx-x" autocomplete="off">
          <p class="help">Encrypted at rest — only shown in full on this edit form, masked everywhere else.</p>
          <?php if (has_error('cnic')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('cnic')) ?></p><?php endif; ?>
        </div>

        <div class="field-grid">
          <div class="field">
            <label for="f-marital">Marital Status (optional)</label>
            <select class="inp" id="f-marital" name="marital_status">
              <option value="">Unspecified</option>
              <?php foreach ($maritalStatuses as $ms): ?>
                <option value="<?= e($ms) ?>" <?= $field('marital_status') === $ms ? 'selected' : '' ?>><?= e(ucfirst($ms)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="f-blood">Blood Group (optional)</label>
            <select class="inp" id="f-blood" name="blood_group">
              <option value="">Unknown</option>
              <?php foreach ($bloodGroups as $bg): ?>
                <option value="<?= e($bg) ?>" <?= $field('blood_group') === $bg ? 'selected' : '' ?>><?= e($bg) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="admin-card">
        <h3 class="card-subhead">Qualification &amp; Role</h3>
        <div class="field-grid">
          <div class="field">
            <label for="f-department">Department (optional)</label>
            <select class="inp" id="f-department" name="department_id">
              <option value="">Unassigned</option>
              <?php foreach ($departments as $dept): ?>
                <option value="<?= (int) $dept['id'] ?>" <?= (string) $field('department_id') === (string) $dept['id'] ? 'selected' : '' ?>><?= e($dept['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="f-designation">Designation (optional)</label>
            <select class="inp" id="f-designation" name="designation_id">
              <option value="">Unassigned</option>
              <?php foreach ($designations as $des): ?>
                <option value="<?= (int) $des['id'] ?>" <?= (string) $field('designation_id') === (string) $des['id'] ? 'selected' : '' ?>><?= e($des['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="field-grid">
          <div class="field">
            <label for="f-qualification">Qualification (optional)</label>
            <input class="inp" type="text" id="f-qualification" name="qualification" value="<?= e($field('qualification')) ?>" placeholder="e.g. MPhil Mathematics">
          </div>
          <div class="field">
            <label for="f-specialization">Specialization (optional)</label>
            <input class="inp" type="text" id="f-specialization" name="specialization" value="<?= e($field('specialization')) ?>">
          </div>
        </div>
        <div class="field">
          <label for="f-experience">Years of Experience</label>
          <input class="inp" type="number" min="0" id="f-experience" name="experience_years" value="<?= e($field('experience_years', '0')) ?>" style="max-width:160px">
        </div>
        <div class="field checkbox-row">
          <input type="checkbox" id="f-teaching" name="is_teaching" value="1" <?= (int) $field('is_teaching', '1') === 1 ? 'checked' : '' ?>>
          <label for="f-teaching" style="margin:0;font-weight:400">This staff member teaches classes</label>
        </div>
      </div>

      <div class="admin-card">
        <h3 class="card-subhead">Contact</h3>
        <div class="field-grid">
          <div class="field">
            <label for="f-phone">Phone (optional)</label>
            <input class="inp" type="text" id="f-phone" name="phone" value="<?= e($field('phone')) ?>">
          </div>
          <div class="field">
            <label for="f-emergency">Emergency Contact (optional)</label>
            <input class="inp" type="text" id="f-emergency" name="emergency_contact" value="<?= e($field('emergency_contact')) ?>">
          </div>
        </div>
        <div class="field">
          <label for="f-email">Email (optional)</label>
          <input class="inp" type="email" id="f-email" name="email" value="<?= e($field('email')) ?>">
          <?php if (has_error('email')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('email')) ?></p><?php endif; ?>
        </div>
        <div class="field">
          <label for="f-address">Address (optional)</label>
          <textarea class="inp" style="height:auto;padding:10px 12px" rows="2" id="f-address" name="address"><?= e($field('address')) ?></textarea>
        </div>
      </div>

      <div class="admin-card">
        <h3 class="card-subhead">Payroll &amp; Bank</h3>
        <div class="field">
          <label for="f-salary">Basic Salary</label>
          <input class="inp" type="number" min="0" step="0.01" id="f-salary" name="basic_salary" value="<?= e($field('basic_salary', '0')) ?>" style="max-width:220px">
        </div>
        <div class="field-grid">
          <div class="field">
            <label for="f-bank">Bank Name (optional)</label>
            <input class="inp" type="text" id="f-bank" name="bank_name" value="<?= e($field('bank_name')) ?>">
          </div>
          <div class="field">
            <label for="f-account">Account No. (optional)</label>
            <input class="inp" type="text" id="f-account" name="account_no" value="<?= e($field('account_no')) ?>" autocomplete="off">
            <p class="help">Encrypted at rest.</p>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><?= $member === null ? 'Create Staff Record' : 'Save Changes' ?></button>
    </div>

    <aside>
      <div class="admin-card">
        <h3 class="card-subhead">Employment</h3>
        <div class="field">
          <label for="f-employee-no">Employee No.</label>
          <input class="inp" type="text" id="f-employee-no" name="employee_no" value="<?= e($old['employee_no'] ?? $suggestedNo) ?>" required>
          <?php if (has_error('employee_no')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('employee_no')) ?></p><?php endif; ?>
        </div>
        <div class="field">
          <label for="f-joining-date">Joining Date</label>
          <input class="inp" type="date" id="f-joining-date" name="joining_date" value="<?= e($joiningDateValue) ?>" required>
          <?php if (has_error('joining_date')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('joining_date')) ?></p><?php endif; ?>
        </div>
        <div class="field">
          <label for="f-employment-type">Employment Type</label>
          <select class="inp" id="f-employment-type" name="employment_type">
            <?php foreach ($employmentTypes as $et): ?>
              <option value="<?= e($et) ?>" <?= $field('employment_type', 'probation') === $et ? 'selected' : '' ?>><?= e(ucfirst($et)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="f-contract-end">Contract End Date (optional)</label>
          <input class="inp" type="date" id="f-contract-end" name="contract_end_date" value="<?= e($contractEndValue) ?>">
          <?php if (has_error('contract_end_date')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('contract_end_date')) ?></p><?php endif; ?>
        </div>
        <div class="field">
          <label for="f-status">Status</label>
          <select class="inp" id="f-status" name="status">
            <?php foreach ($statuses as $st): ?>
              <option value="<?= e($st) ?>" <?= $field('status', 'active') === $st ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $st))) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="f-left-date">Left Date (optional)</label>
          <input class="inp" type="date" id="f-left-date" name="left_date" value="<?= e($leftDateValue) ?>">
          <?php if (has_error('left_date')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('left_date')) ?></p><?php endif; ?>
        </div>
      </div>
    </aside>
  </div>
</form>
