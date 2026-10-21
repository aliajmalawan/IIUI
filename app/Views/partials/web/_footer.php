<?php
/**
 * Public footer — 5-column layout (brand/social, Quick Links, Explore,
 * Contact, Find Us map) modeled on the user's own reference site. Renders
 * the "quick_links" CMS menu plus already-public settings (site name/logo/
 * tagline, address/phone/email, social links) — same cross-cutting-partial
 * pattern as the header. "Explore" points at real existing public routes
 * (there's no separate CMS menu location for it); no Newsletter/Legal
 * columns since there's no subscription backend or Privacy/Terms pages to
 * link to — inventing those would just be dead ends.
 */
use App\Repositories\MenuItemRepository;
use App\Repositories\SettingRepository;

$footerSettings = new SettingRepository();
$footerSiteName = (string) $footerSettings->get('site_name', (string) config('app.name'));
$footerLogo = $footerSettings->get('site_logo');
$footerTagline = trim((string) $footerSettings->get('site_tagline', ''));
$footerAddress = trim((string) $footerSettings->get('address', ''));
$footerPhone = trim((string) $footerSettings->get('phone', ''));
$footerEmail = trim((string) $footerSettings->get('email', ''));
$footerMapSrc = $footerAddress !== '' ? 'https://www.google.com/maps?q=' . rawurlencode($footerAddress) . '&output=embed' : '';

$socialLinks = [
    ['url' => (string) $footerSettings->get('facebook_url', ''),  'slug' => 'facebook',  'icon' => 'facebook',  'label' => 'Facebook'],
    ['url' => (string) $footerSettings->get('instagram_url', ''), 'slug' => 'instagram', 'icon' => 'instagram', 'label' => 'Instagram'],
    ['url' => (string) $footerSettings->get('twitter_url', ''),   'slug' => 'twitter-x', 'icon' => 'twitter-x', 'label' => 'Twitter / X'],
    ['url' => (string) $footerSettings->get('youtube_url', ''),   'slug' => 'youtube',   'icon' => 'youtube',   'label' => 'YouTube'],
    ['url' => (string) $footerSettings->get('linkedin_url', ''),  'slug' => 'linkedin',  'icon' => 'linkedin',  'label' => 'LinkedIn'],
];
$socialLinks = array_values(array_filter($socialLinks, static fn (array $s) => $s['url'] !== ''));

$quickLinkItems = (new MenuItemRepository())->activeTreeForLocation('quick_links');

$exploreLinks = [
    ['url' => '/gallery',   'label' => 'Gallery'],
    ['url' => '/news',      'label' => 'News'],
    ['url' => '/events',    'label' => 'Events'],
    ['url' => '/downloads', 'label' => 'Downloads'],
    ['url' => '/faculty',   'label' => 'Faculty'],
];

$contactRows = [];
if ($footerAddress !== '') {
    $contactRows[] = ['icon' => 'geo-alt-fill', 'href' => null, 'text' => $footerAddress];
}
if ($footerPhone !== '') {
    $contactRows[] = ['icon' => 'telephone', 'href' => 'tel:' . preg_replace('/[^+0-9]/', '', $footerPhone), 'text' => $footerPhone];
}
if ($footerEmail !== '') {
    $contactRows[] = ['icon' => 'envelope', 'href' => 'mailto:' . $footerEmail, 'text' => $footerEmail];
}
?>
<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-col footer-brand-col">
      <a class="footer-brand" href="<?= e(base_url()) ?>">
        <?php if ($footerLogo !== null && is_file(PUBLIC_PATH . $footerLogo)): ?>
          <img src="<?= e(base_url(ltrim($footerLogo, '/'))) ?>" alt="<?= e($footerSiteName) ?>" class="footer-brand-logo" decoding="async">
        <?php else: ?>
          <span class="footer-brand-text-fallback"><?= e($footerSiteName) ?></span>
        <?php endif; ?>
      </a>

      <?php if ($footerTagline !== ''): ?>
        <p class="footer-motto">&ldquo;<?= e($footerTagline) ?>&rdquo;</p>
      <?php endif; ?>

      <?php if ($socialLinks !== []): ?>
        <div class="footer-social">
          <?php foreach ($socialLinks as $social): ?>
            <a href="<?= e(resolve_url($social['url'])) ?>" target="_blank" rel="noopener" class="edu-soc-<?= e($social['slug']) ?>" aria-label="<?= e($social['label']) ?>"><?= icon($social['icon']) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($quickLinkItems !== []): ?>
      <nav class="footer-col footer-links" aria-label="Quick Links">
        <p class="footer-col-title">Quick Links</p>
        <?php foreach ($quickLinkItems as $item): ?>
          <a href="<?= e(resolve_url((string) $item['url'])) ?>" target="<?= e($item['target']) ?>"><?= e($item['title']) ?></a>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>

    <nav class="footer-col footer-links" aria-label="Explore">
      <p class="footer-col-title">Explore</p>
      <?php foreach ($exploreLinks as $link): ?>
        <a href="<?= e(resolve_url($link['url'])) ?>"><?= e($link['label']) ?></a>
      <?php endforeach; ?>
    </nav>

    <?php if ($contactRows !== []): ?>
      <div class="footer-col">
        <p class="footer-col-title">Contact Us</p>
        <ul class="footer-contact">
          <?php foreach ($contactRows as $row): ?>
            <li>
              <span class="fc-icon"><?= icon($row['icon']) ?></span>
              <?php if ($row['href'] !== null): ?>
                <a href="<?= e($row['href']) ?>"><?= e($row['text']) ?></a>
              <?php else: ?>
                <span><?= e($row['text']) ?></span>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($footerMapSrc !== ''): ?>
      <div class="footer-col footer-map-col">
        <p class="footer-col-title">Find Us</p>
        <div class="footer-map">
          <iframe src="<?= e($footerMapSrc) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Campus location map"></iframe>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="container footer-bottom">
    <p>&copy; <?= date('Y') ?> <?= e($footerSiteName) ?>. All rights reserved.</p>
  </div>
</footer>
