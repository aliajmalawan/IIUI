-- =============================================================================
-- 0007_finance.sql  ·  Domain G — Fees, challans, payments, expenses
-- -----------------------------------------------------------------------------
-- Every money column is DECIMAL(12,2). FLOAT/DOUBLE is never used: binary
-- floating point cannot represent 0.10 exactly, and rounding drift in a fee
-- ledger is not recoverable.
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `fee_categories` (
    `id`            SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(80) NOT NULL COMMENT 'e.g. Tuition Fee',
    `code`          VARCHAR(20) NOT NULL,
    `type`          ENUM('recurring','one_time') NOT NULL DEFAULT 'recurring',
    `is_refundable` TINYINT(1)  NOT NULL DEFAULT 0,
    `description`   VARCHAR(255) NULL,
    `sort_order`    SMALLINT    NOT NULL DEFAULT 0,
    `is_active`     TINYINT(1)  NOT NULL DEFAULT 1,
    `created_at`    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP   NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_fee_categories_code` (`code`),
    KEY `idx_fee_categories_active` (`is_active`, `sort_order`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- fee_structures — what a class owes, per session. Fee policy is DATA:
-- changing next year's tuition is a row update, never a deployment.
CREATE TABLE `fee_structures` (
    `id`                  INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `academic_session_id` SMALLINT UNSIGNED NOT NULL,
    `class_id`            SMALLINT UNSIGNED NOT NULL,
    `stream_id`           TINYINT UNSIGNED  NULL,
    `fee_category_id`     SMALLINT UNSIGNED NOT NULL,
    `amount`              DECIMAL(12,2)     NOT NULL,
    `frequency`           ENUM('monthly','quarterly','bi_annual','annual','one_time') NOT NULL DEFAULT 'monthly',
    `due_day`             TINYINT UNSIGNED  NOT NULL DEFAULT 10,
    `applies_to`          ENUM('all','new','existing') NOT NULL DEFAULT 'all',
    `is_active`           TINYINT(1)        NOT NULL DEFAULT 1,
    `created_by`          BIGINT UNSIGNED   NULL,
    `created_at`          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_fee_structures_unique` (`academic_session_id`, `class_id`, `stream_id`, `fee_category_id`),
    KEY `idx_fee_structures_lookup` (`academic_session_id`, `class_id`, `is_active`),
    CONSTRAINT `chk_fee_structures_amount`  CHECK (`amount` >= 0),
    CONSTRAINT `chk_fee_structures_due_day` CHECK (`due_day` BETWEEN 1 AND 28),
    CONSTRAINT `fk_fee_structures_session`  FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fee_structures_class`    FOREIGN KEY (`class_id`)            REFERENCES `school_classes` (`id`)    ON DELETE CASCADE,
    CONSTRAINT `fk_fee_structures_stream`   FOREIGN KEY (`stream_id`)           REFERENCES `streams` (`id`)           ON DELETE CASCADE,
    CONSTRAINT `fk_fee_structures_category` FOREIGN KEY (`fee_category_id`)     REFERENCES `fee_categories` (`id`)    ON DELETE RESTRICT,
    CONSTRAINT `fk_fee_structures_created_by` FOREIGN KEY (`created_by`)        REFERENCES `users` (`id`)             ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `student_fee_discounts` (
    `id`                  INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `student_id`          BIGINT UNSIGNED   NOT NULL,
    `academic_session_id` SMALLINT UNSIGNED NOT NULL,
    `fee_category_id`     SMALLINT UNSIGNED NULL COMMENT 'NULL = applies to every category',
    `type`                ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
    `value`               DECIMAL(10,2)     NOT NULL,
    `reason`              ENUM('sibling','merit','staff_child','hardship','orphan','sports','other') NOT NULL DEFAULT 'other',
    `valid_from`          DATE              NULL,
    `valid_to`            DATE              NULL,
    `approved_by`         BIGINT UNSIGNED   NULL,
    `approved_at`         TIMESTAMP         NULL DEFAULT NULL,
    `remarks`             VARCHAR(255)      NULL,
    `is_active`           TINYINT(1)        NOT NULL DEFAULT 1,
    `created_at`          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_discounts_student_category` (`student_id`, `academic_session_id`, `fee_category_id`),
    KEY `idx_discounts_session_active` (`academic_session_id`, `is_active`),
    CONSTRAINT `chk_discounts_value` CHECK (`value` >= 0 AND (`type` = 'fixed' OR `value` <= 100)),
    CONSTRAINT `fk_discounts_student`  FOREIGN KEY (`student_id`)          REFERENCES `students` (`id`)          ON DELETE CASCADE,
    CONSTRAINT `fk_discounts_session`  FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_discounts_category` FOREIGN KEY (`fee_category_id`)     REFERENCES `fee_categories` (`id`)    ON DELETE CASCADE,
    CONSTRAINT `fk_discounts_approved_by` FOREIGN KEY (`approved_by`)      REFERENCES `users` (`id`)             ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- fee_challans — the invoice. `balance_amount` is a STORED generated column so
-- it can never disagree with payable minus paid; there is no code path that can
-- set it wrongly.
-- -----------------------------------------------------------------------------
CREATE TABLE `fee_challans` (
    `challan_no`          VARCHAR(30)       NOT NULL,
    `id`                  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `student_id`          BIGINT UNSIGNED   NOT NULL,
    `enrollment_id`       BIGINT UNSIGNED   NOT NULL,
    `academic_session_id` SMALLINT UNSIGNED NOT NULL,
    `class_id`            SMALLINT UNSIGNED NOT NULL,
    `section_id`          SMALLINT UNSIGNED NOT NULL,
    `billing_month`       TINYINT UNSIGNED  NULL COMMENT 'NULL for one-time challans',
    `billing_year`        SMALLINT UNSIGNED NOT NULL,
    `issue_date`          DATE              NOT NULL,
    `due_date`            DATE              NOT NULL,
    `gross_amount`        DECIMAL(12,2)     NOT NULL DEFAULT 0.00,
    `discount_amount`     DECIMAL(12,2)     NOT NULL DEFAULT 0.00,
    `fine_amount`         DECIMAL(12,2)     NOT NULL DEFAULT 0.00,
    `payable_amount`      DECIMAL(12,2)     NOT NULL DEFAULT 0.00,
    `paid_amount`         DECIMAL(12,2)     NOT NULL DEFAULT 0.00,
    `balance_amount`      DECIMAL(12,2)     GENERATED ALWAYS AS (`payable_amount` - `paid_amount`) STORED,
    `status`              ENUM('unpaid','partial','paid','overdue','cancelled') NOT NULL DEFAULT 'unpaid',
    `generated_by`        BIGINT UNSIGNED   NULL,
    `batch_ref`           CHAR(36)          NULL COMMENT 'groups one bulk generation run',
    `cancelled_by`        BIGINT UNSIGNED   NULL,
    `cancelled_at`        TIMESTAMP         NULL DEFAULT NULL,
    `cancel_reason`       VARCHAR(255)      NULL,
    `notes`               VARCHAR(255)      NULL,
    `created_at`          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_challans_no` (`challan_no`),
    -- One challan per student per billing period; blocks double-generation.
    UNIQUE KEY `uq_challans_student_period` (`student_id`, `academic_session_id`, `billing_year`, `billing_month`),

    KEY `idx_challans_student_status` (`student_id`, `status`),
    KEY `idx_challans_overdue`        (`status`, `due_date`),
    KEY `idx_challans_period`         (`academic_session_id`, `billing_year`, `billing_month`, `status`),
    KEY `idx_challans_class`          (`class_id`, `section_id`, `status`),
    KEY `idx_challans_batch`          (`batch_ref`),

    CONSTRAINT `chk_challans_month`   CHECK (`billing_month` IS NULL OR `billing_month` BETWEEN 1 AND 12),
    CONSTRAINT `chk_challans_amounts` CHECK (`gross_amount` >= 0 AND `discount_amount` >= 0 AND `fine_amount` >= 0 AND `paid_amount` >= 0),
    CONSTRAINT `chk_challans_due`     CHECK (`due_date` >= `issue_date`),

    CONSTRAINT `fk_challans_student`    FOREIGN KEY (`student_id`)          REFERENCES `students` (`id`)            ON DELETE RESTRICT,
    CONSTRAINT `fk_challans_enrollment` FOREIGN KEY (`enrollment_id`)       REFERENCES `student_enrollments` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_challans_session`    FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`)   ON DELETE RESTRICT,
    CONSTRAINT `fk_challans_class`      FOREIGN KEY (`class_id`)            REFERENCES `school_classes` (`id`)      ON DELETE RESTRICT,
    CONSTRAINT `fk_challans_section`    FOREIGN KEY (`section_id`)          REFERENCES `sections` (`id`)            ON DELETE RESTRICT,
    CONSTRAINT `fk_challans_generated_by` FOREIGN KEY (`generated_by`)      REFERENCES `users` (`id`)               ON DELETE SET NULL,
    CONSTRAINT `fk_challans_cancelled_by` FOREIGN KEY (`cancelled_by`)      REFERENCES `users` (`id`)               ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- A challan is never a single opaque number — it always itemizes.
CREATE TABLE `fee_challan_items` (
    `id`              BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `challan_id`      BIGINT UNSIGNED   NOT NULL,
    `fee_category_id` SMALLINT UNSIGNED NOT NULL,
    `description`     VARCHAR(160)      NULL,
    `amount`          DECIMAL(12,2)     NOT NULL DEFAULT 0.00,
    `discount`        DECIMAL(12,2)     NOT NULL DEFAULT 0.00,
    `net_amount`      DECIMAL(12,2)     GENERATED ALWAYS AS (`amount` - `discount`) STORED,
    `sort_order`      SMALLINT          NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_challan_items_category` (`challan_id`, `fee_category_id`),
    KEY `idx_challan_items_category` (`fee_category_id`),
    CONSTRAINT `chk_challan_items_amounts` CHECK (`amount` >= 0 AND `discount` >= 0 AND `discount` <= `amount`),
    CONSTRAINT `fk_challan_items_challan`  FOREIGN KEY (`challan_id`)      REFERENCES `fee_challans` (`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_challan_items_category` FOREIGN KEY (`fee_category_id`) REFERENCES `fee_categories` (`id`) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- fee_payments — APPEND-ONLY. A correction is a new row with a negative amount
-- referencing `reverses_payment_id`, never an UPDATE of history. This is what
-- makes the ledger auditable: the sum of rows always equals what was banked.
-- -----------------------------------------------------------------------------
CREATE TABLE `fee_payments` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `receipt_no`          VARCHAR(30)     NOT NULL,
    `challan_id`          BIGINT UNSIGNED NOT NULL,
    `student_id`          BIGINT UNSIGNED NOT NULL,
    `amount`              DECIMAL(12,2)   NOT NULL COMMENT 'negative on a reversal row',
    `payment_date`        DATE            NOT NULL,
    `method`              ENUM('cash','bank_deposit','cheque','online','card','adjustment') NOT NULL DEFAULT 'cash',
    `bank_name`           VARCHAR(80)     NULL,
    `transaction_ref`     VARCHAR(80)     NULL,
    `cheque_no`           VARCHAR(40)     NULL,
    `cheque_date`         DATE            NULL,
    `status`              ENUM('pending','cleared','bounced','reversed') NOT NULL DEFAULT 'cleared',
    `reverses_payment_id` BIGINT UNSIGNED NULL,
    `reversal_reason`     VARCHAR(255)    NULL,
    `received_by`         BIGINT UNSIGNED NULL,
    `verified_by`         BIGINT UNSIGNED NULL,
    `remarks`             VARCHAR(255)    NULL,
    `attachment`          VARCHAR(255)    NULL,
    `created_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payments_receipt` (`receipt_no`),
    UNIQUE KEY `uq_payments_reversal` (`reverses_payment_id`),
    KEY `idx_payments_challan`  (`challan_id`),
    KEY `idx_payments_student`  (`student_id`, `payment_date`),
    -- Daily collection report
    KEY `idx_payments_daily`    (`payment_date`, `status`),
    KEY `idx_payments_method`   (`method`, `payment_date`),
    KEY `idx_payments_received_by` (`received_by`, `payment_date`),
    CONSTRAINT `chk_payments_amount_nonzero` CHECK (`amount` <> 0),
    CONSTRAINT `fk_payments_challan`     FOREIGN KEY (`challan_id`)          REFERENCES `fee_challans` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_payments_student`     FOREIGN KEY (`student_id`)          REFERENCES `students` (`id`)     ON DELETE RESTRICT,
    CONSTRAINT `fk_payments_reverses`    FOREIGN KEY (`reverses_payment_id`) REFERENCES `fee_payments` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_payments_received_by` FOREIGN KEY (`received_by`)         REFERENCES `users` (`id`)        ON DELETE SET NULL,
    CONSTRAINT `fk_payments_verified_by` FOREIGN KEY (`verified_by`)         REFERENCES `users` (`id`)        ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `fee_fines` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `challan_id`   BIGINT UNSIGNED NOT NULL,
    `rule`         ENUM('fixed','per_day','percentage') NOT NULL DEFAULT 'fixed',
    `rate`         DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `days_late`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `amount`       DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `applied_on`   DATE            NOT NULL,
    `is_waived`    TINYINT(1)      NOT NULL DEFAULT 0,
    `waived_by`    BIGINT UNSIGNED NULL,
    `waived_at`    TIMESTAMP       NULL DEFAULT NULL,
    `waive_reason` VARCHAR(255)    NULL,
    `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_fines_challan_date` (`challan_id`, `applied_on`),
    KEY `idx_fines_waived` (`is_waived`, `applied_on`),
    CONSTRAINT `chk_fines_amount` CHECK (`amount` >= 0),
    CONSTRAINT `fk_fines_challan`   FOREIGN KEY (`challan_id`) REFERENCES `fee_challans` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fines_waived_by` FOREIGN KEY (`waived_by`)  REFERENCES `users` (`id`)        ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `expense_categories` (
    `id`        SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`      VARCHAR(80) NOT NULL,
    `code`      VARCHAR(20) NOT NULL,
    `is_active` TINYINT(1)  NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_expense_categories_code` (`code`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `expenses` (
    `id`                  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `voucher_no`          VARCHAR(30)       NOT NULL,
    `expense_category_id` SMALLINT UNSIGNED NOT NULL,
    `academic_session_id` SMALLINT UNSIGNED NOT NULL,
    `title`               VARCHAR(160)      NOT NULL,
    `description`         TEXT              NULL,
    `amount`              DECIMAL(12,2)     NOT NULL,
    `expense_date`        DATE              NOT NULL,
    `paid_to`             VARCHAR(160)      NULL,
    `payment_method`      ENUM('cash','bank_transfer','cheque','online') NOT NULL DEFAULT 'cash',
    `reference_no`        VARCHAR(80)       NULL,
    `attachment`          VARCHAR(255)      NULL,
    `status`              ENUM('pending','approved','paid','rejected') NOT NULL DEFAULT 'pending',
    `requested_by`        BIGINT UNSIGNED   NULL,
    `approved_by`         BIGINT UNSIGNED   NULL,
    `approved_at`         TIMESTAMP         NULL DEFAULT NULL,
    `created_at`          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_expenses_voucher` (`voucher_no`),
    KEY `idx_expenses_date_status` (`expense_date`, `status`),
    KEY `idx_expenses_category`    (`expense_category_id`, `expense_date`),
    KEY `idx_expenses_session`     (`academic_session_id`, `status`),
    CONSTRAINT `chk_expenses_amount` CHECK (`amount` > 0),
    CONSTRAINT `fk_expenses_category`     FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_expenses_session`      FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`)  ON DELETE RESTRICT,
    CONSTRAINT `fk_expenses_requested_by` FOREIGN KEY (`requested_by`)        REFERENCES `users` (`id`)              ON DELETE SET NULL,
    CONSTRAINT `fk_expenses_approved_by`  FOREIGN KEY (`approved_by`)         REFERENCES `users` (`id`)              ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- transactions — a unified ledger written by triggers on payments/expenses.
-- The finance dashboard reads one indexed table instead of UNION-ing three
-- at query time.
CREATE TABLE `transactions` (
    `id`                  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `type`                ENUM('income','expense') NOT NULL,
    `source_type`         ENUM('fee_payment','expense','payroll','other') NOT NULL,
    `source_id`           BIGINT UNSIGNED   NOT NULL,
    `academic_session_id` SMALLINT UNSIGNED NULL,
    `amount`              DECIMAL(12,2)     NOT NULL,
    `transaction_date`    DATE              NOT NULL,
    `category`            VARCHAR(80)       NULL,
    `description`         VARCHAR(255)      NULL,
    `created_at`          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_transactions_source` (`source_type`, `source_id`),
    KEY `idx_transactions_date_type` (`transaction_date`, `type`),
    KEY `idx_transactions_session`   (`academic_session_id`, `type`, `transaction_date`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0007_finance.sql', 1);
