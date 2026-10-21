<?php
/** @var string|null $activeTab set by the calling view: faculty|departments|designations */
$tabs = [
    'faculty'      => ['label' => 'Faculty',      'route' => 'admin.faculty'],
    'departments'  => ['label' => 'Departments',  'route' => 'admin.faculty.departments'],
    'designations' => ['label' => 'Designations', 'route' => 'admin.faculty.designations'],
];
?>
<nav class="settings-tabs" aria-label="Faculty sections">
  <?php foreach ($tabs as $slug => $tab): ?>
    <a class="settings-tab<?= $slug === ($activeTab ?? '') ? ' is-active' : '' ?>"
       href="<?= e(route($tab['route'])) ?>"><?= e($tab['label']) ?></a>
  <?php endforeach; ?>
</nav>
