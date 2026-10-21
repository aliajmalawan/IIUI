-- =============================================================================
-- 0036_seed_examination_policy_page.sql · "Examination Policy" page, built
-- on the same generic custom-page system (pages + page_sections) as Vision
-- & Mission (0018) and Accreditation & Affiliations (0035) — no new
-- controller/route/view/CSS required. Adds a second rich_text section whose
-- `content` includes a real <table> (grade scale) — HtmlSanitizer already
-- allows table/thead/tbody/tr/th/td (app/Core/HtmlSanitizer.php
-- ALLOWED_TAGS), it just hadn't been exercised by any seeded content yet.
--
-- Figures below (attendance %, passing marks, grade bands) are realistic
-- STARTER/PLACEHOLDER policy numbers, same convention as the Faculty/
-- Leadership placeholders and Accreditation's body list (0033-0035) — the
-- admin should replace them with the campus's actual policy figures via
-- admin/pages -> Examination Policy -> Sections.
--
-- "Examination Policy" is added as the first child of the existing
-- "Academics" header nav item (menu_items id=7), turning it into a dropdown
-- the same way "About" already is — no template change needed, since
-- _nav-menu.php already renders any item with children as a dropdown.
--
-- One-time seed, not an upsert for page_sections/menu_items (no natural
-- unique key); `pages` uses ON DUPLICATE KEY UPDATE on its unique `slug`,
-- matching 0033_seed_system_pages.sql's convention.
-- =============================================================================
USE `iiui_mardan`;

INSERT INTO `pages` (`title`, `slug`, `excerpt`, `sort_order`, `is_system`, `show_in_menu`, `status`, `published_at`)
VALUES
  ('Examination Policy', 'examination-policy',
   'How we assess, promote, and support every student''s academic progress.',
   22, 0, 1, 'published', NOW())
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

INSERT INTO `page_sections` (`page_id`, `section_type`, `sort_order`, `is_active`, `content`)
VALUES
  (
    (SELECT id FROM `pages` WHERE slug = 'examination-policy'),
    'rich_text', 1, 1,
    '{"heading":"Assessment That Supports Learning","content":"<p>Examinations at IIUI Schools & College, Mardan Campus are designed to measure understanding, not just memorization. Every student is assessed continuously through the term and formally at the end of each term, so that both families and teachers have a clear, ongoing picture of progress, not just a single result at year end.</p><p>The policy below applies to all wings, from Primary through College, with grade-appropriate adjustments made by each department.</p>"}'
  ),
  (
    (SELECT id FROM `pages` WHERE slug = 'examination-policy'),
    'icon_grid', 2, 1,
    '{"heading":"Our Examination Framework","subheading":"How assessment works across the academic year.","items":[{"icon":"journal-bookmark","title":"Continuous Assessment","description":"Class tests, quizzes, and assignments recorded throughout the term and counted toward the final grade.","color":"brand"},{"icon":"calendar-event","title":"Terminal & Final Examinations","description":"A mid-year terminal exam and an annual final exam, both covering the full syllabus taught to date.","color":"gold"},{"icon":"graph-up","title":"Promotion Criteria","description":"Promotion to the next class requires minimum passing marks per subject and at least 75% attendance.","color":"info"},{"icon":"arrow-repeat","title":"Re-take & Improvement Exams","description":"Students who miss an exam for a documented medical or emergency reason may sit a supplementary paper.","color":"success"},{"icon":"shield-check","title":"Academic Integrity","description":"Any form of malpractice during an examination is reported to the Principal''s office and handled under the campus discipline policy.","color":"violet"}]}'
  ),
  (
    (SELECT id FROM `pages` WHERE slug = 'examination-policy'),
    'rich_text', 3, 1,
    '{"heading":"Grading Scale","content":"<p>Final marks in each subject are converted to a letter grade as follows:</p><table><thead><tr><th>Grade</th><th>Percentage</th><th>Description</th></tr></thead><tbody><tr><td>A+</td><td>90% and above</td><td>Outstanding</td></tr><tr><td>A</td><td>80%-89%</td><td>Excellent</td></tr><tr><td>B</td><td>70%-79%</td><td>Very Good</td></tr><tr><td>C</td><td>60%-69%</td><td>Good</td></tr><tr><td>D</td><td>50%-59%</td><td>Satisfactory</td></tr><tr><td>E</td><td>33%-49%</td><td>Marginal Pass</td></tr><tr><td>F</td><td>Below 33%</td><td>Fail, repeat year or supplementary exam required</td></tr></tbody></table>"}'
  ),
  (
    (SELECT id FROM `pages` WHERE slug = 'examination-policy'),
    'faq', 4, 1,
    '{"heading":"Common Questions","items":[{"question":"What happens if my child misses an exam due to illness?","answer":"Submit a medical certificate to the class coordinator within 3 working days. A supplementary exam is then scheduled before the term''s results are finalized."},{"question":"Is there a supplementary or improvement exam?","answer":"Yes, students who fail a subject, or who missed it for a documented reason, may sit one supplementary paper per subject at the end of the academic year."},{"question":"What is the minimum attendance required to sit final exams?","answer":"75% attendance for the term is required. Students below this threshold need Principal approval to sit the exam."},{"question":"How and when are results communicated?","answer":"Report cards are issued within two weeks of each exam, and are also shared with parents at the term''s parent-teacher meeting."}]}'
  ),
  (
    (SELECT id FROM `pages` WHERE slug = 'examination-policy'),
    'cta', 5, 1,
    '{"heading":"Need the Full Examination Policy Document?","text":"Our academic office can share the complete written policy, including subject-wise weightage, on request.","button_text":"Contact Us","button_url":"/contact"}'
  );

INSERT INTO `menu_items` (`menu_id`, `parent_id`, `title`, `type`, `url`, `target`, `sort_order`, `is_active`)
VALUES
  (1, 7, 'Examination Policy', 'custom', '/page/examination-policy', '_self', 1, 1);

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0036_seed_examination_policy_page.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
