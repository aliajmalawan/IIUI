<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class GalleryImageRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> */
    public function forGallery(int $galleryId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM gallery_images WHERE gallery_id = :id ORDER BY sort_order, id');
        $stmt->execute(['id' => $galleryId]);

        return $stmt->fetchAll();
    }

    /**
     * All photos across every published gallery, flattened into one list
     * (each row carries its parent gallery's title/category) so the public
     * /gallery index can show every image directly instead of album cards,
     * optionally narrowed to a single category.
     * @return array<int,array<string,mixed>>
     */
    public function publishedFlat(?string $category = null): array
    {
        $sql = "SELECT gi.*, g.title AS gallery_title, g.category AS gallery_category
                  FROM gallery_images gi
                  JOIN galleries g ON g.id = gi.gallery_id
                 WHERE g.status = 'published'";
        $params = [];

        if ($category !== null && $category !== '') {
            $sql .= ' AND g.category = :category';
            $params['category'] = $category;
        }

        $sql .= ' ORDER BY g.sort_order, g.id, gi.sort_order, gi.id';

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM gallery_images WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function nextSortOrder(int $galleryId): int
    {
        $stmt = $this->pdo()->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM gallery_images WHERE gallery_id = :id');
        $stmt->execute(['id' => $galleryId]);

        return (int) $stmt->fetchColumn();
    }

    public function create(int $galleryId, string $imagePath, ?string $caption, ?string $altText, int $sortOrder, ?string $thumbnailPath = null): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO gallery_images (gallery_id, image_path, thumbnail_path, caption, alt_text, sort_order)
             VALUES (:gallery_id, :image_path, :thumbnail_path, :caption, :alt_text, :sort_order)'
        );
        $stmt->execute([
            'gallery_id' => $galleryId, 'image_path' => $imagePath, 'thumbnail_path' => $thumbnailPath,
            'caption' => $caption, 'alt_text' => $altText, 'sort_order' => $sortOrder,
        ]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function createVideo(int $galleryId, string $videoId, ?string $caption, int $sortOrder): int
    {
        $stmt = $this->pdo()->prepare(
            "INSERT INTO gallery_images (gallery_id, media_type, image_path, video_id, caption, sort_order)
             VALUES (:gallery_id, 'video', '', :video_id, :caption, :sort_order)"
        );
        $stmt->execute(['gallery_id' => $galleryId, 'video_id' => $videoId, 'caption' => $caption, 'sort_order' => $sortOrder]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function updateDetails(int $id, ?string $caption, ?string $altText, int $sortOrder): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE gallery_images SET caption = :caption, alt_text = :alt_text, sort_order = :sort_order WHERE id = :id'
        );
        $stmt->execute(['caption' => $caption, 'alt_text' => $altText, 'sort_order' => $sortOrder, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM gallery_images WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
