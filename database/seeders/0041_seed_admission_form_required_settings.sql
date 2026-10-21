-- =============================================================================
-- 0041_seed_admission_form_required_settings.sql · Per-field "required"
-- toggles for /admission-form, editable from Admin -> Settings -> Admission
-- Form (a new group added to the existing generic settings-group screen,
-- SiteSettingsController::GROUPS) — no new admin controller/view needed,
-- that screen already renders/saves any `type='boolean'` setting row
-- generically.
--
-- Structural fields (Gender, Class Applying For, the accuracy declaration)
-- are NOT included here on purpose — they aren't optional in any meaningful
-- sense (gender is a NOT NULL enum column; a submission needs a class to
-- even be filed under; the declaration is a legal confirmation, not data).
-- Everything else on the form is covered.
--
-- Defaults below match the field's required-ness as already hardcoded in
-- admission-form.php / AdmissionController::store() before this change, so
-- applying this seeder does not alter current behavior until an admin
-- actually changes a toggle. The four matric_* toggles only take effect
-- when Academic Information is shown at all (College-level classes) — that
-- visibility is structural (driven by class wing), not admin-configurable
-- here.
-- =============================================================================
USE `iiui_mardan`;

INSERT INTO `settings` (`group_name`, `key_name`, `value`, `type`, `label`, `description`, `is_public`, `sort_order`)
VALUES
  ('admission_form', 'require_applicant_name',     '1', 'boolean', 'Require Student''s Full Name',    'Applicants must fill this field before submitting.', 1, 1),
  ('admission_form', 'require_father_name',         '1', 'boolean', 'Require Father''s Name',          'Applicants must fill this field before submitting.', 1, 2),
  ('admission_form', 'require_b_form_no',           '1', 'boolean', 'Require CNIC / B-Form No.',       'Applicants must fill this field before submitting.', 1, 3),
  ('admission_form', 'require_date_of_birth',       '1', 'boolean', 'Require Date of Birth',           'Applicants must fill this field before submitting.', 1, 4),
  ('admission_form', 'require_phone',               '1', 'boolean', 'Require Phone Number',            'Applicants must fill this field before submitting.', 1, 5),
  ('admission_form', 'require_address',             '1', 'boolean', 'Require Home Address',            'Applicants must fill this field before submitting.', 1, 6),
  ('admission_form', 'require_religion',            '0', 'boolean', 'Require Religion',                'Applicants must fill this field before submitting.', 1, 7),
  ('admission_form', 'require_city',                '0', 'boolean', 'Require City',                    'Applicants must fill this field before submitting.', 1, 8),
  ('admission_form', 'require_whatsapp',            '0', 'boolean', 'Require WhatsApp Number',         'Applicants must fill this field before submitting.', 1, 9),
  ('admission_form', 'require_email',               '0', 'boolean', 'Require Email Address',           'Applicants must fill this field before submitting.', 1, 10),
  ('admission_form', 'require_previous_school',     '0', 'boolean', 'Require Previous School',         'Applicants must fill this field before submitting.', 1, 11),
  ('admission_form', 'require_matric_board',        '1', 'boolean', 'Require Matriculation Board',       'Only applies when Academic Information is shown (College-level classes).', 1, 12),
  ('admission_form', 'require_matric_year',         '1', 'boolean', 'Require Matriculation Year',        'Only applies when Academic Information is shown (College-level classes).', 1, 13),
  ('admission_form', 'require_matric_marks',        '1', 'boolean', 'Require Matriculation Marks Obtained', 'Only applies when Academic Information is shown (College-level classes).', 1, 14),
  ('admission_form', 'require_matric_total_marks',  '1', 'boolean', 'Require Matriculation Total Marks',  'Only applies when Academic Information is shown (College-level classes).', 1, 15)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0041_seed_admission_form_required_settings.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
