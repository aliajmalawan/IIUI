-- =============================================================================
-- 0004_staff_and_hr.sql  ·  Domain D — Staff, departments, payroll
-- -----------------------------------------------------------------------------
-- Also resolves the deferred foreign keys from 0002: departments.head_staff_id
-- and staff.department_id form a cycle, so the FK is added after both exist.
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `departments` (
    `id`            SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(100)    NOT NULL,
    `code`          VARCHAR(15)     NOT NULL,
    `head_staff_id` BIGINT UNSIGNED NULL,
    `description`   VARCHAR(255)    NULL,
    `sort_order`    SMALLINT        NOT NULL DEFAULT 0,
    `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_departments_code` (`code`),
    KEY `idx_departments_head` (`head_staff_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `designations` (
    `id`         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100) NOT NULL,
    `category`   ENUM('teaching','non_teaching','management','support') NOT NULL DEFAULT 'teaching',
    `rank`       TINYINT UNSIGNED NOT NULL DEFAULT 50,
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_designations_name` (`name`),
    KEY `idx_designations_category` (`category`, `rank`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- staff — `is_public_profile` + `bio` let the website's Faculty page read
-- straight from this table. One source of truth; no parallel "faculty" table
-- to drift out of sync.
-- Sensitive columns (cnic, account_no) are VARBINARY: the application stores
-- them AES-256-GCM encrypted, so a stolen dump alone does not expose them.
-- -----------------------------------------------------------------------------
CREATE TABLE `staff` (
    `id`                BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `uuid`              CHAR(36)          NOT NULL,
    `user_id`           BIGINT UNSIGNED   NULL,
    `employee_no`       VARCHAR(30)       NOT NULL,

    `first_name`        VARCHAR(80)       NOT NULL,
    `last_name`         VARCHAR(80)       NULL,
    `father_name`       VARCHAR(120)      NULL,
    `cnic`              VARBINARY(255)    NULL,
    `cnic_hash`         CHAR(64)          NULL COMMENT 'SHA-256 for uniqueness/lookup without decryption',
    `gender`            ENUM('male','female') NOT NULL,
    `date_of_birth`     DATE              NULL,
    `marital_status`    ENUM('single','married','widowed','divorced') NULL,
    `blood_group`       ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NULL,
    `photo`             VARCHAR(255)      NULL,

    `department_id`     SMALLINT UNSIGNED NULL,
    `designation_id`    SMALLINT UNSIGNED NULL,
    `qualification`     VARCHAR(160)      NULL,
    `specialization`    VARCHAR(160)      NULL,
    `experience_years`  TINYINT UNSIGNED  NOT NULL DEFAULT 0,

    `joining_date`      DATE              NOT NULL,
    `employment_type`   ENUM('permanent','contract','visiting','probation') NOT NULL DEFAULT 'probation',
    `contract_end_date` DATE              NULL,
    `basic_salary`      DECIMAL(12,2)     NOT NULL DEFAULT 0.00,
    `bank_name`         VARCHAR(80)       NULL,
    `account_no`        VARBINARY(255)    NULL,

    `phone`             VARCHAR(20)       NULL,
    `emergency_contact` VARCHAR(20)       NULL,
    `email`             VARCHAR(191)      NULL,
    `address`           VARCHAR(255)      NULL,

    `is_teaching`       TINYINT(1)        NOT NULL DEFAULT 1,
    `is_public_profile` TINYINT(1)        NOT NULL DEFAULT 0,
    `slug`              VARCHAR(140)      NULL,
    `bio`               TEXT              NULL,
    `sort_order`        SMALLINT          NOT NULL DEFAULT 0,

    `status`            ENUM('active','on_leave','resigned','terminated','retired') NOT NULL DEFAULT 'active',
    `left_date`         DATE              NULL,
    `created_by`        BIGINT UNSIGNED   NULL,
    `updated_by`        BIGINT UNSIGNED   NULL,
    `created_at`        TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`        TIMESTAMP         NULL DEFAULT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_staff_uuid`        (`uuid`),
    UNIQUE KEY `uq_staff_employee_no` (`employee_no`),
    UNIQUE KEY `uq_staff_user`        (`user_id`),
    UNIQUE KEY `uq_staff_cnic_hash`   (`cnic_hash`),
    UNIQUE KEY `uq_staff_slug`        (`slug`),
    KEY `idx_staff_listing`  (`status`, `department_id`, `deleted_at`),
    KEY `idx_staff_teaching` (`is_teaching`, `status`),
    KEY `idx_staff_public`   (`is_public_profile`, `sort_order`),
    KEY `idx_staff_contract` (`contract_end_date`, `status`),
    KEY `idx_staff_designation` (`designation_id`),
    FULLTEXT KEY `ftx_staff_names` (`first_name`, `last_name`),

    CONSTRAINT `fk_staff_user`        FOREIGN KEY (`user_id`)        REFERENCES `users` (`id`)        ON DELETE SET NULL,
    CONSTRAINT `fk_staff_department`  FOREIGN KEY (`department_id`)  REFERENCES `departments` (`id`)  ON DELETE SET NULL,
    CONSTRAINT `fk_staff_designation` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_staff_created_by`  FOREIGN KEY (`created_by`)     REFERENCES `users` (`id`)        ON DELETE SET NULL,
    CONSTRAINT `fk_staff_updated_by`  FOREIGN KEY (`updated_by`)     REFERENCES `users` (`id`)        ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `staff_subjects` (
    `id`                  INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `staff_id`            BIGINT UNSIGNED   NOT NULL,
    `subject_id`          SMALLINT UNSIGNED NOT NULL,
    `class_id`            SMALLINT UNSIGNED NOT NULL,
    `section_id`          SMALLINT UNSIGNED NULL,
    `academic_session_id` SMALLINT UNSIGNED NOT NULL,
    `is_primary`          TINYINT(1)        NOT NULL DEFAULT 1,
    `created_at`          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_staff_subjects_assignment` (`academic_session_id`, `staff_id`, `subject_id`, `class_id`, `section_id`),
    -- Drives the teacher data-scope: "which students may this teacher touch?"
    KEY `idx_staff_subjects_scope` (`staff_id`, `academic_session_id`, `section_id`),
    KEY `idx_staff_subjects_section` (`section_id`, `subject_id`),
    CONSTRAINT `fk_staff_subjects_staff`   FOREIGN KEY (`staff_id`)            REFERENCES `staff` (`id`)             ON DELETE CASCADE,
    CONSTRAINT `fk_staff_subjects_subject` FOREIGN KEY (`subject_id`)          REFERENCES `subjects` (`id`)          ON DELETE CASCADE,
    CONSTRAINT `fk_staff_subjects_class`   FOREIGN KEY (`class_id`)            REFERENCES `school_classes` (`id`)    ON DELETE CASCADE,
    CONSTRAINT `fk_staff_subjects_section` FOREIGN KEY (`section_id`)          REFERENCES `sections` (`id`)          ON DELETE CASCADE,
    CONSTRAINT `fk_staff_subjects_session` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `staff_documents` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_id`    BIGINT UNSIGNED NOT NULL,
    `doc_type`    ENUM('cnic','degree','transcript','experience','appointment','contract','photo','other') NOT NULL,
    `title`       VARCHAR(120)    NOT NULL,
    `file_path`   VARCHAR(255)    NOT NULL,
    `file_name`   VARCHAR(180)    NOT NULL,
    `mime_type`   VARCHAR(100)    NOT NULL,
    `size_bytes`  INT UNSIGNED    NOT NULL,
    `is_verified` TINYINT(1)      NOT NULL DEFAULT 0,
    `uploaded_by` BIGINT UNSIGNED NULL,
    `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_staff_documents_staff` (`staff_id`, `doc_type`),
    CONSTRAINT `fk_staff_documents_staff`       FOREIGN KEY (`staff_id`)    REFERENCES `staff` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_staff_documents_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- payrolls — allowances/deductions are JSON because their shape varies per
-- employee and per policy year. A fixed column per allowance type would mean
-- a migration every time HR invents a new stipend.
CREATE TABLE `payrolls` (
    `id`             BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `staff_id`       BIGINT UNSIGNED  NOT NULL,
    `month`          TINYINT UNSIGNED NOT NULL,
    `year`           SMALLINT UNSIGNED NOT NULL,
    `basic_salary`   DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
    `allowances`     JSON             NULL,
    `deductions`     JSON             NULL,
    `gross_salary`   DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
    `total_deduction` DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `net_salary`     DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
    `working_days`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `present_days`   DECIMAL(4,1)     NOT NULL DEFAULT 0.0,
    `leave_days`     DECIMAL(4,1)     NOT NULL DEFAULT 0.0,
    `absent_days`    DECIMAL(4,1)     NOT NULL DEFAULT 0.0,
    `status`         ENUM('draft','approved','paid','cancelled') NOT NULL DEFAULT 'draft',
    `payment_method` ENUM('cash','bank_transfer','cheque') NULL,
    `paid_at`        TIMESTAMP        NULL DEFAULT NULL,
    `generated_by`   BIGINT UNSIGNED  NULL,
    `approved_by`    BIGINT UNSIGNED  NULL,
    `remarks`        VARCHAR(255)     NULL,
    `created_at`     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP        NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payrolls_staff_period` (`staff_id`, `year`, `month`),
    KEY `idx_payrolls_period_status` (`year`, `month`, `status`),
    CONSTRAINT `chk_payrolls_month` CHECK (`month` BETWEEN 1 AND 12),
    CONSTRAINT `fk_payrolls_staff`        FOREIGN KEY (`staff_id`)     REFERENCES `staff` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_payrolls_generated_by` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_payrolls_approved_by`  FOREIGN KEY (`approved_by`)  REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Deferred foreign keys — these point at `staff`, which did not exist when
-- 0002 ran, and one of them (departments.head_staff_id) is part of a cycle.
-- -----------------------------------------------------------------------------
ALTER TABLE `departments`
    ADD CONSTRAINT `fk_departments_head` FOREIGN KEY (`head_staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

ALTER TABLE `sections`
    ADD CONSTRAINT `fk_sections_class_teacher` FOREIGN KEY (`class_teacher_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

ALTER TABLE `class_subjects`
    ADD CONSTRAINT `fk_class_subjects_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

ALTER TABLE `timetables`
    ADD CONSTRAINT `fk_timetables_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0004_staff_and_hr.sql', 1);
