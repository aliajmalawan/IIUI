# 04 — URL Structure, Routing & API

## 1. Routing Design

### Route definition shape
```
Router::get('/news/{slug}', [NewsController::class, 'show'])
      ->name('news.show')
      ->where('slug', '[a-z0-9\-]+');
```

### Groups
```
Router::group([
    'prefix'     => 'admin',
    'middleware' => ['auth', 'active.session', 'audit'],
    'namespace'  => 'Admin',
], function () { … });
```

### Rules
- **Every route is named.** Views and redirects use `route('admin.students.edit', ['id' => $id])` — a URL string never appears in a template.
- **Parameter constraints are mandatory**: `{id:\d+}`, `{slug:[a-z0-9\-]+}`, `{year:\d{4}}`. This turns a malformed URL into a clean 404 before any code runs.
- **HTTP verbs are honoured.** HTML forms use a `_method` hidden field for `PUT`/`PATCH`/`DELETE`.
- **Middleware is declared on the route, never checked inside the controller.**
- Routes compile to `storage/cache/routes.php` in production.

### Resource route convention
| Verb | URI | Action | Name |
|---|---|---|---|
| GET | `/admin/students` | `index` | `admin.students.index` |
| GET | `/admin/students/create` | `create` | `admin.students.create` |
| POST | `/admin/students` | `store` | `admin.students.store` |
| GET | `/admin/students/{id}` | `show` | `admin.students.show` |
| GET | `/admin/students/{id}/edit` | `edit` | `admin.students.edit` |
| PUT | `/admin/students/{id}` | `update` | `admin.students.update` |
| DELETE | `/admin/students/{id}` | `destroy` | `admin.students.destroy` |

---

## 2. Public Website URLs

Clean, human-readable, SEO-friendly. **No `.php`, no query-string IDs, no `index.php` in any public URL.**

| URL | Page |
|---|---|
| `/` | Homepage |
| `/about` | About the campus |
| `/about/message-from-principal` | Principal's message |
| `/about/vision-mission` | Vision & mission |
| `/about/campus-facilities` | Facilities |
| `/academics` | Academics overview |
| `/academics/school` · `/academics/college` | Wing pages |
| `/academics/programs/{slug}` | Program detail (FSc Pre-Medical, ICS…) |
| `/academics/curriculum` | Curriculum |
| `/academics/calendar` | Academic calendar |
| `/admissions` | Admission info & criteria |
| `/admissions/apply` | Online application form |
| `/admissions/status` | Track application by number |
| `/admissions/fee-structure` | Public fee structure |
| `/faculty` | Faculty directory |
| `/faculty/{slug}` | Faculty profile |
| `/news` · `/news/{slug}` | News listing / article |
| `/news/category/{slug}` | News by category |
| `/events` · `/events/{slug}` | Events |
| `/notices` · `/notices/{slug}` | Notice board |
| `/gallery` · `/gallery/{slug}` | Photo galleries |
| `/downloads` | Downloads centre |
| `/result` | Public result check (reg. no + DOB) |
| `/datesheet` | Published datesheets |
| `/contact` | Contact & map |
| `/page/{slug}` | Any CMS page (fallback route — matched last) |
| `/search?q=` | Site search |
| `/sitemap.xml` · `/robots.txt` · `/feed.xml` | SEO/system |

> **Ordering matters:** `/page/{slug}` and `/{slug}` catch-alls are registered **last**, so a CMS page named "admissions" can never shadow the real `/admissions` route.

---

## 3. Authentication URLs

| URL | Purpose |
|---|---|
| `GET/POST /login` | Login |
| `POST /logout` | Logout (POST only — a GET logout is CSRF-abusable) |
| `GET/POST /forgot-password` | Request reset link |
| `GET/POST /reset-password/{token}` | Set new password |
| `GET/POST /password/change` | Forced change on first login |
| `GET/POST /two-factor` | TOTP challenge |
| `GET /verify-email/{token}` | Email verification |

---

## 4. Admin Panel URLs — `/admin`

All behind `auth` + `permission` + `audit` middleware.

```
/admin                              dashboard
/admin/profile                      own profile & password

── Academic
/admin/sessions                     academic sessions (+/{id}/activate, /{id}/rollover)
/admin/classes                      classes
/admin/sections
/admin/subjects
/admin/streams
/admin/curriculum                   class-subject mapping
/admin/grade-scales
/admin/holidays
/admin/timetable                    (+/section/{id})

── Students
/admin/students                     list, create, show, edit, destroy
/admin/students/{id}/ledger         attendance + result + fee combined
/admin/students/{id}/documents
/admin/students/{id}/id-card
/admin/students/import              bulk import (+/preview, /process, /template)
/admin/students/promote             bulk promotion (+/preview)
/admin/students/transfer
/admin/students/certificates        leaving / character certificate
/admin/guardians

── Admissions
/admin/admissions                   applications list
/admin/admissions/{id}              review
/admin/admissions/{id}/status       update stage
/admin/admissions/{id}/convert      → student
/admin/admissions/test              entry test scheduling & scores
/admin/admissions/merit-list

── Staff
/admin/staff                        list/create/show/edit
/admin/staff/{id}/assignments       subjects & classes
/admin/departments
/admin/designations

── Attendance
/admin/attendance                   marking sheet (?section=&date=)
/admin/attendance/store             AJAX bulk save
/admin/attendance/register          monthly register
/admin/attendance/report            percentage & shortage
/admin/attendance/staff
/admin/leaves                       (+/{id}/approve, /{id}/reject)

── Examination
/admin/exam-terms                   (+/{id}/publish-result)
/admin/exams
/admin/datesheets
/admin/marks                        entry sheet (?exam=)
/admin/marks/store                  AJAX save
/admin/marks/{id}/verify
/admin/results/compile              (?term=&class=)
/admin/results/consolidated
/admin/results/{studentId}/card
/admin/results/merit-list

── Finance
/admin/fee-categories
/admin/fee-structures
/admin/discounts
/admin/challans                     list, show, print
/admin/challans/generate            bulk (+/preview)
/admin/challans/{id}/cancel
/admin/payments                     collect, receipt, reverse
/admin/payments/daily-report
/admin/defaulters                   (+/remind)
/admin/expenses
/admin/expense-categories
/admin/payroll                      (+/generate, /{id}/payslip)

── CMS
/admin/cms/pages
/admin/cms/posts                    (+/categories, /tags)
/admin/cms/events
/admin/cms/notices
/admin/cms/galleries                (+/{id}/images)
/admin/cms/sliders
/admin/cms/downloads
/admin/cms/menus                    (+/{id}/items)
/admin/cms/media                    media library
/admin/cms/testimonials
/admin/cms/faqs
/admin/cms/contact-messages
/admin/cms/seo                      meta defaults, redirects, sitemap

── Communication
/admin/notifications
/admin/messages
/admin/announcements                bulk send
/admin/email-templates
/admin/logs/email  ·  /admin/logs/sms

── Reports
/admin/reports                      index
/admin/reports/students | attendance | results | fees | expenses | staff | admissions

── System
/admin/users  ·  /admin/roles  ·  /admin/permissions
/admin/settings/{group}
/admin/audit-logs
/admin/backups                      (+/create, /{id}/download, /{id}/restore)
/admin/system/cron  ·  /admin/system/logs  ·  /admin/system/cache
```

---

## 5. Portal URLs — `/portal`

```
/portal                             role-aware redirect

── Student
/portal/student
/portal/student/profile
/portal/student/attendance          (?month=&year=)
/portal/student/results             (+/{termId}, /{termId}/card)
/portal/student/fees                (+/{challanId}/download)
/portal/student/timetable
/portal/student/notices
/portal/student/downloads

── Parent
/portal/parent
/portal/parent/children
/portal/parent/child/{studentId}/attendance | results | fees
/portal/parent/leave-request
/portal/parent/notices

── Teacher
/portal/teacher
/portal/teacher/classes
/portal/teacher/attendance          (+/mark)
/portal/teacher/marks               (+/entry)
/portal/teacher/timetable
/portal/teacher/leaves
/portal/teacher/messages
```

---

## 6. API Design — `/api/v1`

### Two distinct API surfaces

| Surface | Base | Auth | Consumer |
|---|---|---|---|
| **Internal AJAX** | `/api/v1/internal/*` | Session cookie + CSRF header | The admin panel & portal's own JavaScript |
| **Public/Mobile API** | `/api/v1/*` | `Authorization: Bearer <token>` | Future mobile app, integrations |

Keeping these separate matters: internal endpoints may rely on session state; public endpoints must be stateless, rate-limited, and versioned as a contract.

### Standard response envelope

Success:
```json
{
  "success": true,
  "message": "Students retrieved successfully",
  "data": [ … ],
  "meta": { "page": 1, "per_page": 25, "total": 486, "last_page": 20 }
}
```

Error:
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "father_cnic": ["CNIC must be 13 digits."],
    "date_of_birth": ["Date of birth must be a valid past date."]
  },
  "code": "VALIDATION_FAILED"
}
```

### Status codes

| Code | Used when |
|---|---|
| `200` | Successful read / update |
| `201` | Resource created |
| `204` | Deleted, no body |
| `400` | Malformed request |
| `401` | Missing/invalid token or session |
| `403` | Authenticated but not permitted |
| `404` | Resource not found (also returned instead of 403 for out-of-scope records, so IDs can't be enumerated) |
| `409` | Conflict (duplicate roll no, already marked attendance) |
| `422` | Validation failed |
| `429` | Rate limit exceeded |
| `500` | Unhandled server error (generic message only) |

### Public API endpoints (v1)

```
POST   /api/v1/auth/login                 → token
POST   /api/v1/auth/logout
POST   /api/v1/auth/refresh
GET    /api/v1/auth/me

GET    /api/v1/students                   scoped by role
GET    /api/v1/students/{uuid}
GET    /api/v1/students/{uuid}/attendance ?from=&to=
GET    /api/v1/students/{uuid}/results
GET    /api/v1/students/{uuid}/fees
GET    /api/v1/students/{uuid}/timetable

GET    /api/v1/notices                    ?audience=
GET    /api/v1/events
GET    /api/v1/news
GET    /api/v1/gallery

POST   /api/v1/attendance                 teacher: bulk mark
GET    /api/v1/teacher/classes
POST   /api/v1/marks                      teacher: bulk entry

GET    /api/v1/results/check              public: ?reg_no=&dob=
POST   /api/v1/admissions/apply           public: submit application
GET    /api/v1/admissions/status/{appNo}  public: track
POST   /api/v1/contact                    public: contact form
```

### Internal AJAX endpoints

```
GET    /api/v1/internal/search/global          ?q=
GET    /api/v1/internal/classes/{id}/sections  dependent dropdown
GET    /api/v1/internal/sections/{id}/students
GET    /api/v1/internal/students/lookup        ?reg_no=
POST   /api/v1/internal/attendance/bulk
POST   /api/v1/internal/marks/bulk
GET    /api/v1/internal/fees/preview           challan dry-run
GET    /api/v1/internal/dashboard/{widget}     Chart.js data
POST   /api/v1/internal/media/upload           CropperJS output
DELETE /api/v1/internal/media/{id}
GET    /api/v1/internal/notifications/unread
POST   /api/v1/internal/notifications/{id}/read
POST   /api/v1/internal/cms/menu/reorder       drag-drop order
POST   /api/v1/internal/validate/unique        live field validation
```

### API rules
- **UUIDs, not sequential IDs**, in every public API path — no enumeration.
- Pagination: `?page=&per_page=` (default 25, hard max 100).
- Filtering: `?class_id=&status=&search=`; sorting: `?sort=-created_at` (leading `-` = DESC), against an **allowlist** of sortable columns.
- Sparse fields: `?fields=id,name,roll_no`.
- Rate limits: `60/min` authenticated, `20/min` public, `5/min` on login and application submission. Returned via `X-RateLimit-*` headers.
- Every request carries `X-Request-Id` for log correlation.
- Breaking changes ⇒ `/api/v2`. `v1` is never mutated in place.

---

## 7. Middleware Stacks per Surface

| Surface | Stack (in order) |
|---|---|
| Public web | `SecurityHeaders → Maintenance → Throttle → Session → Csrf` |
| Auth pages | `SecurityHeaders → Throttle(5/min) → Session → Csrf → Guest` |
| Admin | `SecurityHeaders → Maintenance → Session → Csrf → Authenticate → Role → Permission → ActiveSession → Audit` |
| Portal | `SecurityHeaders → Maintenance → Session → Csrf → Authenticate → Role → Scope` |
| Internal API | `SecurityHeaders → Session → Csrf(header) → Authenticate → Permission → Json` |
| Public API | `SecurityHeaders → Throttle → ApiToken → Ability → Json` |

---

## 8. URL & Slug Rules

- Lowercase, hyphen-separated, no underscores, no trailing slash (enforced by a 301 redirect).
- Slugs auto-generated from titles, uniqueness-checked with a `-2`, `-3` suffix, manually overridable.
- Changing a published slug automatically writes a **301 redirect** into `seo_redirects` — old links from Facebook posts and printed prospectuses keep working.
- Dates in URLs use `YYYY-MM-DD`.
- No URL ever exposes a database ID for a public entity; admin URLs may, since they're behind authorization.
