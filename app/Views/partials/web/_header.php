<?php
/**
 * Public header — a two-tier "topbar + navbar" layout (contact/social/apply
 * strip on top, a dark centered menu below), used on every public page.
 * Fetches the "header" menu directly (same cross-cutting-partial pattern as
 * the admin topbar's notification bell) rather than requiring every single
 * web controller to remember to pass menu data. Nav-item markup lives in
 * _nav-menu.php — no Login/Dashboard link there by design.
 */
use App\Repositories\MenuItemRepository;
use App\Repositories\SettingRepository;

$headerSettings = new SettingRepository();
$siteName = (string) $headerSettings->get('site_name', (string) config('app.name'));
$siteLogo = $headerSettings->get('site_logo');
$topbarPhone = trim((string) $headerSettings->get('phone', ''));
$topbarEmail = trim((string) $headerSettings->get('email', ''));
// Routes straight to the standalone application form (not the Admissions
// info page) — this is the site's quick-apply action, so it should not
// require an extra click through a marketing page first.
$applyOnlineHref = resolve_url('/admission-form');

$topbarSocial = [
    ['url' => (string) $headerSettings->get('facebook_url', ''),  'icon' => 'facebook',  'slug' => 'facebook',  'label' => 'Facebook'],
    ['url' => (string) $headerSettings->get('instagram_url', ''), 'icon' => 'instagram', 'slug' => 'instagram', 'label' => 'Instagram'],
    ['url' => (string) $headerSettings->get('twitter_url', ''),   'icon' => 'twitter-x', 'slug' => 'twitter-x', 'label' => 'Twitter / X'],
    ['url' => (string) $headerSettings->get('youtube_url', ''),   'icon' => 'youtube',   'slug' => 'youtube',   'label' => 'YouTube'],
    ['url' => (string) $headerSettings->get('linkedin_url', ''),  'icon' => 'linkedin',  'slug' => 'linkedin',  'label' => 'LinkedIn'],
];
$topbarSocial = array_values(array_filter($topbarSocial, static fn (array $s) => $s['url'] !== ''));

$headerMenuItems = (new MenuItemRepository())->activeTreeForLocation('header');
?>
<header class="site-header" id="site-header">
  <div class="edu-topbar">
    <div class="container edu-topbar-inner">
      <a class="brand" href="<?= e(base_url()) ?>" aria-label="<?= e($siteName !== '' ? $siteName : 'Home') ?>">
        <?php if ($siteLogo !== null && is_file(PUBLIC_PATH . $siteLogo)): ?>
          <img src="<?= e(base_url(ltrim($siteLogo, '/'))) ?>" alt="<?= $siteName !== '' ? '' : 'Site logo' ?>" class="brand-logo">
        <?php endif; ?>
        <?php if ($siteName !== ''): ?>
          <span class="brand-text"><?= e($siteName) ?></span>
        <?php endif; ?>
      </a>

      <?php if ($topbarPhone !== '' || $topbarEmail !== ''): ?>
        <div class="edu-topbar-info-group">
          <?php if ($topbarPhone !== ''): ?>
            <a class="edu-topbar-info" href="tel:<?= e(preg_replace('/[^+0-9]/', '', $topbarPhone)) ?>">
              <span class="edu-topbar-info-icon"><?= icon('telephone') ?></span>
              <span><small>Call Us</small><strong><?= e($topbarPhone) ?></strong></span>
            </a>
          <?php endif; ?>
          <?php if ($topbarEmail !== ''): ?>
            <a class="edu-topbar-info" href="mailto:<?= e($topbarEmail) ?>">
              <span class="edu-topbar-info-icon"><?= icon('envelope') ?></span>
              <span><small>Email Us</small><strong><?= e($topbarEmail) ?></strong></span>
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="edu-topbar-actions">
        <?php if ($topbarSocial !== []): ?>
          <nav class="edu-topbar-social" aria-label="Social media">
            <?php foreach ($topbarSocial as $social): ?>
              <a href="<?= e(resolve_url($social['url'])) ?>" target="_blank" rel="noopener" class="edu-soc-<?= e($social['slug']) ?>" aria-label="<?= e($social['label']) ?>"><?= icon($social['icon']) ?></a>
            <?php endforeach; ?>
          </nav>
        <?php endif; ?>

        <a class="edu-apply-btn" href="<?= e($applyOnlineHref) ?>"><?= icon('pencil-square') ?> Apply Online</a>

        <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="site-nav">
          <span class="nav-toggle-bar"></span>
          <span class="nav-toggle-bar"></span>
          <span class="nav-toggle-bar"></span>
        </button>
      </div>
    </div>
  </div>

  <div class="nav-backdrop" id="nav-backdrop"></div>

  <nav class="edu-navbar" aria-label="Primary navigation">
    <div class="container edu-navbar-inner">
      <?php require VIEWS_PATH . '/partials/web/_nav-menu.php'; ?>
    </div>
  </nav>
</header>
