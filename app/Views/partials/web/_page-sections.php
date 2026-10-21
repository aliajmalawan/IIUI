<?php
/**
 * Renders a page's content-section stack (admin "Pages" → Sections).
 * @var array $sections active page_sections rows for one page, in display order
 */
use App\Repositories\PageSectionRepository;

foreach ($sections as $section):
    $templatePath = VIEWS_PATH . '/web/sections/' . $section['section_type'] . '.php';
    if (!is_file($templatePath)) {
        continue;
    }
    $content = PageSectionRepository::decodeContent($section['content']);
    require $templatePath;
endforeach;
