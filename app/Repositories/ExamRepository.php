<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * `exams` (0002_academic_structure.sql) — the subject-wise exam schedule
 * (date/time/room/invigilator/marks configuration) within an exam term.
 * This is the scheduling slice only: exam_attendance (per-student roll call)
 * and marks/result_summaries/grade_scales (the actual scoring and
 * results-publishing pipeline) are separate, larger future modules — no
 * data exists for them to touch yet. `class_subjects` (per-class curriculum
 * assignment) is similarly out of scope, so the Subject dropdown here draws
 * from the global `subjects` catalog rather than being filtered by class.
 */
final class ExamRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> optionally filtered by term/class, exam-date order. */
    public function all(?int $termId = null, ?int $classId = null): array
    {
        $sql = 'SELECT e.*, t.name AS term_name, c.name AS class_name, st.name AS stream_name,
                       sub.name AS subject_name, sub.code AS subject_code,
                       CONCAT(inv.first_name, \' \', COALESCE(inv.last_name, \'\')) AS invigilator_name
                  FROM exams e
                  JOIN exam_terms t ON t.id = e.exam_term_id
                  JOIN school_classes c ON c.id = e.class_id
             LEFT JOIN streams st ON st.id = e.stream_id
                  JOIN subjects sub ON sub.id = e.subject_id
             LEFT JOIN staff inv ON inv.id = e.invigilator_id
                 WHERE 1 = 1';
        $params = [];

        if ($termId !== null) {
            $sql .= ' AND e.exam_term_id = :term_id';
            $params['term_id'] = $termId;
        }
        if ($classId !== null) {
            $sql .= ' AND e.class_id = :class_id';
            $params['class_id'] = $classId;
        }

        $sql .= ' ORDER BY e.exam_date, e.start_time';

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT e.*, t.name AS term_name, c.name AS class_name, st.name AS stream_name,
                    sub.name AS subject_name, sub.code AS subject_code
               FROM exams e
               JOIN exam_terms t ON t.id = e.exam_term_id
               JOIN school_classes c ON c.id = e.class_id
          LEFT JOIN streams st ON st.id = e.stream_id
               JOIN subjects sub ON sub.id = e.subject_id
              WHERE e.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return array<int,array<string,mixed>> exam terms, newest first, for the term dropdown. */
    public function activeTerms(): array
    {
        return $this->pdo()->query(
            'SELECT id, name, status FROM exam_terms ORDER BY start_date DESC'
        )->fetchAll();
    }

    public function activeClasses(): array
    {
        return $this->pdo()->query(
            'SELECT id, name, wing FROM school_classes WHERE is_active = 1 ORDER BY sort_order'
        )->fetchAll();
    }

    public function activeStreams(): array
    {
        return $this->pdo()->query(
            'SELECT id, name FROM streams WHERE is_active = 1 ORDER BY sort_order'
        )->fetchAll();
    }

    public function activeSubjects(): array
    {
        return $this->pdo()->query(
            'SELECT id, name, code FROM subjects WHERE is_active = 1 ORDER BY sort_order'
        )->fetchAll();
    }

    /** @return array<int,array<string,mixed>> active staff, for the invigilator dropdown. */
    public function invigilators(): array
    {
        return $this->pdo()->query(
            "SELECT id, first_name, last_name, employee_no FROM staff
              WHERE deleted_at IS NULL AND status = 'active' ORDER BY first_name"
        )->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO exams
                (exam_term_id, class_id, stream_id, subject_id, exam_date, start_time, end_time,
                 total_marks, passing_marks, practical_total, practical_passing, room_no, invigilator_id, created_by)
             VALUES
                (:exam_term_id, :class_id, :stream_id, :subject_id, :exam_date, :start_time, :end_time,
                 :total_marks, :passing_marks, :practical_total, :practical_passing, :room_no, :invigilator_id, :author_id)'
        );
        $stmt->execute($this->bindings($data) + ['author_id' => $data['author_id']]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $bindings = $this->bindings($data);
        $bindings['id'] = $id;

        $stmt = $this->pdo()->prepare(
            'UPDATE exams SET
                exam_term_id = :exam_term_id, class_id = :class_id, stream_id = :stream_id,
                subject_id = :subject_id, exam_date = :exam_date, start_time = :start_time, end_time = :end_time,
                total_marks = :total_marks, passing_marks = :passing_marks, practical_total = :practical_total,
                practical_passing = :practical_passing, room_no = :room_no, invigilator_id = :invigilator_id
             WHERE id = :id'
        );
        $stmt->execute($bindings);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM exams WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function bindings(array $data): array
    {
        return [
            'exam_term_id'       => $data['exam_term_id'],
            'class_id'           => $data['class_id'],
            'stream_id'          => $data['stream_id'],
            'subject_id'         => $data['subject_id'],
            'exam_date'          => $data['exam_date'],
            'start_time'         => $data['start_time'],
            'end_time'           => $data['end_time'],
            'total_marks'        => $data['total_marks'],
            'passing_marks'      => $data['passing_marks'],
            'practical_total'    => $data['practical_total'],
            'practical_passing'  => $data['practical_passing'],
            'room_no'            => $data['room_no'] ?: null,
            'invigilator_id'     => $data['invigilator_id'],
        ];
    }
}
