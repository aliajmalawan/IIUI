-- =============================================================================
-- 0023_homepage_facilities.sql  ·  "Facilities" homepage section — large
-- image cards (Library, Computer Lab, Science Lab, Transport, Mosque, Play
-- Ground, Cafeteria). Same admin-ordered-card shape as homepage_features
-- (0019) and homepage_programs (0021), plus an image path since this
-- section is explicitly photo-led — `icon` is kept too, as the fallback
-- shown until a real photo is uploaded (same graceful-degradation pattern
-- used for Faculty/Event cards elsewhere in the public site).
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `homepage_facilities` (
    `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(80)       NOT NULL,
    `description` VARCHAR(180)      NULL,
    `image`       VARCHAR(255)      NULL,
    `icon`        VARCHAR(40)       NULL COMMENT 'bootstrap-icons sprite symbol name; fallback until image is uploaded',
    `sort_order`  SMALLINT          NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1)        NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_homepage_facilities_active` (`is_active`, `sort_order`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0023_homepage_facilities.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
