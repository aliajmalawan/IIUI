<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\StaffRepository;

final class FacultyController extends Controller
{
    public function index(Request $request): Response
    {
        $repo = new StaffRepository();
        $departmentId = (int) $request->query('department', 0) ?: null;

        return $this->view('web.faculty.index', [
            'title'            => 'Faculty',
            'staff'            => $repo->publicList($departmentId),
            'departments'      => $repo->publicDepartments(),
            'totalCount'       => $repo->publicTotal(),
            'activeDepartment' => $departmentId,
        ], 'web');
    }

    public function show(Request $request): Response
    {
        $slug = (string) $request->parameter('slug');
        $member = (new StaffRepository())->findPublicBySlug($slug);

        if ($member === null) {
            throw new HttpException(404);
        }

        return $this->view('web.faculty.show', [
            'title'  => trim($member['first_name'] . ' ' . (string) $member['last_name']),
            'member' => $member,
        ], 'web');
    }
}
