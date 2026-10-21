-- =============================================================================
-- 0011_views.sql  ·  Reporting views
-- -----------------------------------------------------------------------------
-- Views exist so that reporting logic lives in ONE place instead of being
-- re-typed (slightly differently, every time) across controllers, exports and
-- printed reports. All are ALGORITHM = MERGE where possible so the optimiser
-- can still use the base-table indexes.
--
-- Rule followed throughout: no view calls another view more than one level
-- deep, and none contains a derived table — both defeat index usage.
-- =============================================================================
USE `iiui_mardan`;

-- -----------------------------------------------------------------------------
-- v_active_students — the admin student list and every "pick a student" dropdown.
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW `v_active_students` AS
SELECT
    s.`id`                AS student_id,
    s.`uuid`,
    s.`registration_no`,
    s.`admission_no`,
    CONCAT_WS(' ', s.`first_name`, s.`last_name`) AS student_name,
    s.`father_name`,
    s.`gender`,
    s.`date_of_birth`,
    s.`photo`,
    s.`phone`,
    s.`status`,
    e.`id`                AS enrollment_id,
    e.`roll_no`,
    e.`academic_session_id`,
    ses.`name`            AS session_name,
    c.`id`                AS class_id,
    c.`name`              AS class_name,
    c.`numeric_level`,
    c.`wing`,
    sec.`id`              AS section_id,
    sec.`name`            AS section_name,
    st.`name`             AS stream_name,
    s.`transport_required`,
    s.`admission_date`
FROM `students` s
INNER JOIN `student_enrollments` e   ON e.`student_id` = s.`id` AND e.`status` = 'active'
INNER JOIN `academic_sessions` ses   ON ses.`id` = e.`academic_session_id`
INNER JOIN `school_classes` c        ON c.`id` = e.`class_id`
INNER JOIN `sections` sec            ON sec.`id` = e.`section_id`
LEFT  JOIN `streams` st              ON st.`id` = e.`stream_id`
WHERE s.`deleted_at` IS NULL
  AND s.`status` = 'active';

-- -----------------------------------------------------------------------------
-- v_student_contacts — student joined to the PRIMARY guardian only.
-- Used by absence SMS, fee reminders and the parent directory.
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW `v_student_contacts` AS
SELECT
    s.`id`              AS student_id,
    s.`registration_no`,
    CONCAT_WS(' ', s.`first_name`, s.`last_name`) AS student_name,
    s.`current_class_id`,
    s.`current_section_id`,
    g.`id`              AS guardian_id,
    g.`name`            AS guardian_name,
    g.`relation`,
    g.`phone`           AS guardian_phone,
    g.`email`           AS guardian_email,
    g.`user_id`         AS guardian_user_id,
    COALESCE(g.`phone`, s.`father_phone`, s.`phone`) AS contact_phone
FROM `students` s
LEFT JOIN `student_guardians` sg ON sg.`student_id` = s.`id` AND sg.`is_primary` = 1
LEFT JOIN `guardians` g          ON g.`id` = sg.`guardian_id` AND g.`deleted_at` IS NULL
WHERE s.`deleted_at` IS NULL;

-- -----------------------------------------------------------------------------
-- v_class_strength — dashboard tile and the enrolment report.
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW `v_class_strength` AS
SELECT
    e.`academic_session_id`,
    ses.`name`  AS session_name,
    c.`id`      AS class_id,
    c.`name`    AS class_name,
    c.`numeric_level`,
    c.`wing`,
    sec.`id`    AS section_id,
    sec.`name`  AS section_name,
    sec.`capacity`,
    COUNT(e.`id`)                                          AS total_students,
    SUM(CASE WHEN s.`gender` = 'male'   THEN 1 ELSE 0 END) AS male_count,
    SUM(CASE WHEN s.`gender` = 'female' THEN 1 ELSE 0 END) AS female_count,
    sec.`capacity` - COUNT(e.`id`)                         AS seats_available
FROM `student_enrollments` e
INNER JOIN `students` s            ON s.`id` = e.`student_id` AND s.`deleted_at` IS NULL
INNER JOIN `academic_sessions` ses ON ses.`id` = e.`academic_session_id`
INNER JOIN `school_classes` c      ON c.`id` = e.`class_id`
INNER JOIN `sections` sec          ON sec.`id` = e.`section_id`
WHERE e.`status` = 'active'
GROUP BY e.`academic_session_id`, ses.`name`, c.`id`, c.`name`, c.`numeric_level`,
         c.`wing`, sec.`id`, sec.`name`, sec.`capacity`;

-- -----------------------------------------------------------------------------
-- v_attendance_daily — one row per section per day. Feeds the dashboard trend
-- chart and the "which sections have not been marked yet?" alert.
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW `v_attendance_daily` AS
SELECT
    a.`academic_session_id`,
    a.`class_id`,
    c.`name`   AS class_name,
    a.`section_id`,
    sec.`name` AS section_name,
    a.`attendance_date`,
    COUNT(*)                                                    AS total_marked,
    SUM(CASE WHEN a.`status` = 'present'   THEN 1 ELSE 0 END)   AS present_count,
    SUM(CASE WHEN a.`status` = 'absent'    THEN 1 ELSE 0 END)   AS absent_count,
    SUM(CASE WHEN a.`status` = 'late'      THEN 1 ELSE 0 END)   AS late_count,
    SUM(CASE WHEN a.`status` = 'leave'     THEN 1 ELSE 0 END)   AS leave_count,
    SUM(CASE WHEN a.`status` = 'half_day'  THEN 1 ELSE 0 END)   AS half_day_count,
    ROUND(
        100.0 * SUM(CASE WHEN a.`status` IN ('present','late') THEN 1
                         WHEN a.`status` = 'half_day'          THEN 0.5
                         ELSE 0 END) / NULLIF(COUNT(*), 0), 2
    ) AS attendance_percentage
FROM `student_attendance` a
INNER JOIN `school_classes` c ON c.`id` = a.`class_id`
INNER JOIN `sections` sec     ON sec.`id` = a.`section_id`
GROUP BY a.`academic_session_id`, a.`class_id`, c.`name`,
         a.`section_id`, sec.`name`, a.`attendance_date`;

-- -----------------------------------------------------------------------------
-- v_student_attendance_summary — per student, per session.
-- A half-day counts as 0.5 present; late still counts as present for the
-- eligibility percentage, which is the school's stated policy.
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW `v_student_attendance_summary` AS
SELECT
    a.`student_id`,
    a.`academic_session_id`,
    a.`class_id`,
    a.`section_id`,
    COUNT(*)                                                  AS total_days,
    SUM(CASE WHEN a.`status` = 'present'  THEN 1 ELSE 0 END)  AS present_days,
    SUM(CASE WHEN a.`status` = 'absent'   THEN 1 ELSE 0 END)  AS absent_days,
    SUM(CASE WHEN a.`status` = 'late'     THEN 1 ELSE 0 END)  AS late_days,
    SUM(CASE WHEN a.`status` = 'leave'    THEN 1 ELSE 0 END)  AS leave_days,
    SUM(CASE WHEN a.`status` = 'half_day' THEN 1 ELSE 0 END)  AS half_days,
    ROUND(
        100.0 * SUM(CASE WHEN a.`status` IN ('present','late') THEN 1
                         WHEN a.`status` = 'half_day'          THEN 0.5
                         ELSE 0 END) / NULLIF(COUNT(*), 0), 2
    ) AS attendance_percentage
FROM `student_attendance` a
GROUP BY a.`student_id`, a.`academic_session_id`, a.`class_id`, a.`section_id`;

-- -----------------------------------------------------------------------------
-- v_challan_ledger — a challan with everything the print layout, the parent
-- portal and the defaulter report all need. `days_overdue` is computed here so
-- three different screens cannot compute it three different ways.
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW `v_challan_ledger` AS
SELECT
    ch.`id`             AS challan_id,
    ch.`challan_no`,
    ch.`student_id`,
    s.`registration_no`,
    CONCAT_WS(' ', s.`first_name`, s.`last_name`) AS student_name,
    s.`father_name`,
    ch.`academic_session_id`,
    c.`name`            AS class_name,
    sec.`name`          AS section_name,
    e.`roll_no`,
    ch.`billing_month`,
    ch.`billing_year`,
    ch.`issue_date`,
    ch.`due_date`,
    ch.`gross_amount`,
    ch.`discount_amount`,
    ch.`fine_amount`,
    ch.`payable_amount`,
    ch.`paid_amount`,
    ch.`balance_amount`,
    ch.`status`,
    CASE
        WHEN ch.`status` IN ('paid','cancelled') THEN 0
        WHEN ch.`due_date` >= CURRENT_DATE       THEN 0
        ELSE DATEDIFF(CURRENT_DATE, ch.`due_date`)
    END AS days_overdue
FROM `fee_challans` ch
INNER JOIN `students` s            ON s.`id` = ch.`student_id`
INNER JOIN `student_enrollments` e ON e.`id` = ch.`enrollment_id`
INNER JOIN `school_classes` c      ON c.`id` = ch.`class_id`
INNER JOIN `sections` sec          ON sec.`id` = ch.`section_id`;

-- -----------------------------------------------------------------------------
-- v_fee_defaulters — one row per student with outstanding money, plus the
-- guardian phone so the reminder campaign needs no extra join.
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW `v_fee_defaulters` AS
SELECT
    ch.`student_id`,
    s.`registration_no`,
    CONCAT_WS(' ', s.`first_name`, s.`last_name`) AS student_name,
    s.`father_name`,
    ch.`academic_session_id`,
    ch.`class_id`,
    c.`name`   AS class_name,
    sec.`name` AS section_name,
    COALESCE(g.`phone`, s.`father_phone`, s.`phone`) AS contact_phone,
    COUNT(ch.`id`)             AS unpaid_challans,
    SUM(ch.`balance_amount`)   AS total_outstanding,
    MIN(ch.`due_date`)         AS oldest_due_date,
    DATEDIFF(CURRENT_DATE, MIN(ch.`due_date`)) AS max_days_overdue
FROM `fee_challans` ch
INNER JOIN `students` s          ON s.`id` = ch.`student_id` AND s.`deleted_at` IS NULL
INNER JOIN `school_classes` c    ON c.`id` = ch.`class_id`
INNER JOIN `sections` sec        ON sec.`id` = ch.`section_id`
LEFT  JOIN `student_guardians` sg ON sg.`student_id` = s.`id` AND sg.`is_primary` = 1
LEFT  JOIN `guardians` g          ON g.`id` = sg.`guardian_id`
WHERE ch.`status` IN ('unpaid','partial','overdue')
  AND ch.`balance_amount` > 0
GROUP BY ch.`student_id`, s.`registration_no`, s.`first_name`, s.`last_name`,
         s.`father_name`, ch.`academic_session_id`, ch.`class_id`, c.`name`,
         sec.`name`, g.`phone`, s.`father_phone`, s.`phone`;

-- -----------------------------------------------------------------------------
-- v_daily_collection — the accountant's day-close report.
-- Reversal rows carry a negative amount, so SUM() is already net.
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW `v_daily_collection` AS
SELECT
    p.`payment_date`,
    p.`method`,
    p.`received_by`,
    CONCAT_WS(' ', u.`first_name`, u.`last_name`) AS received_by_name,
    COUNT(*)          AS transaction_count,
    SUM(p.`amount`)   AS total_amount
FROM `fee_payments` p
LEFT JOIN `users` u ON u.`id` = p.`received_by`
WHERE p.`status` = 'cleared'
GROUP BY p.`payment_date`, p.`method`, p.`received_by`, u.`first_name`, u.`last_name`;

-- -----------------------------------------------------------------------------
-- v_marks_detail — the marks-entry sheet and the consolidated result sheet.
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW `v_marks_detail` AS
SELECT
    m.`id`             AS mark_id,
    m.`exam_id`,
    m.`student_id`,
    s.`registration_no`,
    CONCAT_WS(' ', s.`first_name`, s.`last_name`) AS student_name,
    e.`roll_no`,
    et.`id`            AS exam_term_id,
    et.`name`          AS term_name,
    et.`academic_session_id`,
    ex.`class_id`,
    c.`name`           AS class_name,
    e.`section_id`,
    sec.`name`         AS section_name,
    sub.`id`           AS subject_id,
    sub.`name`         AS subject_name,
    sub.`code`         AS subject_code,
    ex.`total_marks`,
    ex.`passing_marks`,
    ex.`practical_total`,
    m.`obtained_marks`,
    m.`practical_obtained`,
    m.`total_obtained`,
    m.`is_absent`,
    m.`grade`,
    m.`grade_point`,
    ROUND(100.0 * m.`total_obtained` / NULLIF(ex.`total_marks` + ex.`practical_total`, 0), 2) AS percentage,
    CASE
        WHEN m.`is_absent` = 1                              THEN 'absent'
        WHEN m.`obtained_marks` IS NULL                     THEN 'pending'
        WHEN m.`total_obtained` >= ex.`passing_marks`       THEN 'pass'
        ELSE 'fail'
    END AS subject_result,
    m.`verified_at`,
    m.`is_locked`
FROM `marks` m
INNER JOIN `exams` ex              ON ex.`id` = m.`exam_id`
INNER JOIN `exam_terms` et         ON et.`id` = ex.`exam_term_id`
INNER JOIN `subjects` sub          ON sub.`id` = ex.`subject_id`
INNER JOIN `students` s            ON s.`id` = m.`student_id`
INNER JOIN `student_enrollments` e ON e.`id` = m.`enrollment_id`
INNER JOIN `school_classes` c      ON c.`id` = ex.`class_id`
INNER JOIN `sections` sec          ON sec.`id` = e.`section_id`;

-- -----------------------------------------------------------------------------
-- v_result_cards — one row per student per term, print-ready.
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW `v_result_cards` AS
SELECT
    r.`id`         AS result_id,
    r.`student_id`,
    s.`registration_no`,
    CONCAT_WS(' ', s.`first_name`, s.`last_name`) AS student_name,
    s.`father_name`,
    s.`date_of_birth`,
    s.`photo`,
    e.`roll_no`,
    r.`exam_term_id`,
    et.`name`      AS term_name,
    et.`type`      AS term_type,
    r.`academic_session_id`,
    ses.`name`     AS session_name,
    c.`name`       AS class_name,
    sec.`name`     AS section_name,
    r.`total_marks`,
    r.`obtained_marks`,
    r.`percentage`,
    r.`grade`,
    r.`grade_point`,
    r.`position_in_section`,
    r.`position_in_class`,
    r.`subjects_total`,
    r.`subjects_failed`,
    r.`result_status`,
    r.`remarks`,
    r.`is_published`,
    r.`published_at`
FROM `result_summaries` r
INNER JOIN `students` s            ON s.`id` = r.`student_id`
INNER JOIN `student_enrollments` e ON e.`id` = r.`enrollment_id`
INNER JOIN `exam_terms` et         ON et.`id` = r.`exam_term_id`
INNER JOIN `academic_sessions` ses ON ses.`id` = r.`academic_session_id`
INNER JOIN `school_classes` c      ON c.`id` = r.`class_id`
INNER JOIN `sections` sec          ON sec.`id` = r.`section_id`;

-- -----------------------------------------------------------------------------
-- v_teacher_workload — periods and sections per teacher; used to spot the
-- teacher carrying 34 periods before they complain about it.
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW `v_teacher_workload` AS
SELECT
    st.`id`   AS staff_id,
    st.`employee_no`,
    CONCAT_WS(' ', st.`first_name`, st.`last_name`) AS staff_name,
    d.`name`  AS department_name,
    ss.`academic_session_id`,
    COUNT(DISTINCT ss.`subject_id`) AS subjects_taught,
    COUNT(DISTINCT ss.`section_id`) AS sections_taught,
    COUNT(DISTINCT ss.`class_id`)   AS classes_taught,
    (SELECT COUNT(*) FROM `timetables` t
       WHERE t.`teacher_id` = st.`id`
         AND t.`academic_session_id` = ss.`academic_session_id`) AS weekly_periods
FROM `staff` st
INNER JOIN `staff_subjects` ss ON ss.`staff_id` = st.`id`
LEFT  JOIN `departments` d     ON d.`id` = st.`department_id`
WHERE st.`deleted_at` IS NULL AND st.`status` = 'active'
GROUP BY st.`id`, st.`employee_no`, st.`first_name`, st.`last_name`,
         d.`name`, ss.`academic_session_id`;

-- -----------------------------------------------------------------------------
-- v_staff_directory — also feeds the public Faculty page (filter on
-- is_public_profile), so there is no duplicate "faculty" table to drift.
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW `v_staff_directory` AS
SELECT
    st.`id`   AS staff_id,
    st.`uuid`,
    st.`employee_no`,
    st.`slug`,
    CONCAT_WS(' ', st.`first_name`, st.`last_name`) AS staff_name,
    st.`gender`,
    st.`photo`,
    st.`qualification`,
    st.`specialization`,
    st.`experience_years`,
    st.`bio`,
    st.`phone`,
    st.`email`,
    st.`joining_date`,
    st.`employment_type`,
    st.`status`,
    st.`is_teaching`,
    st.`is_public_profile`,
    st.`sort_order`,
    d.`id`    AS department_id,
    d.`name`  AS department_name,
    dg.`id`   AS designation_id,
    dg.`name` AS designation_name,
    dg.`category` AS designation_category
FROM `staff` st
LEFT JOIN `departments` d   ON d.`id`  = st.`department_id`
LEFT JOIN `designations` dg ON dg.`id` = st.`designation_id`
WHERE st.`deleted_at` IS NULL;

-- -----------------------------------------------------------------------------
-- v_monthly_finance — income vs expense per month, straight off the unified
-- ledger. This is why `transactions` exists: the finance dashboard reads one
-- indexed table instead of UNION-ing three at query time.
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW `v_monthly_finance` AS
SELECT
    t.`academic_session_id`,
    YEAR(t.`transaction_date`)  AS txn_year,
    MONTH(t.`transaction_date`) AS txn_month,
    SUM(CASE WHEN t.`type` = 'income'  THEN t.`amount` ELSE 0 END) AS total_income,
    SUM(CASE WHEN t.`type` = 'expense' THEN t.`amount` ELSE 0 END) AS total_expense,
    SUM(CASE WHEN t.`type` = 'income'  THEN t.`amount` ELSE -t.`amount` END) AS net_balance,
    COUNT(*) AS transaction_count
FROM `transactions` t
GROUP BY t.`academic_session_id`, YEAR(t.`transaction_date`), MONTH(t.`transaction_date`);

-- -----------------------------------------------------------------------------
-- v_active_notices — what is publishable RIGHT NOW. Keeps the publish-window
-- logic out of every controller that lists notices.
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW `v_active_notices` AS
SELECT
    n.`id`,
    n.`title`,
    n.`slug`,
    n.`content`,
    n.`attachment`,
    n.`audience`,
    n.`class_id`,
    c.`name` AS class_name,
    n.`priority`,
    n.`is_pinned`,
    n.`publish_from`,
    n.`publish_to`,
    n.`views`,
    n.`author_id`
FROM `notices` n
LEFT JOIN `school_classes` c ON c.`id` = n.`class_id`
WHERE n.`deleted_at` IS NULL
  AND n.`status` = 'published'
  AND n.`publish_from` <= NOW()
  AND (n.`publish_to` IS NULL OR n.`publish_to` >= NOW());

-- -----------------------------------------------------------------------------
-- v_unmarked_attendance — sections that still have no attendance for a given
-- date. The actionable widget on the class teacher's dashboard.
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW `v_unmarked_attendance` AS
SELECT
    sec.`id`   AS section_id,
    sec.`name` AS section_name,
    c.`id`     AS class_id,
    c.`name`   AS class_name,
    sec.`academic_session_id`,
    sec.`class_teacher_id`,
    CURRENT_DATE AS check_date,
    (SELECT COUNT(*) FROM `student_enrollments` en
       WHERE en.`section_id` = sec.`id` AND en.`status` = 'active') AS expected_students,
    (SELECT COUNT(*) FROM `student_attendance` a
       WHERE a.`section_id` = sec.`id` AND a.`attendance_date` = CURRENT_DATE) AS marked_students
FROM `sections` sec
INNER JOIN `school_classes` c      ON c.`id` = sec.`class_id`
INNER JOIN `academic_sessions` ses ON ses.`id` = sec.`academic_session_id` AND ses.`is_current` = 1
WHERE sec.`is_active` = 1;

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0011_views.sql', 1);
