-- =============================================================================
-- 0037_seed_academic_calendar.sql · Starter exam terms + holidays for the
-- current session (2026-2027), so /academic-calendar and the new admin
-- "Academic Calendar" screens (Exam Terms / Holidays) have real content to
-- show/edit from day one instead of an empty state. Dates are placeholders
-- typical of a Pakistani school-year calendar — meant to be edited from
-- the admin panel, not treated as authoritative.
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `exam_terms` (`academic_session_id`, `name`, `type`, `start_date`, `end_date`, `status`, `sort_order`)
SELECT id, 'Monthly Test — May', 'monthly', '2026-05-25', '2026-05-29', 'completed', 1 FROM `academic_sessions` WHERE `is_current` = 1
UNION ALL
SELECT id, 'Mid Term Examination', 'mid', '2026-09-14', '2026-09-25', 'planned', 2 FROM `academic_sessions` WHERE `is_current` = 1
UNION ALL
SELECT id, 'Monthly Test — November', 'monthly', '2026-11-16', '2026-11-20', 'planned', 3 FROM `academic_sessions` WHERE `is_current` = 1
UNION ALL
SELECT id, 'Pre-Board Examination', 'pre_board', '2027-01-11', '2027-01-20', 'planned', 4 FROM `academic_sessions` WHERE `is_current` = 1
UNION ALL
SELECT id, 'Final Term Examination', 'final', '2027-02-15', '2027-02-27', 'planned', 5 FROM `academic_sessions` WHERE `is_current` = 1;

INSERT INTO `holidays` (`academic_session_id`, `title`, `date_from`, `date_to`, `type`, `applies_to`, `description`)
SELECT id, 'Eid-ul-Adha Holidays', '2026-06-16', '2026-06-19', 'religious', 'all', NULL FROM `academic_sessions` WHERE `is_current` = 1
UNION ALL
SELECT id, 'Summer Vacations', '2026-06-22', '2026-08-09', 'vacation', 'all', 'Campus reopens after the summer break.' FROM `academic_sessions` WHERE `is_current` = 1
UNION ALL
SELECT id, 'Independence Day', '2026-08-14', '2026-08-14', 'public', 'all', NULL FROM `academic_sessions` WHERE `is_current` = 1
UNION ALL
SELECT id, 'Iqbal Day', '2026-11-09', '2026-11-09', 'public', 'all', NULL FROM `academic_sessions` WHERE `is_current` = 1
UNION ALL
SELECT id, 'Winter Vacations', '2026-12-25', '2027-01-05', 'vacation', 'all', 'Campus reopens for the spring term.' FROM `academic_sessions` WHERE `is_current` = 1
UNION ALL
SELECT id, 'Kashmir Solidarity Day', '2027-02-05', '2027-02-05', 'public', 'all', NULL FROM `academic_sessions` WHERE `is_current` = 1
UNION ALL
SELECT id, 'Pakistan Day', '2027-03-23', '2027-03-23', 'public', 'all', NULL FROM `academic_sessions` WHERE `is_current` = 1;

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0037_seed_academic_calendar.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
