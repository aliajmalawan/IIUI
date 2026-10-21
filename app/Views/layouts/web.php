<?php
/**
 * Public site base layout.
 * @var string $title
 */
use App\Repositories\SettingRepository;

$title ??= (string) config('app.name');

// wa.me needs the full country code, not the leading-0 local format the
// "phone" setting is stored in (e.g. 0302-8819999) — reuses the same
// setting _header.php shows under "Call Us" rather than adding a second
// admin field just for this.
$whatsappNumber = preg_replace('/[^0-9]/', '', (string) (new SettingRepository())->get('phone', ''));
if ($whatsappNumber !== '' && $whatsappNumber[0] === '0') {
    $whatsappNumber = '92' . substr($whatsappNumber, 1);
}

// The header renders transparent-over-hero ONLY on the homepage — every
// other public page uses the light .hero-minimal banner, which a white-text
// transparent header would make unreadable.
//
// $_SERVER['REQUEST_URI'] still carries the app's own base path (e.g. the
// /IIUI subdirectory this dev install runs under), so it's stripped the
// same way Request::path()/basePath() do it — otherwise this never matches
// '/' on any deployment that isn't the domain root.
$rawPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '/');
$scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
$basePath = (string) preg_replace('#/public$#', '', $scriptDir);
if ($basePath !== '' && $basePath !== '/' && str_starts_with($rawPath, $basePath)) {
    $rawPath = substr($rawPath, strlen($basePath));
}
$currentPath = '/' . trim($rawPath, '/');
$isHomepage = $currentPath === '/';

$seo = require VIEWS_PATH . '/partials/web/_seo.php';
?><!doctype html>
<html lang="<?= e((string) config('app.locale')) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($seo['title']) ?></title>
<?php if ($seo['description'] !== ''): ?><meta name="description" content="<?= e($seo['description']) ?>"><?php endif; ?>
<?php if ($seo['keywords'] !== ''): ?><meta name="keywords" content="<?= e($seo['keywords']) ?>"><?php endif; ?>
<meta name="robots" content="<?= $seo['noindex'] ? 'noindex, nofollow' : 'index, follow' ?>">
<link rel="canonical" href="<?= e($seo['canonical']) ?>">
<?php if ($seo['verification'] !== ''): ?><meta name="google-site-verification" content="<?= e($seo['verification']) ?>"><?php endif; ?>
<meta property="og:type" content="<?= e($seo['type']) ?>">
<meta property="og:site_name" content="<?= e($seo['siteName']) ?>">
<meta property="og:title" content="<?= e($seo['title']) ?>">
<meta property="og:url" content="<?= e($seo['canonical']) ?>">
<?php if ($seo['description'] !== ''): ?><meta property="og:description" content="<?= e($seo['description']) ?>"><?php endif; ?>
<?php if ($seo['image'] !== ''): ?><meta property="og:image" content="<?= e($seo['image']) ?>"><?php endif; ?>
<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@graph' => $seo['jsonld']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<meta name="twitter:card" content="<?= $seo['image'] !== '' ? 'summary_large_image' : 'summary' ?>">
<?php if ($seo['gaId'] !== ''): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($seo['gaId']) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','<?= e($seo['gaId']) ?>');</script>
<?php endif; ?>
<?php require VIEWS_PATH . '/partials/_favicon.php'; ?>
<style>.skip-link{position:absolute;left:-9999px;top:8px;z-index:10000}.skip-link:focus{left:8px}</style>
<meta name="theme-color" content="#C62828">
<link rel="preload" href="<?= e(asset('fonts/inter-latin-wght-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(asset('fonts/source-serif-4-latin-wght-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/tokens.css')) ?>">
<?php if ($isHomepage): // Swiper powers only the homepage sliders — inner pages skip its ~170 KB ?>
<link rel="stylesheet" href="<?= e(asset('vendor/swiper/swiper-bundle.min.css')) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="<?= $isHomepage ? 'is-home' : '' ?>">
<a class="skip-link" href="#main-content">Skip to main content</a>
<?php require VIEWS_PATH . '/partials/web/_header.php'; ?>
<main class="site-main" id="main-content" tabindex="-1">
<?php if (count($seo['breadcrumbs']) > 1): ?>
<nav class="breadcrumb-bar" aria-label="Breadcrumb">
  <div class="container">
    <ol>
<?php foreach ($seo['breadcrumbs'] as $bi => $crumb): ?>
<?php if ($bi === count($seo['breadcrumbs']) - 1): ?>
      <li aria-current="page"><?= e($crumb['name']) ?></li>
<?php else: ?>
      <li><a href="<?= e($crumb['url']) ?>"><?= e($crumb['name']) ?></a></li>
<?php endif; ?>
<?php endforeach; ?>
    </ol>
  </div>
</nav>
<?php endif; ?>
<?= \App\Core\View::yield('content') ?>
</main>
<?php require VIEWS_PATH . '/partials/web/_footer.php'; ?>
<aside aria-label="Quick actions">
<?php if ($whatsappNumber !== ''): ?>
  <a class="whatsapp-fab" href="<?= e('https://wa.me/' . $whatsappNumber) ?>" target="_blank" rel="noopener noreferrer" aria-label="Chat with us on WhatsApp">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21h.01c5.46 0 9.9-4.45 9.9-9.91C21.96 6.45 17.5 2 12.04 2Zm5.8 14.1c-.24.68-1.4 1.32-1.93 1.4-.5.08-1.11.11-1.79-.11-.41-.13-.95-.31-1.63-.6-2.87-1.24-4.74-4.12-4.88-4.31-.14-.19-1.17-1.56-1.17-2.98s.75-2.11 1.02-2.4c.27-.29.58-.36.78-.36.19 0 .39 0 .55.01.18.01.42-.07.65.5.24.58.82 2 .89 2.14.07.14.12.31.02.5-.09.19-.14.31-.28.48-.14.17-.29.37-.42.5-.14.14-.28.29-.12.56.16.28.72 1.19 1.55 1.93 1.06.95 1.96 1.24 2.24 1.38.28.14.44.12.6-.07.17-.19.71-.83.9-1.11.19-.28.38-.24.63-.14.26.09 1.65.78 1.94.92.28.14.47.21.54.33.07.12.07.7-.17 1.38Z"/></svg>
  </a>
<?php endif; ?>
<button type="button" id="scroll-top-btn" class="scroll-top-btn" aria-label="Scroll to top">
  <?= icon('chevron-down') ?>
</button>
</aside>
<?php if ($isHomepage): ?>
<script src="<?= e(asset('vendor/swiper/swiper-bundle.min.js')) ?>" defer></script>
<?php endif; ?>
<script src="<?= e(asset('js/site.js')) ?>" defer></script>
</body>
</html>
