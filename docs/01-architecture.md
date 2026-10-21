# 01 — Project Architecture

## 1. Architectural Style

**Layered MVC + Service/Repository**, running on a single front controller.

This is *not* raw procedural PHP with one file per page. It is a hand-rolled micro-framework that gives us framework discipline without framework weight — because the client stack mandates Core PHP.

```
HTTP Request
     │
     ▼
┌─────────────────────────────────────────────────────┐
│  public/index.php  ── FRONT CONTROLLER              │
│  bootstrap → env → error handler → session → router │
└─────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────┐
│  ROUTER          match method + URI → route         │
└─────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────┐
│  MIDDLEWARE PIPELINE                                │
│  Maintenance → Throttle → Session → CSRF →          │
│  Auth → Role → Permission → Scope                   │
└─────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────┐
│  CONTROLLER (thin)                                  │
│  validate input → call service → return response    │
└─────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────┐
│  SERVICE (business logic)                           │
│  rules, transactions, orchestration, events         │
└─────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────┐
│  REPOSITORY / MODEL   ← the ONLY layer writing SQL  │
└─────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────┐
│  PDO → MySQL                                        │
└─────────────────────────────────────────────────────┘
     │
     ▼
   VIEW (layout + partials)  ──or──  JSON Response
```

### Layer responsibilities — the contract

| Layer | May do | Must NOT do |
|---|---|---|
| **Controller** | Read request, validate, call one service, pick view/JSON | Write SQL, contain business rules, echo HTML |
| **Service** | Business rules, transactions, call multiple repositories, dispatch mail/notifications | Touch `$_POST`/`$_GET`, render views, write raw SQL |
| **Repository** | Build & execute queries, map rows to models | Contain business rules, validate, send mail |
| **Model** | Represent a row, casts, computed accessors | Query the database |
| **View** | Loop, escape, display | Query the database, contain `if` chains of business logic |
| **Middleware** | Allow / reject / redirect a request | Contain domain logic |

**One rule that keeps this honest:** if a class has both `PDO` and `$_POST` in it, the architecture has been violated.

---

## 2. Two Applications, One Codebase

The system is really three surfaces sharing one core:

| Surface | Path | Audience | Nature |
|---|---|---|---|
| **Public Website** | `/` | Anyone | CMS-driven, cached, SEO-critical, read-mostly |
| **Admin Panel** | `/admin` | Staff & management | Full CRUD, heavy authorization, audit-logged |
| **User Portal** | `/portal` | Students, Parents, Teachers | Read-mostly, self-scoped data only |
| **API** | `/api/v1` | Mobile app, AJAX, integrations | Stateless (token) + stateful (session AJAX) |

They share: Core, Models, Repositories, Services, Middleware.
They differ: Controllers, Views, Layouts, Routes, Middleware stacks.

This is the key structural decision — **it means a fee calculation is written once and behaves identically whether an accountant triggers it in `/admin`, a parent views it in `/portal`, or the mobile app fetches it from `/api`.**

---

## 3. Folder Structure

```
IIUI/
│
├── public/                          ← THE ONLY WEB-EXPOSED FOLDER
│   ├── index.php                    front controller (the only PHP entry point)
│   ├── .htaccess                    rewrite everything → index.php
│   ├── robots.txt
│   ├── favicon.ico
│   └── assets/
│       ├── css/
│       │   ├── app.css              public site styles
│       │   ├── admin.css            admin panel styles
│       │   └── print.css            challans, result cards, ID cards
│       ├── js/
│       │   ├── app.js               public site
│       │   ├── admin.js             admin shell (sidebar, datatables init)
│       │   ├── modules/             one file per module's JS
│       │   │   ├── students.js
│       │   │   ├── attendance.js
│       │   │   ├── fees.js
│       │   │   └── exams.js
│       │   └── lib/
│       │       ├── http.js          fetch/AJAX wrapper (CSRF auto-attach)
│       │       ├── notify.js        SweetAlert2 wrapper
│       │       └── validate.js      client-side validation helpers
│       ├── vendor/                  Bootstrap, jQuery, Chart.js, Swiper,
│       │                            SweetAlert2, AOS, TinyMCE, CropperJS
│       ├── img/
│       │   ├── logo/  icons/  placeholders/
│       └── fonts/
│
├── app/                             ← APPLICATION CODE (not web-reachable)
│   │
│   ├── Config/
│   │   ├── app.php                  name, url, timezone, locale, debug
│   │   ├── database.php             PDO DSN + options
│   │   ├── auth.php                 guards, session lifetime, lockout policy
│   │   ├── mail.php                 PHPMailer SMTP config
│   │   ├── upload.php               allowed mimes, size caps, paths
│   │   ├── security.php             CSP, headers, throttle limits
│   │   ├── cache.php
│   │   └── modules.php              module registry (enable/disable)
│   │
│   ├── Core/                        ← the mini-framework
│   │   ├── Application.php          bootstrap & container wiring
│   │   ├── Container.php            lightweight DI resolver
│   │   ├── Router.php               route matching & dispatch
│   │   ├── Route.php                single route value object
│   │   ├── Request.php              wraps $_GET/$_POST/$_FILES/headers
│   │   ├── Response.php             body, status, headers, redirect, json
│   │   ├── Controller.php           base controller
│   │   ├── Model.php                base model (casts, fillable, timestamps)
│   │   ├── Repository.php           base repository (CRUD, pagination)
│   │   ├── Database.php             PDO singleton, transactions
│   │   ├── QueryBuilder.php         safe fluent builder (binds only)
│   │   ├── View.php                 template renderer, layout inheritance
│   │   ├── Session.php              hardened session handler
│   │   ├── Auth.php                 login, logout, user(), can()
│   │   ├── Validator.php            rule engine
│   │   ├── Middleware.php           middleware contract
│   │   ├── Pipeline.php             middleware chaining
│   │   ├── Logger.php               PSR-3 style file logger
│   │   ├── Cache.php                file-based cache (Redis-swappable)
│   │   ├── Mailer.php               PHPMailer wrapper
│   │   ├── Uploader.php             validated file upload handler
│   │   ├── Paginator.php
│   │   ├── Hash.php                 password hashing / verification
│   │   ├── Csrf.php
│   │   ├── Event.php                simple event dispatcher
│   │   └── Exceptions/
│   │       ├── Handler.php          global exception → error page / JSON
│   │       ├── HttpException.php
│   │       ├── ValidationException.php
│   │       └── AuthorizationException.php
│   │
│   ├── Controllers/
│   │   ├── Web/                     public website
│   │   │   ├── HomeController.php
│   │   │   ├── PageController.php
│   │   │   ├── NewsController.php
│   │   │   ├── EventController.php
│   │   │   ├── GalleryController.php
│   │   │   ├── NoticeController.php
│   │   │   ├── FacultyController.php
│   │   │   ├── AdmissionController.php
│   │   │   ├── ResultController.php      public result check
│   │   │   ├── DownloadController.php
│   │   │   └── ContactController.php
│   │   ├── Auth/
│   │   │   ├── LoginController.php
│   │   │   ├── LogoutController.php
│   │   │   ├── PasswordResetController.php
│   │   │   └── TwoFactorController.php
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── StudentController.php
│   │   │   ├── EnrollmentController.php
│   │   │   ├── GuardianController.php
│   │   │   ├── StaffController.php
│   │   │   ├── ClassController.php
│   │   │   ├── SectionController.php
│   │   │   ├── SubjectController.php
│   │   │   ├── AttendanceController.php
│   │   │   ├── ExamController.php
│   │   │   ├── MarkController.php
│   │   │   ├── ResultController.php
│   │   │   ├── FeeStructureController.php
│   │   │   ├── ChallanController.php
│   │   │   ├── PaymentController.php
│   │   │   ├── ExpenseController.php
│   │   │   ├── PayrollController.php
│   │   │   ├── LibraryController.php
│   │   │   ├── TransportController.php
│   │   │   ├── ReportController.php
│   │   │   ├── UserController.php
│   │   │   ├── RoleController.php
│   │   │   ├── SettingController.php
│   │   │   ├── BackupController.php
│   │   │   ├── AuditLogController.php
│   │   │   └── Cms/
│   │   │       ├── PageController.php
│   │   │       ├── PostController.php
│   │   │       ├── SliderController.php
│   │   │       ├── GalleryController.php
│   │   │       ├── MenuController.php
│   │   │       ├── MediaController.php
│   │   │       └── SeoController.php
│   │   ├── Portal/
│   │   │   ├── Student/  (Dashboard, Attendance, Result, Fee, Timetable)
│   │   │   ├── Parent/   (Dashboard, Children, Attendance, Fee, Result)
│   │   │   └── Teacher/  (Dashboard, MyClasses, Attendance, Marks)
│   │   └── Api/
│   │       └── V1/       (Auth, Student, Attendance, Result, Fee, Notice)
│   │
│   ├── Middleware/
│   │   ├── AuthenticateMiddleware.php
│   │   ├── GuestMiddleware.php
│   │   ├── RoleMiddleware.php
│   │   ├── PermissionMiddleware.php
│   │   ├── CsrfMiddleware.php
│   │   ├── ThrottleMiddleware.php
│   │   ├── SecurityHeadersMiddleware.php
│   │   ├── MaintenanceMiddleware.php
│   │   ├── ActiveSessionMiddleware.php     enforces current academic session
│   │   ├── AuditMiddleware.php
│   │   └── ApiTokenMiddleware.php
│   │
│   ├── Models/                      one class per table
│   │   ├── User.php  Role.php  Permission.php
│   │   ├── Student.php  Enrollment.php  Guardian.php
│   │   ├── Staff.php  Department.php  Designation.php
│   │   ├── SchoolClass.php  Section.php  Subject.php  Stream.php
│   │   ├── AcademicSession.php
│   │   ├── Attendance.php  Holiday.php  LeaveApplication.php
│   │   ├── ExamTerm.php  Exam.php  Mark.php  ResultSummary.php  GradeScale.php
│   │   ├── FeeCategory.php  FeeStructure.php  Challan.php  Payment.php
│   │   ├── Expense.php  Payroll.php
│   │   ├── Page.php  Post.php  Event.php  Notice.php  Gallery.php
│   │   ├── Slider.php  Menu.php  Media.php  Setting.php
│   │   └── AuditLog.php  Notification.php
│   │
│   ├── Repositories/                one per aggregate, mirrors Models
│   │   ├── Contracts/               interfaces (swap impl for tests)
│   │   └── *Repository.php
│   │
│   ├── Services/                    business logic
│   │   ├── AuthService.php
│   │   ├── StudentService.php
│   │   ├── AdmissionService.php
│   │   ├── EnrollmentService.php    admission, promotion, transfer
│   │   ├── AttendanceService.php
│   │   ├── ExamService.php
│   │   ├── ResultService.php        grading, position, pass/fail
│   │   ├── FeeService.php           structure → challan generation
│   │   ├── PaymentService.php       collection, fines, receipts
│   │   ├── PayrollService.php
│   │   ├── ReportService.php
│   │   ├── NotificationService.php  in-app + email + SMS fan-out
│   │   ├── MediaService.php         upload, crop, thumbnail, optimize
│   │   ├── CmsService.php
│   │   ├── SeoService.php           sitemap, meta, redirects
│   │   ├── BackupService.php
│   │   └── AuditService.php
│   │
│   ├── Requests/                    validation rule sets per action
│   │   ├── StoreStudentRequest.php
│   │   ├── UpdateStudentRequest.php
│   │   ├── MarkAttendanceRequest.php
│   │   └── ...
│   │
│   ├── Policies/                    "can this user touch THIS record?"
│   │   ├── StudentPolicy.php
│   │   ├── MarkPolicy.php
│   │   └── ChallanPolicy.php
│   │
│   ├── Enums/
│   │   ├── AttendanceStatus.php     PRESENT | ABSENT | LATE | LEAVE | HALF_DAY
│   │   ├── ChallanStatus.php        UNPAID | PARTIAL | PAID | OVERDUE | CANCELLED
│   │   ├── StudentStatus.php        ACTIVE | GRADUATED | LEFT | SUSPENDED
│   │   ├── PublishStatus.php        DRAFT | PUBLISHED | SCHEDULED | ARCHIVED
│   │   └── Gender.php  PaymentMethod.php  UserStatus.php
│   │
│   ├── Helpers/
│   │   ├── functions.php            e(), asset(), route(), old(), __()
│   │   ├── DateHelper.php           academic dates, Hijri display
│   │   ├── NumberHelper.php         currency, words (for challans)
│   │   ├── StringHelper.php         slug, mask, initials
│   │   └── FileHelper.php
│   │
│   └── Views/
│       ├── layouts/
│       │   ├── web.php              public site shell
│       │   ├── admin.php            admin shell (sidebar + topbar)
│       │   ├── portal.php
│       │   ├── auth.php             login screens
│       │   ├── print.php            challan / result card / ID card
│       │   └── mail.php             email wrapper
│       ├── partials/
│       │   ├── web/     header, footer, nav, breadcrumb, slider, cta
│       │   ├── admin/   sidebar, topbar, alerts, footer
│       │   └── ui/      card, modal, table, pagination, empty-state, form-*
│       ├── components/              reusable snippets w/ params
│       ├── web/                     public pages
│       ├── auth/
│       ├── admin/                   mirrors Controllers/Admin structure
│       ├── portal/
│       ├── emails/                  PHPMailer HTML templates
│       └── errors/                  403, 404, 419, 422, 500, 503
│
├── routes/
│   ├── web.php                      public routes
│   ├── auth.php                     login / logout / reset
│   ├── admin.php                    /admin/*
│   ├── portal.php                   /portal/*
│   └── api.php                      /api/v1/*
│
├── database/
│   ├── migrations/                  0001_create_users_table.sql … (ordered)
│   ├── seeders/                     roles, permissions, settings, demo data
│   ├── schema/
│   │   ├── schema.sql               full DDL snapshot
│   │   └── erd.md                   diagrams
│   └── backups/                     .gitignored
│
├── storage/                         ← NEVER web-reachable
│   ├── app/
│   │   ├── uploads/
│   │   │   ├── students/  staff/  documents/  media/  gallery/
│   │   └── generated/               challans, result PDFs, ID cards, exports
│   ├── cache/                       views, config, query cache
│   ├── logs/                        app-YYYY-MM-DD.log, error, audit, mail
│   ├── sessions/
│   └── temp/
│
├── scripts/                         CLI — run by cron, never by HTTP
│   ├── migrate.php
│   ├── seed.php
│   ├── generate-monthly-challans.php
│   ├── mark-overdue-challans.php
│   ├── send-queued-emails.php
│   ├── backup-database.php
│   ├── prune-logs.php
│   └── build-sitemap.php
│
├── tests/
│   ├── Unit/                        services, helpers, validators
│   ├── Feature/                     route → response
│   └── Fixtures/
│
├── docs/                            ← this documentation
├── vendor/                          Composer (PHPMailer, etc.)
├── .env                             secrets — gitignored
├── .env.example                     committed template
├── .gitignore
├── composer.json
└── README.md
```

### Why `public/` matters

Everything except `public/` is unreachable over HTTP. That single decision removes an entire class of vulnerabilities: no one can request `/app/Config/database.php`, no one can browse `/storage/logs/`, no one can execute an uploaded `.php` file.

**On XAMPP this needs one setup step.** Two options:

| Option | Setup | Use for |
|---|---|---|
| **A — Virtual host (recommended)** | `httpd-vhosts.conf` → `DocumentRoot "C:/xampp/htdocs/IIUI/public"`, hosts file → `127.0.0.1 iiui.test` | Development that mirrors production |
| **B — Root `.htaccess`** | `c:\xampp\htdocs\IIUI\.htaccess` rewrites all traffic into `public/` and hard-denies `app/`, `storage/`, `database/`, `.env` | Quick start, shared hosting |

Production **must** use Option A. Option B is a compatibility shim, not a security boundary.

---

## 4. Request Lifecycle — step by step

```
 1. .htaccess           any URI that isn't a real file → public/index.php
 2. index.php           define ROOT_PATH, require vendor/autoload.php
 3. Env::load()         parse .env into config
 4. Handler::register() set error/exception/shutdown handlers
 5. Application::boot() build container, register core services
 6. Database::connect() lazy PDO (ERRMODE_EXCEPTION, EMULATE_PREPARES=false)
 7. Session::start()    hardened cookie params, fingerprint check
 8. Request::capture()  normalize URI, method (incl. _method spoofing)
 9. Router::dispatch()  match → Route object (controller, action, middleware)
10. Pipeline::send()    run middleware stack in order
11. Controller@action   resolve dependencies from container, invoke
12. Request validation  Validator; on failure → 422 JSON or redirect+errors
13. Service call        business logic inside a DB transaction where needed
14. Repository          prepared statement → PDO → rows → hydrated Models
15. Response            View::render(layout, data)  OR  Response::json(...)
16. Middleware (after)  security headers, audit log write, response cache
17. Output              echo body, flush, close session write
```

**Failure paths:**

| Situation | Result |
|---|---|
| No route matched | `404` error view / JSON |
| Not logged in | Redirect `/login?redirect=…` (web) or `401` (API) |
| Logged in, lacks permission | `403` view / JSON |
| CSRF token missing or stale | `419` "page expired" |
| Validation failed | `302` back with old input + errors, or `422` JSON |
| Uncaught exception | Logged with trace → generic `500` page (never leaks trace in production) |

---

## 5. Core Components — design notes

### Router
- Route table built from `routes/*.php`; supports `GET POST PUT PATCH DELETE`.
- Parameters with constraints: `{id:\d+}`, `{slug:[a-z0-9\-]+}`.
- **Named routes** so URLs are never hardcoded in views: `route('admin.students.edit', ['id' => 12])`.
- Route **groups** with shared prefix + middleware.
- Compiled route cache in `storage/cache/routes.php` for production.

### Container
Constructor-injection resolver via Reflection, with explicit bindings for interfaces. Not a full PSR-11 implementation — just enough to keep controllers and services testable without `new` scattered everywhere.

### Database
Single PDO instance. Mandatory options:
`ERRMODE_EXCEPTION`, `EMULATE_PREPARES = false`, `DEFAULT_FETCH_MODE = FETCH_ASSOC`, `charset=utf8mb4`, `STRINGIFY_FETCHES = false`.
Transaction helper `Database::transaction(fn)` with automatic rollback — used by every multi-table write (admission, promotion, challan generation, payment posting).

### View
Plain PHP templates. Layout inheritance via section capture (`$this->section('content')`), partial includes, and a **mandatory** `e()` escape helper. Only rich-text fields sanitized by HTMLPurifier may bypass escaping.

### Validator
Declarative rules: `'father_cnic' => 'required|cnic|unique:students,father_cnic,{id}'`.
Rules needed for this domain: `cnic`, `phone_pk`, `b_form`, `roll_no`, `academic_date`, `marks_within:{exam}`.

### Cache
File-driven behind a `CacheInterface`. Caches: site settings, menus, published pages, dashboard aggregates. Swappable to Redis later with zero call-site changes — that interface is the whole reason it exists.

---

## 6. Multi-Session (Academic Year) Design

The most important non-obvious decision in this system.

Every academic record (`enrollments`, `attendance`, `exams`, `marks`, `challans`, `fee_structures`, `timetables`) carries `academic_session_id`.

- One session is flagged `is_current = 1`.
- `ActiveSessionMiddleware` resolves the working session per request (default: current; admins may switch via a session-scoped selector).
- Repositories auto-scope queries to the active session unless explicitly overridden.

**Payoff:** year-end promotion becomes a data operation, not a database wipe. Historical results, old challans, and past attendance stay queryable forever — which is exactly what a school needs for transcripts, leaving certificates, and audits.

---

## 7. Environments

| | Development | Production |
|---|---|---|
| Entry | `iiui.test` vhost | domain vhost, HTTPS forced |
| `APP_DEBUG` | `true` | **`false`** |
| Errors | Displayed | Logged only, generic 500 page |
| Assets | Unminified | Minified + versioned (`?v=hash`) |
| Cache | Off | Routes, config, views, queries cached |
| Mail | Log driver / Mailtrap | Real SMTP |
| Backups | Manual | Nightly cron + offsite copy |
