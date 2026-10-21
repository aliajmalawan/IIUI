# 03 — Modules, Roles & Permissions

## 1. Module Registry

Modules are declared in `app/Config/modules.php` and can be switched off without touching routes or views. Core modules cannot be disabled.

| # | Module | Type | Depends on | Phase |
|---|---|---|---|---|
| M01 | Authentication & Access Control | Core | — | 2 |
| M02 | Dashboard & Analytics | Core | M01 | 4 |
| M03 | Academic Setup | Core | M01 | 3 |
| M04 | Student Management | Core | M03 | 5 |
| M05 | Admission Management | Core | M04 | 6 |
| M06 | Staff / HR Management | Core | M01 | 7 |
| M07 | Attendance | Core | M04, M06 | 8 |
| M08 | Examination & Results | Core | M04, M03 | 9 |
| M09 | Fee & Finance | Core | M04 | 10 |
| M10 | Website CMS | Core | M01 | 11 |
| M11 | Communication & Notifications | Core | M01 | 12 |
| M12 | Reports & Exports | Core | M04–M09 | 13 |
| M13 | Timetable | Optional | M03, M06 | 14 |
| M14 | Library | Optional | M04, M06 | 15 |
| M15 | Transport | Optional | M04 | 15 |
| M16 | Payroll | Optional | M06, M07 | 16 |
| M17 | System & Settings | Core | M01 | 3 |

---

## 2. Module Breakdown

### M01 — Authentication & Access Control
Login (username / email / registration no), logout, remember-me, password reset by email token, forced password change on first login, optional TOTP 2FA for admin roles, brute-force lockout, session device list with remote revoke, role & permission management UI, per-user permission overrides, full audit trail.

**Key screens:** Login · Forgot/Reset Password · Users list · User form · Roles & Permissions matrix · Active Sessions · Audit Log viewer

---

### M02 — Dashboard & Analytics
A **different dashboard per role** — not one dashboard with hidden widgets.

| Role | Sees |
|---|---|
| Admin / Principal | Total students by class (Chart.js bar), today's attendance %, monthly fee collection vs target (line), outstanding dues, staff on leave, new admissions, recent audit events |
| Accountant | Today's collection, month-to-date, defaulters list, expense vs income, challan status donut |
| Teacher | My classes today, periods remaining, attendance not yet marked (actionable), marks entry pending, my leave balance |
| Class Teacher | + section attendance trend, students below 75% attendance |
| Student | Attendance %, latest result, fee status, timetable today, notices |
| Parent | Per-child cards: attendance, result, fee due, notices |

---

### M03 — Academic Setup
Academic sessions (create, activate, close, roll over) · Classes & wings · Sections with capacity and class teacher · Subjects · Streams (Pre-Medical / Pre-Engineering / ICS / Commerce / Arts) · Class–subject curriculum mapping with marks distribution · Grade scales · Holidays calendar.

**Critical flow — Session Rollover:** create next session → copy class/section/subject structure → copy fee structures with optional % increase → run bulk promotion → set new session current. All inside one transaction with a pre-run snapshot.

---

### M04 — Student Management
Student CRUD with photo upload + CropperJS · Registration/admission number auto-generation with configurable format · Guardian linking (many-to-many, siblings share a guardian) · Document upload & verification · Enrollment history timeline · Bulk import from Excel/CSV with dry-run preview and per-row error report · Bulk promotion / detention · Section transfer · Leaving certificate & character certificate generation · ID card generation (print layout) · Student ledger (attendance + result + fee in one view) · Advanced filters (class, section, session, status, gender, transport, fee status).

---

### M05 — Admission Management
Public online application form (multi-step, AJAX-validated) · Application number + tracking page · Document upload · Admin review pipeline: `submitted → under_review → shortlisted → test_scheduled → test_taken → interview → admitted / rejected / waitlisted` · Entry-test scheduling, roll-number slips, score entry · Merit list generation & publication · **One-click convert to student** (creates `students` + `student_enrollments` + `user` + first challan in one transaction) · Email/SMS at each status change.

---

### M06 — Staff / HR Management
Staff CRUD · Departments & designations · Qualification and experience records · Document management · Subject/class teaching assignment · Contract expiry alerts · Public faculty profile toggle (feeds the website Faculty page) · Staff directory & org chart.

---

### M07 — Attendance
Daily marking sheet per section (single-screen, keyboard-navigable, AJAX-saved) · Mark-all-present shortcut with exceptions · Subject-wise attendance for college classes · Staff attendance · Holiday awareness (marking blocked on holidays) · Leave applications with approval workflow · Absent-student SMS/email to parents (queued) · Monthly register (printable) · Attendance percentage reports with a configurable shortage threshold (75%) · Attendance-based eligibility flag for exams.

---

### M08 — Examination & Results
Exam terms with weightage · Datesheet builder & publication · Exam-wise marks entry sheet (per class+subject, AJAX-saved, tab-navigable) · Absent handling · **Two-step approval:** teacher enters → exam controller verifies → row locks · Automatic grade & GPA from `grade_scales` · Result compilation: totals, percentage, grade, pass/fail, position in section and class · Consolidated result sheet (all subjects × all students) · Individual result card (print + PDF) · Term-weighted final result · Publish/unpublish control · **Public result check** by registration no + DOB · Toppers / merit list · Subject-wise performance charts.

---

### M09 — Fee & Finance
Fee categories & class-wise fee structures per session · Student-level discounts (sibling, merit, staff child, hardship, orphan) with approval · **Bulk challan generation** (monthly/quarterly, per class or whole campus) with dry-run preview · Challan print (3-part: Bank / School / Student copy) with barcode · Payment collection (cash, bank, cheque, online) with receipt printing · Partial payments · Late fine rules (fixed / per-day / percentage) with waiver + reason · Defaulter list & reminder campaign · Fee ledger per student · Expense management with voucher & approval · Income vs expense reports · Daily/monthly collection reports · Payment reversal as an append-only correcting entry.

---

### M10 — Website CMS
Pages (hierarchical, TinyMCE, SEO fields, templates) · News/Posts with categories & tags · Events with calendar view · Notices with audience targeting and publish windows · Photo galleries (bulk upload, drag-reorder) · Homepage sliders (Swiper) · Downloads centre · Faculty page (reads `staff`) · Menu builder (drag-drop, nested) · Media library with folders, search, CropperJS editing, auto-thumbnails · Site settings (logo, colors, contact, social, maps) · SEO: meta per entity, XML sitemap, `robots.txt`, Open Graph, canonical URLs, 301 redirect manager · Contact form with spam protection and reply-from-admin · Testimonials & FAQs · Page view analytics.

---

### M11 — Communication & Notifications
In-app notification bell with unread count · Email via PHPMailer with database-editable templates and variable substitution · SMS gateway abstraction (interface ready; provider pluggable) · Internal messaging (staff ↔ staff, teacher ↔ parent) · Bulk announcements to targeted audiences · Queued sending via `jobs` table drained by cron — **no bulk send ever blocks a web request** · Delivery logs with retry.

**Automatic triggers:** admission status change · absence marked · challan issued · payment received · result published · fee overdue · new notice for audience · password reset · account locked.

---

### M12 — Reports & Exports
Every report: on-screen (Bootstrap table) + print layout + CSV/Excel export + PDF where it's a formal document.

Student strength (class/section/gender) · Admission trends · Attendance register & shortage list · Consolidated & individual results · Merit lists · Fee collection (daily/monthly/annual) · Outstanding dues & ageing · Category-wise income & expense · Defaulter analysis · Staff attendance & leave · Payroll register · Custom date-range report builder.

---

### M13–M16 — Optional Modules
**Timetable** — period grid per section, teacher clash detection, teacher-wise view, printable.
**Library** — catalogue, issue/return, due reminders, fines, availability tracking.
**Transport** — routes, stops, vehicles, drivers, student allocation, route-wise fare into the fee module.
**Payroll** — salary structure, attendance-linked deductions, allowances, payslip generation, monthly register.

---

### M17 — System & Settings
General/site settings · Academic settings (session, roll number format, attendance threshold) · Fee settings (challan format, fine rules, due day) · Mail & SMS configuration with test-send · Role & permission editor · Backup & restore with download · Audit log viewer with filters · Cron/job monitor · Error log viewer · Cache clear · Maintenance mode with allowlisted IPs.

---

## 3. User Roles

| # | Role | Slug | Level | Scope |
|---|---|---|---|---|
| 1 | Super Admin | `super_admin` | 100 | Everything, including system config and role editing |
| 2 | Principal | `principal` | 90 | All academic, financial and HR data; no system config |
| 3 | Vice Principal | `vice_principal` | 80 | Academics, attendance, exams, staff; read-only finance |
| 4 | Academic Coordinator | `coordinator` | 70 | Assigned wing: classes, sections, timetable, exams |
| 5 | Exam Controller | `exam_controller` | 70 | Exams, datesheets, marks verification, result publication |
| 6 | Admission Officer | `admission_officer` | 60 | Admissions pipeline, student registration |
| 7 | Accountant | `accountant` | 60 | Fees, challans, payments, expenses, financial reports |
| 8 | Class Teacher | `class_teacher` | 50 | Own section: attendance, marks, student profiles, remarks |
| 9 | Teacher | `teacher` | 40 | Own assigned subjects/classes: attendance, marks entry |
| 10 | Librarian | `librarian` | 40 | Library module only |
| 11 | Transport Incharge | `transport_incharge` | 40 | Transport module only |
| 12 | Content Editor | `content_editor` | 40 | Website CMS only — **zero access to student/staff/finance data** |
| 13 | Receptionist | `receptionist` | 30 | Read-only directory, contact messages, visitor log |
| 14 | Student | `student` | 20 | Own records only |
| 15 | Parent / Guardian | `parent` | 20 | Linked children's records only |
| 16 | Guest | — | 0 | Public website only |

> A user may hold **multiple roles** (`user_roles`). Effective permissions = union of role permissions, plus per-user `grant`, minus per-user `deny`. **Deny always wins.**

---

## 4. Permission Model

**Slug format:** `module.action`

**Standard actions:** `view` · `create` · `edit` · `delete` · `export` · `approve` · `publish`
**Domain-specific actions:** `fees.collect` · `fees.waive_fine` · `marks.verify` · `results.publish` · `students.promote` · `settings.manage` · `backup.restore` · `session.switch`

### Permission Matrix (abridged — key modules)

Legend: **F** = full · **O** = own/scoped records only · **R** = read-only · **–** = no access

| Module | Super Admin | Principal | Coordinator | Exam Ctrl | Accountant | Class Teacher | Teacher | Content Editor | Student | Parent |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| Dashboard | F | F | F | F | F | F | F | F | O | O |
| Users & Roles | F | R | – | – | – | – | – | – | – | – |
| Academic Setup | F | F | F | R | – | R | R | – | – | – |
| Students | F | F | F | R | R | O | R | – | O | O |
| Admissions | F | F | R | – | R | – | – | – | – | – |
| Staff / HR | F | F | R | – | R | – | – | – | – | – |
| Attendance | F | F | F | – | – | **O** | **O** | – | O | O |
| Exams & Datesheets | F | F | F | **F** | – | R | R | – | R | R |
| Marks Entry | F | R | R | F | – | **O** | **O** | – | – | – |
| Marks Verify / Publish | F | F | – | **F** | – | – | – | – | – | – |
| Fee Structure | F | F | – | – | **F** | – | – | – | – | – |
| Challans & Collection | F | R | – | – | **F** | – | – | – | O | O |
| Expenses & Payroll | F | F | – | – | F | – | – | – | – | – |
| Website CMS | F | R | – | – | – | – | – | **F** | – | – |
| Notices | F | F | F | F | – | R | R | F | R | R |
| Reports | F | F | O | O | O | O | O | – | O | O |
| Settings | **F** | R | – | – | – | – | – | – | – | – |
| Audit Log | **F** | R | – | – | – | – | – | – | – | – |
| Backups | **F** | – | – | – | – | – | – | – | – | – |

---

## 5. Data Scoping — the part permissions alone can't solve

A permission answers *"may this user open the marks screen?"*. It does **not** answer *"may this user open **these** marks?"*. That second question is answered by **Policies + repository query scopes**, and it is enforced on every single record access.

| Role | Scope rule |
|---|---|
| Teacher | Only `students` in sections present in their `staff_subjects` rows for the active session |
| Class Teacher | Full access to own section; read-only elsewhere in their wing |
| Coordinator | Only classes in their assigned wing |
| Student | `students.id === auth()->student_id` |
| Parent | `student_id IN (SELECT student_id FROM student_guardians WHERE guardian_id = ?)` |
| Accountant | All financial records; student data limited to name, class, roll no, guardian contact |
| Content Editor | CMS tables only — never joined against `students` or `staff` |

**Enforced in two places, always:**
1. `Policy::check()` before the action runs — returns 403.
2. A `WHERE` clause injected by the repository scope — so even a crafted ID can't leak a row.

> Relying on only the first is the single most common authorization bug in school systems: hiding the Edit button while `POST /admin/students/999/update` still works. Both layers are mandatory.

---

## 6. Portal Experience by Role

**Student Portal** — dashboard · profile (view; edit request only) · attendance calendar with % · results per term + downloadable card · fee status & challan download · timetable · notices · downloads · change password.

**Parent Portal** — child switcher (siblings) · per-child attendance, results, fee ledger · pay-now / challan download · teacher contact · notices · leave application for child.

**Teacher Portal** — today's periods · mark attendance (only unmarked periods highlighted) · marks entry with save-progress · my classes & students · my attendance & leave · notices · internal messages.

---

## 7. Permission Seeding

`PermissionSeeder` generates the full slug list from a module × action definition array, then `RoleSeeder` attaches slugs per role. Adding a module later means adding one entry to that array and re-running the seeder — permissions are never hand-typed into the database, which is what keeps the matrix above and the actual system from drifting apart.
