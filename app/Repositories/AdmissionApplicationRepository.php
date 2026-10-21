<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Public "Apply Now" submissions against the real SIS `admission_applications`
 * table (0003_students_and_guardians.sql) — kept separate from `students` so
 * an unsuccessful applicant never becomes a student record. Covers intake,
 * the admin inbox (status/remarks/listing), and the entrance-test/merit
 * pipeline (test_date/test_roll_no/test_score/interview_remarks/
 * merit_position) — everything on this table except conversion to an
 * enrolled student, which belongs to StudentRepository/AdmissionApplication
 * Controller::convert().
 */
final class AdmissionApplicationRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> active classes grouped by wing, for the applicant's class dropdown. */
    public function activeClasses(): array
    {
        return $this->pdo()->query(
            "SELECT id, name, wing FROM school_classes WHERE is_active = 1 ORDER BY sort_order, id"
        )->fetchAll();
    }

    /** @return array<int,array<string,mixed>> streams (Pre-Medical, Pre-Engineering, ...), only relevant for college-wing classes. */
    public function activeStreams(): array
    {
        return $this->pdo()->query(
            "SELECT id, name FROM streams WHERE is_active = 1 ORDER BY sort_order, id"
        )->fetchAll();
    }

    public function currentSessionId(): ?int
    {
        $id = $this->pdo()->query("SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1")->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /** @return array<int,array<string,mixed>> the newest applications, for the Dashboard's "Recent Admissions" card. */
    public function recent(int $limit): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT a.id, a.application_no, a.applicant_name, a.status, a.created_at, c.name AS class_name
               FROM admission_applications a
               JOIN school_classes c ON c.id = a.class_id
              ORDER BY a.created_at DESC
              LIMIT :limit"
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> newest first, joined with class/stream names for the admin inbox. */
    public function all(): array
    {
        return $this->pdo()->query(
            "SELECT a.*, c.name AS class_name, s.name AS stream_name
               FROM admission_applications a
               JOIN school_classes c ON c.id = a.class_id
          LEFT JOIN streams s ON s.id = a.stream_id
              ORDER BY a.created_at DESC"
        )->fetchAll();
    }

    /**
     * Filtered inbox query. Filters: status (exact), q (reference, applicant, parent, phone, email, B-Form/CNIC contains).
     *
     * @param array{status?:string,q?:string} $filters
     * @return array<int,array<string,mixed>>
     */
    public function search(array $filters, ?int $limit = null, int $offset = 0): array
    {
        [$where, $params] = $this->conditions($filters);
        $sql = "SELECT a.*, c.name AS class_name, s.name AS stream_name
                  FROM admission_applications a
                  JOIN school_classes c ON c.id = a.class_id
             LEFT JOIN streams s ON s.id = a.stream_id" . $where . ' ORDER BY a.created_at DESC, a.id DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);
        }
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @param array{status?:string,q?:string} $filters */
    public function countMatching(array $filters): int
    {
        [$where, $params] = $this->conditions($filters);
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM admission_applications a' . $where);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /** @return array<string,int> status => count (every given status present, zero-filled) */
    public function statusCounts(array $statuses): array
    {
        $counts = array_fill_keys($statuses, 0);
        foreach ($this->pdo()->query('SELECT status, COUNT(*) c FROM admission_applications GROUP BY status')->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['c'];
        }

        return $counts;
    }

    /** @return array{0:string,1:array<string,string>} */
    private function conditions(array $filters): array
    {
        $where = [];
        $params = [];
        if (($filters['status'] ?? '') !== '') {
            $where[] = 'a.status = :status';
            $params['status'] = $filters['status'];
        }
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . addcslashes($q, '\\%_') . '%';
            $parts = [];
            foreach (['application_no', 'applicant_name', 'father_name', 'phone', 'email', 'b_form_no', 'father_cnic'] as $i => $col) {
                $parts[] = "a.$col LIKE :q$i";
                $params["q$i"] = $like;
            }
            $where[] = '(' . implode(' OR ', $parts) . ')';
        }

        return [$where === [] ? '' : ' WHERE ' . implode(' AND ', $where), $params];
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM admission_applications WHERE status = :status');
        $stmt->execute(['status' => $status]);

        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT a.*, c.name AS class_name, s.name AS stream_name
               FROM admission_applications a
               JOIN school_classes c ON c.id = a.class_id
          LEFT JOIN streams s ON s.id = a.stream_id
              WHERE a.id = :id"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Public form submission. `application_no` has to be unique and NOT NULL
     * at insert time but its readable form ("ADM-2026-00001") depends on the
     * row's own id, so it's inserted with a throwaway placeholder and
     * rewritten once the id is known — both inside one transaction.
     */
    public function create(array $data): array
    {
        return Database::transaction(function (PDO $pdo) use ($data): array {
            $stmt = $pdo->prepare(
                'INSERT INTO admission_applications
                    (application_no, academic_session_id, class_id, stream_id, applicant_name, father_name,
                     father_cnic, date_of_birth, gender, b_form_no, phone, email, address, religion, city,
                     whatsapp, previous_school, matric_board, matric_year, matric_marks, matric_total_marks,
                     submitted_ip)
                 VALUES
                    (:app_no, :session_id, :class_id, :stream_id, :applicant_name, :father_name,
                     :father_cnic, :dob, :gender, :b_form_no, :phone, :email, :address, :religion, :city,
                     :whatsapp, :previous_school, :matric_board, :matric_year, :matric_marks, :matric_total_marks,
                     INET6_ATON(:ip))'
            );
            $stmt->execute([
                'app_no'              => 'TMP-' . bin2hex(random_bytes(8)),
                'session_id'          => $data['academic_session_id'],
                'class_id'            => $data['class_id'],
                'stream_id'           => $data['stream_id'] ?: null,
                'applicant_name'      => $data['applicant_name'],
                'father_name'         => $data['father_name'],
                'father_cnic'         => $data['father_cnic'] ?: null,
                'dob'                 => $data['date_of_birth'],
                'gender'              => $data['gender'],
                'b_form_no'           => $data['b_form_no'] ?: null,
                'phone'               => $data['phone'],
                'email'               => $data['email'] ?: null,
                'address'             => $data['address'] ?: null,
                'religion'            => $data['religion'] ?: null,
                'city'                => $data['city'] ?: null,
                'whatsapp'            => $data['whatsapp'] ?: null,
                'previous_school'     => $data['previous_school'] ?: null,
                'matric_board'        => $data['matric_board'] ?: null,
                'matric_year'         => $data['matric_year'] ?: null,
                'matric_marks'        => $data['matric_marks'] !== '' && $data['matric_marks'] !== null ? (int) $data['matric_marks'] : null,
                'matric_total_marks'  => $data['matric_total_marks'] !== '' && $data['matric_total_marks'] !== null ? (int) $data['matric_total_marks'] : null,
                'ip'                  => $data['ip_address'],
            ]);

            $id = (int) $pdo->lastInsertId();
            $applicationNo = sprintf('ADM-%s-%05d', date('Y'), $id);

            $update = $pdo->prepare('UPDATE admission_applications SET application_no = :no WHERE id = :id');
            $update->execute(['no' => $applicationNo, 'id' => $id]);

            return ['id' => $id, 'application_no' => $applicationNo];
        });
    }

    public function updateStatus(int $id, string $status, int $reviewedBy): void
    {
        $stmt = $this->pdo()->prepare(
            "UPDATE admission_applications
                SET status = :status, reviewed_by = :by, reviewed_at = NOW()
              WHERE id = :id"
        );
        $stmt->execute(['status' => $status, 'by' => $reviewedBy, 'id' => $id]);
    }

    public function saveRemarks(int $id, string $remarks): void
    {
        $stmt = $this->pdo()->prepare('UPDATE admission_applications SET remarks = :remarks WHERE id = :id');
        $stmt->execute(['remarks' => $remarks, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM admission_applications WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** Links an admitted application to the `students` row it was converted into. */
    public function markConverted(int $id, int $studentId): void
    {
        $stmt = $this->pdo()->prepare('UPDATE admission_applications SET converted_student_id = :student_id WHERE id = :id');
        $stmt->execute(['student_id' => $studentId, 'id' => $id]);
    }

    // -------------------------------------------------------------------
    // Entrance test & merit pipeline
    // -------------------------------------------------------------------

    /** Also advances status to 'test_scheduled' — scheduling a test is what that status means. */
    public function scheduleTest(int $id, string $testDate, ?string $rollNo): void
    {
        $stmt = $this->pdo()->prepare(
            "UPDATE admission_applications
                SET test_date = :test_date, test_roll_no = :roll_no, status = 'test_scheduled'
              WHERE id = :id"
        );
        $stmt->execute(['test_date' => $testDate, 'roll_no' => $rollNo, 'id' => $id]);
    }

    /** Also advances status to 'test_taken' — recording a score is what that status means. */
    public function recordTestScore(int $id, float $score): void
    {
        $stmt = $this->pdo()->prepare(
            "UPDATE admission_applications
                SET test_score = :score, status = 'test_taken'
              WHERE id = :id"
        );
        $stmt->execute(['score' => $score, 'id' => $id]);
    }

    /** Merit ranking and interview feedback aren't tied to a single status value, so this never touches `status`. */
    public function saveMeritAndInterview(int $id, ?int $meritPosition, ?string $interviewRemarks): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE admission_applications
                SET merit_position = :merit_position, interview_remarks = :interview_remarks
              WHERE id = :id'
        );
        $stmt->execute(['merit_position' => $meritPosition, 'interview_remarks' => $interviewRemarks, 'id' => $id]);
    }
}
