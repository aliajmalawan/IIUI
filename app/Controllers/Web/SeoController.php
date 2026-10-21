<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\SettingRepository;

/** sitemap.xml and robots.txt — generated from live CMS content, never stale. */
final class SeoController extends Controller
{
    private const STATIC_PATHS = [
        '/', '/about', '/leadership', '/academics', '/academic-calendar', '/departments',
        '/fee-structure', '/admissions', '/admission-form', '/contact', '/gallery',
        '/news', '/blog', '/events', '/faculty', '/downloads',
    ];

    public function sitemap(Request $request): Response
    {
        $pdo = Database::connection();
        $urls = [];
        foreach (self::STATIC_PATHS as $path) {
            $urls[] = [base_url(ltrim($path, '/')), null];
        }

        $sources = [
            ['SELECT slug, updated_at FROM pages WHERE status = \'published\' AND deleted_at IS NULL AND is_system = 0', '/page/'],
            ['SELECT slug, updated_at FROM posts WHERE status = \'published\' AND deleted_at IS NULL AND post_type = \'news\'', '/news/'],
            ['SELECT slug, updated_at FROM posts WHERE status = \'published\' AND deleted_at IS NULL AND post_type = \'blog\'', '/blog/'],
            ['SELECT slug, updated_at FROM events WHERE status IN (\'published\',\'completed\') AND deleted_at IS NULL', '/events/'],
            ['SELECT slug, updated_at FROM galleries WHERE status = \'published\'', '/gallery/'],
        ];
        foreach ($sources as [$sql, $prefix]) {
            foreach ($pdo->query($sql)->fetchAll() as $row) {
                $urls[] = [base_url(ltrim($prefix, '/') . $row['slug']), $row['updated_at']];
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as [$loc, $updated]) {
            $xml .= '  <url><loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc>';
            if ($updated) {
                $xml .= '<lastmod>' . date('c', strtotime((string) $updated)) . '</lastmod>';
            }
            $xml .= "</url>\n";
        }
        $xml .= '</urlset>' . "\n";

        return Response::make($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    public function robots(Request $request): Response
    {
        $hidden = (string) (new SettingRepository())->get('robots_noindex', '0') === '1';
        $body = $hidden
            ? "User-agent: *\nDisallow: /\n"
            : "User-agent: *\nDisallow: /admin\nDisallow: /login\nAllow: /\n\nSitemap: " . base_url('sitemap.xml') . "\n";

        return Response::make($body, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
