-- =============================================================================
-- 0036_page_sections.sql · Full section-based page builder. A page's hero
-- (title/excerpt/featured_image on `pages`, added in 0033-0034) stays as the
-- one universal banner every page has; everything in the BODY is now a
-- stack of reorderable, toggleable, admin-editable sections — mirroring the
-- reference implementation the user pointed at (EduTop's page_sections).
--
-- `content` is a JSON blob whose shape is defined per section_type by
-- app/Config/section_types.php (the admin form generator and the public
-- renderer both read that config, not this table, to know a type's fields).
--
-- About's existing intro paragraph + photo (pages.content/content_image,
-- from 0035) becomes this table's first real row — an `image_text` section
-- — instead of living as flat columns on `pages`. Those two columns are
-- left in place (harmless, just unused by the page-render path from here
-- on) rather than dropped, since dropping columns is not easily reversible.
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `page_sections` (
    `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `page_id`      BIGINT UNSIGNED NOT NULL,
    `section_type` VARCHAR(60)     NOT NULL,
    `sort_order`   SMALLINT        NOT NULL DEFAULT 0,
    `is_active`    TINYINT(1)      NOT NULL DEFAULT 1,
    `content`      LONGTEXT        NULL COMMENT 'JSON, shape defined per section_type in app/Config/section_types.php',
    `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_page_sections_page` (`page_id`, `sort_order`),
    CONSTRAINT `fk_page_sections_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO `page_sections` (`page_id`, `section_type`, `sort_order`, `content`)
SELECT
    p.id,
    'image_text',
    1,
    JSON_OBJECT(
        'heading', '',
        'text', COALESCE(p.content, ''),
        'image', p.content_image,
        'image_position', 'right'
    )
FROM `pages` p
WHERE p.slug = 'about' AND (p.content IS NOT NULL AND p.content <> '');

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0036_page_sections.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
