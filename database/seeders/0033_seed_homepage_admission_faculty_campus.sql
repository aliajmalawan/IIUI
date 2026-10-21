-- =============================================================================
-- 0033_seed_homepage_admission_faculty_campus.sql  ·  Content for three new
-- homepage sections (Admission CTA banner, Our Faculty, Vibrant Campus):
--   1. Enriches the existing 'cta_' settings (admin tab already built, only
--      button_text/button_url had values) with cta_heading/cta_body.
--   2. Adds faculty_heading/faculty_subheading and campus_heading/campus_body
--      settings, plus registers them as new Homepage Builder tabs.
--   3. Seeds a few departments/designations and 4 placeholder faculty
--      profiles (is_public_profile=1) since Staff management has no admin
--      UI yet and the only 2 existing staff rows are soft-deleted test data
--      — the "Our Faculty" section would otherwise render permanently empty.
--      No photos: falls back to the same icon-avatar treatment already used
--      for Welcome-message cards when a photo is absent.
-- One-time seed, not an upsert — plain INSERTs, no natural unique key for
-- most of these rows to key an upsert off of.
-- =============================================================================
USE `iiui_mardan`;

INSERT INTO `settings` (`group_name`, `key_name`, `value`, `type`, `label`, `description`, `is_public`, `sort_order`, `updated_by`)
VALUES
  ('homepage', 'cta_heading', 'Secure Your Seat at IIUI Schools & College', 'text', 'CTA Heading', NULL, 1, 5, 1),
  ('homepage', 'cta_body', 'Admissions are now open for the 2026-27 session. Limited seats available across Play Group through College — apply today and give your child a strong start rooted in academic excellence and Islamic values.', 'textarea', 'CTA Body', NULL, 1, 6, 1),
  ('homepage', 'faculty_heading', 'Meet Our Expert Faculty', 'text', 'Faculty Section Heading', NULL, 1, 1, 1),
  ('homepage', 'faculty_subheading', 'Experienced, dedicated educators committed to every student''s growth.', 'text', 'Faculty Section Subheading', NULL, 1, 2, 1),
  ('homepage', 'campus_heading', 'Vibrant Campus Life & Activities', 'text', 'Campus Section Heading', NULL, 1, 1, 1),
  ('homepage', 'campus_body', 'From science fairs and cultural celebrations to sports days and educational trips, our campus is always alive with learning beyond the classroom.', 'textarea', 'Campus Section Body', NULL, 1, 2, 1);

INSERT INTO `departments` (`name`, `code`, `sort_order`) VALUES
  ('Science', 'SCI', 1),
  ('Mathematics', 'MATH', 2),
  ('Islamic Studies', 'ISL', 3),
  ('English', 'ENG', 4);

INSERT INTO `designations` (`name`, `category`, `rank`) VALUES
  ('Head of Department', 'management', 20),
  ('Senior Teacher', 'teaching', 40),
  ('Subject Teacher', 'teaching', 60);

INSERT INTO `staff`
  (`uuid`, `employee_no`, `first_name`, `last_name`, `gender`, `department_id`, `designation_id`,
   `qualification`, `specialization`, `experience_years`, `joining_date`, `employment_type`,
   `is_teaching`, `is_public_profile`, `slug`, `sort_order`, `status`)
VALUES
  (UUID(), 'FAC-0003', 'Muhammad', 'Farhan', 'male',
   (SELECT id FROM departments WHERE code = 'SCI'), (SELECT id FROM designations WHERE name = 'Senior Teacher'),
   'M.Phil Chemistry', 'Physical Sciences', 12, '2015-08-01', 'permanent', 1, 1, 'muhammad-farhan', 1, 'active'),
  (UUID(), 'FAC-0004', 'Ayesha', 'Siddiqui', 'female',
   (SELECT id FROM departments WHERE code = 'MATH'), (SELECT id FROM designations WHERE name = 'Head of Department'),
   'M.Sc Mathematics', 'Applied Mathematics', 9, '2018-03-15', 'permanent', 1, 1, 'ayesha-siddiqui', 2, 'active'),
  (UUID(), 'FAC-0005', 'Abdul', 'Rehman', 'male',
   (SELECT id FROM departments WHERE code = 'ISL'), (SELECT id FROM designations WHERE name = 'Senior Teacher'),
   'Shahadat-ul-Alamiyyah, M.A Islamiyat', 'Quran & Islamic Studies', 15, '2011-06-01', 'permanent', 1, 1, 'abdul-rehman', 3, 'active'),
  (UUID(), 'FAC-0006', 'Sana', 'Malik', 'female',
   (SELECT id FROM departments WHERE code = 'ENG'), (SELECT id FROM designations WHERE name = 'Subject Teacher'),
   'M.A English Literature', 'Language & Literature', 6, '2021-01-10', 'permanent', 1, 1, 'sana-malik', 4, 'active');

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0033_seed_homepage_admission_faculty_campus.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
