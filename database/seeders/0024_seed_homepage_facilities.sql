-- =============================================================================
-- 0024_seed_homepage_facilities.sql  ·  "Facilities" starter cards for the
-- homepage_facilities table (0023_homepage_facilities.sql). No `image` value
-- is seeded — admins upload real campus photos via the admin UI; each card
-- falls back to its `icon` until then.
-- -----------------------------------------------------------------------------
-- One-time seed, not an upsert — same rationale as 0020/0022: no natural
-- unique key on `title`, idempotency comes from the `migrations` guard below.
-- =============================================================================
USE `iiui_mardan`;

INSERT INTO `homepage_facilities` (`title`, `description`, `icon`, `sort_order`, `is_active`) VALUES
    ('Library',       'A quiet, well-stocked space for research and reading.',        'book-half', 1, 1),
    ('Computer Lab',  'Modern workstations for hands-on computing practice.',         'pc-display', 2, 1),
    ('Science Lab',   'Equipped labs for practical physics, chemistry, and biology.', 'flask',     3, 1),
    ('Transport',     'Safe, supervised transport covering major routes.',            'bus-front', 4, 1),
    ('Mosque',        'An on-campus mosque supporting daily prayer and Islamic studies.', 'building', 5, 1),
    ('Play Ground',   'Open space for sports, physical activity, and recreation.',    'balloon',   6, 1),
    ('Cafeteria',     'A clean, hygienic cafeteria serving healthy meals.',           'cup-hot',   7, 1);

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0024_seed_homepage_facilities.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
