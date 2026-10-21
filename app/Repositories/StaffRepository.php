<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * The shared `staff` table backs two admin modules that each own a distinct
 * slice of its columns: Faculty (public-profile subset — see the methods
 * above this notice) and Staff/HR (full employment record — see "Full HR
 * record" below). staff_attendance/staff_documents/staff_subjects/payrolls/
 * leave_applications are separate future modules, same as students'
 * guardians/attendance/documents (docs/03-database §staff).
 */
final class StaffRepository
{
    private const SELECT_COLUMNS = 's.id, s.employee_no, s.first_name, s.last_name, s.gender, s.photo,
        s.department_id, s.designation_id, s.qualification, s.specialization, s.experience_years,
        s.joining_date, s.is_teaching, s.is_public_profile, s.slug, s.bio, s.sort_order, s.status,
        s.created_at, s.updated_at';

    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** Admin listing — every profile, soft-deleted rows excluded. */
    public function all(): array
    {
        return $this->pdo()->query(
            'SELECT ' . self::SELECT_COLUMNS . ', d.name AS department_name, g.name AS designation_name
               FROM staff s
               LEFT JOIN departments d ON d.id = s.department_id
               LEFT JOIN designations g ON g.id = s.designation_id
              WHERE s.deleted_at IS NULL
              ORDER BY s.sort_order, s.first_name'
        )->fetchAll();
    }

    /** Public directory — active status, public profile flag, optionally filtered by department. */
    public function publicList(?int $departmentId = null): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ', d.name AS department_name, g.name AS designation_name
                  FROM staff s
                  LEFT JOIN departments d ON d.id = s.department_id
                  LEFT JOIN designations g ON g.id = s.designation_id
                 WHERE s.deleted_at IS NULL AND s.is_public_profile = 1 AND s.status = \'active\'';
        $params = [];

        if ($departmentId !== null) {
            $sql .= ' AND s.department_id = :dept';
            $params['dept'] = $departmentId;
        }

        $sql .= ' ORDER BY s.sort_order, s.first_name';

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** Leadership page — public profiles whose designation is management-track, senior-most first. */
    public function leadershipList(): array
    {
        return $this->pdo()->query(
            'SELECT ' . self::SELECT_COLUMNS . ', d.name AS department_name, g.name AS designation_name
               FROM staff s
               LEFT JOIN departments d ON d.id = s.department_id
               JOIN designations g ON g.id = s.designation_id
              WHERE s.deleted_at IS NULL AND s.is_public_profile = 1 AND s.status = \'active\'
                AND g.category = \'management\'
              ORDER BY g.rank, s.sort_order, s.first_name'
        )->fetchAll();
    }

    /** @return array<int,array<string,mixed>> departments that currently have at least one public profile */
    /** @return array<int,array{id:int,name:string,total:int}> one row per department with a public profile, for the filter pills' counts */
    public function publicDepartments(): array
    {
        return $this->pdo()->query(
            "SELECT d.id, d.name, COUNT(*) AS total
               FROM staff s
               JOIN departments d ON d.id = s.department_id
              WHERE s.deleted_at IS NULL AND s.is_public_profile = 1 AND s.status = 'active'
              GROUP BY d.id, d.name
              ORDER BY d.name"
        )->fetchAll();
    }

    public function publicTotal(): int
    {
        return (int) $this->pdo()->query(
            "SELECT COUNT(*) FROM staff WHERE deleted_at IS NULL AND is_public_profile = 1 AND status = 'active'"
        )->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT ' . self::SELECT_COLUMNS . ', d.name AS department_name, g.name AS designation_name
               FROM staff s
               LEFT JOIN departments d ON d.id = s.department_id
               LEFT JOIN designations g ON g.id = s.designation_id
              WHERE s.id = :id AND s.deleted_at IS NULL'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findPublicBySlug(string $slug): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT ' . self::SELECT_COLUMNS . ', d.name AS department_name, g.name AS designation_name
               FROM staff s
               LEFT JOIN departments d ON d.id = s.department_id
               LEFT JOIN designations g ON g.id = s.designation_id
              WHERE s.slug = :slug AND s.deleted_at IS NULL AND s.is_public_profile = 1 AND s.status = \'active\''
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM staff WHERE slug = :slug AND id <> :except');
        $stmt->execute(['slug' => $slug, 'except' => $exceptId ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function uniqueSlug(string $base, ?int $exceptId = null): string
    {
        $slug = $base;
        $suffix = 2;
        while ($this->slugExists($slug, $exceptId)) {
            $slug = $base . '-' . $suffix;
            ++$suffix;
        }

        return $slug;
    }

    public function employeeNoExists(string $employeeNo, ?int $exceptId = null): bool
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM staff WHERE employee_no = :no AND id <> :except');
        $stmt->execute(['no' => $employeeNo, 'except' => $exceptId ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** Suggests the next sequential "FAC-0001" style number; the form field remains editable. */
    public function nextEmployeeNo(): string
    {
        $count = (int) $this->pdo()->query('SELECT COUNT(*) FROM staff')->fetchColumn();

        return 'FAC-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO staff
                (employee_no, first_name, last_name, gender, joining_date, photo,
                 department_id, designation_id, qualification, specialization, experience_years,
                 is_teaching, is_public_profile, slug, bio, sort_order, status, created_by)
             VALUES
                (:employee_no, :first_name, :last_name, :gender, :joining_date, :photo,
                 :department_id, :designation_id, :qualification, :specialization, :experience_years,
                 :is_teaching, :is_public_profile, :slug, :bio, :sort_order, :status, :created_by)'
        );
        $stmt->execute($this->bindings($data) + ['created_by' => $data['author_id']]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $bindings = $this->bindings($data);
        $bindings['id'] = $id;
        $bindings['updated_by'] = $data['author_id'];

        $stmt = $this->pdo()->prepare(
            'UPDATE staff SET
                employee_no = :employee_no, first_name = :first_name, last_name = :last_name,
                gender = :gender, joining_date = :joining_date, photo = :photo,
                department_id = :department_id, designation_id = :designation_id,
                qualification = :qualification, specialization = :specialization, experience_years = :experience_years,
                is_teaching = :is_teaching, is_public_profile = :is_public_profile, slug = :slug,
                bio = :bio, sort_order = :sort_order, status = :status, updated_by = :updated_by
             WHERE id = :id'
        );
        $stmt->execute($bindings);
    }

    /** Soft delete — operational HR records are never hard-removed (docs/01-architecture.md §1). */
    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('UPDATE staff SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    // -------------------------------------------------------------------
    // Full HR record — every column on `staff` (payroll, CNIC, employment
    // status, ...) for the dedicated Staff module. This intentionally never
    // touches photo/is_public_profile/slug/bio/sort_order, which stay the
    // Faculty module's exclusive "public website presentation" concern on
    // the same underlying rows. `cnic`/`account_no` travel as opaque
    // already-encrypted bytes here — Crypto::encrypt/decrypt happens in
    // StaffController, matching the app's existing "crypto/uploads are a
    // controller concern" convention.
    // -------------------------------------------------------------------

    /** Admin HR listing — every column, optional department/status/search filters. */
    public function allFull(?int $departmentId = null, ?string $status = null, string $search = ''): array
    {
        $sql = 'SELECT s.*, d.name AS department_name, g.name AS designation_name
                  FROM staff s
                  LEFT JOIN departments d ON d.id = s.department_id
                  LEFT JOIN designations g ON g.id = s.designation_id
                 WHERE s.deleted_at IS NULL';
        $params = [];

        if ($departmentId !== null) {
            $sql .= ' AND s.department_id = :dept';
            $params['dept'] = $departmentId;
        }
        if ($status !== null && $status !== '') {
            $sql .= ' AND s.status = :status';
            $params['status'] = $status;
        }
        if ($search !== '') {
            $sql .= ' AND (s.first_name LIKE :search OR s.last_name LIKE :search OR s.employee_no LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY s.first_name';

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findFull(int $id): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT s.*, d.name AS department_name, g.name AS designation_name
               FROM staff s
               LEFT JOIN departments d ON d.id = s.department_id
               LEFT JOIN designations g ON g.id = s.designation_id
              WHERE s.id = :id AND s.deleted_at IS NULL'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** Suggests the next sequential "EMP-0001" style number for staff created outside the Faculty flow. */
    public function nextStaffNo(): string
    {
        $count = (int) $this->pdo()->query('SELECT COUNT(*) FROM staff')->fetchColumn();

        return 'EMP-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public function cnicHashExists(string $hash, ?int $exceptId = null): bool
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM staff WHERE cnic_hash = :hash AND id <> :except');
        $stmt->execute(['hash' => $hash, 'except' => $exceptId ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function createFull(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO staff
                (employee_no, first_name, last_name, father_name, cnic, cnic_hash, gender, date_of_birth,
                 marital_status, blood_group, department_id, designation_id, qualification, specialization,
                 experience_years, joining_date, employment_type, contract_end_date, basic_salary, bank_name,
                 account_no, phone, emergency_contact, email, address, is_teaching, status, left_date, created_by)
             VALUES
                (:employee_no, :first_name, :last_name, :father_name, :cnic, :cnic_hash, :gender, :date_of_birth,
                 :marital_status, :blood_group, :department_id, :designation_id, :qualification, :specialization,
                 :experience_years, :joining_date, :employment_type, :contract_end_date, :basic_salary, :bank_name,
                 :account_no, :phone, :emergency_contact, :email, :address, :is_teaching, :status, :left_date, :author_id)'
        );
        $stmt->execute($this->hrBindings($data) + ['author_id' => $data['author_id']]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function updateFull(int $id, array $data): void
    {
        $bindings = $this->hrBindings($data);
        $bindings['id'] = $id;
        $bindings['updated_by'] = $data['author_id'];

        $stmt = $this->pdo()->prepare(
            'UPDATE staff SET
                employee_no = :employee_no, first_name = :first_name, last_name = :last_name,
                father_name = :father_name, cnic = :cnic, cnic_hash = :cnic_hash, gender = :gender,
                date_of_birth = :date_of_birth, marital_status = :marital_status, blood_group = :blood_group,
                department_id = :department_id, designation_id = :designation_id, qualification = :qualification,
                specialization = :specialization, experience_years = :experience_years, joining_date = :joining_date,
                employment_type = :employment_type, contract_end_date = :contract_end_date, basic_salary = :basic_salary,
                bank_name = :bank_name, account_no = :account_no, phone = :phone, emergency_contact = :emergency_contact,
                email = :email, address = :address, is_teaching = :is_teaching, status = :status, left_date = :left_date,
                updated_by = :updated_by
             WHERE id = :id'
        );
        $stmt->execute($bindings);
    }

    private function hrBindings(array $data): array
    {
        return [
            'employee_no'       => $data['employee_no'],
            'first_name'        => $data['first_name'],
            'last_name'         => $data['last_name'] ?: null,
            'father_name'       => $data['father_name'] ?: null,
            'cnic'              => $data['cnic'],
            'cnic_hash'         => $data['cnic_hash'],
            'gender'            => $data['gender'],
            'date_of_birth'     => $data['date_of_birth'],
            'marital_status'    => $data['marital_status'],
            'blood_group'       => $data['blood_group'],
            'department_id'     => $data['department_id'],
            'designation_id'    => $data['designation_id'],
            'qualification'     => $data['qualification'] ?: null,
            'specialization'    => $data['specialization'] ?: null,
            'experience_years'  => $data['experience_years'],
            'joining_date'      => $data['joining_date'],
            'employment_type'   => $data['employment_type'],
            'contract_end_date' => $data['contract_end_date'],
            'basic_salary'      => $data['basic_salary'],
            'bank_name'         => $data['bank_name'] ?: null,
            'account_no'        => $data['account_no'],
            'phone'             => $data['phone'] ?: null,
            'emergency_contact' => $data['emergency_contact'] ?: null,
            'email'             => $data['email'] ?: null,
            'address'           => $data['address'] ?: null,
            'is_teaching'       => $data['is_teaching'],
            'status'            => $data['status'],
            'left_date'         => $data['left_date'],
        ];
    }

    private function bindings(array $data): array
    {
        return [
            'employee_no'       => $data['employee_no'],
            'first_name'        => $data['first_name'],
            'last_name'         => $data['last_name'] ?: null,
            'gender'            => $data['gender'],
            'joining_date'      => $data['joining_date'],
            'photo'             => $data['photo'] ?: null,
            'department_id'     => $data['department_id'] ?: null,
            'designation_id'    => $data['designation_id'] ?: null,
            'qualification'     => $data['qualification'] ?: null,
            'specialization'    => $data['specialization'] ?: null,
            'experience_years'  => $data['experience_years'],
            'is_teaching'       => $data['is_teaching'],
            'is_public_profile' => $data['is_public_profile'],
            'slug'              => $data['slug'],
            'bio'               => $data['bio'] ?: null,
            'sort_order'        => $data['sort_order'],
            'status'            => $data['status'],
        ];
    }
}
