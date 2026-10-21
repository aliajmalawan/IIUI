<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * `fee_structures` (0007_finance.sql) — the per-class amount for each fee
 * category, for one academic session. This repository covers the display/
 * management slice only (list, add, edit, delete one amount); challans,
 * payments, fines, and discounts are the rest of the not-yet-built Finance
 * module (sidebar's Fee Challans/Payments are still "Soon").
 */
final class FeeStructureRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> every row for one session, joined with class/wing/category names, in display order */
    public function forSession(int $sessionId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT fs.*, c.name AS class_name, c.wing, c.sort_order AS class_sort, fc.name AS category_name, fc.sort_order AS category_sort
               FROM fee_structures fs
               JOIN school_classes c ON c.id = fs.class_id
               JOIN fee_categories fc ON fc.id = fs.fee_category_id
              WHERE fs.academic_session_id = :sid AND fs.is_active = 1
              ORDER BY c.sort_order, fc.sort_order'
        );
        $stmt->execute(['sid' => $sessionId]);

        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> admin list — every row regardless of session, newest first */
    public function all(): array
    {
        return $this->pdo()->query(
            'SELECT fs.*, c.name AS class_name, s.name AS session_name, fc.name AS category_name
               FROM fee_structures fs
               JOIN school_classes c ON c.id = fs.class_id
               JOIN academic_sessions s ON s.id = fs.academic_session_id
               JOIN fee_categories fc ON fc.id = fs.fee_category_id
              ORDER BY s.start_date DESC, c.sort_order, fc.sort_order'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM fee_structures WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function exists(int $sessionId, int $classId, int $categoryId, ?int $exceptId = null): bool
    {
        $stmt = $this->pdo()->prepare(
            'SELECT COUNT(*) FROM fee_structures
              WHERE academic_session_id = :sid AND class_id = :cid AND fee_category_id = :fcid
                AND stream_id IS NULL AND id != :except'
        );
        $stmt->execute(['sid' => $sessionId, 'cid' => $classId, 'fcid' => $categoryId, 'except' => $exceptId ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO fee_structures (academic_session_id, class_id, fee_category_id, amount, frequency, created_by)
             VALUES (:session_id, :class_id, :category_id, :amount, :frequency, :created_by)'
        );
        $stmt->execute($this->bindings($data) + ['created_by' => $data['created_by'] ?? null]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $bindings = $this->bindings($data);
        $bindings['id'] = $id;

        $stmt = $this->pdo()->prepare(
            'UPDATE fee_structures
                SET academic_session_id = :session_id, class_id = :class_id, fee_category_id = :category_id,
                    amount = :amount, frequency = :frequency
              WHERE id = :id'
        );
        $stmt->execute($bindings);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM fee_structures WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function bindings(array $data): array
    {
        return [
            'session_id'  => $data['academic_session_id'],
            'class_id'    => $data['class_id'],
            'category_id' => $data['fee_category_id'],
            'amount'      => $data['amount'],
            'frequency'   => $data['frequency'],
        ];
    }
}
