-- =============================================================================
-- 0035_pages_body_content.sql · Adds the About page's intro paragraph +
-- intro photo to the Pages admin screen — currently under "Homepage Builder
-- → About" as `settings.about_body`/`about_image`, which is ALSO the source
-- for the homepage's own "About Our Campus" Welcome section (same sharing
-- situation as about_heading/about_subheading, resolved in 0034 the same
-- way): the About PAGE gets its own copy here; the homepage Welcome section
-- keeps reading the original settings rows, now relabelled to make that
-- clear. `pages.content` (existing CMS column) holds the paragraph as plain
-- text — this is not a rich-text/HTML field, so it stays escaped on render
-- exactly like about_body always was. `content_image` is new: `og_image`
-- already exists on this table but means something else (social-share
-- preview), so reusing it here would be a landmine for whoever wires that
-- up later.
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `pages`
    ADD COLUMN `content_image` VARCHAR(255) NULL AFTER `content`;

UPDATE `pages` p
  JOIN `settings` sb ON sb.key_name = 'about_body'
  JOIN `settings` si ON si.key_name = 'about_image'
   SET p.`content` = NULLIF(sb.value, ''),
       p.`content_image` = NULLIF(si.value, '')
 WHERE p.slug = 'about';

UPDATE `settings` SET `label` = 'Homepage Welcome Body Text', `description` = 'Shown in the "About Our Campus" section on the homepage. The About page itself is edited separately from Pages.' WHERE `key_name` = 'about_body';
UPDATE `settings` SET `label` = 'Homepage Welcome Image', `description` = 'Shown in the "About Our Campus" section on the homepage. The About page itself is edited separately from Pages.' WHERE `key_name` = 'about_image';

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0035_pages_body_content.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
