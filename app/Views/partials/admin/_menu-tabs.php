<?php
/**
 * @var string $location
 * @var array<string,string> $locations
 */
?>
<nav class="settings-tabs" aria-label="Menu locations">
  <?php foreach ($locations as $slug => $label): ?>
    <a class="settings-tab<?= $slug === $location ? ' is-active' : '' ?>"
       href="<?= e(route('admin.menus.location', ['location' => $slug])) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>
