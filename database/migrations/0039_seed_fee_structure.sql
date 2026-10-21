-- =============================================================================
-- 0039_seed_fee_structure.sql · Starter per-class fee amounts for the
-- current session, so the new admin "Fee Structure" screen and the public
-- /fee-structure page have real content instead of an empty state.
--
-- Four fee heads seeded (Admission Fee, Tuition Fee, Security Deposit,
-- Annual Fund), tiered by wing — a common flat-per-wing pricing pattern.
-- These are PLACEHOLDER amounts (typical of a mid-tier Pakistani private
-- school), not the institution's real published fees — meant to be
-- corrected from Admin → Finance → Fee Structure, not treated as final.
-- Examination/Library/Laboratory/Transport fees are left for the admin to
-- add per class as needed; the CRUD supports every category in
-- `fee_categories`, this seed just doesn't populate all of them.
-- =============================================================================
USE `iiui_mardan`;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `fee_structures` (`academic_session_id`, `class_id`, `fee_category_id`, `amount`, `frequency`)
SELECT
    s.id,
    c.id,
    fc.id,
    CASE fc.code
        WHEN 'ADM' THEN CASE c.wing WHEN 'primary' THEN 15000 WHEN 'middle' THEN 18000 WHEN 'secondary' THEN 20000 ELSE 25000 END
        WHEN 'TUI' THEN CASE c.wing WHEN 'primary' THEN 4000  WHEN 'middle' THEN 5000  WHEN 'secondary' THEN 6000  ELSE 7500  END
        WHEN 'SEC' THEN 10000
        WHEN 'ANN' THEN 3000
    END,
    CASE fc.code WHEN 'TUI' THEN 'monthly' ELSE 'one_time' END
FROM `academic_sessions` s
CROSS JOIN `school_classes` c
CROSS JOIN `fee_categories` fc
WHERE s.is_current = 1
  AND c.is_active = 1
  AND fc.code IN ('ADM', 'TUI', 'SEC', 'ANN');

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0039_seed_fee_structure.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
