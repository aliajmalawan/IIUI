-- =============================================================================
-- 0038_seed_curriculum_page.sql · "Curriculum" page, built purely on the
-- generic custom-page system (pages + page_sections, served at
-- /page/{slug} by the existing PageController::show()/web.pages.custom
-- view) — same zero-new-code approach as Vision & Mission (0018) and
-- Accreditation & Affiliations (0035). Unlike Leadership/Departments, this
-- page needs no relational data of its own (no per-subject table exists,
-- or should exist just for this), so no new controller method, route, or
-- view was required — only content.
--
-- Content complements rather than duplicates two existing pages:
-- Academics (/academics) already covers the wing/grade-range structure
-- (Play Group ... College, from homepage_programs); Departments
-- (/departments) already covers who teaches each subject. This page covers
-- WHAT is taught — subject areas and a grade-wise subject breakdown — and
-- deliberately cross-links back to Academics via its closing CTA rather
-- than re-describing the same wings.
--
-- Figures/subject lists below are realistic STARTER/PLACEHOLDER copy, same
-- convention as every other seeded page this project — the admin should
-- refine them via Admin -> Pages -> Curriculum -> Sections.
--
-- "Curriculum" is added as the second child of "Academics" (menu_items
-- parent_id=7), right after "Departments" — sort_order of the existing
-- Examination Policy/Academic Calendar children is bumped by one to make
-- room, same mechanism used when Departments was added (0037).
--
-- One-time seed for page_sections/menu_items (no natural unique key);
-- `pages` uses ON DUPLICATE KEY UPDATE on its unique `slug`, matching
-- 0033_seed_system_pages.sql's convention.
-- =============================================================================
USE `iiui_mardan`;

INSERT INTO `pages` (`title`, `slug`, `excerpt`, `sort_order`, `is_system`, `show_in_menu`, `status`, `published_at`)
VALUES
  ('Curriculum', 'curriculum',
   'A structured, values-based curriculum that grows with every student from Play Group through College.',
   23, 0, 1, 'published', NOW())
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

INSERT INTO `page_sections` (`page_id`, `section_type`, `sort_order`, `is_active`, `content`)
VALUES
  (
    (SELECT id FROM `pages` WHERE slug = 'curriculum'),
    'rich_text', 1, 1,
    '{"heading":"A Curriculum Built for Every Stage","content":"<p>Our curriculum blends Pakistan''s national academic standards with strong Islamic values, structured so every student builds on what they learned the year before. From Play Group through College, teaching stays hands-on and age-appropriate, with FBISE-aligned syllabi guiding Secondary and Intermediate classes.</p><p>Subject teams work together across grades so a student''s learning in English, Mathematics, Science, and Islamic Studies reinforces itself year over year, not just within a single term.</p>"}'
  ),
  (
    (SELECT id FROM `pages` WHERE slug = 'curriculum'),
    'icon_grid', 2, 1,
    '{"heading":"Core Subject Areas","subheading":"What every student studies as they move through the campus.","items":[{"icon":"flask","title":"Science","description":"Physics, Chemistry, and Biology taught with hands-on lab work from Middle School onward.","color":"info"},{"icon":"graph-up","title":"Mathematics","description":"Building numerical fluency from foundational arithmetic through Intermediate-level Mathematics.","color":"violet"},{"icon":"moon-stars-fill","title":"Islamic Studies","description":"Quran, Hadith, and Islamiyat woven into everyday learning, not confined to a single period.","color":"brand"},{"icon":"book-half","title":"English","description":"Reading, writing, and communication skills built and tested at every grade level.","color":"gold"},{"icon":"pc-display","title":"Computer Studies","description":"Digital literacy and computing fundamentals introduced from Middle School.","color":"success"},{"icon":"trophy","title":"Co-Curricular Activities","description":"Sports, debates, and clubs that build teamwork and leadership beyond the classroom.","color":"info"}]}'
  ),
  (
    (SELECT id FROM `pages` WHERE slug = 'curriculum'),
    'rich_text', 3, 1,
    '{"heading":"Subjects by Wing","content":"<p>Core subjects build in depth and specialization as students move from Play Group through College:</p><table><thead><tr><th>Wing</th><th>Grades</th><th>Core Subjects</th></tr></thead><tbody><tr><td>Play Group</td><td>Ages 3-4</td><td>Early literacy, numeracy, and Islamic values through guided play</td></tr><tr><td>Primary</td><td>Grades 1-5</td><td>English, Urdu, Mathematics, Science, Islamiyat, Social Studies</td></tr><tr><td>Middle</td><td>Grades 6-8</td><td>English, Urdu, Mathematics, Science, Islamiyat, Computer Studies, Social Studies</td></tr><tr><td>High School</td><td>Grades 9-10</td><td>Physics, Chemistry, Biology or Computer Science, Mathematics, English, Urdu, Islamiyat, Pakistan Studies</td></tr><tr><td>College</td><td>Grades 11-12</td><td>Pre-Medical, Pre-Engineering, or Commerce group subjects per the FBISE syllabus</td></tr></tbody></table>"}'
  ),
  (
    (SELECT id FROM `pages` WHERE slug = 'curriculum'),
    'faq', 4, 1,
    '{"heading":"Curriculum Questions","items":[{"question":"What is the medium of instruction?","answer":"Classes are taught in English, with Urdu and Islamiyat taught per the national curriculum requirements."},{"question":"Are textbooks provided or purchased separately?","answer":"A book list is issued at the start of each session, and books are available through the campus bookshop."},{"question":"Is homework given at every grade level?","answer":"Yes, age-appropriate homework begins in Primary and increases gradually through Secondary and College."},{"question":"What co-curricular activities are offered?","answer":"Sports, debates, science fairs, and Islamic knowledge competitions run throughout the academic year."}]}'
  ),
  (
    (SELECT id FROM `pages` WHERE slug = 'curriculum'),
    'cta', 5, 1,
    '{"heading":"Want to See How This Fits Your Child''s Grade?","text":"Explore our grade-wise academic programs, from Play Group through College.","button_text":"Explore Grade-Wise Programs","button_url":"/academics"}'
  );

UPDATE `menu_items` SET `sort_order` = `sort_order` + 1 WHERE `parent_id` = 7 AND `sort_order` >= 2;

INSERT INTO `menu_items` (`menu_id`, `parent_id`, `title`, `type`, `url`, `target`, `sort_order`, `is_active`)
VALUES
  (1, 7, 'Curriculum', 'custom', '/page/curriculum', '_self', 2, 1);

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0038_seed_curriculum_page.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
