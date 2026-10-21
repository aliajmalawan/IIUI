-- =============================================================================
-- 0018_seed_homepage_welcome_settings.sql  ·  Welcome Section — Principal
-- Message + Owner/Chairman Message, stored as 'homepage'-group rows in the
-- same generic `settings` table as the About Section (0017). Institution
-- Introduction reuses the existing about_* fields rather than duplicating
-- them under a new prefix.
--
-- Also corrects two rows in 0017 that were corrupted after that seeder was
-- first applied: `about_body` picked up a mojibake'd dash (a charset
-- mismatch when the seed SQL was executed, not a bug in 0017's own text),
-- and `cta_button_url` was overwritten with an absolute Windows path during
-- manual testing (the MSYS2 curl path-mangling issue tracked elsewhere in
-- this project's history) instead of the intended relative URL.
-- =============================================================================
USE `iiui_mardan`;

INSERT INTO `settings` (`group_name`, `key_name`, `value`, `type`, `label`, `description`, `is_public`, `sort_order`) VALUES
    ('homepage', 'welcome_principal_name', 'Dr. Muhammad Naeem', 'text', 'Principal Name', NULL, 1, 20),
    ('homepage', 'welcome_principal_designation', 'Principal', 'text', 'Principal Designation', NULL, 1, 21),
    ('homepage', 'welcome_principal_photo', NULL, 'image', 'Principal Photo', NULL, 1, 22),
    ('homepage', 'welcome_principal_message',
        'Welcome to IIUI Schools & College, Mardan Campus. Our aim is to nurture confident, capable learners who combine academic excellence with strong Islamic values. Every child who walks through our gates is given the guidance and opportunity to discover their full potential.',
        'textarea', 'Principal Message', 'Shown in the Welcome section on the homepage.', 1, 23),
    ('homepage', 'welcome_owner_name', 'Alhaj Muhammad Yousaf', 'text', 'Owner Name', NULL, 1, 24),
    ('homepage', 'welcome_owner_designation', 'Chairman / Owner', 'text', 'Owner Designation', NULL, 1, 25),
    ('homepage', 'welcome_owner_photo', NULL, 'image', 'Owner Photo', NULL, 1, 26),
    ('homepage', 'welcome_owner_message',
        'It has always been our vision to establish an institution where quality education and Islamic character-building go hand in hand. I am grateful to our dedicated faculty and staff for making that vision a daily reality for every student in our care.',
        'textarea', 'Owner Message', 'Shown in the Welcome section on the homepage.', 1, 27)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);

UPDATE `settings` SET `value` = 'IIUI Schools & College – Mardan Campus offers quality education from primary through intermediate level, combining a modern curriculum with strong Islamic values.'
 WHERE `key_name` = 'about_body';

UPDATE `settings` SET `value` = '/admissions/apply'
 WHERE `key_name` = 'cta_button_url';

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0018_seed_homepage_welcome_settings.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
