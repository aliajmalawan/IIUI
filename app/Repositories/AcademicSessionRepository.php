<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Read-only for now — `academic_sessions` (0002_academic_structure.sql) is
 * the anchor every exam term/holiday hangs off of, but creating new sessions
 * (closing one, rolling enrollment forward) is a much bigger SIS workflow
 * that isn't built yet (sidebar's Examinations/Attendance are still "Soon").
 * This just lets the Academic Calendar admin pick which existing session an
 * entry belongs to, and lets the public page find "the current one".
 */
final class AcademicSessionRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> newest first */
    public function all(): array
    {
        return $this->pdo()->query('SELECT * FROM academic_sessions ORDER BY start_date DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM academic_sessions WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function current(): ?array
    {
        $row = $this->pdo()->query('SELECT * FROM academic_sessions WHERE is_current = 1 LIMIT 1')->fetch();

        return $row === false ? null : $row;
    }
}
