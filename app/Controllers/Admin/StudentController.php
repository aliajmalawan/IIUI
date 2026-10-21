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
use App\Repositories\StudentRepository;
use RuntimeException;

final class StudentController extends Controller
{
    private const STATUSES = ['active', 'graduated', 'left', 'suspended', 'expelled', 'transferred'];

    public function index(Request $request): Response
    {
        $repo = new StudentRepository();
        $classId = (int) $request->query('class_id', 0) ?: null;
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));

        return $this->view('admin.students.index', [
            'title'         => 'Students',
            'students'      => $repo->all($classId, null, $status !== '' ? $status : null, $search),
            'classes'       => $repo->activeClasses(),
            'activeClassId' => $classId,
            'activeStatus'  => $status,
            'search'        => $search,
            'statuses'      => self::STATUSES,
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        $repo = new StudentRepository();

        return $this->view('admin.students.form', [
            'title'                 => 'Add Student',
            'student'               => null,
            'suggestedRegNo'        => $repo->nextRegistrationNo(),
            'suggestedAdmissionNo'  => $repo->nextAdmissionNo(),
            'classes'               => $repo->activeClasses(),
            'sections'              => $repo->activeSections(),
        ], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $repo = new StudentRepository();
        $student = $repo->find((int) $request->parameter('id'));
        if ($student === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.students.form', [
            'title'                => 'Edit Student',
            'student'              => $student,
            'suggestedRegNo'       => $student['registration_no'],
            'suggestedAdmissionNo' => $student['admission_no'],
            'classes'              => $repo->activeClasses(),
            'sections'             => $repo->activeSections(),
        ], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new StudentRepository())->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new StudentRepository();
        $student = $repo->find($id);
        if ($student === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        $name = trim($student['first_name'] . ' ' . (string) $student['last_name']);
        Audit::log('student.deleted', 'students', 'students', $id, "Deleted student record \"{$name}\"");
        Session::flash('status', 'Student record deleted.');

        return $this->redirectRoute('admin.students');
    }

    private function save(Request $request, ?int $id): Response
    {
        $repo = new StudentRepository();
        $existing = $id !== null ? $repo->find($id) : null;

        $validator = Validator::make($request->all(), [
            'first_name'      => 'required|max:80',
            'father_name'     => 'required|max:120',
            'date_of_birth'   => 'required',
            'admission_date'  => 'required',
            'registration_no' => 'required|max:30',
            'admission_no'    => 'required|max:30',
            'email'           => 'email|max:191',
        ]);
        $errors = $validator->errors();

        $gender = (string) $request->input('gender', '');
        if (!in_array($gender, ['male', 'female'], true)) {
            $errors['gender'] = ['Please select a gender.'];
        }

        $status = (string) $request->input('status', 'active');
        if (!in_array($status, self::STATUSES, true)) {
            $status = 'active';
        }

        $dob = (string) $request->input('date_of_birth', '');
        if ($dob !== '' && strtotime($dob) === false) {
            $errors['date_of_birth'] = ['Please provide a valid date.'];
        }

        $admissionDate = (string) $request->input('admission_date', '');
        if ($admissionDate !== '' && strtotime($admissionDate) === false) {
            $errors['admission_date'] = ['Please provide a valid date.'];
        }

        $regNo = (string) $request->input('registration_no', '');
        if ($regNo !== '' && $repo->registrationNoExists($regNo, $id)) {
            $errors['registration_no'] = ['This registration number is already in use.'];
        }

        $admissionNo = (string) $request->input('admission_no', '');
        if ($admissionNo !== '' && $repo->admissionNoExists($admissionNo, $id)) {
            $errors['admission_no'] = ['This admission number is already in use.'];
        }

        $photoPath = $existing['photo'] ?? null;
        $file = $request->file('photo');
        if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $photoPath = Uploader::storeImage($file, 'students');
            } catch (RuntimeException $e) {
                $errors['photo'] = [$e->getMessage()];
            }
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.students.create')
                : $this->redirectRoute('admin.students.edit', ['id' => $id]);
        }

        $data = [
            'registration_no'    => $regNo,
            'admission_no'       => $admissionNo,
            'first_name'         => (string) $request->input('first_name'),
            'last_name'          => (string) $request->input('last_name', ''),
            'name_ur'            => (string) $request->input('name_ur', ''),
            'father_name'        => (string) $request->input('father_name'),
            'father_cnic'        => (string) $request->input('father_cnic', ''),
            'father_occupation'  => (string) $request->input('father_occupation', ''),
            'father_phone'       => (string) $request->input('father_phone', ''),
            'mother_name'        => (string) $request->input('mother_name', ''),
            'mother_cnic'        => (string) $request->input('mother_cnic', ''),
            'gender'             => $gender,
            'date_of_birth'      => date('Y-m-d', strtotime($dob)),
            'b_form_no'          => (string) $request->input('b_form_no', ''),
            'religion'           => (string) $request->input('religion', ''),
            'nationality'        => (string) $request->input('nationality', ''),
            'blood_group'        => (string) $request->input('blood_group', ''),
            'photo'              => $photoPath,
            'address'            => (string) $request->input('address', ''),
            'city'               => (string) $request->input('city', ''),
            'district'           => (string) $request->input('district', ''),
            'province'           => (string) $request->input('province', ''),
            'postal_code'        => (string) $request->input('postal_code', ''),
            'phone'              => (string) $request->input('phone', ''),
            'email'              => (string) $request->input('email', ''),
            'admission_date'     => date('Y-m-d', strtotime($admissionDate)),
            'admission_class_id' => (int) $request->input('admission_class_id', 0) ?: null,
            'current_class_id'   => (int) $request->input('current_class_id', 0) ?: null,
            'current_section_id' => (int) $request->input('current_section_id', 0) ?: null,
            'status'             => $status,
            'previous_school'    => (string) $request->input('previous_school', ''),
            'previous_class'     => (string) $request->input('previous_class', ''),
            'transport_required' => $request->has('transport_required') ? 1 : 0,
            'is_orphan'          => $request->has('is_orphan') ? 1 : 0,
            'is_disabled'        => $request->has('is_disabled') ? 1 : 0,
            'special_notes'      => (string) $request->input('special_notes', ''),
            'author_id'          => (int) Auth::id(),
        ];

        if ($id === null) {
            $newId = $repo->create($data);
            Audit::log('student.created', 'students', 'students', $newId, "Added student \"{$data['first_name']} {$data['last_name']}\"");
            Session::flash('status', 'Student record created.');

            return $this->redirectRoute('admin.students.edit', ['id' => $newId]);
        }

        $repo->update($id, $data);
        Audit::log('student.updated', 'students', 'students', $id, "Updated student \"{$data['first_name']} {$data['last_name']}\"");
        Session::flash('status', 'Student record updated.');

        return $this->redirectRoute('admin.students.edit', ['id' => $id]);
    }
}
