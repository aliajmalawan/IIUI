<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class GalleryRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** Admin listing — every status, with a live count of its images. */
    public function all(): array
    {
        return $this->pdo()->query(
            "SELECT g.*, (SELECT COUNT(*) FROM gallery_images gi WHERE gi.gallery_id = g.id) AS image_count
               FROM galleries g
              ORDER BY g.sort_order, g.gallery_date DESC, g.id DESC"
        )->fetchAll();
    }

    /** Public listing — published only, newest first, optionally filtered by category. */
    public function published(?string $category = null): array
    {
        $sql = "SELECT g.*, (SELECT COUNT(*) FROM gallery_images gi WHERE gi.gallery_id = g.id) AS image_count
                  FROM galleries g
                 WHERE g.status = 'published'";
        $params = [];

        if ($category !== null && $category !== '') {
            $sql .= ' AND g.category = :category';
            $params['category'] = $category;
        }

        $sql .= ' ORDER BY g.sort_order, g.gallery_date DESC, g.id DESC';

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array<int,string> distinct categories currently in use among published galleries */
    public function publishedCategories(): array
    {
        $stmt = $this->pdo()->query(
            "SELECT DISTINCT category FROM galleries
              WHERE status = 'published' AND category IS NOT NULL AND category <> ''
              ORDER BY category"
        );

        return array_column($stmt->fetchAll(), 'category');
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM galleries WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        $stmt = $this->pdo()->prepare("SELECT * FROM galleries WHERE slug = :slug AND status = 'published'");
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM galleries WHERE slug = :slug AND id <> :except');
        $stmt->execute(['slug' => $slug, 'except' => $exceptId ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** Appends -2, -3, ... until the slug is unique. */
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
            'INSERT INTO galleries (title, slug, description, cover_image, gallery_date, category, status, sort_order, created_by)
             VALUES (:title, :slug, :description, :cover_image, :gallery_date, :category, :status, :sort_order, :created_by)'
        );
        $stmt->execute($this->bindings($data) + ['created_by' => $data['created_by']]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $bindings = $this->bindings($data);
        $bindings['id'] = $id;

        $stmt = $this->pdo()->prepare(
            'UPDATE galleries SET
                title = :title, slug = :slug, description = :description, cover_image = :cover_image,
                gallery_date = :gallery_date, category = :category, status = :status, sort_order = :sort_order
             WHERE id = :id'
        );
        $stmt->execute($bindings);
    }

    public function delete(int $id): void
    {
        // ON DELETE CASCADE on gallery_images.gallery_id takes every image with it.
        $stmt = $this->pdo()->prepare('DELETE FROM galleries WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function setCoverImage(int $id, string $path): void
    {
        $stmt = $this->pdo()->prepare('UPDATE galleries SET cover_image = :path WHERE id = :id');
        $stmt->execute(['path' => $path, 'id' => $id]);
    }

    private function bindings(array $data): array
    {
        return [
            'title'        => $data['title'],
            'slug'         => $data['slug'],
            'description'  => $data['description'] ?: null,
            'cover_image'  => $data['cover_image'] ?: null,
            'gallery_date' => $data['gallery_date'] ?: null,
            'category'     => $data['category'] ?: null,
            'status'       => $data['status'],
            'sort_order'   => $data['sort_order'],
        ];
    }
}
