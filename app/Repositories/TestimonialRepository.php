<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class TestimonialRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        return $this->pdo()->query('SELECT * FROM testimonials ORDER BY sort_order, id')->fetchAll();
    }

    /** Public homepage carousel — published only, featured first. */
    public function published(): array
    {
        return $this->pdo()->query(
            "SELECT * FROM testimonials WHERE status = 'published' ORDER BY is_featured DESC, sort_order, id"
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM testimonials WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO testimonials (name, designation, photo, content, rating, type, is_featured, status, sort_order)
             VALUES (:name, :designation, :photo, :content, :rating, :type, :is_featured, :status, :sort_order)'
        );
        $stmt->execute($this->bindings($data));

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $bindings = $this->bindings($data);
        $bindings['id'] = $id;

        $stmt = $this->pdo()->prepare(
            'UPDATE testimonials SET
                name = :name, designation = :designation, photo = :photo, content = :content,
                rating = :rating, type = :type, is_featured = :is_featured, status = :status, sort_order = :sort_order
             WHERE id = :id'
        );
        $stmt->execute($bindings);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM testimonials WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function bindings(array $data): array
    {
        return [
            'name'        => $data['name'],
            'designation' => $data['designation'] ?: null,
            'photo'       => $data['photo'] ?: null,
            'content'     => $data['content'],
            'rating'      => $data['rating'] !== '' ? $data['rating'] : null,
            'type'        => $data['type'],
            'is_featured' => $data['is_featured'],
            'status'      => $data['status'],
            'sort_order'  => $data['sort_order'],
        ];
    }
}
