-- =============================================================================
-- 0020_seed_homepage_features.sql  ·  "Why Choose Us" starter cards for the
-- homepage_features table (0019_homepage_features.sql).
-- -----------------------------------------------------------------------------
-- One-time seed, not an upsert: `homepage_features` has no natural unique key
-- on `title` (admins may legitimately edit or duplicate a card's wording), so
-- idempotency here comes from the `migrations` guard below, same as every
-- other seeder in this project — re-running this file by hand would insert
-- a second batch of rows.
-- =============================================================================
USE `iiui_mardan`;

INSERT INTO `homepage_features` (`title`, `description`, `icon`, `sort_order`, `is_active`) VALUES
    ('Qualified Faculty',    'Experienced, dedicated teachers committed to every student\'s growth.', 'person-badge', 1, 1),
    ('Islamic Environment',  'A campus culture rooted in Islamic values and character.',             'moon-stars',   2, 1),
    ('Modern Education',     'A contemporary curriculum built for today\'s learners.',               'mortarboard',  3, 1),
    ('Computer Labs',        'Fully equipped labs for hands-on computing skills.',                    'pc-display',   4, 1),
    ('Science Labs',         'Well-resourced labs for practical scientific learning.',                'flask',        5, 1),
    ('Smart Classrooms',     'Technology-enabled classrooms for interactive lessons.',                'display',      6, 1),
    ('Sports',                'Facilities and activities for physical development and teamwork.',      'trophy',       7, 1),
    ('Character Building',   'Guidance and mentorship that shapes strong personal values.',           'shield-check', 8, 1);

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0020_seed_homepage_features.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
