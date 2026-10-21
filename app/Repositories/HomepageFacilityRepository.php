<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class HomepageFacilityRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        return $this->pdo()->query('SELECT * FROM homepage_facilities ORDER BY sort_order, id')->fetchAll();
    }

    /** Public homepage grid — active only. */
    public function activeList(): array
    {
        return $this->pdo()->query(
            'SELECT * FROM homepage_facilities WHERE is_active = 1 ORDER BY sort_order, id'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM homepage_facilities WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO homepage_facilities (title, description, image, icon, sort_order, is_active)
             VALUES (:title, :description, :image, :icon, :sort_order, :is_active)'
        );
        $stmt->execute($this->bindings($data));

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $bindings = $this->bindings($data);
        $bindings['id'] = $id;

        $stmt = $this->pdo()->prepare(
            'UPDATE homepage_facilities SET title = :title, description = :description, image = :image,
                icon = :icon, sort_order = :sort_order, is_active = :is_active
             WHERE id = :id'
        );
        $stmt->execute($bindings);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM homepage_facilities WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function bindings(array $data): array
    {
        return [
            'title'       => $data['title'],
            'description' => $data['description'] ?: null,
            'image'       => $data['image'] ?: null,
            'icon'        => $data['icon'] ?: null,
            'sort_order'  => $data['sort_order'],
            'is_active'   => $data['is_active'],
        ];
    }
}
