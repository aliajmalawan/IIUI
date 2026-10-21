-- =============================================================================
-- 0016_homepage_builder.sql  ·  Two small tables the Homepage Builder needs
-- that nothing else in the schema already covers.
-- -----------------------------------------------------------------------------
-- Hero Slider  -> existing `sliders` table (0008_cms.sql) — no change needed.
-- Testimonials -> existing `testimonials` table (0008_cms.sql) — no change needed.
-- About / CTA  -> existing `settings` table, new 'homepage' group — see
--                 database/seeders/0017_seed_homepage_settings.sql.
-- Statistics & the homepage's own image showcase have no existing table, so:
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

-- Curated marketing numbers ("1,200+ Students", "25 Years of Excellence") —
-- deliberately admin-authored text, not a live COUNT() query, since a stat
-- like "25 Years" isn't derivable from any table.
CREATE TABLE `homepage_stats` (
    `id`         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `label`      VARCHAR(80)       NOT NULL,
    `value`      VARCHAR(20)       NOT NULL COMMENT 'e.g. 1200',
    `suffix`     VARCHAR(10)       NULL COMMENT 'e.g. + or %',
    `icon`       VARCHAR(40)       NULL COMMENT 'bootstrap-icons sprite symbol name',
    `sort_order` SMALLINT          NOT NULL DEFAULT 0,
    `is_active`  TINYINT(1)        NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_homepage_stats_active` (`is_active`, `sort_order`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- A lightweight homepage image showcase — decoupled from the full Gallery
-- module (galleries + gallery_images) that a later CMS step builds; that
-- module manages event photo albums, this one is just "a few images on the
-- homepage". They may end up linked later; not assumed now.
CREATE TABLE `homepage_gallery_images` (
    `id`         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `image_path` VARCHAR(255)      NOT NULL,
    `caption`    VARCHAR(180)      NULL,
    `sort_order` SMALLINT          NOT NULL DEFAULT 0,
    `is_active`  TINYINT(1)        NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_homepage_gallery_active` (`is_active`, `sort_order`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0016_homepage_builder.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
