-- =============================================================================
-- 0039_seed_admission_form_page.sql · Companion to the new standalone
-- /admission-form page (Controllers/Web/AdmissionController::form(),
-- app/Views/web/pages/admission-form.php) — a dedicated, multi-step
-- application form separate from the /admissions marketing page, modeled
-- on the reference site's own separate Admissions-info vs Admission-Form
-- pages. The form itself still posts to the pre-existing admissions.apply
-- endpoint; no new fields, validation, or table were introduced.
--
-- Fixes a real pre-existing bug along the way: the homepage's "Admission
-- CTA" button (settings.cta_button_url) pointed at `/admissions/apply`,
-- which is POST-only (routes/web.php) — clicking it as a normal link would
-- have 404'd/405'd. It now points at the real, GET-able /admission-form
-- page, same destination the header's "Apply Online" button and the
-- Admissions page's own "Start Application Form" button already use.
--
-- No nav_item added: the header already has an always-visible "Apply
-- Online" action button (not a regular nav link) that now targets this
-- page directly, so a second entry in the About/Academics-style dropdowns
-- would just be a redundant path to the same place.
-- =============================================================================
USE `iiui_mardan`;

UPDATE `settings` SET `value` = '/admission-form' WHERE `group_name` = 'homepage' AND `key_name` = 'cta_button_url' AND `value` = '/admissions/apply';

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0039_seed_admission_form_page.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
