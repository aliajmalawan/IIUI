-- =============================================================================
-- 0038_admissions_overview_sections.sql · Turns /admissions into a fuller
-- "admissions overview" page (reference: darsons.edu.pk/admissions.php) by
-- adding four content sections through the existing Pages section-builder
-- (0036/section_types.php) — no new tables, controllers, or admin screens
-- needed, since icon_grid/rich_text/faq/cta already exist as reusable types.
-- All four are admin-editable afterwards from Admin → Pages → Admissions →
-- Manage Content Sections.
--
-- Fee Structure intentionally lists fee HEADS and frequency only, not Rupee
-- amounts — `fee_structures` (the real per-class amount table) is empty and
-- part of the not-yet-built Finance module; inventing numbers here would be
-- presented as real to a prospective parent, which is worse than omitting
-- them. `fee_categories` (already seeded) is exactly the eight rows quoted
-- below, kept in sync with that table's `name` column.
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `page_sections` (`page_id`, `section_type`, `sort_order`, `content`)
SELECT id, 'icon_grid', 1, JSON_OBJECT(
    'heading', 'Eligibility Criteria',
    'subheading', 'General admission requirements across all levels — Play Group through College.',
    'items', JSON_ARRAY(
        JSON_OBJECT('icon', 'star', 'title', 'Play Group – Class 5 (Primary)', 'description', 'Age-appropriate for the grade applied to. No prior academic record required for Play Group or Nursery.'),
        JSON_OBJECT('icon', 'book-half', 'title', 'Class 6 – 8 (Middle)', 'description', 'Promotion/leaving certificate from the previous school along with the last grade\'s result card.'),
        JSON_OBJECT('icon', 'award', 'title', 'Class 9 – 10 (Secondary)', 'description', 'Minimum 60% marks in the previous class. Class 10 admissions require an SSC-I result card.'),
        JSON_OBJECT('icon', 'graph-up-arrow', 'title', 'Class 11 – 12 (College)', 'description', 'Minimum 60% marks in Matriculation to qualify for Pre-Medical, Pre-Engineering, ICS, Commerce, or Arts.')
    )
) FROM `pages` WHERE `slug` = 'admissions'

UNION ALL

SELECT id, 'rich_text', 2, JSON_OBJECT(
    'heading', 'Fee Structure',
    'content', CONCAT(
        '<table><thead><tr><th>Fee Head</th><th>Frequency</th></tr></thead><tbody>',
        '<tr><td>Admission Fee</td><td>One-time, at enrollment</td></tr>',
        '<tr><td>Tuition Fee</td><td>Monthly</td></tr>',
        '<tr><td>Examination Fee</td><td>Per term</td></tr>',
        '<tr><td>Library Fee</td><td>Monthly</td></tr>',
        '<tr><td>Laboratory Fee</td><td>Monthly (Science students)</td></tr>',
        '<tr><td>Transport Fee</td><td>Monthly, optional — route-dependent</td></tr>',
        '<tr><td>Annual Fund</td><td>One-time, yearly</td></tr>',
        '<tr><td>Security Deposit</td><td>One-time, refundable on leaving</td></tr>',
        '</tbody></table><p>Exact amounts vary by class and are shared during the admission interview, or available anytime from the admission office.</p>'
    )
) FROM `pages` WHERE `slug` = 'admissions'

UNION ALL

SELECT id, 'faq', 3, JSON_OBJECT(
    'heading', 'Frequently Asked Questions',
    'items', JSON_ARRAY(
        JSON_OBJECT('question', 'What is the minimum age for Play Group / Nursery admission?', 'answer', 'Children should be at least 3 years old to be considered for Play Group.'),
        JSON_OBJECT('question', 'Is there an entrance test for admission?', 'answer', 'Applicants for Class VI and above sit a short entrance assessment. Play Group through Class 5 admissions are based on an informal interaction with the student and guardian.'),
        JSON_OBJECT('question', 'What documents are required at the interview?', 'answer', 'The student\'s B-Form/CNIC, the guardian\'s CNIC, a school leaving certificate (if applicable), and 4 recent passport-size photographs.'),
        JSON_OBJECT('question', 'Can I apply online?', 'answer', 'Yes — use the Online Application Form on this page. You will receive a reference number and we will contact you about the next steps.'),
        JSON_OBJECT('question', 'Is transport available?', 'answer', 'Transport is available on select routes for an additional monthly fee. Ask the admission office whether your area is covered.'),
        JSON_OBJECT('question', 'When do admissions close?', 'answer', 'Admissions stay open for the current academic session shown above. Seats are limited and allotted on a first-come, first-served basis, subject to eligibility.')
    )
) FROM `pages` WHERE `slug` = 'admissions'

UNION ALL

SELECT id, 'cta', 4, JSON_OBJECT(
    'heading', 'Need Help with Admissions?',
    'text', 'Our admission office is available six days a week to answer questions about eligibility, fees, or the application process.',
    'button_text', 'Call +92-302-8819999',
    'button_url', 'tel:+923028819999'
) FROM `pages` WHERE `slug` = 'admissions';

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0038_admissions_overview_sections.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
