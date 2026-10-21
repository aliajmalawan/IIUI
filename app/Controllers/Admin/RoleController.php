<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\UserRepository;

/**
 * Read-only role/permission viewer — see the notice above UserRepository's
 * "Users & Roles admin module" section for why editing role definitions
 * (role_permissions) isn't part of this slice.
 */
final class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin.roles.index', [
            'title' => 'Roles',
            'roles' => (new UserRepository())->allRoles(),
        ], 'admin');
    }

    public function show(Request $request): Response
    {
        $repo = new UserRepository();
        $role = $repo->findRole((int) $request->parameter('id'));
        if ($role === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.roles.show', [
            'title'            => $role['name'],
            'role'             => $role,
            'permissionGroups' => $repo->permissionsForRole((int) $role['id']),
        ], 'admin');
    }
}
