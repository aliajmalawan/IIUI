-- =============================================================================
-- 0034_seed_leadership_page.sql · New standalone /leadership route
-- (Controllers/Web/PageController::leadership, routes/web.php):
--   1. Adds 'Principal' and 'Vice Principal' designations (category =
--      'management', ranking above the existing 'Head of Department') so the
--      Leadership page's team grid — StaffRepository::leadershipList(),
--      filtered to category='management' — has someone senior to show.
--   2. Seeds 2 placeholder staff profiles for those designations
--      (is_public_profile=1) using the same no-photo/icon-avatar fallback as
--      the existing Faculty placeholders from 0033. Ayesha Siddiqui (Head of
--      Department, seeded in 0033) is already category='management' and
--      needs no changes to also appear on this page. The Principal profile
--      reuses the "Dr. Muhammad Naeem" name already seeded into the
--      welcome_principal_* settings (0018) so the Leadership grid and the
--      Principal's-message quote block don't show two different people
--      claiming the same title.
--   3. Registers 'leadership' as a system page row (pages, is_system=1) so
--      it gets the same admin-editable hero/content-sections treatment as
--      About/Academics/Admissions/Contact (database/migrations/0033).
--   4. Adds a "Leadership" header nav item as a child of "About" (menu_items,
--      menu_id=1, parent_id=6) alongside the existing "Vision & Mission"
--      child.
-- One-time seed, not an upsert for designations/staff/menu_items (no natural
-- unique key); `pages` uses ON DUPLICATE KEY UPDATE on its unique `slug`,
-- matching 0033_seed_system_pages.sql's own convention.
-- =============================================================================
USE `iiui_mardan`;

INSERT INTO `designations` (`name`, `category`, `rank`) VALUES
  ('Principal', 'management', 5),
  ('Vice Principal', 'management', 10);

INSERT INTO `staff`
  (`uuid`, `employee_no`, `first_name`, `last_name`, `gender`, `department_id`, `designation_id`,
   `qualification`, `specialization`, `experience_years`, `joining_date`, `employment_type`,
   `is_teaching`, `is_public_profile`, `slug`, `bio`, `sort_order`, `status`)
VALUES
  (UUID(), 'FAC-0007', 'Dr. Muhammad', 'Naeem', 'male',
   NULL, (SELECT id FROM designations WHERE name = 'Principal'),
   'PhD Education', 'Academic Leadership & School Administration', 18, '2010-04-01', 'permanent',
   0, 1, 'dr-muhammad-naeem',
   'Dr. Muhammad Naeem has led IIUI Schools & College, Mardan Campus for over a decade, building a culture that pairs strong academic outcomes with the university''s Islamic values. He holds a doctorate in Education and has served in senior academic leadership roles throughout his career.',
   0, 'active'),
  (UUID(), 'FAC-0008', 'Farah', 'Naz', 'female',
   NULL, (SELECT id FROM designations WHERE name = 'Vice Principal'),
   'M.Phil Education', 'Curriculum Development & Student Affairs', 14, '2013-09-01', 'permanent',
   0, 1, 'farah-naz',
   'Farah Naz oversees curriculum quality and student affairs across the campus. With an M.Phil in Education and over a decade of experience, she works closely with department heads to keep teaching standards consistently high.',
   1, 'active');

INSERT INTO `pages` (`title`, `slug`, `excerpt`, `sort_order`, `is_system`, `show_in_menu`, `status`, `published_at`)
VALUES
  ('Our Leadership', 'leadership', 'The people guiding our academic vision and everyday campus life.', 5, 1, 0, 'published', NOW())
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

INSERT INTO `menu_items` (`menu_id`, `parent_id`, `title`, `type`, `url`, `target`, `sort_order`, `is_active`)
VALUES
  (1, 6, 'Leadership', 'custom', '/leadership', '_self', 3, 1);

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0034_seed_leadership_page.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
