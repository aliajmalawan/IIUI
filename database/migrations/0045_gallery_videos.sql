-- Gallery items can now be YouTube videos as well as photos. Only the
-- 11-character video ID is stored (validated in PHP), never a free-form URL.
ALTER TABLE `gallery_images`
    ADD COLUMN `media_type` ENUM('image','video') NOT NULL DEFAULT 'image' AFTER `gallery_id`,
    ADD COLUMN `video_id` VARCHAR(16) NULL DEFAULT NULL AFTER `image_path`;
