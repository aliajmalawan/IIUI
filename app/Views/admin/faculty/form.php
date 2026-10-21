<?php
/**
 * @var array|null $member
 * @var string $suggestedNo
 * @var array $departments
 * @var array $designations
 */
$activeTab = 'faculty';
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($member[$key] ?? $default);
$joiningDateValue = $old['joining_date'] ?? (string) ($member['joining_date'] ?? date('Y-m-d'));
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($member === null ? 'Add Faculty Member' : 'Edit Faculty Member') ?></h1>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.faculty')) ?>"><?= icon('chevron-left') ?> Back to Faculty</a>
</div>

<form method="post"
      action="<?= e($member === null ? route('admin.faculty.store') : route('admin.faculty.update', ['id' => $member['id']])) ?>"
      enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="news-form-grid">
    <div>
      <div class="admin-card">
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
          <label for="f-slug">URL Slug (optional)</label>
          <input class="inp" type="text" id="f-slug" name="slug" value="<?= e($field('slug')) ?>" placeholder="auto-generated from the name">
          <p class="help">Leave blank to auto-generate. Shown as <code>/faculty/your-slug</code>.</p>
        </div>

        <div class="field-grid">
          <div class="field">
            <label for="f-qualification">Qualification (optional)</label>
            <input class="inp" type="text" id="f-qualification" name="qualification" value="<?= e($field('qualification')) ?>" placeholder="e.g. MPhil Mathematics">
          </div>
          <div class="field">
            <label for="f-specialization">Specialization (optional)</label>
            <input class="inp" type="text" id="f-specialization" name="specialization" value="<?= e($field('specialization')) ?>" placeholder="e.g. Applied Statistics">
          </div>
        </div>

        <div class="field">
          <label for="f-experience">Years of Experience</label>
          <input class="inp" type="number" min="0" id="f-experience" name="experience_years" value="<?= e($field('experience_years', '0')) ?>" style="max-width:160px">
        </div>

        <div class="field">
          <label for="f-bio">Bio (optional)</label>
          <textarea class="inp" style="height:auto;padding:10px 12px" rows="5" id="f-bio" name="bio"
                     placeholder="Shown on the faculty member's public profile page"><?= e($field('bio')) ?></textarea>
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><?= $member === null ? 'Create Faculty Profile' : 'Save Changes' ?></button>
    </div>

    <aside>
      <div class="admin-card">
        <h3 class="card-subhead">Department &amp; Role</h3>
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

      <div class="admin-card">
        <h3 class="card-subhead">HR Record</h3>
        <div class="field">
          <label for="f-employee-no">Employee No.</label>
          <input class="inp" type="text" id="f-employee-no" name="employee_no" value="<?= e($old['employee_no'] ?? $suggestedNo) ?>" required>
          <?php if (has_error('employee_no')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('employee_no')) ?></p><?php endif; ?>
        </div>
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
          <label for="f-joining-date">Joining Date</label>
          <input class="inp" type="date" id="f-joining-date" name="joining_date" value="<?= e($joiningDateValue) ?>" required>
          <?php if (has_error('joining_date')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('joining_date')) ?></p><?php endif; ?>
        </div>
      </div>

      <div class="admin-card">
        <h3 class="card-subhead">Public Visibility</h3>
        <div class="field checkbox-row">
          <input type="checkbox" id="f-public" name="is_public_profile" value="1" <?= (int) $field('is_public_profile', '0') === 1 ? 'checked' : '' ?>>
          <label for="f-public" style="margin:0;font-weight:400">Show on the public Faculty page</label>
        </div>
        <div class="field">
          <label for="f-sort">Display Order</label>
          <input class="inp" type="number" id="f-sort" name="sort_order" value="<?= e($field('sort_order', '0')) ?>">
        </div>
      </div>

      <div class="admin-card">
        <h3 class="card-subhead">Photo</h3>
        <div class="upload-preview" style="width:100%;height:160px;margin-bottom:var(--space-3)">
          <?php if ($member !== null && $member['photo'] && is_file(PUBLIC_PATH . $member['photo'])): ?>
            <img src="<?= e(base_url(ltrim($member['photo'], '/'))) ?>" alt="">
          <?php else: ?>
            <?= icon('person-badge') ?>
          <?php endif; ?>
        </div>
        <input type="file" id="f-photo" name="photo" accept="image/png,image/jpeg,image/webp">
        <?php if (has_error('photo')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('photo')) ?></p><?php endif; ?>
      </div>
    </aside>
  </div>
</form>
