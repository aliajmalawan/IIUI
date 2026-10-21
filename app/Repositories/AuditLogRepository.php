<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class AuditLogRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> */
    public function recent(int $limit = 8): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT a.*, CONCAT_WS(' ', u.first_name, u.last_name) AS actor_name
               FROM audit_logs a
               LEFT JOIN users u ON u.id = a.user_id
              ORDER BY a.created_at DESC
              LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array{rows: array<int,array<string,mixed>>, total: int} */
    public function paginate(int $page, int $perPage): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo()->prepare(
            "SELECT a.*, CONCAT_WS(' ', u.first_name, u.last_name) AS actor_name
               FROM audit_logs a
               LEFT JOIN users u ON u.id = a.user_id
              ORDER BY a.created_at DESC
              LIMIT :lim OFFSET :off"
        );
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $total = (int) $this->pdo()->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();

        return ['rows' => $rows, 'total' => $total];
    }
}
