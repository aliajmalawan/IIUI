-- =============================================================================
-- 0014_seed_core_data.sql  ·  Baseline data every environment needs
-- -----------------------------------------------------------------------------
-- Idempotent: every INSERT uses ON DUPLICATE KEY UPDATE or INSERT IGNORE, so
-- running this file twice never duplicates rows or errors out.
--
-- Seeds, in order: roles → permissions → role_permissions (from a module ×
-- action matrix, never hand-typed) → super admin → campus → academic session
-- → classes/sections/subjects/curriculum → grade scale → fee categories →
-- site settings → email templates → CMS menus.
--
-- DemoSeeder-style sample students/staff live in a SEPARATE file
-- (0015_demo_data.sql) and must never run against production.
-- =============================================================================
USE `iiui_mardan`;

-- -----------------------------------------------------------------------------
-- 1. ROLES  (slug, name, level — level gates who may assign whom)
-- -----------------------------------------------------------------------------
INSERT INTO `roles` (`slug`, `name`, `description`, `level`, `is_system`) VALUES
    ('super_admin',        'Super Admin',            'Full system access including configuration and role editing.', 100, 1),
    ('principal',          'Principal',               'All academic, financial and HR data; no system configuration.', 90, 1),
    ('vice_principal',     'Vice Principal',          'Academics, attendance, exams, staff; read-only finance.', 80, 0),
    ('coordinator',        'Academic Coordinator',    'Classes, sections, timetable, exams for an assigned wing.', 70, 0),
    ('exam_controller',    'Exam Controller',         'Exams, datesheets, marks verification, result publication.', 70, 0),
    ('admission_officer',  'Admission Officer',       'Admissions pipeline and student registration.', 60, 0),
    ('accountant',         'Accountant',               'Fees, challans, payments, expenses, financial reports.', 60, 0),
    ('class_teacher',      'Class Teacher',           'Own section: attendance, marks, student profiles.', 50, 0),
    ('teacher',            'Teacher',                  'Own assigned subjects/classes: attendance, marks entry.', 40, 0),
    ('librarian',          'Librarian',                'Library module only.', 40, 0),
    ('transport_incharge', 'Transport Incharge',      'Transport module only.', 40, 0),
    ('content_editor',     'Content Editor',           'Website CMS only — no student/staff/finance access.', 40, 0),
    ('receptionist',       'Receptionist',             'Read-only directory, contact messages, visitor log.', 30, 0),
    ('student',            'Student',                  'Own records only.', 20, 1),
    ('parent',             'Parent / Guardian',        'Linked children''s records only.', 20, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `level` = VALUES(`level`);

-- -----------------------------------------------------------------------------
-- 2. PERMISSIONS — generated from a module × action matrix, never hand-typed
-- one by one, so the matrix in docs/03-modules-roles.md and the actual
-- database can never silently drift apart.
-- -----------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS `tmp_modules`;
CREATE TEMPORARY TABLE `tmp_modules` (`module` VARCHAR(50), `group_name` VARCHAR(50));
INSERT INTO `tmp_modules` (`module`, `group_name`) VALUES
    ('dashboard',    'Core'), ('users',        'Access Control'), ('roles', 'Access Control'),
    ('sessions',     'Academic Setup'), ('classes', 'Academic Setup'), ('subjects', 'Academic Setup'),
    ('students',     'Students'), ('guardians', 'Students'), ('admissions', 'Admissions'),
    ('staff',        'Staff'), ('departments', 'Staff'),
    ('attendance',   'Attendance'), ('leaves', 'Attendance'),
    ('exams',        'Examination'), ('marks', 'Examination'), ('results', 'Examination'), ('datesheets', 'Examination'),
    ('fee_structures','Finance'), ('challans', 'Finance'), ('payments', 'Finance'),
    ('expenses',     'Finance'), ('payroll', 'Finance'),
    ('cms_pages',    'CMS'), ('cms_posts', 'CMS'), ('cms_media', 'CMS'), ('cms_menus', 'CMS'),
    ('notices',      'Communication'), ('messages', 'Communication'),
    ('reports',      'Reports'),
    ('settings',     'System'), ('audit_logs', 'System'), ('backups', 'System'),
    ('library',      'Optional'), ('transport', 'Optional');

DROP TEMPORARY TABLE IF EXISTS `tmp_actions`;
CREATE TEMPORARY TABLE `tmp_actions` (`action` VARCHAR(30));
INSERT INTO `tmp_actions` (`action`) VALUES ('view'), ('create'), ('edit'), ('delete'), ('export');

-- NOTE: a comma join (not CROSS JOIN) is used deliberately — MariaDB's parser
-- treats a table immediately followed by "ON" as the start of a join
-- condition, so "CROSS JOIN tmp_actions a ON DUPLICATE KEY UPDATE ..." is
-- misread as a (broken) join condition. A comma join has no ON/USING lookahead.
INSERT INTO `permissions` (`slug`, `name`, `module`, `group_name`)
SELECT
    CONCAT(m.`module`, '.', a.`action`),
    CONCAT(REPLACE(a.`action`, '_', ' '), ' ', REPLACE(m.`module`, '_', ' ')),
    m.`module`, m.`group_name`
  FROM `tmp_modules` m, `tmp_actions` a
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Domain-specific actions beyond the standard CRUD set.
INSERT INTO `permissions` (`slug`, `name`, `module`, `group_name`) VALUES
    ('fees.collect',        'Collect fee payment',            'payments',   'Finance'),
    ('fees.waive_fine',     'Waive a late fine',               'payments',   'Finance'),
    ('challans.cancel',     'Cancel a challan',                'challans',   'Finance'),
    ('challans.generate',   'Bulk-generate challans',          'challans',   'Finance'),
    ('marks.verify',        'Verify entered marks',            'marks',      'Examination'),
    ('marks.edit_locked',   'Edit marks after verification',   'marks',      'Examination'),
    ('results.publish',     'Publish term results',            'results',    'Examination'),
    ('students.promote',    'Bulk-promote students',           'students',   'Students'),
    ('admissions.convert',  'Convert application to student',  'admissions', 'Admissions'),
    ('settings.manage',     'Manage system settings',          'settings',   'System'),
    ('backup.restore',      'Restore a backup',                'backups',    'System'),
    ('session.switch',      'Switch active academic session',  'sessions',   'Academic Setup'),
    ('audit_logs.view',     'View audit trail',                'audit_logs', 'System')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

DROP TEMPORARY TABLE IF EXISTS `tmp_modules`;
DROP TEMPORARY TABLE IF EXISTS `tmp_actions`;

-- -----------------------------------------------------------------------------
-- 3. ROLE → PERMISSION assignment (abridged matrix from docs/03; full CRUD
-- for super_admin, scoped grants for everyone else)
-- -----------------------------------------------------------------------------

-- super_admin: everything, no exceptions.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r CROSS JOIN `permissions` p
 WHERE r.`slug` = 'super_admin'
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);

-- principal: full academic/financial/HR read+write, no system config.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r CROSS JOIN `permissions` p
 WHERE r.`slug` = 'principal'
   AND p.`module` NOT IN ('settings', 'backups', 'roles')
   AND p.`slug` NOT IN ('users.delete', 'audit_logs.view')
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);

-- accountant: full finance, view-only students.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r CROSS JOIN `permissions` p
 WHERE r.`slug` = 'accountant'
   AND (p.`module` IN ('fee_structures','challans','payments','expenses','payroll','reports')
        OR p.`slug` = 'students.view')
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);

-- exam_controller: full examination module.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r CROSS JOIN `permissions` p
 WHERE r.`slug` = 'exam_controller'
   AND p.`module` IN ('exams','marks','results','datesheets')
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);

-- admission_officer: admissions pipeline + student view/create.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r CROSS JOIN `permissions` p
 WHERE r.`slug` = 'admission_officer'
   AND (p.`module` = 'admissions' OR p.`slug` IN ('students.view','students.create','guardians.view','guardians.create'))
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);

-- class_teacher / teacher: attendance + marks entry (scoped further by
-- staff_subjects at the query level — see docs/03-modules-roles.md §5).
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r CROSS JOIN `permissions` p
 WHERE r.`slug` IN ('class_teacher','teacher')
   AND p.`slug` IN ('attendance.view','attendance.create','attendance.edit',
                     'marks.view','marks.create','marks.edit',
                     'students.view','notices.view','reports.view')
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);

-- content_editor: CMS only. Deliberately excludes every student/staff/finance module.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r CROSS JOIN `permissions` p
 WHERE r.`slug` = 'content_editor'
   AND p.`module` IN ('cms_pages','cms_posts','cms_media','cms_menus','notices')
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);

-- librarian / transport_incharge: their single module.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r CROSS JOIN `permissions` p
 WHERE r.`slug` = 'librarian' AND p.`module` = 'library'
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r CROSS JOIN `permissions` p
 WHERE r.`slug` = 'transport_incharge' AND p.`module` = 'transport'
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);

-- student / parent: view own records only (record-level scoping is enforced
-- by the repository layer, not by this table — see docs/05-security.md §6).
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r CROSS JOIN `permissions` p
 WHERE r.`slug` IN ('student','parent')
   AND p.`slug` IN ('students.view','attendance.view','marks.view','results.view',
                     'challans.view','payments.view','notices.view')
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);

-- -----------------------------------------------------------------------------
-- 4. SUPER ADMIN ACCOUNT
-- Password placeholder below is the Argon2id hash of a strong random string
-- that is discarded immediately after seeding — NOT a usable default
-- password. must_change_password forces a reset on first login regardless.
-- Generate the real hash with: password_hash(random_bytes(24) as hex, PASSWORD_ARGON2ID)
-- -----------------------------------------------------------------------------
INSERT INTO `users`
    (`uuid`, `username`, `email`, `password_hash`, `first_name`, `last_name`,
     `status`, `must_change_password`, `email_verified_at`)
VALUES
    (UUID(), 'admin', 'admin@iiuimardan.edu.pk',
     '$argon2id$v=19$m=65536,t=4,p=2$CHANGE_ME_ON_DEPLOY$CHANGE_ME_ON_DEPLOY',
     'System', 'Administrator', 'active', 1, NOW())
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT u.`id`, r.`id` FROM `users` u CROSS JOIN `roles` r
 WHERE u.`username` = 'admin' AND r.`slug` = 'super_admin'
ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`);

-- -----------------------------------------------------------------------------
-- 5. CAMPUS + ACADEMIC SESSION
-- -----------------------------------------------------------------------------
INSERT INTO `campuses` (`id`, `name`, `code`, `city`, `is_active`) VALUES
    (1, 'IIUI Schools & College – Mardan Campus', 'MDN', 'Mardan', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `academic_sessions` (`name`, `code`, `start_date`, `end_date`, `is_current`, `status`) VALUES
    ('2026-2027', 'AS2627', '2026-04-01', '2027-03-31', 1, 'active')
ON DUPLICATE KEY UPDATE `is_current` = VALUES(`is_current`);

-- -----------------------------------------------------------------------------
-- 6. CLASSES, SECTIONS, STREAMS, SUBJECTS
-- -----------------------------------------------------------------------------
INSERT INTO `school_classes` (`campus_id`, `name`, `short_name`, `numeric_level`, `wing`, `sort_order`) VALUES
    (1, 'Nursery',   'NUR', 0,  'primary',   0),
    (1, 'Class 1',   '1',   1,  'primary',   1),
    (1, 'Class 2',   '2',   2,  'primary',   2),
    (1, 'Class 3',   '3',   3,  'primary',   3),
    (1, 'Class 4',   '4',   4,  'primary',   4),
    (1, 'Class 5',   '5',   5,  'primary',   5),
    (1, 'Class 6',   '6',   6,  'middle',    6),
    (1, 'Class 7',   '7',   7,  'middle',    7),
    (1, 'Class 8',   '8',   8,  'middle',    8),
    (1, 'Class 9',   '9',   9,  'secondary', 9),
    (1, 'Class 10',  '10',  10, 'secondary', 10),
    (1, 'Class 11',  '11',  11, 'college',   11),
    (1, 'Class 12',  '12',  12, 'college',   12)
ON DUPLICATE KEY UPDATE `numeric_level` = VALUES(`numeric_level`);

INSERT INTO `streams` (`name`, `code`, `applies_from_level`, `sort_order`) VALUES
    ('Pre-Medical',    'PRE-MED', 11, 1),
    ('Pre-Engineering','PRE-ENG', 11, 2),
    ('ICS',            'ICS',     11, 3),
    ('Commerce',       'COM',     11, 4),
    ('Arts',           'ARTS',    11, 5)
ON DUPLICATE KEY UPDATE `applies_from_level` = VALUES(`applies_from_level`);

INSERT INTO `class_streams` (`class_id`, `stream_id`)
SELECT c.`id`, s.`id` FROM `school_classes` c CROSS JOIN `streams` s
 WHERE c.`numeric_level` >= 11
ON DUPLICATE KEY UPDATE `class_id` = VALUES(`class_id`);

-- One section per class for the current session, to give the system a working
-- default. Additional sections are an admin-panel action, not a seed concern.
INSERT INTO `sections` (`class_id`, `academic_session_id`, `name`, `capacity`, `sort_order`)
SELECT c.`id`, ses.`id`, 'A', 40, 1
  FROM `school_classes` c CROSS JOIN `academic_sessions` ses
 WHERE ses.`is_current` = 1
ON DUPLICATE KEY UPDATE `capacity` = VALUES(`capacity`);

INSERT INTO `subjects` (`code`, `name`, `name_ur`, `type`, `sort_order`) VALUES
    ('ENG', 'English',              'انگریزی',   'theory', 1),
    ('URD', 'Urdu',                 'اردو',      'theory', 2),
    ('MATH','Mathematics',          'ریاضی',     'theory', 3),
    ('SCI', 'General Science',      'سائنس',     'both',   4),
    ('SST', 'Social Studies',       'معاشرتی علوم','theory',5),
    ('ISL', 'Islamiyat',            'اسلامیات',   'theory', 6),
    ('PHY', 'Physics',              'طبیعیات',   'both',   7),
    ('CHEM','Chemistry',            'کیمیا',      'both',   8),
    ('BIO', 'Biology',              'حیاتیات',   'both',   9),
    ('CS',  'Computer Science',     'کمپیوٹر سائنس','both', 10),
    ('PAK', 'Pakistan Studies',     'مطالعہ پاکستان','theory',11)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- -----------------------------------------------------------------------------
-- 7. GRADE SCALE (2026-2027 session)
-- -----------------------------------------------------------------------------
INSERT INTO `grade_scales` (`academic_session_id`, `grade`, `min_percentage`, `max_percentage`, `grade_point`, `remarks`, `is_fail`, `sort_order`)
SELECT ses.`id`, g.grade, g.min_pct, g.max_pct, g.gp, g.remarks, g.is_fail, g.sort_order
  FROM `academic_sessions` ses
  CROSS JOIN (
      SELECT 'A+' AS grade, 90.00 AS min_pct, 100.00 AS max_pct, 4.00 AS gp, 'Outstanding'    AS remarks, 0 AS is_fail, 1 AS sort_order UNION ALL
      SELECT 'A',  80.00, 89.99, 3.70, 'Excellent',     0, 2 UNION ALL
      SELECT 'B+', 70.00, 79.99, 3.30, 'Very Good',     0, 3 UNION ALL
      SELECT 'B',  60.00, 69.99, 3.00, 'Good',          0, 4 UNION ALL
      SELECT 'C+', 50.00, 59.99, 2.30, 'Satisfactory',  0, 5 UNION ALL
      SELECT 'C',  40.00, 49.99, 2.00, 'Pass',          0, 6 UNION ALL
      SELECT 'D',  33.00, 39.99, 1.00, 'Marginal Pass', 0, 7 UNION ALL
      SELECT 'F',  0.00,  32.99, 0.00, 'Fail',          1, 8
  ) g
 WHERE ses.`is_current` = 1
ON DUPLICATE KEY UPDATE `min_percentage` = VALUES(`min_percentage`);

-- -----------------------------------------------------------------------------
-- 8. FEE CATEGORIES
-- -----------------------------------------------------------------------------
INSERT INTO `fee_categories` (`name`, `code`, `type`, `sort_order`) VALUES
    ('Admission Fee',   'ADM',  'one_time',  1),
    ('Tuition Fee',     'TUI',  'recurring', 2),
    ('Examination Fee', 'EXM',  'recurring', 3),
    ('Library Fee',     'LIB',  'recurring', 4),
    ('Laboratory Fee',  'LAB',  'recurring', 5),
    ('Transport Fee',   'TRN',  'recurring', 6),
    ('Annual Fund',     'ANN',  'one_time',  7),
    ('Security Deposit','SEC',  'one_time',  8)
ON DUPLICATE KEY UPDATE `type` = VALUES(`type`);

INSERT INTO `expense_categories` (`name`, `code`) VALUES
    ('Salaries',           'SAL'),
    ('Utilities',          'UTL'),
    ('Maintenance',        'MNT'),
    ('Stationery & Supplies', 'STA'),
    ('Transport & Fuel',   'TRN'),
    ('Events & Activities','EVT'),
    ('Miscellaneous',      'MSC')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- -----------------------------------------------------------------------------
-- 9. SITE SETTINGS (defaults the CMS reads on first boot)
-- -----------------------------------------------------------------------------
INSERT INTO `settings` (`group_name`, `key_name`, `value`, `type`, `label`, `is_public`, `sort_order`) VALUES
    ('general', 'site_name',      'IIUI Schools & College – Mardan Campus', 'text', 'Site Name', 1, 1),
    ('general', 'site_tagline',   'International Islamic University Islamabad', 'text', 'Tagline', 1, 2),
    ('general', 'site_logo',      '/assets/img/logo/logo.svg', 'image', 'Logo', 1, 3),
    ('general', 'timezone',       'Asia/Karachi', 'select', 'Timezone', 0, 4),
    ('contact', 'address',        'Mardan Campus, Khyber Pakhtunkhwa, Pakistan', 'textarea', 'Address', 1, 1),
    ('contact', 'phone',          '+92-937-000000', 'text', 'Phone', 1, 2),
    ('contact', 'email',          'info@iiuimardan.edu.pk', 'email', 'Email', 1, 3),
    ('social',  'facebook_url',   '', 'url', 'Facebook URL', 1, 1),
    ('social',  'youtube_url',    '', 'url', 'YouTube URL', 1, 2),
    ('seo',     'meta_description', 'IIUI Schools & College Mardan Campus — quality education from primary through intermediate.', 'textarea', 'Default Meta Description', 1, 1),
    ('academic','attendance_shortage_threshold', '75', 'number', 'Attendance Shortage Threshold (%)', 0, 1),
    ('fee',     'default_due_day', '10', 'number', 'Default Fee Due Day', 0, 1),
    ('fee',     'fine_rule',      'per_day', 'select', 'Late Fine Rule', 0, 2),
    ('fee',     'fine_rate',      '20.00', 'number', 'Late Fine Rate', 0, 3)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- -----------------------------------------------------------------------------
-- 10. EMAIL TEMPLATES (referenced by NotificationService)
-- -----------------------------------------------------------------------------
INSERT INTO `email_templates` (`slug`, `name`, `subject`, `body`, `available_variables`) VALUES
    ('admission-status-changed', 'Admission Status Update',
     'Your application {{application_no}} status has been updated',
     '<p>Dear {{applicant_name}},</p><p>Your admission application <strong>{{application_no}}</strong> status is now: <strong>{{status}}</strong>.</p>',
     JSON_ARRAY('applicant_name','application_no','status')),
    ('fee-challan-generated', 'Fee Challan Generated',
     'Fee Challan for {{month_year}} — {{student_name}}',
     '<p>Dear Parent,</p><p>A new fee challan <strong>{{challan_no}}</strong> of amount <strong>PKR {{amount}}</strong> has been generated for {{student_name}}, due on {{due_date}}.</p>',
     JSON_ARRAY('student_name','challan_no','amount','due_date','month_year')),
    ('result-published', 'Result Published',
     '{{term_name}} Result Published — {{student_name}}',
     '<p>Dear Parent,</p><p>The result for {{student_name}} in {{term_name}} has been published. Percentage: {{percentage}}%, Grade: {{grade}}.</p>',
     JSON_ARRAY('student_name','term_name','percentage','grade')),
    ('password-reset', 'Password Reset Request',
     'Reset your password',
     '<p>Click the link below to reset your password. This link expires in 60 minutes.</p><p><a href="{{reset_url}}">{{reset_url}}</a></p>',
     JSON_ARRAY('reset_url'))
ON DUPLICATE KEY UPDATE `subject` = VALUES(`subject`);

-- -----------------------------------------------------------------------------
-- 11. CMS MENUS (empty shells — items are an admin-panel action)
-- -----------------------------------------------------------------------------
INSERT INTO `menus` (`name`, `location`) VALUES
    ('Header Navigation', 'header'),
    ('Footer Links',       'footer'),
    ('Quick Links',        'quick_links')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0014_seed_core_data.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
