-- =============================================================================
-- 0005_attendance.sql  ·  Domain E — Student & staff attendance, leave
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- student_attendance — the highest-volume table in the system
-- (~250k rows/year at 1,200 students).
--
-- UNIQUE(student_id, attendance_date) is the most important constraint in this
-- domain: it makes double-marking structurally impossible no matter what the
-- UI, a retried AJAX call, or a bulk import does.
--
-- class_id / section_id are denormalized from the enrollment so the daily
-- marking sheet and the monthly register never need a join to filter.
-- -----------------------------------------------------------------------------
CREATE TABLE `student_attendance` (
    `id`                  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `student_id`          BIGINT UNSIGNED   NOT NULL,
    `enrollment_id`       BIGINT UNSIGNED   NOT NULL,
    `academic_session_id` SMALLINT UNSIGNED NOT NULL,
    `class_id`            SMALLINT UNSIGNED NOT NULL,
    `section_id`          SMALLINT UNSIGNED NOT NULL,
    `attendance_date`     DATE              NOT NULL,
    `status`              ENUM('present','absent','late','leave','half_day') NOT NULL,
    `check_in`            TIME              NULL,
    `remarks`             VARCHAR(160)      NULL,
    `marked_by`           BIGINT UNSIGNED   NULL,
    `marked_at`           TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_attendance_student_date` (`student_id`, `attendance_date`),

    -- Daily marking sheet: "give me 9-A for 14 August"
    KEY `idx_attendance_sheet` (`section_id`, `attendance_date`),
    -- Monthly percentage reports and shortage lists
    KEY `idx_attendance_report` (`academic_session_id`, `student_id`, `status`),
    -- Class-wide daily summary for the dashboard
    KEY `idx_attendance_class_date` (`academic_session_id`, `class_id`, `attendance_date`, `status`),
    KEY `idx_attendance_enrollment` (`enrollment_id`),

    CONSTRAINT `fk_attendance_student`    FOREIGN KEY (`student_id`)          REFERENCES `students` (`id`)            ON DELETE RESTRICT,
    CONSTRAINT `fk_attendance_enrollment` FOREIGN KEY (`enrollment_id`)       REFERENCES `student_enrollments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_attendance_session`    FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`)   ON DELETE RESTRICT,
    CONSTRAINT `fk_attendance_class`      FOREIGN KEY (`class_id`)            REFERENCES `school_classes` (`id`)      ON DELETE RESTRICT,
    CONSTRAINT `fk_attendance_section`    FOREIGN KEY (`section_id`)          REFERENCES `sections` (`id`)            ON DELETE RESTRICT,
    CONSTRAINT `fk_attendance_marked_by`  FOREIGN KEY (`marked_by`)           REFERENCES `users` (`id`)               ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `staff_attendance` (
    `id`                  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `staff_id`            BIGINT UNSIGNED   NOT NULL,
    `academic_session_id` SMALLINT UNSIGNED NOT NULL,
    `attendance_date`     DATE              NOT NULL,
    `status`              ENUM('present','absent','late','leave','half_day','official_duty') NOT NULL,
    `check_in`            TIME              NULL,
    `check_out`           TIME              NULL,
    `remarks`             VARCHAR(160)      NULL,
    `marked_by`           BIGINT UNSIGNED   NULL,
    `marked_at`           TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_staff_attendance_date` (`staff_id`, `attendance_date`),
    KEY `idx_staff_attendance_date`   (`attendance_date`, `status`),
    KEY `idx_staff_attendance_report` (`academic_session_id`, `staff_id`, `status`),
    CONSTRAINT `chk_staff_attendance_times` CHECK (`check_out` IS NULL OR `check_in` IS NULL OR `check_out` >= `check_in`),
    CONSTRAINT `fk_staff_attendance_staff`     FOREIGN KEY (`staff_id`)            REFERENCES `staff` (`id`)             ON DELETE RESTRICT,
    CONSTRAINT `fk_staff_attendance_session`   FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_staff_attendance_marked_by` FOREIGN KEY (`marked_by`)           REFERENCES `users` (`id`)             ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- leave_applications — polymorphic applicant (student or staff). The pair
-- (applicant_type, applicant_id) cannot carry a foreign key, so referential
-- integrity here is enforced by the service layer; the trade-off buys one
-- approval workflow instead of two near-identical tables.
CREATE TABLE `leave_applications` (
    `id`                  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `applicant_type`      ENUM('student','staff') NOT NULL,
    `applicant_id`        BIGINT UNSIGNED   NOT NULL,
    `academic_session_id` SMALLINT UNSIGNED NOT NULL,
    `leave_type`          ENUM('sick','casual','emergency','maternity','study','unpaid','other') NOT NULL DEFAULT 'casual',
    `from_date`           DATE              NOT NULL,
    `to_date`             DATE              NOT NULL,
    `total_days`          DECIMAL(4,1)      NOT NULL DEFAULT 1.0,
    `reason`              VARCHAR(500)      NOT NULL,
    `attachment`          VARCHAR(255)      NULL,
    `status`              ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    `reviewed_by`         BIGINT UNSIGNED   NULL,
    `reviewed_at`         TIMESTAMP         NULL DEFAULT NULL,
    `review_remarks`      VARCHAR(255)      NULL,
    `applied_by`          BIGINT UNSIGNED   NULL,
    `created_at`          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_leaves_applicant` (`applicant_type`, `applicant_id`, `status`),
    KEY `idx_leaves_pending`   (`status`, `from_date`),
    KEY `idx_leaves_session`   (`academic_session_id`, `from_date`, `to_date`),
    CONSTRAINT `chk_leaves_range` CHECK (`to_date` >= `from_date`),
    CONSTRAINT `fk_leaves_session`     FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_leaves_reviewed_by` FOREIGN KEY (`reviewed_by`)         REFERENCES `users` (`id`)             ON DELETE SET NULL,
    CONSTRAINT `fk_leaves_applied_by`  FOREIGN KEY (`applied_by`)          REFERENCES `users` (`id`)             ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- attendance_monthly_summary — pre-aggregated, rebuilt nightly by cron.
-- Exists so that a five-year attendance report never scans 1.25M raw rows.
-- The raw table remains the source of truth; this is a derived cache.
CREATE TABLE `attendance_monthly_summary` (
    `id`                  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `student_id`          BIGINT UNSIGNED   NOT NULL,
    `academic_session_id` SMALLINT UNSIGNED NOT NULL,
    `class_id`            SMALLINT UNSIGNED NOT NULL,
    `section_id`          SMALLINT UNSIGNED NOT NULL,
    `year`                SMALLINT UNSIGNED NOT NULL,
    `month`               TINYINT UNSIGNED  NOT NULL,
    `working_days`        TINYINT UNSIGNED  NOT NULL DEFAULT 0,
    `present_days`        TINYINT UNSIGNED  NOT NULL DEFAULT 0,
    `absent_days`         TINYINT UNSIGNED  NOT NULL DEFAULT 0,
    `late_days`           TINYINT UNSIGNED  NOT NULL DEFAULT 0,
    `leave_days`          TINYINT UNSIGNED  NOT NULL DEFAULT 0,
    `half_days`           TINYINT UNSIGNED  NOT NULL DEFAULT 0,
    `percentage`          DECIMAL(5,2)      NOT NULL DEFAULT 0.00,
    `computed_at`         TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_att_summary_student_period` (`student_id`, `year`, `month`),
    KEY `idx_att_summary_section` (`section_id`, `year`, `month`),
    KEY `idx_att_summary_shortage` (`academic_session_id`, `percentage`),
    CONSTRAINT `chk_att_summary_month` CHECK (`month` BETWEEN 1 AND 12),
    CONSTRAINT `fk_att_summary_student` FOREIGN KEY (`student_id`)          REFERENCES `students` (`id`)          ON DELETE CASCADE,
    CONSTRAINT `fk_att_summary_session` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0005_attendance.sql', 1);
