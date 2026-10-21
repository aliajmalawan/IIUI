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
use App\Repositories\DesignationRepository;

final class DesignationController extends Controller
{
    private const CATEGORIES = ['teaching', 'non_teaching', 'management', 'support'];

    public function index(Request $request): Response
    {
        return $this->view('admin.faculty.designations.index', [
            'title'        => 'Designations',
            'activeTab'    => 'designations',
            'designations' => (new DesignationRepository())->all(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.faculty.designations.form', [
            'title' => 'Add Designation', 'activeTab' => 'designations', 'designation' => null,
        ], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $designation = (new DesignationRepository())->find((int) $request->parameter('id'));
        if ($designation === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.faculty.designations.form', [
            'title' => 'Edit Designation', 'activeTab' => 'designations', 'designation' => $designation,
        ], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new DesignationRepository())->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new DesignationRepository();
        $designation = $repo->find($id);
        if ($designation === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('faculty.designation_deleted', 'faculty', 'designations', $id, "Deleted designation \"{$designation['name']}\"");
        Session::flash('status', 'Designation deleted.');

        return $this->redirectRoute('admin.faculty.designations');
    }

    private function save(Request $request, ?int $id): Response
    {
        $repo = new DesignationRepository();

        $validator = Validator::make($request->all(), ['name' => 'required|max:100']);
        $errors = $validator->errors();

        $name = (string) $request->input('name', '');
        if ($name !== '' && $repo->nameExists($name, $id)) {
            $errors['name'] = ['This designation name is already in use.'];
        }

        $category = (string) $request->input('category', 'teaching');
        if (!in_array($category, self::CATEGORIES, true)) {
            $category = 'teaching';
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.faculty.designations.create')
                : $this->redirectRoute('admin.faculty.designations.edit', ['id' => $id]);
        }

        $data = [
            'name'      => $name,
            'category'  => $category,
            'rank'      => (int) $request->input('rank', 50),
            'is_active' => $request->has('is_active') ? 1 : 0,
        ];

        if ($id === null) {
            $newId = $repo->create($data);
            Audit::log('faculty.designation_created', 'faculty', 'designations', $newId, "Created designation \"{$data['name']}\"");
            Session::flash('status', 'Designation created.');
        } else {
            $repo->update($id, $data);
            Audit::log('faculty.designation_updated', 'faculty', 'designations', $id, "Updated designation \"{$data['name']}\"");
            Session::flash('status', 'Designation updated.');
        }

        return $this->redirectRoute('admin.faculty.designations');
    }
}
