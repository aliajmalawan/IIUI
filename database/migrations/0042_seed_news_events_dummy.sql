-- =============================================================================
-- 0042_seed_news_events_dummy.sql · Starter news categories, posts and events
-- so the combined "News & Events" feed (/news) has real content and working
-- filter pills from day one instead of an empty state. Content is generic
-- placeholder copy meant to be edited/replaced from the admin panel, not
-- treated as authoritative announcements.
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `post_categories` (`name`, `slug`, `description`, `sort_order`, `is_active`)
VALUES
    ('Announcements', 'announcements', 'Official notices and updates from the campus administration.', 1, 1),
    ('Achievements', 'achievements', 'Student and faculty accomplishments in academics, sports and co-curriculars.', 2, 1),
    ('Campus News', 'campus-news', 'General happenings and developments around the campus.', 3, 1);

INSERT INTO `posts` (`category_id`, `title`, `slug`, `excerpt`, `content`, `status`, `is_featured`, `is_pinned`, `published_at`, `author_id`)
SELECT id, 'Admissions Open for Academic Year 2026-27',
    'admissions-open-academic-year-2026-27',
    'Applications are now being accepted for the upcoming academic year across all campus programs.',
    '<p>IIUI Mardan Campus is pleased to announce that admissions for the 2026-27 academic year are now open. Interested students and parents can apply online through the Admissions section of this website.</p><p>Seats are limited and offered on a first-come, first-served basis subject to eligibility criteria. For queries, please contact the admissions office.</p>',
    'published', 1, 1, '2026-08-01 09:00:00', 1
FROM `post_categories` WHERE `slug` = 'announcements'
UNION ALL
SELECT id, 'Winter Vacation Schedule Announced',
    'winter-vacation-schedule-announced',
    'The campus will remain closed for winter break starting late December, reopening for the spring term in the new year.',
    '<p>The administration has announced the winter vacation schedule for all students and staff. The campus will close for the winter break and resume regular classes for the spring term as per the academic calendar.</p><p>Please refer to the Academic Calendar page for exact dates.</p>',
    'published', 0, 0, '2026-07-20 10:30:00', 1
FROM `post_categories` WHERE `slug` = 'announcements'
UNION ALL
SELECT id, 'IIUI Mardan Students Excel in Inter-School Science Olympiad',
    'students-excel-inter-school-science-olympiad',
    'Our students secured top positions at the regional Inter-School Science Olympiad, bringing home multiple honors.',
    '<p>Students of IIUI Mardan Campus put in an outstanding performance at the regional Inter-School Science Olympiad, securing first and second positions in the Physics and Chemistry categories respectively.</p><p>The campus congratulates the participating students and their mentoring faculty for this achievement.</p>',
    'published', 1, 0, '2026-07-25 14:00:00', 1
FROM `post_categories` WHERE `slug` = 'achievements'
UNION ALL
SELECT id, 'Annual Sports Gala Concludes with Record Participation',
    'annual-sports-gala-concludes-record-participation',
    'This year''s Annual Sports Gala saw record turnout across track, field and team events.',
    '<p>The Annual Sports Gala concluded successfully with enthusiastic participation from students across all grades. Events included track and field, football, cricket and tug-of-war, with prizes distributed to winning houses.</p>',
    'published', 0, 0, '2026-07-15 16:45:00', 1
FROM `post_categories` WHERE `slug` = 'achievements'
UNION ALL
SELECT id, 'New Computer Lab Inaugurated on Campus',
    'new-computer-lab-inaugurated-on-campus',
    'A state-of-the-art computer lab has been added to campus facilities to strengthen digital learning.',
    '<p>A newly equipped computer lab was inaugurated on campus, aimed at enhancing hands-on digital literacy and computer science instruction for students.</p><p>The lab is fitted with modern workstations and is available for use as part of the regular curriculum.</p>',
    'published', 0, 0, '2026-07-10 11:15:00', 1
FROM `post_categories` WHERE `slug` = 'campus-news'
UNION ALL
SELECT id, 'Faculty Development Workshop Held for Teaching Staff',
    'faculty-development-workshop-held-teaching-staff',
    'Teaching staff participated in a professional development workshop focused on modern classroom practices.',
    '<p>A faculty development workshop was held on campus, covering modern teaching methodologies, classroom management and student assessment techniques.</p><p>The session was well received by attending faculty members.</p>',
    'published', 0, 0, '2026-07-05 13:00:00', 1
FROM `post_categories` WHERE `slug` = 'campus-news';

INSERT INTO `events` (`title`, `slug`, `description`, `content`, `start_datetime`, `end_datetime`, `venue`, `organizer`, `category`, `registration_required`, `is_featured`, `status`, `author_id`)
VALUES
    ('Parent-Teacher Meeting - Term 1', 'parent-teacher-meeting-term-1',
     'An opportunity for parents to discuss student progress with class teachers for the first term.',
     '<p>Parents are invited to meet with class teachers to discuss student progress, attendance and upcoming academic plans for Term 1.</p>',
     '2026-08-22 09:00:00', '2026-08-22 13:00:00', 'Main Campus Hall', 'Academic Office', 'Academic', 0, 1, 'published', 1),
    ('Annual Science Exhibition', 'annual-science-exhibition',
     'Students showcase science projects and experiments from across all grade levels.',
     '<p>The Annual Science Exhibition features student-led projects and experiments spanning physics, chemistry and biology. Parents and visitors are welcome.</p>',
     '2026-09-12 10:00:00', '2026-09-12 16:00:00', 'Campus Auditorium', 'Science Department', 'Academic', 0, 1, 'published', 1),
    ('Inter-House Debate Competition', 'inter-house-debate-competition',
     'Students compete across houses in English and Urdu debate categories.',
     '<p>The Inter-House Debate Competition brings together student speakers from all houses to compete in English and Urdu categories on current topics.</p>',
     '2026-09-28 11:00:00', '2026-09-28 14:00:00', 'Campus Auditorium', 'English Department', 'Cultural', 1, 0, 'published', 1),
    ('Winter Break Begins', 'winter-break-begins',
     'Campus closes for the winter vacation and resumes for the spring term in the new year.',
     '<p>The campus will close for the winter vacation. Classes resume for the spring term as per the Academic Calendar.</p>',
     '2026-12-25 00:00:00', '2027-01-05 23:59:00', 'Campus-wide', 'Administration', 'Announcement', 0, 0, 'published', 1);

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0042_seed_news_events_dummy.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
