<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Uploader;
use App\Core\Validator;
use App\Repositories\PageRepository;
use App\Repositories\PageSectionRepository;
use RuntimeException;

/**
 * Admin "Pages" — the four system pages seeded by 0033_seed_system_pages.sql
 * (About/Academics/Admissions/Contact) plus any custom pages created here.
 * Every page has a hero (heading/subheading/banner, straight on `pages`)
 * and a stack of reorderable, toggleable content sections (`page_sections`,
 * types defined in config/section_types.php) — see PageSectionRepository.
 * Custom pages are served at /page/{slug} (routes/web.php); the four system
 * pages keep their existing clean URLs and bespoke templates, with sections
 * rendered as extra content alongside that bespoke markup — the Admissions
 * application form, Contact form/map, and Academic Programs grid are real
 * working features, not something a generic section should replace.
 */
final class PageController extends Controller
{
    public function index(Request $request): Response
    {
        $pages = (new PageRepository())->all();
        $sectionRepo = new PageSectionRepository();
        foreach ($pages as &$page) {
            $page['section_count'] = count($sectionRepo->forPage((int) $page['id']));
        }
        unset($page);

        return $this->view('admin.pages.index', [
            'title' => 'Pages',
            'pages' => $pages,
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.pages.create', ['title' => 'Add Page'], 'admin');
    }

    public function store(Request $request): Response
    {
        $title = trim((string) $request->input('title'));
        $slugInput = trim((string) $request->input('slug', ''));
        $slug = slugify($slugInput !== '' ? $slugInput : $title);

        $validator = Validator::make(['title' => $title], ['title' => 'required|max:180']);
        $errors = $validator->errors();

        if ((new PageRepository())->findBySlug($slug) !== null) {
            $errors['slug'][] = 'That slug is already in use.';
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $this->redirectRoute('admin.pages.create');
        }

        $id = (new PageRepository())->create($title, $slug);

        Audit::log('pages.created', 'pages', 'pages', $id, "Created page \"{$title}\" (/page/{$slug})");
        Session::flash('status', 'Page created. Add sections to build it out.');

        return $this->redirectRoute('admin.pages.sections', ['id' => $id]);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new PageRepository();
        $page = $repo->find($id);
        if ($page === null) {
            throw new HttpException(404);
        }
        if ((int) $page['is_system'] === 1) {
            Session::flash('status', 'System pages cannot be deleted.');

            return $this->redirectRoute('admin.pages');
        }

        $sectionRepo = new PageSectionRepository();
        foreach ($sectionRepo->forPage($id) as $section) {
            $this->unlinkAllImagesIn(PageSectionRepository::decodeContent($section['content']));
        }
        $this->unlinkImage((string) $page['featured_image']);

        $repo->delete($id);

        Audit::log('pages.deleted', 'pages', 'pages', $id, "Deleted page \"{$page['title']}\"");
        Session::flash('status', 'Page deleted.');

        return $this->redirectRoute('admin.pages');
    }

    public function edit(Request $request): Response
    {
        $page = (new PageRepository())->find((int) $request->parameter('id'));
        if ($page === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.pages.edit', [
            'title' => 'Edit Page — ' . $page['title'],
            'page'  => $page,
        ], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new PageRepository();
        $page = $repo->find($id);
        if ($page === null) {
            throw new HttpException(404);
        }

        $validator = Validator::make($request->all(), [
            'title'   => 'required|max:180',
            'excerpt' => 'max:500',
        ]);
        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            Session::flash('_old_input', $request->all());

            return $this->redirectRoute('admin.pages.edit', ['id' => $id]);
        }

        $errors = [];
        $heroImage = $this->handleImageField($request, 'hero_image', 'hero_image_remove', (string) $page['featured_image'], $errors);
        if ($errors !== []) {
            Session::flash('errors', $errors);

            return $this->redirectRoute('admin.pages.edit', ['id' => $id]);
        }
        if ($heroImage !== null) {
            $repo->updateHeroImage($id, $heroImage !== '' ? $heroImage : null);
        }

        $repo->updateHero($id, (string) $request->input('title'), trim((string) $request->input('excerpt', '')));

        Audit::log('pages.updated', 'pages', 'pages', $id, "Updated page \"{$page['title']}\"");
        Session::flash('status', 'Page updated.');

        return $this->redirectRoute('admin.pages.edit', ['id' => $id]);
    }

    public function sections(Request $request): Response
    {
        $page = (new PageRepository())->find((int) $request->parameter('id'));
        if ($page === null) {
            throw new HttpException(404);
        }

        $sectionTypes = (array) config('section_types');
        $sections = (new PageSectionRepository())->forPage((int) $page['id']);
        foreach ($sections as &$section) {
            $section['type_label'] = $sectionTypes[$section['section_type']]['label'] ?? $section['section_type'];
        }
        unset($section);

        return $this->view('admin.pages.sections', [
            'title'        => 'Sections — ' . $page['title'],
            'page'         => $page,
            'sections'     => $sections,
            'sectionTypes' => $sectionTypes,
        ], 'admin');
    }

    public function addSection(Request $request): Response
    {
        $pageId = (int) $request->parameter('id');
        $page = (new PageRepository())->find($pageId);
        if ($page === null) {
            throw new HttpException(404);
        }

        $sectionTypes = (array) config('section_types');
        $type = (string) $request->input('section_type');
        if (!isset($sectionTypes[$type])) {
            Session::flash('status', 'Unknown section type.');

            return $this->redirectRoute('admin.pages.sections', ['id' => $pageId]);
        }

        $sectionId = (new PageSectionRepository())->create($pageId, $type);

        Audit::log('pages.section_added', 'pages', 'page_sections', $sectionId, "Added \"{$type}\" section to page \"{$page['title']}\"");

        return $this->redirectRoute('admin.pages.sections.edit', ['id' => $pageId, 'sectionId' => $sectionId]);
    }

    public function editSection(Request $request): Response
    {
        [$page, $section, $schema] = $this->findSectionOr404($request);

        return $this->view('admin.pages.section_edit', [
            'title'   => $schema['label'] . ' — ' . $page['title'],
            'page'    => $page,
            'section' => $section,
            'schema'  => $schema,
            'content' => PageSectionRepository::decodeContent($section['content']),
        ], 'admin');
    }

    public function updateSection(Request $request): Response
    {
        [$page, $section, $schema] = $this->findSectionOr404($request);
        $existing = PageSectionRepository::decodeContent($section['content']);

        $errors = [];
        $sanitized = $this->sanitizeFields($schema['fields'], $request, $existing, $errors);

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $this->redirectRoute('admin.pages.sections.edit', ['id' => $page['id'], 'sectionId' => $section['id']]);
        }

        $sortOrder = (int) $request->input('sort_order', $section['sort_order']);
        (new PageSectionRepository())->updateContent((int) $section['id'], $sanitized, $sortOrder);

        Audit::log('pages.section_updated', 'pages', 'page_sections', (int) $section['id'], "Updated section on page \"{$page['title']}\"");
        Session::flash('status', 'Section saved.');

        return $this->redirectRoute('admin.pages.sections', ['id' => $page['id']]);
    }

    public function toggleSection(Request $request): Response
    {
        $pageId = (int) $request->parameter('id');
        $sectionId = (int) $request->parameter('sectionId');

        (new PageSectionRepository())->toggle($sectionId);
        Audit::log('pages.section_toggled', 'pages', 'page_sections', $sectionId, 'Toggled section visibility');

        return $this->redirectRoute('admin.pages.sections', ['id' => $pageId]);
    }

    public function deleteSection(Request $request): Response
    {
        [$page, $section] = $this->findSectionOr404($request);

        $this->unlinkAllImagesIn(PageSectionRepository::decodeContent($section['content']));
        (new PageSectionRepository())->delete((int) $section['id']);

        Audit::log('pages.section_deleted', 'pages', 'page_sections', (int) $section['id'], "Deleted section from page \"{$page['title']}\"");
        Session::flash('status', 'Section deleted.');

        return $this->redirectRoute('admin.pages.sections', ['id' => $page['id']]);
    }

    /** @return array{0:array,1:array,2:array} [page, section, sectionTypeSchema] */
    private function findSectionOr404(Request $request): array
    {
        $pageId = (int) $request->parameter('id');
        $page = (new PageRepository())->find($pageId);
        if ($page === null) {
            throw new HttpException(404);
        }

        $sectionId = (int) $request->parameter('sectionId');
        $section = (new PageSectionRepository())->find($sectionId);
        if ($section === null || (int) $section['page_id'] !== $pageId) {
            throw new HttpException(404);
        }

        $sectionTypes = (array) config('section_types');
        $schema = $sectionTypes[$section['section_type']] ?? null;
        if ($schema === null) {
            throw new HttpException(404);
        }

        return [$page, $section, $schema];
    }

    /**
     * Builds a section's `content` array from submitted input, following the
     * field-naming convention section_edit.php's form uses: text/textarea/
     * richtext/select fields are nested under content[...] (fine for plain
     * POST arrays); media fields — including inside a repeater — get their
     * own flat `media__field` / `media__repeaterField__index__subfield` file
     * input names instead, specifically to avoid PHP's nested-$_FILES
     * structure (name/tmp_name/error as parallel nested arrays), which a
     * bracket-nested file input name would otherwise produce.
     *
     * @param array<string,array<string,mixed>> $fields section_types.php's 'fields' schema
     * @param array<string,mixed> $existing previously-saved content, for fallback when a media field isn't being changed
     * @param array<string,string[]> $errors filled in by reference, keyed by the same field names has_error() checks
     */
    private function sanitizeFields(array $fields, Request $request, array $existing, array &$errors): array
    {
        $submitted = $request->input('content', []);
        $submitted = is_array($submitted) ? $submitted : [];
        $result = [];

        foreach ($fields as $key => $def) {
            $type = $def['type'];

            if ($type === 'repeater') {
                $result[$key] = $this->sanitizeRepeater($key, $def['fields'], $submitted[$key] ?? [], $request, $existing[$key] ?? [], $errors);

                continue;
            }

            if ($type === 'media') {
                $current = (string) ($existing[$key] ?? '');
                $new = $this->handleImageField($request, "media__{$key}", "media_remove__{$key}", $current, $errors);
                $result[$key] = $new !== null ? ($new !== '' ? $new : null) : ($current !== '' ? $current : null);

                continue;
            }

            if ($type === 'select') {
                $val = (string) ($submitted[$key] ?? '');
                $options = array_keys($def['options'] ?? []);
                $result[$key] = in_array($val, $options, true) ? $val : (string) ($options[0] ?? '');

                continue;
            }

            // text, textarea, richtext — all stored as plain strings; richtext
            // is sanitized at RENDER time via HtmlSanitizer::clean(), matching
            // the News/Events TinyMCE fields already in this codebase.
            $result[$key] = trim((string) ($submitted[$key] ?? ''));
        }

        return $result;
    }

    /**
     * @param array<string,array<string,mixed>> $rowFields
     * @param array<int,array<string,mixed>> $existingRows
     * @param array<string,string[]> $errors
     */
    private function sanitizeRepeater(string $repeaterKey, array $rowFields, mixed $submittedRows, Request $request, array $existingRows, array &$errors): array
    {
        $submittedRows = is_array($submittedRows) ? $submittedRows : [];
        $indexes = array_map('intval', array_keys($submittedRows));
        $maxIndex = $indexes === [] ? -1 : max($indexes);

        $rows = [];
        for ($i = 0; $i <= $maxIndex; $i++) {
            $rowSubmitted = is_array($submittedRows[$i] ?? null) ? $submittedRows[$i] : [];
            $existingRow = $existingRows[$i] ?? [];
            $row = [];
            $hasContent = false;

            foreach ($rowFields as $subKey => $subDef) {
                if ($subDef['type'] === 'media') {
                    $fieldName = "media__{$repeaterKey}__{$i}__{$subKey}";
                    $removeName = "media_remove__{$repeaterKey}__{$i}__{$subKey}";
                    $current = (string) ($existingRow[$subKey] ?? '');
                    $new = $this->handleImageField($request, $fieldName, $removeName, $current, $errors);
                    $value = $new !== null ? ($new !== '' ? $new : null) : ($current !== '' ? $current : null);
                    $row[$subKey] = $value;
                    if (!empty($value)) {
                        $hasContent = true;
                    }

                    continue;
                }

                $value = trim((string) ($rowSubmitted[$subKey] ?? ''));
                $row[$subKey] = $value;
                if ($value !== '') {
                    $hasContent = true;
                }
            }

            if ($hasContent) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Shared upload/remove handling for one image field. Returns the new
     * path to save, null when nothing changed (keep the existing value), or
     * '' when the field was explicitly cleared. Appends to $errors (keyed by
     * $field, matching has_error($field) in the views) on a failed upload —
     * doesn't redirect itself, since a section can have several independent
     * media fields and callers want to collect every error before bailing.
     */
    private function handleImageField(Request $request, string $field, string $removeField, string $currentPath, array &$errors): ?string
    {
        $file = $request->file($field);
        $hasNewFile = $file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $wantsRemove = $request->input($removeField) === '1';

        if (!$hasNewFile && !$wantsRemove) {
            return null;
        }

        if ($hasNewFile) {
            try {
                $newPath = Uploader::storeImage($file, 'pages');
            } catch (RuntimeException $e) {
                $errors[$field] = [$e->getMessage()];

                return null;
            }

            $this->unlinkImage($currentPath);

            return $newPath;
        }

        $this->unlinkImage($currentPath);

        return '';
    }

    /** Recursively unlinks every /uploads/... path found anywhere in a decoded section content array. */
    private function unlinkAllImagesIn(array $content): void
    {
        foreach ($content as $value) {
            if (is_array($value)) {
                $this->unlinkAllImagesIn($value);
            } elseif (is_string($value) && str_starts_with($value, '/uploads/')) {
                $this->unlinkImage($value);
            }
        }
    }

    private function unlinkImage(string $relativePath): void
    {
        if ($relativePath === '') {
            return;
        }

        $path = PUBLIC_PATH . $relativePath;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
