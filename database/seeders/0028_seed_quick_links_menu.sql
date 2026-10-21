-- =============================================================================
-- 0028_seed_quick_links_menu.sql  ·  Footer "Quick Links" column items, for
-- the "Quick Links" menu (menus.id=3, location='quick_links', already
-- created in an earlier phase). Same URL targets as the header's matching
-- items (0026_seed_header_menu.sql) — see that file for why Admissions and
-- Contact both point at the homepage's Contact section.
-- -----------------------------------------------------------------------------
-- One-time seed, not an upsert — no natural unique key on `title`;
-- idempotency comes from the `migrations` guard below.
-- =============================================================================
USE `iiui_mardan`;

INSERT INTO `menu_items` (`menu_id`, `title`, `type`, `url`, `target`, `sort_order`, `is_active`) VALUES
    (3, 'Admissions', 'custom', '/#contact', '_self', 1, 1),
    (3, 'Gallery',    'custom', '/gallery',  '_self', 2, 1),
    (3, 'News',       'custom', '/news',     '_self', 3, 1),
    (3, 'Contact',    'custom', '/#contact', '_self', 4, 1);

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0028_seed_quick_links_menu.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
