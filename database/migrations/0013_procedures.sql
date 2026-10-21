-- =============================================================================
-- 0013_procedures.sql  ·  Stored procedures for multi-step, transactional work
-- -----------------------------------------------------------------------------
-- RUN THIS FILE WITH THE mysql CLIENT (it uses DELIMITER):
--   mysql -u root iiui_mardan < 0013_procedures.sql
--
-- DESIGN POSITION ON STORED PROCEDURES
-- These procedures exist only for operations that are:
--   (a) genuinely multi-statement and must be atomic (bulk challan generation,
--       promotion, result compilation), or
--   (b) called identically from three surfaces (admin UI, cron, CLI script)
--       and must not drift into three slightly-different implementations.
-- Anything that is a single business decision stays in the PHP service layer,
-- where it can be unit-tested and read by the next developer without a MySQL
-- client. A procedure that encodes policy (e.g. "who can promote") is a
-- procedure nobody will find when the policy changes — so these only ever
-- take the decision as a parameter; they never make it.
--
-- All procedures are transactional: START TRANSACTION / COMMIT, with a
-- handler that ROLLBACKs and re-signals on any SQL exception, so a partial
-- bulk run can never leave the database half-updated.
-- =============================================================================
USE `iiui_mardan`;

DELIMITER $$

-- =============================================================================
-- sp_generate_monthly_challans
-- Bulk-generates fee challans for every active enrollment in a class (or the
-- whole session if p_class_id is NULL) for one billing month, applying each
-- student's active discounts. Idempotent: re-running for a month that already
-- has challans skips those students instead of duplicating (the UNIQUE key on
-- fee_challans would reject the duplicate anyway; this avoids the noisy error).
-- =============================================================================
DROP PROCEDURE IF EXISTS `sp_generate_monthly_challans`$$
CREATE PROCEDURE `sp_generate_monthly_challans` (
    IN  p_session_id     SMALLINT UNSIGNED,
    IN  p_class_id       SMALLINT UNSIGNED,   -- NULL = every class in the session
    IN  p_billing_month  TINYINT UNSIGNED,
    IN  p_billing_year   SMALLINT UNSIGNED,
    IN  p_issue_date     DATE,
    IN  p_generated_by   BIGINT UNSIGNED,
    OUT p_batch_ref      CHAR(36),
    OUT p_challans_created INT,
    OUT p_students_skipped INT
)
proc_body: BEGIN
    DECLARE done            INT DEFAULT 0;
    DECLARE v_enrollment_id BIGINT UNSIGNED;
    DECLARE v_student_id    BIGINT UNSIGNED;
    DECLARE v_class_id      SMALLINT UNSIGNED;
    DECLARE v_section_id    SMALLINT UNSIGNED;
    DECLARE v_stream_id     TINYINT UNSIGNED;
    DECLARE v_challan_id    BIGINT UNSIGNED;
    DECLARE v_due_day       TINYINT UNSIGNED;
    DECLARE v_due_date      DATE;
    DECLARE v_already       INT DEFAULT 0;

    DECLARE cur_students CURSOR FOR
        SELECT e.`id`, e.`student_id`, e.`class_id`, e.`section_id`, e.`stream_id`
          FROM `student_enrollments` e
         WHERE e.`academic_session_id` = p_session_id
           AND e.`status` = 'active'
           AND (p_class_id IS NULL OR e.`class_id` = p_class_id);

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SET p_batch_ref = UUID();
    SET p_challans_created = 0;
    SET p_students_skipped = 0;

    START TRANSACTION;

    OPEN cur_students;
    read_loop: LOOP
        FETCH cur_students INTO v_enrollment_id, v_student_id, v_class_id, v_section_id, v_stream_id;
        IF done = 1 THEN LEAVE read_loop; END IF;

        -- Skip if this student already has a challan for this exact period.
        SELECT COUNT(*) INTO v_already
          FROM `fee_challans`
         WHERE `student_id` = v_student_id
           AND `academic_session_id` = p_session_id
           AND `billing_year` = p_billing_year
           AND `billing_month` <=> p_billing_month;

        IF v_already > 0 THEN
            SET p_students_skipped = p_students_skipped + 1;
        ELSE
            -- due_day comes from the tuition fee structure (or defaults to 10).
            SELECT COALESCE(MIN(fs.`due_day`), 10) INTO v_due_day
              FROM `fee_structures` fs
             WHERE fs.`academic_session_id` = p_session_id
               AND fs.`class_id` = v_class_id
               AND (fs.`stream_id` <=> v_stream_id OR fs.`stream_id` IS NULL)
               AND fs.`is_active` = 1;

            SET v_due_date = DATE_ADD(
                MAKEDATE(p_billing_year, 1) + INTERVAL (p_billing_month - 1) MONTH,
                INTERVAL (v_due_day - 1) DAY
            );

            INSERT INTO `fee_challans`
                (`challan_no`, `student_id`, `enrollment_id`, `academic_session_id`,
                 `class_id`, `section_id`, `billing_month`, `billing_year`,
                 `issue_date`, `due_date`, `status`, `generated_by`, `batch_ref`)
            VALUES (
                CONCAT('CH-', p_billing_year, LPAD(p_billing_month,2,'0'), '-', LPAD(v_student_id,6,'0')),
                v_student_id, v_enrollment_id, p_session_id,
                v_class_id, v_section_id, p_billing_month, p_billing_year,
                p_issue_date, v_due_date, 'unpaid', p_generated_by, p_batch_ref
            );
            SET v_challan_id = LAST_INSERT_ID();

            -- Line items: every active recurring fee category for this class,
            -- net of the student's active discounts for that category.
            INSERT INTO `fee_challan_items` (`challan_id`, `fee_category_id`, `description`, `amount`, `discount`)
            SELECT
                v_challan_id,
                fs.`fee_category_id`,
                fc.`name`,
                fs.`amount`,
                COALESCE((
                    SELECT CASE WHEN d.`type` = 'percentage'
                                THEN ROUND(fs.`amount` * d.`value` / 100, 2)
                                ELSE LEAST(d.`value`, fs.`amount`)
                           END
                      FROM `student_fee_discounts` d
                     WHERE d.`student_id` = v_student_id
                       AND d.`academic_session_id` = p_session_id
                       AND d.`is_active` = 1
                       AND (d.`fee_category_id` <=> fs.`fee_category_id` OR d.`fee_category_id` IS NULL)
                       AND (d.`valid_from` IS NULL OR d.`valid_from` <= p_issue_date)
                       AND (d.`valid_to`   IS NULL OR d.`valid_to`   >= p_issue_date)
                     ORDER BY d.`fee_category_id` IS NULL ASC  -- category-specific wins over blanket
                     LIMIT 1
                ), 0)
              FROM `fee_structures` fs
              INNER JOIN `fee_categories` fc ON fc.`id` = fs.`fee_category_id`
             WHERE fs.`academic_session_id` = p_session_id
               AND fs.`class_id` = v_class_id
               AND (fs.`stream_id` <=> v_stream_id OR fs.`stream_id` IS NULL)
               AND fs.`frequency` = 'monthly'
               AND fs.`is_active` = 1;

            SET p_challans_created = p_challans_created + 1;
        END IF;
    END LOOP;
    CLOSE cur_students;

    COMMIT;
END proc_body$$


-- =============================================================================
-- sp_promote_students
-- Bulk-promotes every active enrollment in `p_from_class_id` (for the closing
-- session) into `p_to_class_id` (in the new session), skipping any student
-- whose latest result_summary says 'fail' unless p_include_detained = 1. Every
-- promoted student's OLD enrollment is marked completed; a NEW enrollment row
-- is created. Nothing is ever deleted — see student_promotions for the audit
-- trail this writes.
-- =============================================================================
DROP PROCEDURE IF EXISTS `sp_promote_students`$$
CREATE PROCEDURE `sp_promote_students` (
    IN  p_from_session_id    SMALLINT UNSIGNED,
    IN  p_to_session_id      SMALLINT UNSIGNED,
    IN  p_from_class_id      SMALLINT UNSIGNED,
    IN  p_to_class_id        SMALLINT UNSIGNED,   -- NULL = graduating class
    IN  p_to_section_id      SMALLINT UNSIGNED,   -- default landing section
    IN  p_include_detained   TINYINT(1),
    IN  p_processed_by       BIGINT UNSIGNED,
    OUT p_batch_ref          CHAR(36),
    OUT p_promoted_count     INT,
    OUT p_detained_count     INT
)
proc_body: BEGIN
    DECLARE done             INT DEFAULT 0;
    DECLARE v_old_enroll_id   BIGINT UNSIGNED;
    DECLARE v_student_id      BIGINT UNSIGNED;
    DECLARE v_result_status   VARCHAR(20);
    DECLARE v_new_enroll_id   BIGINT UNSIGNED;
    DECLARE v_roll_seq        INT DEFAULT 0;

    DECLARE cur_enrollments CURSOR FOR
        SELECT e.`id`, e.`student_id`,
               COALESCE(rs.`result_status`, 'pass') AS result_status
          FROM `student_enrollments` e
          LEFT JOIN `result_summaries` rs
                 ON rs.`student_id` = e.`student_id`
                AND rs.`exam_term_id` = (
                    SELECT et.`id` FROM `exam_terms` et
                     WHERE et.`academic_session_id` = p_from_session_id
                       AND et.`type` = 'final'
                     ORDER BY et.`end_date` DESC LIMIT 1
                )
         WHERE e.`academic_session_id` = p_from_session_id
           AND e.`class_id` = p_from_class_id
           AND e.`status` = 'active'
         ORDER BY e.`roll_no`;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SET p_batch_ref = UUID();
    SET p_promoted_count = 0;
    SET p_detained_count = 0;

    START TRANSACTION;

    OPEN cur_enrollments;
    read_loop: LOOP
        FETCH cur_enrollments INTO v_old_enroll_id, v_student_id, v_result_status;
        IF done = 1 THEN LEAVE read_loop; END IF;

        IF v_result_status = 'fail' AND p_include_detained = 0 THEN
            -- Stays in the same class next session: re-enroll into from_class,
            -- not to_class. Recorded as 'detained', not silently dropped.
            UPDATE `student_enrollments` SET `status` = 'completed', `result_status` = 'detained'
             WHERE `id` = v_old_enroll_id;

            SET v_roll_seq = v_roll_seq + 1;

            INSERT INTO `student_enrollments`
                (`student_id`, `academic_session_id`, `class_id`, `section_id`,
                 `roll_no`, `enrollment_date`, `promoted_from_enrollment_id`, `status`)
            VALUES (v_student_id, p_to_session_id, p_from_class_id, p_to_section_id,
                    CONCAT('TMP-', v_roll_seq), CURRENT_DATE, v_old_enroll_id, 'active');
            SET v_new_enroll_id = LAST_INSERT_ID();

            INSERT INTO `student_promotions`
                (`student_id`, `from_session_id`, `to_session_id`, `from_class_id`,
                 `to_class_id`, `decision`, `batch_ref`, `processed_by`)
            VALUES (v_student_id, p_from_session_id, p_to_session_id, p_from_class_id,
                    p_from_class_id, 'detained', p_batch_ref, p_processed_by);

            SET p_detained_count = p_detained_count + 1;
        ELSE
            UPDATE `student_enrollments` SET `status` = 'completed', `result_status` = 'promoted'
             WHERE `id` = v_old_enroll_id;

            IF p_to_class_id IS NULL THEN
                -- Graduating class: mark the student graduated, no new enrollment.
                UPDATE `students` SET `status` = 'graduated' WHERE `id` = v_student_id;

                INSERT INTO `student_promotions`
                    (`student_id`, `from_session_id`, `to_session_id`, `from_class_id`,
                     `to_class_id`, `decision`, `batch_ref`, `processed_by`)
                VALUES (v_student_id, p_from_session_id, p_to_session_id, p_from_class_id,
                        NULL, 'graduated', p_batch_ref, p_processed_by);
            ELSE
                SET v_roll_seq = v_roll_seq + 1;

                INSERT INTO `student_enrollments`
                    (`student_id`, `academic_session_id`, `class_id`, `section_id`,
                     `roll_no`, `enrollment_date`, `promoted_from_enrollment_id`, `status`)
                VALUES (v_student_id, p_to_session_id, p_to_class_id, p_to_section_id,
                        CONCAT('TMP-', v_roll_seq), CURRENT_DATE, v_old_enroll_id, 'active');
                SET v_new_enroll_id = LAST_INSERT_ID();

                INSERT INTO `student_promotions`
                    (`student_id`, `from_session_id`, `to_session_id`, `from_class_id`,
                     `to_class_id`, `decision`, `batch_ref`, `processed_by`)
                VALUES (v_student_id, p_from_session_id, p_to_session_id, p_from_class_id,
                        p_to_class_id, 'promoted', p_batch_ref, p_processed_by);
            END IF;

            SET p_promoted_count = p_promoted_count + 1;
        END IF;
    END LOOP;
    CLOSE cur_enrollments;

    COMMIT;
END proc_body$$


-- =============================================================================
-- sp_compile_term_results
-- Recomputes result_summaries for every student examined in one exam term:
-- totals, percentage, overall grade, pass/fail, and rank within section AND
-- within class. Safe to re-run after a late mark correction — it fully
-- replaces each student's row rather than incrementing it.
-- =============================================================================
DROP PROCEDURE IF EXISTS `sp_compile_term_results`$$
CREATE PROCEDURE `sp_compile_term_results` (
    IN  p_exam_term_id SMALLINT UNSIGNED,
    IN  p_class_id     SMALLINT UNSIGNED,   -- NULL = every class examined in the term
    OUT p_students_processed INT
)
proc_body: BEGIN
    DECLARE v_session_id SMALLINT UNSIGNED;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SELECT `academic_session_id` INTO v_session_id
      FROM `exam_terms` WHERE `id` = p_exam_term_id;

    START TRANSACTION;

    -- Per-student aggregation over every exam this student sat in the term.
    DROP TEMPORARY TABLE IF EXISTS `tmp_term_totals`;
    CREATE TEMPORARY TABLE `tmp_term_totals` AS
    SELECT
        m.`student_id`,
        m.`enrollment_id`,
        e.`class_id`,
        en.`section_id`,
        SUM(ex.`total_marks` + ex.`practical_total`)                       AS total_marks,
        SUM(COALESCE(m.`total_obtained`, 0))                               AS obtained_marks,
        COUNT(*)                                                          AS subjects_total,
        SUM(CASE WHEN m.`is_absent` = 1 THEN 1 ELSE 0 END)                AS subjects_absent,
        SUM(CASE WHEN m.`is_absent` = 0
                  AND m.`total_obtained` < (ex.`passing_marks`)
                 THEN 1 ELSE 0 END)                                       AS subjects_failed
    FROM `marks` m
    INNER JOIN `exams` ex               ON ex.`id` = m.`exam_id`
    INNER JOIN `exams` e                ON e.`id` = m.`exam_id`
    INNER JOIN `student_enrollments` en ON en.`id` = m.`enrollment_id`
    WHERE ex.`exam_term_id` = p_exam_term_id
      AND (p_class_id IS NULL OR ex.`class_id` = p_class_id)
    GROUP BY m.`student_id`, m.`enrollment_id`, e.`class_id`, en.`section_id`;

    -- Upsert one result_summaries row per student, with pass/fail/absent logic.
    INSERT INTO `result_summaries`
        (`student_id`, `enrollment_id`, `exam_term_id`, `academic_session_id`,
         `class_id`, `section_id`, `total_marks`, `obtained_marks`, `percentage`,
         `subjects_total`, `subjects_failed`, `result_status`)
    SELECT
        t.`student_id`, t.`enrollment_id`, p_exam_term_id, v_session_id,
        t.`class_id`, t.`section_id`, t.`total_marks`, t.`obtained_marks`,
        ROUND(100.0 * t.`obtained_marks` / NULLIF(t.`total_marks`, 0), 2),
        t.`subjects_total`, t.`subjects_failed`,
        CASE
            WHEN t.`subjects_absent` = t.`subjects_total`   THEN 'absent'
            WHEN t.`subjects_failed` > 0                    THEN 'fail'
            ELSE 'pass'
        END
    FROM `tmp_term_totals` t
    ON DUPLICATE KEY UPDATE
        `total_marks`     = VALUES(`total_marks`),
        `obtained_marks`  = VALUES(`obtained_marks`),
        `percentage`      = VALUES(`percentage`),
        `subjects_total`  = VALUES(`subjects_total`),
        `subjects_failed` = VALUES(`subjects_failed`),
        `result_status`   = VALUES(`result_status`),
        `generated_at`    = NOW();

    -- Overall grade from the session's grade_scales.
    UPDATE `result_summaries` r
    INNER JOIN `grade_scales` g
            ON g.`academic_session_id` = v_session_id
           AND r.`percentage` BETWEEN g.`min_percentage` AND g.`max_percentage`
       SET r.`grade` = g.`grade`, r.`grade_point` = g.`grade_point`
     WHERE r.`exam_term_id` = p_exam_term_id
       AND (p_class_id IS NULL OR r.`class_id` = p_class_id);

    -- Rank within section (dense rank via a session-scoped counter).
    SET @sec_rank := 0, @sec_prev := NULL;
    UPDATE `result_summaries` r
    INNER JOIN (
        SELECT `id`,
               @sec_rank := IF(@sec_prev = `section_id`, @sec_rank, 0) + 1 AS rnk,
               @sec_prev := `section_id`
          FROM `result_summaries`
         WHERE `exam_term_id` = p_exam_term_id
           AND `result_status` <> 'absent'
         ORDER BY `section_id`, `percentage` DESC
    ) ranked ON ranked.`id` = r.`id`
       SET r.`position_in_section` = ranked.rnk;

    -- Rank within class.
    SET @cls_rank := 0, @cls_prev := NULL;
    UPDATE `result_summaries` r
    INNER JOIN (
        SELECT `id`,
               @cls_rank := IF(@cls_prev = `class_id`, @cls_rank, 0) + 1 AS rnk,
               @cls_prev := `class_id`
          FROM `result_summaries`
         WHERE `exam_term_id` = p_exam_term_id
           AND `result_status` <> 'absent'
         ORDER BY `class_id`, `percentage` DESC
    ) ranked ON ranked.`id` = r.`id`
       SET r.`position_in_class` = ranked.rnk;

    SELECT COUNT(*) INTO p_students_processed
      FROM `result_summaries`
     WHERE `exam_term_id` = p_exam_term_id
       AND (p_class_id IS NULL OR `class_id` = p_class_id);

    DROP TEMPORARY TABLE IF EXISTS `tmp_term_totals`;

    COMMIT;
END proc_body$$


-- =============================================================================
-- sp_publish_term_result
-- Publishes results only when every subject for every student in the term has
-- a verified mark — this is the enforcement of "no partial results ever go
-- public." Returns 0 and leaves everything untouched if the check fails.
-- =============================================================================
DROP PROCEDURE IF EXISTS `sp_publish_term_result`$$
CREATE PROCEDURE `sp_publish_term_result` (
    IN  p_exam_term_id SMALLINT UNSIGNED,
    IN  p_published_by BIGINT UNSIGNED,
    OUT p_success       TINYINT(1),
    OUT p_unverified_count INT
)
proc_body: BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SELECT COUNT(*) INTO p_unverified_count
      FROM `marks` m
      INNER JOIN `exams` ex ON ex.`id` = m.`exam_id`
     WHERE ex.`exam_term_id` = p_exam_term_id
       AND m.`is_absent` = 0
       AND m.`verified_at` IS NULL;

    IF p_unverified_count > 0 THEN
        SET p_success = 0;
    ELSE
        START TRANSACTION;

        UPDATE `result_summaries`
           SET `is_published` = 1, `published_at` = NOW()
         WHERE `exam_term_id` = p_exam_term_id;

        UPDATE `exam_terms`
           SET `is_result_published` = 1, `published_at` = NOW(), `published_by` = p_published_by
         WHERE `id` = p_exam_term_id;

        COMMIT;
        SET p_success = 1;
    END IF;
END proc_body$$


-- =============================================================================
-- sp_apply_overdue_fines
-- Nightly cron target: finds every challan past its due date still owing
-- money, applies the fine rule (fixed / per_day / percentage) once per day
-- (guarded by fee_fines' UNIQUE(challan_id, applied_on)), and flips the
-- challan to 'overdue'.
-- =============================================================================
DROP PROCEDURE IF EXISTS `sp_apply_overdue_fines`$$
CREATE PROCEDURE `sp_apply_overdue_fines` (
    IN  p_fine_rule       ENUM('fixed','per_day','percentage'),
    IN  p_fine_rate       DECIMAL(10,2),
    OUT p_fines_applied   INT
)
proc_body: BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    UPDATE `fee_challans`
       SET `status` = 'overdue'
     WHERE `status` IN ('unpaid','partial')
       AND `due_date` < CURRENT_DATE;

    INSERT INTO `fee_fines` (`challan_id`, `rule`, `rate`, `days_late`, `amount`, `applied_on`)
    SELECT
        ch.`id`, p_fine_rule, p_fine_rate,
        DATEDIFF(CURRENT_DATE, ch.`due_date`),
        CASE p_fine_rule
            WHEN 'fixed'      THEN p_fine_rate
            WHEN 'per_day'    THEN p_fine_rate * DATEDIFF(CURRENT_DATE, ch.`due_date`)
            WHEN 'percentage' THEN ROUND(ch.`balance_amount` * p_fine_rate / 100, 2)
        END,
        CURRENT_DATE
      FROM `fee_challans` ch
     WHERE ch.`status` = 'overdue'
       AND NOT EXISTS (
           SELECT 1 FROM `fee_fines` f
            WHERE f.`challan_id` = ch.`id` AND f.`applied_on` = CURRENT_DATE
       );

    SET p_fines_applied = ROW_COUNT();

    -- Roll the new fine into each affected challan's fine_amount / payable_amount.
    UPDATE `fee_challans` ch
    INNER JOIN (
        SELECT `challan_id`, SUM(`amount`) AS today_fine
          FROM `fee_fines`
         WHERE `applied_on` = CURRENT_DATE AND `is_waived` = 0
         GROUP BY `challan_id`
    ) f ON f.`challan_id` = ch.`id`
       SET ch.`fine_amount`    = ch.`fine_amount` + f.`today_fine`,
           ch.`payable_amount` = ch.`payable_amount` + f.`today_fine`;

    COMMIT;
END proc_body$$


-- =============================================================================
-- sp_rebuild_attendance_summary
-- Rebuilds attendance_monthly_summary for one month from the raw
-- student_attendance rows. Cron target — see docs/07-roadmap.md cron table.
-- =============================================================================
DROP PROCEDURE IF EXISTS `sp_rebuild_attendance_summary`$$
CREATE PROCEDURE `sp_rebuild_attendance_summary` (
    IN p_year  SMALLINT UNSIGNED,
    IN p_month TINYINT UNSIGNED
)
proc_body: BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    INSERT INTO `attendance_monthly_summary`
        (`student_id`, `academic_session_id`, `class_id`, `section_id`,
         `year`, `month`, `working_days`, `present_days`, `absent_days`,
         `late_days`, `leave_days`, `half_days`, `percentage`)
    SELECT
        a.`student_id`, a.`academic_session_id`, a.`class_id`, a.`section_id`,
        p_year, p_month,
        COUNT(*),
        SUM(CASE WHEN a.`status` = 'present'  THEN 1 ELSE 0 END),
        SUM(CASE WHEN a.`status` = 'absent'   THEN 1 ELSE 0 END),
        SUM(CASE WHEN a.`status` = 'late'     THEN 1 ELSE 0 END),
        SUM(CASE WHEN a.`status` = 'leave'    THEN 1 ELSE 0 END),
        SUM(CASE WHEN a.`status` = 'half_day' THEN 1 ELSE 0 END),
        ROUND(100.0 * SUM(CASE WHEN a.`status` IN ('present','late') THEN 1
                               WHEN a.`status` = 'half_day'          THEN 0.5
                               ELSE 0 END) / NULLIF(COUNT(*),0), 2)
      FROM `student_attendance` a
     WHERE YEAR(a.`attendance_date`) = p_year AND MONTH(a.`attendance_date`) = p_month
     GROUP BY a.`student_id`, a.`academic_session_id`, a.`class_id`, a.`section_id`
    ON DUPLICATE KEY UPDATE
        `working_days` = VALUES(`working_days`),
        `present_days` = VALUES(`present_days`),
        `absent_days`  = VALUES(`absent_days`),
        `late_days`    = VALUES(`late_days`),
        `leave_days`   = VALUES(`leave_days`),
        `half_days`    = VALUES(`half_days`),
        `percentage`   = VALUES(`percentage`),
        `computed_at`  = NOW();

    COMMIT;
END proc_body$$


-- =============================================================================
-- sp_convert_application_to_student
-- The one-click "admit" action: creates the students row, the first
-- student_enrollments row, optionally a users login, and marks the
-- application admitted — atomically. Returns the new student_id.
-- =============================================================================
DROP PROCEDURE IF EXISTS `sp_convert_application_to_student`$$
CREATE PROCEDURE `sp_convert_application_to_student` (
    IN  p_application_id BIGINT UNSIGNED,
    IN  p_section_id     SMALLINT UNSIGNED,
    IN  p_roll_no        VARCHAR(20),
    IN  p_registration_no VARCHAR(30),
    IN  p_admission_no   VARCHAR(30),
    IN  p_processed_by   BIGINT UNSIGNED,
    OUT p_student_id     BIGINT UNSIGNED
)
proc_body: BEGIN
    DECLARE v_session_id SMALLINT UNSIGNED;
    DECLARE v_class_id   SMALLINT UNSIGNED;
    DECLARE v_stream_id  TINYINT UNSIGNED;
    DECLARE v_name       VARCHAR(120);
    DECLARE v_father     VARCHAR(120);
    DECLARE v_cnic       VARCHAR(15);
    DECLARE v_dob        DATE;
    DECLARE v_gender     ENUM('male','female');
    DECLARE v_bform      VARCHAR(20);
    DECLARE v_phone      VARCHAR(20);
    DECLARE v_email      VARCHAR(191);
    DECLARE v_address    VARCHAR(255);
    DECLARE v_photo      VARCHAR(255);
    DECLARE v_school     VARCHAR(160);
    DECLARE v_first      VARCHAR(80);
    DECLARE v_last       VARCHAR(80);
    DECLARE v_space_pos  INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SELECT `academic_session_id`, `class_id`, `stream_id`, `applicant_name`,
           `father_name`, `father_cnic`, `date_of_birth`, `gender`, `b_form_no`,
           `phone`, `email`, `address`, `photo`, `previous_school`
      INTO v_session_id, v_class_id, v_stream_id, v_name,
           v_father, v_cnic, v_dob, v_gender, v_bform,
           v_phone, v_email, v_address, v_photo, v_school
      FROM `admission_applications`
     WHERE `id` = p_application_id AND `status` <> 'admitted';

    IF v_session_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Application not found or already admitted.';
    END IF;

    SET v_space_pos = LOCATE(' ', v_name);
    IF v_space_pos > 0 THEN
        SET v_first = SUBSTRING(v_name, 1, v_space_pos - 1);
        SET v_last  = SUBSTRING(v_name, v_space_pos + 1);
    ELSE
        SET v_first = v_name;
        SET v_last  = NULL;
    END IF;

    START TRANSACTION;

    INSERT INTO `students`
        (`registration_no`, `admission_no`, `first_name`, `last_name`, `father_name`,
         `father_cnic`, `gender`, `date_of_birth`, `b_form_no`, `photo`, `address`,
         `phone`, `email`, `admission_date`, `admission_class_id`,
         `current_class_id`, `current_section_id`, `previous_school`, `status`,
         `created_by`)
    VALUES
        (p_registration_no, p_admission_no, v_first, v_last, v_father,
         v_cnic, v_gender, v_dob, v_bform, v_photo, v_address,
         v_phone, v_email, CURRENT_DATE, v_class_id,
         v_class_id, p_section_id, v_school, 'active',
         p_processed_by);

    SET p_student_id = LAST_INSERT_ID();

    INSERT INTO `student_enrollments`
        (`student_id`, `academic_session_id`, `class_id`, `section_id`, `stream_id`,
         `roll_no`, `enrollment_date`, `status`, `created_by`)
    VALUES
        (p_student_id, v_session_id, v_class_id, p_section_id, v_stream_id,
         p_roll_no, CURRENT_DATE, 'active', p_processed_by);

    UPDATE `admission_applications`
       SET `status` = 'admitted', `converted_student_id` = p_student_id,
           `reviewed_by` = p_processed_by, `reviewed_at` = NOW()
     WHERE `id` = p_application_id;

    COMMIT;
END proc_body$$

DELIMITER ;

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0013_procedures.sql', 1);
