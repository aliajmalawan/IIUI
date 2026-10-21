-- =============================================================================
-- 0010_optional_modules.sql  ·  Domain J — Library, Transport
-- -----------------------------------------------------------------------------
-- These modules can be disabled in app/Config/modules.php without breaking
-- anything above; nothing in Domains A–I references them.
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------ LIBRARY --------------------------------------
CREATE TABLE `book_categories` (
    `id`        SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`      VARCHAR(100) NOT NULL,
    `code`      VARCHAR(20)  NOT NULL,
    `is_active` TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_book_categories_code` (`code`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `books` (
    `id`               BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `accession_no`     VARCHAR(30)       NOT NULL,
    `isbn`             VARCHAR(20)       NULL,
    `title`            VARCHAR(255)      NOT NULL,
    `author`           VARCHAR(180)      NULL,
    `publisher`        VARCHAR(180)      NULL,
    `edition`          VARCHAR(60)       NULL,
    `publish_year`     SMALLINT UNSIGNED NULL,
    `language`         VARCHAR(40)       NULL DEFAULT 'English',
    `category_id`      SMALLINT UNSIGNED NULL,
    `total_copies`     SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `available_copies` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `shelf_no`         VARCHAR(20)       NULL,
    `price`            DECIMAL(10,2)     NULL,
    `cover_image`      VARCHAR(255)      NULL,
    `is_active`        TINYINT(1)        NOT NULL DEFAULT 1,
    `created_at`       TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_books_accession` (`accession_no`),
    KEY `idx_books_category`  (`category_id`, `is_active`),
    KEY `idx_books_available` (`available_copies`),
    FULLTEXT KEY `ftx_books_search` (`title`, `author`, `publisher`),
    CONSTRAINT `chk_books_copies` CHECK (`available_copies` <= `total_copies` AND `available_copies` >= 0),
    CONSTRAINT `fk_books_category` FOREIGN KEY (`category_id`) REFERENCES `book_categories` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- book_issues — polymorphic borrower (student or staff), same trade-off as
-- leave_applications: one workflow instead of two mirrored tables.
CREATE TABLE `book_issues` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `book_id`       BIGINT UNSIGNED NOT NULL,
    `borrower_type` ENUM('student','staff') NOT NULL,
    `borrower_id`   BIGINT UNSIGNED NOT NULL,
    `issue_date`    DATE            NOT NULL,
    `due_date`      DATE            NOT NULL,
    `return_date`   DATE            NULL,
    `fine_amount`   DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `fine_paid`     TINYINT(1)      NOT NULL DEFAULT 0,
    `condition_on_return` ENUM('good','damaged','lost') NULL,
    `status`        ENUM('issued','returned','overdue','lost') NOT NULL DEFAULT 'issued',
    `issued_by`     BIGINT UNSIGNED NULL,
    `received_by`   BIGINT UNSIGNED NULL,
    `remarks`       VARCHAR(255)    NULL,
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_book_issues_borrower` (`borrower_type`, `borrower_id`, `status`),
    KEY `idx_book_issues_overdue`  (`status`, `due_date`),
    KEY `idx_book_issues_book`     (`book_id`, `status`),
    CONSTRAINT `chk_book_issues_due` CHECK (`due_date` >= `issue_date`),
    CONSTRAINT `fk_book_issues_book`        FOREIGN KEY (`book_id`)     REFERENCES `books` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_book_issues_issued_by`   FOREIGN KEY (`issued_by`)   REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_book_issues_received_by` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ----------------------------- TRANSPORT -------------------------------------
CREATE TABLE `vehicles` (
    `id`                SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `registration_no`   VARCHAR(20)       NOT NULL,
    `type`              ENUM('bus','van','coaster','car') NOT NULL DEFAULT 'bus',
    `make_model`        VARCHAR(80)       NULL,
    `capacity`          SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    `driver_staff_id`   BIGINT UNSIGNED   NULL,
    `driver_name`       VARCHAR(120)      NULL,
    `driver_phone`      VARCHAR(20)       NULL,
    `driver_license_no` VARCHAR(40)       NULL,
    `insurance_expiry`  DATE              NULL,
    `fitness_expiry`    DATE              NULL,
    `status`            ENUM('active','maintenance','retired') NOT NULL DEFAULT 'active',
    `created_at`        TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_vehicles_registration` (`registration_no`),
    KEY `idx_vehicles_status` (`status`),
    KEY `idx_vehicles_expiry` (`insurance_expiry`, `fitness_expiry`),
    CONSTRAINT `fk_vehicles_driver` FOREIGN KEY (`driver_staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `transport_routes` (
    `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(120)      NOT NULL,
    `code`        VARCHAR(20)       NOT NULL,
    `start_point` VARCHAR(160)      NULL,
    `end_point`   VARCHAR(160)      NULL,
    `distance_km` DECIMAL(6,2)      NULL,
    `base_fare`   DECIMAL(10,2)     NOT NULL DEFAULT 0.00,
    `vehicle_id`  SMALLINT UNSIGNED NULL,
    `is_active`   TINYINT(1)        NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_routes_code` (`code`),
    KEY `idx_routes_vehicle` (`vehicle_id`),
    CONSTRAINT `fk_routes_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `route_stops` (
    `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `route_id`    SMALLINT UNSIGNED NOT NULL,
    `stop_name`   VARCHAR(160)      NOT NULL,
    `pickup_time` TIME              NULL,
    `drop_time`   TIME              NULL,
    `fare`        DECIMAL(10,2)     NOT NULL DEFAULT 0.00,
    `latitude`    DECIMAL(10,7)     NULL,
    `longitude`   DECIMAL(10,7)     NULL,
    `sort_order`  SMALLINT          NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_route_stops_name` (`route_id`, `stop_name`),
    KEY `idx_route_stops_order` (`route_id`, `sort_order`),
    CONSTRAINT `fk_route_stops_route` FOREIGN KEY (`route_id`) REFERENCES `transport_routes` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `student_transport` (
    `id`                  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `student_id`          BIGINT UNSIGNED   NOT NULL,
    `academic_session_id` SMALLINT UNSIGNED NOT NULL,
    `route_id`            SMALLINT UNSIGNED NOT NULL,
    `stop_id`             SMALLINT UNSIGNED NOT NULL,
    `monthly_fare`        DECIMAL(10,2)     NOT NULL DEFAULT 0.00,
    `start_date`          DATE              NOT NULL,
    `end_date`            DATE              NULL,
    `status`              ENUM('active','suspended','discontinued') NOT NULL DEFAULT 'active',
    `created_at`          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_student_transport_session` (`student_id`, `academic_session_id`),
    KEY `idx_student_transport_route` (`route_id`, `status`),
    KEY `idx_student_transport_stop`  (`stop_id`),
    CONSTRAINT `fk_student_transport_student` FOREIGN KEY (`student_id`)          REFERENCES `students` (`id`)          ON DELETE CASCADE,
    CONSTRAINT `fk_student_transport_session` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_student_transport_route`   FOREIGN KEY (`route_id`)            REFERENCES `transport_routes` (`id`)  ON DELETE RESTRICT,
    CONSTRAINT `fk_student_transport_stop`    FOREIGN KEY (`stop_id`)             REFERENCES `route_stops` (`id`)       ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0010_optional_modules.sql', 1);
