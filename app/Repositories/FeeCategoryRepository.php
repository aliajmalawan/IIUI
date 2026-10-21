<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/** Read-only — `fee_categories` (0007_finance.sql) is a small, fixed reference list managed once via migration, not day-to-day admin UI. */
final class FeeCategoryRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> */
    public function activeList(): array
    {
        return $this->pdo()->query(
            "SELECT * FROM fee_categories WHERE is_active = 1 ORDER BY sort_order"
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM fee_categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
