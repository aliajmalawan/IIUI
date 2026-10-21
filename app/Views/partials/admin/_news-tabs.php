<?php
/** @var string|null $activeTab set by the calling view: posts|categories */
$tabs = [
    'posts'      => ['label' => 'Articles',   'route' => 'admin.news'],
    'categories' => ['label' => 'Categories', 'route' => 'admin.news.categories'],
];
?>
<nav class="settings-tabs" aria-label="News sections">
  <?php foreach ($tabs as $slug => $tab): ?>
    <a class="settings-tab<?= $slug === ($activeTab ?? '') ? ' is-active' : '' ?>"
       href="<?= e(route($tab['route'])) ?>"><?= e($tab['label']) ?></a>
  <?php endforeach; ?>
</nav>
