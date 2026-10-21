<?php
/**
 * Primary nav-item list, required by _header.php. No Login/Dashboard/Logout
 * link anywhere here by design — the admin panel is reached only by typing
 * its URL directly, never advertised from the public nav.
 * Expects $headerMenuItems, $applyOnlineHref (from _header.php) and
 * $currentPath / $basePath (from layouts/web.php) to already be set.
 */

// "You are here": a link is current when its path equals the page's, or the page lives under it
// (/news is current on /news/some-post). Home only matches the homepage itself.
$navIsCurrent = static function (string $url) use ($currentPath, $basePath): bool {
    $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
    if ($basePath !== '' && $basePath !== '/' && str_starts_with($path, $basePath)) {
        $path = substr($path, strlen($basePath));
    }
    $path = '/' . trim($path, '/');

    return $path === $currentPath || ($path !== '/' && str_starts_with($currentPath, $path . '/'));
};
$navAttrs = static function (string $url, string $target) use ($navIsCurrent): string {
    $attrs = $navIsCurrent($url) ? ' class="is-active" aria-current="page"' : '';
    if ($target === '_blank') {
        $attrs .= ' target="_blank" rel="noopener noreferrer"';
    }

    return $attrs;
};
?>
<nav class="site-nav" id="site-nav" aria-label="Primary">
  <a href="<?= e(base_url()) ?>"<?= $currentPath === '/' ? ' class="is-active" aria-current="page"' : '' ?>>Home</a>
  <?php foreach ($headerMenuItems as $item): ?>
    <?php $hasLink = trim((string) $item['url']) !== ''; ?>
    <?php if ($item['children'] === []): ?>
      <?php if ($hasLink): ?>
        <?php $itemUrl = resolve_url((string) $item['url']); ?>
        <a href="<?= e($itemUrl) ?>"<?= $navAttrs($itemUrl, (string) $item['target']) ?>><?= e($item['title']) ?></a>
      <?php else: ?>
        <span class="nav-heading"><?= e($item['title']) ?></span>
      <?php endif; ?>
    <?php else: ?>
      <?php
        $childUrls = array_map(static fn (array $c): string => resolve_url((string) $c['url']), $item['children']);
        $parentUrl = $hasLink ? resolve_url((string) $item['url']) : '';
        $groupActive = ($parentUrl !== '' && $navIsCurrent($parentUrl)) || array_filter($childUrls, $navIsCurrent) !== [];
      ?>
      <div class="nav-dropdown<?= $groupActive ? ' is-active' : '' ?>">
        <?php if ($hasLink): ?>
          <a href="<?= e($parentUrl) ?>" class="nav-dropdown-trigger" aria-haspopup="true" aria-expanded="false"<?= $groupActive ? ' aria-current="true"' : '' ?><?= $item['target'] === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>
            <?= e($item['title']) ?> <?= icon('chevron-down', 'icon nav-caret') ?>
          </a>
        <?php else: ?>
          <span class="nav-dropdown-trigger" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false">
            <?= e($item['title']) ?> <?= icon('chevron-down', 'icon nav-caret') ?>
          </span>
        <?php endif; ?>
        <div class="nav-dropdown-panel">
          <?php foreach ($item['children'] as $ci => $child): ?>
            <a href="<?= e($childUrls[$ci]) ?>"<?= $navAttrs($childUrls[$ci], (string) $child['target']) ?>><?= e($child['title']) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>

  <a class="btn btn-accent btn-sm header-apply-btn" href="<?= e($applyOnlineHref) ?>">Apply Online</a>
</nav>
