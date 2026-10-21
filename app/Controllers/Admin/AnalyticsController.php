<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AnalyticsRepository;

final class AnalyticsController extends Controller
{
    private const RANGES = [7, 30, 90];
    private const DEFAULT_RANGE = 30;
    private const LEADS_CHART_MONTHS = 6;

    public function index(Request $request): Response
    {
        $repo = new AnalyticsRepository();

        $days = (int) $request->query('days', self::DEFAULT_RANGE);
        if (!in_array($days, self::RANGES, true)) {
            $days = self::DEFAULT_RANGE;
        }

        $dailyCounts = $repo->dailyCounts($days);
        $views30 = array_sum($dailyCounts);
        $uniqueVisitors30 = $repo->uniqueVisitorsSince($days);

        if ($request->query('export') === 'csv') {
            return $this->csv($days, $dailyCounts, $repo);
        }

        return $this->view('admin.analytics', [
            'title' => 'Analytics',
            'days'  => $days,
            'ranges' => self::RANGES,

            // Leads & Content
            'admissionsTotal'        => $repo->admissionsTotal(),
            'admissionsStatusCounts' => $repo->admissionsStatusCounts(),
            'admissionsMonthly'      => $repo->admissionsMonthly(self::LEADS_CHART_MONTHS),
            'contactMessagesTotal'        => $repo->contactMessagesTotal(),
            'contactMessagesStatusCounts' => $repo->contactMessagesStatusCounts(),
            'postsTotal'         => $repo->postsTotal(),
            'postsStatusCounts'  => $repo->postsStatusCounts(),
            'eventsTotal'        => $repo->eventsTotal(),
            'galleriesTotal'         => $repo->galleriesTotal(),
            'galleriesStatusCounts'  => $repo->galleriesStatusCounts(),
            'downloadsTotal'     => $repo->downloadsTotal(),
            'staffTotal'         => $repo->staffPublicTotal(),
            'usersTotal'         => $repo->usersTotal(),

            // Website Traffic
            'onlineNow'         => $repo->onlineNow(),
            'totalViews'        => $repo->viewsCountTotal(),
            'views30'           => $views30,
            'views7'            => $repo->viewsCountSince(7),
            'viewsToday'        => $repo->viewsCountToday(),
            'viewsYesterday'    => $repo->viewsCountYesterday(),
            'viewsMonth'        => $repo->viewsCountThisMonth(),
            'viewsYear'         => $repo->viewsCountThisYear(),
            'uniqueVisitors30'  => $uniqueVisitors30,
            'dailyCounts'       => $dailyCounts,
            'topPages'          => $repo->topPages(15, $days),
            'topReferrers'      => $repo->topReferrers(10, $days),
            'agents'            => $repo->agentBreakdown($days),
            'recentVisits'      => $repo->recentVisits(20),
        ], 'admin');
    }

    /** @param array<string,int> $dailyCounts */
    private function csv(int $days, array $dailyCounts, AnalyticsRepository $repo): Response
    {
        $h = fopen('php://temp', 'r+');
        $cell = static fn (mixed $v): mixed => is_string($v) && preg_match('/^[=+\-@	]/', $v) ? "'" . $v : $v;

        fputcsv($h, ['Daily page views - last ' . $days . ' days']);
        fputcsv($h, ['Date', 'Views']);
        foreach ($dailyCounts as $date => $views) {
            fputcsv($h, [$date, $views]);
        }
        fputcsv($h, []);
        fputcsv($h, ['Top pages']);
        fputcsv($h, ['URL', 'Views', 'Unique visitors']);
        foreach ($repo->topPages(100, $days) as $row) {
            fputcsv($h, [$cell($row['url']), $row['views'], $row['visitors']]);
        }
        fputcsv($h, []);
        fputcsv($h, ['Top external referrers']);
        fputcsv($h, ['Referrer', 'Views']);
        foreach ($repo->topReferrers(100, $days) as $row) {
            fputcsv($h, [$cell($row['referrer']), $row['views']]);
        }

        rewind($h);
        $csv = chr(0xEF) . chr(0xBB) . chr(0xBF) . stream_get_contents($h);
        fclose($h);

        return Response::make($csv, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="analytics-' . $days . 'd-' . date('Y-m-d') . '.csv"',
        ]);
    }
}
