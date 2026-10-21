<?php
/**
 * Resolves per-page SEO data for the public layout. Precedence: values a
 * controller passed explicitly ($metaTitle, $metaDescription, $metaImage,
 * $ogType) > the CMS `pages` row matching the URL slug > site-wide SEO
 * settings. Returns an array; the layout prints the tags.
 *
 * @var string $title
 * @var string $currentPath
 */
use App\Repositories\PageRepository;
use App\Repositories\SettingRepository;

$settings = new SettingRepository();
$page = null;
if (preg_match('#^/page/([a-z0-9-]+)$#i', $currentPath, $m) || preg_match('#^/([a-z0-9-]+)$#i', $currentPath, $m)) {
    $found = (new PageRepository())->findBySlug($m[1]);
    if ($found !== null && $found['status'] === 'published') {
        $page = $found;
    }
}

$pick = static function (?string ...$values): string {
    foreach ($values as $v) {
        if ($v !== null && trim($v) !== '') {
            return trim($v);
        }
    }
    return '';
};
$toUrl = static fn (string $path): string => $path === '' ? '' : (preg_match('#^https?://#i', $path) ? $path : base_url(ltrim($path, '/')));

$siteName = (string) $settings->get('site_name', config('app.name'));
$pageTitle = $pick($metaTitle ?? null, $page['meta_title'] ?? null, $title ?? null, $siteName);
// Search results cut titles at ~60 characters: "Title | Site" when it fits, else the
// title alone, else the title trimmed at a word boundary.
$suffixed = ($pageTitle === $siteName || str_contains($pageTitle, $siteName)) ? $pageTitle : $pageTitle . ' | ' . $siteName;
if (mb_strlen($suffixed) <= 65) {
    $fullTitle = $suffixed;
} elseif (mb_strlen($pageTitle) <= 65) {
    $fullTitle = $pageTitle;
} else {
    $fullTitle = rtrim(preg_replace('/\s+\S*$/u', '', mb_substr($pageTitle, 0, 62)), " ,.;:-") . '…';
}
$description = $pick($metaDescription ?? null, $page['meta_description'] ?? null, $page['excerpt'] ?? null, (string) $settings->get('meta_description', ''));
$image = $toUrl($pick($metaImage ?? null, $page['og_image'] ?? null, $page['featured_image'] ?? null, (string) $settings->get('og_image', ''), (string) $settings->get('site_logo', '')));
$canonical = $pick($page['canonical_url'] ?? null) ?: base_url(ltrim($currentPath, '/'));

// ---- Breadcrumbs (visible bar + BreadcrumbList structured data) ----------------
$breadcrumbs = [];
if ($currentPath !== '/') {
    $sectionLabels = ['news' => 'News & Events', 'blog' => 'Blog', 'events' => 'Events', 'gallery' => 'Gallery', 'faculty' => 'Faculty', 'downloads' => 'Downloads'];
    $segments = explode('/', trim($currentPath, '/'));
    $breadcrumbs[] = ['name' => 'Home', 'url' => base_url()];
    if (count($segments) >= 2 && isset($sectionLabels[$segments[0]])) {
        $breadcrumbs[] = ['name' => $sectionLabels[$segments[0]], 'url' => base_url($segments[0])];
    }
    $breadcrumbs[] = ['name' => trim((string) ($title ?? $pageTitle)), 'url' => $canonical];
}

// ---- JSON-LD ------------------------------------------------------------------
$orgId = base_url() . '#organization';
$logoUrl = $toUrl((string) $settings->get('site_logo', ''));
$sameAs = [];
foreach (['facebook_url', 'youtube_url', 'instagram_url', 'twitter_url', 'linkedin_url'] as $socialKey) {
    $u = trim((string) $settings->get($socialKey, ''));
    if (preg_match('#^https?://#i', $u)) {
        $sameAs[] = $u;
    }
}
$organization = array_filter([
    '@type'       => 'EducationalOrganization',
    '@id'         => $orgId,
    'name'        => $siteName,
    'url'         => base_url(),
    'logo'        => $logoUrl !== '' ? $logoUrl : null,
    'image'       => $image !== '' ? $image : null,
    'description' => trim((string) $settings->get('meta_description', '')) ?: null,
    'telephone'   => trim((string) $settings->get('phone', '')) ?: null,
    'email'       => trim((string) $settings->get('email', '')) ?: null,
    'address'     => trim((string) $settings->get('address', '')) !== '' ? [
        '@type'          => 'PostalAddress',
        'streetAddress'  => trim((string) $settings->get('address', '')),
        'addressRegion'  => 'Khyber Pakhtunkhwa',
        'addressCountry' => 'PK',
    ] : null,
    'sameAs'      => $sameAs !== [] ? $sameAs : null,
]);
$graph = [$organization];

if ($currentPath === '/') {
    $graph[] = ['@type' => 'WebSite', '@id' => base_url() . '#website', 'url' => base_url(), 'name' => $siteName, 'publisher' => ['@id' => $orgId]];
}

if (count($breadcrumbs) > 1) {
    $graph[] = [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => array_map(
            static fn (array $b, int $i): array => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $b['name'], 'item' => $b['url']],
            $breadcrumbs,
            array_keys($breadcrumbs)
        ),
    ];
}

$iso = static fn (?string $d): ?string => ($d !== null && $d !== '' && strtotime($d) !== false) ? date('c', strtotime($d)) : null;

if (isset($post) && is_array($post) && isset($post['content'])) {
    $isBlog = str_starts_with($currentPath, '/blog/');
    $graph[] = array_filter([
        '@type'            => $isBlog ? 'BlogPosting' : 'NewsArticle',
        'headline'         => mb_substr((string) $post['title'], 0, 110),
        'description'      => $description !== '' ? $description : null,
        'image'            => $image !== '' ? [$image] : null,
        'datePublished'    => $iso($post['published_at'] ?? null),
        'dateModified'     => $iso($post['updated_at'] ?? $post['published_at'] ?? null),
        'author'           => ['@id' => $orgId],
        'publisher'        => ['@id' => $orgId],
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
    ]);
}

if (isset($event) && is_array($event) && isset($event['start_datetime'])) {
    $graph[] = array_filter([
        '@type'               => 'Event',
        'name'                => (string) $event['title'],
        'description'         => $description !== '' ? $description : null,
        'startDate'           => $iso($event['start_datetime']),
        'endDate'             => $iso($event['end_datetime'] ?? null),
        'eventStatus'         => ($event['status'] ?? '') === 'cancelled' ? 'https://schema.org/EventCancelled' : 'https://schema.org/EventScheduled',
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'location'            => array_filter([
            '@type'   => 'Place',
            'name'    => trim((string) ($event['venue'] ?? '')) ?: $siteName,
            'address' => trim((string) $settings->get('address', '')) ?: null,
        ]),
        'image'               => $image !== '' ? [$image] : null,
        'organizer'           => ['@id' => $orgId],
        'url'                 => $canonical,
    ]);
}

return [
    'breadcrumbs'  => $breadcrumbs,
    'jsonld'       => $graph,
    'title'        => $fullTitle,
    'description'  => mb_substr($description, 0, 300),
    'keywords'     => $pick($page['meta_keywords'] ?? null, (string) $settings->get('meta_keywords', '')),
    'image'        => $image,
    'canonical'    => $canonical,
    'type'         => $pick($ogType ?? null) ?: 'website',
    'siteName'     => $siteName,
    'noindex'      => (string) $settings->get('robots_noindex', '0') === '1',
    'gaId'         => preg_match('/^(G|UA|GT)-[A-Z0-9-]+$/i', trim((string) $settings->get('google_analytics_id', ''))) ? trim((string) $settings->get('google_analytics_id')) : '',
    'verification' => trim((string) $settings->get('google_site_verification', '')),
];
