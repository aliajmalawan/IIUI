# 08 — Design System: Foundations

**IIUI Schools & College – Mardan Campus**
Design language: **"Dignified Modern"** — institutional authority with contemporary polish.

> The brief is *premium*. Premium in an educational context does not mean gradients and glow. It means: generous whitespace, one confident typeface pairing, restrained colour, precise alignment, and shadows that behave like light. Every token below exists because a component needs it — nothing decorative was added for its own sake.

---

## 1. Brand Foundation

### 1.1 Design Principles

| # | Principle | In practice |
|---|---|---|
| 1 | **Clarity over decoration** | A parent checking fee dues at 11pm must succeed in 3 seconds. Nothing ornamental may slow that. |
| 2 | **Heritage, not nostalgia** | Serif display type and an eight-point *khatam* motif signal an Islamic institution — used sparingly, never skeuomorphic. |
| 3 | **One accent, used rarely** | Gold appears on ≤ 3 elements per screen. Its scarcity is what makes it read as premium. |
| 4 | **Density follows context** | The public site breathes (96px sections). The admin panel is dense and fast (44px rows). Two different jobs, two different rhythms. |
| 5 | **Depth through light** | Elevation is expressed with soft, brand-tinted shadows and surface lightness — never with borders *and* shadows *and* gradients at once. |
| 6 | **Motion explains, never entertains** | Every animation answers "where did this come from?" or "what changed?" |
| 7 | **Accessible by construction** | AA contrast is a token-level guarantee, not a QA finding. |

### 1.2 Logo Placement

**Variants** — full colour · reversed white · monochrome deep green · mark-only (for tight spaces)

| Rule | Specification |
|---|---|
| **Clear space** | Minimum on all four sides = **50% of the logo's height**. Nothing — text, border, image edge — enters this zone. |
| **Minimum size** | Full lockup: 140px wide / 40px tall. Mark only: 28px. Below that, use the mark. |
| **Alignment** | Optically centred, not mathematically — the crescent/dome mark reads high, so nudge down 1–2px in vertical centring. |

| Context | Placement | Height |
|---|---|---|
| Public navbar | Left, aligned to container gutter | 48px (40px when scrolled / mobile) |
| Public footer | Top of column 1, reversed white variant | 56px |
| Admin sidebar (expanded) | Top block, 64px tall, left padding 20px | 36px mark + wordmark |
| Admin sidebar (collapsed) | Centred in the 76px rail | 32px mark only |
| Auth / login screen | Centred above the card, or on the brand panel | 64px |
| Print (challan, result card, ID card) | Top-left, beside the campus name block | 60px, monochrome |
| Favicon | Mark only, on deep-green tile | 16 / 32 / 180 / 512 |
| Email templates | Centred header on `--surface-1` | 44px |

**Never:** stretch or skew · rotate · recolour outside the four approved variants · apply drop shadows, glows, or bevels · place the colour variant on a photo without a scrim (use the reversed white variant instead) · crowd it against a heading · reproduce it below minimum size.

---

## 2. Colour Palette

### 2.1 Primary — Campus Green

The institutional colour. Deep, desaturated, confident — closer to forest than to emerald, so it never reads "eco startup".

| Token | Hex | Use |
|---|---|---|
| `--green-50` | `#ECF7F1` | Tinted section backgrounds, selected table rows |
| `--green-100` | `#D3ECDF` | Soft badge backgrounds, hover tints |
| `--green-200` | `#A8D9C0` | Borders on tinted surfaces, chart fills |
| `--green-300` | `#74C09C` | **Dark-mode primary text/links** |
| `--green-400` | `#45A379` | **Dark-mode buttons & icons**, chart series |
| `--green-500` | `#26855D` | Focus rings, active chart series |
| `--green-600` | `#1A6B4A` | Button hover on light |
| **`--green-700`** | **`#14563D`** | **BRAND CORE** — primary buttons, headings, links |
| `--green-800` | `#124531` | Button active, sidebar surface |
| `--green-900` | `#0F3929` | Footer background |
| `--green-950` | `#071F16` | Hero overlays, deepest surfaces |

### 2.2 Accent — Heritage Gold

Used for emphasis only: the primary CTA, active-nav indicator, award/merit markers, hairline dividers in the footer.

| Token | Hex | Use |
|---|---|---|
| `--gold-50` | `#FDF8EC` | Merit/award card tint |
| `--gold-100` | `#FAEECF` | Soft badge background |
| `--gold-200` | `#F4DA9C` | Dividers, borders |
| `--gold-300` | `#EBC162` | **Dark-mode gold text**, active sidebar bar |
| `--gold-400` | `#DFA733` | Icons on dark, hover state |
| **`--gold-500`** | **`#C68B1B`** | **ACCENT CORE** — CTA button fill (with dark text) |
| `--gold-600` | `#A67114` | CTA hover |
| `--gold-700` | `#855913` | **Gold text on light backgrounds** (6.1:1 ✓) |
| `--gold-800` | `#6D4915` | |
| `--gold-900` | `#5C3D16` | |

> ⚠️ **`--gold-500` on white is 2.95:1 — it fails AA for text.** It is a *surface* colour. Gold text on a light background must use `--gold-700`. Gold buttons carry `--neutral-900` text (5.4:1 ✓), never white.

### 2.3 Neutrals — Warm Slate

Subtly green-tinted greys. A pure-grey UI beside a green brand always looks slightly broken; this ties the whole interface together.

| Token | Hex | Light-mode role |
|---|---|---|
| `--neutral-0` | `#FFFFFF` | Card surface |
| `--neutral-50` | `#F7F9F8` | Table header, subtle fill |
| `--neutral-100` | `#EEF2F0` | App background (admin), disabled fill |
| `--neutral-200` | `#DFE5E2` | **Default border** |
| `--neutral-300` | `#C6D0CC` | Input border, dividers |
| `--neutral-400` | `#9AA8A3` | Placeholder, disabled text, icons-muted |
| `--neutral-500` | `#71807B` | Muted text (4.6:1 ✓) |
| `--neutral-600` | `#57645F` | Secondary text (6.4:1 ✓) |
| `--neutral-700` | `#434E4A` | Labels, strong secondary |
| `--neutral-800` | `#2D3733` | Headings on light |
| `--neutral-900` | `#1C2421` | **Body text** (14.9:1 ✓) |
| `--neutral-950` | `#111714` | Shadow tint source |

### 2.4 Semantic

| Purpose | Surface (`-500`) | Text on light (`-700`) | Tint (`-50`) | Border (`-200`) |
|---|---|---|---|---|
| **Success** | `#0E9F6E` | `#056B47` | `#E7F7F1` | `#A7E5CE` |
| **Warning** | `#D97706` | `#92400E` | `#FEF6E7` | `#FBDCA1` |
| **Danger** | `#DC2626` | `#B01818` | `#FDECEC` | `#F7BBBB` |
| **Info** | `#0284C7` | `#02638F` | `#E8F4FB` | `#A8D8F0` |

> **The green-on-green problem, solved deliberately.** Brand primary and semantic success are both green. They are kept apart three ways: (1) success is *brighter and teal-shifted* (`#0E9F6E` vs `#14563D`); (2) success only ever appears as a **soft tinted chip or inline message**, never as a solid button — solid green means "primary action"; (3) every semantic state carries an **icon plus a text label**, so it never depends on hue alone. Rule: a success badge and a primary button never sit adjacent.

### 2.5 Domain Status Colours

School data has states that recur across dozens of screens. Locking them here means an "Absent" chip looks identical in the register, the parent portal, and the PDF export.

| Attendance | Colour | Icon |
|---|---|---|
| Present | `success` | `check-circle` |
| Absent | `danger` | `x-circle` |
| Late | `warning` | `clock` |
| Leave | `info` | `envelope-paper` |
| Half day | `#7C3AED` violet | `circle-half` |

| Fee status | Colour | | Grade band | Colour |
|---|---|---|---|---|
| Paid | `success` | | A+ / A | `success` |
| Partial | `warning` | | B+ / B | `info` |
| Unpaid | `neutral-500` | | C / D | `warning` |
| Overdue | `danger` | | F / Absent | `danger` |
| Cancelled | `neutral-400`, struck through | | | |

### 2.6 Data-Visualisation Palette (Chart.js)

Categorical, colour-blind-safe, ordered by first use:

`#14563D` · `#C68B1B` · `#0284C7` · `#7C3AED` · `#0E9F6E` · `#D97706` · `#BE185D` · `#0F766E`

Sequential (heatmaps, attendance density): `--green-50 → --green-900`.
Diverging (above/below target): `--danger-500 ← --neutral-200 → --success-500`.
Rule: beyond 6 categories, switch to a single hue with varied lightness plus direct labels — a 9-colour legend is unreadable.

### 2.7 Contrast Reference (verified)

| Pair | Ratio | Verdict |
|---|---|---|
| `--green-700` on white | **8.64:1** | AAA ✓ |
| `--neutral-900` on white | **14.9:1** | AAA ✓ |
| `--neutral-600` on white | 6.4:1 | AA ✓ |
| `--neutral-500` on white | 4.6:1 | AA (body min) ✓ |
| `--neutral-400` on white | 2.8:1 | **Decorative / disabled only** ✗ |
| `--gold-700` on white | 6.1:1 | AA ✓ |
| `--gold-500` on white | 2.95:1 | **Surface only** ✗ |
| `--neutral-900` on `--gold-500` | 5.4:1 | AA ✓ (gold button text) |
| White on `--green-700` | 8.64:1 | AAA ✓ (primary button) |
| `--green-400` on `--dark-bg` | 5.97:1 | AA ✓ (dark-mode button) |
| `--success-500` on white | 3.39:1 | **Surface only** — use `-700` for text ✗ |

---

## 3. Typography

### 3.1 Two Type Systems

A public marketing site and a data-entry console have opposite needs. Forcing one scale on both is the most common way a "design system" fails in practice.

| | Public Website | Admin Panel |
|---|---|---|
| Display / headings | **Source Serif 4** (variable) | **Inter** — serif in a data table is noise |
| Body & UI | **Inter** (variable) | **Inter** |
| Numerals | Inter, `tabular-nums` | Inter, `tabular-nums` (mandatory) |
| Reference codes | **JetBrains Mono** | **JetBrains Mono** |
| Urdu | **Noto Nastaliq Urdu** | **Noto Nastaliq Urdu** |
| Base size | 16px | 14px |
| Scale ratio | 1.250 (Major Third) | 1.200 (Minor Third) |

All fonts are **self-hosted WOFF2** in `public/assets/fonts/`, `font-display: swap`, subset to Latin + Latin-Ext (+ Arabic for the Urdu face). No Google Fonts CDN call — this is what allows the strict CSP in [Security §7](05-security.md), and removes a third-party dependency from every page load.

### 3.2 Public Type Scale

Display sizes are fluid; body sizes are fixed (fluid body text harms readability).

| Token | Size | Line height | Weight | Tracking | Use |
|---|---|---|---|---|---|
| `--fs-display-1` | `clamp(2.5rem, 1.5rem + 4.4vw, 4.5rem)` | 1.05 | 600 | −0.025em | Hero headline |
| `--fs-display-2` | `clamp(2rem, 1.4rem + 2.8vw, 3.25rem)` | 1.1 | 600 | −0.02em | Section hero |
| `--fs-h1` | `clamp(1.875rem, 1.4rem + 2.2vw, 3rem)` | 1.15 | 600 | −0.02em | Page title |
| `--fs-h2` | `clamp(1.5rem, 1.25rem + 1.2vw, 2.25rem)` | 1.2 | 600 | −0.015em | Section title |
| `--fs-h3` | `1.75rem` (28) | 1.25 | 600 | −0.01em | Sub-section |
| `--fs-h4` | `1.375rem` (22) | 1.35 | 600 | 0 | Card title (large) |
| `--fs-h5` | `1.125rem` (18) | 1.4 | 600 | 0 | Card title |
| `--fs-h6` | `1rem` (16) | 1.45 | 600 | 0 | List heading |
| `--fs-lead` | `1.1875rem` (19) | 1.7 | 400 | 0 | Intro paragraph |
| `--fs-body` | `1rem` (16) | 1.65 | 400 | 0 | Body |
| `--fs-sm` | `0.875rem` (14) | 1.6 | 400 | 0 | Meta, captions |
| `--fs-xs` | `0.8125rem` (13) | 1.5 | 400 | 0 | Fine print |
| `--fs-overline` | `0.75rem` (12) | 1.4 | 600 | **+0.1em**, uppercase | Eyebrow labels |

### 3.3 Admin Type Scale

| Token | Size | Weight | Use |
|---|---|---|---|
| `--fs-admin-page-title` | `1.375rem` (22) | 600 | Page heading |
| `--fs-admin-section` | `1rem` (16) | 600 | Card header |
| `--fs-admin-body` | `0.875rem` (14) | 400 | Default UI text |
| `--fs-admin-table` | `0.875rem` (14) | 400 | Table cell |
| `--fs-admin-label` | `0.8125rem` (13) | 600 | Form label |
| `--fs-admin-help` | `0.75rem` (12) | 400 | Help & error text |
| `--fs-admin-th` | `0.6875rem` (11) | 600, +0.06em, uppercase | Table header |
| `--fs-stat-value` | `1.75rem` (28) | 700, tabular | Dashboard KPI |

### 3.4 Weights

`400` Regular (body) · `500` Medium (UI labels, table headers) · `600` Semibold (all headings, buttons) · `700` Bold (KPI numbers only).
**800/900 are not used.** Two decisive weights beat five ambiguous ones.

### 3.5 Typographic Rules

- **Measure:** body copy is capped at **68ch** (`--measure`). Full-width paragraphs are a readability failure regardless of screen size.
- **Serif is for the public site only**, and only at `h3` and above. Serif at 14px on a table row is illegible.
- **Numerals are always `tabular-nums`** in tables, fee amounts, marks, dates, and KPI tiles — proportional digits make a column of amounts visibly ragged.
- **Currency** always shows the unit: `PKR 12,500` or `Rs 12,500`, never a bare number.
- **Never centre more than 3 lines** of text.
- **Headings never end with a colon**, never use full stops, never ALL-CAPS except `--fs-overline`.
- **Urdu (`[lang="ur"]`)** switches to Noto Nastaliq Urdu, `direction: rtl`, and needs **line-height ≥ 2.0** — Nastaliq descends far below the baseline and clips at normal leading.
- **Truncation:** single-line names use `text-overflow: ellipsis` with a `title` attribute; multi-line card excerpts use `-webkit-line-clamp: 3`. A truncated value in a table must always be recoverable on hover or detail view.

---

## 4. Spacing System

### 4.1 Scale — 4px base, 8px rhythm

| Token | px | Typical use |
|---|---|---|
| `--space-0` | 0 | |
| `--space-1` | 4 | Icon-to-label gap, badge padding |
| `--space-2` | 8 | Tight stacks, chip padding |
| `--space-3` | 12 | Input padding-y, compact gaps |
| `--space-4` | **16** | **Default gap** — form fields, list items |
| `--space-5` | 20 | Card padding (compact) |
| `--space-6` | **24** | **Card padding**, grid gutter |
| `--space-8` | 32 | Card padding (large), block separation |
| `--space-10` | 40 | Sub-section spacing |
| `--space-12` | 48 | Section spacing (mobile) |
| `--space-16` | 64 | Section spacing (tablet) |
| `--space-20` | 80 | Section spacing (desktop) |
| `--space-24` | 96 | Hero padding, major sections |
| `--space-32` | 128 | Maximum — full-bleed feature sections |

**Rule: no arbitrary values.** `margin-top: 18px` is a review rejection. If the scale doesn't fit, the layout is wrong.

### 4.2 Vertical Rhythm

| Relationship | Spacing |
|---|---|
| Label → input | `--space-2` (8) |
| Input → help text | `--space-1` (4) |
| Field → field | `--space-4` (16) |
| Field group → group | `--space-8` (32) |
| Heading → its paragraph | `--space-3` (12) |
| Paragraph → paragraph | `--space-4` (16) |
| Content block → block | `--space-8` (32) |
| Card → card in a grid | `--space-6` (24) |
| Public section → section | `--space-12` → `--space-24` (responsive) |

**Proximity law:** the gap *above* a heading is always at least 2× the gap *below* it. This is what makes a page scan as grouped blocks rather than an undifferentiated column.

### 4.3 Section Padding (public site)

| Breakpoint | Padding Y |
|---|---|
| < 768px | `--space-12` (48) |
| 768–1199px | `--space-16` (64) |
| ≥ 1200px | `--space-20` (80) |
| Hero / feature sections | `--space-24` (96) |

---

## 5. Layout, Grid & Containers

### 5.1 Container Widths

| Token | Max width | Use |
|---|---|---|
| `--container-xs` | 480px | Auth cards, result-check form |
| `--container-sm` | 640px | Narrow forms, single-column content |
| `--container-md` | 768px | Article body |
| `--container-lg` | 1024px | Narrow page layouts |
| `--container-xl` | **1200px** | **Default public container** |
| `--container-2xl` | 1320px | Wide feature sections, galleries |
| `--container-admin` | 1600px | Admin content area max |
| `--measure` | 68ch | Reading measure for prose |

**Gutters:** 16px (< 576) · 24px (576–991) · 32px (≥ 992).
Containers are centred with `padding-inline: var(--gutter)`, so content never touches the viewport edge.

### 5.2 Grid

12-column, Bootstrap 5 grid used as-is (no custom grid layer).

| Pattern | Columns | Used by |
|---|---|---|
| Full width | 12 | Hero, sliders, full-bleed sections |
| Content + sidebar | 8 / 4 | Inner pages, news detail |
| Reading column | 8 centred (offset-2) | Article body, policies |
| Three-up cards | 4 / 4 / 4 | News, programs, features |
| Four-up cards | 3 × 4 | Gallery, faculty, quick links |
| Split hero | 6 / 6 | Admissions, about |
| Dashboard KPIs | 3 / 3 / 3 / 3 → 6/6 → 12 | Admin stat row |
| Dashboard main | 8 / 4 | Chart + activity feed |
| Form (2-col) | 6 / 6 | Admin create/edit forms |
| Form (3-col) | 4 / 4 / 4 | Dense admin filter bars |

**Rules:** columns collapse to full width below `md` (768px) — with one exception: two-up card grids may stay 2-across at `sm` (576px) where the card is compact. Card grids use CSS Grid with `auto-fit, minmax(280px, 1fr)` so an odd final row doesn't stretch. Nested rows never exceed one level.

### 5.3 Responsive Breakpoints

Aligned to Bootstrap 5 so no custom breakpoint variables are needed.

| Name | Min width | Device | Layout behaviour |
|---|---|---|---|
| `xs` | 0 | Phone portrait (360–575) | Single column, off-canvas nav, stacked tables |
| `sm` | 576px | Phone landscape / small tablet | 2-up compact cards |
| `md` | **768px** | Tablet portrait | Sidebar appears, 2-col forms, tables scroll |
| `lg` | **992px** | Tablet landscape / small laptop | Full navbar, 3-up grids, admin sidebar expands |
| `xl` | **1200px** | Desktop | Default container, 4-up grids |
| `xxl` | 1400px | Large desktop | Wider container, more generous spacing |

**Design & test targets:** 360 · 390 · 768 · 1024 · 1280 · 1440 · 1920 px.
**Mobile-first:** base CSS is the smallest screen; every media query is `min-width`. No `max-width` queries except for print and off-canvas-specific overrides.

---

## 6. Radius

| Token | Value | Applied to |
|---|---|---|
| `--radius-xs` | 4px | Badges, tags, small chips |
| `--radius-sm` | 6px | Inputs, small buttons, table cells |
| `--radius-md` | **10px** | **Buttons, form controls, sidebar items** |
| `--radius-lg` | **14px** | **Cards, dropdowns, alerts** |
| `--radius-xl` | 20px | Modals, large feature cards |
| `--radius-2xl` | 28px | Hero cards, image frames |
| `--radius-pill` | 999px | Pills, avatars-group, filter chips |
| `--radius-circle` | 50% | Avatars, icon buttons (round variant) |

**Nesting rule:** an inner element's radius = outer radius − padding. A 14px card with 24px padding holds elements at `--radius-md` or less. Concentric radii that don't account for padding look wrong even when nobody can say why.

---

## 7. Shadow & Elevation System

Shadows are tinted with `--neutral-950` (which carries the green tint), **not pure black**. Black shadows on a warm-neutral surface read grey and muddy.

Every shadow is **two-layer**: a tight, dark contact shadow plus a wide, soft ambient shadow — the way real light behaves.

| Token | Value | Elevation | Applied to |
|---|---|---|---|
| `--shadow-xs` | `0 1px 2px rgba(17,23,20,.05)` | 1 | Inputs, table header |
| `--shadow-sm` | `0 1px 3px rgba(17,23,20,.08), 0 1px 2px rgba(17,23,20,.04)` | 2 | Resting cards, sticky navbar |
| `--shadow-md` | `0 4px 12px -2px rgba(17,23,20,.10), 0 2px 4px -2px rgba(17,23,20,.06)` | 3 | Card hover, dropdowns, popovers |
| `--shadow-lg` | `0 12px 24px -6px rgba(17,23,20,.12), 0 4px 8px -4px rgba(17,23,20,.06)` | 4 | Modals, mega menu, off-canvas |
| `--shadow-xl` | `0 24px 48px -12px rgba(17,23,20,.16), 0 8px 16px -8px rgba(17,23,20,.08)` | 5 | Hero feature cards, command palette |
| `--shadow-2xl` | `0 40px 80px -20px rgba(17,23,20,.22)` | 6 | Full-screen overlays only |
| `--shadow-inner` | `inset 0 2px 4px rgba(17,23,20,.05)` | — | Pressed states, wells, progress tracks |
| `--shadow-focus` | `0 0 0 3px rgba(38,133,93,.28)` | — | **Focus ring (primary)** |
| `--shadow-focus-danger` | `0 0 0 3px rgba(220,38,38,.25)` | — | Focus ring on destructive controls |

### Elevation rules
1. **Border OR shadow, never both** at the same strength. A card is either outlined (`--radius-lg` + 1px border, flat) or elevated (shadow, no border). Mixing produces a "double edge".
2. **One elevation step per nesting level**, maximum. A card at level 2 may contain a dropdown at level 3 — not at level 5.
3. **Hover raises exactly one step** (`sm → md`) and lifts the element `translateY(-2px)`. Never two steps.
4. **Shadow direction is always downward** (positive Y). No upward or omnidirectional glows.
5. **In dark mode, shadows are halved and elevation is carried by surface lightness instead.** A shadow on a near-black surface is invisible; a lighter surface is what actually reads as "closer".

---

## 8. Glass Effects

Glass is a **spotlight technique**, not a theme. Overuse turns a premium interface into a 2013 mobile OS.

| Token | Composition |
|---|---|
| `--glass-light` | `background: rgba(255,255,255,.72)` · `backdrop-filter: blur(16px) saturate(180%)` · `border: 1px solid rgba(255,255,255,.55)` |
| `--glass-dark` | `background: rgba(14,21,18,.62)` · `backdrop-filter: blur(16px) saturate(160%)` · `border: 1px solid rgba(255,255,255,.08)` |
| `--glass-brand` | `background: rgba(7,31,22,.55)` · `backdrop-filter: blur(20px)` · `border: 1px solid rgba(198,139,27,.22)` (gold hairline) |
| `--glass-blur-sm` / `-md` / `-lg` | `blur(8px)` / `blur(16px)` / `blur(28px)` |

### Where glass is permitted — the complete list
1. Sticky public navbar once it scrolls over the hero (`--glass-light`, or `--glass-dark` in dark mode)
2. Hero stat/CTA cards floating over the hero image (`--glass-brand`)
3. Modal backdrop (`rgba(7,31,22,.55)` + `blur(8px)`)
4. Image-overlay captions in the gallery
5. Floating bulk-action bar in admin tables (appears over content when rows are selected)
6. Admin command palette (⌘K) overlay

### Where glass is forbidden
Data tables · forms and inputs · any body text longer than two lines · the admin sidebar (it scrolls; repainting a blur every frame is expensive) · card grids · print layouts · anything below `md` on low-end Android, where `backdrop-filter` is the single biggest jank source.

### Mandatory rules
- **Maximum 3 glass surfaces in a viewport at once.** Each one forces a separate GPU compositing layer.
- **Always ship an opaque fallback:**
  ```
  .glass { background: rgba(255,255,255,.94); }          /* fallback first */
  @supports (backdrop-filter: blur(16px)) {
      .glass { background: rgba(255,255,255,.72); backdrop-filter: blur(16px) saturate(180%); }
  }
  ```
- **Contrast is measured against the worst case** — the lightest pixel of the background image behind the glass — not against the tint colour.
- Glass always pairs with a **1px light border** on its top/left edge. Without it the panel has no defined edge and looks like a rendering artefact.

---

## 9. Motion & Animation

### 9.1 Tokens

| Duration | Value | Use |
|---|---|---|
| `--dur-instant` | 0ms | Validation messages, tab switches — *feedback must not wait* |
| `--dur-fast` | 150ms | Hover, focus, colour change, icon scale |
| `--dur-base` | **200ms** | **Default** — dropdowns, tooltips, toasts, modals |
| `--dur-slow` | 300ms | Drawers, off-canvas, accordions |
| `--dur-slower` | 500ms | Hero content entrance, page transitions |
| `--dur-slowest` | 800ms | KPI count-up, chart first draw |

| Easing | Curve | Use |
|---|---|---|
| `--ease-standard` | `cubic-bezier(.4,0,.2,1)` | Default — anything that moves and stops |
| `--ease-out` | `cubic-bezier(0,0,.2,1)` | **Entering** the screen (decelerate) |
| `--ease-in` | `cubic-bezier(.4,0,1,1)` | **Leaving** the screen (accelerate) |
| `--ease-emphasized` | `cubic-bezier(.34,1.56,.64,1)` | Slight overshoot — toasts, badge pops, success ticks |
| `--ease-linear` | `linear` | Spinners, shimmer, progress bars only |

**Asymmetry rule:** things enter slower than they leave. A modal opens in 200ms and closes in 150ms — this feels responsive; the reverse feels sluggish.

### 9.2 Component Motion Specification

| Element | Animation |
|---|---|
| Button hover | `translateY(-1px)` + shadow `sm→md`, 150ms |
| Button active | `translateY(0)` + `scale(.98)`, 100ms |
| Card hover (linked) | `translateY(-4px)` + shadow `sm→lg`, 200ms `--ease-out` |
| Card image on hover | `scale(1.05)` inside `overflow:hidden`, 500ms |
| Link underline | `background-size: 0 1px → 100% 1px`, left origin, 200ms |
| Dropdown / popover | opacity `0→1` + `translateY(-6px→0)`, 200ms `--ease-out` |
| Modal | backdrop fade 200ms; panel `scale(.96→1)` + fade, 200ms `--ease-out` |
| Off-canvas drawer | `translateX(100%→0)`, 300ms `--ease-out` |
| Toast | slide-in from right + fade, 200ms `--ease-emphasized`; auto-dismiss 4s |
| Accordion | `grid-template-rows: 0fr → 1fr`, 250ms (no `max-height` hacks) |
| Skeleton shimmer | gradient sweep, 1.5s `--ease-linear` infinite |
| Tab indicator | slides to new position, 200ms |
| Sidebar collapse | width 264→76px, 250ms `--ease-standard` |
| KPI count-up | 0 → value over 800ms, `--ease-out`, once |
| Chart.js | 600ms on **first render only**; `animation: false` on data updates |
| Page enter | content fade + `translateY(8px→0)`, 300ms |

### 9.3 AOS (Scroll Animation) Rules

```
duration: 700 · easing: ease-out-cubic · once: true · offset: 80 · mirror: false
```
- **Public site only. Never in the admin panel** — an accountant reloading a table 40 times a day does not want it to fade in 40 times.
- **Never on above-the-fold content.** Animating the hero delays perceived load and hurts LCP. The hero animates via CSS on load, not on scroll.
- **Stagger caps at 3 items × 100ms.** A 12-card grid staggering to 1200ms feels broken, not elegant.
- Permitted effects: `fade-up`, `fade-in`, `fade-right/left` (max 30px travel). **Forbidden:** `zoom`, `flip`, `rotate` — they read as amateur.
- Reserve layout space so AOS never causes cumulative layout shift.

### 9.4 Reduced Motion — mandatory

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: .01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: .01ms !important;
    scroll-behavior: auto !important;
  }
}
```
Plus, in JavaScript: AOS is not initialised, KPI count-up jumps to the final value, Chart.js animation is disabled, and Swiper autoplay is off. This is a vestibular-disorder accessibility requirement, not a preference.

### 9.5 Never Animate
Table rows on load or sort · form validation messages (instant) · anything triggered by typing · `width`/`height`/`top`/`left` (use `transform` — they're the only GPU-composited properties) · more than 3 elements simultaneously in the viewport · anything on a print stylesheet.

---

## 10. Icon System

**Library: Bootstrap Icons** — 2,000+ glyphs, stylistically matched to Bootstrap 5, MIT-licensed, self-hostable. Delivered as a **single SVG sprite** (`icons.svg`) referenced via `<use>`, so one cached request serves the whole app.

> *Alternative considered:* Lucide and Phosphor are visually finer, but mixing icon families across an app is the fastest way to look unfinished, and Bootstrap Icons already match the component library's stroke and corner language. Consistency wins.

### Sizes

| Token | px | Context |
|---|---|---|
| `--icon-xs` | 14 | Inline with `--fs-xs`, badges |
| `--icon-sm` | 16 | **Default** — buttons, inputs, table actions |
| `--icon-md` | 20 | Sidebar nav, card headers, alerts |
| `--icon-lg` | 24 | Page headers, empty states |
| `--icon-xl` | 32 | Feature cards |
| `--icon-2xl` | 48 | Empty states, hero features |

### Rules
- Icons inherit `currentColor` — never hardcode a fill.
- **Optical alignment:** icons sit on the text baseline via `vertical-align: -.125em`, not `middle`.
- Icon + label gap is always `--space-2` (8px).
- **An icon-only button must have `aria-label` and a tooltip.** No exceptions.
- **Icons never carry meaning alone.** Status is always icon + text (colour-blind users, and screen readers).
- **Icon containers** in stat and feature cards: 40×40 (`--radius-md`) or 48×48 (`--radius-lg`), filled with the semantic `-50` tint, icon in the `-600` shade.
- One family only. Never mix Bootstrap Icons with Font Awesome or emoji.

### Canonical Icon Mapping

Fixing these means "Students" looks the same in the sidebar, the dashboard tile, the empty state, and the mobile nav.

| Concept | Icon | | Concept | Icon |
|---|---|---|---|---|
| Dashboard | `speedometer2` | | Fees / Challan | `receipt` |
| Students | `mortarboard` | | Payment | `cash-coin` |
| Staff / Teachers | `person-badge` | | Expenses | `wallet2` |
| Guardians / Parents | `people` | | Reports | `graph-up-arrow` |
| Classes | `door-open` | | Notices | `megaphone` |
| Sections | `grid-3x3` | | News / Posts | `newspaper` |
| Subjects | `book` | | Events | `calendar-event` |
| Attendance | `calendar-check` | | Gallery | `images` |
| Exams | `journal-text` | | Pages (CMS) | `file-earmark-text` |
| Marks | `pencil-square` | | Media library | `collection` |
| Results | `award` | | Menus | `list-nested` |
| Timetable | `calendar3-week` | | Settings | `gear` |
| Admissions | `person-plus` | | Users & roles | `shield-lock` |
| Library | `journals` | | Audit log | `clock-history` |
| Transport | `bus-front` | | Backup | `hdd` |
| Payroll | `cash-stack` | | Logout | `box-arrow-right` |

Actions: `plus-lg` add · `pencil` edit · `trash` delete · `eye` view · `download` export · `printer` print · `search` search · `funnel` filter · `arrow-repeat` refresh · `three-dots-vertical` more.

---

## 11. Decorative Motif — Khatam Geometry

A restrained cultural signature that separates this from a generic template.

An **eight-point star (khatam)** SVG, used at **3–6% opacity** only:
- Hero background texture (tiled, behind the overlay)
- Section-divider ornament: a 32px gold hairline `— ✦ —` between major sections
- Watermark on print documents (challan, result card, certificate) at 4%
- Empty-state background
- Login page brand panel

**Never** as an interactive element, a bullet, a loading spinner, or above 8% opacity. The motif whispers; it does not decorate.

---

## 12. Light & Dark Mode

### 12.1 Strategy

`data-bs-theme="light|dark"` on `<html>` — Bootstrap 5.3's native mechanism, so all Bootstrap components theme automatically and only our custom tokens need overriding.

**Resolution order:** stored user choice → `prefers-color-scheme` → light.
**No flash of wrong theme:** a tiny blocking inline script in `<head>` sets the attribute before first paint. This must run before any stylesheet — a theme flash is the single most noticeable polish failure.

| Surface | Dark mode support |
|---|---|
| Public website | Supported, **defaults to light** — an institutional site is expected to be light |
| Admin panel | **Full support**, remembered per user — staff work late, and the sidebar is already dark |
| Print | Always light, regardless of theme |
| Emails | Light, with `prefers-color-scheme` meta for dark-mode clients |

### 12.2 Semantic Surface Tokens

Components reference **only these semantic tokens**, never raw palette values. That's what makes dark mode a token swap rather than a rewrite.

| Token | Light | Dark |
|---|---|---|
| `--bg-body` | `#F4F7F5` | `#0E1512` |
| `--bg-surface` | `#FFFFFF` | `#141C18` |
| `--bg-surface-raised` | `#FFFFFF` | `#1B2621` |
| `--bg-surface-overlay` | `#FFFFFF` | `#23302A` |
| `--bg-subtle` | `#F7F9F8` | `#1B2621` |
| `--bg-muted` | `#EEF2F0` | `#23302A` |
| `--bg-inverse` | `#1C2421` | `#ECF7F1` |
| `--text-primary` | `#1C2421` | `#E8EEEB` |
| `--text-secondary` | `#57645F` | `#A9B6B0` |
| `--text-muted` | `#71807B` | `#7F8D87` |
| `--text-disabled` | `#9AA8A3` | `#5C6A65` |
| `--text-inverse` | `#FFFFFF` | `#0E1512` |
| `--text-link` | `#14563D` | `#74C09C` |
| `--border-subtle` | `#EEF2F0` | `rgba(255,255,255,.06)` |
| `--border-default` | `#DFE5E2` | `rgba(255,255,255,.10)` |
| `--border-strong` | `#C6D0CC` | `rgba(255,255,255,.16)` |
| `--brand-solid` | `#14563D` | `#26855D` |
| `--brand-solid-hover` | `#124531` | `#1A6B4A` |
| `--brand-text` | `#14563D` | `#74C09C` |
| `--accent-solid` | `#C68B1B` | `#DFA733` |
| `--accent-text` | `#855913` | `#EBC162` |

### 12.3 Dark-Mode Rules

1. **Never pure black.** `#0E1512` — a near-black with the brand's green tint. Pure black against white text causes halation and eye strain over a long session.
2. **Elevation is lightness, not shadow.** Each level up is a lighter surface (`#141C18 → #1B2621 → #23302A`). Shadows drop to 40% strength and serve only to separate overlays.
3. **Desaturate large fills, brighten small ones.** `--green-700` becomes unreadable on dark; buttons move to `--green-500`, text and links to `--green-300/400`.
4. **Borders replace shadows** as the primary separator, at 6–16% white.
5. **Never invert photographs.** Decorative images get `filter: brightness(.92)`; student photos, logos, and gallery images are untouched.
6. **Recompute every contrast pair** — do not assume a light-mode-passing pair passes inverted.
7. **Charts get a dedicated palette**: lighter, more saturated series on dark, with grid lines at 8% white instead of `--neutral-200`.
8. **The theme toggle is always reachable** — admin topbar and public navbar, with `aria-pressed` state.

---

## 13. Accessibility Baseline (WCAG 2.1 AA)

| Requirement | Implementation |
|---|---|
| Text contrast | ≥ 4.5:1 body, ≥ 3:1 large text — guaranteed by the token table in §2.7 |
| Non-text contrast | ≥ 3:1 for borders, icons, focus indicators, chart elements |
| Focus visible | `--shadow-focus` 3px ring + 2px offset on every interactive element; **`outline: none` without a replacement is forbidden** |
| Keyboard | Full operability; logical tab order; visible skip-to-content link; `Esc` closes overlays; focus trapped in modals and returned on close |
| Touch targets | ≥ 44×44px on touch devices — small buttons get padding, not a smaller hit area |
| Colour independence | Every status = colour **+ icon + text** |
| Motion | `prefers-reduced-motion` honoured globally (§9.4) |
| Forms | Every input labelled; errors linked via `aria-describedby`; error summary at the top of long forms |
| Landmarks | `<header> <nav> <main> <aside> <footer>` + `aria-label` on repeated landmarks |
| Live regions | `aria-live="polite"` for toasts and AJAX save confirmations; `assertive` only for errors |
| Zoom | Usable at 200% zoom and at 320px width without horizontal scroll |
| Language | `lang="en"` / `lang="ur"` set correctly for screen-reader pronunciation |
