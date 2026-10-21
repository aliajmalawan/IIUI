# 07 — Build Phases, Scalability & Future Expansion

## 1. Build Sequence

Each phase produces something demonstrable. The order is driven by dependency, not by what's easiest — the foundation phases are unglamorous but everything else stands on them.

| Phase | Deliverable | Depends on | Why here |
|---|---|---|---|
| **1** | **Architecture & design docs** ✅ | — | Decide before building |
| **2** | Core framework: front controller, router, container, PDO, Request/Response, View, Session, Validator, Logger, exception handler, `.env`, base layouts | 1 | Nothing works without the engine |
| **3** | Database: migrations, seeders, migrate/seed scripts, full schema live | 2 | Every module needs tables |
| **4** | Auth & RBAC: login, reset, roles, permissions, middleware, policies, audit log, admin shell (sidebar/topbar/dashboard skeleton) | 2, 3 | Every admin screen sits inside this shell |
| **5** | Academic setup: sessions, classes, sections, subjects, streams, curriculum, grade scales, holidays | 4 | Student records need somewhere to live |
| **6** | Student management: CRUD, guardians, documents, enrollment, photo/CropperJS, import, promotion, ID cards | 5 | The core entity |
| **7** | Admission: public form, pipeline, test & merit, convert-to-student | 6 | Feeds students |
| **8** | Staff & HR: staff CRUD, departments, designations, teaching assignments | 4 | Needed by attendance & exams |
| **9** | Attendance: marking sheet, staff attendance, leave workflow, registers, reports, parent alerts | 6, 8 | Daily-use screen |
| **10** | Examination: terms, exams, datesheets, marks entry, verification, grading, result compilation, cards, publication | 6, 5 | The academic payoff |
| **11** | Fee & finance: categories, structures, discounts, challan generation, collection, fines, defaulters, expenses | 6 | The operational payoff |
| **12** | Website CMS: pages, posts, events, notices, galleries, sliders, menus, media, settings, SEO | 4 | Public face |
| **13** | Public website front-end: all public routes, responsive templates, Swiper/AOS, contact, result check | 12 | Consumes the CMS |
| **14** | Portals: student, parent, teacher | 9, 10, 11 | Consumes everything above |
| **15** | Communication: notifications, PHPMailer templates, queued jobs, SMS abstraction, announcements | 4 | Cross-cutting |
| **16** | Reports & exports: all reports, Chart.js dashboards, CSV/Excel/PDF | 9, 10, 11 | Needs the data |
| **17** | Optional modules: timetable, library, transport, payroll | 5, 6, 8 | Value-add |
| **18** | Hardening & launch: security checklist, performance pass, backups, cron, error pages, UAT, deployment | all | |

> Phases 5–11 each end with a working, demonstrable admin module. That cadence is deliberate — it keeps the client seeing progress instead of waiting six weeks for "the framework".

---

## 2. Scalability

### Expected load (realistic for a single campus)

| Metric | Year 1 | Year 5 |
|---|---|---|
| Students | ~1,200 | ~2,500 |
| Staff | ~90 | ~180 |
| Concurrent users (peak) | ~60 | ~150 |
| `student_attendance` rows/year | ~250k | ~500k |
| `marks` rows/year | ~60k | ~130k |
| `fee_challans` rows/year | ~14k | ~30k |
| Total DB after 5 years | — | ~3–5 GB |

**This is comfortably within a single MySQL instance.** The architecture is not over-engineered for imaginary scale — it's designed so the *known* growth points don't require a rewrite.

### Where it will actually strain, and the planned answer

| Pressure point | Symptom | Response |
|---|---|---|
| Attendance table growth | Slow monthly reports | Composite indexes now → yearly partitioning by `academic_session_id` → summary table `attendance_monthly_summary` |
| Dashboard aggregates | Slow admin login | Cache widget queries (5 min TTL); nightly cron pre-computes heavy figures |
| Bulk challan generation (2,500 students) | Request timeout | Already designed as a queued job with a dry-run preview — never a synchronous request |
| Result compilation & positions | Slow publish | Batch computation into `result_summaries`, run as a job, not on page view |
| Media library growth | Slow uploads page, big backups | Paginated + folder-scoped; move to object storage behind the existing `disk` column |
| Bulk email/SMS | Blocked requests | `jobs` table drained by cron — already the design |
| Public site traffic spikes (result day) | Site slow for everyone | Full-page cache for public routes, separate rate limit on `/result`, static asset caching |

### Scaling path, in order of what we'd actually do

1. **Query & index tuning** — slow-query log driven. Almost always enough.
2. **Caching layer** — file cache today; swap `CacheInterface` to Redis (no call-site changes — this is why the interface exists).
3. **Read replica** — point reporting/read repositories at a replica.
4. **Object storage** — move `storage/app/uploads` to S3-compatible storage via the `disk` column already in `media`.
5. **Queue worker** — replace cron-drained `jobs` with a supervised long-running worker.
6. **Horizontal web tier** — sessions already move to DB/Redis; app servers become stateless.
7. **Archival** — move sessions older than 5 years to archive tables; reports read the union.

---

## 3. Future Expansion

Each of these has a **seam already present in the design** — that's the point of listing them now rather than later.

| # | Feature | Seam already in place |
|---|---|---|
| F01 | **Multi-campus** | `campuses` table exists; `campus_id` on classes; add a campus scope to repositories and a campus switcher |
| F02 | **Urdu / RTL interface** | `name_ur` columns; `__()` translation helper; a `translations` table and an RTL stylesheet complete it |
| F03 | **Mobile app (Android/iOS)** | `/api/v1` + `personal_access_tokens` are designed for exactly this consumer |
| F04 | **Online fee payment** (1Link, JazzCash, EasyPaisa, bank API) | `fee_payments.method` already includes `online`; add a gateway adapter interface + webhook route + reconciliation |
| F05 | **SMS gateway** (Telenor, Jazz, Twilio) | `sms_templates`/`sms_logs` tables and the queued-job path already exist; only the provider adapter is missing |
| F06 | **Biometric / RFID attendance** | `student_attendance.check_in` and a device-import endpoint; attendance service is already device-agnostic |
| F07 | **LMS: assignments, materials, online classes** | New module hanging off `class_subjects` and `staff_subjects` |
| F08 | **Online examinations / MCQ tests** | Question bank + attempts tables feeding the existing `marks` pipeline |
| F09 | **Alumni portal** | `students.status = graduated` already retained; alumni is a role + a portal surface |
| F10 | **Inventory & asset management** | Standalone module; reuses the same CRUD and audit scaffolding |
| F11 | **Hostel management** | Mirrors the transport module's shape |
| F12 | **Parent–teacher meeting scheduling** | Uses `timetables` + `notifications` |
| F13 | **PWA / offline attendance** | Attendance is already an AJAX bulk-save; add a service worker + local queue |
| F14 | **Two-way parent messaging** | `messages` table exists; add a parent-facing thread UI |
| F15 | **Automated transcripts** | `result_summaries` retained per session is precisely the data a transcript needs |
| F16 | **Google Workspace / Microsoft SSO** | `AuthService` is provider-agnostic behind the login controller |
| F17 | **Data warehouse / BI dashboards** | Read replica + the `transactions` unified ledger table |
| F18 | **WhatsApp Business notifications** | Same `NotificationService` fan-out as email/SMS |

---

## 4. Deployment & Operations

### Environments
| | Local | Staging | Production |
|---|---|---|---|
| Host | XAMPP (`iiui.test`) | Subdomain, HTTP auth | Live domain |
| Data | Demo seed | Anonymised copy | Real |
| Debug | On | On | **Off** |
| Backups | None | Weekly | Nightly + offsite |

### Deployment steps
1. Tag release on `main`
2. Maintenance mode on
3. Pull code, `composer install --no-dev --optimize-autoloader`
4. **Backup database** (pre-migration snapshot)
5. Run migrations
6. Clear route/config/view cache; rebuild production caches
7. Smoke test: login, one admin list, one public page
8. Maintenance mode off
9. Watch error log for 30 minutes

Rollback: restore the pre-migration snapshot and redeploy the previous tag.

### Cron schedule
| Time | Job |
|---|---|
| `*/5 * * * *` | Drain `jobs` queue (email/SMS) |
| `0 1 * * *` | Mark overdue challans, apply fines |
| `0 2 * * *` | Database backup |
| `0 3 * * *` | Prune logs, expired sessions, old page views |
| `0 6 * * *` | Absence alerts to parents (previous day) |
| `0 7 1 * *` | Generate monthly fee challans |
| `0 4 * * 0` | Rebuild sitemap, weekly file backup |
| `0 8 * * *` | Contract-expiry & fee-due reminder digest to admin |

### Monitoring
Uptime check on `/` · daily error-log digest · failed-job alert · backup success/failure alert · disk-space alert at 80% · slow-query log reviewed weekly.

---

## 5. Risks & Mitigations

| Risk | Mitigation |
|---|---|
| Scope creep across 18 phases | Phase gate: each phase signed off before the next starts |
| Data-entry burden at go-live | Bulk import with dry-run preview built in Phase 6, before it's needed |
| Staff resistance to a new system | Role-specific dashboards, keyboard-optimised daily screens, training in Phase 18 |
| Wrong fee/grading rules encoded | Both are **data**, not code — configurable per session without a deploy |
| Result-day traffic spike | Public caching + separate rate limit on `/result` |
| Single developer / bus factor | Documentation-first (this `docs/` set), conventional structure, no clever tricks |
| Backup that has never been restored | Restore drill is an explicit item on the launch checklist |
| PHP 8.2 vs 8.3 target drift | Code targets 8.2; a runtime upgrade stays forward-compatible |

---

## 6. Phase 1 — Deliverables Checklist

| Deliverable | Where |
|---|---|
| Folder structure | [01-architecture.md §3](01-architecture.md) |
| Project architecture | [01-architecture.md §1–2, 4–5](01-architecture.md) |
| Database planning & structure | [02-database.md §1–3](02-database.md) |
| Database relationships & ERD | [02-database.md §4–5](02-database.md) |
| Module planning & breakdown | [03-modules-roles.md §1–2](03-modules-roles.md) |
| User roles | [03-modules-roles.md §3–6](03-modules-roles.md) |
| URL structure & routing | [04-routing-api.md §1–5](04-routing-api.md) |
| API planning | [04-routing-api.md §6–7](04-routing-api.md) |
| Security architecture | [05-security.md](05-security.md) |
| Naming conventions | [06-standards.md §2](06-standards.md) |
| Coding standards | [06-standards.md §1, 3–9](06-standards.md) |
| Future scalability & expansion | [07-roadmap.md §2–3](07-roadmap.md) |

**Phase 1 is complete.** ✅ Next: Phase 2 — build the core framework.
