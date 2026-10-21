<?php
/**
 * Standalone, multi-step application form — posts to the same
 * admissions.apply endpoint as before; only the presentation changed.
 * @var array<string,array<int,array<string,mixed>>> $classesByWing
 * @var array $streams
 * @var bool $sessionOpen
 * @var array<string,bool> $requiredMap field => is required, admin-configurable via Settings -> Admission Form
 */
$wingLabels = ['primary' => 'Primary', 'middle' => 'Middle', 'secondary' => 'Secondary', 'college' => 'College'];
$old = flash('_old_input', []);
$field = static fn (string $key, string $default = '') => (string) ($old[$key] ?? $default);
// $req($x) => 'required' attribute or '' ; $opt($x, $label) => label with "(optional)" appended when not required.
$req = static fn (string $key) => ($requiredMap[$key] ?? true) ? 'required' : '';
$opt = static fn (string $key, string $label) => ($requiredMap[$key] ?? true) ? $label : $label . ' (optional)';

// Jump straight to the earliest step that failed validation, rather than
// making the applicant click back through steps they already got right.
$stepFields = [
    1 => ['applicant_name', 'father_name', 'b_form_no', 'date_of_birth', 'gender', 'religion', 'city', 'phone', 'whatsapp', 'email', 'address'],
    2 => ['class_id', 'stream_id', 'previous_school'],
    3 => ['matric_board', 'matric_year', 'matric_marks', 'matric_total_marks'],
    4 => ['confirm'],
];
$initialStep = 1;
foreach ($stepFields as $stepNum => $fields) {
    foreach ($fields as $f) {
        if (has_error($f)) {
            $initialStep = $stepNum;
            break 2;
        }
    }
}
?>
<section class="hero-minimal">
  <div class="hero-khatam" aria-hidden="true"></div>
  <div class="container">
    <p class="hero-eyebrow">Admissions</p>
    <h1 class="hero-title">Admission Form</h1>
    <p class="hero-lead">Complete the steps below to apply for a seat at IIUI Schools &amp; College, Mardan Campus.</p>
  </div>
</section>

<div class="container section-block">
  <?php if ($admissionStatus = flash('admission_status')): ?>
    <div class="alert-box alert-success contact-alert"><?= e((string) $admissionStatus) ?></div>
  <?php endif; ?>

  <?php if (!$sessionOpen): ?>
    <div class="alert-box alert-error contact-alert">Admissions are currently closed. Please check back later or contact us for more information.</div>
  <?php else: ?>
    <div class="form-stepper" role="list">
      <div class="form-stepper-step" data-stepper="1" role="listitem">
        <span class="form-stepper-circle">1</span>
        <span class="form-stepper-label">Personal Information</span>
      </div>
      <div class="form-stepper-connector"></div>
      <div class="form-stepper-step" data-stepper="2" role="listitem">
        <span class="form-stepper-circle">2</span>
        <span class="form-stepper-label">Program</span>
      </div>
      <div class="form-stepper-connector"></div>
      <div class="form-stepper-step" data-stepper="3" role="listitem">
        <span class="form-stepper-circle">3</span>
        <span class="form-stepper-label">Academic Information</span>
      </div>
      <div class="form-stepper-connector"></div>
      <div class="form-stepper-step" data-stepper="4" role="listitem">
        <span class="form-stepper-circle">4</span>
        <span class="form-stepper-label">Review &amp; Submit</span>
      </div>
    </div>

    <form class="contact-form admission-form" id="admission-form" data-submit-lock method="post" action="<?= e(route('admissions.apply')) ?>" data-initial-step="<?= (int) $initialStep ?>" novalidate>
      <?= csrf_field() ?>
      <div style="position:absolute;left:-9999px" aria-hidden="true">
        <label for="f-website">Leave this field blank</label>
        <input type="text" id="f-website" name="website" tabindex="-1" autocomplete="off">
      </div>

      <div class="form-step" data-step="1">
<h2 class="visually-hidden">Application form</h2>
        <h3 class="apply-form-subhead">Personal Information</h3>
        <div class="field-grid">
          <div class="field">
            <label for="f-applicant-name"><?= e($opt('applicant_name', "Student's Full Name")) ?></label>
            <input class="inp" type="text" id="f-applicant-name" name="applicant_name" maxlength="120" autocomplete="off"<?= has_error('applicant_name') ? ' aria-invalid="true" aria-describedby="err-applicant_name"' : '' ?> value="<?= e($field('applicant_name')) ?>" <?= $req('applicant_name') ?>>
            <?php if (has_error('applicant_name')): ?><p class="field-error" id="err-applicant_name" role="alert"><?= e((string) error('applicant_name')) ?></p><?php endif; ?>
          </div>
          <div class="field">
            <label for="f-father-name"><?= e($opt('father_name', "Father's Name")) ?></label>
            <input class="inp" type="text" id="f-father-name" name="father_name" maxlength="120" autocomplete="off"<?= has_error('father_name') ? ' aria-invalid="true" aria-describedby="err-father_name"' : '' ?> value="<?= e($field('father_name')) ?>" <?= $req('father_name') ?>>
            <?php if (has_error('father_name')): ?><p class="field-error" id="err-father_name" role="alert"><?= e((string) error('father_name')) ?></p><?php endif; ?>
          </div>
        </div>
        <div class="field-grid">
          <div class="field">
            <label for="f-bform"><?= e($opt('b_form_no', 'CNIC / B-Form No.')) ?></label>
            <input class="inp" type="text" id="f-bform" name="b_form_no" maxlength="20" autocomplete="off" inputmode="numeric"<?= has_error('b_form_no') ? ' aria-invalid="true" aria-describedby="err-b_form_no"' : '' ?> value="<?= e($field('b_form_no')) ?>" placeholder="42101-1234567-1" <?= $req('b_form_no') ?>>
            <?php if (has_error('b_form_no')): ?><p class="field-error" id="err-b_form_no" role="alert"><?= e((string) error('b_form_no')) ?></p><?php endif; ?>
          </div>
          <div class="field">
            <label for="f-dob"><?= e($opt('date_of_birth', 'Date of Birth')) ?></label>
            <input class="inp" type="date" id="f-dob" max="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d', strtotime('-40 years')) ?>" name="date_of_birth"<?= has_error('date_of_birth') ? ' aria-invalid="true" aria-describedby="err-date_of_birth"' : '' ?> value="<?= e($field('date_of_birth')) ?>" <?= $req('date_of_birth') ?>>
            <?php if (has_error('date_of_birth')): ?><p class="field-error" id="err-date_of_birth" role="alert"><?= e((string) error('date_of_birth')) ?></p><?php endif; ?>
          </div>
        </div>
        <div class="field">
          <label>Gender</label>
          <div class="radio-row">
            <label class="radio-inline"><input type="radio" name="gender" value="male" <?= $field('gender') === 'male' ? 'checked' : '' ?> required> Male</label>
            <label class="radio-inline"><input type="radio" name="gender" value="female" <?= $field('gender') === 'female' ? 'checked' : '' ?>> Female</label>
          </div>
          <?php if (has_error('gender')): ?><p class="field-error" id="err-gender" role="alert"><?= e((string) error('gender')) ?></p><?php endif; ?>
        </div>
        <div class="field-grid">
          <div class="field">
            <label for="f-religion"><?= e($opt('religion', 'Religion')) ?></label>
            <input class="inp" type="text" id="f-religion" name="religion" maxlength="40" autocomplete="off"<?= has_error('religion') ? ' aria-invalid="true" aria-describedby="err-religion"' : '' ?> value="<?= e($field('religion')) ?>" <?= $req('religion') ?>>
          </div>
          <div class="field">
            <label for="f-city"><?= e($opt('city', 'City')) ?></label>
            <input class="inp" type="text" id="f-city" name="city" maxlength="80" autocomplete="address-level2"<?= has_error('city') ? ' aria-invalid="true" aria-describedby="err-city"' : '' ?> value="<?= e($field('city')) ?>" <?= $req('city') ?>>
          </div>
        </div>
        <div class="field-grid">
          <div class="field">
            <label for="f-phone"><?= e($opt('phone', 'Phone Number')) ?></label>
            <input class="inp" type="tel" id="f-phone" name="phone" maxlength="20" autocomplete="tel" inputmode="tel"<?= has_error('phone') ? ' aria-invalid="true" aria-describedby="err-phone"' : '' ?> value="<?= e($field('phone')) ?>" placeholder="03xx-xxxxxxx" <?= $req('phone') ?>>
            <p class="help">We'll send the assessment date and result updates here.</p>
            <?php if (has_error('phone')): ?><p class="field-error" id="err-phone" role="alert"><?= e((string) error('phone')) ?></p><?php endif; ?>
          </div>
          <div class="field">
            <label for="f-whatsapp"><?= e($opt('whatsapp', 'WhatsApp Number')) ?></label>
            <input class="inp" type="tel" id="f-whatsapp" name="whatsapp" maxlength="20" autocomplete="tel" inputmode="tel"<?= has_error('whatsapp') ? ' aria-invalid="true" aria-describedby="err-whatsapp"' : '' ?> value="<?= e($field('whatsapp')) ?>" placeholder="03xx-xxxxxxx" <?= $req('whatsapp') ?>>
            <?php if (has_error('whatsapp')): ?><p class="field-error" id="err-whatsapp" role="alert"><?= e((string) error('whatsapp')) ?></p><?php endif; ?>
          </div>
        </div>
        <div class="field">
          <label for="f-email"><?= e($opt('email', 'Email Address')) ?></label>
          <input class="inp" type="email" id="f-email" name="email" maxlength="191" autocomplete="email" inputmode="email"<?= has_error('email') ? ' aria-invalid="true" aria-describedby="err-email"' : '' ?> value="<?= e($field('email')) ?>" <?= $req('email') ?>>
          <?php if (has_error('email')): ?><p class="field-error" id="err-email" role="alert"><?= e((string) error('email')) ?></p><?php endif; ?>
        </div>
        <div class="field">
          <label for="f-address"><?= e($opt('address', 'Home Address')) ?></label>
          <input class="inp" type="text" id="f-address" name="address" maxlength="255" autocomplete="street-address"<?= has_error('address') ? ' aria-invalid="true" aria-describedby="err-address"' : '' ?> value="<?= e($field('address')) ?>" <?= $req('address') ?>>
          <?php if (has_error('address')): ?><p class="field-error" id="err-address" role="alert"><?= e((string) error('address')) ?></p><?php endif; ?>
        </div>
        <div class="form-step-actions">
          <span></span>
          <button type="button" class="btn btn-primary form-step-next">Next <?= icon('arrow-right', 'icon') ?></button>
        </div>
      </div>

      <div class="form-step" data-step="2" hidden>
        <h3 class="apply-form-subhead">Program</h3>
        <div class="field-grid">
          <div class="field">
            <label for="f-class">Class Applying For</label>
            <select class="inp" id="f-class" name="class_id"<?= has_error('class_id') ? ' aria-invalid="true" aria-describedby="err-class_id"' : '' ?> required>
              <option value="">Select a class&hellip;</option>
              <?php foreach ($classesByWing as $wing => $classes): ?>
                <?php if ($classes !== []): ?>
                  <optgroup label="<?= e($wingLabels[$wing] ?? ucfirst($wing)) ?>">
                    <?php foreach ($classes as $class): ?>
                      <option value="<?= (int) $class['id'] ?>" data-wing="<?= e($wing) ?>" <?= $field('class_id') === (string) $class['id'] ? 'selected' : '' ?>><?= e($class['name']) ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
            <?php if (has_error('class_id')): ?><p class="field-error" id="err-class_id" role="alert"><?= e((string) error('class_id')) ?></p><?php endif; ?>
          </div>
          <div class="field" id="f-stream-field" hidden>
            <label for="f-stream">Stream</label>
            <select class="inp" id="f-stream" name="stream_id"<?= has_error('stream_id') ? ' aria-invalid="true" aria-describedby="err-stream_id"' : '' ?>>
              <option value="">Select a stream&hellip;</option>
              <?php foreach ($streams as $stream): ?>
                <option value="<?= (int) $stream['id'] ?>" <?= $field('stream_id') === (string) $stream['id'] ? 'selected' : '' ?>><?= e($stream['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (has_error('stream_id')): ?><p class="field-error" id="err-stream_id" role="alert"><?= e((string) error('stream_id')) ?></p><?php endif; ?>
          </div>
        </div>
        <div class="field">
          <label for="f-prev-school"><?= e($opt('previous_school', 'Previous School')) ?></label>
          <input class="inp" type="text" id="f-prev-school" name="previous_school" maxlength="160" autocomplete="off"<?= has_error('previous_school') ? ' aria-invalid="true" aria-describedby="err-previous_school"' : '' ?> value="<?= e($field('previous_school')) ?>" <?= $req('previous_school') ?>>
        </div>
        <div class="form-step-actions">
          <button type="button" class="btn btn-secondary form-step-back"><?= icon('chevron-left', 'icon') ?> Back</button>
          <button type="button" class="btn btn-primary form-step-next">Next <?= icon('arrow-right', 'icon') ?></button>
        </div>
      </div>

      <div class="form-step" data-step="3" hidden>
        <h3 class="apply-form-subhead">Academic Information</h3>

        <div id="academic-info-message" class="alert-box alert-info" hidden>
          Academic history isn't required for this class — you can go straight to the next step.
        </div>

        <div id="matric-fieldset" hidden>
          <p class="help" style="margin-bottom: var(--space-4)">Matriculation (Class X) result — required to apply for College-level classes.</p>
          <div class="field-grid">
            <div class="field">
              <label for="f-matric-board"><?= e($opt('matric_board', 'Board')) ?></label>
              <input class="inp" type="text" id="f-matric-board" name="matric_board" maxlength="120"<?= has_error('matric_board') ? ' aria-invalid="true" aria-describedby="err-matric_board"' : '' ?> value="<?= e($field('matric_board')) ?>" placeholder="e.g. BISE Mardan" data-admin-required="<?= $requiredMap['matric_board'] ?? true ? '1' : '0' ?>">
              <?php if (has_error('matric_board')): ?><p class="field-error" id="err-matric_board" role="alert"><?= e((string) error('matric_board')) ?></p><?php endif; ?>
            </div>
            <div class="field">
              <label for="f-matric-year"><?= e($opt('matric_year', 'Year')) ?></label>
              <input class="inp" type="text" id="f-matric-year" name="matric_year"<?= has_error('matric_year') ? ' aria-invalid="true" aria-describedby="err-matric_year"' : '' ?> value="<?= e($field('matric_year')) ?>" placeholder="e.g. 2024" maxlength="4" inputmode="numeric" data-admin-required="<?= $requiredMap['matric_year'] ?? true ? '1' : '0' ?>">
              <?php if (has_error('matric_year')): ?><p class="field-error" id="err-matric_year" role="alert"><?= e((string) error('matric_year')) ?></p><?php endif; ?>
            </div>
          </div>
          <div class="field-grid">
            <div class="field">
              <label for="f-matric-marks"><?= e($opt('matric_marks', 'Marks Obtained')) ?></label>
              <input class="inp" type="number" id="f-matric-marks" name="matric_marks"<?= has_error('matric_marks') ? ' aria-invalid="true" aria-describedby="err-matric_marks"' : '' ?> value="<?= e($field('matric_marks')) ?>" min="0" data-admin-required="<?= $requiredMap['matric_marks'] ?? true ? '1' : '0' ?>">
              <?php if (has_error('matric_marks')): ?><p class="field-error" id="err-matric_marks" role="alert"><?= e((string) error('matric_marks')) ?></p><?php endif; ?>
            </div>
            <div class="field">
              <label for="f-matric-total"><?= e($opt('matric_total_marks', 'Total Marks')) ?></label>
              <input class="inp" type="number" id="f-matric-total" name="matric_total_marks"<?= has_error('matric_total_marks') ? ' aria-invalid="true" aria-describedby="err-matric_total_marks"' : '' ?> value="<?= e($field('matric_total_marks')) ?>" min="0" data-admin-required="<?= $requiredMap['matric_total_marks'] ?? true ? '1' : '0' ?>">
              <?php if (has_error('matric_total_marks')): ?><p class="field-error" id="err-matric_total_marks" role="alert"><?= e((string) error('matric_total_marks')) ?></p><?php endif; ?>
            </div>
          </div>
        </div>

        <div class="form-step-actions">
          <button type="button" class="btn btn-secondary form-step-back"><?= icon('chevron-left', 'icon') ?> Back</button>
          <button type="button" class="btn btn-primary form-step-next">Next <?= icon('arrow-right', 'icon') ?></button>
        </div>
      </div>

      <div class="form-step" data-step="4" hidden>
        <h3 class="apply-form-subhead">Review &amp; Submit</h3>
        <div class="review-summary" id="review-summary"></div>

        <div class="apply-docs apply-docs--inline">
          <h3><?= icon('folder2') ?> Documents Required at Interview</h3>
          <ul class="doc-checklist">
            <li>Student's B-Form / CNIC (original + copy)</li>
            <li>Guardian's CNIC (copy)</li>
            <li>School leaving certificate (if applicable)</li>
            <li>4 recent passport-size photographs</li>
          </ul>
          <p class="help">These aren't uploaded online — bring them to the interview once your application is reviewed.</p>
        </div>

        <div class="field checkbox-row">
          <input type="checkbox" id="f-confirm" name="confirm"<?= has_error('confirm') ? ' aria-invalid="true" aria-describedby="err-confirm"' : '' ?> value="1" <?= $field('confirm') === '1' ? 'checked' : '' ?> required>
          <label for="f-confirm" style="margin:0;font-weight:400">I confirm the information above is accurate to the best of my knowledge.</label>
        </div>
        <?php if (has_error('confirm')): ?><p class="field-error" id="err-confirm" role="alert"><?= e((string) error('confirm')) ?></p><?php endif; ?>

        <div class="form-step-actions">
          <button type="button" class="btn btn-secondary form-step-back"><?= icon('chevron-left', 'icon') ?> Back</button>
          <button type="submit" class="btn btn-primary btn-lg" data-loading-text="Submitting…"><?= icon('send') ?> <span>Submit Application</span></button>
        </div>
      </div>
    </form>
  <?php endif; ?>
</div>

<script>
(function () {
  var form = document.getElementById('admission-form');
  if (!form) { return; }

  var steps = Array.prototype.slice.call(form.querySelectorAll('.form-step'));
  var stepperItems = Array.prototype.slice.call(document.querySelectorAll('.form-stepper-step'));
  var connectors = Array.prototype.slice.call(document.querySelectorAll('.form-stepper-connector'));
  var current = parseInt(form.dataset.initialStep, 10) || 1;

  function showStep(n) {
    steps.forEach(function (stepEl) {
      var isTarget = parseInt(stepEl.dataset.step, 10) === n;
      stepEl.hidden = !isTarget;
    });
    stepperItems.forEach(function (item) {
      var num = parseInt(item.dataset.stepper, 10);
      item.classList.toggle('is-active', num === n);
      item.classList.toggle('is-complete', num < n);
    });
    connectors.forEach(function (c, i) {
      c.classList.toggle('is-complete', (i + 1) < n);
    });
    current = n;
    if (n === 4) { populateReview(); }
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function validateStep(n) {
    var stepEl = form.querySelector('.form-step[data-step="' + n + '"]');
    var fields = stepEl.querySelectorAll('input, select');
    var valid = true;
    fields.forEach(function (f) {
      if (f.offsetParent === null) { return; } // skip fields inside a hidden block (e.g. matric fieldset when not needed)
      if (!f.checkValidity()) { valid = false; f.reportValidity(); }
    });
    return valid;
  }

  form.querySelectorAll('.form-step-next').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (validateStep(current)) { showStep(current + 1); }
    });
  });
  form.querySelectorAll('.form-step-back').forEach(function (btn) {
    btn.addEventListener('click', function () { showStep(current - 1); });
  });

  form.addEventListener('submit', function (e) {
    for (var i = 1; i <= steps.length; i++) {
      if (!validateStep(i)) {
        e.preventDefault();
        showStep(i);
        return;
      }
    }
  });

  function populateReview() {
    var box = document.getElementById('review-summary');
    if (!box) { return; }

    var classSelect = document.getElementById('f-class');
    var className = classSelect.selectedIndex > -1 ? classSelect.options[classSelect.selectedIndex].text : '';
    var streamSelect = document.getElementById('f-stream');
    var streamName = streamSelect.selectedIndex > 0 ? streamSelect.options[streamSelect.selectedIndex].text : '';
    var genderInput = form.querySelector('input[name="gender"]:checked');
    var needsMatric = !document.getElementById('matric-fieldset').hidden;

    var rows = [
      ['Student', form.querySelector('[name="applicant_name"]').value],
      ["Father's Name", form.querySelector('[name="father_name"]').value],
      ['CNIC / B-Form No.', form.querySelector('[name="b_form_no"]').value],
      ['Date of Birth', form.querySelector('[name="date_of_birth"]').value],
      ['Gender', genderInput ? (genderInput.value === 'male' ? 'Male' : 'Female') : ''],
      ['Religion', form.querySelector('[name="religion"]').value],
      ['City', form.querySelector('[name="city"]').value],
      ['Phone Number', form.querySelector('[name="phone"]').value],
      ['WhatsApp Number', form.querySelector('[name="whatsapp"]').value],
      ['Email Address', form.querySelector('[name="email"]').value],
      ['Address', form.querySelector('[name="address"]').value],
      ['Class', className && className.indexOf('Select') !== 0 ? className : ''],
      ['Stream', streamName],
      ['Previous School', form.querySelector('[name="previous_school"]').value]
    ];

    if (needsMatric) {
      rows.push(['Matric Board', form.querySelector('[name="matric_board"]').value]);
      rows.push(['Matric Year', form.querySelector('[name="matric_year"]').value]);
      var mm = form.querySelector('[name="matric_marks"]').value;
      var mt = form.querySelector('[name="matric_total_marks"]').value;
      if (mm || mt) { rows.push(['Matric Marks', (mm || '?') + ' / ' + (mt || '?')]); }
    }

    box.innerHTML = rows
      .filter(function (r) { return r[1]; })
      .map(function (r) {
        return '<div class="review-row"><span class="review-label">' + r[0] + '</span><span class="review-value"></span></div>'
          .replace('<span class="review-value">', '<span class="review-value">' + r[1].replace(/&/g, '&amp;').replace(/</g, '&lt;'));
      })
      .join('');
  }

  var classSelect = document.getElementById('f-class');
  var streamField = document.getElementById('f-stream-field');
  var streamSelect = document.getElementById('f-stream');
  var matricFieldset = document.getElementById('matric-fieldset');
  var academicMessage = document.getElementById('academic-info-message');
  var matricInputs = matricFieldset ? Array.prototype.slice.call(matricFieldset.querySelectorAll('input')) : [];

  function syncClassDependentFields() {
    var selected = classSelect.options[classSelect.selectedIndex];
    var wing = selected ? selected.dataset.wing : '';
    var isCollege = wing === 'college';

    streamField.hidden = !isCollege;
    streamSelect.required = isCollege;

    matricFieldset.hidden = !isCollege;
    academicMessage.hidden = isCollege;
    matricInputs.forEach(function (inp) { inp.required = isCollege && inp.dataset.adminRequired === '1'; });
  }

  if (classSelect && streamField && streamSelect && matricFieldset && academicMessage) {
    classSelect.addEventListener('change', syncClassDependentFields);
    syncClassDependentFields();
  }

  showStep(current);
})();
</script>
