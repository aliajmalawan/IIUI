-- =============================================================================
-- 0034_pages_content.sql · Full "editable from the Pages list" for the four
-- system pages — extends 0033 (banner-only) to also own each page's heading
-- (`title`) and lead paragraph (`excerpt`), reusing columns the `pages` CMS
-- schema already had rather than adding new ones.
--
-- This intentionally decouples two pairs of content that used to be shared:
--   - About page hero (was `settings.about_heading`/`about_subheading`) is
--     now its own copy here. The homepage Welcome section keeps reading
--     those same settings rows unchanged — the two headings can now be
--     edited independently instead of always mirroring each other.
--   - Admissions page hero (was `settings.cta_heading`/`cta_body`) is now
--     its own copy here. Those two settings rows are removed below since
--     nothing else ever read them; `cta_button_text`/`cta_button_url`
--     are untouched — the Admissions button text still comes from there.
--
-- Seeded with each page's CURRENT live text so nothing visibly changes
-- until an admin edits it from /admin/pages.
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

UPDATE `pages` SET `title` = 'About Our Campus', `excerpt` = 'A legacy of academic excellence' WHERE `slug` = 'about';
UPDATE `pages` SET `title` = 'Our Academic Programs', `excerpt` = 'A structured path from Play Group through College, built on a strong academic and Islamic foundation.' WHERE `slug` = 'academics';
UPDATE `pages` SET `title` = 'Admissions Open for 2026-2027', `excerpt` = 'Apply now and secure your place for the upcoming academic session.' WHERE `slug` = 'admissions';
UPDATE `pages` SET `title` = 'We''d Love to Hear From You', `excerpt` = 'Questions about admissions, academics, or anything else? Reach out below.' WHERE `slug` = 'contact';

-- Now redundant — the Admissions page hero reads pages.title/excerpt instead.
DELETE FROM `settings` WHERE `key_name` IN ('cta_heading', 'cta_body');

-- Clarify these two now only feed the homepage Welcome section, not the
-- About page itself (which reads pages.title/excerpt as of this migration).
UPDATE `settings` SET `label` = 'Homepage Welcome Heading', `description` = 'Shown in the "About Our Campus" section on the homepage. The About page itself is edited separately from Pages.' WHERE `key_name` = 'about_heading';
UPDATE `settings` SET `label` = 'Homepage Welcome Subheading', `description` = 'Shown in the "About Our Campus" section on the homepage. The About page itself is edited separately from Pages.' WHERE `key_name` = 'about_subheading';

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0034_pages_content.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
