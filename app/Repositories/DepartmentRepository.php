<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class DepartmentRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        return $this->pdo()->query(
            "SELECT d.*, (SELECT COUNT(*) FROM staff s WHERE s.department_id = d.id AND s.deleted_at IS NULL) AS staff_count
               FROM departments d
              ORDER BY d.sort_order, d.name"
        )->fetchAll();
    }

    /** Departments page — active departments with a public faculty count and, if set, their Head of Department. */
    public function publicList(): array
    {
        return $this->pdo()->query(
            "SELECT d.*,
                    (SELECT COUNT(*) FROM staff s WHERE s.department_id = d.id AND s.deleted_at IS NULL
                       AND s.is_public_profile = 1 AND s.status = 'active') AS staff_count,
                    hs.first_name AS head_first_name, hs.last_name AS head_last_name, hs.slug AS head_slug
               FROM departments d
               LEFT JOIN staff hs ON hs.id = d.head_staff_id AND hs.deleted_at IS NULL
              WHERE d.is_active = 1
              ORDER BY d.sort_order, d.name"
        )->fetchAll();
    }

    /** For select dropdowns — active departments only. */
    public function activeList(): array
    {
        return $this->pdo()->query(
            'SELECT id, name FROM departments WHERE is_active = 1 ORDER BY sort_order, name'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM departments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function codeExists(string $code, ?int $exceptId = null): bool
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM departments WHERE code = :code AND id <> :except');
        $stmt->execute(['code' => $code, 'except' => $exceptId ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO departments (name, code, description, sort_order, is_active)
             VALUES (:name, :code, :description, :sort_order, :is_active)'
        );
        $stmt->execute($this->bindings($data));

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $bindings = $this->bindings($data);
        $bindings['id'] = $id;

        $stmt = $this->pdo()->prepare(
            'UPDATE departments SET name = :name, code = :code, description = :description,
                sort_order = :sort_order, is_active = :is_active
             WHERE id = :id'
        );
        $stmt->execute($bindings);
    }

    public function delete(int $id): void
    {
        // fk_staff_department is ON DELETE SET NULL — faculty in this department
        // become uncategorized rather than being deleted with it.
        $stmt = $this->pdo()->prepare('DELETE FROM departments WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function bindings(array $data): array
    {
        return [
            'name'        => $data['name'],
            'code'        => $data['code'],
            'description' => $data['description'] ?: null,
            'sort_order'  => $data['sort_order'],
            'is_active'   => $data['is_active'],
        ];
    }
}
