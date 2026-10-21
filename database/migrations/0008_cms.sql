-- =============================================================================
-- 0008_cms.sql  ·  Domain H — Website content, media, menus, SEO
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

-- pages — `is_system = 1` on Home/About/Contact stops an editor deleting a page
-- the router depends on.
CREATE TABLE `pages` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id`        BIGINT UNSIGNED NULL,
    `title`            VARCHAR(180)    NOT NULL,
    `slug`             VARCHAR(200)    NOT NULL,
    `excerpt`          VARCHAR(500)    NULL,
    `content`          LONGTEXT        NULL,
    `featured_image`   VARCHAR(255)    NULL,
    `template`         VARCHAR(60)     NOT NULL DEFAULT 'default',
    `meta_title`       VARCHAR(180)    NULL,
    `meta_description` VARCHAR(300)    NULL,
    `meta_keywords`    VARCHAR(255)    NULL,
    `og_image`         VARCHAR(255)    NULL,
    `canonical_url`    VARCHAR(255)    NULL,
    `sort_order`       SMALLINT        NOT NULL DEFAULT 0,
    `is_system`        TINYINT(1)      NOT NULL DEFAULT 0,
    `show_in_menu`     TINYINT(1)      NOT NULL DEFAULT 0,
    `status`           ENUM('draft','published','scheduled','archived') NOT NULL DEFAULT 'draft',
    `published_at`     TIMESTAMP       NULL DEFAULT NULL,
    `author_id`        BIGINT UNSIGNED NULL,
    `views`            INT UNSIGNED    NOT NULL DEFAULT 0,
    `created_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`       TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pages_slug` (`slug`),
    KEY `idx_pages_status_published` (`status`, `published_at`),
    KEY `idx_pages_parent` (`parent_id`, `sort_order`),
    FULLTEXT KEY `ftx_pages_content` (`title`, `excerpt`, `content`),
    CONSTRAINT `fk_pages_parent` FOREIGN KEY (`parent_id`) REFERENCES `pages` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_pages_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `post_categories` (
    `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id`   SMALLINT UNSIGNED NULL,
    `name`        VARCHAR(100) NOT NULL,
    `slug`        VARCHAR(120) NOT NULL,
    `description` VARCHAR(255) NULL,
    `sort_order`  SMALLINT     NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_post_categories_slug` (`slug`),
    KEY `idx_post_categories_parent` (`parent_id`),
    CONSTRAINT `fk_post_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `post_categories` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `posts` (
    `id`               BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `category_id`      SMALLINT UNSIGNED NULL,
    `title`            VARCHAR(180)      NOT NULL,
    `slug`             VARCHAR(200)      NOT NULL,
    `excerpt`          VARCHAR(500)      NULL,
    `content`          LONGTEXT          NULL,
    `featured_image`   VARCHAR(255)      NULL,
    `gallery`          JSON              NULL,
    `meta_title`       VARCHAR(180)      NULL,
    `meta_description` VARCHAR(300)      NULL,
    `og_image`         VARCHAR(255)      NULL,
    `status`           ENUM('draft','published','scheduled','archived') NOT NULL DEFAULT 'draft',
    `is_featured`      TINYINT(1)        NOT NULL DEFAULT 0,
    `is_pinned`        TINYINT(1)        NOT NULL DEFAULT 0,
    `views`            INT UNSIGNED      NOT NULL DEFAULT 0,
    `published_at`     TIMESTAMP         NULL DEFAULT NULL,
    `author_id`        BIGINT UNSIGNED   NULL,
    `created_at`       TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`       TIMESTAMP         NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_posts_slug` (`slug`),
    -- The public news listing: status + date, newest first.
    KEY `idx_posts_listing`  (`status`, `published_at`),
    KEY `idx_posts_category` (`category_id`, `status`, `published_at`),
    KEY `idx_posts_featured` (`is_featured`, `status`, `published_at`),
    FULLTEXT KEY `ftx_posts_content` (`title`, `excerpt`, `content`),
    CONSTRAINT `fk_posts_category` FOREIGN KEY (`category_id`) REFERENCES `post_categories` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_posts_author`   FOREIGN KEY (`author_id`)   REFERENCES `users` (`id`)           ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tags` (
    `id`   SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(60)  NOT NULL,
    `slug` VARCHAR(80)  NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tags_slug` (`slug`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `post_tags` (
    `post_id` BIGINT UNSIGNED   NOT NULL,
    `tag_id`  SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (`post_id`, `tag_id`),
    KEY `idx_post_tags_tag` (`tag_id`),
    CONSTRAINT `fk_post_tags_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_post_tags_tag`  FOREIGN KEY (`tag_id`)  REFERENCES `tags` (`id`)  ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `events` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`                 VARCHAR(180)    NOT NULL,
    `slug`                  VARCHAR(200)    NOT NULL,
    `description`           VARCHAR(500)    NULL,
    `content`               LONGTEXT        NULL,
    `banner`                VARCHAR(255)    NULL,
    `start_datetime`        DATETIME        NOT NULL,
    `end_datetime`          DATETIME        NULL,
    `venue`                 VARCHAR(180)    NULL,
    `organizer`             VARCHAR(120)    NULL,
    `category`              VARCHAR(60)     NULL,
    `registration_required` TINYINT(1)      NOT NULL DEFAULT 0,
    `registration_url`      VARCHAR(255)    NULL,
    `capacity`              SMALLINT UNSIGNED NULL,
    `is_featured`           TINYINT(1)      NOT NULL DEFAULT 0,
    `status`                ENUM('draft','published','cancelled','completed') NOT NULL DEFAULT 'draft',
    `meta_title`            VARCHAR(180)    NULL,
    `meta_description`      VARCHAR(300)    NULL,
    `author_id`             BIGINT UNSIGNED NULL,
    `created_at`            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`            TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_events_slug` (`slug`),
    KEY `idx_events_schedule` (`status`, `start_datetime`),
    KEY `idx_events_featured` (`is_featured`, `start_datetime`),
    CONSTRAINT `chk_events_range` CHECK (`end_datetime` IS NULL OR `end_datetime` >= `start_datetime`),
    CONSTRAINT `fk_events_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `notices` (
    `id`           BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `title`        VARCHAR(180)      NOT NULL,
    `slug`         VARCHAR(200)      NOT NULL,
    `content`      LONGTEXT          NULL,
    `attachment`   VARCHAR(255)      NULL,
    `audience`     ENUM('all','students','parents','staff','specific_class') NOT NULL DEFAULT 'all',
    `class_id`     SMALLINT UNSIGNED NULL,
    `priority`     ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    `publish_from` DATETIME          NOT NULL,
    `publish_to`   DATETIME          NULL,
    `is_pinned`    TINYINT(1)        NOT NULL DEFAULT 0,
    `status`       ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    `views`        INT UNSIGNED      NOT NULL DEFAULT 0,
    `author_id`    BIGINT UNSIGNED   NULL,
    `created_at`   TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`   TIMESTAMP         NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_notices_slug` (`slug`),
    -- "What should this audience see right now?"
    KEY `idx_notices_window`   (`status`, `publish_from`, `publish_to`),
    KEY `idx_notices_audience` (`audience`, `class_id`, `status`),
    KEY `idx_notices_priority` (`is_pinned`, `priority`, `publish_from`),
    CONSTRAINT `chk_notices_window` CHECK (`publish_to` IS NULL OR `publish_to` >= `publish_from`),
    CONSTRAINT `fk_notices_class`  FOREIGN KEY (`class_id`)  REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notices_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`)          ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `galleries` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`        VARCHAR(180)    NOT NULL,
    `slug`         VARCHAR(200)    NOT NULL,
    `description`  VARCHAR(500)    NULL,
    `cover_image`  VARCHAR(255)    NULL,
    `event_id`     BIGINT UNSIGNED NULL,
    `gallery_date` DATE            NULL,
    `category`     VARCHAR(60)     NULL,
    `status`       ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    `sort_order`   SMALLINT        NOT NULL DEFAULT 0,
    `created_by`   BIGINT UNSIGNED NULL,
    `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_galleries_slug` (`slug`),
    KEY `idx_galleries_status` (`status`, `gallery_date`),
    CONSTRAINT `fk_galleries_event`      FOREIGN KEY (`event_id`)   REFERENCES `events` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_galleries_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)  ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `gallery_images` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `gallery_id`     BIGINT UNSIGNED NOT NULL,
    `image_path`     VARCHAR(255)    NOT NULL,
    `thumbnail_path` VARCHAR(255)    NULL,
    `caption`        VARCHAR(255)    NULL,
    `alt_text`       VARCHAR(180)    NULL,
    `sort_order`     SMALLINT        NOT NULL DEFAULT 0,
    `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_gallery_images_gallery` (`gallery_id`, `sort_order`),
    CONSTRAINT `fk_gallery_images_gallery` FOREIGN KEY (`gallery_id`) REFERENCES `galleries` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `sliders` (
    `id`              SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`           VARCHAR(180)  NOT NULL,
    `subtitle`        VARCHAR(255)  NULL,
    `description`     VARCHAR(500)  NULL,
    `image`           VARCHAR(255)  NOT NULL,
    `mobile_image`    VARCHAR(255)  NULL,
    `button_text`     VARCHAR(60)   NULL,
    `button_url`      VARCHAR(255)  NULL,
    `text_position`   ENUM('left','center','right') NOT NULL DEFAULT 'left',
    `overlay_opacity` TINYINT UNSIGNED NOT NULL DEFAULT 70,
    `sort_order`      SMALLINT      NOT NULL DEFAULT 0,
    `is_active`       TINYINT(1)    NOT NULL DEFAULT 1,
    `starts_at`       DATETIME      NULL,
    `ends_at`         DATETIME      NULL,
    `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sliders_active` (`is_active`, `sort_order`),
    CONSTRAINT `chk_sliders_opacity` CHECK (`overlay_opacity` BETWEEN 0 AND 100)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `downloads` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`          VARCHAR(180)    NOT NULL,
    `description`    VARCHAR(500)    NULL,
    `category`       VARCHAR(60)     NULL,
    `file_path`      VARCHAR(255)    NOT NULL,
    `file_name`      VARCHAR(180)    NOT NULL,
    `file_size`      INT UNSIGNED    NOT NULL,
    `mime_type`      VARCHAR(100)    NOT NULL,
    `audience`       ENUM('public','students','parents','staff') NOT NULL DEFAULT 'public',
    `download_count` INT UNSIGNED    NOT NULL DEFAULT 0,
    `is_active`      TINYINT(1)      NOT NULL DEFAULT 1,
    `uploaded_by`    BIGINT UNSIGNED NULL,
    `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_downloads_audience` (`audience`, `is_active`, `category`),
    CONSTRAINT `fk_downloads_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `menus` (
    `id`        TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`      VARCHAR(80) NOT NULL,
    `location`  ENUM('header','footer','quick_links','sidebar','mobile') NOT NULL,
    `is_active` TINYINT(1)  NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_menus_location` (`location`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `menu_items` (
    `id`           SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `menu_id`      TINYINT UNSIGNED  NOT NULL,
    `parent_id`    SMALLINT UNSIGNED NULL,
    `title`        VARCHAR(100)      NOT NULL,
    `type`         ENUM('page','post','category','custom','module') NOT NULL DEFAULT 'custom',
    `reference_id` BIGINT UNSIGNED   NULL,
    `url`          VARCHAR(255)      NULL,
    `icon`         VARCHAR(60)       NULL,
    `target`       ENUM('_self','_blank') NOT NULL DEFAULT '_self',
    `description`  VARCHAR(180)      NULL COMMENT 'shown in the mega menu',
    `sort_order`   SMALLINT          NOT NULL DEFAULT 0,
    `is_active`    TINYINT(1)        NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_menu_items_tree` (`menu_id`, `parent_id`, `sort_order`),
    CONSTRAINT `fk_menu_items_menu`   FOREIGN KEY (`menu_id`)   REFERENCES `menus` (`id`)      ON DELETE CASCADE,
    CONSTRAINT `fk_menu_items_parent` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- media — the shared library. `disk` is the seam for moving to S3-compatible
-- object storage later without touching a single call site.
CREATE TABLE `media` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`           CHAR(36)        NOT NULL,
    `filename`       VARCHAR(180)    NOT NULL,
    `original_name`  VARCHAR(255)    NOT NULL,
    `path`           VARCHAR(255)    NOT NULL,
    `thumbnail_path` VARCHAR(255)    NULL,
    `disk`           VARCHAR(30)     NOT NULL DEFAULT 'local',
    `mime_type`      VARCHAR(100)    NOT NULL,
    `extension`      VARCHAR(10)     NOT NULL,
    `size_bytes`     INT UNSIGNED    NOT NULL,
    `width`          SMALLINT UNSIGNED NULL,
    `height`         SMALLINT UNSIGNED NULL,
    `folder`         VARCHAR(120)    NOT NULL DEFAULT 'uploads',
    `alt_text`       VARCHAR(180)    NULL,
    `caption`        VARCHAR(255)    NULL,
    `entity_type`    VARCHAR(60)     NULL,
    `entity_id`      BIGINT UNSIGNED NULL,
    `uploaded_by`    BIGINT UNSIGNED NULL,
    `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_media_uuid` (`uuid`),
    KEY `idx_media_folder` (`folder`, `created_at`),
    KEY `idx_media_entity` (`entity_type`, `entity_id`),
    KEY `idx_media_mime`   (`mime_type`),
    CONSTRAINT `fk_media_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `settings` (
    `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_name`  VARCHAR(40)  NOT NULL,
    `key_name`    VARCHAR(80)  NOT NULL,
    `value`       TEXT         NULL,
    `type`        ENUM('text','textarea','number','boolean','image','file','json','select','color','email','url') NOT NULL DEFAULT 'text',
    `options`     JSON         NULL,
    `label`       VARCHAR(120) NOT NULL,
    `description` VARCHAR(255) NULL,
    `is_public`   TINYINT(1)   NOT NULL DEFAULT 0,
    `sort_order`  SMALLINT     NOT NULL DEFAULT 0,
    `updated_by`  BIGINT UNSIGNED NULL,
    `updated_at`  TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_settings_key` (`key_name`),
    KEY `idx_settings_group` (`group_name`, `sort_order`),
    CONSTRAINT `fk_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `faqs` (
    `id`         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `question`   VARCHAR(300) NOT NULL,
    `answer`     TEXT         NOT NULL,
    `category`   VARCHAR(60)  NULL,
    `sort_order` SMALLINT     NOT NULL DEFAULT 0,
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_faqs_category` (`category`, `is_active`, `sort_order`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `testimonials` (
    `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(120) NOT NULL,
    `designation` VARCHAR(120) NULL,
    `photo`       VARCHAR(255) NULL,
    `content`     TEXT         NOT NULL,
    `rating`      TINYINT UNSIGNED NULL,
    `type`        ENUM('student','parent','alumni','staff') NOT NULL DEFAULT 'parent',
    `is_featured` TINYINT(1)   NOT NULL DEFAULT 0,
    `status`      ENUM('draft','published') NOT NULL DEFAULT 'draft',
    `sort_order`  SMALLINT     NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_testimonials_status` (`status`, `is_featured`, `sort_order`),
    CONSTRAINT `chk_testimonials_rating` CHECK (`rating` IS NULL OR `rating` BETWEEN 1 AND 5)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `contact_messages` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(120)    NOT NULL,
    `email`         VARCHAR(191)    NOT NULL,
    `phone`         VARCHAR(20)     NULL,
    `subject`       VARCHAR(180)    NULL,
    `message`       TEXT            NOT NULL,
    `ip_address`    VARBINARY(16)   NULL,
    `user_agent`    VARCHAR(255)    NULL,
    `status`        ENUM('new','read','replied','spam','archived') NOT NULL DEFAULT 'new',
    `replied_by`    BIGINT UNSIGNED NULL,
    `replied_at`    TIMESTAMP       NULL DEFAULT NULL,
    `reply_message` TEXT            NULL,
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_contact_status` (`status`, `created_at`),
    KEY `idx_contact_email`  (`email`),
    CONSTRAINT `fk_contact_replied_by` FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- seo_redirects — a changed slug writes a 301 here automatically, so links from
-- Facebook posts and printed prospectuses keep working.
CREATE TABLE `seo_redirects` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `from_path`   VARCHAR(255) NOT NULL,
    `to_path`     VARCHAR(255) NOT NULL,
    `status_code` SMALLINT UNSIGNED NOT NULL DEFAULT 301,
    `hits`        INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_redirects_from` (`from_path`),
    CONSTRAINT `chk_redirects_code` CHECK (`status_code` IN (301, 302, 307, 308))
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- page_views — lightweight in-house analytics. Pruned monthly by cron; the
-- IP is stored hashed, never in the clear.
CREATE TABLE `page_views` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `url`         VARCHAR(255)    NOT NULL,
    `entity_type` VARCHAR(60)     NULL,
    `entity_id`   BIGINT UNSIGNED NULL,
    `ip_hash`     CHAR(64)        NULL,
    `referrer`    VARCHAR(255)    NULL,
    `user_agent`  VARCHAR(255)    NULL,
    `viewed_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_page_views_entity` (`entity_type`, `entity_id`, `viewed_at`),
    KEY `idx_page_views_date`   (`viewed_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0008_cms.sql', 1);
