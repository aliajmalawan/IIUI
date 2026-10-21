-- =============================================================================
-- 0041_societies_clubs_page.sql · New custom page (reference: darsons.edu.pk/
-- societies.php), built the same way as Student Life (0040) — entirely
-- through the existing Pages section-builder (stats/icon_grid/cta), no new
-- tables or PHP. Club names/activities adapted for a K-12 Islamic school
-- context rather than copied from Darsons' pharmacy-college societies.
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `pages` (`title`, `slug`, `excerpt`, `template`, `sort_order`, `is_system`, `show_in_menu`, `status`, `published_at`)
VALUES (
    'Societies & Clubs',
    'societies-clubs',
    'Beyond the classroom — where students discover new interests, build lifelong skills, and make friends who share them.',
    'default',
    25,
    0,
    0,
    'published',
    NOW()
);

SET @page_id = LAST_INSERT_ID();

INSERT INTO `page_sections` (`page_id`, `section_type`, `sort_order`, `content`) VALUES
(@page_id, 'stats', 1, JSON_OBJECT(
    'heading', '',
    'items', JSON_ARRAY(
        JSON_OBJECT('value', '6+', 'label', 'Active Societies'),
        JSON_OBJECT('value', '20+', 'label', 'Events Per Year'),
        JSON_OBJECT('value', '100+', 'label', 'Student Members'),
        JSON_OBJECT('value', '4', 'label', 'Academic Wings Represented')
    )
)),
(@page_id, 'icon_grid', 2, JSON_OBJECT(
    'heading', 'Our Societies',
    'subheading', 'Every student is welcome to join — sign-ups open at the start of each academic session.',
    'items', JSON_ARRAY(
        JSON_OBJECT('icon', 'journal-bookmark', 'title', 'Literary & Debating Society', 'description', 'Builds confidence and communication through declamations, debates, essay writing, and speech contests.'),
        JSON_OBJECT('icon', 'flask', 'title', 'Science & Robotics Club', 'description', 'Hands-on experiments, science exhibitions, and robotics projects for curious young minds.'),
        JSON_OBJECT('icon', 'trophy', 'title', 'Sports & Fitness Club', 'description', 'Cricket, football, badminton, and athletics — organizing tournaments and the annual Sports Day.'),
        JSON_OBJECT('icon', 'easel', 'title', 'Arts & Media Society', 'description', 'Photography, creative writing, and the school magazine — the voice and eye of campus life.'),
        JSON_OBJECT('icon', 'star', 'title', 'Islamic Society', 'description', 'Quran recitation, Naat competitions, Seerat conferences, and Islamic knowledge quizzes.'),
        JSON_OBJECT('icon', 'shield-check', 'title', 'Community Service Club', 'description', 'Charity drives, cleanliness campaigns, and awareness walks that build empathy and civic sense.')
    )
)),
(@page_id, 'cta', 3, JSON_OBJECT(
    'heading', 'Want to Join a Society?',
    'text', 'Sign-ups open during the first two weeks of every academic session. Ask your class teacher or visit the school office to register.',
    'button_text', 'Contact Us',
    'button_url', '/contact'
));

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0041_societies_clubs_page.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
