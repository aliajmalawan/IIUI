<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Uploader;
use App\Core\Validator;
use App\Repositories\DepartmentRepository;
use App\Repositories\DesignationRepository;
use App\Repositories\StaffRepository;
use RuntimeException;

final class FacultyController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin.faculty.index', [
            'title'     => 'Faculty',
            'activeTab' => 'faculty',
            'staff'     => (new StaffRepository())->all(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        $repo = new StaffRepository();

        return $this->view('admin.faculty.form', [
            'title'        => 'Add Faculty Member',
            'activeTab'    => 'faculty',
            'member'       => null,
            'suggestedNo'  => $repo->nextEmployeeNo(),
            'departments'  => (new DepartmentRepository())->activeList(),
            'designations' => (new DesignationRepository())->activeList(),
        ], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $member = (new StaffRepository())->find((int) $request->parameter('id'));
        if ($member === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.faculty.form', [
            'title'        => 'Edit Faculty Member',
            'activeTab'    => 'faculty',
            'member'       => $member,
            'suggestedNo'  => $member['employee_no'],
            'departments'  => (new DepartmentRepository())->activeList(),
            'designations' => (new DesignationRepository())->activeList(),
        ], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new StaffRepository())->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new StaffRepository();
        $member = $repo->find($id);
        if ($member === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        $name = trim($member['first_name'] . ' ' . (string) $member['last_name']);
        Audit::log('faculty.deleted', 'faculty', 'staff', $id, "Deleted faculty profile \"{$name}\"");
        Session::flash('status', 'Faculty profile deleted.');

        return $this->redirectRoute('admin.faculty');
    }

    private function save(Request $request, ?int $id): Response
    {
        $repo = new StaffRepository();
        $existing = $id !== null ? $repo->find($id) : null;

        $validator = Validator::make($request->all(), [
            'first_name'   => 'required|max:80',
            'gender'       => 'required',
            'joining_date' => 'required',
            'employee_no'  => 'required|max:30',
        ]);
        $errors = $validator->errors();

        $gender = (string) $request->input('gender', '');
        if ($gender !== '' && !in_array($gender, ['male', 'female'], true)) {
            $errors['gender'] = ['Please select a valid gender.'];
        }

        $employeeNo = (string) $request->input('employee_no', '');
        if ($employeeNo !== '' && $repo->employeeNoExists($employeeNo, $id)) {
            $errors['employee_no'] = ['This employee number is already in use.'];
        }

        $joiningDate = (string) $request->input('joining_date', '');
        if ($joiningDate !== '' && strtotime($joiningDate) === false) {
            $errors['joining_date'] = ['Please provide a valid date.'];
        }

        $requestedSlug = (string) $request->input('slug', '');
        $baseName = trim((string) $request->input('first_name', '') . ' ' . (string) $request->input('last_name', ''));
        $baseSlug = slugify($requestedSlug !== '' ? $requestedSlug : $baseName);
        $slug = $repo->uniqueSlug($baseSlug, $id);

        $photoPath = $existing['photo'] ?? null;
        $file = $request->file('photo');
        if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $photoPath = Uploader::storeImage($file, 'staff');
            } catch (RuntimeException $e) {
                $errors['photo'] = [$e->getMessage()];
            }
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.faculty.create')
                : $this->redirectRoute('admin.faculty.edit', ['id' => $id]);
        }

        $data = [
            'employee_no'       => $employeeNo,
            'first_name'        => (string) $request->input('first_name'),
            'last_name'         => (string) $request->input('last_name', ''),
            'gender'            => $gender,
            'joining_date'      => date('Y-m-d', strtotime($joiningDate)),
            'photo'             => $photoPath,
            'department_id'     => (int) $request->input('department_id', 0) ?: null,
            'designation_id'    => (int) $request->input('designation_id', 0) ?: null,
            'qualification'     => (string) $request->input('qualification', ''),
            'specialization'    => (string) $request->input('specialization', ''),
            'experience_years'  => (int) $request->input('experience_years', 0),
            'is_teaching'       => 1,
            'is_public_profile' => $request->has('is_public_profile') ? 1 : 0,
            'slug'              => $slug,
            'bio'               => (string) $request->input('bio', ''),
            'sort_order'        => (int) $request->input('sort_order', 0),
            // Status stays out of this lightweight form (docs decision: public-profile
            // subset only); preserve it on edit rather than silently resetting to active.
            'status'            => $existing['status'] ?? 'active',
            'author_id'         => (int) Auth::id(),
        ];

        if ($id === null) {
            $newId = $repo->create($data);
            Audit::log('faculty.created', 'faculty', 'staff', $newId, "Added faculty profile \"{$data['first_name']} {$data['last_name']}\"");
            Session::flash('status', 'Faculty profile created.');

            return $this->redirectRoute('admin.faculty.edit', ['id' => $newId]);
        }

        $repo->update($id, $data);
        Audit::log('faculty.updated', 'faculty', 'staff', $id, "Updated faculty profile \"{$data['first_name']} {$data['last_name']}\"");
        Session::flash('status', 'Faculty profile updated.');

        return $this->redirectRoute('admin.faculty.edit', ['id' => $id]);
    }
}
