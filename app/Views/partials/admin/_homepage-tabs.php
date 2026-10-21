<?php
/** @var string|null $activeTab set by the calling view: hero|welcome|about|stats|features|programs|cta|faculty|facilities|campus|gallery|testimonials|sections */
// Ordered to roughly match the live homepage's section order top to bottom.
$tabs = [
    'hero'         => ['label' => 'Hero Slider',   'route' => 'admin.homepage.hero'],
    'welcome'      => ['label' => 'Welcome Message', 'route' => 'admin.homepage.welcome'],
    'about'        => ['label' => 'About',         'route' => 'admin.homepage.about'],
    'stats'        => ['label' => 'Statistics',    'route' => 'admin.homepage.stats'],
    'features'     => ['label' => 'Why Choose Us', 'route' => 'admin.homepage.features'],
    'programs'     => ['label' => 'Academic Programs', 'route' => 'admin.homepage.programs'],
    'cta'          => ['label' => 'Admission CTA', 'route' => 'admin.homepage.cta'],
    'faculty'      => ['label' => 'Our Faculty',   'route' => 'admin.homepage.faculty'],
    'facilities'   => ['label' => 'Facilities',    'route' => 'admin.homepage.facilities'],
    'campus'       => ['label' => 'Vibrant Campus', 'route' => 'admin.homepage.campus'],
    'testimonials' => ['label' => 'Testimonials',  'route' => 'admin.homepage.testimonials'],
    'gallery'      => ['label' => 'Gallery',       'route' => 'admin.homepage.gallery'],
    'sections'     => ['label' => 'Section Titles', 'route' => 'admin.homepage.sections'],
];
?>
<nav class="settings-tabs" aria-label="Homepage Builder sections">
  <?php foreach ($tabs as $slug => $tab): ?>
    <a class="settings-tab<?= $slug === ($activeTab ?? '') ? ' is-active' : '' ?>"
       href="<?= e(route($tab['route'])) ?>"><?= e($tab['label']) ?></a>
  <?php endforeach; ?>
</nav>
