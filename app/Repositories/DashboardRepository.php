<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Dashboard aggregate queries. Every number here is a real query against the
 * live schema — on a fresh install several will legitimately read 0 until
 * students/staff are enrolled, which is the honest state of an empty system,
 * not a bug.
 */
final class DashboardRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    public function totalStudents(): int
    {
        return (int) $this->pdo()
            ->query('SELECT COUNT(*) FROM students WHERE deleted_at IS NULL')
            ->fetchColumn();
    }

    public function totalStaff(): int
    {
        return (int) $this->pdo()
            ->query('SELECT COUNT(*) FROM staff WHERE deleted_at IS NULL')
            ->fetchColumn();
    }

    public function totalActiveClasses(): int
    {
        return (int) $this->pdo()
            ->query('SELECT COUNT(*) FROM school_classes WHERE is_active = 1')
            ->fetchColumn();
    }

    public function currentSession(): ?array
    {
        $row = $this->pdo()
            ->query('SELECT * FROM academic_sessions WHERE is_current = 1 LIMIT 1')
            ->fetch();

        return $row === false ? null : $row;
    }

    /** @return array<int,array{wing:string,total:int}> class count per wing, for the primary chart. */
    public function classesByWing(): array
    {
        $stmt = $this->pdo()->query(
            "SELECT wing, COUNT(*) AS total
               FROM school_classes
              WHERE is_active = 1
              GROUP BY wing
              ORDER BY FIELD(wing, 'primary','middle','secondary','college')"
        );

        return $stmt->fetchAll();
    }

    /** @return array<int,array{type:string,total:int}> subject count per type, for the secondary chart. */
    public function subjectsByType(): array
    {
        $stmt = $this->pdo()->query(
            "SELECT type, COUNT(*) AS total
               FROM subjects
              WHERE is_active = 1
              GROUP BY type"
        );

        return $stmt->fetchAll();
    }

    public function totalUsers(): int
    {
        return (int) $this->pdo()
            ->query('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL')
            ->fetchColumn();
    }

    /** Applications still somewhere in the pipeline — excludes the three terminal statuses. */
    public function pendingAdmissions(): int
    {
        return (int) $this->pdo()
            ->query("SELECT COUNT(*) FROM admission_applications WHERE status NOT IN ('admitted', 'rejected', 'withdrawn')")
            ->fetchColumn();
    }
}
