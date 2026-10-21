-- =============================================================================
-- 0025_seed_homepage_stats.sql  ·  "Statistics" starter cards for the
-- existing homepage_stats table (0016_homepage_builder.sql). That table has
-- never had a seeder before now — it was only ever populated by hand through
-- the admin UI during Step 6.2 testing (and cleaned up again afterward).
-- -----------------------------------------------------------------------------
-- One-time seed, not an upsert — no natural unique key on `label`;
-- idempotency comes from the `migrations` guard below.
-- =============================================================================
USE `iiui_mardan`;

INSERT INTO `homepage_stats` (`label`, `value`, `suffix`, `icon`, `sort_order`, `is_active`) VALUES
    ('Students',            '1200', '+', 'people',       1, 1),
    ('Teachers',            '80',   '+', 'person-badge', 2, 1),
    ('Classes',              '40',   '+', 'easel',        3, 1),
    ('Years of Excellence', '25',   '+', 'award',        4, 1);

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0025_seed_homepage_stats.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
