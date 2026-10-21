# Third-party assets (self-hosted)

Self-hosted per docs/05-security.md §7 — everything the admin panel and
public site load is served from `public/assets/vendor/`, never a CDN, so a
strict `script-src 'self'` CSP is achievable and there's no external
supply-chain dependency at request time.

| Library | Version | License | Path |
|---|---|---|---|
| Chart.js | 4.4.4 | MIT | `chartjs/chart.umd.min.js` |
| Bootstrap Icons (subset) | latest (icons vendored individually, not the full package) | MIT | `bootstrap-icons/icons.svg` |
| Swiper | 11.x | MIT | `swiper/swiper-bundle.min.{css,js}` |
| TinyMCE (Community) | 8.8.2 | GPL v2+ | `tinymce/` |

Bootstrap Icons are combined into one `<symbol>`-based SVG sprite containing
only the glyphs actually used by the admin panel, referenced via
`<use href="/assets/vendor/bootstrap-icons/icons.svg#bi-{name}">`. Add more
icons to the sprite the same way as new screens need them — there is no
build step, it's a flat SVG file.

**TinyMCE** was vendored from the official npm tarball (`tinymce@8.8.2`, GPL
license path — no Tiny Cloud API key needed for self-hosted use), keeping
only what the CMS actually uses rather than the full ~30-plugin package:

- Core: `tinymce.min.js`, `icons/default`, `themes/silver`, `models/dom`
- Skins: `skins/ui/oxide` (light) + `oxide-dark`, `skins/content/default` + `dark`
  — matched to the admin panel's own light/dark theme via `skin` /
  `content_css` set from the page's `data-bs-theme`.
- Plugins: `lists link image table code autolink wordcount advlist charmap
  searchreplace visualblocks fullscreen preview help`

**Every TinyMCE-produced HTML value is sanitized server-side** through
`App\Core\HtmlSanitizer` before it's stored, and again before it's rendered
(docs/05-security.md §4) — self-hosting the editor doesn't change that; it
only removes a CDN dependency. Add a plugin later by copying its folder from
a fresh tarball into `plugins/` and adding it to the editor's `plugins`
config string — no build step.
