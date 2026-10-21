-- =============================================================================
-- IIUI Schools & College – Mardan Campus
-- 0000_init.sql  ·  Database creation + migration tracking
-- -----------------------------------------------------------------------------
-- Target      : MySQL 8.0+ / MariaDB 10.4+   (installed: MariaDB 10.4.32)
-- Engine      : InnoDB      Charset: utf8mb4      Collation: utf8mb4_unicode_ci
-- Run order   : 0000 → 0014, in sequence. Files 0012–0014 (views, triggers,
--               procedures) require the mysql client because they use DELIMITER.
-- -----------------------------------------------------------------------------
--
--  ENTITY RELATIONSHIP OVERVIEW
--
--  ┌───────────────────────── A. IDENTITY & ACCESS ─────────────────────────┐
--  │  users ─┬─< user_roles >─ roles ─< role_permissions >─ permissions     │
--  │         ├─< user_permissions >──────────────────────────┘              │
--  │         ├─< user_sessions   ├─< personal_access_tokens                 │
--  │         └─< audit_logs                                                 │
--  └────────────────────────────────────────────────────────────────────────┘
--        │ 1:0..1                │ 1:0..1              │ 1:0..1
--        ▼                       ▼                     ▼
--    students                  staff                guardians
--
--  ┌────────────────────── B. ACADEMIC STRUCTURE ───────────────────────────┐
--  │  campuses ─< school_classes ─┬─< sections ────────────┐                │
--  │                              ├─< class_streams >─ streams              │
--  │                              └─< class_subjects >─ subjects            │
--  │  academic_sessions ──scopes──> sections, class_subjects, enrollments,  │
--  │                                attendance, exams, challans, timetables │
--  │  grade_scales · holidays · timetables                                  │
--  └────────────────────────────────────────────────────────────────────────┘
--
--  ┌────────────────── C. STUDENTS ──────────────┐  ┌──── D. STAFF ─────────┐
--  │ admission_applications ──converts──> students│  │ departments           │
--  │ students ─┬─< student_enrollments (1/session)│  │ designations          │
--  │           ├─< student_guardians >─ guardians │  │ staff ─┬─< staff_subj │
--  │           ├─< student_documents              │  │        ├─< staff_docs │
--  │           ├─< student_promotions             │  │        └─< payrolls   │
--  │           └─< student_transfers              │  └───────────────────────┘
--  └──────────────────────────────────────────────┘
--                  │                                        │
--  ┌──── E. ATTENDANCE ────┐  ┌──── F. EXAMINATION ────┐  ┌── G. FINANCE ───┐
--  │ student_attendance    │  │ exam_terms ─< exams    │  │ fee_categories  │
--  │   UQ(student,date)    │  │   exams ─< marks       │  │ fee_structures  │
--  │ staff_attendance      │  │   UQ(exam,student)     │  │ fee_challans    │
--  │ leave_applications    │  │ result_summaries       │  │  ├─< items      │
--  │ holidays              │  │ datesheets             │  │  ├─< payments   │
--  └───────────────────────┘  │ exam_attendance        │  │  └─< fines      │
--                             └────────────────────────┘  │ expenses        │
--                                                         │ transactions    │
--  ┌──────── H. CMS ────────┐  ┌── I. COMMS ──┐           └─────────────────┘
--  │ pages · posts · tags   │  │ notifications │  ┌─── J. OPTIONAL ─────────┐
--  │ events · notices       │  │ messages      │  │ books ─< book_issues    │
--  │ galleries ─< images    │  │ email_*       │  │ transport_routes        │
--  │ menus ─< menu_items    │  │ sms_*         │  │   ├─< route_stops       │
--  │ media · settings · seo │  └───────────────┘  │   └─< student_transport │
--  └────────────────────────┘                     └─────────────────────────┘
--
--  DELETE-RULE POLICY
--    RESTRICT  → academic + financial history (enrollments, marks, payments)
--    CASCADE   → true composition only (challan items, gallery images, pivots)
--    SET NULL  → optional references (author_id, class_teacher_id)
--    Business "delete" is soft delete (deleted_at), never DDL removal.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE;
SET SQL_MODE = 'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE DATABASE IF NOT EXISTS `iiui_mardan`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `iiui_mardan`;

-- -----------------------------------------------------------------------------
-- Migration tracking — every file below records itself here on success.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `migrations` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `filename`   VARCHAR(191) NOT NULL,
    `batch`      INT UNSIGNED NOT NULL DEFAULT 1,
    `checksum`   CHAR(64)     NULL,
    `applied_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_migrations_filename` (`filename`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0000_init.sql', 1)
    ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;

SET FOREIGN_KEY_CHECKS = 1;
