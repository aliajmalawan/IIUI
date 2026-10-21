<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AdmissionApplicationRepository;
use App\Repositories\DashboardRepository;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $dashboard = new DashboardRepository();

        return $this->view('admin.dashboard', [
            'title'             => 'Dashboard',
            'totalStudents'     => $dashboard->totalStudents(),
            'totalStaff'        => $dashboard->totalStaff(),
            'totalClasses'      => $dashboard->totalActiveClasses(),
            'totalUsers'        => $dashboard->totalUsers(),
            'pendingAdmissions' => $dashboard->pendingAdmissions(),
            'currentSession'    => $dashboard->currentSession(),
            'classesByWing'     => $dashboard->classesByWing(),
            'subjectsByType'    => $dashboard->subjectsByType(),
            'recentAdmissions'  => (new AdmissionApplicationRepository())->recent(8),
        ], 'admin');
    }
}
