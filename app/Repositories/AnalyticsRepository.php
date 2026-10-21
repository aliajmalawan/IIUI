<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Read-only aggregate queries for the admin Analytics dashboard — both real
 * website traffic (page_views, populated by PageViewMiddleware) and a
 * "leads & content" overview across the site's actual data (admissions,
 * contact messages, news, events, galleries, downloads, staff, users).
 * Deliberately its own repository rather than new methods scattered across
 * eight unrelated repositories, since every query here exists only to feed
 * this one dashboard.
 */
final class AnalyticsRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    // ---- Page views (website traffic) --------------------------------------

    public function viewsCountTotal(): int
    {
        return (int) $this->pdo()->query('SELECT COUNT(*) FROM page_views')->fetchColumn();
    }

    public function viewsCountSince(int $days): int
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM page_views WHERE viewed_at >= (NOW() - INTERVAL :days DAY)');
        $stmt->execute(['days' => $days]);

        return (int) $stmt->fetchColumn();
    }

    public function viewsCountToday(): int
    {
        return (int) $this->pdo()->query('SELECT COUNT(*) FROM page_views WHERE DATE(viewed_at) = CURDATE()')->fetchColumn();
    }

    public function viewsCountYesterday(): int
    {
        return (int) $this->pdo()->query("SELECT COUNT(*) FROM page_views WHERE DATE(viewed_at) = (CURDATE() - INTERVAL 1 DAY)")->fetchColumn();
    }

    public function viewsCountThisMonth(): int
    {
        return (int) $this->pdo()->query(
            'SELECT COUNT(*) FROM page_views WHERE YEAR(viewed_at) = YEAR(NOW()) AND MONTH(viewed_at) = MONTH(NOW())'
        )->fetchColumn();
    }

    public function viewsCountThisYear(): int
    {
        return (int) $this->pdo()->query('SELECT COUNT(*) FROM page_views WHERE YEAR(viewed_at) = YEAR(NOW())')->fetchColumn();
    }

    public function onlineNow(int $minutes = 5): int
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(DISTINCT ip_hash) FROM page_views WHERE viewed_at >= (NOW() - INTERVAL :minutes MINUTE)');
        $stmt->execute(['minutes' => $minutes]);

        return (int) $stmt->fetchColumn();
    }

    public function uniqueVisitorsSince(int $days): int
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(DISTINCT ip_hash) FROM page_views WHERE viewed_at >= (NOW() - INTERVAL :days DAY)');
        $stmt->execute(['days' => $days]);

        return (int) $stmt->fetchColumn();
    }

    /** @return array<string,int> date (Y-m-d) => view count, zero-filled for days with no views */
    public function dailyCounts(int $days): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT DATE(viewed_at) AS day, COUNT(*) AS c FROM page_views
              WHERE viewed_at >= (NOW() - INTERVAL :days DAY)
              GROUP BY DATE(viewed_at)'
        );
        $stmt->execute(['days' => $days]);
        $byDay = array_column($stmt->fetchAll(), 'c', 'day');

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $result[$date] = (int) ($byDay[$date] ?? 0);
        }

        return $result;
    }

    /** @return array<int,array<string,mixed>> */
    public function topPages(int $limit = 15, ?int $days = null): array
    {
        $where = $days !== null ? 'WHERE viewed_at >= (NOW() - INTERVAL ' . (int) $days . ' DAY)' : '';
        $stmt = $this->pdo()->prepare("SELECT url, COUNT(*) AS views, COUNT(DISTINCT ip_hash) AS visitors FROM page_views {$where} GROUP BY url ORDER BY views DESC LIMIT " . max(1, $limit));
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** External referrers only — visits that came from another website, not internal navigation. */
    public function topReferrers(int $limit = 10, ?int $days = null): array
    {
        $range = $days !== null ? ' AND viewed_at >= (NOW() - INTERVAL ' . (int) $days . ' DAY)' : '';
        $stmt = $this->pdo()->prepare(
            "SELECT referrer, COUNT(*) AS views FROM page_views
              WHERE referrer IS NOT NULL AND referrer != ''{$range}
                AND referrer NOT LIKE CONCAT('%', :host, '%')
              GROUP BY referrer ORDER BY views DESC LIMIT " . max(1, $limit)
        );
        $stmt->execute(['host' => $_SERVER['HTTP_HOST'] ?? 'localhost']);

        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public function recentVisits(int $limit = 20): array
    {
        $stmt = $this->pdo()->prepare('SELECT url, referrer, user_agent, viewed_at FROM page_views ORDER BY id DESC LIMIT ' . max(1, $limit));
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array{browsers: array<string,int>, systems: array<string,int>, devices: array<string,int>} */
    public function agentBreakdown(int $days = 30): array
    {
        $stmt = $this->pdo()->prepare('SELECT user_agent, COUNT(*) AS c FROM page_views WHERE viewed_at >= (NOW() - INTERVAL :days DAY) GROUP BY user_agent');
        $stmt->execute(['days' => $days]);

        $browsers = [];
        $systems = [];
        $devices = [];
        foreach ($stmt->fetchAll() as $row) {
            $count = (int) $row['c'];
            $parsed = self::classifyUserAgent((string) $row['user_agent']);
            $browsers[$parsed['browser']] = ($browsers[$parsed['browser']] ?? 0) + $count;
            $systems[$parsed['system']] = ($systems[$parsed['system']] ?? 0) + $count;
            $devices[$parsed['device']] = ($devices[$parsed['device']] ?? 0) + $count;
        }
        arsort($browsers);
        arsort($systems);
        arsort($devices);

        return ['browsers' => $browsers, 'systems' => $systems, 'devices' => $devices];
    }

    /** @return array{browser: string, system: string, device: string} */
    public static function classifyUserAgent(string $ua): array
    {
        $browser = 'Other';
        if (stripos($ua, 'Edg') !== false) { $browser = 'Edge'; }
        elseif (stripos($ua, 'OPR') !== false || stripos($ua, 'Opera') !== false) { $browser = 'Opera'; }
        elseif (stripos($ua, 'SamsungBrowser') !== false) { $browser = 'Samsung Internet'; }
        elseif (stripos($ua, 'Firefox') !== false) { $browser = 'Firefox'; }
        elseif (stripos($ua, 'Chrome') !== false || stripos($ua, 'CriOS') !== false) { $browser = 'Chrome'; }
        elseif (stripos($ua, 'Safari') !== false) { $browser = 'Safari'; }
        elseif ($ua !== '' && $ua !== 'unknown') { $browser = 'Other'; }

        $system = 'Other';
        if (stripos($ua, 'Windows') !== false) { $system = 'Windows'; }
        elseif (stripos($ua, 'Android') !== false) { $system = 'Android'; }
        elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false || stripos($ua, 'iOS') !== false) { $system = 'iOS'; }
        elseif (stripos($ua, 'Mac OS') !== false || stripos($ua, 'Macintosh') !== false) { $system = 'macOS'; }
        elseif (stripos($ua, 'Linux') !== false) { $system = 'Linux'; }

        $device = 'Desktop';
        if (stripos($ua, 'iPad') !== false || stripos($ua, 'Tablet') !== false) { $device = 'Tablet'; }
        elseif (stripos($ua, 'Mobi') !== false || stripos($ua, 'Android') !== false || stripos($ua, 'iPhone') !== false) { $device = 'Mobile'; }

        return ['browser' => $browser, 'system' => $system, 'device' => $device];
    }

    // ---- Leads & content -----------------------------------------------------

    public function admissionsTotal(): int
    {
        return (int) $this->pdo()->query('SELECT COUNT(*) FROM admission_applications')->fetchColumn();
    }

    /** @return array<string,int> status => count, only statuses that actually occur */
    public function admissionsStatusCounts(): array
    {
        $rows = $this->pdo()->query('SELECT status, COUNT(*) AS c FROM admission_applications GROUP BY status')->fetchAll();

        return array_column($rows, 'c', 'status');
    }

    /** @return array<string,int> "Y-m" => new applications that month, zero-filled */
    public function admissionsMonthly(int $months): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c FROM admission_applications
              WHERE created_at >= (NOW() - INTERVAL :months MONTH)
              GROUP BY ym"
        );
        $stmt->execute(['months' => $months]);
        $byMonth = array_column($stmt->fetchAll(), 'c', 'ym');

        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-{$i} months"));
            $result[$ym] = (int) ($byMonth[$ym] ?? 0);
        }

        return $result;
    }

    public function contactMessagesTotal(): int
    {
        return (int) $this->pdo()->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn();
    }

    /** @return array<string,int> */
    public function contactMessagesStatusCounts(): array
    {
        $rows = $this->pdo()->query('SELECT status, COUNT(*) AS c FROM contact_messages GROUP BY status')->fetchAll();

        return array_column($rows, 'c', 'status');
    }

    public function postsTotal(): int
    {
        return (int) $this->pdo()->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    }

    /** @return array<string,int> */
    public function postsStatusCounts(): array
    {
        $rows = $this->pdo()->query('SELECT status, COUNT(*) AS c FROM posts GROUP BY status')->fetchAll();

        return array_column($rows, 'c', 'status');
    }

    public function eventsTotal(): int
    {
        return (int) $this->pdo()->query('SELECT COUNT(*) FROM events')->fetchColumn();
    }

    public function galleriesTotal(): int
    {
        return (int) $this->pdo()->query('SELECT COUNT(*) FROM galleries')->fetchColumn();
    }

    /** @return array<string,int> */
    public function galleriesStatusCounts(): array
    {
        $rows = $this->pdo()->query('SELECT status, COUNT(*) AS c FROM galleries GROUP BY status')->fetchAll();

        return array_column($rows, 'c', 'status');
    }

    public function downloadsTotal(): int
    {
        return (int) $this->pdo()->query('SELECT COUNT(*) FROM downloads')->fetchColumn();
    }

    /** Active, public-profile staff — same filter as the public Faculty page. */
    public function staffPublicTotal(): int
    {
        return (int) $this->pdo()->query(
            "SELECT COUNT(*) FROM staff WHERE deleted_at IS NULL AND is_public_profile = 1 AND status = 'active'"
        )->fetchColumn();
    }

    public function usersTotal(): int
    {
        return (int) $this->pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }
}
