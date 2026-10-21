<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class HomepageGalleryRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        return $this->pdo()->query('SELECT * FROM homepage_gallery_images ORDER BY sort_order, id')->fetchAll();
    }

    /** Public homepage masonry grid — active only. */
    public function activeList(): array
    {
        return $this->pdo()->query(
            'SELECT * FROM homepage_gallery_images WHERE is_active = 1 ORDER BY sort_order, id'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM homepage_gallery_images WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(string $imagePath, ?string $caption, int $sortOrder, bool $isActive): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO homepage_gallery_images (image_path, caption, sort_order, is_active)
             VALUES (:image, :caption, :sort_order, :is_active)'
        );
        $stmt->execute([
            'image'      => $imagePath,
            'caption'    => $caption,
            'sort_order' => $sortOrder,
            'is_active'  => $isActive ? 1 : 0,
        ]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, ?string $imagePath, ?string $caption, int $sortOrder, bool $isActive): void
    {
        if ($imagePath !== null) {
            $stmt = $this->pdo()->prepare(
                'UPDATE homepage_gallery_images SET image_path = :image, caption = :caption, sort_order = :sort_order, is_active = :is_active WHERE id = :id'
            );
            $stmt->execute([
                'image' => $imagePath, 'caption' => $caption, 'sort_order' => $sortOrder,
                'is_active' => $isActive ? 1 : 0, 'id' => $id,
            ]);

            return;
        }

        $stmt = $this->pdo()->prepare(
            'UPDATE homepage_gallery_images SET caption = :caption, sort_order = :sort_order, is_active = :is_active WHERE id = :id'
        );
        $stmt->execute(['caption' => $caption, 'sort_order' => $sortOrder, 'is_active' => $isActive ? 1 : 0, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM homepage_gallery_images WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
