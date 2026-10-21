-- =============================================================================
-- 0029_update_menu_urls_to_pages.sql  ·  Points the header and quick_links
-- menu items (0026, 0028) at real standalone pages now that they exist
-- (/about, /academics, /admissions, /contact), instead of the homepage
-- anchors (#about, #academics, #contact) used as a stopgap before those
-- pages were built.
-- =============================================================================
USE `iiui_mardan`;

UPDATE `menu_items` SET `url` = '/about'      WHERE `title` = 'About'      AND `url` = '/#about';
UPDATE `menu_items` SET `url` = '/academics'  WHERE `title` = 'Academics'  AND `url` = '/#academics';
UPDATE `menu_items` SET `url` = '/admissions' WHERE `title` = 'Admissions' AND `url` = '/#contact';
UPDATE `menu_items` SET `url` = '/contact'    WHERE `title` = 'Contact'    AND `url` = '/#contact';

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0029_update_menu_urls_to_pages.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
