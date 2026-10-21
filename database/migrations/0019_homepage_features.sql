-- =============================================================================
-- 0019_homepage_features.sql  ·  "Why Choose Us" homepage section — admin-
-- authored feature cards (icon + title + short description), same shape as
-- homepage_stats (0016_homepage_builder.sql) since it's the same kind of
-- curated, admin-ordered homepage content block.
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `homepage_features` (
    `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(80)       NOT NULL,
    `description` VARCHAR(180)      NULL,
    `icon`        VARCHAR(40)       NULL COMMENT 'bootstrap-icons sprite symbol name',
    `sort_order`  SMALLINT          NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1)        NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_homepage_features_active` (`is_active`, `sort_order`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0019_homepage_features.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
