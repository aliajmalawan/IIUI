-- =============================================================================
-- 0033_seed_system_pages.sql · Seeds the four existing standalone routes
-- (About, Academics, Admissions, Contact — routes/web.php +
-- Controllers/Web/PageController) as rows in the `pages` table that was
-- already part of the base CMS schema (0008_cms.sql) but never populated —
-- this is what unlocks the sidebar's "Pages" item out of "Soon". Scope for
-- now is just the per-page hero banner (`featured_image`); `content`/SEO/
-- status stay at their column defaults since nothing on the public site
-- reads them for these four pages yet — wiring that up is a separate step,
-- not implied by turning the banner-per-page feature on.
-- `is_system = 1` marks them as tied to real application routes (not
-- deletable through the generic Pages admin UI); `status = 'published'`
-- because they are already live, not because the CMS workflow is wired up.
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `pages` (`title`, `slug`, `sort_order`, `is_system`, `show_in_menu`, `status`, `published_at`)
VALUES
    ('About Us',   'about',      1, 1, 0, 'published', NOW()),
    ('Academics',  'academics',  2, 1, 0, 'published', NOW()),
    ('Admissions', 'admissions', 3, 1, 0, 'published', NOW()),
    ('Contact Us', 'contact',    4, 1, 0, 'published', NOW())
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0033_seed_system_pages.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
