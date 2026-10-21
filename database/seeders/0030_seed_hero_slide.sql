-- =============================================================================
-- 0030_seed_hero_slide.sql  ·  One real hero slide so the homepage stops
-- falling back to the plain gradient/shapes design (`.home-hero`, shown only
-- when the `sliders` table is empty, which it was). Image is a stock
-- Islamic-architecture photo (Pexels license, free for commercial use, no
-- attribution required) — a placeholder until a real IIUI Mardan campus
-- photo is uploaded via Admin -> Homepage Builder -> Hero Slider, which
-- will replace this automatically.
-- One-time seed, not an upsert — sliders has no natural unique key to key
-- off of, so this simply inserts a starting row.
-- =============================================================================
USE `iiui_mardan`;

INSERT INTO `sliders`
  (`title`, `subtitle`, `description`, `image`, `button_text`, `button_url`, `text_position`, `overlay_opacity`, `sort_order`, `is_active`)
VALUES
  ('Schools & College, Mardan Campus',
   'International Islamic University Islamabad',
   'Nurturing confident, capable learners through modern education and strong Islamic values.',
   '/uploads/sliders/96a5a6eaae1b91021ba46abfb86d281a.jpg',
   'Apply Online', '/admissions', 'left', 70, 0, 1);

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0030_seed_hero_slide.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
