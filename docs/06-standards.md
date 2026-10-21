# 06 — Coding Standards & Naming Conventions

> These are enforceable rules, not preferences. Consistency is what lets a second developer read module 14 having only ever seen module 3.

---

## 1. PHP Standards

**Base:** PSR-12 (style) · PSR-4 (autoloading) · PSR-3-shaped logging interface.

```php
<?php

declare(strict_types=1);          // first line of EVERY PHP file

namespace App\Services;

use App\Repositories\StudentRepository;
use App\Core\Database;
```

### Hard rules

| Rule | Why |
|---|---|
| `declare(strict_types=1);` in every file | Silent type juggling is where school-fee bugs hide |
| Type hints on **all** parameters and return types | `function calculate(int $marks, float $total): float` |
| `===` / `!==` always; `==` never | `'0' == false` has cost people real money |
| No closing `?>` in PHP-only files | Prevents stray-whitespace "headers already sent" |
| 4 spaces, never tabs · LF endings · UTF-8 without BOM | |
| Single quotes unless interpolating | |
| One class per file, filename = class name | PSR-4 requirement |
| Max line length 120 (soft), 80 preferred for readability | |
| Trailing commas in multi-line arrays and argument lists | Cleaner diffs |

### Complexity limits

| Unit | Limit |
|---|---|
| Method length | 40 lines (hard stop: extract) |
| Class length | 300 lines |
| Method parameters | 4 (beyond that, pass a DTO/array) |
| Nesting depth | 3 (use early returns / guard clauses) |
| Cyclomatic complexity | 10 |

### Structure rules
- **Controllers are thin** — validate, call one service, return a response. If a controller has an `if` about business rules, it belongs in a service.
- **No SQL outside `Repositories/`.** Not in controllers, not in services, never in views.
- **No `$_POST` / `$_GET` / `$_SESSION` outside `Core/`** — everything goes through `Request` and `Session`.
- **No `echo` outside views.**
- **No business logic in views** — a view may loop and format; it may not decide.
- Every public method has a docblock when its purpose isn't obvious from its signature; comments explain **why**, never **what**.

### Error handling
```php
// Throw domain exceptions; never return false/null to signal failure.
throw new ValidationException($errors);
throw new AuthorizationException('Cannot edit marks after verification.');
```
- Multi-table writes are wrapped in `Database::transaction()`. No exceptions.
- Never `@` to suppress errors. Never a bare `catch (Exception $e) {}`.
- User-facing messages are safe and generic; the trace goes to the log.

---

## 2. Naming Conventions

### PHP

| Element | Convention | Example |
|---|---|---|
| Class / Interface / Enum / Trait | `PascalCase` | `StudentRepository`, `AttendanceStatus` |
| Interface | `PascalCase` + `Interface` | `CacheInterface` |
| Abstract class | `Abstract` + `PascalCase` | `AbstractRepository` |
| Trait | `PascalCase` + `Trait` | `SoftDeletesTrait` |
| Method / function | `camelCase`, verb-first | `generateChallan()`, `hasOverdueFees()` |
| Variable | `camelCase` | `$studentId`, `$totalPayable` |
| Property | `camelCase` | `private PDO $connection;` |
| Constant | `UPPER_SNAKE_CASE` | `MAX_UPLOAD_SIZE` |
| Enum case | `UPPER_SNAKE_CASE` | `AttendanceStatus::HALF_DAY` |
| Boolean method | `is/has/can/should` prefix | `isPublished()`, `canCollectFee()` |
| Getter | `get` prefix or bare noun | `getFullName()` |
| Collection variable | plural | `$students`, `$challans` |

**Suffix by layer** — the class name must announce its layer:
`*Controller` · `*Service` · `*Repository` · `*Middleware` · `*Request` · `*Policy` · `*Exception` · `*Helper` · `*Seeder`

### Database

| Element | Convention | Example |
|---|---|---|
| Table | `snake_case`, **plural** | `student_enrollments`, `fee_challans` |
| Pivot table | both tables, singular, alphabetical | `post_tags`, `role_permissions` |
| Column | `snake_case`, singular | `first_name`, `payable_amount` |
| Primary key | `id` | |
| Foreign key | `{singular_table}_id` | `student_id`, `academic_session_id` |
| Boolean column | `is_` / `has_` prefix | `is_active`, `has_transport` |
| Date column | `_at` (datetime) / `_date` (date) | `published_at`, `admission_date` |
| Amount column | `_amount` | `discount_amount` |
| Count column | `_count` | `download_count` |
| Index | `idx_{table}_{cols}` | `idx_students_class_section` |
| Unique | `uq_{table}_{cols}` | `uq_attendance_student_date` |
| Foreign key | `fk_{table}_{column}` | `fk_marks_exam_id` |

> **Never** use reserved words as identifiers: `class` → `school_classes`, `group` → `group_name`, `order` → `sort_order`, `key` → `key_name`, `read` → `read_at`.

### Files

| Type | Convention | Example |
|---|---|---|
| Class file | `PascalCase.php` (matches class) | `StudentService.php` |
| View file | `kebab-case.php` | `student-list.php`, `fee-challan.php` |
| Partial view | `_kebab-case.php` (leading underscore) | `_sidebar.php`, `_pagination.php` |
| Config file | `lowercase.php` | `database.php` |
| Route file | `lowercase.php` | `admin.php` |
| Migration | `NNNN_snake_case.sql` | `0007_create_fee_challans.sql` |
| CSS / JS | `kebab-case` | `admin-dashboard.js` |
| Image | `kebab-case` | `campus-main-building.jpg` |
| Documentation | `NN-kebab-case.md` | `05-security.md` |

### Views & routes
| Element | Convention | Example |
|---|---|---|
| View path | mirrors controller | `admin/students/index.php` |
| Route name | `surface.module.action` | `admin.students.edit` |
| URL segment | `kebab-case`, plural resource | `/admin/fee-challans` |
| Permission slug | `module.action` | `fees.collect` |

### Front-end

| Element | Convention | Example |
|---|---|---|
| CSS class (custom) | BEM `block__element--modifier` | `student-card__avatar--large` |
| Bootstrap utilities | used as-is, not wrapped | `d-flex`, `mb-3` |
| CSS custom property | `--iiui-{name}` | `--iiui-primary` |
| JS variable / function | `camelCase` | `loadSectionStudents()` |
| JS class | `PascalCase` | `AttendanceSheet` |
| JS constant | `UPPER_SNAKE_CASE` | `API_BASE_URL` |
| DOM id | `kebab-case` | `#student-form` |
| `data-` attribute | `data-kebab-case` | `data-student-id` |
| JS hook class | `js-` prefix, never styled | `js-delete-student` |

> The `js-` prefix rule matters: it means a designer can safely rename a styling class without breaking behaviour.

---

## 3. JavaScript Standards

- **ES6+**: `const` by default, `let` when reassigned, **never `var`**.
- Arrow functions for callbacks; `async/await` over promise chains.
- Template literals over concatenation.
- **All AJAX goes through `lib/http.js`** — which attaches the CSRF header, sets `Accept: application/json`, handles `419`/`422`/`500` uniformly, and shows a SweetAlert2 on failure. Raw `fetch()` calls scattered through modules are a review rejection.
- Strict mode; one IIFE or module per file; no globals except an explicit `window.IIUI` namespace.
- Never build HTML with string concatenation from user data — use `textContent` or an escaping helper.
- Every user-facing async action: disable the trigger, show a loading state, re-enable in `finally`.
- Confirm destructive actions with SweetAlert2, never `confirm()`.
- Server-side validation is the authority; client-side is UX only.

---

## 4. CSS Standards

- Bootstrap 5 utilities first; write custom CSS only when utilities genuinely can't express it.
- Custom styles are BEM-named and live in `app.css` / `admin.css` — **no inline `style=""`**, no `!important` (exception: print stylesheets).
- Theme via CSS custom properties in `:root` (`--iiui-primary`, `--iiui-radius`) so a rebrand is one file.
- Mobile-first: base styles unprefixed, then `min-width` media queries.
- Every module that prints (challan, result card, ID card, register) has print rules in `print.css`.
- Design tokens (colors, spacing, radius, shadow) are defined once and referenced — never hardcoded hex values in module CSS.

---

## 5. HTML & Accessibility

- Semantic elements: `<header> <nav> <main> <section> <article> <footer>`.
- Every `<input>` has a `<label>` (visible or `sr-only`), every image a meaningful `alt`.
- One `<h1>` per page; heading levels never skip.
- Interactive elements are `<button>`/`<a>`, never a clickable `<div>`.
- Tables use `<thead>/<tbody>/<th scope>`; data tables get a `<caption>`.
- Focus states are never removed; keyboard navigation works on attendance and marks entry screens (they're used daily at speed — arrow/tab navigation is a functional requirement, not an accessibility nicety).
- Colour contrast meets WCAG AA; status is never conveyed by colour alone (icon + text too).
- Forms show errors adjacent to the field with `aria-describedby`.

---

## 6. Git Conventions

**Branches**
```
main                     production-ready only
develop                  integration
feature/M09-fee-challan-generation
bugfix/attendance-duplicate-entry
hotfix/login-lockout-bypass
```

**Commits** — Conventional Commits:
```
feat(fees): add bulk challan generation with dry-run preview
fix(attendance): prevent duplicate marking on the same date
refactor(students): move promotion logic into EnrollmentService
docs(architecture): document session rollover flow
chore(deps): update PHPMailer to 6.9
```
Rules: imperative mood, ≤ 72-char subject, body explains **why**, one logical change per commit. Never commit `.env`, `vendor/`, `storage/`, backups, or IDE folders.

**Pull requests** must state what changed, why, how it was tested, and any migration/config step required.

---

## 7. Definition of Done

A module is complete only when **all** of these are true:

- [ ] Controllers thin; logic in services; SQL only in repositories
- [ ] Server-side validation for every input, with a `Requests/` rule set
- [ ] Authorization enforced at route **and** record level, with tests for the record level
- [ ] CSRF on every state-changing request
- [ ] All output escaped
- [ ] Multi-table writes inside a transaction
- [ ] Sensitive actions written to `audit_logs`
- [ ] Errors handled with domain exceptions and user-safe messages
- [ ] Responsive at 360 / 768 / 1024 / 1440 px
- [ ] Loading, empty, and error states designed — not just the happy path
- [ ] Print layout where the module produces a document
- [ ] Indexed queries; no N+1 (verified by query count on a list screen)
- [ ] Seed data available for local testing
- [ ] Docs updated if a schema or contract changed

---

## 8. Code Review Checklist

**Security** — any string interpolation into SQL? unescaped output? missing CSRF? authorization at record level? upload validated? secret hardcoded?
**Correctness** — money as `DECIMAL`? transaction around multi-write? edge cases (zero students, absent marks, partial payment, leap year, session boundary)?
**Performance** — query inside a loop? missing index? unbounded result set? full table load into memory?
**Maintainability** — logic in the right layer? name says what it does? duplicated from elsewhere? dead code?
**Consistency** — matches the naming table above? follows the surrounding file's idiom?

---

## 9. Documentation Requirements

- `docs/` is updated in the **same commit** as any schema, route, or permission change — documentation that lags is documentation that lies.
- Every service class carries a header comment stating its responsibility and its invariants.
- Complex business rules (grading formula, position calculation, fine accrual, promotion criteria) are documented **where the code lives**, with the rule spelled out in plain language.
- `README.md` keeps a working local-setup path; `.env.example` lists every key the app reads.
