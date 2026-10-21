<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class PostCategoryRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        return $this->pdo()->query(
            "SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.category_id = c.id AND p.deleted_at IS NULL) AS post_count
               FROM post_categories c
              ORDER BY c.sort_order, c.name"
        )->fetchAll();
    }

    /** For select dropdowns and public filter pills — active categories only. */
    public function activeList(): array
    {
        return $this->pdo()->query(
            'SELECT id, name, slug FROM post_categories WHERE is_active = 1 ORDER BY sort_order, name'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM post_categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM post_categories WHERE slug = :slug AND is_active = 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM post_categories WHERE slug = :slug AND id <> :except');
        $stmt->execute(['slug' => $slug, 'except' => $exceptId ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function uniqueSlug(string $base, ?int $exceptId = null): string
    {
        $slug = $base;
        $suffix = 2;
        while ($this->slugExists($slug, $exceptId)) {
            $slug = $base . '-' . $suffix;
            ++$suffix;
        }

        return $slug;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO post_categories (name, slug, description, sort_order, is_active)
             VALUES (:name, :slug, :description, :sort_order, :is_active)'
        );
        $stmt->execute($this->bindings($data));

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $bindings = $this->bindings($data);
        $bindings['id'] = $id;

        $stmt = $this->pdo()->prepare(
            'UPDATE post_categories SET name = :name, slug = :slug, description = :description,
                sort_order = :sort_order, is_active = :is_active
             WHERE id = :id'
        );
        $stmt->execute($bindings);
    }

    public function delete(int $id): void
    {
        // fk_posts_category is ON DELETE SET NULL — posts in this category
        // become uncategorized rather than being deleted with it.
        $stmt = $this->pdo()->prepare('DELETE FROM post_categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function bindings(array $data): array
    {
        return [
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'description' => $data['description'] ?: null,
            'sort_order'  => $data['sort_order'],
            'is_active'   => $data['is_active'],
        ];
    }
}
