-- =============================================================================
-- 0012_triggers.sql  ·  Data-integrity and derived-value triggers
-- -----------------------------------------------------------------------------
-- RUN THIS FILE WITH THE mysql CLIENT (it uses DELIMITER):
--   mysql -u root iiui_mardan < 0012_triggers.sql
--
-- DESIGN POSITION ON TRIGGERS
-- Triggers here do exactly three jobs and nothing else:
--   1. Guarantee invariants that MUST hold even when a row is written by a
--      script, an import, or a DBA — not just by the application.
--   2. Maintain derived values (challan totals, ledger rows, copy counts) that
--      would otherwise drift the first time someone forgets a service call.
--   3. Record audit facts that must not be skippable.
-- Business WORKFLOW stays in the service layer. A trigger that decides policy
-- is a trigger nobody can find when the policy changes.
--
-- TWO SESSION VARIABLES the application sets once per request:
--   SET @app_user_id = <users.id>          -- who is acting (for audit rows)
--   SET @allow_locked_mark_edit = 1        -- ONLY after the permission check
-- =============================================================================
USE `iiui_mardan`;

DELIMITER $$

-- =============================================================================
-- 1. UUID GENERATION — a row can never exist without its public identifier.
-- =============================================================================

DROP TRIGGER IF EXISTS `trg_users_bi_uuid`$$
CREATE TRIGGER `trg_users_bi_uuid` BEFORE INSERT ON `users` FOR EACH ROW
BEGIN
    IF NEW.`uuid` IS NULL OR NEW.`uuid` = '' THEN SET NEW.`uuid` = UUID(); END IF;
END$$

DROP TRIGGER IF EXISTS `trg_students_bi_uuid`$$
CREATE TRIGGER `trg_students_bi_uuid` BEFORE INSERT ON `students` FOR EACH ROW
BEGIN
    IF NEW.`uuid` IS NULL OR NEW.`uuid` = '' THEN SET NEW.`uuid` = UUID(); END IF;
END$$

DROP TRIGGER IF EXISTS `trg_staff_bi_uuid`$$
CREATE TRIGGER `trg_staff_bi_uuid` BEFORE INSERT ON `staff` FOR EACH ROW
BEGIN
    IF NEW.`uuid` IS NULL OR NEW.`uuid` = '' THEN SET NEW.`uuid` = UUID(); END IF;
END$$

DROP TRIGGER IF EXISTS `trg_guardians_bi_uuid`$$
CREATE TRIGGER `trg_guardians_bi_uuid` BEFORE INSERT ON `guardians` FOR EACH ROW
BEGIN
    IF NEW.`uuid` IS NULL OR NEW.`uuid` = '' THEN SET NEW.`uuid` = UUID(); END IF;
END$$

DROP TRIGGER IF EXISTS `trg_media_bi_uuid`$$
CREATE TRIGGER `trg_media_bi_uuid` BEFORE INSERT ON `media` FOR EACH ROW
BEGIN
    IF NEW.`uuid` IS NULL OR NEW.`uuid` = '' THEN SET NEW.`uuid` = UUID(); END IF;
END$$


-- =============================================================================
-- 2. ENROLLMENT — capacity guard + denormalization sync
-- =============================================================================

-- Section capacity is a hard limit, not a suggestion. Enforced here so a bulk
-- import cannot quietly put 63 students into a 40-seat room.
DROP TRIGGER IF EXISTS `trg_enrollments_bi_capacity`$$
CREATE TRIGGER `trg_enrollments_bi_capacity` BEFORE INSERT ON `student_enrollments` FOR EACH ROW
BEGIN
    DECLARE v_capacity SMALLINT UNSIGNED DEFAULT 0;
    DECLARE v_current  SMALLINT UNSIGNED DEFAULT 0;

    IF NEW.`status` = 'active' THEN
        SELECT `capacity` INTO v_capacity FROM `sections` WHERE `id` = NEW.`section_id`;
        SELECT COUNT(*) INTO v_current
          FROM `student_enrollments`
         WHERE `section_id` = NEW.`section_id` AND `status` = 'active';

        IF v_current >= v_capacity THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Section is at full capacity. Increase the capacity or choose another section.';
        END IF;
    END IF;
END$$

-- Keeps students.current_class_id / current_section_id aligned with the
-- enrollment for the CURRENT session. student_enrollments stays the source of
-- truth; these two columns are a read-path optimisation only.
DROP TRIGGER IF EXISTS `trg_enrollments_ai_sync`$$
CREATE TRIGGER `trg_enrollments_ai_sync` AFTER INSERT ON `student_enrollments` FOR EACH ROW
BEGIN
    DECLARE v_is_current TINYINT(1) DEFAULT 0;

    SELECT `is_current` INTO v_is_current
      FROM `academic_sessions` WHERE `id` = NEW.`academic_session_id`;

    IF v_is_current = 1 AND NEW.`status` = 'active' THEN
        UPDATE `students`
           SET `current_class_id`   = NEW.`class_id`,
               `current_section_id` = NEW.`section_id`
         WHERE `id` = NEW.`student_id`;
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_enrollments_au_sync`$$
CREATE TRIGGER `trg_enrollments_au_sync` AFTER UPDATE ON `student_enrollments` FOR EACH ROW
BEGIN
    DECLARE v_is_current TINYINT(1) DEFAULT 0;

    IF NEW.`class_id` <> OLD.`class_id`
       OR NEW.`section_id` <> OLD.`section_id`
       OR NEW.`status` <> OLD.`status` THEN

        SELECT `is_current` INTO v_is_current
          FROM `academic_sessions` WHERE `id` = NEW.`academic_session_id`;

        IF v_is_current = 1 AND NEW.`status` = 'active' THEN
            UPDATE `students`
               SET `current_class_id`   = NEW.`class_id`,
                   `current_section_id` = NEW.`section_id`
             WHERE `id` = NEW.`student_id`;
        END IF;
    END IF;
END$$


-- =============================================================================
-- 3. ATTENDANCE — no marking on holidays, no marking outside the session
-- =============================================================================

DROP TRIGGER IF EXISTS `trg_attendance_bi_validate`$$
CREATE TRIGGER `trg_attendance_bi_validate` BEFORE INSERT ON `student_attendance` FOR EACH ROW
BEGIN
    DECLARE v_holiday    INT  DEFAULT 0;
    DECLARE v_start      DATE;
    DECLARE v_end        DATE;

    SELECT `start_date`, `end_date` INTO v_start, v_end
      FROM `academic_sessions` WHERE `id` = NEW.`academic_session_id`;

    IF NEW.`attendance_date` < v_start OR NEW.`attendance_date` > v_end THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Attendance date falls outside the academic session.';
    END IF;

    IF NEW.`attendance_date` > CURRENT_DATE THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Attendance cannot be marked for a future date.';
    END IF;

    SELECT COUNT(*) INTO v_holiday
      FROM `holidays`
     WHERE `academic_session_id` = NEW.`academic_session_id`
       AND `applies_to` IN ('all','students')
       AND NEW.`attendance_date` BETWEEN `date_from` AND `date_to`;

    IF v_holiday > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'This date is a holiday. Remove the holiday first if attendance is genuinely required.';
    END IF;
END$$


-- =============================================================================
-- 4. MARKS — range validation, automatic grading, lock enforcement, audit
-- =============================================================================

-- Auto-grading reads grade_scales for the exam's session, so changing the
-- grading policy is a data change, never a deployment.
DROP TRIGGER IF EXISTS `trg_marks_bi_grade`$$
CREATE TRIGGER `trg_marks_bi_grade` BEFORE INSERT ON `marks` FOR EACH ROW
BEGIN
    DECLARE v_total       SMALLINT UNSIGNED DEFAULT 0;
    DECLARE v_prac_total  SMALLINT UNSIGNED DEFAULT 0;
    DECLARE v_session     SMALLINT UNSIGNED DEFAULT 0;
    DECLARE v_pct         DECIMAL(6,2)      DEFAULT 0;
    -- MariaDB does not allow `SELECT ... INTO NEW.col`; NEW may only be
    -- assigned via SET, so the looked-up grade lands in a local variable first.
    DECLARE v_grade       VARCHAR(5)        DEFAULT NULL;
    DECLARE v_grade_point DECIMAL(3,2)      DEFAULT NULL;

    SELECT ex.`total_marks`, ex.`practical_total`, et.`academic_session_id`
      INTO v_total, v_prac_total, v_session
      FROM `exams` ex
      INNER JOIN `exam_terms` et ON et.`id` = ex.`exam_term_id`
     WHERE ex.`id` = NEW.`exam_id`;

    IF NEW.`obtained_marks` IS NOT NULL AND NEW.`obtained_marks` > v_total THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Obtained marks exceed the paper total.';
    END IF;

    IF NEW.`practical_obtained` IS NOT NULL AND NEW.`practical_obtained` > v_prac_total THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Practical marks exceed the practical total.';
    END IF;

    IF NEW.`is_absent` = 1 THEN
        SET NEW.`obtained_marks`     = NULL;
        SET NEW.`practical_obtained` = NULL;
        SET NEW.`grade`              = 'ABS';
        SET NEW.`grade_point`        = 0.00;
    ELSEIF NEW.`obtained_marks` IS NOT NULL THEN
        SET v_pct = 100.0 * (COALESCE(NEW.`obtained_marks`,0) + COALESCE(NEW.`practical_obtained`,0))
                    / NULLIF(v_total + v_prac_total, 0);

        SELECT `grade`, `grade_point` INTO v_grade, v_grade_point
          FROM `grade_scales`
         WHERE `academic_session_id` = v_session
           AND v_pct BETWEEN `min_percentage` AND `max_percentage`
         ORDER BY `min_percentage` DESC
         LIMIT 1;

        SET NEW.`grade` = v_grade;
        SET NEW.`grade_point` = v_grade_point;
    END IF;

    IF NEW.`entered_at` IS NULL THEN SET NEW.`entered_at` = NOW(); END IF;
END$$

-- A locked row is frozen. The application may override ONLY after checking the
-- `marks.edit_locked` permission, which it signals by setting the session
-- variable below. A raw UPDATE from a script hits the wall.
DROP TRIGGER IF EXISTS `trg_marks_bu_grade`$$
CREATE TRIGGER `trg_marks_bu_grade` BEFORE UPDATE ON `marks` FOR EACH ROW
BEGIN
    DECLARE v_total       SMALLINT UNSIGNED DEFAULT 0;
    DECLARE v_prac_total  SMALLINT UNSIGNED DEFAULT 0;
    DECLARE v_session     SMALLINT UNSIGNED DEFAULT 0;
    DECLARE v_pct         DECIMAL(6,2)      DEFAULT 0;
    DECLARE v_grade       VARCHAR(5)        DEFAULT NULL;
    DECLARE v_grade_point DECIMAL(3,2)      DEFAULT NULL;

    IF OLD.`is_locked` = 1
       AND COALESCE(@allow_locked_mark_edit, 0) <> 1
       AND (NOT (NEW.`obtained_marks`     <=> OLD.`obtained_marks`)
         OR NOT (NEW.`practical_obtained` <=> OLD.`practical_obtained`)
         OR NOT (NEW.`is_absent`          <=> OLD.`is_absent`)) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'These marks are locked after verification and cannot be edited.';
    END IF;

    SELECT ex.`total_marks`, ex.`practical_total`, et.`academic_session_id`
      INTO v_total, v_prac_total, v_session
      FROM `exams` ex
      INNER JOIN `exam_terms` et ON et.`id` = ex.`exam_term_id`
     WHERE ex.`id` = NEW.`exam_id`;

    IF NEW.`obtained_marks` IS NOT NULL AND NEW.`obtained_marks` > v_total THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Obtained marks exceed the paper total.';
    END IF;

    IF NEW.`practical_obtained` IS NOT NULL AND NEW.`practical_obtained` > v_prac_total THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Practical marks exceed the practical total.';
    END IF;

    IF NEW.`is_absent` = 1 THEN
        SET NEW.`obtained_marks`     = NULL;
        SET NEW.`practical_obtained` = NULL;
        SET NEW.`grade`              = 'ABS';
        SET NEW.`grade_point`        = 0.00;
    ELSEIF NEW.`obtained_marks` IS NOT NULL THEN
        SET v_pct = 100.0 * (COALESCE(NEW.`obtained_marks`,0) + COALESCE(NEW.`practical_obtained`,0))
                    / NULLIF(v_total + v_prac_total, 0);

        SELECT `grade`, `grade_point` INTO v_grade, v_grade_point
          FROM `grade_scales`
         WHERE `academic_session_id` = v_session
           AND v_pct BETWEEN `min_percentage` AND `max_percentage`
         ORDER BY `min_percentage` DESC
         LIMIT 1;

        SET NEW.`grade` = v_grade;
        SET NEW.`grade_point` = v_grade_point;
    END IF;
END$$

-- Any change to a mark that was already verified is written to the audit trail
-- unconditionally. This is the control that makes result tampering visible.
DROP TRIGGER IF EXISTS `trg_marks_au_audit`$$
CREATE TRIGGER `trg_marks_au_audit` AFTER UPDATE ON `marks` FOR EACH ROW
BEGIN
    IF OLD.`verified_at` IS NOT NULL
       AND (NOT (NEW.`obtained_marks`     <=> OLD.`obtained_marks`)
         OR NOT (NEW.`practical_obtained` <=> OLD.`practical_obtained`)
         OR NOT (NEW.`is_absent`          <=> OLD.`is_absent`)) THEN

        INSERT INTO `audit_logs`
            (`user_id`, `action`, `module`, `entity_type`, `entity_id`,
             `description`, `old_values`, `new_values`)
        VALUES (
            NULLIF(@app_user_id, 0),
            'marks.edited_after_verification',
            'examination',
            'marks',
            NEW.`id`,
            CONCAT('Verified mark changed for student #', NEW.`student_id`, ' on exam #', NEW.`exam_id`),
            JSON_OBJECT('obtained_marks', OLD.`obtained_marks`,
                        'practical_obtained', OLD.`practical_obtained`,
                        'is_absent', OLD.`is_absent`,
                        'grade', OLD.`grade`),
            JSON_OBJECT('obtained_marks', NEW.`obtained_marks`,
                        'practical_obtained', NEW.`practical_obtained`,
                        'is_absent', NEW.`is_absent`,
                        'grade', NEW.`grade`)
        );
    END IF;
END$$


-- =============================================================================
-- 5. FEE CHALLANS — totals derived from items, never hand-set
-- =============================================================================

DROP TRIGGER IF EXISTS `trg_challan_items_ai_total`$$
CREATE TRIGGER `trg_challan_items_ai_total` AFTER INSERT ON `fee_challan_items` FOR EACH ROW
BEGIN
    UPDATE `fee_challans` ch
       SET ch.`gross_amount`    = (SELECT COALESCE(SUM(i.`amount`),0)   FROM `fee_challan_items` i WHERE i.`challan_id` = NEW.`challan_id`),
           ch.`discount_amount` = (SELECT COALESCE(SUM(i.`discount`),0) FROM `fee_challan_items` i WHERE i.`challan_id` = NEW.`challan_id`),
           ch.`payable_amount`  = (SELECT COALESCE(SUM(i.`net_amount`),0) FROM `fee_challan_items` i WHERE i.`challan_id` = NEW.`challan_id`) + ch.`fine_amount`
     WHERE ch.`id` = NEW.`challan_id`;
END$$

DROP TRIGGER IF EXISTS `trg_challan_items_au_total`$$
CREATE TRIGGER `trg_challan_items_au_total` AFTER UPDATE ON `fee_challan_items` FOR EACH ROW
BEGIN
    UPDATE `fee_challans` ch
       SET ch.`gross_amount`    = (SELECT COALESCE(SUM(i.`amount`),0)   FROM `fee_challan_items` i WHERE i.`challan_id` = NEW.`challan_id`),
           ch.`discount_amount` = (SELECT COALESCE(SUM(i.`discount`),0) FROM `fee_challan_items` i WHERE i.`challan_id` = NEW.`challan_id`),
           ch.`payable_amount`  = (SELECT COALESCE(SUM(i.`net_amount`),0) FROM `fee_challan_items` i WHERE i.`challan_id` = NEW.`challan_id`) + ch.`fine_amount`
     WHERE ch.`id` = NEW.`challan_id`;
END$$

DROP TRIGGER IF EXISTS `trg_challan_items_ad_total`$$
CREATE TRIGGER `trg_challan_items_ad_total` AFTER DELETE ON `fee_challan_items` FOR EACH ROW
BEGIN
    UPDATE `fee_challans` ch
       SET ch.`gross_amount`    = (SELECT COALESCE(SUM(i.`amount`),0)   FROM `fee_challan_items` i WHERE i.`challan_id` = OLD.`challan_id`),
           ch.`discount_amount` = (SELECT COALESCE(SUM(i.`discount`),0) FROM `fee_challan_items` i WHERE i.`challan_id` = OLD.`challan_id`),
           ch.`payable_amount`  = (SELECT COALESCE(SUM(i.`net_amount`),0) FROM `fee_challan_items` i WHERE i.`challan_id` = OLD.`challan_id`) + ch.`fine_amount`
     WHERE ch.`id` = OLD.`challan_id`;
END$$


-- =============================================================================
-- 6. FEE PAYMENTS — challan reconciliation + unified ledger
-- The paid amount is ALWAYS recomputed from the payment rows, never
-- incremented. Recomputation is idempotent; increments drift.
-- =============================================================================

DROP TRIGGER IF EXISTS `trg_payments_bi_guard`$$
CREATE TRIGGER `trg_payments_bi_guard` BEFORE INSERT ON `fee_payments` FOR EACH ROW
BEGIN
    DECLARE v_status  VARCHAR(20);
    DECLARE v_payable DECIMAL(12,2) DEFAULT 0;
    DECLARE v_paid    DECIMAL(12,2) DEFAULT 0;

    SELECT `status`, `payable_amount`, `paid_amount`
      INTO v_status, v_payable, v_paid
      FROM `fee_challans` WHERE `id` = NEW.`challan_id`;

    IF v_status = 'cancelled' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot record a payment against a cancelled challan.';
    END IF;

    -- Positive payments may not exceed the outstanding balance. Reversals
    -- (negative amounts) are exempt — that is how a correction is made.
    IF NEW.`amount` > 0 AND (v_paid + NEW.`amount`) > v_payable THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Payment exceeds the outstanding balance on this challan.';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_payments_ai_reconcile`$$
CREATE TRIGGER `trg_payments_ai_reconcile` AFTER INSERT ON `fee_payments` FOR EACH ROW
BEGIN
    DECLARE v_paid    DECIMAL(12,2) DEFAULT 0;
    DECLARE v_payable DECIMAL(12,2) DEFAULT 0;
    DECLARE v_due     DATE;
    DECLARE v_status  VARCHAR(20);
    DECLARE v_session SMALLINT UNSIGNED;

    SELECT COALESCE(SUM(`amount`),0) INTO v_paid
      FROM `fee_payments`
     WHERE `challan_id` = NEW.`challan_id` AND `status` = 'cleared';

    SELECT `payable_amount`, `due_date`, `status`, `academic_session_id`
      INTO v_payable, v_due, v_status, v_session
      FROM `fee_challans` WHERE `id` = NEW.`challan_id`;

    IF v_status <> 'cancelled' THEN
        UPDATE `fee_challans`
           SET `paid_amount` = v_paid,
               `status` = CASE
                   WHEN v_paid >= v_payable      THEN 'paid'
                   WHEN v_paid > 0               THEN 'partial'
                   WHEN v_due < CURRENT_DATE     THEN 'overdue'
                   ELSE 'unpaid'
               END
         WHERE `id` = NEW.`challan_id`;
    END IF;

    -- Unified ledger row (income). Reversals post as negative income, so the
    -- ledger nets out without deleting anything.
    IF NEW.`status` = 'cleared' THEN
        INSERT INTO `transactions`
            (`type`, `source_type`, `source_id`, `academic_session_id`,
             `amount`, `transaction_date`, `category`, `description`)
        VALUES ('income', 'fee_payment', NEW.`id`, v_session,
                NEW.`amount`, NEW.`payment_date`, 'Fee Collection',
                CONCAT('Receipt ', NEW.`receipt_no`))
        ON DUPLICATE KEY UPDATE `amount` = VALUES(`amount`), `transaction_date` = VALUES(`transaction_date`);
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_payments_au_reconcile`$$
CREATE TRIGGER `trg_payments_au_reconcile` AFTER UPDATE ON `fee_payments` FOR EACH ROW
BEGIN
    DECLARE v_paid    DECIMAL(12,2) DEFAULT 0;
    DECLARE v_payable DECIMAL(12,2) DEFAULT 0;
    DECLARE v_due     DATE;
    DECLARE v_status  VARCHAR(20);

    SELECT COALESCE(SUM(`amount`),0) INTO v_paid
      FROM `fee_payments`
     WHERE `challan_id` = NEW.`challan_id` AND `status` = 'cleared';

    SELECT `payable_amount`, `due_date`, `status`
      INTO v_payable, v_due, v_status
      FROM `fee_challans` WHERE `id` = NEW.`challan_id`;

    IF v_status <> 'cancelled' THEN
        UPDATE `fee_challans`
           SET `paid_amount` = v_paid,
               `status` = CASE
                   WHEN v_paid >= v_payable  THEN 'paid'
                   WHEN v_paid > 0           THEN 'partial'
                   WHEN v_due < CURRENT_DATE THEN 'overdue'
                   ELSE 'unpaid'
               END
         WHERE `id` = NEW.`challan_id`;
    END IF;

    -- A bounced or reversed cheque must disappear from the income ledger.
    IF NEW.`status` <> 'cleared' AND OLD.`status` = 'cleared' THEN
        DELETE FROM `transactions`
         WHERE `source_type` = 'fee_payment' AND `source_id` = NEW.`id`;
    END IF;
END$$


-- =============================================================================
-- 7. EXPENSES — ledger row appears only when the expense is actually paid
-- =============================================================================

DROP TRIGGER IF EXISTS `trg_expenses_au_ledger`$$
CREATE TRIGGER `trg_expenses_au_ledger` AFTER UPDATE ON `expenses` FOR EACH ROW
BEGIN
    DECLARE v_category VARCHAR(80);

    IF NEW.`status` = 'paid' AND OLD.`status` <> 'paid' THEN
        SELECT `name` INTO v_category FROM `expense_categories` WHERE `id` = NEW.`expense_category_id`;

        INSERT INTO `transactions`
            (`type`, `source_type`, `source_id`, `academic_session_id`,
             `amount`, `transaction_date`, `category`, `description`)
        VALUES ('expense', 'expense', NEW.`id`, NEW.`academic_session_id`,
                NEW.`amount`, NEW.`expense_date`, v_category,
                CONCAT('Voucher ', NEW.`voucher_no`, ' — ', NEW.`title`))
        ON DUPLICATE KEY UPDATE `amount` = VALUES(`amount`), `transaction_date` = VALUES(`transaction_date`);
    END IF;

    IF NEW.`status` <> 'paid' AND OLD.`status` = 'paid' THEN
        DELETE FROM `transactions` WHERE `source_type` = 'expense' AND `source_id` = NEW.`id`;
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_payrolls_au_ledger`$$
CREATE TRIGGER `trg_payrolls_au_ledger` AFTER UPDATE ON `payrolls` FOR EACH ROW
BEGIN
    IF NEW.`status` = 'paid' AND OLD.`status` <> 'paid' THEN
        INSERT INTO `transactions`
            (`type`, `source_type`, `source_id`, `amount`, `transaction_date`, `category`, `description`)
        VALUES ('expense', 'payroll', NEW.`id`, NEW.`net_salary`,
                COALESCE(DATE(NEW.`paid_at`), CURRENT_DATE), 'Salaries',
                CONCAT('Payroll ', NEW.`month`, '/', NEW.`year`, ' — staff #', NEW.`staff_id`))
        ON DUPLICATE KEY UPDATE `amount` = VALUES(`amount`), `transaction_date` = VALUES(`transaction_date`);
    END IF;
END$$


-- =============================================================================
-- 8. LIBRARY — copy counts derived from issue rows
-- =============================================================================

DROP TRIGGER IF EXISTS `trg_book_issues_ai_stock`$$
CREATE TRIGGER `trg_book_issues_ai_stock` AFTER INSERT ON `book_issues` FOR EACH ROW
BEGIN
    IF NEW.`status` = 'issued' THEN
        UPDATE `books` SET `available_copies` = `available_copies` - 1
         WHERE `id` = NEW.`book_id` AND `available_copies` > 0;
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_book_issues_bi_stock`$$
CREATE TRIGGER `trg_book_issues_bi_stock` BEFORE INSERT ON `book_issues` FOR EACH ROW
BEGIN
    DECLARE v_available SMALLINT DEFAULT 0;
    SELECT `available_copies` INTO v_available FROM `books` WHERE `id` = NEW.`book_id`;
    IF NEW.`status` = 'issued' AND v_available <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No copies of this book are available.';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_book_issues_au_stock`$$
CREATE TRIGGER `trg_book_issues_au_stock` AFTER UPDATE ON `book_issues` FOR EACH ROW
BEGIN
    IF OLD.`status` = 'issued' AND NEW.`status` IN ('returned','lost') THEN
        IF NEW.`status` = 'returned' AND NEW.`condition_on_return` <> 'lost' THEN
            UPDATE `books` SET `available_copies` = `available_copies` + 1 WHERE `id` = NEW.`book_id`;
        ELSEIF NEW.`status` = 'lost' THEN
            UPDATE `books` SET `total_copies` = GREATEST(`total_copies` - 1, 0) WHERE `id` = NEW.`book_id`;
        END IF;
    END IF;
END$$


-- =============================================================================
-- 9. SEO — a changed slug never breaks an existing link
-- Printed prospectuses and old Facebook posts outlive editorial decisions.
-- =============================================================================

DROP TRIGGER IF EXISTS `trg_pages_au_redirect`$$
CREATE TRIGGER `trg_pages_au_redirect` AFTER UPDATE ON `pages` FOR EACH ROW
BEGIN
    IF NEW.`slug` <> OLD.`slug` AND OLD.`status` = 'published' THEN
        INSERT INTO `seo_redirects` (`from_path`, `to_path`, `status_code`)
        VALUES (CONCAT('/page/', OLD.`slug`), CONCAT('/page/', NEW.`slug`), 301)
        ON DUPLICATE KEY UPDATE `to_path` = VALUES(`to_path`), `is_active` = 1;
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_posts_au_redirect`$$
CREATE TRIGGER `trg_posts_au_redirect` AFTER UPDATE ON `posts` FOR EACH ROW
BEGIN
    IF NEW.`slug` <> OLD.`slug` AND OLD.`status` = 'published' THEN
        INSERT INTO `seo_redirects` (`from_path`, `to_path`, `status_code`)
        VALUES (CONCAT('/news/', OLD.`slug`), CONCAT('/news/', NEW.`slug`), 301)
        ON DUPLICATE KEY UPDATE `to_path` = VALUES(`to_path`), `is_active` = 1;
    END IF;
END$$


-- =============================================================================
-- 10. ADMISSIONS — status changes are always traceable
-- =============================================================================

DROP TRIGGER IF EXISTS `trg_applications_au_audit`$$
CREATE TRIGGER `trg_applications_au_audit` AFTER UPDATE ON `admission_applications` FOR EACH ROW
BEGIN
    IF NEW.`status` <> OLD.`status` THEN
        INSERT INTO `audit_logs`
            (`user_id`, `action`, `module`, `entity_type`, `entity_id`,
             `description`, `old_values`, `new_values`)
        VALUES (
            NULLIF(@app_user_id, 0),
            'admission.status_changed',
            'admissions',
            'admission_applications',
            NEW.`id`,
            CONCAT('Application ', NEW.`application_no`, ': ', OLD.`status`, ' → ', NEW.`status`),
            JSON_OBJECT('status', OLD.`status`),
            JSON_OBJECT('status', NEW.`status`)
        );
    END IF;
END$$

DELIMITER ;

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0012_triggers.sql', 1);
