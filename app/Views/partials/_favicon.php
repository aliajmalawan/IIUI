<?php
/**
 * Favicon <link> tags, shared by all three layouts (web/admin/auth) — reads
 * the "Site Favicon" image uploaded via Settings -> General. Previously that
 * setting was stored and served correctly but never actually referenced by
 * any <head>, so an uploaded favicon never showed up in the browser tab.
 */
use App\Repositories\SettingRepository;

$faviconPath = (string) (new SettingRepository())->get('site_favicon', '');

if ($faviconPath !== '' && is_file(PUBLIC_PATH . $faviconPath)):
    $faviconUrl = base_url(ltrim($faviconPath, '/'));
    $faviconMime = match (strtolower((string) pathinfo($faviconPath, PATHINFO_EXTENSION))) {
        'jpg', 'jpeg' => 'image/jpeg',
        'webp'        => 'image/webp',
        default       => 'image/png',
    };
?>
<link rel="icon" type="<?= e($faviconMime) ?>" href="<?= e($faviconUrl) ?>">
<link rel="apple-touch-icon" href="<?= e($faviconUrl) ?>">
<?php endif; ?>
