-- =============================================================================
-- 0031_fix_site_logo_transparency.sql  ·  The uploaded site_logo PNG had a
-- solid opaque black background baked in (not transparent) — invisible
-- against a dark canvas, but rendered as an ugly black rectangle once the
-- homepage topbar's logo was enlarged onto a white background. Regenerated
-- via chroma-key (near-black pixels -> transparent) into a new file rather
-- than overwriting the original upload, which is left untouched on disk at
-- /uploads/branding/0adc69e07c193684a37db43346d0a16a.png in case a revert
-- or a proper re-export is needed.
-- =============================================================================
USE `iiui_mardan`;

UPDATE `settings`
SET `value` = '/uploads/branding/78306d6d3386e52e001918a01c669e6f.png'
WHERE `key_name` = 'site_logo';

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0031_fix_site_logo_transparency.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
