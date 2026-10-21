<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Core student records — admission/personal/contact info and current
 * class-section placement, all columns that live directly on `students`.
 * The richer per-student tables the schema already anticipates
 * (guardians/student_guardians as structured multi-contact records,
 * student_attendance, student_documents, student_fee_discounts,
 * student_promotions, student_transfers, student_transport) belong to
 * their own future modules — same scoping decision already made for
 * Faculty (StaffRepository: "public-profile subset only") and Admissions
 * (test/merit pipeline out of scope). Parent contact here is the simple
 * father_name/father_cnic/father_phone/mother_name/mother_cnic snapshot
 * already on the table, not the separate `guardians` system.
 */
final class StudentRepository
{
    private const SELECT_COLUMNS = 's.id, s.registration_no, s.admission_no, s.first_name, s.last_name, s.name_ur,
        s.father_name, s.father_cnic, s.father_occupation, s.father_phone, s.mother_name, s.mother_cnic,
        s.gender, s.date_of_birth, s.b_form_no, s.religion, s.nationality, s.blood_group, s.photo,
        s.address, s.city, s.district, s.province, s.postal_code, s.phone, s.email,
        s.admission_date, s.admission_class_id, s.current_class_id, s.current_section_id, s.status,
        s.previous_school, s.previous_class, s.transport_required, s.is_orphan, s.is_disabled, s.special_notes,
        s.created_at, s.updated_at';

    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** Admin listing — optional class/section/status/search filters, soft-deleted rows excluded. */
    public function all(?int $classId = null, ?int $sectionId = null, ?string $status = null, string $search = ''): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ', c.name AS class_name, sec.name AS section_name
                  FROM students s
                  LEFT JOIN school_classes c ON c.id = s.current_class_id
                  LEFT JOIN sections sec ON sec.id = s.current_section_id
                 WHERE s.deleted_at IS NULL';
        $params = [];

        if ($classId !== null) {
            $sql .= ' AND s.current_class_id = :class_id';
            $params['class_id'] = $classId;
        }
        if ($sectionId !== null) {
            $sql .= ' AND s.current_section_id = :section_id';
            $params['section_id'] = $sectionId;
        }
        if ($status !== null && $status !== '') {
            $sql .= ' AND s.status = :status';
            $params['status'] = $status;
        }
        if ($search !== '') {
            $sql .= ' AND (s.first_name LIKE :search OR s.last_name LIKE :search
                            OR s.registration_no LIKE :search OR s.admission_no LIKE :search
                            OR s.father_name LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY c.sort_order, sec.name, s.first_name';

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT ' . self::SELECT_COLUMNS . ', c.name AS class_name, sec.name AS section_name
               FROM students s
               LEFT JOIN school_classes c ON c.id = s.current_class_id
               LEFT JOIN sections sec ON sec.id = s.current_section_id
              WHERE s.id = :id AND s.deleted_at IS NULL'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return array<int,array<string,mixed>> active classes, for the admission/current-class dropdowns. */
    public function activeClasses(): array
    {
        return $this->pdo()->query(
            'SELECT id, name, wing FROM school_classes WHERE is_active = 1 ORDER BY sort_order'
        )->fetchAll();
    }

    /** @return array<int,array<string,mixed>> active sections for the CURRENT session, grouped by class_id in PHP by the caller. */
    public function activeSections(): array
    {
        return $this->pdo()->query(
            "SELECT sec.id, sec.class_id, sec.name
               FROM sections sec
               JOIN academic_sessions ses ON ses.id = sec.academic_session_id
              WHERE sec.is_active = 1 AND ses.is_current = 1
              ORDER BY sec.class_id, sec.sort_order"
        )->fetchAll();
    }

    public function registrationNoExists(string $no, ?int $exceptId = null): bool
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM students WHERE registration_no = :no AND id <> :except');
        $stmt->execute(['no' => $no, 'except' => $exceptId ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function admissionNoExists(string $no, ?int $exceptId = null): bool
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM students WHERE admission_no = :no AND id <> :except');
        $stmt->execute(['no' => $no, 'except' => $exceptId ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** Suggests the next sequential "REG-2026-0001" style number; the form field remains editable. */
    public function nextRegistrationNo(): string
    {
        $count = (int) $this->pdo()->query('SELECT COUNT(*) FROM students')->fetchColumn();

        return 'REG-' . date('Y') . '-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public function nextAdmissionNo(): string
    {
        $count = (int) $this->pdo()->query('SELECT COUNT(*) FROM students')->fetchColumn();

        return 'ADM-' . date('Y') . '-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO students
                (registration_no, admission_no, first_name, last_name, name_ur, father_name, father_cnic,
                 father_occupation, father_phone, mother_name, mother_cnic, gender, date_of_birth, b_form_no,
                 religion, nationality, blood_group, photo, address, city, district, province, postal_code,
                 phone, email, admission_date, admission_class_id, current_class_id, current_section_id,
                 status, previous_school, previous_class, transport_required, is_orphan, is_disabled,
                 special_notes, created_by)
             VALUES
                (:registration_no, :admission_no, :first_name, :last_name, :name_ur, :father_name, :father_cnic,
                 :father_occupation, :father_phone, :mother_name, :mother_cnic, :gender, :date_of_birth, :b_form_no,
                 :religion, :nationality, :blood_group, :photo, :address, :city, :district, :province, :postal_code,
                 :phone, :email, :admission_date, :admission_class_id, :current_class_id, :current_section_id,
                 :status, :previous_school, :previous_class, :transport_required, :is_orphan, :is_disabled,
                 :special_notes, :author_id)'
        );
        $stmt->execute($this->bindings($data) + ['author_id' => $data['author_id']]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $bindings = $this->bindings($data);
        $bindings['id'] = $id;
        $bindings['updated_by'] = $data['author_id'];

        $stmt = $this->pdo()->prepare(
            'UPDATE students SET
                registration_no = :registration_no, admission_no = :admission_no, first_name = :first_name,
                last_name = :last_name, name_ur = :name_ur, father_name = :father_name, father_cnic = :father_cnic,
                father_occupation = :father_occupation, father_phone = :father_phone, mother_name = :mother_name,
                mother_cnic = :mother_cnic, gender = :gender, date_of_birth = :date_of_birth, b_form_no = :b_form_no,
                religion = :religion, nationality = :nationality, blood_group = :blood_group, photo = :photo,
                address = :address, city = :city, district = :district, province = :province, postal_code = :postal_code,
                phone = :phone, email = :email, admission_date = :admission_date, admission_class_id = :admission_class_id,
                current_class_id = :current_class_id, current_section_id = :current_section_id, status = :status,
                previous_school = :previous_school, previous_class = :previous_class,
                transport_required = :transport_required, is_orphan = :is_orphan, is_disabled = :is_disabled,
                special_notes = :special_notes, updated_by = :updated_by
             WHERE id = :id'
        );
        $stmt->execute($bindings);
    }

    /** Soft delete — operational SIS records are never hard-removed (docs/01-architecture.md §1). */
    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('UPDATE students SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function bindings(array $data): array
    {
        return [
            'registration_no'    => $data['registration_no'],
            'admission_no'       => $data['admission_no'],
            'first_name'         => $data['first_name'],
            'last_name'          => $data['last_name'] ?: null,
            'name_ur'            => $data['name_ur'] ?: null,
            'father_name'        => $data['father_name'],
            'father_cnic'        => $data['father_cnic'] ?: null,
            'father_occupation'  => $data['father_occupation'] ?: null,
            'father_phone'       => $data['father_phone'] ?: null,
            'mother_name'        => $data['mother_name'] ?: null,
            'mother_cnic'        => $data['mother_cnic'] ?: null,
            'gender'             => $data['gender'],
            'date_of_birth'      => $data['date_of_birth'],
            'b_form_no'          => $data['b_form_no'] ?: null,
            'religion'           => $data['religion'] ?: 'Islam',
            'nationality'        => $data['nationality'] ?: 'Pakistani',
            'blood_group'        => $data['blood_group'] ?: null,
            'photo'              => $data['photo'] ?: null,
            'address'            => $data['address'] ?: null,
            'city'               => $data['city'] ?: null,
            'district'           => $data['district'] ?: 'Mardan',
            'province'           => $data['province'] ?: 'Khyber Pakhtunkhwa',
            'postal_code'        => $data['postal_code'] ?: null,
            'phone'              => $data['phone'] ?: null,
            'email'              => $data['email'] ?: null,
            'admission_date'     => $data['admission_date'],
            'admission_class_id' => $data['admission_class_id'] ?: null,
            'current_class_id'   => $data['current_class_id'] ?: null,
            'current_section_id' => $data['current_section_id'] ?: null,
            'status'             => $data['status'],
            'previous_school'    => $data['previous_school'] ?: null,
            'previous_class'     => $data['previous_class'] ?: null,
            'transport_required' => $data['transport_required'],
            'is_orphan'          => $data['is_orphan'],
            'is_disabled'        => $data['is_disabled'],
            'special_notes'      => $data['special_notes'] ?: null,
        ];
    }
}
