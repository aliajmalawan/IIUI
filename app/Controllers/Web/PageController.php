<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\DepartmentRepository;
use App\Repositories\DownloadRepository;
use App\Repositories\HomepageProgramRepository;
use App\Repositories\PageRepository;
use App\Repositories\PageSectionRepository;
use App\Repositories\SettingRepository;
use App\Repositories\StaffRepository;

/**
 * Standalone pages for the nav items that used to just anchor-scroll into
 * homepage sections (About/Academics/Admissions/Contact) — each now has its
 * own URL. Each page's hero (heading/subheading/banner) and content-section
 * stack are owned by its own row in `pages`/`page_sections` (admin "Pages"
 * list) — see PageRepository/PageSectionRepository. About's Principal/Owner
 * messengers and Admissions' button text are still read from `settings`,
 * since those are genuinely shared with the homepage. `show()` at the
 * bottom serves custom (non-system) pages created from the same admin list,
 * at /page/{slug} — routes/web.php.
 */
final class PageController extends Controller
{
    /** @return array{page:?array,heading:string,excerpt:string,heroImage:string,sections:array} */
    private function loadPage(string $slug, string $titleDefault, string $excerptDefault = ''): array
    {
        $page = (new PageRepository())->findBySlug($slug);
        $sections = $page !== null ? (new PageSectionRepository())->forPage((int) $page['id'], true) : [];

        return [
            'page'      => $page,
            'heading'   => $page !== null && trim((string) $page['title']) !== '' ? (string) $page['title'] : $titleDefault,
            'excerpt'   => $page !== null && trim((string) $page['excerpt']) !== '' ? (string) $page['excerpt'] : $excerptDefault,
            'heroImage' => $page !== null ? (string) ($page['featured_image'] ?? '') : '',
            'sections'  => $sections,
        ];
    }

    public function about(Request $request): Response
    {
        $p = $this->loadPage('about', 'About Our Campus');

        return $this->view('web.pages.about', [
            'title'          => 'About Us',
            'heading'        => $p['heading'],
            'subheading'     => $p['excerpt'],
            'messengers'     => $this->loadMessengers(),
            'pageHeroImage'  => $p['heroImage'],
            'sections'       => $p['sections'],
        ], 'web');
    }

    public function leadership(Request $request): Response
    {
        $p = $this->loadPage(
            'leadership',
            'Our Leadership',
            'The people guiding our academic vision and everyday campus life.'
        );

        return $this->view('web.pages.leadership', [
            'title'         => 'Leadership',
            'heading'       => $p['heading'],
            'subheading'    => $p['excerpt'],
            'messengers'    => $this->loadMessengers(),
            'team'          => (new StaffRepository())->leadershipList(),
            'pageHeroImage' => $p['heroImage'],
            'sections'      => $p['sections'],
        ], 'web');
    }

    /** Principal/Owner welcome quotes — shared by About and Leadership, sourced from Homepage Builder settings. */
    private function loadMessengers(): array
    {
        $settings = new SettingRepository();
        $c = static function (string $key, string $default = '') use ($settings): string {
            $value = trim((string) $settings->get($key, ''));

            return $value !== '' ? $value : $default;
        };

        $messengers = [
            [
                'name'        => $c('welcome_principal_name'),
                'designation' => $c('welcome_principal_designation', 'Principal'),
                'photo'       => $c('welcome_principal_photo'),
                'message'     => $c('welcome_principal_message'),
            ],
            [
                'name'        => $c('welcome_owner_name'),
                'designation' => $c('welcome_owner_designation', 'Chairman / Owner'),
                'photo'       => $c('welcome_owner_photo'),
                'message'     => $c('welcome_owner_message'),
            ],
        ];

        return array_values(array_filter($messengers, static fn (array $m) => $m['name'] !== '' && $m['message'] !== ''));
    }

    public function departments(Request $request): Response
    {
        $p = $this->loadPage(
            'departments',
            'Our Academic Departments',
            'The subject teams behind every class, from Primary through College.'
        );

        return $this->view('web.pages.departments', [
            'title'         => 'Departments',
            'heading'       => $p['heading'],
            'subheading'    => $p['excerpt'],
            'departments'   => (new DepartmentRepository())->publicList(),
            'pageHeroImage' => $p['heroImage'],
            'sections'      => $p['sections'],
        ], 'web');
    }

    public function academics(Request $request): Response
    {
        $p = $this->loadPage(
            'academics',
            'Our Academic Programs',
            'A structured path from Play Group through College, built on a strong academic and Islamic foundation.'
        );

        return $this->view('web.pages.academics', [
            'title'         => 'Academics',
            'heading'       => $p['heading'],
            'subheading'    => $p['excerpt'],
            'programs'      => (new HomepageProgramRepository())->activeList(),
            'pageHeroImage' => $p['heroImage'],
            'sections'      => $p['sections'],
        ], 'web');
    }

    public function admissions(Request $request): Response
    {
        $settings = new SettingRepository();
        $p = $this->loadPage('admissions', 'Admissions Open');

        return $this->view('web.pages.admissions', [
            'title'         => 'Admissions',
            'heading'       => $p['heading'],
            'body'          => $p['excerpt'],
            'buttonText'    => (string) $settings->get('cta_button_text', 'Apply Now'),
            'applyHref'     => resolve_url('/admission-form'),
            'programs'      => (new HomepageProgramRepository())->activeList(),
            'downloads'     => (new DownloadRepository())->publicList('Admissions'),
            'pageHeroImage' => $p['heroImage'],
            'sections'      => $p['sections'],
        ], 'web');
    }

    public function contact(Request $request): Response
    {
        $p = $this->loadPage(
            'contact',
            "We'd Love to Hear From You",
            'Questions about admissions, academics, or anything else? Reach out below.'
        );

        return $this->view('web.pages.contact', [
            'title'         => 'Contact Us',
            'heading'       => $p['heading'],
            'subheading'    => $p['excerpt'],
            'pageHeroImage' => $p['heroImage'],
            'sections'      => $p['sections'],
        ], 'web');
    }

    /** Custom pages created from the admin "Pages" list — /page/{slug}. */
    public function show(Request $request): Response
    {
        $slug = (string) $request->parameter('slug');
        $page = (new PageRepository())->findBySlug($slug);

        if ($page === null || (int) $page['is_system'] === 1 || $page['status'] !== 'published') {
            throw new HttpException(404);
        }

        $hasHeroImage = !empty($page['featured_image']) && is_file(PUBLIC_PATH . $page['featured_image']);

        return $this->view('web.pages.custom', [
            'title'          => (string) $page['title'],
            'heading'        => (string) $page['title'],
            'subheading'     => (string) ($page['excerpt'] ?? ''),
            'pageHeroImage'  => $hasHeroImage ? (string) $page['featured_image'] : '',
            'sections'       => (new PageSectionRepository())->forPage((int) $page['id'], true),
        ], 'web');
    }
}
