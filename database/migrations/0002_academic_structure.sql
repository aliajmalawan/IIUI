-- =============================================================================
-- 0002_academic_structure.sql  ·  Domain B — Sessions, classes, subjects, grading
-- -----------------------------------------------------------------------------
-- Foreign keys pointing at `staff` (class_teacher_id, teacher_id) are added in
-- 0004 once that table exists — see the deferred ALTER block there.
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- academic_sessions — the spine of the whole schema.
-- `is_current_key` is a STORED generated column that is 1 when the session is
-- current and NULL otherwise. Because NULLs never collide in a UNIQUE index,
-- this makes "exactly one current session" a database-level guarantee rather
-- than an application convention.
-- -----------------------------------------------------------------------------
CREATE TABLE `academic_sessions` (
    `id`             SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`           VARCHAR(20)  NOT NULL COMMENT 'e.g. 2026-2027',
    `code`           VARCHAR(12)  NOT NULL,
    `start_date`     DATE         NOT NULL,
    `end_date`       DATE         NOT NULL,
    `is_current`     TINYINT(1)   NOT NULL DEFAULT 0,
    `is_current_key` TINYINT(1)   GENERATED ALWAYS AS (CASE WHEN `is_current` = 1 THEN 1 ELSE NULL END) STORED,
    `status`         ENUM('upcoming','active','closed') NOT NULL DEFAULT 'upcoming',
    `closed_at`      TIMESTAMP    NULL DEFAULT NULL,
    `closed_by`      BIGINT UNSIGNED NULL,
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_academic_sessions_name`    (`name`),
    UNIQUE KEY `uq_academic_sessions_code`    (`code`),
    UNIQUE KEY `uq_academic_sessions_current` (`is_current_key`),
    KEY `idx_academic_sessions_dates` (`start_date`, `end_date`),
    CONSTRAINT `chk_academic_sessions_dates` CHECK (`end_date` > `start_date`),
    CONSTRAINT `fk_academic_sessions_closed_by` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- campuses — one row today; the seam for multi-campus expansion.
CREATE TABLE `campuses` (
    `id`         TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(120) NOT NULL,
    `code`       VARCHAR(10)  NOT NULL,
    `address`    VARCHAR(255) NULL,
    `city`       VARCHAR(60)  NULL,
    `phone`      VARCHAR(20)  NULL,
    `email`      VARCHAR(191) NULL,
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_campuses_code` (`code`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- school_classes — named `school_classes`, never `classes`: `class` collides with
-- reserved words in ORMs and reads ambiguously in SQL.
CREATE TABLE `school_classes` (
    `id`            SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `campus_id`     TINYINT UNSIGNED  NOT NULL DEFAULT 1,
    `name`          VARCHAR(40)  NOT NULL COMMENT 'e.g. Class 9',
    `short_name`    VARCHAR(12)  NULL,
    `numeric_level` TINYINT UNSIGNED NOT NULL COMMENT 'ordering + promotion path',
    `wing`          ENUM('primary','middle','secondary','college') NOT NULL DEFAULT 'secondary',
    `sort_order`    SMALLINT     NOT NULL DEFAULT 0,
    `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_school_classes_campus_name` (`campus_id`, `name`),
    KEY `idx_school_classes_level` (`numeric_level`),
    KEY `idx_school_classes_wing`  (`wing`, `is_active`),
    CONSTRAINT `fk_school_classes_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- streams — college side: Pre-Medical, Pre-Engineering, ICS, Commerce, Arts
CREATE TABLE `streams` (
    `id`                 TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`               VARCHAR(60) NOT NULL,
    `code`               VARCHAR(12) NOT NULL,
    `applies_from_level` TINYINT UNSIGNED NOT NULL DEFAULT 11,
    `sort_order`         SMALLINT   NOT NULL DEFAULT 0,
    `is_active`          TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_streams_code` (`code`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `class_streams` (
    `class_id`  SMALLINT UNSIGNED NOT NULL,
    `stream_id` TINYINT UNSIGNED  NOT NULL,
    PRIMARY KEY (`class_id`, `stream_id`),
    KEY `idx_class_streams_stream` (`stream_id`),
    CONSTRAINT `fk_class_streams_class`  FOREIGN KEY (`class_id`)  REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_class_streams_stream` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`)        ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- sections — scoped per session, because 9-A in 2026 is a different cohort than 9-A in 2027.
CREATE TABLE `sections` (
    `id`                  SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `class_id`            SMALLINT UNSIGNED NOT NULL,
    `academic_session_id` SMALLINT UNSIGNED NOT NULL,
    `name`                VARCHAR(20)  NOT NULL COMMENT 'e.g. A',
    `capacity`            SMALLINT UNSIGNED NOT NULL DEFAULT 40,
    `room_no`             VARCHAR(20)  NULL,
    `class_teacher_id`    BIGINT UNSIGNED NULL,
    `sort_order`          SMALLINT     NOT NULL DEFAULT 0,
    `is_active`           TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_sections_class_session_name` (`class_id`, `academic_session_id`, `name`),
    KEY `idx_sections_session_active` (`academic_session_id`, `is_active`),
    KEY `idx_sections_teacher`        (`class_teacher_id`),
    CONSTRAINT `chk_sections_capacity` CHECK (`capacity` > 0),
    CONSTRAINT `fk_sections_class`   FOREIGN KEY (`class_id`)            REFERENCES `school_classes` (`id`)    ON DELETE RESTRICT,
    CONSTRAINT `fk_sections_session` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `subjects` (
    `id`         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`       VARCHAR(20)  NOT NULL,
    `name`       VARCHAR(100) NOT NULL,
    `name_ur`    VARCHAR(100) NULL,
    `type`       ENUM('theory','practical','both') NOT NULL DEFAULT 'theory',
    `is_elective` TINYINT(1)  NOT NULL DEFAULT 0,
    `sort_order` SMALLINT     NOT NULL DEFAULT 0,
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_subjects_code` (`code`),
    KEY `idx_subjects_active` (`is_active`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- class_subjects — the curriculum: which subject is taught in which class,
-- for which stream, in which session, by whom, and for how many marks.
CREATE TABLE `class_subjects` (
    `id`                  INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `class_id`            SMALLINT UNSIGNED NOT NULL,
    `stream_id`           TINYINT UNSIGNED  NULL,
    `subject_id`          SMALLINT UNSIGNED NOT NULL,
    `academic_session_id` SMALLINT UNSIGNED NOT NULL,
    `teacher_id`          BIGINT UNSIGNED   NULL,
    `total_marks`         SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    `passing_marks`       SMALLINT UNSIGNED NOT NULL DEFAULT 33,
    `practical_marks`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `is_optional`         TINYINT(1)        NOT NULL DEFAULT 0,
    `sort_order`          SMALLINT          NOT NULL DEFAULT 0,
    `created_at`          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_class_subjects_unique` (`academic_session_id`, `class_id`, `stream_id`, `subject_id`),
    KEY `idx_class_subjects_subject` (`subject_id`),
    KEY `idx_class_subjects_teacher` (`teacher_id`, `academic_session_id`),
    CONSTRAINT `chk_class_subjects_marks` CHECK (`passing_marks` <= `total_marks` AND `total_marks` > 0),
    CONSTRAINT `fk_class_subjects_class`   FOREIGN KEY (`class_id`)            REFERENCES `school_classes` (`id`)    ON DELETE CASCADE,
    CONSTRAINT `fk_class_subjects_stream`  FOREIGN KEY (`stream_id`)           REFERENCES `streams` (`id`)           ON DELETE CASCADE,
    CONSTRAINT `fk_class_subjects_subject` FOREIGN KEY (`subject_id`)          REFERENCES `subjects` (`id`)          ON DELETE RESTRICT,
    CONSTRAINT `fk_class_subjects_session` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- grade_scales — grading is DATA, not code, so it can change per session
-- without a deployment.
CREATE TABLE `grade_scales` (
    `id`                  SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `academic_session_id` SMALLINT UNSIGNED NOT NULL,
    `grade`               VARCHAR(5)    NOT NULL,
    `min_percentage`      DECIMAL(5,2)  NOT NULL,
    `max_percentage`      DECIMAL(5,2)  NOT NULL,
    `grade_point`         DECIMAL(3,2)  NOT NULL DEFAULT 0.00,
    `remarks`             VARCHAR(60)   NULL,
    `is_fail`             TINYINT(1)    NOT NULL DEFAULT 0,
    `sort_order`          SMALLINT      NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_grade_scales_session_grade` (`academic_session_id`, `grade`),
    KEY `idx_grade_scales_range` (`academic_session_id`, `min_percentage`, `max_percentage`),
    CONSTRAINT `chk_grade_scales_range` CHECK (`max_percentage` >= `min_percentage`),
    CONSTRAINT `fk_grade_scales_session` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `holidays` (
    `id`                  SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `academic_session_id` SMALLINT UNSIGNED NOT NULL,
    `title`               VARCHAR(120) NOT NULL,
    `date_from`           DATE         NOT NULL,
    `date_to`             DATE         NOT NULL,
    `type`                ENUM('public','religious','vacation','emergency','weekend') NOT NULL DEFAULT 'public',
    `applies_to`          ENUM('all','students','staff') NOT NULL DEFAULT 'all',
    `description`         VARCHAR(255) NULL,
    `created_by`          BIGINT UNSIGNED NULL,
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_holidays_session_range` (`academic_session_id`, `date_from`, `date_to`),
    CONSTRAINT `chk_holidays_range` CHECK (`date_to` >= `date_from`),
    CONSTRAINT `fk_holidays_session`    FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_holidays_created_by` FOREIGN KEY (`created_by`)          REFERENCES `users` (`id`)             ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `timetables` (
    `id`                  INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `academic_session_id` SMALLINT UNSIGNED NOT NULL,
    `section_id`          SMALLINT UNSIGNED NOT NULL,
    `subject_id`          SMALLINT UNSIGNED NOT NULL,
    `teacher_id`          BIGINT UNSIGNED   NULL,
    `day_of_week`         TINYINT UNSIGNED  NOT NULL COMMENT '1=Mon … 7=Sun',
    `period_no`           TINYINT UNSIGNED  NOT NULL,
    `start_time`          TIME              NOT NULL,
    `end_time`            TIME              NOT NULL,
    `room_no`             VARCHAR(20)       NULL,
    `created_at`          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_timetables_slot` (`academic_session_id`, `section_id`, `day_of_week`, `period_no`),
    KEY `idx_timetables_teacher_slot` (`teacher_id`, `day_of_week`, `period_no`),
    CONSTRAINT `chk_timetables_day`   CHECK (`day_of_week` BETWEEN 1 AND 7),
    CONSTRAINT `chk_timetables_times` CHECK (`end_time` > `start_time`),
    CONSTRAINT `fk_timetables_session` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_timetables_section` FOREIGN KEY (`section_id`)          REFERENCES `sections` (`id`)          ON DELETE CASCADE,
    CONSTRAINT `fk_timetables_subject` FOREIGN KEY (`subject_id`)          REFERENCES `subjects` (`id`)          ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0002_academic_structure.sql', 1);
