-- =============================================================================
-- 0006_examination.sql  ·  Domain F — Exam terms, marks, grading, results
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `exam_terms` (
    `id`                  SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `academic_session_id` SMALLINT UNSIGNED NOT NULL,
    `name`                VARCHAR(80)  NOT NULL COMMENT 'e.g. First Term',
    `type`                ENUM('monthly','mid','final','test','pre_board') NOT NULL DEFAULT 'mid',
    `start_date`          DATE         NOT NULL,
    `end_date`            DATE         NOT NULL,
    `weightage`           DECIMAL(5,2) NOT NULL DEFAULT 100.00 COMMENT 'contribution to the final result',
    `is_result_published` TINYINT(1)   NOT NULL DEFAULT 0,
    `published_at`        TIMESTAMP    NULL DEFAULT NULL,
    `published_by`        BIGINT UNSIGNED NULL,
    `status`              ENUM('planned','ongoing','marking','completed','cancelled') NOT NULL DEFAULT 'planned',
    `sort_order`          SMALLINT     NOT NULL DEFAULT 0,
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_exam_terms_session_name` (`academic_session_id`, `name`),
    KEY `idx_exam_terms_session_status` (`academic_session_id`, `status`),
    CONSTRAINT `chk_exam_terms_dates`     CHECK (`end_date` >= `start_date`),
    CONSTRAINT `chk_exam_terms_weightage` CHECK (`weightage` > 0 AND `weightage` <= 100),
    CONSTRAINT `fk_exam_terms_session`      FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_exam_terms_published_by` FOREIGN KEY (`published_by`)        REFERENCES `users` (`id`)             ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `exams` (
    `id`               INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `exam_term_id`     SMALLINT UNSIGNED NOT NULL,
    `class_id`         SMALLINT UNSIGNED NOT NULL,
    `stream_id`        TINYINT UNSIGNED  NULL,
    `subject_id`       SMALLINT UNSIGNED NOT NULL,
    `exam_date`        DATE              NOT NULL,
    `start_time`       TIME              NULL,
    `end_time`         TIME              NULL,
    `total_marks`      SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    `passing_marks`    SMALLINT UNSIGNED NOT NULL DEFAULT 33,
    `practical_total`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `practical_passing` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `room_no`          VARCHAR(20)       NULL,
    `invigilator_id`   BIGINT UNSIGNED   NULL,
    `is_marks_locked`  TINYINT(1)        NOT NULL DEFAULT 0,
    `created_by`       BIGINT UNSIGNED   NULL,
    `created_at`       TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_exams_term_class_subject` (`exam_term_id`, `class_id`, `stream_id`, `subject_id`),
    KEY `idx_exams_date`    (`exam_date`),
    KEY `idx_exams_class`   (`class_id`, `exam_term_id`),
    KEY `idx_exams_subject` (`subject_id`),
    CONSTRAINT `chk_exams_marks` CHECK (`passing_marks` <= `total_marks` AND `total_marks` > 0),
    CONSTRAINT `chk_exams_times` CHECK (`end_time` IS NULL OR `start_time` IS NULL OR `end_time` > `start_time`),
    CONSTRAINT `fk_exams_term`        FOREIGN KEY (`exam_term_id`)  REFERENCES `exam_terms` (`id`)     ON DELETE CASCADE,
    CONSTRAINT `fk_exams_class`       FOREIGN KEY (`class_id`)      REFERENCES `school_classes` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_exams_stream`      FOREIGN KEY (`stream_id`)     REFERENCES `streams` (`id`)        ON DELETE SET NULL,
    CONSTRAINT `fk_exams_subject`     FOREIGN KEY (`subject_id`)    REFERENCES `subjects` (`id`)       ON DELETE RESTRICT,
    CONSTRAINT `fk_exams_invigilator` FOREIGN KEY (`invigilator_id`) REFERENCES `staff` (`id`)         ON DELETE SET NULL,
    CONSTRAINT `fk_exams_created_by`  FOREIGN KEY (`created_by`)    REFERENCES `users` (`id`)          ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- marks — four-eyes control lives in these columns:
--   entered_by  → the subject teacher records the mark
--   verified_by → the exam controller confirms it
--   is_locked   → the row freezes; further edits need a permission AND write
--                 an audit_logs entry (trigger in 0013).
-- -----------------------------------------------------------------------------
CREATE TABLE `marks` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `exam_id`            INT UNSIGNED    NOT NULL,
    `student_id`         BIGINT UNSIGNED NOT NULL,
    `enrollment_id`      BIGINT UNSIGNED NOT NULL,
    `obtained_marks`     DECIMAL(6,2)    NULL,
    `practical_obtained` DECIMAL(6,2)    NULL,
    `total_obtained`     DECIMAL(6,2)    GENERATED ALWAYS AS (COALESCE(`obtained_marks`,0) + COALESCE(`practical_obtained`,0)) STORED,
    `is_absent`          TINYINT(1)      NOT NULL DEFAULT 0,
    `grade`              VARCHAR(5)      NULL,
    `grade_point`        DECIMAL(3,2)    NULL,
    `remarks`            VARCHAR(160)    NULL,
    `entered_by`         BIGINT UNSIGNED NULL,
    `entered_at`         TIMESTAMP       NULL DEFAULT NULL,
    `verified_by`        BIGINT UNSIGNED NULL,
    `verified_at`        TIMESTAMP       NULL DEFAULT NULL,
    `is_locked`          TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at`         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_marks_exam_student` (`exam_id`, `student_id`),
    KEY `idx_marks_student`    (`student_id`),
    KEY `idx_marks_enrollment` (`enrollment_id`),
    KEY `idx_marks_unverified` (`exam_id`, `verified_at`),
    CONSTRAINT `chk_marks_non_negative` CHECK (
        (`obtained_marks`     IS NULL OR `obtained_marks`     >= 0) AND
        (`practical_obtained` IS NULL OR `practical_obtained` >= 0)
    ),
    CONSTRAINT `fk_marks_exam`        FOREIGN KEY (`exam_id`)       REFERENCES `exams` (`id`)               ON DELETE CASCADE,
    CONSTRAINT `fk_marks_student`     FOREIGN KEY (`student_id`)    REFERENCES `students` (`id`)            ON DELETE RESTRICT,
    CONSTRAINT `fk_marks_enrollment`  FOREIGN KEY (`enrollment_id`) REFERENCES `student_enrollments` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_marks_entered_by`  FOREIGN KEY (`entered_by`)    REFERENCES `users` (`id`)               ON DELETE SET NULL,
    CONSTRAINT `fk_marks_verified_by` FOREIGN KEY (`verified_by`)   REFERENCES `users` (`id`)               ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- result_summaries — computed per student per term by sp_compile_results.
-- Positions are stored, not calculated on read: a result card must show the
-- same rank in 2029 as it did the day it was published, even if a mark is
-- later corrected for a different student.
CREATE TABLE `result_summaries` (
    `id`                  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `student_id`          BIGINT UNSIGNED   NOT NULL,
    `enrollment_id`       BIGINT UNSIGNED   NOT NULL,
    `exam_term_id`        SMALLINT UNSIGNED NOT NULL,
    `academic_session_id` SMALLINT UNSIGNED NOT NULL,
    `class_id`            SMALLINT UNSIGNED NOT NULL,
    `section_id`          SMALLINT UNSIGNED NOT NULL,
    `total_marks`         DECIMAL(8,2)      NOT NULL DEFAULT 0.00,
    `obtained_marks`      DECIMAL(8,2)      NOT NULL DEFAULT 0.00,
    `percentage`          DECIMAL(5,2)      NOT NULL DEFAULT 0.00,
    `grade`               VARCHAR(5)        NULL,
    `grade_point`         DECIMAL(3,2)      NULL,
    `position_in_section` SMALLINT UNSIGNED NULL,
    `position_in_class`   SMALLINT UNSIGNED NULL,
    `subjects_total`      TINYINT UNSIGNED  NOT NULL DEFAULT 0,
    `subjects_failed`     TINYINT UNSIGNED  NOT NULL DEFAULT 0,
    `result_status`       ENUM('pass','fail','absent','pending','withheld') NOT NULL DEFAULT 'pending',
    `remarks`             VARCHAR(160)      NULL,
    `is_published`        TINYINT(1)        NOT NULL DEFAULT 0,
    `published_at`        TIMESTAMP         NULL DEFAULT NULL,
    `generated_at`        TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_results_student_term` (`student_id`, `exam_term_id`),
    -- Drives merit lists and position calculation
    KEY `idx_results_merit` (`exam_term_id`, `class_id`, `percentage`),
    KEY `idx_results_section` (`exam_term_id`, `section_id`, `percentage`),
    KEY `idx_results_published` (`is_published`, `exam_term_id`),
    CONSTRAINT `fk_results_student`    FOREIGN KEY (`student_id`)          REFERENCES `students` (`id`)            ON DELETE RESTRICT,
    CONSTRAINT `fk_results_enrollment` FOREIGN KEY (`enrollment_id`)       REFERENCES `student_enrollments` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_results_term`       FOREIGN KEY (`exam_term_id`)        REFERENCES `exam_terms` (`id`)          ON DELETE CASCADE,
    CONSTRAINT `fk_results_session`    FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`)   ON DELETE RESTRICT,
    CONSTRAINT `fk_results_class`      FOREIGN KEY (`class_id`)            REFERENCES `school_classes` (`id`)      ON DELETE RESTRICT,
    CONSTRAINT `fk_results_section`    FOREIGN KEY (`section_id`)          REFERENCES `sections` (`id`)            ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `exam_attendance` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `exam_id`    INT UNSIGNED    NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `is_present` TINYINT(1)      NOT NULL DEFAULT 1,
    `remarks`    VARCHAR(160)    NULL,
    `marked_by`  BIGINT UNSIGNED NULL,
    `marked_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_exam_attendance` (`exam_id`, `student_id`),
    KEY `idx_exam_attendance_student` (`student_id`),
    CONSTRAINT `fk_exam_attendance_exam`      FOREIGN KEY (`exam_id`)    REFERENCES `exams` (`id`)    ON DELETE CASCADE,
    CONSTRAINT `fk_exam_attendance_student`   FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_exam_attendance_marked_by` FOREIGN KEY (`marked_by`)  REFERENCES `users` (`id`)    ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `datesheets` (
    `id`           BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `exam_term_id` SMALLINT UNSIGNED NOT NULL,
    `class_id`     SMALLINT UNSIGNED NULL,
    `title`        VARCHAR(160)      NOT NULL,
    `file_path`    VARCHAR(255)      NULL,
    `notes`        TEXT              NULL,
    `is_published` TINYINT(1)        NOT NULL DEFAULT 0,
    `published_at` TIMESTAMP         NULL DEFAULT NULL,
    `created_by`   BIGINT UNSIGNED   NULL,
    `created_at`   TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_datesheets_term` (`exam_term_id`, `is_published`),
    CONSTRAINT `fk_datesheets_term`       FOREIGN KEY (`exam_term_id`) REFERENCES `exam_terms` (`id`)     ON DELETE CASCADE,
    CONSTRAINT `fk_datesheets_class`      FOREIGN KEY (`class_id`)     REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_datesheets_created_by` FOREIGN KEY (`created_by`)   REFERENCES `users` (`id`)          ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0006_examination.sql', 1);
