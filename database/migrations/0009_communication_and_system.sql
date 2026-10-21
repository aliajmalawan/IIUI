-- =============================================================================
-- 0009_communication_and_system.sql  ·  Domains I + K
-- Notifications, mail/SMS, queue, backups, cron
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `notifications` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `type`       VARCHAR(60)     NOT NULL,
    `title`      VARCHAR(180)    NOT NULL,
    `body`       VARCHAR(500)    NULL,
    `icon`       VARCHAR(60)     NULL,
    `data`       JSON            NULL,
    `action_url` VARCHAR(255)    NULL,
    `priority`   ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    `read_at`    TIMESTAMP       NULL DEFAULT NULL,
    `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    -- Drives the unread badge count — the single most frequent authenticated query.
    KEY `idx_notifications_unread` (`user_id`, `read_at`, `created_at`),
    KEY `idx_notifications_type`   (`type`, `created_at`),
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `messages` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sender_id`           BIGINT UNSIGNED NOT NULL,
    `receiver_id`         BIGINT UNSIGNED NOT NULL,
    `parent_id`           BIGINT UNSIGNED NULL,
    `subject`             VARCHAR(180)    NULL,
    `body`                TEXT            NOT NULL,
    `attachment`          VARCHAR(255)    NULL,
    `read_at`             TIMESTAMP       NULL DEFAULT NULL,
    `is_starred`          TINYINT(1)      NOT NULL DEFAULT 0,
    `deleted_by_sender`   TINYINT(1)      NOT NULL DEFAULT 0,
    `deleted_by_receiver` TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_messages_inbox`  (`receiver_id`, `deleted_by_receiver`, `created_at`),
    KEY `idx_messages_sent`   (`sender_id`, `deleted_by_sender`, `created_at`),
    KEY `idx_messages_thread` (`parent_id`),
    CONSTRAINT `chk_messages_not_self` CHECK (`sender_id` <> `receiver_id`),
    CONSTRAINT `fk_messages_sender`   FOREIGN KEY (`sender_id`)   REFERENCES `users` (`id`)    ON DELETE CASCADE,
    CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`)    ON DELETE CASCADE,
    CONSTRAINT `fk_messages_parent`   FOREIGN KEY (`parent_id`)   REFERENCES `messages` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `email_templates` (
    `id`                  SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`                VARCHAR(60)  NOT NULL,
    `name`                VARCHAR(120) NOT NULL,
    `subject`             VARCHAR(255) NOT NULL,
    `body`                LONGTEXT     NOT NULL,
    `available_variables` JSON         NULL,
    `is_active`           TINYINT(1)   NOT NULL DEFAULT 1,
    `updated_by`          BIGINT UNSIGNED NULL,
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email_templates_slug` (`slug`),
    CONSTRAINT `fk_email_templates_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `email_logs` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `to_email`      VARCHAR(191)    NOT NULL,
    `cc`            VARCHAR(500)    NULL,
    `subject`       VARCHAR(255)    NOT NULL,
    `template_slug` VARCHAR(60)     NULL,
    `status`        ENUM('queued','sending','sent','failed') NOT NULL DEFAULT 'queued',
    `error_message` VARCHAR(500)    NULL,
    `attempts`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `sent_at`       TIMESTAMP       NULL DEFAULT NULL,
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_email_logs_status` (`status`, `created_at`),
    KEY `idx_email_logs_to`     (`to_email`, `created_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `sms_templates` (
    `id`                  SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`                VARCHAR(60)  NOT NULL,
    `name`                VARCHAR(120) NOT NULL,
    `body`                VARCHAR(500) NOT NULL,
    `available_variables` JSON         NULL,
    `is_active`           TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_sms_templates_slug` (`slug`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `sms_logs` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `to_phone`      VARCHAR(20)     NOT NULL,
    `body`          VARCHAR(500)    NOT NULL,
    `template_slug` VARCHAR(60)     NULL,
    `provider`      VARCHAR(40)     NULL,
    `provider_ref`  VARCHAR(80)     NULL,
    `cost`          DECIMAL(8,2)    NULL,
    `status`        ENUM('queued','sent','delivered','failed') NOT NULL DEFAULT 'queued',
    `error_message` VARCHAR(500)    NULL,
    `attempts`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `sent_at`       TIMESTAMP       NULL DEFAULT NULL,
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sms_logs_status` (`status`, `created_at`),
    KEY `idx_sms_logs_phone`  (`to_phone`, `created_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- =============================================================================
-- Domain K — System
-- =============================================================================

-- jobs — cron-drained queue. Bulk email, SMS, challan generation and result
-- compilation run here so no HTTP request ever waits on 2,500 rows.
CREATE TABLE `jobs` (
    `id`           BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `queue`        VARCHAR(40)      NOT NULL DEFAULT 'default',
    `job_class`    VARCHAR(120)     NOT NULL,
    `payload`      JSON             NOT NULL,
    `attempts`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `available_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reserved_at`  TIMESTAMP        NULL DEFAULT NULL,
    `created_at`   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    -- The worker's claim query: unreserved, due, oldest first.
    KEY `idx_jobs_claim` (`queue`, `reserved_at`, `available_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
    `id`        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `queue`     VARCHAR(40)     NOT NULL,
    `job_class` VARCHAR(120)    NOT NULL,
    `payload`   JSON            NOT NULL,
    `exception` LONGTEXT        NOT NULL,
    `failed_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_failed_jobs_date` (`failed_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `backups` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `filename`     VARCHAR(180)    NOT NULL,
    `path`         VARCHAR(255)    NOT NULL,
    `size_bytes`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `type`         ENUM('database','files','full') NOT NULL DEFAULT 'database',
    `is_encrypted` TINYINT(1)      NOT NULL DEFAULT 1,
    `checksum`     CHAR(64)        NULL,
    `status`       ENUM('running','completed','failed') NOT NULL DEFAULT 'running',
    `error_message` VARCHAR(500)   NULL,
    `created_by`   BIGINT UNSIGNED NULL,
    `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_backups_status` (`status`, `created_at`),
    CONSTRAINT `fk_backups_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `cron_logs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `job_name`    VARCHAR(80)     NOT NULL,
    `status`      ENUM('running','success','failed') NOT NULL DEFAULT 'running',
    `output`      TEXT            NULL,
    `records_processed` INT UNSIGNED NOT NULL DEFAULT 0,
    `duration_ms` INT UNSIGNED    NULL,
    `ran_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_cron_logs_job` (`job_name`, `ran_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `cache` (
    `key_name`   VARCHAR(191) NOT NULL,
    `value`      LONGTEXT     NOT NULL,
    `expires_at` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`key_name`),
    KEY `idx_cache_expiry` (`expires_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0009_communication_and_system.sql', 1);
