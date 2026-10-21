<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * `exam_terms` (0002_academic_structure.sql) — scheduling (name/type/dates/
 * status/weightage) plus the results-publishing flag (is_result_published/
 * published_at/published_by). "Publishing" here is a workflow signal the
 * public Academic Calendar page can show ("Results Published"), not a
 * computed report card — there's no marks-entry module yet for that; see
 * ExamRepository's notice for what that would take.
 */
final class ExamTermRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> all terms, newest session/date first, joined with session name */
    public function all(): array
    {
        return $this->pdo()->query(
            'SELECT t.*, s.name AS session_name
               FROM exam_terms t
               JOIN academic_sessions s ON s.id = t.academic_session_id
              ORDER BY t.start_date DESC, t.sort_order'
        )->fetchAll();
    }

    /** @return array<int,array<string,mixed>> one session's terms in calendar order */
    public function forSession(int $sessionId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM exam_terms WHERE academic_session_id = :sid ORDER BY start_date, sort_order'
        );
        $stmt->execute(['sid' => $sessionId]);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM exam_terms WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO exam_terms (academic_session_id, name, type, start_date, end_date, status, weightage, sort_order)
             VALUES (:session_id, :name, :type, :start_date, :end_date, :status, :weightage, :sort_order)'
        );
        $stmt->execute($this->bindings($data));

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $bindings = $this->bindings($data);
        $bindings['id'] = $id;

        $stmt = $this->pdo()->prepare(
            'UPDATE exam_terms
                SET academic_session_id = :session_id, name = :name, type = :type,
                    start_date = :start_date, end_date = :end_date, status = :status,
                    weightage = :weightage, sort_order = :sort_order
              WHERE id = :id'
        );
        $stmt->execute($bindings);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM exam_terms WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** Only meaningful once the term's own status is 'completed' — enforced by the controller, not here. */
    public function publishResults(int $id, int $publishedBy): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE exam_terms SET is_result_published = 1, published_at = NOW(), published_by = :by WHERE id = :id'
        );
        $stmt->execute(['by' => $publishedBy, 'id' => $id]);
    }

    public function unpublishResults(int $id): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE exam_terms SET is_result_published = 0, published_at = NULL, published_by = NULL WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    private function bindings(array $data): array
    {
        return [
            'session_id' => $data['academic_session_id'],
            'name'       => $data['name'],
            'type'       => $data['type'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'status'     => $data['status'],
            'weightage'  => $data['weightage'],
            'sort_order' => $data['sort_order'],
        ];
    }
}
