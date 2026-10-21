-- =============================================================================
-- 0001_identity_and_access.sql  ·  Domain A — Identity, RBAC, sessions, audit
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- users — the login identity, deliberately separate from students/staff/guardians
-- so one person may hold several profiles (parent AND teacher), and a young
-- student may exist with no login at all.
-- -----------------------------------------------------------------------------
CREATE TABLE `users` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`                  CHAR(36)        NOT NULL,
    `username`              VARCHAR(60)     NOT NULL,
    `email`                 VARCHAR(191)    NULL,
    `phone`                 VARCHAR(20)     NULL,
    `password_hash`         VARCHAR(255)    NOT NULL,
    `first_name`            VARCHAR(80)     NOT NULL,
    `last_name`             VARCHAR(80)     NULL,
    `avatar`                VARCHAR(255)    NULL,
    `status`                ENUM('active','inactive','suspended','pending') NOT NULL DEFAULT 'pending',
    `email_verified_at`     TIMESTAMP       NULL DEFAULT NULL,
    `must_change_password`  TINYINT(1)      NOT NULL DEFAULT 1,
    `two_factor_secret`     VARBINARY(255)  NULL,
    `two_factor_enabled`    TINYINT(1)      NOT NULL DEFAULT 0,
    `two_factor_recovery`   TEXT            NULL,
    `last_login_at`         TIMESTAMP       NULL DEFAULT NULL,
    `last_login_ip`         VARBINARY(16)   NULL,
    `failed_attempts`       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until`          TIMESTAMP       NULL DEFAULT NULL,
    `password_changed_at`   TIMESTAMP       NULL DEFAULT NULL,
    `remember_token`        CHAR(64)        NULL,
    `locale`                CHAR(5)         NOT NULL DEFAULT 'en',
    `theme`                 ENUM('light','dark','system') NOT NULL DEFAULT 'system',
    `created_by`            BIGINT UNSIGNED NULL,
    `updated_by`            BIGINT UNSIGNED NULL,
    `created_at`            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`            TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_uuid`     (`uuid`),
    UNIQUE KEY `uq_users_username` (`username`),
    UNIQUE KEY `uq_users_email`    (`email`),
    KEY `idx_users_status_deleted` (`status`, `deleted_at`),
    KEY `idx_users_phone`          (`phone`),
    KEY `idx_users_last_login`     (`last_login_at`),
    CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_users_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- roles — `level` is a hierarchy rank; a user may never assign a role at or
-- above their own level (enforced in the service layer, documented here).
-- -----------------------------------------------------------------------------
CREATE TABLE `roles` (
    `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`        VARCHAR(50)  NOT NULL,
    `name`        VARCHAR(80)  NOT NULL,
    `description` VARCHAR(255) NULL,
    `level`       TINYINT UNSIGNED NOT NULL DEFAULT 10,
    `is_system`   TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_slug` (`slug`),
    KEY `idx_roles_level` (`level`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- permissions — slug format is strictly `module.action` (students.create, fees.collect)
CREATE TABLE `permissions` (
    `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`        VARCHAR(80) NOT NULL,
    `name`        VARCHAR(120) NOT NULL,
    `module`      VARCHAR(50)  NOT NULL,
    `group_name`  VARCHAR(50)  NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_permissions_slug` (`slug`),
    KEY `idx_permissions_module` (`module`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `role_permissions` (
    `role_id`       SMALLINT UNSIGNED NOT NULL,
    `permission_id` SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    KEY `idx_role_permissions_permission` (`permission_id`),
    CONSTRAINT `fk_role_permissions_role`       FOREIGN KEY (`role_id`)       REFERENCES `roles` (`id`)       ON DELETE CASCADE,
    CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `user_roles` (
    `user_id`     BIGINT UNSIGNED   NOT NULL,
    `role_id`     SMALLINT UNSIGNED NOT NULL,
    `assigned_by` BIGINT UNSIGNED   NULL,
    `assigned_at` TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `role_id`),
    KEY `idx_user_roles_role` (`role_id`),
    CONSTRAINT `fk_user_roles_user`        FOREIGN KEY (`user_id`)     REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_roles_role`        FOREIGN KEY (`role_id`)     REFERENCES `roles` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_user_roles_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- user_permissions — per-user override. type='deny' always wins over any role grant.
CREATE TABLE `user_permissions` (
    `user_id`       BIGINT UNSIGNED   NOT NULL,
    `permission_id` SMALLINT UNSIGNED NOT NULL,
    `type`          ENUM('grant','deny') NOT NULL DEFAULT 'grant',
    `granted_by`    BIGINT UNSIGNED   NULL,
    `reason`        VARCHAR(255)      NULL,
    `created_at`    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `permission_id`),
    KEY `idx_user_permissions_permission` (`permission_id`),
    CONSTRAINT `fk_user_permissions_user`       FOREIGN KEY (`user_id`)       REFERENCES `users` (`id`)       ON DELETE CASCADE,
    CONSTRAINT `fk_user_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_permissions_granted_by` FOREIGN KEY (`granted_by`)    REFERENCES `users` (`id`)       ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Security operations tables
-- -----------------------------------------------------------------------------
CREATE TABLE `password_resets` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(191) NOT NULL,
    `token_hash` CHAR(64)     NOT NULL,
    `ip_address` VARBINARY(16) NULL,
    `expires_at` TIMESTAMP    NOT NULL,
    `used_at`    TIMESTAMP    NULL DEFAULT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_password_resets_token` (`token_hash`),
    KEY `idx_password_resets_email_expiry` (`email`, `expires_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- login_attempts — the source of truth for throttling and lockout decisions.
CREATE TABLE `login_attempts` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `identifier`     VARCHAR(191)  NOT NULL,
    `ip_address`     VARBINARY(16) NOT NULL,
    `user_agent`     VARCHAR(255)  NULL,
    `was_successful` TINYINT(1)    NOT NULL DEFAULT 0,
    `failure_reason` VARCHAR(60)   NULL,
    `attempted_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_login_attempts_identifier` (`identifier`, `attempted_at`),
    KEY `idx_login_attempts_ip`         (`ip_address`, `attempted_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- user_sessions — enables the "log out all other devices" control.
CREATE TABLE `user_sessions` (
    `id`            CHAR(64)        NOT NULL,
    `user_id`       BIGINT UNSIGNED NULL,
    `ip_address`    VARBINARY(16)   NULL,
    `user_agent`    VARCHAR(255)    NULL,
    `fingerprint`   CHAR(64)        NULL,
    `payload`       MEDIUMTEXT      NULL,
    `last_activity` INT UNSIGNED    NOT NULL,
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_sessions_user`     (`user_id`),
    KEY `idx_user_sessions_activity` (`last_activity`),
    CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `personal_access_tokens` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      BIGINT UNSIGNED NOT NULL,
    `name`         VARCHAR(100)    NOT NULL,
    `token_hash`   CHAR(64)        NOT NULL,
    `abilities`    JSON            NULL,
    `last_used_at` TIMESTAMP       NULL DEFAULT NULL,
    `expires_at`   TIMESTAMP       NULL DEFAULT NULL,
    `revoked_at`   TIMESTAMP       NULL DEFAULT NULL,
    `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pat_token` (`token_hash`),
    KEY `idx_pat_user` (`user_id`, `revoked_at`),
    CONSTRAINT `fk_pat_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- audit_logs — append-only. No application path updates or deletes these rows;
-- the app's DB user is granted INSERT/SELECT here only.
-- -----------------------------------------------------------------------------
CREATE TABLE `audit_logs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED NULL,
    `action`      VARCHAR(60)     NOT NULL,
    `module`      VARCHAR(50)     NULL,
    `entity_type` VARCHAR(80)     NULL,
    `entity_id`   BIGINT UNSIGNED NULL,
    `description` VARCHAR(255)    NULL,
    `old_values`  JSON            NULL,
    `new_values`  JSON            NULL,
    `ip_address`  VARBINARY(16)   NULL,
    `user_agent`  VARCHAR(255)    NULL,
    `request_id`  CHAR(36)        NULL,
    `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_logs_entity` (`entity_type`, `entity_id`),
    KEY `idx_audit_logs_user`   (`user_id`, `created_at`),
    KEY `idx_audit_logs_action` (`action`, `created_at`),
    KEY `idx_audit_logs_module` (`module`, `created_at`),
    CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0001_identity_and_access.sql', 1);
