<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Hash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\UserRepository;

/**
 * Admin account management and role assignment — see the notice above
 * UserRepository's "Users & Roles admin module" section for what's
 * deliberately out of scope (editing role/permission definitions).
 */
final class UserController extends Controller
{
    private const STATUSES = ['active', 'inactive', 'suspended', 'pending'];

    public function index(Request $request): Response
    {
        return $this->view('admin.users.index', [
            'title' => 'Users & Roles',
            'users' => (new UserRepository())->allWithRoles(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        $repo = new UserRepository();

        return $this->view('admin.users.form', [
            'title'        => 'Add User',
            'targetUser'   => null,
            'roles'        => $repo->allRoles(),
            'assignedIds'  => [],
            'statuses'     => self::STATUSES,
        ], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $repo = new UserRepository();
        $targetUser = $repo->find((int) $request->parameter('id'));
        if ($targetUser === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.users.form', [
            'title'       => 'Edit User',
            'targetUser'  => $targetUser,
            'roles'       => $repo->allRoles(),
            'assignedIds' => $repo->roleIdsForUser((int) $targetUser['id']),
            'statuses'    => self::STATUSES,
        ], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new UserRepository())->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new UserRepository();
        $targetUser = $repo->find($id);
        if ($targetUser === null) {
            throw new HttpException(404);
        }

        if ($id === (int) Auth::id()) {
            Session::flash('status', 'You cannot delete your own account.');

            return $this->redirectRoute('admin.users');
        }

        $targetIsSuperAdmin = in_array('super_admin', array_column($this->rolesOf($repo, $id), 'slug'), true);
        if ($targetIsSuperAdmin && $repo->countActiveUsersWithRole('super_admin', $id) === 0) {
            Session::flash('status', 'Cannot delete the last active Super Admin — assign another account that role first.');

            return $this->redirectRoute('admin.users');
        }

        $repo->delete($id);
        $name = trim($targetUser['first_name'] . ' ' . (string) $targetUser['last_name']);
        Audit::log('user.deleted', 'users', 'users', $id, "Deleted user account \"{$name}\" ({$targetUser['username']})");
        Session::flash('status', 'User deleted.');

        return $this->redirectRoute('admin.users');
    }

    private function rolesOf(UserRepository $repo, int $userId): array
    {
        $roleIds = $repo->roleIdsForUser($userId);
        $allRoles = $repo->allRoles();

        return array_values(array_filter($allRoles, static fn (array $r) => in_array((int) $r['id'], $roleIds, true)));
    }

    private function save(Request $request, ?int $id): Response
    {
        $repo = new UserRepository();
        $isSuperAdminBefore = $id !== null && in_array('super_admin', array_column($this->rolesOf($repo, $id), 'slug'), true);

        $rules = [
            'username'   => 'required|max:60',
            'email'      => 'required|email|max:191',
            'first_name' => 'required|max:80',
        ];
        // 'min' only skips on null, not '', so on edit the password rules are
        // applied only when a new one was actually typed — otherwise leaving
        // the field blank (to keep the current password) would always fail
        // the length check.
        $passwordProvided = trim((string) $request->input('password', '')) !== '';
        if ($id === null || $passwordProvided) {
            $rules['password'] = 'required|min:10|strong_password|confirmed';
        }

        $validator = Validator::make($request->all(), $rules);
        $errors = $validator->errors();

        $username = trim((string) $request->input('username', ''));
        if ($username !== '' && $repo->usernameExists($username, $id)) {
            $errors['username'][] = 'This username is already in use.';
        }

        $email = trim((string) $request->input('email', ''));
        if ($email !== '' && $repo->emailTakenByAnotherUser($email, $id ?? 0)) {
            $errors['email'][] = 'This email is already in use by another account.';
        }

        $status = (string) $request->input('status', 'active');
        if (!in_array($status, self::STATUSES, true)) {
            $status = 'active';
        }

        $roleIds = array_map('intval', (array) $request->input('roles', []));
        $wasRemovingLastSuperAdmin = $id !== null
            && $isSuperAdminBefore
            && !in_array($this->superAdminRoleId($repo), $roleIds, true)
            && $repo->countActiveUsersWithRole('super_admin', $id) === 0;

        if ($id !== null && $id === (int) Auth::id() && $status !== 'active') {
            $errors['status'][] = 'You cannot deactivate your own account.';
        }
        if ($wasRemovingLastSuperAdmin) {
            $errors['roles'][] = 'Cannot remove the Super Admin role from the last active Super Admin account.';
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.users.create')
                : $this->redirectRoute('admin.users.edit', ['id' => $id]);
        }

        $data = [
            'username'   => $username,
            'email'      => $email,
            'first_name' => (string) $request->input('first_name'),
            'last_name'  => (string) $request->input('last_name', ''),
            'status'     => $status,
            'author_id'  => (int) Auth::id(),
        ];

        $password = (string) $request->input('password', '');

        if ($id === null) {
            $data['password_hash'] = Hash::make($password);
            $newId = $repo->create($data);
            $repo->syncRoles($newId, $roleIds, (int) Auth::id());
            Audit::log('user.created', 'users', 'users', $newId, "Created user account \"{$data['first_name']} {$data['last_name']}\" ({$username})");
            Session::flash('status', 'User created.');

            return $this->redirectRoute('admin.users.edit', ['id' => $newId]);
        }

        $repo->update($id, $data);
        if ($password !== '') {
            $repo->updatePasswordHash($id, Hash::make($password));
        }
        $repo->syncRoles($id, $roleIds, (int) Auth::id());
        Audit::log('user.updated', 'users', 'users', $id, "Updated user account \"{$data['first_name']} {$data['last_name']}\" ({$username})");
        Session::flash('status', 'User updated.');

        return $this->redirectRoute('admin.users.edit', ['id' => $id]);
    }

    private function superAdminRoleId(UserRepository $repo): int
    {
        foreach ($repo->allRoles() as $role) {
            if ($role['slug'] === 'super_admin') {
                return (int) $role['id'];
            }
        }

        return 0;
    }
}
