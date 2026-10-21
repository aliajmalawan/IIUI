<?php
/**
 * Sidebar navigation — the single source of truth for both the rendered
 * menu and the command-palette search data ($navSearchItems, read back by
 * the layout after this partial runs). Modules not yet built are shown with
 * a "Soon" badge rather than omitted entirely, so the IA reads as a real
 * enterprise system rather than a two-page demo — but nothing here links
 * anywhere that 404s.
 */
// parse_url() strips any query string, so pagination (?page=2) etc. doesn't
// break the active-nav-item comparison below.
$currentPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '/';

$navSections = [
    [
        'label' => 'Overview',
        'items' => [
            ['label' => 'Dashboard', 'icon' => 'speedometer2', 'route' => 'admin.dashboard'],
            ['label' => 'Analytics', 'icon' => 'bar-chart-line', 'route' => 'admin.analytics', 'activePrefix' => '/admin/analytics'],
        ],
    ],
    [
        'label' => 'Academics',
        'items' => [
            ['label' => 'Students', 'icon' => 'mortarboard', 'route' => 'admin.students', 'activePrefix' => '/admin/students'],
            [
                'label' => 'Admissions', 'icon' => 'award', 'route' => 'admin.admissions',
                'activePrefix' => '/admin/admissions',
            ],
            ['label' => 'Staff', 'icon' => 'person-badge', 'route' => 'admin.staff', 'activePrefix' => '/admin/staff'],
            [
                'label' => 'Academic Calendar', 'icon' => 'calendar3', 'route' => 'admin.exam-terms',
                'activePrefix' => '/admin/exam-terms', 'altPrefixes' => ['/admin/holidays'],
            ],
            [
                'label' => 'Examinations', 'icon' => 'bar-chart-line', 'route' => 'admin.examinations',
                'activePrefix' => '/admin/examinations',
            ],
        ],
    ],
    [
        'label' => 'Finance',
        'items' => [
            [
                'label' => 'Fee Structure', 'icon' => 'receipt', 'route' => 'admin.fee-structures',
                'activePrefix' => '/admin/fee-structures',
            ],
        ],
    ],
    [
        'label' => 'Website',
        'items' => [
            [
                'label' => 'Homepage Builder', 'icon' => 'grid-1x2', 'route' => 'admin.homepage.hero',
                'activePrefix' => '/admin/homepage',
            ],
            [
                'label' => 'Menus', 'icon' => 'list', 'route' => 'admin.menus.location',
                'routeParams' => ['location' => 'header'], 'activePrefix' => '/admin/menus',
            ],
            [
                'label' => 'Gallery', 'icon' => 'images', 'route' => 'admin.galleries',
                'activePrefix' => '/admin/galleries',
            ],
            [
                'label' => 'News', 'icon' => 'megaphone', 'route' => 'admin.news',
                'activePrefix' => '/admin/news',
            ],
            [
                'label' => 'Blog', 'icon' => 'pencil-square', 'route' => 'admin.blog',
                'activePrefix' => '/admin/blog',
            ],
            [
                'label' => 'Events', 'icon' => 'calendar-event', 'route' => 'admin.events',
                'activePrefix' => '/admin/events',
            ],
            [
                'label' => 'Faculty', 'icon' => 'mortarboard', 'route' => 'admin.faculty',
                'activePrefix' => '/admin/faculty',
            ],
            [
                'label' => 'Downloads', 'icon' => 'file-earmark-arrow-down', 'route' => 'admin.downloads',
                'activePrefix' => '/admin/downloads',
            ],
            [
                'label' => 'Pages', 'icon' => 'file-earmark-text', 'route' => 'admin.pages',
                'activePrefix' => '/admin/pages',
            ],
            [
                'label' => 'Media Library', 'icon' => 'grid-1x2', 'route' => 'admin.media-library',
                'activePrefix' => '/admin/media-library',
            ],
        ],
    ],
    [
        'label' => 'System',
        'items' => [
            ['label' => 'Audit Log', 'icon' => 'clock-history', 'route' => 'admin.audit-logs'],
            [
                'label' => 'Contact Messages', 'icon' => 'envelope', 'route' => 'admin.contact-messages',
                'activePrefix' => '/admin/contact-messages',
            ],
            [
                'label' => 'Users & Roles', 'icon' => 'people', 'route' => 'admin.users',
                'activePrefix' => '/admin/users', 'altPrefixes' => ['/admin/roles'],
            ],
            ['label' => 'My Profile', 'icon' => 'person-circle', 'route' => 'admin.profile'],
            [
                'label' => 'Settings', 'icon' => 'gear', 'route' => 'admin.settings.group',
                'routeParams' => ['group' => 'general'], 'activePrefix' => '/admin/settings',
            ],
        ],
    ],
];

// Hide what the signed-in user cannot open (the server enforces this too — see routes/admin.php).
$navPermission = [
    'admin.analytics' => 'reports.view', 'admin.students' => 'students.view', 'admin.admissions' => 'admissions.view',
    'admin.staff' => 'staff.view', 'admin.exam-terms' => 'exams.view', 'admin.examinations' => 'exams.view',
    'admin.fee-structures' => 'fee_structures.view', 'admin.homepage.hero' => 'cms_pages.view', 'admin.menus.location' => 'cms_menus.view',
    'admin.galleries' => 'cms_media.view', 'admin.news' => 'cms_posts.view', 'admin.blog' => 'cms_posts.view',
    'admin.events' => 'cms_posts.view', 'admin.faculty' => 'staff.view', 'admin.downloads' => 'cms_media.view',
    'admin.pages' => 'cms_pages.view', 'admin.media-library' => 'cms_media.view', 'admin.audit-logs' => 'audit_logs.view',
    'admin.contact-messages' => 'messages.view', 'admin.users' => 'users.view', 'admin.settings.group' => 'settings.view',
];
foreach ($navSections as $si => $section) {
    $navSections[$si]['items'] = array_values(array_filter(
        $section['items'],
        static fn (array $item): bool => !isset($navPermission[$item['route'] ?? '']) || \App\Core\Auth::can($navPermission[$item['route']])
    ));
}
$navSections = array_values(array_filter($navSections, static fn (array $s): bool => $s['items'] !== []));

$navSearchItems = [];
foreach ($navSections as $section) {
    foreach ($section['items'] as $item) {
        if (!empty($item['route'])) {
            $navSearchItems[] = [
                'label' => $item['label'],
                'section' => $section['label'],
                'url' => route($item['route'], $item['routeParams'] ?? []),
                'icon' => $item['icon'],
            ];
        }
    }
}
$sidebarLogo = (string) (new \App\Repositories\SettingRepository())->get('site_logo', '');
$hasSidebarLogo = $sidebarLogo !== '' && is_file(PUBLIC_PATH . $sidebarLogo);
?>
<aside class="admin-sidebar" id="admin-sidebar">
  <div class="sidebar-brand">
    <span class="sidebar-brand-mark<?= $hasSidebarLogo ? ' has-logo' : '' ?>"><?php if ($hasSidebarLogo): ?><img src="<?= e(base_url(ltrim($sidebarLogo, '/'))) ?>" alt=""><?php else: ?>II<?php endif; ?></span>
    <span class="sidebar-brand-text">IIUI Mardan<small>Admin Console</small></span>
  </div>

  <nav class="sidebar-nav" aria-label="Admin navigation">
    <?php foreach ($navSections as $section): ?>
      <div class="sidebar-group-label"><?= e($section['label']) ?></div>
      <?php foreach ($section['items'] as $item): ?>
        <?php if (!empty($item['soon'])): ?>
          <span class="sidebar-item is-soon" title="Coming soon">
            <?= icon($item['icon']) ?>
            <span class="sidebar-item-label"><?= e($item['label']) ?></span>
            <span class="sidebar-item-soon">Soon</span>
          </span>
        <?php else:
          $url = route($item['route'], $item['routeParams'] ?? []);
          // activePrefix (+ optional altPrefixes, for one sidebar item that
          // fronts more than one route group — e.g. Academic Calendar covers
          // both /admin/exam-terms and /admin/holidays) is deployment-agnostic
          // (no base-path prefix baked in, since that varies between the
          // current subdirectory dev setup and a future vhost), so it's
          // matched as a substring, not a startsWith.
          $prefixes = array_merge(
              isset($item['activePrefix']) ? [$item['activePrefix']] : [],
              $item['altPrefixes'] ?? []
          );
          $isActive = $prefixes !== []
              ? array_reduce($prefixes, static fn (bool $hit, string $p) => $hit || str_contains($currentPath, $p), false)
              : $currentPath === parse_url($url, PHP_URL_PATH);
        ?>
          <a class="sidebar-item<?= $isActive ? ' is-active' : '' ?>" href="<?= e($url) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
            <?= icon($item['icon']) ?>
            <span class="sidebar-item-label"><?= e($item['label']) ?></span>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <button type="button" class="sidebar-collapse-btn" id="sidebar-collapse-btn">
      <?= icon('chevron-left') ?>
      <span>Collapse</span>
    </button>
  </div>
</aside>
