<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AuditLogRepository;

final class AuditLogController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $result = (new AuditLogRepository())->paginate($page, self::PER_PAGE);

        return $this->view('admin.audit-logs', [
            'title'      => 'Audit Log',
            'logs'       => $result['rows'],
            'total'      => $result['total'],
            'page'       => $page,
            'perPage'    => self::PER_PAGE,
            'lastPage'   => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
        ], 'admin');
    }
}
