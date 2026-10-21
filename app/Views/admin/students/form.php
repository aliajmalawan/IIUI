<?php
/**
 * @var array|null $student
 * @var string $suggestedRegNo
 * @var string $suggestedAdmissionNo
 * @var array $classes
 * @var array $sections
 */
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => $old[$key] ?? (string) ($student[$key] ?? $default);
$dobValue = $old['date_of_birth'] ?? (string) ($student['date_of_birth'] ?? '');
$admissionDateValue = $old['admission_date'] ?? (string) ($student['admission_date'] ?? date('Y-m-d'));
$sectionsByClass = [];
foreach ($sections as $sec) {
    $sectionsByClass[(int) $sec['class_id']][] = $sec;
}
?>
<div class="admin-page-header">
  <div>
    <h1><?= e($student === null ? 'Add Student' : 'Edit Student') ?></h1>
  </div>
  <a class="btn btn-secondary" href="<?= e(route('admin.students')) ?>"><?= icon('chevron-left') ?> Back to Students</a>
</div>

<form method="post"
      action="<?= e($student === null ? route('admin.students.store') : route('admin.students.update', ['id' => $student['id']])) ?>"
      enctype="multipart/form-data">
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
          <label for="f-name-ur">Name in Urdu (optional)</label>
          <input class="inp" type="text" id="f-name-ur" name="name_ur" value="<?= e($field('name_ur')) ?>" dir="rtl">
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
            <label for="f-dob">Date of Birth</label>
            <input class="inp" type="date" id="f-dob" name="date_of_birth" value="<?= e($dobValue) ?>" required>
            <?php if (has_error('date_of_birth')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('date_of_birth')) ?></p><?php endif; ?>
          </div>
        </div>

        <div class="field-grid">
          <div class="field">
            <label for="f-bform">B-Form No. (optional)</label>
            <input class="inp" type="text" id="f-bform" name="b_form_no" value="<?= e($field('b_form_no')) ?>" placeholder="xxxxx-xxxxxxx-x">
          </div>
          <div class="field">
            <label for="f-blood">Blood Group (optional)</label>
            <select class="inp" id="f-blood" name="blood_group">
              <option value="">Unknown</option>
              <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg): ?>
                <option value="<?= e($bg) ?>" <?= $field('blood_group') === $bg ? 'selected' : '' ?>><?= e($bg) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="field-grid">
          <div class="field">
            <label for="f-religion">Religion</label>
            <input class="inp" type="text" id="f-religion" name="religion" value="<?= e($field('religion', 'Islam')) ?>">
          </div>
          <div class="field">
            <label for="f-nationality">Nationality</label>
            <input class="inp" type="text" id="f-nationality" name="nationality" value="<?= e($field('nationality', 'Pakistani')) ?>">
          </div>
        </div>
      </div>

      <div class="admin-card">
        <h3 class="card-subhead">Parent / Guardian Information</h3>
        <div class="field-grid">
          <div class="field">
            <label for="f-father-name">Father's Name</label>
            <input class="inp" type="text" id="f-father-name" name="father_name" value="<?= e($field('father_name')) ?>" required>
            <?php if (has_error('father_name')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('father_name')) ?></p><?php endif; ?>
          </div>
          <div class="field">
            <label for="f-father-cnic">Father's CNIC (optional)</label>
            <input class="inp" type="text" id="f-father-cnic" name="father_cnic" value="<?= e($field('father_cnic')) ?>" placeholder="xxxxx-xxxxxxx-x">
          </div>
        </div>
        <div class="field-grid">
          <div class="field">
            <label for="f-father-occupation">Father's Occupation (optional)</label>
            <input class="inp" type="text" id="f-father-occupation" name="father_occupation" value="<?= e($field('father_occupation')) ?>">
          </div>
          <div class="field">
            <label for="f-father-phone">Father's Phone (optional)</label>
            <input class="inp" type="text" id="f-father-phone" name="father_phone" value="<?= e($field('father_phone')) ?>">
          </div>
        </div>
        <div class="field-grid">
          <div class="field">
            <label for="f-mother-name">Mother's Name (optional)</label>
            <input class="inp" type="text" id="f-mother-name" name="mother_name" value="<?= e($field('mother_name')) ?>">
          </div>
          <div class="field">
            <label for="f-mother-cnic">Mother's CNIC (optional)</label>
            <input class="inp" type="text" id="f-mother-cnic" name="mother_cnic" value="<?= e($field('mother_cnic')) ?>" placeholder="xxxxx-xxxxxxx-x">
          </div>
        </div>
      </div>

      <div class="admin-card">
        <h3 class="card-subhead">Contact &amp; Address</h3>
        <div class="field-grid">
          <div class="field">
            <label for="f-phone">Phone (optional)</label>
            <input class="inp" type="text" id="f-phone" name="phone" value="<?= e($field('phone')) ?>">
          </div>
          <div class="field">
            <label for="f-email">Email (optional)</label>
            <input class="inp" type="email" id="f-email" name="email" value="<?= e($field('email')) ?>">
            <?php if (has_error('email')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('email')) ?></p><?php endif; ?>
          </div>
        </div>
        <div class="field">
          <label for="f-address">Address (optional)</label>
          <textarea class="inp" style="height:auto;padding:10px 12px" rows="2" id="f-address" name="address"><?= e($field('address')) ?></textarea>
        </div>
        <div class="field-grid">
          <div class="field">
            <label for="f-city">City</label>
            <input class="inp" type="text" id="f-city" name="city" value="<?= e($field('city')) ?>">
          </div>
          <div class="field">
            <label for="f-district">District</label>
            <input class="inp" type="text" id="f-district" name="district" value="<?= e($field('district', 'Mardan')) ?>">
          </div>
        </div>
        <div class="field-grid">
          <div class="field">
            <label for="f-province">Province</label>
            <input class="inp" type="text" id="f-province" name="province" value="<?= e($field('province', 'Khyber Pakhtunkhwa')) ?>">
          </div>
          <div class="field">
            <label for="f-postal">Postal Code (optional)</label>
            <input class="inp" type="text" id="f-postal" name="postal_code" value="<?= e($field('postal_code')) ?>">
          </div>
        </div>
      </div>

      <div class="admin-card">
        <h3 class="card-subhead">Previous School (optional)</h3>
        <div class="field-grid">
          <div class="field">
            <label for="f-prev-school">Previous School</label>
            <input class="inp" type="text" id="f-prev-school" name="previous_school" value="<?= e($field('previous_school')) ?>">
          </div>
          <div class="field">
            <label for="f-prev-class">Previous Class</label>
            <input class="inp" type="text" id="f-prev-class" name="previous_class" value="<?= e($field('previous_class')) ?>">
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><?= $student === null ? 'Create Student Record' : 'Save Changes' ?></button>
    </div>

    <aside>
      <div class="admin-card">
        <h3 class="card-subhead">Admission &amp; Class</h3>
        <div class="field">
          <label for="f-reg-no">Registration No.</label>
          <input class="inp" type="text" id="f-reg-no" name="registration_no" value="<?= e($old['registration_no'] ?? $suggestedRegNo) ?>" required>
          <?php if (has_error('registration_no')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('registration_no')) ?></p><?php endif; ?>
        </div>
        <div class="field">
          <label for="f-adm-no">Admission No.</label>
          <input class="inp" type="text" id="f-adm-no" name="admission_no" value="<?= e($old['admission_no'] ?? $suggestedAdmissionNo) ?>" required>
          <?php if (has_error('admission_no')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('admission_no')) ?></p><?php endif; ?>
        </div>
        <div class="field">
          <label for="f-admission-date">Admission Date</label>
          <input class="inp" type="date" id="f-admission-date" name="admission_date" value="<?= e($admissionDateValue) ?>" required>
          <?php if (has_error('admission_date')): ?><p class="help" style="color:var(--danger-700)"><?= e((string) error('admission_date')) ?></p><?php endif; ?>
        </div>
        <div class="field">
          <label for="f-admission-class">Admission Class (optional)</label>
          <select class="inp" id="f-admission-class" name="admission_class_id">
            <option value="">Unspecified</option>
            <?php foreach ($classes as $class): ?>
              <option value="<?= (int) $class['id'] ?>" <?= $field('admission_class_id') === (string) $class['id'] ? 'selected' : '' ?>><?= e($class['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="f-current-class">Current Class</label>
          <select class="inp" id="f-current-class" name="current_class_id" data-section-source>
            <option value="">Select&hellip;</option>
            <?php foreach ($classes as $class): ?>
              <option value="<?= (int) $class['id'] ?>" <?= $field('current_class_id') === (string) $class['id'] ? 'selected' : '' ?>><?= e($class['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="f-current-section">Current Section</label>
          <select class="inp" id="f-current-section" name="current_section_id" data-section-target>
            <option value="">Select a class first</option>
          </select>
        </div>
        <div class="field">
          <label for="f-status">Status</label>
          <select class="inp" id="f-status" name="status">
            <?php foreach (['active', 'graduated', 'left', 'suspended', 'expelled', 'transferred'] as $st): ?>
              <option value="<?= e($st) ?>" <?= $field('status', 'active') === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="admin-card">
        <h3 class="card-subhead">Additional</h3>
        <div class="field checkbox-row">
          <input type="checkbox" id="f-transport" name="transport_required" value="1" <?= (int) $field('transport_required', '0') === 1 ? 'checked' : '' ?>>
          <label for="f-transport" style="margin:0;font-weight:400">Requires school transport</label>
        </div>
        <div class="field checkbox-row">
          <input type="checkbox" id="f-orphan" name="is_orphan" value="1" <?= (int) $field('is_orphan', '0') === 1 ? 'checked' : '' ?>>
          <label for="f-orphan" style="margin:0;font-weight:400">Orphan</label>
        </div>
        <div class="field checkbox-row">
          <input type="checkbox" id="f-disabled" name="is_disabled" value="1" <?= (int) $field('is_disabled', '0') === 1 ? 'checked' : '' ?>>
          <label for="f-disabled" style="margin:0;font-weight:400">Has a disability</label>
        </div>
        <div class="field">
          <label for="f-notes">Special Notes (optional)</label>
          <textarea class="inp" style="height:auto;padding:10px 12px" rows="3" id="f-notes" name="special_notes"><?= e($field('special_notes')) ?></textarea>
        </div>
      </div>

      <div class="admin-card">
        <h3 class="card-subhead">Photo</h3>
        <div class="upload-preview" style="width:100%;height:160px;margin-bottom:var(--space-3)">
          <?php if ($student !== null && $student['photo'] && is_file(PUBLIC_PATH . $student['photo'])): ?>
            <img src="<?= e(base_url(ltrim($student['photo'], '/'))) ?>" alt="">
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

<script>
(function () {
  var sectionsByClass = <?= json_encode($sectionsByClass, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var classSelect = document.querySelector('[data-section-source]');
  var sectionSelect = document.querySelector('[data-section-target]');
  var initialSection = '<?= e($field('current_section_id')) ?>';

  function renderSections(classId, selected) {
    var options = sectionsByClass[classId] || [];
    sectionSelect.innerHTML = '';
    if (options.length === 0) {
      sectionSelect.appendChild(new Option('No sections available', ''));
      return;
    }
    sectionSelect.appendChild(new Option('Select…', ''));
    options.forEach(function (sec) {
      var opt = new Option(sec.name, sec.id);
      if (String(sec.id) === String(selected)) opt.selected = true;
      sectionSelect.appendChild(opt);
    });
  }

  if (classSelect && sectionSelect) {
    if (classSelect.value) renderSections(classSelect.value, initialSection);
    classSelect.addEventListener('change', function () {
      renderSections(classSelect.value, '');
    });
  }
})();
</script>
