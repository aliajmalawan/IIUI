-- =============================================================================
-- 0035_seed_accreditation_page.sql · "Accreditation & Affiliations" page,
-- built entirely on the existing generic custom-page system (pages +
-- page_sections + PageController::show() at /page/{slug}) — the same
-- approach used for Vision & Mission (0018/manual seed) — since this
-- content is static/admin-editable and needs no new relational data, no
-- new controller, route, view, or CSS was needed; every section_type used
-- here (rich_text, icon_grid, stats, cta) already has a polished, responsive
-- .ps-* template.
--
-- Content below (accrediting bodies, stat figures) is realistic STARTER/
-- PLACEHOLDER copy, same convention as the Faculty/Leadership placeholder
-- profiles (0033/0034) — the admin should review and replace names/figures
-- with the campus's actual certificates and registration numbers via
-- admin/pages -> Accreditation & Affiliations -> Sections.
--
-- One-time seed, not an upsert for page_sections/menu_items (no natural
-- unique key); `pages` uses ON DUPLICATE KEY UPDATE on its unique `slug`,
-- matching 0033_seed_system_pages.sql's convention.
-- =============================================================================
USE `iiui_mardan`;

INSERT INTO `pages` (`title`, `slug`, `excerpt`, `sort_order`, `is_system`, `show_in_menu`, `status`, `published_at`)
VALUES
  ('Accreditation & Affiliations', 'accreditation-affiliations',
   'Recognized and regulated by the institutions that safeguard educational quality across Pakistan.',
   21, 0, 1, 'published', NOW())
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

INSERT INTO `page_sections` (`page_id`, `section_type`, `sort_order`, `is_active`, `content`)
VALUES
  (
    (SELECT id FROM `pages` WHERE slug = 'accreditation-affiliations'),
    'rich_text', 1, 1,
    '{"heading":"Held to Recognized Standards","content":"<p>IIUI Schools & College, Mardan Campus operates as a constituent campus under the academic charter of the International Islamic University Islamabad, and follows the curriculum, examination, and reporting requirements set by the relevant boards and regulatory bodies below.</p><p>We welcome inspection and review at any time, and keep our documentation available to parents on request.</p>"}'
  ),
  (
    (SELECT id FROM `pages` WHERE slug = 'accreditation-affiliations'),
    'icon_grid', 2, 1,
    '{"heading":"Our Accreditations & Affiliations","subheading":"The bodies that recognize, regulate, and review our academic programs.","items":[{"icon":"bank","title":"International Islamic University Islamabad","description":"Operating as a constituent campus under the charter and academic oversight of IIUI, Islamabad.","color":"brand"},{"icon":"mortarboard","title":"Federal Board of Intermediate & Secondary Education","description":"Secondary and Intermediate classes follow FBISE-approved curricula and sit FBISE examinations.","color":"gold"},{"icon":"building","title":"Directorate of Education, Khyber Pakhtunkhwa","description":"Registered with the provincial Directorate of Education and subject to its periodic inspections.","color":"info"},{"icon":"shield-check","title":"Campus Safety & Compliance","description":"Regularly reviewed against provincial fire-safety and campus-security regulations.","color":"success"},{"icon":"award","title":"Continuous Quality Assurance","description":"Internal academic audits each term to keep teaching and assessment standards consistent across every wing.","color":"violet"}]}'
  ),
  (
    (SELECT id FROM `pages` WHERE slug = 'accreditation-affiliations'),
    'stats', 3, 1,
    '{"heading":"Recognition at a Glance","items":[{"value":"IIUI","label":"Affiliated University","color":"brand"},{"value":"FBISE","label":"Examining Board","color":"gold"},{"value":"100%","label":"Curriculum Compliance","color":"success"},{"value":"Annual","label":"Inspection Cycle","color":"info"}]}'
  ),
  (
    (SELECT id FROM `pages` WHERE slug = 'accreditation-affiliations'),
    'cta', 4, 1,
    '{"heading":"Have Questions About Our Standing?","text":"Our admissions office can share affiliation letters and board registration details on request.","button_text":"Contact Us","button_url":"/contact"}'
  );

INSERT INTO `menu_items` (`menu_id`, `parent_id`, `title`, `type`, `url`, `target`, `sort_order`, `is_active`)
VALUES
  (1, 6, 'Accreditation & Affiliations', 'custom', '/page/accreditation-affiliations', '_self', 4, 1);

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0035_seed_accreditation_page.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
