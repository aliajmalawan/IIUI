-- =============================================================================
-- 0017_seed_homepage_settings.sql  ·  About Section + CTA fields, stored as
-- 'homepage'-group rows in the existing generic `settings` table (reuses the
-- Website Settings module's editor built in Step 6.1 — no new admin pattern
-- needed for single-instance content blocks).
-- =============================================================================
USE `iiui_mardan`;

INSERT INTO `settings` (`group_name`, `key_name`, `value`, `type`, `label`, `description`, `is_public`, `sort_order`) VALUES
    ('homepage', 'about_heading',    'About Our Campus', 'text', 'Heading', NULL, 1, 1),
    ('homepage', 'about_subheading', 'A legacy of academic excellence', 'text', 'Subheading', NULL, 1, 2),
    ('homepage', 'about_body',
        'IIUI Schools & College – Mardan Campus offers quality education from primary through intermediate level, combining a modern curriculum with strong Islamic values.',
        'textarea', 'Body Text', 'Shown in the About section on the homepage.', 1, 3),
    ('homepage', 'about_image', NULL, 'image', 'About Section Image', NULL, 1, 4),
    ('homepage', 'cta_heading', 'Admissions Open for 2026-2027', 'text', 'CTA Heading', NULL, 1, 5),
    ('homepage', 'cta_body',
        'Apply now and secure your place for the upcoming academic session.',
        'textarea', 'CTA Body Text', NULL, 1, 6),
    ('homepage', 'cta_button_text', 'Apply Now', 'text', 'CTA Button Text', NULL, 1, 7),
    -- type=text (not 'url'): the button legitimately points to an internal
    -- relative path most of the time, which fails a strict URL validator.
    ('homepage', 'cta_button_url', '/admissions/apply', 'text', 'CTA Button URL',
        'A relative path (/admissions/apply) or a full https:// URL.', 1, 8)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0017_seed_homepage_settings.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
