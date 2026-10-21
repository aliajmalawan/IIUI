<?php
/**
 * Admin shell: sidebar + topbar + content. Theme is resolved SERVER-SIDE
 * from the logged-in user's stored preference (users.theme), so there is no
 * flash-of-wrong-theme to hide with an inline bootstrap script — the
 * authenticated request already knows the answer before the first byte.
 * @var string $title
 */
use App\Core\Auth;

$title ??= 'Dashboard';
$authUser = Auth::user();
$themePref = (string) ($authUser['theme'] ?? 'system');
$themeAttr = $themePref === 'system' ? '' : ' data-bs-theme="' . e($themePref) . '"';
$sidebarCollapsed = ($_COOKIE['sidebar_collapsed'] ?? '0') === '1';
$initials = mb_strtoupper(mb_substr((string) ($authUser['first_name'] ?? '?'), 0, 1));
?><!doctype html>
<html lang="<?= e((string) config('app.locale')) ?>"<?= $themeAttr ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> &mdash; <?= e((string) config('app.name')) ?></title>
<?php require VIEWS_PATH . '/partials/_favicon.php'; ?>
<meta name="theme-color" content="#C62828">
<link rel="preload" href="<?= e(asset('fonts/inter-latin-wght-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(asset('fonts/source-serif-4-latin-wght-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/tokens.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
</head>
<body class="admin-body">
<div class="admin-shell<?= $sidebarCollapsed ? ' is-collapsed' : '' ?>" id="admin-shell">

  <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

  <?php require VIEWS_PATH . '/partials/admin/_sidebar.php'; ?>

  <div class="admin-content">
    <?php require VIEWS_PATH . '/partials/admin/_topbar.php'; ?>
    <main class="admin-main">
      <?= \App\Core\View::yield('content') ?>
    </main>
  </div>
</div>

<!-- Command palette (search) -->
<div class="cmdk-overlay" id="cmdk-overlay">
  <div class="cmdk-box">
    <div class="cmdk-input-row">
      <?= icon('search') ?>
      <input type="text" class="cmdk-input" id="cmdk-input" placeholder="Search admin sections..." autocomplete="off">
      <span class="cmdk-esc">Esc</span>
    </div>
    <div class="cmdk-results" id="cmdk-results"></div>
  </div>
</div>
<script type="application/json" id="cmdk-data"><?= json_encode($navSearchItems ?? [], JSON_UNESCAPED_SLASHES) ?></script>

<script>
  window.ADMIN_THEME_URL = <?= json_encode(route('admin.theme')) ?>;
  window.ADMIN_NOTIF_READ_URL_TEMPLATE = <?= json_encode(base_url('admin/notifications/__ID__/read')) ?>;
  window.ADMIN_NOTIF_READ_ALL_URL = <?= json_encode(route('admin.notifications.read-all')) ?>;
  window.ADMIN_ICON_SPRITE = <?= json_encode(asset('vendor/bootstrap-icons/icons.svg')) ?>;
</script>
<script src="<?= e(asset('vendor/chartjs/chart.umd.min.js')) ?>"></script>
<script src="<?= e(asset('js/admin.js')) ?>"></script>
</body>
</html>
