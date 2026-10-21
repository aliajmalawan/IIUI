<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Crypto;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\DepartmentRepository;
use App\Repositories\DesignationRepository;
use App\Repositories\StaffRepository;

/**
 * Full HR record for every staff member (teaching and non-teaching), on the
 * same `staff` table Faculty uses for its public-profile subset — see the
 * notice above StaffRepository's "Full HR record" section for how the two
 * modules divide the columns between them.
 */
final class StaffController extends Controller
{
    private const EMPLOYMENT_TYPES = ['permanent', 'contract', 'visiting', 'probation'];
    private const STATUSES = ['active', 'on_leave', 'resigned', 'terminated', 'retired'];
    private const MARITAL_STATUSES = ['single', 'married', 'widowed', 'divorced'];
    private const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public function index(Request $request): Response
    {
        $repo = new StaffRepository();
        $status = (string) $request->query('status', '');
        $deptId = (int) $request->query('department_id', 0) ?: null;
        $search = trim((string) $request->query('q', ''));

        $rows = $repo->allFull($deptId, $status !== '' ? $status : null, $search);
        foreach ($rows as &$row) {
            $row['cnic_masked'] = self::maskCnic(Crypto::decrypt($row['cnic']));
        }
        unset($row);

        return $this->view('admin.staff.index', [
            'title'        => 'Staff',
            'staff'        => $rows,
            'departments'  => (new DepartmentRepository())->activeList(),
            'activeDept'   => $deptId,
            'activeStatus' => $status,
            'search'       => $search,
            'statuses'     => self::STATUSES,
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        $repo = new StaffRepository();

        return $this->view('admin.staff.form', array_merge($this->formOptions(), [
            'title'       => 'Add Staff Member',
            'member'      => null,
            'suggestedNo' => $repo->nextStaffNo(),
        ]), 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $member = (new StaffRepository())->findFull((int) $request->parameter('id'));
        if ($member === null) {
            throw new HttpException(404);
        }
        $member['cnic'] = Crypto::decrypt($member['cnic']);
        $member['account_no'] = Crypto::decrypt($member['account_no']);

        return $this->view('admin.staff.form', array_merge($this->formOptions(), [
            'title'       => 'Edit Staff Member',
            'member'      => $member,
            'suggestedNo' => $member['employee_no'],
        ]), 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new StaffRepository())->findFull($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new StaffRepository();
        $member = $repo->findFull($id);
        if ($member === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        $name = trim($member['first_name'] . ' ' . (string) $member['last_name']);
        Audit::log('staff.deleted', 'staff', 'staff', $id, "Deleted staff record \"{$name}\"");
        Session::flash('status', 'Staff record deleted.');

        return $this->redirectRoute('admin.staff');
    }

    private function formOptions(): array
    {
        return [
            'departments'     => (new DepartmentRepository())->activeList(),
            'designations'    => (new DesignationRepository())->activeList(),
            'employmentTypes' => self::EMPLOYMENT_TYPES,
            'statuses'        => self::STATUSES,
            'maritalStatuses' => self::MARITAL_STATUSES,
            'bloodGroups'     => self::BLOOD_GROUPS,
        ];
    }

    private function save(Request $request, ?int $id): Response
    {
        $repo = new StaffRepository();

        $validator = Validator::make($request->all(), [
            'first_name'   => 'required|max:80',
            'gender'       => 'required',
            'joining_date' => 'required',
            'employee_no'  => 'required|max:30',
            'email'        => 'email|max:191',
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

        $dob = (string) $request->input('date_of_birth', '');
        if ($dob !== '' && strtotime($dob) === false) {
            $errors['date_of_birth'] = ['Please provide a valid date.'];
        }

        $contractEnd = (string) $request->input('contract_end_date', '');
        if ($contractEnd !== '' && strtotime($contractEnd) === false) {
            $errors['contract_end_date'] = ['Please provide a valid date.'];
        }

        $leftDate = (string) $request->input('left_date', '');
        if ($leftDate !== '' && strtotime($leftDate) === false) {
            $errors['left_date'] = ['Please provide a valid date.'];
        }

        $employmentType = (string) $request->input('employment_type', 'probation');
        if (!in_array($employmentType, self::EMPLOYMENT_TYPES, true)) {
            $employmentType = 'probation';
        }

        $status = (string) $request->input('status', 'active');
        if (!in_array($status, self::STATUSES, true)) {
            $status = 'active';
        }

        $maritalStatus = (string) $request->input('marital_status', '');
        if ($maritalStatus !== '' && !in_array($maritalStatus, self::MARITAL_STATUSES, true)) {
            $maritalStatus = '';
        }

        $bloodGroup = (string) $request->input('blood_group', '');
        if ($bloodGroup !== '' && !in_array($bloodGroup, self::BLOOD_GROUPS, true)) {
            $bloodGroup = '';
        }

        $cnic = trim((string) $request->input('cnic', ''));
        $cnicEncrypted = null;
        $cnicHash = null;
        if ($cnic !== '') {
            $cnicHash = Crypto::hash($cnic);
            if ($repo->cnicHashExists($cnicHash, $id)) {
                $errors['cnic'] = ['This CNIC is already on file for another staff member.'];
            } else {
                $cnicEncrypted = Crypto::encrypt($cnic);
            }
        }

        $accountNo = trim((string) $request->input('account_no', ''));
        $accountEncrypted = $accountNo !== '' ? Crypto::encrypt($accountNo) : null;

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.staff.create')
                : $this->redirectRoute('admin.staff.edit', ['id' => $id]);
        }

        $data = [
            'employee_no'        => $employeeNo,
            'first_name'         => (string) $request->input('first_name'),
            'last_name'          => (string) $request->input('last_name', ''),
            'father_name'        => (string) $request->input('father_name', ''),
            'cnic'               => $cnicEncrypted,
            'cnic_hash'          => $cnicHash,
            'gender'             => $gender,
            'date_of_birth'      => $dob !== '' ? date('Y-m-d', strtotime($dob)) : null,
            'marital_status'     => $maritalStatus !== '' ? $maritalStatus : null,
            'blood_group'        => $bloodGroup !== '' ? $bloodGroup : null,
            'department_id'      => (int) $request->input('department_id', 0) ?: null,
            'designation_id'     => (int) $request->input('designation_id', 0) ?: null,
            'qualification'      => (string) $request->input('qualification', ''),
            'specialization'     => (string) $request->input('specialization', ''),
            'experience_years'   => (int) $request->input('experience_years', 0),
            'joining_date'       => date('Y-m-d', strtotime($joiningDate)),
            'employment_type'    => $employmentType,
            'contract_end_date'  => $contractEnd !== '' ? date('Y-m-d', strtotime($contractEnd)) : null,
            'basic_salary'       => (float) $request->input('basic_salary', 0),
            'bank_name'          => (string) $request->input('bank_name', ''),
            'account_no'         => $accountEncrypted,
            'phone'              => (string) $request->input('phone', ''),
            'emergency_contact'  => (string) $request->input('emergency_contact', ''),
            'email'              => (string) $request->input('email', ''),
            'address'            => (string) $request->input('address', ''),
            'is_teaching'        => $request->has('is_teaching') ? 1 : 0,
            'status'             => $status,
            'left_date'          => $leftDate !== '' ? date('Y-m-d', strtotime($leftDate)) : null,
            'author_id'          => (int) Auth::id(),
        ];

        if ($id === null) {
            $newId = $repo->createFull($data);
            Audit::log('staff.created', 'staff', 'staff', $newId, "Added staff record \"{$data['first_name']} {$data['last_name']}\"");
            Session::flash('status', 'Staff record created.');

            return $this->redirectRoute('admin.staff.edit', ['id' => $newId]);
        }

        $repo->updateFull($id, $data);
        Audit::log('staff.updated', 'staff', 'staff', $id, "Updated staff record \"{$data['first_name']} {$data['last_name']}\"");
        Session::flash('status', 'Staff record updated.');

        return $this->redirectRoute('admin.staff.edit', ['id' => $id]);
    }

    /** "•••••••••1234" — full number kept out of the list view, only the last 4 digits shown. */
    private static function maskCnic(?string $cnic): ?string
    {
        if ($cnic === null || $cnic === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', $cnic) ?? '';
        if (strlen($digits) <= 4) {
            return str_repeat('•', strlen($digits));
        }

        return str_repeat('•', strlen($digits) - 4) . substr($digits, -4);
    }
}
