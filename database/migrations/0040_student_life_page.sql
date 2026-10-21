-- =============================================================================
-- 0040_student_life_page.sql · A new custom page (reference: darsons.edu.pk/
-- student-life.php), built the same way as the Admissions overview sections
-- (0038) — through the existing Pages section-builder, no new tables. This
-- is the first page created via the "custom pages" path (is_system = 0,
-- served at /page/{slug}) rather than one of the four fixed system pages.
--
-- The gallery section reuses 8 REAL photos already uploaded to the
-- "Campus Life & Activities" album (galleries.id = 4) instead of inventing
-- placeholder images — same reasoning as 0038 leaving Rupee amounts out:
-- don't present fabricated content as real. A "View Full Gallery" CTA links
-- to that album's own page for the rest of its photos.
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `pages` (`title`, `slug`, `excerpt`, `template`, `sort_order`, `is_system`, `show_in_menu`, `status`, `published_at`)
VALUES (
    'Student Life',
    'student-life',
    'A rich and vibrant experience beyond the classroom — clubs, sports, events, and community service.',
    'default',
    24,
    0,
    0,
    'published',
    NOW()
);

SET @page_id = LAST_INSERT_ID();

INSERT INTO `page_sections` (`page_id`, `section_type`, `sort_order`, `content`) VALUES
(@page_id, 'rich_text', 1, JSON_OBJECT(
    'heading', 'Beyond Academics',
    'content', '<p>Student life at IIUI Schools &amp; College — Mardan Campus extends well past the classroom. Through clubs, sports, seminars, and community outreach, students build confidence, teamwork, and character alongside their academic learning.</p>'
)),
(@page_id, 'icon_grid', 2, JSON_OBJECT(
    'heading', 'Life at Our Campus',
    'subheading', 'Six ways students grow outside regular coursework.',
    'items', JSON_ARRAY(
        JSON_OBJECT('icon', 'calendar-event', 'title', 'Annual Events', 'description', 'Cultural programs, science exhibitions, farewell ceremonies, and award nights celebrated throughout the year.'),
        JSON_OBJECT('icon', 'easel', 'title', 'Seminars & Workshops', 'description', 'Guest lectures, skill workshops, and career-guidance sessions that go beyond the syllabus.'),
        JSON_OBJECT('icon', 'trophy', 'title', 'Sports Activities', 'description', 'Cricket, football, badminton, and athletics that build teamwork, discipline, and healthy competition.'),
        JSON_OBJECT('icon', 'geo-alt-fill', 'title', 'Educational Tours', 'description', 'Field visits to museums, exhibitions, and places of educational interest that bring lessons to life.'),
        JSON_OBJECT('icon', 'people', 'title', 'Student Clubs', 'description', 'Debate, science, and literary societies where students pursue interests alongside like-minded peers.'),
        JSON_OBJECT('icon', 'star', 'title', 'Community Service', 'description', 'Awareness drives and community outreach programs that build empathy and civic responsibility.')
    )
)),
(@page_id, 'gallery', 3, JSON_OBJECT(
    'heading', 'Campus Life Gallery',
    'images', JSON_ARRAY(
        JSON_OBJECT('image', '/uploads/galleries/4/515b4479e4163bdbbcdc22d7ea61c99f.jpg', 'caption', ''),
        JSON_OBJECT('image', '/uploads/galleries/4/5a5b8f2f46a1675d24b724187f042298.jpg', 'caption', ''),
        JSON_OBJECT('image', '/uploads/galleries/4/3b3e483ece2e4f6d6263df38a1cb5d08.jpg', 'caption', ''),
        JSON_OBJECT('image', '/uploads/galleries/4/aaf7a1a3bc812197aeaf1df805b88094.jpg', 'caption', ''),
        JSON_OBJECT('image', '/uploads/galleries/4/4faa867576dc3ef17244718165480b44.jpg', 'caption', ''),
        JSON_OBJECT('image', '/uploads/galleries/4/6955327c6cd2ca6b22ce3a3f061e8fb4.jpg', 'caption', ''),
        JSON_OBJECT('image', '/uploads/galleries/4/c62d02b63a5c115f9463e00bf9ff8397.jpg', 'caption', ''),
        JSON_OBJECT('image', '/uploads/galleries/4/81c438675fa44dc7d56851b1cfd7d2b0.jpg', 'caption', '')
    )
)),
(@page_id, 'cta', 4, JSON_OBJECT(
    'heading', 'See More From Campus Life',
    'text', 'Browse the full Campus Life & Activities album for more photos from events throughout the year.',
    'button_text', 'View Full Gallery',
    'button_url', '/gallery/campus-life-activities'
));

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0040_student_life_page.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
