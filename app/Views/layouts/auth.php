<?php
/**
 * Auth layout — split hero per docs/09-design-components.md §10 (L10):
 * brand panel + centred form card, single column below md.
 * @var string $title
 */
use App\Repositories\SettingRepository;

$title ??= 'Sign in';
$authSettings = new SettingRepository();
$authLogo = (string) $authSettings->get('site_logo', '');
$authSiteName = (string) $authSettings->get('site_name', (string) config('app.name'));
?><!doctype html>
<html lang="<?= e((string) config('app.locale')) ?>">
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
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/auth.css')) ?>">
</head>
<body class="auth-body">
<div class="auth-shell">
  <aside class="auth-brand">
    <a class="auth-brand-logo" href="<?= e(base_url()) ?>">
      <?php if ($authLogo !== '' && is_file(PUBLIC_PATH . $authLogo)): ?>
        <img src="<?= e(base_url(ltrim($authLogo, '/'))) ?>" alt="<?= e($authSiteName) ?>">
      <?php else: ?>
        IIUI
      <?php endif; ?>
    </a>
    <h2>Schools &amp; College<br>Mardan Campus</h2>
    <p>International Islamic University Islamabad</p>
  </aside>
  <main class="auth-panel">
    <div class="auth-card">
<?= \App\Core\View::yield('content') ?>
    </div>
  </main>
</div>
</body>
</html>
