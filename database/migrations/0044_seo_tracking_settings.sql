-- SEO + tracking settings surfaced in Admin > Settings > SEO.
INSERT INTO `settings` (`group_name`, `key_name`, `value`, `type`, `label`, `description`, `is_public`, `sort_order`) VALUES
    ('seo', 'meta_keywords',            '', 'text',    'Default Meta Keywords',      'Comma-separated fallback keywords for pages that have none of their own.', 1, 2),
    ('seo', 'og_image',                 '', 'image',   'Default Social Share Image', 'Used for link previews (WhatsApp, Facebook) when a page has no image of its own.', 1, 3),
    ('seo', 'google_analytics_id',      '', 'text',    'Google Analytics ID',        'GA4 measurement ID, e.g. G-XXXXXXXXXX. Leave empty to disable.', 1, 4),
    ('seo', 'google_site_verification', '', 'text',    'Google Search Console Code', 'Only the content value of the google-site-verification meta tag.', 1, 5),
    ('seo', 'robots_noindex',           '0','boolean', 'Hide Site From Search Engines', 'Turn on while the site is still in development.', 1, 6)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);
