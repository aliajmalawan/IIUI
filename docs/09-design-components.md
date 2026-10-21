# 09 — Design System: Components & Layouts

Builds on [08 — Foundations](08-design-foundations.md). Every value here references a token; no component introduces a new raw value.

---

## 1. Buttons

### 1.1 Variants

| Variant | Fill | Text | Border | Use — **one primary per view** |
|---|---|---|---|---|
| **Primary** | `--brand-solid` | white | none | The single main action on a screen |
| **Accent (Gold)** | `--gold-500` | `--neutral-900` | none | Public CTA only: "Apply Now", "Download Prospectus" |
| **Secondary** | `--bg-surface` | `--text-primary` | 1px `--border-default` | Cancel, Back, secondary actions |
| **Outline** | transparent | `--brand-text` | 1px `--brand-text` | On tinted sections, card footers |
| **Ghost** | transparent | `--text-secondary` | none | Table row actions, toolbar, low emphasis |
| **Danger** | `--danger-500` | white | none | Delete, cancel challan, terminate |
| **Danger soft** | `--danger-50` | `--danger-700` | 1px `--danger-200` | Destructive action in a list — less alarming in bulk |
| **Success** | `--success-500` | white | none | Confirm-only contexts (approve, verify) |
| **Link** | none | `--text-link` | none | Inline in prose |
| **White** | white | `--neutral-900` | none | Over hero images, on dark sections |

> Gold is the loudest colour in the system. **One gold button per page, maximum**, and never inside the admin panel — there, primary green is the top of the hierarchy.

### 1.2 Sizes

| Size | Height | Padding X | Font | Icon | Radius | Use |
|---|---|---|---|---|---|---|
| `xs` | 28px | 10px | 12px/600 | 14 | `--radius-sm` | Table inline actions, chips |
| `sm` | 34px | 14px | 13px/600 | 16 | `--radius-sm` | Admin toolbars, filters |
| `md` | **40px** | **18px** | **14px/600** | 16 | `--radius-md` | **Default** — forms, cards |
| `lg` | 48px | 24px | 15px/600 | 20 | `--radius-md` | Public page CTAs |
| `xl` | 56px | 32px | 16px/600 | 20 | `--radius-md` | Hero CTAs only |

Icon-only buttons are **square** at the same height. On touch devices every button gets a minimum 44×44 hit area via an invisible `::before` overlay — the visual size does not change.

### 1.3 States

| State | Treatment |
|---|---|
| Hover | Fill one shade darker · `translateY(-1px)` · shadow `sm→md` · 150ms |
| Active | `translateY(0)` · `scale(.98)` · shadow `xs` · 100ms |
| Focus-visible | `--shadow-focus` (3px ring) + 2px offset. **Never `outline:none` alone** |
| Disabled | `opacity:.5` · `cursor:not-allowed` · `pointer-events:none` · no hover |
| Loading | Label replaced by a 16px spinner, **width locked to the original** so the layout doesn't jump; button disabled; `aria-busy="true"` |
| Selected (toggle) | `--brand-solid` fill or `--green-50` tint + `aria-pressed="true"` |

### 1.4 Rules
- Labels are **verb-first and specific**: "Save Student", "Generate Challans" — never "Submit", "OK", or "Click here".
- Sentence case. Never ALL CAPS.
- Icon sits **left** of the label, except "next / continue" chevrons which sit right.
- Button groups: 8px gap; the primary action is **rightmost** in dialogs and modals, **leftmost** in page toolbars.
- Destructive actions are never adjacent to the primary action — insert a spacer or place them at the opposite end.
- Full-width buttons only below `md`, and only in forms and cards.

---

## 2. Cards

### 2.1 Anatomy
```
┌──────────────────────────────┐
│ [media]        16:9 / 4:3    │
├──────────────────────────────┤
│ header   title · meta · [⋮]  │  padding 24
│ body     content              │
│ footer   actions · timestamp  │  border-top --border-subtle
└──────────────────────────────┘
```

### 2.2 Base specification

| Property | Value |
|---|---|
| Background | `--bg-surface` |
| Radius | `--radius-lg` (14px) |
| Padding | 24px desktop · 20px tablet · 16px mobile |
| Border | 1px `--border-subtle` **or** `--shadow-sm` — never both (see Foundations §7) |
| Header/footer divider | 1px `--border-subtle`, full-bleed to the card edge |
| Media | Full-bleed to the card edges, `overflow: hidden`, radius inherited on the top corners only |

### 2.3 Variants

| Variant | Definition |
|---|---|
| **Elevated** | `--shadow-sm`, no border. Default for the public site. |
| **Outlined** | 1px `--border-default`, no shadow. Default for the admin panel — a dashboard of 20 shadowed cards is visual noise. |
| **Soft** | `--green-50` background, no border, no shadow. Informational blocks. |
| **Interactive** | Elevated + hover `translateY(-4px)` and shadow `sm→lg`. **Only when the entire card is a link.** |
| **Glass** | `--glass-brand` over hero imagery only. |
| **Accent-bar** | 3px left bar in a semantic colour — used for notices by priority and for KPI tiles. |

### 2.4 Domain Card Patterns

**Stat / KPI tile (admin dashboard)**
```
┌────────────────────────────┐
│ ▌ TOTAL STUDENTS      [◇]  │  overline 11px +.06em, muted
│ ▌ 1,248                    │  28px/700 tabular-nums
│ ▌ ↑ 4.2%  vs last session  │  13px, success/danger chip
└────────────────────────────┘
```
3px left accent bar · 40px tinted icon container top-right · count-up animation on first paint · the delta chip is **always** icon + sign + text, never colour alone.

**News card** — 16:9 image (`scale(1.05)` on hover) · category pill overlaid bottom-left on the image · date meta · `h5` title clamped to 2 lines · excerpt clamped to 3 · "Read more →" with an arrow that slides 4px right on hover.

**Faculty card** — 1:1 avatar 96px (or 3:4 portrait in the directory grid) · name `h5` · designation in `--text-secondary` · department in `--fs-overline` · qualification chips · centred alignment.

**Notice card** — priority accent bar (urgent `danger` / high `warning` / normal `info` / low `neutral`) · date badge on the left in a 56px tinted square (day over month) · title · audience chip · attachment icon if present.

**Event card** — date block (day 24px/700 over month uppercase 11px) in a `--green-50` square on the left · title · time and venue with icons · "Add to calendar" ghost button.

**Program card** — 48px icon in a gold-tinted container · title · duration and eligibility meta rows · outline button.

### 2.5 Rules
- Cards in a grid **stretch to equal height** (`align-items: stretch`), with the footer pinned to the bottom via `margin-top: auto`.
- **One elevation step per nesting level.** A card inside a card is `--bg-subtle` and flat.
- Never place a card directly on `--bg-surface` of the same colour without a border — it disappears.
- A card is either **entirely** clickable or has **explicit** action buttons. Never both — nested click targets are an accessibility failure.
- Card grids use `repeat(auto-fit, minmax(280px, 1fr))` so the final row never stretches oddly.

---

## 3. Forms

### 3.1 Field anatomy
```
Label *                          13px/600, --text-primary, mb 8
┌──────────────────────────────┐
│ Placeholder text             │  height 40, radius --radius-md
└──────────────────────────────┘
Helper text                      12px, --text-muted, mt 4
```

### 3.2 Input specification

| Property | Value |
|---|---|
| Height | 34 (`sm`) · **40 (`md`, default)** · 48 (`lg`) |
| Padding | 10px 12px · left padding 40px when a prefix icon is present |
| Font | 14px / 400 · **16px on mobile** (below 16px, iOS Safari zooms the viewport on focus) |
| Radius | `--radius-md` |
| Background | `--bg-surface` |
| Border | 1px `--border-strong` |
| Placeholder | `--text-disabled` — **never used as a substitute for a label** |
| Transition | border + shadow, 150ms |

### 3.3 States

| State | Treatment |
|---|---|
| Hover | Border `--neutral-400` |
| **Focus** | Border `--green-500` + `--shadow-focus` ring |
| Filled & valid | Border `--border-default`; a success tick appears **only** after async validation (e.g. registration-number uniqueness) |
| **Error** | Border `--danger-500` + danger focus ring + message below with a 14px `exclamation-circle` icon, `aria-invalid="true"`, `aria-describedby` |
| Disabled | `--bg-muted` fill · `--text-disabled` · `cursor: not-allowed` |
| Read-only | Transparent fill · `--border-subtle` · normal text colour (it is real data, not disabled) |
| Required | Red asterisk after the label + `required` + `aria-required="true"` |

### 3.4 Controls

| Control | Specification |
|---|---|
| **Select** | Native `<select>` styled with a custom `chevron-down`, 40px right padding. Lists over 15 options get a searchable enhanced select. |
| **Checkbox** | 18×18, `--radius-xs`, `--brand-solid` fill when checked, white tick, focus ring. Label is clickable. |
| **Radio** | 18×18 circle, 6px inner dot. Grouped in a `<fieldset>` with a `<legend>`. |
| **Switch** | 40×22 track, 18px thumb, `--brand-solid` when on, 200ms slide. For instant-effect settings only — never inside a form that needs Save. |
| **Textarea** | min-height 120px, `resize: vertical`, optional character counter bottom-right. |
| **File upload** | Dashed 2px `--border-strong` drop zone, 120px tall, `--radius-lg`, cloud icon + "Drag files here or browse". Hover/drag-over: `--green-50` fill + `--green-500` border. Shows filename, size, thumbnail, and a remove button after selection. |
| **Image upload** | Same, then opens a **CropperJS** modal with a fixed aspect ratio (1:1 student photo, 16:9 featured image) and a live preview. |
| **Date** | Native `date` input + calendar icon; academic date fields are clamped to the active session's start/end. |
| **Search** | Pill radius, leading `search` icon, clear (`x`) button when filled, 300ms debounce. |
| **Input group** | Prefix/suffix addons share the input's border, `--bg-subtle` fill: `PKR │ 12,500`, `Roll No │ ___`. |

### 3.5 Layout

| Rule | Detail |
|---|---|
| Columns | 2-column (6/6) at `md`+, single column below. Dense admin filter bars may go 3-column at `lg`+. |
| **Field width matches content** | A CNIC field is not full width. Widths: CNIC/phone 240px · date 180px · amount 160px · roll no 120px · name 100% · address full width. **A 1200px-wide input for a 13-digit CNIC looks broken.** |
| Grouping | Related fields sit in a `<fieldset>` with a `<legend>` styled as `--fs-admin-section`; 32px between groups. |
| Long forms | Split into a stepper (admissions) or tabs (student profile); a **sticky bottom action bar** holds Save/Cancel with a top shadow. |
| Actions | Primary right, Cancel/ghost left of it, destructive far left. |
| Errors | Inline below each field, **plus** a summary panel at the top of the form listing every error as an anchor link when more than three fields fail. |
| Validation timing | On **blur** first. After a field has errored once, re-validate on **input** so the user sees it clear as they fix it. Never validate while first typing. |
| Unsaved changes | `beforeunload` guard on dirty forms. |
| Autosave | Attendance and marks-entry screens autosave per row via AJAX with an inline "Saved ✓" indicator — never a modal. |

---

## 4. Tables

The admin panel is mostly tables. This is where density and clarity matter most.

### 4.1 Specification

| Element | Specification |
|---|---|
| Header | `--fs-admin-th` (11px/600, +0.06em, uppercase), `--text-secondary`, `--bg-subtle`, **sticky** at the scroll container top |
| Row height | 52px comfortable · **44px default** · 36px dense (user-toggleable, remembered) |
| Cell padding | 12px 16px |
| Divider | 1px `--border-subtle` bottom. **No zebra striping** — with clean dividers it's redundant, and it fights row hover and selection states |
| Row hover | `--bg-subtle` background, 100ms |
| Row selected | `--green-50` background + 3px `--brand-solid` left bar |
| Font | 14px / 400 · **`tabular-nums` on every numeric column** |
| Alignment | Text left · numbers and currency **right** · status and actions centred/right · dates left |
| Actions column | Rightmost, **sticky on horizontal scroll**, ghost icon buttons (`eye`, `pencil`, `trash`) or a `three-dots-vertical` menu when there are more than three actions |

### 4.2 Behaviours

- **Sortable headers** show a neutral chevron on hover and a solid `--brand-solid` chevron when active. Only the allowlisted columns from [Routing §6](04-routing-api.md) are sortable.
- **Bulk selection**: header checkbox = select all *on this page* (with an explicit "Select all 486 matching" link). When any row is selected, a **glass floating action bar** rises from the bottom with the count and bulk actions.
- **Sticky first column** (student name / roll no) on horizontal scroll, with a subtle right shadow to signal the scroll seam.
- **Row expansion** for detail-in-place rather than navigating away (e.g. challan items inside a challan row).
- **Filters** live in a bar above the table: search input, up to 4 selects, date range, and an "Advanced" popover. Active filters render as removable chips below the bar with a "Clear all" action.
- **Pagination**: bottom-right; "Showing 1–25 of 486" bottom-left; a per-page select (25/50/100).

### 4.3 States

| State | Design |
|---|---|
| **Loading** | 5 skeleton rows with shimmer — **not** a centred spinner. Skeletons preserve layout and communicate the shape of what's coming. |
| **Empty (no data yet)** | 48px muted icon · "No students yet" · one line of explanation · **primary action button** |
| **Empty (no results)** | `search` icon · "No students match these filters" · "Clear filters" ghost button. **Distinct from the above** — telling a user with 3 filters applied that they have no students is a bug. |
| **Error** | Danger icon · message · "Retry" button |
| **Partial failure** | Row-level inline error with a retry icon; the rest of the table stays usable |

### 4.4 Responsive

| Breakpoint | Behaviour |
|---|---|
| ≥ `lg` | Full table |
| `md`–`lg` | Horizontal scroll, sticky first + last columns, an edge shadow hinting at more content |
| < `md` | **Key tables transform into stacked cards** — student list, fee challans, results. Each card: primary identifier as the title, 3–4 label/value rows, status badge, action row. Reference tables keep horizontal scroll. |

Never hide columns silently on small screens — a value that vanishes is worse than one requiring a scroll.

---

## 5. Alerts, Badges & Feedback

### 5.1 Inline alert
Soft `-50` background · 1px `-200` border · **3px left accent bar** in the semantic colour · 20px icon · bold title + body at 14px · optional action links · optional dismiss `x`.
Radius `--radius-lg`, padding 16px, gap 12px.

### 5.2 Toast (SweetAlert2 toast)
Top-right, 360px max, `--shadow-lg`, `--radius-lg` · icon + message + optional undo · auto-dismiss **4s** (errors: 8s, or persist until dismissed) · **maximum 3 stacked**, oldest evicted · pauses on hover · `aria-live="polite"`.
Used for: save confirmations, background job completion, non-blocking errors.

### 5.3 Modal / dialog
`--radius-xl` · `--shadow-xl` · max-width 520 (confirm) / 720 (form) / 960 (large) · backdrop `rgba(7,31,22,.55)` + `blur(8px)` · panel `scale(.96→1)` + fade 200ms.
Focus is trapped and returned to the trigger on close · `Esc` closes · header (title + `x`), body, footer (actions right-aligned).

**Destructive confirmation** — danger icon, the object's **name spelled out** ("Delete student *Ahmad Ali (Reg 2024-0451)*?"), an explicit consequence line, and a red confirm button whose label restates the action ("Delete Student", not "Yes"). Irreversible high-stakes actions (restore backup, cancel a paid challan, publish results) additionally require typing a confirmation word.

### 5.4 Badges & chips

| Variant | Design |
|---|---|
| **Soft** (default) | `-50` fill, `-700` text, `-200` border, 11px/600, `--radius-pill`, padding 3px 10px |
| **Solid** | `-500` fill, white text — for counts and high-emphasis status |
| **Dot** | 8px circle + label — for compact table cells |
| **Outline** | Transparent + 1px border — for filter chips |
| **Removable chip** | Adds a 12px `x` — for active filters and multi-select values |
| **Count badge** | Solid `--danger-500`, min-width 18px, pill, on the notification bell and sidebar items |

Status badges always render **icon + text**.

### 5.5 Other feedback
**Banner** — full-width, top of page, above the navbar. System-level only: maintenance window, session about to expire, unpaid-fee notice in the parent portal.
**Progress bar** — 6px track `--bg-muted`, `--brand-solid` fill, `--radius-pill`; determinate for uploads and bulk jobs (with a % label), indeterminate shimmer for unknown duration.
**Skeleton** — `--bg-muted` blocks at `--radius-sm` matching the real content's geometry, 1.5s shimmer sweep.
**Empty state** — centred: 48px muted icon in a tinted circle, `h5` title, one line of `--text-secondary` body, one primary action.
**Tooltip** — `--bg-inverse` fill, `--text-inverse`, 12px, `--radius-sm`, 6px 10px, 200ms delay in / 0 out, arrow, max-width 240px. Never contains essential information.

---

## 6. Navbar (Public)

### 6.1 Structure
```
┌─ Topbar 36px ─ deep green ────────────────────────────────────┐
│ ✆ +92 937 xxxxxxx  ✉ info@…    │  Portal Login · اردو · [◐]  │
├─ Main navbar 80px ─ surface ──────────────────────────────────┤
│ [LOGO 48]     Home  About▾  Academics▾  Admissions  News …    │
│                                          [Search]  [Apply Now]│
└───────────────────────────────────────────────────────────────┘
```

| Element | Specification |
|---|---|
| Topbar | 36px, `--green-900`, 12px text at 80% white, contact left, utilities right. Hidden below `lg` (moves into the mobile drawer). |
| Main bar | 80px desktop / 64px mobile, `--bg-surface`, `--border-subtle` bottom |
| Nav links | 15px/500, `--text-primary`, 16px horizontal padding, full-height hit area |
| Hover | `--brand-text` colour + a 2px `--gold-500` underline sliding in from the left, 200ms |
| Active | `--brand-text` at 600 weight + a persistent 2px gold underline |
| CTA | Gold `lg` button, "Apply Now" — **the only gold element in the header** |
| **Scrolled state** | Height 80→64px, `--glass-light` background, `--shadow-sm`, topbar slides away. 250ms. |

### 6.2 Mega menu
Triggered by About / Academics. Full-container width, `--bg-surface`, `--shadow-lg`, `--radius-lg` (bottom corners), 32px padding, 3 columns of grouped links each with a title and a one-line description, plus a featured panel on the right (a promoted program or the prospectus download). Opens on hover with a 120ms intent delay, and on `Enter`/`Space` for keyboard users; closes on `Esc`.

### 6.3 Mobile navigation
Hamburger → full-height drawer sliding from the right at 88% width (max 400px), `--shadow-xl`, backdrop with blur. Logo + close at the top; 52px accordion nav items with chevrons; the CTA is a full-width gold button pinned near the top; contact details and social icons at the bottom. Body scroll is locked; focus is trapped; `Esc` and backdrop-tap close it.

---

## 7. Sidebar (Admin)

### 7.1 Specification

| Property | Value |
|---|---|
| Width | **264px** expanded · **76px** collapsed (icons + tooltips) |
| Background | `#0B1712` (deep green-black) — a fixed dark surface in **both** themes; it is a brand anchor, not a content surface |
| Logo block | 64px tall, bottom border `rgba(255,255,255,.06)` |
| Section label | `--fs-overline`, `rgba(255,255,255,.38)`, 20px top margin, 12px horizontal padding |
| Nav item | 40px tall, 20px icon + 14px/500 label, 10px gap, `--radius-md`, 8px horizontal margin |
| Item colour | `rgba(255,255,255,.70)` |
| Hover | `rgba(255,255,255,.06)` fill, text to full white |
| **Active** | `rgba(38,133,93,.22)` fill + **3px `--gold-500` left bar** + white 600-weight text + white icon |
| Nested items | Indented 32px, 36px tall, 13px text, chevron rotates 90° on expand, 250ms |
| Count badge | Right-aligned pill, `--danger-500` |
| Scrollbar | 4px, `rgba(255,255,255,.14)`, appears on hover only |
| Footer | User chip: 32px avatar + name + role, opens an upward dropdown (Profile, Preferences, Logout) |

The gold active-bar is the one place gold appears in the admin panel. It ties the console back to the brand without ever competing with a primary action.

### 7.2 Behaviour
Collapse is toggled from the topbar and **persisted per user**. Collapsed items show a tooltip on hover and their sub-menus become flyout panels. The active parent stays expanded on page load. Below `lg` the sidebar becomes an off-canvas overlay with a backdrop.

### 7.3 Admin topbar
64px, `--bg-surface`, `--border-subtle` bottom, sticky.
Left: sidebar toggle · breadcrumb (`--fs-sm`, `--text-secondary`, current page in `--text-primary`).
Right: global search (`⌘K` / `Ctrl+K`, opens a command palette overlay) · **academic-session switcher** (a pill showing "2026-2027", with a warning tint when a non-current session is selected — this prevents the classic "entered marks into last year" disaster) · notification bell with count · theme toggle · avatar menu.

---

## 8. Footer (Public)

```
┌─ 80px top padding ─ --green-900 ──────────────────────────────┐
│  [LOGO white]      QUICK LINKS    ACADEMICS      CONTACT      │
│  Two-line about    · Home         · School       ⚲ Address    │
│  Social ○ ○ ○ ○    · Admissions   · College      ✆ Phone      │
│                    · News         · Programs     ✉ Email      │
│                    · Downloads    · Calendar     [Map link]   │
│  ─────────────── gold hairline, 30% opacity ───────────────   │
│  © 2026 IIUI Schools & College – Mardan Campus                │
│                        Privacy · Terms · Sitemap              │
└───────────────────────────────────────────────────────────────┘
```

| Element | Specification |
|---|---|
| Background | `--green-900`, with the khatam motif tiled at 3% opacity |
| Padding | 80px top, 32px bottom (48/24 on mobile) |
| Columns | 4 / 2 / 2 / 4 at `lg` · 6/6 at `md` · stacked at `xs` |
| Heading | `--fs-overline`, `--gold-300` |
| Links | 14px, `rgba(255,255,255,.72)` → white on hover with a 4px left shift, 150ms |
| Social icons | 36px circles, `rgba(255,255,255,.08)` → `--gold-500` on hover |
| Divider | 1px `--gold-500` at 30% opacity |
| Bottom bar | 13px, `rgba(255,255,255,.55)`, copyright left, legal links right |
| Newsletter | Optional inline: pill input + gold submit button |

---

## 9. Hero Styles

| # | Style | Specification |
|---|---|---|
| **1** | **Slider hero** (homepage) | Swiper, `min(88vh, 820px)`, min 600px. Image + gradient overlay `linear-gradient(100deg, rgba(7,31,22,.92) 0%, rgba(7,31,22,.62) 45%, rgba(7,31,22,.25) 100%)`. Content left-aligned, max 640px: gold overline → `--fs-display-1` in white → 19px subtitle at 85% white → gold CTA + white outline CTA. Bottom-left slide pagination as gold-filled progress bars; a scroll-cue chevron bobbing 6px. Autoplay 6s, pauses on hover/focus, **disabled under reduced-motion**. |
| **2** | **Page hero** (inner pages) | 300px (240 mobile), image + `rgba(7,31,22,.78)` overlay, khatam texture at 4%. Page title `--fs-h1` white, centred or left, with a breadcrumb below in 13px at 70% white. |
| **3** | **Split hero** | 6/6 at `lg`. Left: `--green-50` panel with overline, `--fs-display-2`, body, CTAs. Right: full-bleed image with a `--radius-2xl` outer corner and an overlapping gold-bordered stat card. |
| **4** | **Minimal hero** | No image. `--green-50` background, khatam motif at 5%, centred title + subtitle, 96px vertical padding. For policy, contact, and utility pages. |
| **5** | **Glass-card hero** | Slider hero + a row of 3 `--glass-brand` stat cards overlapping the hero's bottom edge by 40px, `--shadow-xl`. Homepage only. |

**Rules:** text over an image **always** sits on an overlay achieving ≥ 4.5:1 against the lightest pixel behind it. Hero images are served responsively (`srcset` 768/1280/1920) with `loading="eager"` and `fetchpriority="high"` for LCP. Hero height never uses bare `100vh` (mobile browser chrome makes it jump) — `svh` units with a `min-height` fallback. Hero content animates on **load**, never on scroll.

---

## 10. Page Layouts

| # | Layout | Structure |
|---|---|---|
| **L1** | Homepage | Slider hero → glass stat row → about split (6/6) → programs (3-up) → news (3-up) + notices sidebar → gallery strip → principal's message → CTA band → footer |
| **L2** | Inner content | Page hero → breadcrumb → content 8 / sidebar 4 (in-page nav, downloads, contact card) → related → footer |
| **L3** | Full-width content | Page hero → centred 8-column prose at `--measure` → footer |
| **L4** | Listing / archive | Page hero → filter bar (search, category, date) → card grid (3-up) → pagination → footer |
| **L5** | Detail (news/event) | Page hero → meta row (date, category, author, share) → 68ch prose → gallery → tags → prev/next → related 3-up |
| **L6** | Faculty directory | Page hero → department filter chips → 4-up faculty grid → profile page: 4/8 split (photo + contact card / bio, qualifications, subjects) |
| **L7** | Admissions | Split hero → process stepper (4 steps) → eligibility accordion → fee table → multi-step form → FAQ |
| **L8** | Contact | Page hero → 3 info cards → 7/5 split (form / map) → departments table |
| **L9** | Result check | Minimal hero → centred `--container-xs` card (reg no + DOB) → result card with a print button |
| **L10** | Auth | 50/50 split: left brand panel (`--green-800`, khatam motif, logo, tagline) · right centred form, `--container-xs`. Single column below `md`. |
| **L11** | Admin list | Sidebar + topbar → page header (title, count, primary action) → filter bar → table card → pagination |
| **L12** | Admin form | Sidebar + topbar → page header with back link → tabs or stepper → 2-column form card → sticky action bar |
| **L13** | Admin dashboard | KPI row (4 tiles) → 8/4 (chart card / activity feed) → 6/6 (two charts) → recent tables |
| **L14** | Print | No chrome. Logo + campus header block, khatam watermark at 4%, tabular content, signature blocks, footer with generation timestamp and page numbers. |

---

## 11. Admin Dashboard Style

**Canvas:** `--bg-body` (`#F4F7F5`) so white cards lift off the page. **Outlined** cards, not elevated — 20 shadowed tiles is noise.

**Composition (top to bottom):**
1. **Page header** — title, last-updated timestamp, date-range selector, export button
2. **KPI row** — 4 stat tiles (`3/3/3/3` → `6/6` → `12`), each with an accent bar, icon container, count-up value, and a trend chip
3. **Primary chart** (8) — fee collection vs target, or attendance trend — with the activity feed (4) beside it
4. **Secondary charts** (6/6) — class-wise strength bar, attendance donut
5. **Actionable tables** — pending approvals, recent admissions, overdue challans

**Chart.js house style:** no chart borders or heavy gridlines — horizontal gridlines only, at `--border-subtle` · axis labels 11px `--text-muted` · no chart title (the card header is the title) · legend top-right, 12px, or omitted when there's a single series · tooltips use `--bg-inverse` with `--radius-md` and 10px padding · line charts use 2px lines with `tension: .35` and a 12%-opacity gradient fill · bars get `borderRadius: 6` and `maxBarThickness: 44` · 600ms animation on first render only · a fixed `aspectRatio` so cards don't jump on resize · **every chart is accompanied by a screen-reader-accessible data table**.

**Role-aware:** each role's dashboard shows only widgets it has permission for — and the widget grid is defined per role, not filtered at render time from one master layout. See [Modules §M02](03-modules-roles.md).

---

## 12. Component Inventory

For build sequencing — every UI atom the system needs.

**Atoms** — button · icon button · link · badge · chip · avatar · divider · spinner · progress bar · skeleton · tooltip · label · input · select · checkbox · radio · switch · textarea
**Molecules** — form field (label+input+help+error) · input group · search box · file drop zone · pagination · breadcrumb · tab bar · accordion item · stat tile · alert · toast · dropdown menu · date range picker · filter chip row
**Organisms** — navbar (+ mega menu) · mobile drawer · admin sidebar · admin topbar · footer · data table (+ toolbar, bulk bar, empty states) · card grid · form card with sticky actions · modal · off-canvas panel · chart card · activity feed · stepper · calendar · gallery lightbox · comment/message thread
**Templates** — the 14 layouts in §10
**Print** — fee challan (3-part) · result card · student ID card · attendance register · certificate · payslip

---

## 13. Design Quality Gate

A screen ships only when every item is true:

- [ ] Every colour, size, space, radius, and shadow is a **token** — no raw hex, no arbitrary px
- [ ] Exactly **one** primary action; at most **one** gold element
- [ ] All four content states designed: **loading (skeleton) · empty · error · populated**
- [ ] Empty-because-new and empty-because-filtered are **different** states
- [ ] Verified at 360 · 768 · 1024 · 1440 px
- [ ] Verified in **both** light and dark mode
- [ ] Keyboard-only pass: every control reachable, focus always visible, `Esc` closes overlays
- [ ] Contrast checked against §2.7, including text over images
- [ ] Every status conveys meaning through **icon + text**, not colour alone
- [ ] Touch targets ≥ 44px on mobile
- [ ] `prefers-reduced-motion` honoured
- [ ] Numeric columns are `tabular-nums` and right-aligned
- [ ] Destructive actions name the object and state the consequence
- [ ] No layout shift on load (images and skeletons reserve their space)
- [ ] Print layout exists where the screen produces a document
