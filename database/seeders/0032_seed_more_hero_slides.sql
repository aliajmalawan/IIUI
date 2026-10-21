-- =============================================================================
-- 0032_seed_more_hero_slides.sql  ·  Adds two more Hero Slider slides
-- (Students, Library/Books themes) so the hero actually rotates instead of
-- showing a single static slide — matching the multi-theme placeholder set
-- discussed with the user (0030 added the first, Islamic-architecture,
-- slide). Same sourcing rule as before: safe, non-identifying stock photos
-- (Pexels license, free for commercial use, no attribution required), no
-- other institution's signage/landmarks visible. Placeholders until real
-- IIUI Mardan campus photos are uploaded via Admin -> Homepage Builder ->
-- Hero Slider, which will replace these.
-- One-time seed, not an upsert — sliders has no natural unique key.
-- =============================================================================
USE `iiui_mardan`;

INSERT INTO `sliders`
  (`title`, `subtitle`, `description`, `image`, `button_text`, `button_url`, `text_position`, `overlay_opacity`, `sort_order`, `is_active`)
VALUES
  ('Learning Together, Growing Together',
   'Our Students',
   'A supportive, collaborative environment where every student is encouraged to reach their full potential.',
   '/uploads/sliders/cf0c76df423fb5e40caa1b5c945945df.jpg',
   'Explore Academics', '/academics', 'left', 70, 1, 1),
  ('A Love for Learning and Books',
   'Our Library',
   'Well-stocked reading spaces that nurture curiosity from the earliest years onward.',
   '/uploads/sliders/ca3c39ba0840e816ae4c74433ee06ffa.jpg',
   'Learn More', '/about', 'left', 70, 2, 1);

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0032_seed_more_hero_slides.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
