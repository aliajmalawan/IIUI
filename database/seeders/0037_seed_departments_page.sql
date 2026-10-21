-- =============================================================================
-- 0037_seed_departments_page.sql · New standalone /departments route
-- (Controllers/Web/PageController::departments, routes/web.php), pulling
-- from the `departments` table that already had a full admin CRUD
-- (Admin/DepartmentController, admin/faculty/departments) but no public
-- page — same "infrastructure existed, was never exposed" pattern as
-- page_views/analytics and the Homepage Builder cta tab earlier this
-- project. `departments.description` and `head_staff_id` existed as
-- columns from day one but were never populated (0033's seeder only set
-- name/code/sort_order), so the page would otherwise render empty text
-- and no Head of Department line.
--
-- Descriptions below are realistic STARTER/PLACEHOLDER copy, same
-- convention as the Faculty/Leadership/Accreditation/Examination-Policy
-- placeholders — the admin should refine them via Admin -> Faculty ->
-- Departments. head_staff_id is NOT a placeholder guess: it's set only for
-- Mathematics, to the one staff member (Ayesha Siddiqui, seeded in 0033)
-- who already actually holds the "Head of Department" designation; Science,
-- Islamic Studies, and English are left NULL since no such designation is
-- currently assigned there.
--
-- "Departments" is added as the first child of the "Academics" header nav
-- item, ahead of "Examination Policy" and "Academic Calendar" (their
-- sort_order bumped by one to make room) — no template change needed,
-- reusing the dropdown behaviour introduced for Examination Policy (0036).
--
-- One-time seed for menu_items (no natural unique key); `pages` uses
-- ON DUPLICATE KEY UPDATE on its unique `slug`, matching
-- 0033_seed_system_pages.sql's convention; the `departments` UPDATEs are
-- naturally idempotent (re-running just re-sets the same values).
-- =============================================================================
USE `iiui_mardan`;

UPDATE `departments` SET `description` = 'Physics, Chemistry, and Biology taught with a strong lab-based, hands-on approach from Middle School through College.' WHERE `code` = 'SCI';
UPDATE `departments` SET `description` = 'Building numerical fluency and problem-solving skills from foundational arithmetic through Intermediate-level Mathematics.', `head_staff_id` = (SELECT id FROM `staff` WHERE `slug` = 'ayesha-siddiqui') WHERE `code` = 'MATH';
UPDATE `departments` SET `description` = 'Quran, Hadith, and Islamiyat instruction woven into daily campus life, not taught as a subject apart from it.' WHERE `code` = 'ISL';
UPDATE `departments` SET `description` = 'Reading, writing, and communication skills developed across every grade, in preparation for board and international examinations.' WHERE `code` = 'ENG';

INSERT INTO `pages` (`title`, `slug`, `excerpt`, `sort_order`, `is_system`, `show_in_menu`, `status`, `published_at`)
VALUES
  ('Our Academic Departments', 'departments',
   'The subject teams behind every class, from Primary through College.',
   6, 1, 0, 'published', NOW())
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

UPDATE `menu_items` SET `sort_order` = `sort_order` + 1 WHERE `parent_id` = 7;

INSERT INTO `menu_items` (`menu_id`, `parent_id`, `title`, `type`, `url`, `target`, `sort_order`, `is_active`)
VALUES
  (1, 7, 'Departments', 'custom', '/departments', '_self', 1, 1);

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0037_seed_departments_page.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
