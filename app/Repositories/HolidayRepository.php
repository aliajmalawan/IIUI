<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class HolidayRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> all holidays, newest session/date first, joined with session name */
    public function all(): array
    {
        return $this->pdo()->query(
            'SELECT h.*, s.name AS session_name
               FROM holidays h
               JOIN academic_sessions s ON s.id = h.academic_session_id
              ORDER BY h.date_from DESC'
        )->fetchAll();
    }

    /** @return array<int,array<string,mixed>> one session's holidays in calendar order */
    public function forSession(int $sessionId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM holidays WHERE academic_session_id = :sid ORDER BY date_from'
        );
        $stmt->execute(['sid' => $sessionId]);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM holidays WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(array $data, ?int $createdBy): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO holidays (academic_session_id, title, date_from, date_to, type, applies_to, description, created_by)
             VALUES (:session_id, :title, :date_from, :date_to, :type, :applies_to, :description, :created_by)'
        );
        $stmt->execute($this->bindings($data) + ['created_by' => $createdBy]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $bindings = $this->bindings($data);
        $bindings['id'] = $id;

        $stmt = $this->pdo()->prepare(
            'UPDATE holidays
                SET academic_session_id = :session_id, title = :title, date_from = :date_from, date_to = :date_to,
                    type = :type, applies_to = :applies_to, description = :description
              WHERE id = :id'
        );
        $stmt->execute($bindings);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM holidays WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function bindings(array $data): array
    {
        return [
            'session_id'  => $data['academic_session_id'],
            'title'       => $data['title'],
            'date_from'   => $data['date_from'],
            'date_to'     => $data['date_to'],
            'type'        => $data['type'],
            'applies_to'  => $data['applies_to'],
            'description' => $data['description'] ?: null,
        ];
    }
}
