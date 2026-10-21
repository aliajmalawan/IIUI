-- =============================================================================
-- 0026_seed_header_menu.sql  ·  Header navigation items for the "Header
-- Navigation" menu (menus.id=1, already seeded/created in an earlier phase).
-- "Home" is not stored here — it's hardcoded first in _header.php.
--
-- Gallery/News point at their full standalone pages (Steps 6.5/6.6). About,
-- Academics, and Admissions have no dedicated pages yet, so they anchor into
-- homepage sections instead — About -> Welcome/Institution-intro section,
-- Academics -> Academic Programs section, Admissions -> the Contact section
-- (no admissions module exists yet, so inquiries route through the contact
-- form, same stopgap used for the Hero's "Apply Online" button).
-- -----------------------------------------------------------------------------
-- One-time seed, not an upsert — no natural unique key on `title`;
-- idempotency comes from the `migrations` guard below.
-- =============================================================================
USE `iiui_mardan`;

INSERT INTO `menu_items` (`menu_id`, `title`, `type`, `url`, `target`, `sort_order`, `is_active`) VALUES
    (1, 'About',      'custom', '/#about',     '_self', 1, 1),
    (1, 'Academics',  'custom', '/#academics', '_self', 2, 1),
    (1, 'Admissions', 'custom', '/#contact',   '_self', 3, 1),
    (1, 'Gallery',    'custom', '/gallery',    '_self', 4, 1),
    (1, 'News',       'custom', '/news',       '_self', 5, 1),
    (1, 'Contact',    'custom', '/#contact',   '_self', 6, 1);

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0026_seed_header_menu.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
