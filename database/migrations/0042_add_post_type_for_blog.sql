-- =============================================================================
-- 0042_add_post_type_for_blog.sql · Adds a "Blog" content stream that reuses
-- the existing `posts` table/News infrastructure (PostRepository, TinyMCE
-- editor, image upload, scheduling, SEO fields) rather than duplicating the
-- schema — a `post_type` discriminator is the only structural change.
--
-- Blog posts deliberately do NOT use `category_id` (post_categories is a
-- News-specific taxonomy — "Announcements", "Achievements", "Campus News" —
-- that doesn't fit blog content); Admin\BlogController always leaves it
-- NULL. Every existing row defaults to 'news', so current News behavior is
-- completely unchanged by this migration.
-- =============================================================================
USE `iiui_mardan`;

ALTER TABLE `posts`
    ADD COLUMN `post_type` ENUM('news','blog') NOT NULL DEFAULT 'news' AFTER `category_id`;

ALTER TABLE `posts` ADD INDEX `idx_posts_type_status` (`post_type`, `status`, `published_at`);

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0042_add_post_type_for_blog.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
