# 05 — Security Architecture

> This system stores minors' personal data, family CNICs, academic records and financial ledgers. Security is a requirement, not a hardening pass at the end.

---

## 1. Threat Model

| # | Threat | Realistic scenario here | Primary control |
|---|---|---|---|
| T1 | SQL Injection | Search box, roll-number lookup, public result check | PDO prepared statements, `EMULATE_PREPARES=false` |
| T2 | XSS | TinyMCE content, student names, contact form messages | Output escaping everywhere + HTMLPurifier for rich text + CSP |
| T3 | CSRF | Forged POST that deletes a student or waives a fine | Per-session token on every state-changing request |
| T4 | Broken authorization (IDOR) | Parent edits `/portal/parent/child/999` to view another child | Policy check **and** repository query scope |
| T5 | Credential attacks | Weak/shared staff passwords, brute force on `/login` | Argon2id, throttle + lockout, forced rotation, optional 2FA |
| T6 | Malicious upload | `.php` disguised as a student photo | Extension allowlist + MIME sniff + re-encode + store outside webroot |
| T7 | Session hijack/fixation | Shared lab computers | Regenerate on login, fingerprint, idle+absolute timeout, device revoke |
| T8 | Sensitive data exposure | `.env`, `/storage/logs`, DB backups reachable over HTTP | Everything outside `public/` + explicit deny rules |
| T9 | Privilege escalation | Teacher assigns themselves `super_admin` | Role level hierarchy, `roles.manage` restricted, all changes audited |
| T10 | Insider data theft | Mass export of the student directory | Export permission gated, rate-limited, and audit-logged |
| T11 | Financial tampering | Silent edit of a paid challan | Append-only payments, reversal entries, four-eyes approval, immutable audit |
| T12 | Spam/abuse | Bot-submitted admission applications, contact spam | Rate limit + honeypot + timing check + optional captcha |
| T13 | Enumeration | Guessing `/admin/students/1..9999` or valid emails | UUIDs publicly, 404-not-403 for out-of-scope, generic auth errors |
| T14 | DoS via heavy reports | Full-session report with no date bound | Pagination caps, query timeouts, queued heavy exports |

---

## 2. Authentication

**Password storage** — `PASSWORD_ARGON2ID` (memory 64 MB, time 4, threads 2), with automatic `password_needs_rehash()` upgrade on successful login. Never MD5/SHA1, never a custom "salt + hash".

**Password policy** — minimum 10 characters; must contain 3 of 4 character classes; blocked against a common-password list and against the user's own name/username/registration number; no reuse of the last 3; forced change on first login and after an admin reset; optional 90-day expiry for finance and admin roles.

**Brute-force defence**
| Trigger | Response |
|---|---|
| 5 failed attempts (same account) | 15-minute lock, email notice to the user |
| 10 failed attempts (same IP, any account) | IP throttled 1 hour |
| 3 failed attempts | CAPTCHA required on subsequent attempts |
| Any lockout | Written to `audit_logs` and surfaced on the admin dashboard |

Login errors are always generic — *"Invalid credentials"* — never "no such user", which would confirm which accounts exist.

**Two-factor (TOTP)** — optional per user, **mandatory for `super_admin`, `principal`, and `accountant`**. Recovery codes issued once, stored hashed.

**Remember-me** — random 64-byte selector/validator pair, validator stored hashed, 30-day expiry, rotated on each use, invalidated on password change.

---

## 3. Session Security

```
session.cookie_httponly = 1        JS can never read the session cookie
session.cookie_secure   = 1        HTTPS only (production)
session.cookie_samesite = Lax      blocks cross-site POST
session.use_strict_mode = 1        rejects attacker-supplied session IDs
session.use_only_cookies= 1        no session ID in the URL
session.gc_maxlifetime  = 7200
session.name            = IIUI_SESS   (no "PHPSESSID" fingerprint)
```

- **Regenerated** on login, on logout, and on any privilege change.
- **Fingerprint** = hash(User-Agent + IP /24 subnet). A mismatch destroys the session. The /24 tolerance is deliberate — full-IP binding breaks mobile users switching towers.
- **Idle timeout** 30 min (60 for portal, 15 for finance screens); **absolute timeout** 8 hours regardless of activity.
- Session files live in `storage/sessions/`, never the shared system temp directory — on shared hosting, the default location is readable by other tenants.
- Active sessions are listed per user with a **"log out all other devices"** action.

---

## 4. Input Handling

**Golden rule: validate on input, escape on output. Never sanitize-and-trust.**

- Every request is validated by a `Requests/*Request.php` rule set **before** it reaches a service. There is no "the form already checked it" path — client-side validation is a UX feature only.
- Type coercion is explicit: `(int)`, `(float)`, enum casting. No `==`; `===` throughout.
- Allowlists, not denylists, for: sortable columns, filterable fields, upload MIME types, redirect targets, template names.
- **Mass assignment** is blocked by an explicit `$fillable` per model — a request can never set `role_id`, `status`, or `paid_amount` just by adding a field to the form.

### SQL injection
```
Every query: prepared statement with bound parameters.
Never: string concatenation or interpolation into SQL.
```
Column and table names can't be bound, so anywhere they're dynamic (sorting, filtering) they are matched against a hardcoded allowlist array — never taken from input.
`PDO::ATTR_EMULATE_PREPARES = false` is non-negotiable: with emulation on, PDO builds the query string client-side and the parameterization guarantee weakens.

### XSS
- `e()` (`htmlspecialchars` with `ENT_QUOTES | ENT_SUBSTITUTE`, UTF-8) on **every** variable echoed into HTML.
- Context-aware escaping: HTML body, HTML attribute, JavaScript (`json_encode` with `JSON_HEX_*`), and URL (`rawurlencode`) each use the right function — a single escaper for all four contexts is a bug waiting to happen.
- TinyMCE output is the **only** unescaped content, and it passes through **HTMLPurifier** with a restricted allowlist (no `<script>`, no `<iframe>` except an allowlisted YouTube/Vimeo embed, no `on*` attributes, no `javascript:` URLs) on **save**, then again on render.
- Content Security Policy as the second line of defence (below).

### CSRF
- Token generated per session (`random_bytes(32)`), placed in every form and in `<meta name="csrf-token">`.
- Verified with `hash_equals()` on every `POST`/`PUT`/`PATCH`/`DELETE`.
- AJAX sends it via the `X-CSRF-TOKEN` header — the `http.js` wrapper attaches it automatically, so no developer can forget.
- Failure → `419` page, never a silent partial action.
- Stateless API routes are CSRF-exempt because they authenticate by `Authorization` header, which browsers don't attach automatically.

---

## 5. File Upload Security

The single most common route to remote code execution in PHP school systems. Every uploaded file passes all seven checks:

1. **Extension allowlist** — `jpg, jpeg, png, webp` (images) · `pdf, docx, xlsx` (documents). Denylist approaches are rejected outright.
2. **Real MIME sniff** via `finfo_file()` — the browser-supplied `$_FILES['type']` is attacker-controlled and ignored.
3. **Size caps** — 2 MB images, 5 MB documents, enforced in PHP *and* in the web-server config.
4. **Image verification** — `getimagesize()` must succeed and dimensions must be sane.
5. **Re-encode** — images are decoded and re-written with GD. This destroys any polyglot payload embedded in EXIF or appended after the image data.
6. **Random filename** — `bin2hex(random_bytes(16)) . '.' . $ext`. The original name is stored in the database for display only, never used on disk.
7. **Stored outside the webroot** — files go to `storage/app/uploads/`. Public delivery goes through a controller that checks authorization and streams the bytes with a forced `Content-Type` and `Content-Disposition`.

Additionally: `.htaccess` in every upload directory denies PHP execution as a defence-in-depth backstop; documents are always served with `Content-Disposition: attachment` so a crafted HTML/SVG file can't execute in our origin.

---

## 6. Authorization

Three layers, all mandatory:

```
1. ROUTE       middleware: is the user authenticated and does the role exist?
2. PERMISSION  middleware: does the user hold `students.edit`?
3. POLICY      does the user have rights over THIS student record?
                └── plus a repository scope injecting the WHERE clause
```

Layer 3 is what stops IDOR. A teacher with `marks.edit` still cannot touch marks for a class they don't teach, because `MarkRepository` appends `AND class_subject_id IN (…teacher's assignments…)` to the query itself.

**Out-of-scope records return `404`, not `403`** — a 403 confirms the record exists, which is itself a leak.

**Role assignment guard:** a user may only assign roles with a `level` strictly below their own. `super_admin` is not assignable through the UI at all; it's seeded and changed only through a CLI script.

---

## 7. HTTP Security Headers

Set by `SecurityHeadersMiddleware` on every response:

```
Content-Security-Policy: default-src 'self';
    script-src 'self' 'nonce-{random}';
    style-src 'self' 'unsafe-inline';
    img-src 'self' data: https:;
    font-src 'self';
    frame-src https://www.youtube.com https://www.google.com;
    frame-ancestors 'none';
    form-action 'self';
    base-uri 'self';
    object-src 'none'
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()
X-Powered-By / Server: removed
```

> All third-party JS (Bootstrap, jQuery, Chart.js, Swiper, SweetAlert2, AOS, TinyMCE, CropperJS) is **self-hosted in `public/assets/vendor/`**, not CDN-loaded. This is what makes a strict `script-src 'self'` achievable and removes a supply-chain dependency on external hosts.

---

## 8. Server & Filesystem Hardening

**PHP** — `display_errors=Off` (production), `expose_php=Off`, `session.save_path` set to `storage/sessions`, `open_basedir` limited to the project root, dangerous functions disabled (`exec`, `shell_exec`, `system`, `passthru`, `eval` via linting), `upload_max_filesize`/`post_max_size` capped, `allow_url_fopen=Off`, `allow_url_include=Off`.

**Web server** — DocumentRoot is `public/` only; directory listing off; deny access to dotfiles, `.env`, `.sql`, `.log`, `.md`, `composer.*`; force HTTPS with a 301; disable `TRACE`.

**Permissions** — directories `755`, files `644`, writable only where required (`storage/`, `public/assets/uploads` if used). Never `777`, anywhere, for any reason.

**Database** — the application connects as a user with `SELECT, INSERT, UPDATE, DELETE` only. No `DROP`, no `ALTER`, no `FILE`, no `GRANT`. Migrations run under a separate DDL account from the CLI. No remote root access; `localhost` binding.

**Secrets** — every credential lives in `.env`, which is gitignored, outside the webroot, and readable only by the web-server user. `.env.example` holds keys with empty values. Rotating a credential never requires a code change.

---

## 9. Audit & Monitoring

**Always logged to `audit_logs`:** login success/failure/lockout · role or permission changes · user create/disable/password reset · student admission, promotion, transfer, deletion · marks entry, edit after verification, result publication · challan generation, cancellation, fine waiver · payment recording and reversal · fee structure changes · settings changes · backup create/restore · bulk exports.

Each entry captures actor, action, entity type & ID, `old_values`/`new_values` JSON diff, IP, user agent, and timestamp. **Audit logs are append-only** — no UI path updates or deletes them, and the app DB user's rights make tampering visible.

**Application logs** — daily-rotated files in `storage/logs/`, levels `debug|info|warning|error|critical`. Personal data, passwords, tokens, and CNICs are **never** written to logs.

**Alerts to admin** — repeated lockouts, 500-error spikes, failed backups, failed cron runs, unusual after-hours mass exports.

---

## 10. Rate Limiting

| Endpoint | Limit |
|---|---|
| `POST /login` | 5 / 15 min per IP + per account |
| `POST /forgot-password` | 3 / hour per email |
| `POST /admissions/apply` | 3 / hour per IP |
| `POST /contact` | 5 / hour per IP |
| `GET /result` (public check) | 20 / hour per IP |
| Public API | 20 / min per IP |
| Authenticated API | 60 / min per token |
| Report exports | 10 / hour per user |

---

## 11. Privacy & Data Protection

- **Data minimisation** — collect only what the campus actually needs; CNIC/B-Form are required fields but are **masked in list views** (`•••••••••1234`) and shown in full only to roles with `students.view_sensitive`.
- **Encryption at rest** for the most sensitive columns (CNIC, bank account, 2FA secrets) using AES-256-GCM with a key from `.env` — separate from the DB, so a stolen dump alone doesn't reveal them.
- **HTTPS everywhere**, HSTS preloaded.
- **Student photos** are served through an authorization-checking controller, never as a guessable public path.
- **Backups are encrypted** before leaving the server, and restore is `super_admin`-only with a confirmation step and an audit entry.
- **Retention** — see [Database §9](02-database.md). Academic and financial records are permanent; behavioural logs are pruned.
- **Right to correction** — students/parents can raise a profile-correction request rather than edit directly, keeping an approval trail.

---

## 12. Pre-Launch Security Checklist

- [ ] `APP_DEBUG=false`, `display_errors=Off`, generic error pages verified
- [ ] Default/seeded passwords changed; `super_admin` has 2FA enabled
- [ ] `.env`, `/storage`, `/app`, `/database`, `/vendor` all return 403/404 over HTTP
- [ ] HTTPS forced; HSTS present; certificate auto-renewal working
- [ ] All security headers present (verified against securityheaders.com)
- [ ] CSRF verified on every state-changing form and AJAX call
- [ ] Upload of a `.php` renamed to `.jpg` is rejected — tested
- [ ] IDOR tested: parent A cannot read student B; teacher X cannot mark class Y
- [ ] Sequential-ID enumeration on public routes returns 404
- [ ] DB user has no DDL privileges; root not used by the app
- [ ] Nightly backup runs, and a **restore has actually been tested** — an untested backup is not a backup
- [ ] Rate limits verified on login, apply, and contact
- [ ] Audit logging confirmed for every action in §9
- [ ] Dependency scan clean (`composer audit`)
- [ ] Cron jobs run as a non-privileged user, unreachable over HTTP
