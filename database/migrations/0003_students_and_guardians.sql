-- =============================================================================
-- 0003_students_and_guardians.sql  ·  Domain C — Students, guardians, admissions
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- students — the master record. Holds identity facts only; anything that varies
-- by year lives in student_enrollments.
--
-- `current_class_id` / `current_section_id` are a DELIBERATE denormalization:
-- they keep the most-run query in the system ("list students in 9-A") off a
-- three-way join. student_enrollments remains the source of truth, and a
-- trigger (0013) keeps these two columns in sync automatically.
-- -----------------------------------------------------------------------------
CREATE TABLE `students` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`               CHAR(36)        NOT NULL,
    `user_id`            BIGINT UNSIGNED NULL,
    `registration_no`    VARCHAR(30)     NOT NULL,
    `admission_no`       VARCHAR(30)     NOT NULL,

    `first_name`         VARCHAR(80)     NOT NULL,
    `last_name`          VARCHAR(80)     NULL,
    `name_ur`            VARCHAR(160)    NULL,
    `father_name`        VARCHAR(120)    NOT NULL,
    `father_cnic`        VARCHAR(15)     NULL,
    `father_occupation`  VARCHAR(80)     NULL,
    `father_phone`       VARCHAR(20)     NULL,
    `mother_name`        VARCHAR(120)    NULL,
    `mother_cnic`        VARCHAR(15)     NULL,

    `gender`             ENUM('male','female') NOT NULL,
    `date_of_birth`      DATE            NOT NULL,
    `b_form_no`          VARCHAR(20)     NULL,
    `religion`           VARCHAR(40)     NULL DEFAULT 'Islam',
    `nationality`        VARCHAR(40)     NULL DEFAULT 'Pakistani',
    `blood_group`        ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NULL,
    `photo`              VARCHAR(255)    NULL,

    `address`            VARCHAR(255)    NULL,
    `city`               VARCHAR(60)     NULL,
    `district`           VARCHAR(60)     NULL DEFAULT 'Mardan',
    `province`           VARCHAR(60)     NULL DEFAULT 'Khyber Pakhtunkhwa',
    `postal_code`        VARCHAR(10)     NULL,
    `phone`              VARCHAR(20)     NULL,
    `email`              VARCHAR(191)    NULL,

    `admission_date`     DATE            NOT NULL,
    `admission_class_id` SMALLINT UNSIGNED NULL,
    `current_class_id`   SMALLINT UNSIGNED NULL,
    `current_section_id` SMALLINT UNSIGNED NULL,

    `status`             ENUM('active','graduated','left','suspended','expelled','transferred') NOT NULL DEFAULT 'active',
    `previous_school`    VARCHAR(160)    NULL,
    `previous_class`     VARCHAR(40)     NULL,
    `transport_required` TINYINT(1)      NOT NULL DEFAULT 0,
    `is_orphan`          TINYINT(1)      NOT NULL DEFAULT 0,
    `is_disabled`        TINYINT(1)      NOT NULL DEFAULT 0,
    `special_notes`      TEXT            NULL,

    `created_by`         BIGINT UNSIGNED NULL,
    `updated_by`         BIGINT UNSIGNED NULL,
    `created_at`         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`         TIMESTAMP       NULL DEFAULT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_students_uuid`         (`uuid`),
    UNIQUE KEY `uq_students_registration` (`registration_no`),
    UNIQUE KEY `uq_students_admission_no` (`admission_no`),
    UNIQUE KEY `uq_students_user`         (`user_id`),

    -- The single most-executed query in the system: the admin student list.
    KEY `idx_students_listing` (`status`, `current_class_id`, `current_section_id`, `deleted_at`),
    KEY `idx_students_father_cnic` (`father_cnic`),
    KEY `idx_students_admission_date` (`admission_date`),
    KEY `idx_students_transport` (`transport_required`, `status`),
    FULLTEXT KEY `ftx_students_names` (`first_name`, `last_name`, `father_name`),

    CONSTRAINT `chk_students_dob` CHECK (`date_of_birth` < `admission_date`),
    CONSTRAINT `fk_students_user`            FOREIGN KEY (`user_id`)            REFERENCES `users` (`id`)          ON DELETE SET NULL,
    CONSTRAINT `fk_students_admission_class` FOREIGN KEY (`admission_class_id`) REFERENCES `school_classes` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_students_current_class`   FOREIGN KEY (`current_class_id`)   REFERENCES `school_classes` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_students_current_section` FOREIGN KEY (`current_section_id`) REFERENCES `sections` (`id`)       ON DELETE SET NULL,
    CONSTRAINT `fk_students_created_by`      FOREIGN KEY (`created_by`)         REFERENCES `users` (`id`)          ON DELETE SET NULL,
    CONSTRAINT `fk_students_updated_by`      FOREIGN KEY (`updated_by`)         REFERENCES `users` (`id`)          ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- student_enrollments — one row per student per session. The historical spine:
-- this is what makes a 2019 result card reproducible in 2029.
-- -----------------------------------------------------------------------------
CREATE TABLE `student_enrollments` (
    `id`                        BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `student_id`                BIGINT UNSIGNED   NOT NULL,
    `academic_session_id`       SMALLINT UNSIGNED NOT NULL,
    `class_id`                  SMALLINT UNSIGNED NOT NULL,
    `section_id`                SMALLINT UNSIGNED NOT NULL,
    `stream_id`                 TINYINT UNSIGNED  NULL,
    `roll_no`                   VARCHAR(20)       NOT NULL,
    `enrollment_date`           DATE              NOT NULL,
    `promoted_from_enrollment_id` BIGINT UNSIGNED NULL,
    `result_status`             ENUM('pending','promoted','detained','left') NOT NULL DEFAULT 'pending',
    `status`                    ENUM('active','completed','withdrawn') NOT NULL DEFAULT 'active',
    `remarks`                   VARCHAR(255)      NULL,
    `created_by`                BIGINT UNSIGNED   NULL,
    `created_at`                TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),

    -- Structurally prevents double-enrolling a student in one academic year.
    UNIQUE KEY `uq_enrollments_student_session` (`student_id`, `academic_session_id`),
    -- Structurally prevents two students sharing a roll number in one section.
    UNIQUE KEY `uq_enrollments_roll` (`academic_session_id`, `section_id`, `roll_no`),

    KEY `idx_enrollments_section_status` (`section_id`, `status`),
    KEY `idx_enrollments_class_session`  (`academic_session_id`, `class_id`, `status`),
    KEY `idx_enrollments_promoted_from`  (`promoted_from_enrollment_id`),

    CONSTRAINT `fk_enrollments_student`   FOREIGN KEY (`student_id`)          REFERENCES `students` (`id`)          ON DELETE RESTRICT,
    CONSTRAINT `fk_enrollments_session`   FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_enrollments_class`     FOREIGN KEY (`class_id`)            REFERENCES `school_classes` (`id`)    ON DELETE RESTRICT,
    CONSTRAINT `fk_enrollments_section`   FOREIGN KEY (`section_id`)          REFERENCES `sections` (`id`)          ON DELETE RESTRICT,
    CONSTRAINT `fk_enrollments_stream`    FOREIGN KEY (`stream_id`)           REFERENCES `streams` (`id`)           ON DELETE SET NULL,
    CONSTRAINT `fk_enrollments_prev`      FOREIGN KEY (`promoted_from_enrollment_id`) REFERENCES `student_enrollments` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_enrollments_created_by` FOREIGN KEY (`created_by`)         REFERENCES `users` (`id`)             ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- guardians + student_guardians (M:N) — siblings legitimately share one guardian,
-- and one guardian login must see all their children. A guardian_id column on
-- students would make that impossible.
-- -----------------------------------------------------------------------------
CREATE TABLE `guardians` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`         CHAR(36)        NOT NULL,
    `user_id`      BIGINT UNSIGNED NULL,
    `name`         VARCHAR(120)    NOT NULL,
    `relation`     ENUM('father','mother','uncle','aunt','brother','sister','grandfather','other') NOT NULL DEFAULT 'father',
    `cnic`         VARCHAR(15)     NULL,
    `occupation`   VARCHAR(80)     NULL,
    `organization` VARCHAR(120)    NULL,
    `phone`        VARCHAR(20)     NOT NULL,
    `alt_phone`    VARCHAR(20)     NULL,
    `email`        VARCHAR(191)    NULL,
    `address`      VARCHAR(255)    NULL,
    `monthly_income` DECIMAL(12,2) NULL,
    `is_active`    TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`   TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_guardians_uuid` (`uuid`),
    UNIQUE KEY `uq_guardians_cnic` (`cnic`),
    UNIQUE KEY `uq_guardians_user` (`user_id`),
    KEY `idx_guardians_phone` (`phone`),
    CONSTRAINT `fk_guardians_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `student_guardians` (
    `student_id`          BIGINT UNSIGNED NOT NULL,
    `guardian_id`         BIGINT UNSIGNED NOT NULL,
    `relation`            VARCHAR(40)     NULL,
    `is_primary`          TINYINT(1)      NOT NULL DEFAULT 0,
    `is_emergency_contact` TINYINT(1)     NOT NULL DEFAULT 0,
    `created_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`student_id`, `guardian_id`),
    KEY `idx_student_guardians_guardian` (`guardian_id`),
    KEY `idx_student_guardians_primary`  (`student_id`, `is_primary`),
    CONSTRAINT `fk_student_guardians_student`  FOREIGN KEY (`student_id`)  REFERENCES `students` (`id`)  ON DELETE CASCADE,
    CONSTRAINT `fk_student_guardians_guardian` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `student_documents` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`  BIGINT UNSIGNED NOT NULL,
    `doc_type`    ENUM('b_form','father_cnic','photo','previous_result','leaving_certificate','domicile','medical','other') NOT NULL,
    `title`       VARCHAR(120)    NOT NULL,
    `file_path`   VARCHAR(255)    NOT NULL,
    `file_name`   VARCHAR(180)    NOT NULL,
    `mime_type`   VARCHAR(100)    NOT NULL,
    `size_bytes`  INT UNSIGNED    NOT NULL,
    `is_verified` TINYINT(1)      NOT NULL DEFAULT 0,
    `verified_by` BIGINT UNSIGNED NULL,
    `verified_at` TIMESTAMP       NULL DEFAULT NULL,
    `uploaded_by` BIGINT UNSIGNED NULL,
    `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_student_documents_student` (`student_id`, `doc_type`),
    CONSTRAINT `fk_student_documents_student`     FOREIGN KEY (`student_id`)  REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_student_documents_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`)    ON DELETE SET NULL,
    CONSTRAINT `fk_student_documents_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)    ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- student_promotions — the audit trail of bulk promotion runs.
CREATE TABLE `student_promotions` (
    `id`              BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `student_id`      BIGINT UNSIGNED   NOT NULL,
    `from_session_id` SMALLINT UNSIGNED NOT NULL,
    `to_session_id`   SMALLINT UNSIGNED NOT NULL,
    `from_class_id`   SMALLINT UNSIGNED NOT NULL,
    `to_class_id`     SMALLINT UNSIGNED NULL,
    `decision`        ENUM('promoted','detained','graduated','left') NOT NULL,
    `batch_ref`       CHAR(36)          NULL COMMENT 'groups one bulk run',
    `remarks`         VARCHAR(255)      NULL,
    `processed_by`    BIGINT UNSIGNED   NULL,
    `processed_at`    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_promotions_student_from` (`student_id`, `from_session_id`),
    KEY `idx_promotions_batch` (`batch_ref`),
    CONSTRAINT `fk_promotions_student`      FOREIGN KEY (`student_id`)      REFERENCES `students` (`id`)          ON DELETE CASCADE,
    CONSTRAINT `fk_promotions_from_session` FOREIGN KEY (`from_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_promotions_to_session`   FOREIGN KEY (`to_session_id`)   REFERENCES `academic_sessions` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_promotions_from_class`   FOREIGN KEY (`from_class_id`)   REFERENCES `school_classes` (`id`)    ON DELETE RESTRICT,
    CONSTRAINT `fk_promotions_to_class`     FOREIGN KEY (`to_class_id`)     REFERENCES `school_classes` (`id`)    ON DELETE RESTRICT,
    CONSTRAINT `fk_promotions_processed_by` FOREIGN KEY (`processed_by`)    REFERENCES `users` (`id`)             ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `student_transfers` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`       BIGINT UNSIGNED NOT NULL,
    `type`             ENUM('leaving','transfer','character') NOT NULL DEFAULT 'leaving',
    `certificate_no`   VARCHAR(30)     NOT NULL,
    `issue_date`       DATE            NOT NULL,
    `last_class_id`    SMALLINT UNSIGNED NULL,
    `reason`           VARCHAR(255)    NULL,
    `conduct`          ENUM('excellent','good','satisfactory','poor') NOT NULL DEFAULT 'good',
    `dues_cleared`     TINYINT(1)      NOT NULL DEFAULT 0,
    `file_path`        VARCHAR(255)    NULL,
    `issued_by`        BIGINT UNSIGNED NULL,
    `created_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_transfers_certificate` (`certificate_no`),
    KEY `idx_transfers_student` (`student_id`),
    CONSTRAINT `fk_transfers_student`    FOREIGN KEY (`student_id`)    REFERENCES `students` (`id`)       ON DELETE RESTRICT,
    CONSTRAINT `fk_transfers_last_class` FOREIGN KEY (`last_class_id`) REFERENCES `school_classes` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_transfers_issued_by`  FOREIGN KEY (`issued_by`)     REFERENCES `users` (`id`)          ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- admission_applications — public-facing, pre-enrollment. Kept separate from
-- `students` so an unsuccessful applicant never becomes a student record.
-- -----------------------------------------------------------------------------
CREATE TABLE `admission_applications` (
    `id`                   BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `application_no`       VARCHAR(30)       NOT NULL,
    `academic_session_id`  SMALLINT UNSIGNED NOT NULL,
    `class_id`             SMALLINT UNSIGNED NOT NULL,
    `stream_id`            TINYINT UNSIGNED  NULL,

    `applicant_name`       VARCHAR(120)      NOT NULL,
    `father_name`          VARCHAR(120)      NOT NULL,
    `father_cnic`          VARCHAR(15)       NULL,
    `date_of_birth`        DATE              NOT NULL,
    `gender`               ENUM('male','female') NOT NULL,
    `b_form_no`            VARCHAR(20)       NULL,
    `phone`                VARCHAR(20)       NOT NULL,
    `email`                VARCHAR(191)      NULL,
    `address`              VARCHAR(255)      NULL,
    `previous_school`      VARCHAR(160)      NULL,
    `previous_percentage`  DECIMAL(5,2)      NULL,
    `photo`                VARCHAR(255)      NULL,
    `documents`            JSON              NULL,

    `status`               ENUM('submitted','under_review','shortlisted','test_scheduled','test_taken','interview','admitted','rejected','waitlisted','withdrawn') NOT NULL DEFAULT 'submitted',
    `test_date`            DATETIME          NULL,
    `test_roll_no`         VARCHAR(20)       NULL,
    `test_score`           DECIMAL(6,2)      NULL,
    `interview_remarks`    VARCHAR(255)      NULL,
    `merit_position`       SMALLINT UNSIGNED NULL,

    `converted_student_id` BIGINT UNSIGNED   NULL,
    `reviewed_by`          BIGINT UNSIGNED   NULL,
    `reviewed_at`          TIMESTAMP         NULL DEFAULT NULL,
    `remarks`              VARCHAR(255)      NULL,
    `submitted_ip`         VARBINARY(16)     NULL,
    `created_at`           TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_applications_no`      (`application_no`),
    UNIQUE KEY `uq_applications_student` (`converted_student_id`),
    KEY `idx_applications_pipeline` (`academic_session_id`, `status`, `class_id`),
    KEY `idx_applications_merit`    (`academic_session_id`, `class_id`, `test_score`),
    KEY `idx_applications_phone`    (`phone`),

    CONSTRAINT `fk_applications_session` FOREIGN KEY (`academic_session_id`)  REFERENCES `academic_sessions` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_applications_class`   FOREIGN KEY (`class_id`)             REFERENCES `school_classes` (`id`)    ON DELETE RESTRICT,
    CONSTRAINT `fk_applications_stream`  FOREIGN KEY (`stream_id`)            REFERENCES `streams` (`id`)           ON DELETE SET NULL,
    CONSTRAINT `fk_applications_student` FOREIGN KEY (`converted_student_id`) REFERENCES `students` (`id`)          ON DELETE SET NULL,
    CONSTRAINT `fk_applications_reviewer` FOREIGN KEY (`reviewed_by`)         REFERENCES `users` (`id`)             ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0003_students_and_guardians.sql', 1);
