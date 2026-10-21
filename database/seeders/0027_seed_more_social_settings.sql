-- =============================================================================
-- 0027_seed_more_social_settings.sql  ·  Extends the existing 'social'
-- settings group (Facebook/YouTube only, Step 6.1) with three more common
-- platforms for the footer's Social Icons row. Values are left blank —
-- these are real admin-entered links, not placeholder URLs — the footer
-- already hides an icon whenever its URL is empty.
-- =============================================================================
USE `iiui_mardan`;

INSERT INTO `settings` (`group_name`, `key_name`, `value`, `type`, `label`, `description`, `is_public`, `sort_order`) VALUES
    ('social', 'instagram_url', NULL, 'url', 'Instagram URL', NULL, 1, 3),
    ('social', 'twitter_url',   NULL, 'url', 'Twitter / X URL', NULL, 1, 4),
    ('social', 'linkedin_url',  NULL, 'url', 'LinkedIn URL', NULL, 1, 5)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0027_seed_more_social_settings.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
