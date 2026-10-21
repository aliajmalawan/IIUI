-- =============================================================================
-- 0040_admission_applications_extra_fields.sql · Extends admission_applications
-- (0003_students_and_guardians.sql) with the fields needed by the redesigned
-- /admission-form (Personal Information: religion/city/whatsapp; Academic
-- Information: Matriculation Board/Year/Marks/Total Marks, plus a symmetric
-- Intermediate set for schema completeness even though the current class
-- range never actually requires it — see AdmissionController::store()).
--
-- Matric/Inter marks are stored as plain SMALLINT (obtained/total), not a
-- percentage, since boards report both raw numbers on the certificate and
-- callers can derive a percentage themselves.
-- =============================================================================
USE `iiui_mardan`;

ALTER TABLE `admission_applications`
    ADD COLUMN `religion`           VARCHAR(40)       NULL AFTER `address`,
    ADD COLUMN `city`               VARCHAR(80)       NULL AFTER `religion`,
    ADD COLUMN `whatsapp`           VARCHAR(20)       NULL AFTER `city`,
    ADD COLUMN `matric_board`       VARCHAR(120)      NULL AFTER `previous_percentage`,
    ADD COLUMN `matric_year`        VARCHAR(4)        NULL AFTER `matric_board`,
    ADD COLUMN `matric_marks`       SMALLINT UNSIGNED NULL AFTER `matric_year`,
    ADD COLUMN `matric_total_marks` SMALLINT UNSIGNED NULL AFTER `matric_marks`,
    ADD COLUMN `inter_board`        VARCHAR(120)      NULL AFTER `matric_total_marks`,
    ADD COLUMN `inter_year`         VARCHAR(4)        NULL AFTER `inter_board`,
    ADD COLUMN `inter_marks`        SMALLINT UNSIGNED NULL AFTER `inter_year`,
    ADD COLUMN `inter_total_marks`  SMALLINT UNSIGNED NULL AFTER `inter_marks`;

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0040_admission_applications_extra_fields.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
