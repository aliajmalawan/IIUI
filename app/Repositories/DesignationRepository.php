<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class DesignationRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        return $this->pdo()->query(
            "SELECT d.*, (SELECT COUNT(*) FROM staff s WHERE s.designation_id = d.id AND s.deleted_at IS NULL) AS staff_count
               FROM designations d
              ORDER BY d.rank, d.name"
        )->fetchAll();
    }

    /** For select dropdowns — active designations only. */
    public function activeList(): array
    {
        return $this->pdo()->query(
            'SELECT id, name FROM designations WHERE is_active = 1 ORDER BY rank, name'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM designations WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM designations WHERE name = :name AND id <> :except');
        $stmt->execute(['name' => $name, 'except' => $exceptId ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO designations (name, category, rank, is_active)
             VALUES (:name, :category, :rank, :is_active)'
        );
        $stmt->execute($this->bindings($data));

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $bindings = $this->bindings($data);
        $bindings['id'] = $id;

        $stmt = $this->pdo()->prepare(
            'UPDATE designations SET name = :name, category = :category, rank = :rank, is_active = :is_active
             WHERE id = :id'
        );
        $stmt->execute($bindings);
    }

    public function delete(int $id): void
    {
        // fk_staff_designation is ON DELETE SET NULL — faculty with this
        // designation become unassigned rather than being deleted with it.
        $stmt = $this->pdo()->prepare('DELETE FROM designations WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function bindings(array $data): array
    {
        return [
            'name'      => $data['name'],
            'category'  => $data['category'],
            'rank'      => $data['rank'],
            'is_active' => $data['is_active'],
        ];
    }
}
