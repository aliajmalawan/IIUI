# IIUI Schools & College — Mardan Campus
## Architecture & Design Documentation

> **Project:** School Management System + Website CMS
> **Client:** International Islamic University Islamabad Schools & College – Mardan Campus
> **Phase:** 1 — Planning & Architecture (design only, no implementation)
> **Date:** 2026-07-30

---

### Document Index

| # | Document | Covers |
|---|---|---|
| 01 | [Architecture](01-architecture.md) | Layered architecture, request lifecycle, folder structure, core components |
| 02 | [Database](02-database.md) | Full schema, relationships, ERD, indexing & data conventions |
| 03 | [Modules & Roles](03-modules-roles.md) | Module breakdown, user roles, RBAC permission matrix |
| 04 | [Routing & API](04-routing-api.md) | URL structure, router design, REST API plan, AJAX contracts |
| 05 | [Security](05-security.md) | Threat model, auth, CSRF/XSS/SQLi, uploads, headers, audit |
| 06 | [Standards](06-standards.md) | Coding standards, naming conventions, file/Git conventions |
| 07 | [Roadmap](07-roadmap.md) | Build phases, future expansion, scalability path |

---

### The Stack (locked)

| Layer | Technology |
|---|---|
| Language | PHP 8.2+ (Core PHP — no framework) |
| Database | MySQL / MariaDB via **PDO** (prepared statements only) |
| CSS Framework | Bootstrap 5 |
| JavaScript | ES6 + jQuery + AJAX |
| Charts | Chart.js |
| Alerts | SweetAlert2 |
| Scroll Animation | AOS |
| Sliders | Swiper |
| Rich Text | TinyMCE |
| Image Cropping | CropperJS |
| Email | PHPMailer (SMTP) |

> ⚠️ Roadmap says PHP 8.3; the installed XAMPP runtime is **PHP 8.2.12 / MariaDB 10.4.32**.
> All design targets **PHP 8.2 compatibility** — no 8.3-only syntax (typed class constants, `json_validate()`).

---

### Design Principles

1. **Separation of concerns** — Controllers stay thin, business rules live in Services, SQL lives in Repositories.
2. **Single entry point** — every request goes through one front controller; nothing else is web-reachable.
3. **Secure by default** — escaping, CSRF, and authorization are opt-out, not opt-in.
4. **Session-scoped academics** — every academic record is tied to an `academic_session_id`; nothing is "current year only".
5. **Soft delete + audit** — operational records are never hard-deleted; every sensitive write is logged.
6. **Config over code** — grading scales, fee structures, menus, and site content are data, not hardcoded.
7. **Framework-free, not structure-free** — Core PHP, but with the discipline of a framework.
