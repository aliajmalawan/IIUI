<?php
/** @var string|null $activeTab set by the calling view: terms|holidays */
$tabs = [
    'terms'    => ['label' => 'Exam Terms', 'route' => 'admin.exam-terms'],
    'holidays' => ['label' => 'Holidays',   'route' => 'admin.holidays'],
];
?>
<nav class="settings-tabs" aria-label="Academic Calendar sections">
  <?php foreach ($tabs as $slug => $tab): ?>
    <a class="settings-tab<?= $slug === ($activeTab ?? '') ? ' is-active' : '' ?>"
       href="<?= e(route($tab['route'])) ?>"><?= e($tab['label']) ?></a>
  <?php endforeach; ?>
</nav>
