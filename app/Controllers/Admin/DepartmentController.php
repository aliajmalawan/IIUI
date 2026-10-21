<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\DepartmentRepository;

final class DepartmentController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin.faculty.departments.index', [
            'title'       => 'Departments',
            'activeTab'   => 'departments',
            'departments' => (new DepartmentRepository())->all(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.faculty.departments.form', [
            'title' => 'Add Department', 'activeTab' => 'departments', 'department' => null,
        ], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $department = (new DepartmentRepository())->find((int) $request->parameter('id'));
        if ($department === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.faculty.departments.form', [
            'title' => 'Edit Department', 'activeTab' => 'departments', 'department' => $department,
        ], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new DepartmentRepository())->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new DepartmentRepository();
        $department = $repo->find($id);
        if ($department === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('faculty.department_deleted', 'faculty', 'departments', $id, "Deleted department \"{$department['name']}\"");
        Session::flash('status', 'Department deleted.');

        return $this->redirectRoute('admin.faculty.departments');
    }

    private function save(Request $request, ?int $id): Response
    {
        $repo = new DepartmentRepository();

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:100',
            'code' => 'required|max:15',
        ]);
        $errors = $validator->errors();

        $code = mb_strtoupper((string) $request->input('code', ''));
        if ($code !== '' && $repo->codeExists($code, $id)) {
            $errors['code'] = ['This department code is already in use.'];
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.faculty.departments.create')
                : $this->redirectRoute('admin.faculty.departments.edit', ['id' => $id]);
        }

        $data = [
            'name'        => (string) $request->input('name'),
            'code'        => $code,
            'description' => (string) $request->input('description', ''),
            'sort_order'  => (int) $request->input('sort_order', 0),
            'is_active'   => $request->has('is_active') ? 1 : 0,
        ];

        if ($id === null) {
            $newId = $repo->create($data);
            Audit::log('faculty.department_created', 'faculty', 'departments', $newId, "Created department \"{$data['name']}\"");
            Session::flash('status', 'Department created.');
        } else {
            $repo->update($id, $data);
            Audit::log('faculty.department_updated', 'faculty', 'departments', $id, "Updated department \"{$data['name']}\"");
            Session::flash('status', 'Department updated.');
        }

        return $this->redirectRoute('admin.faculty.departments');
    }
}
