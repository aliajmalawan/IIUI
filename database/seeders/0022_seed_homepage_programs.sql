-- =============================================================================
-- 0022_seed_homepage_programs.sql  ·  "Academic Programs" starter cards for
-- the homepage_programs table (0021_homepage_programs.sql).
-- -----------------------------------------------------------------------------
-- One-time seed, not an upsert — same rationale as 0020_seed_homepage_features:
-- no natural unique key on `title`, idempotency comes from the `migrations`
-- guard below.
-- =============================================================================
USE `iiui_mardan`;

INSERT INTO `homepage_programs` (`title`, `grade_range`, `description`, `icon`, `sort_order`, `is_active`) VALUES
    ('Play Group',   'Ages 3 - 4',    'A nurturing start built on play-based learning and early social skills.', 'stars',            1, 1),
    ('Primary',      'Grades 1 - 5',  'Building strong foundations in literacy, numeracy, and Islamic studies.', 'backpack2',        2, 1),
    ('Middle',       'Grades 6 - 8',  'Expanding subject depth while developing independent study habits.',     'journal-bookmark', 3, 1),
    ('High School',  'Grades 9 - 10', 'Matriculation-focused preparation across science and arts groups.',      'mortarboard',      4, 1),
    ('College',      'Grades 11 - 12','Intermediate-level programs preparing students for university studies.', 'bank',             5, 1);

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0022_seed_homepage_programs.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
