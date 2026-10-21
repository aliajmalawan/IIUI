-- =============================================================================
-- 0021_homepage_programs.sql  ·  "Academic Programs" homepage section — admin-
-- authored program cards (icon + title + grade range + short description).
-- Same shape as homepage_features (0019) since it's the same kind of curated,
-- admin-ordered homepage content block.
--
-- Deliberately NOT built on the real `school_classes`/`wing` SIS structure
-- (0002_academic_structure.sql): that table models individual classes for
-- the future Students/Academics module and has no admin UI yet. This is
-- marketing copy for five broad program levels, editable independently of
-- however the SIS eventually organizes actual class rosters.
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `homepage_programs` (
    `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(80)       NOT NULL,
    `grade_range` VARCHAR(60)       NULL COMMENT 'e.g. Ages 3-4, Grades 1-5',
    `description` VARCHAR(180)      NULL,
    `icon`        VARCHAR(40)       NULL COMMENT 'bootstrap-icons sprite symbol name',
    `sort_order`  SMALLINT          NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1)        NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_homepage_programs_active` (`is_active`, `sort_order`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0021_homepage_programs.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
