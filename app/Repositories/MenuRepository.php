<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * The three menu locations (header, footer, quick_links) are structural —
 * seeded once in 0014_seed_core_data.sql and tied to real <nav> render
 * points in the templates — so this repository only ever reads them.
 * Creating/deleting a location is a template change, not an admin action.
 */
final class MenuRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        return $this->pdo()->query('SELECT * FROM menus ORDER BY id')->fetchAll();
    }

    public function findByLocation(string $location): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM menus WHERE location = :loc');
        $stmt->execute(['loc' => $location]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
