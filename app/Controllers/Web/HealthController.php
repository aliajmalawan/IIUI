<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use Throwable;

/**
 * GET /health — for uptime monitors. 200 when the app can reach its database and
 * write to storage, 503 otherwise. Reveals nothing beyond ok/fail per check.
 */
final class HealthController extends Controller
{
    public function show(Request $request): Response
    {
        $checks = ['database' => false, 'storage' => false];

        try {
            $checks['database'] = (int) Database::connection()->query('SELECT 1')->fetchColumn() === 1;
        } catch (Throwable) {
            // reported below as a failed check
        }

        $dir = STORAGE_PATH . '/sessions';
        $checks['storage'] = is_dir($dir) && is_writable($dir);

        $ok = !in_array(false, $checks, true);

        return Response::json(
            ['status' => $ok ? 'ok' : 'degraded', 'checks' => array_map(static fn (bool $c): string => $c ? 'ok' : 'fail', $checks)],
            $ok ? 200 : 503
        )->header('Cache-Control', 'no-store');
    }
}
