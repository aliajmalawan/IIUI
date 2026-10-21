-- =============================================================================
-- 0015_seed_theme_settings.sql  ·  Additions needed by the Website Settings
-- admin module (Phase 6, Step 6.1): a favicon setting, a site-wide default-
-- appearance group, and dropdown option lists for the two 'select' settings
-- that didn't have any populated yet.
-- =============================================================================
USE `iiui_mardan`;

INSERT INTO `settings` (`group_name`, `key_name`, `value`, `type`, `label`, `description`, `is_public`, `sort_order`) VALUES
    ('general', 'site_favicon', NULL, 'image', 'Favicon', 'Small icon shown in the browser tab. Square image recommended.', 1, 4),
    ('theme', 'default_site_theme', 'system', 'select', 'Default Site Appearance',
     'The light/dark mode a first-time visitor to the public website sees. Logged-in staff can still override this from their own Appearance preference.', 1, 1)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);

UPDATE `settings`
   SET `options` = JSON_ARRAY(
           JSON_OBJECT('value', 'Asia/Karachi', 'label', 'Asia/Karachi (PKT)'),
           JSON_OBJECT('value', 'UTC', 'label', 'UTC'),
           JSON_OBJECT('value', 'Asia/Dubai', 'label', 'Asia/Dubai (GST)')
       )
 WHERE `key_name` = 'timezone';

UPDATE `settings`
   SET `options` = JSON_ARRAY(
           JSON_OBJECT('value', 'light',  'label', 'Light'),
           JSON_OBJECT('value', 'dark',   'label', 'Dark'),
           JSON_OBJECT('value', 'system', 'label', 'Match Visitor Device')
       )
 WHERE `key_name` = 'default_site_theme';

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0015_seed_theme_settings.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
