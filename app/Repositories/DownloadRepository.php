<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class DownloadRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** Admin listing — every audience and status. */
    public function all(): array
    {
        return $this->pdo()->query('SELECT * FROM downloads ORDER BY created_at DESC')->fetchAll();
    }

    /** Public listing — active, public-audience files only, optionally filtered by category. */
    public function publicList(?string $category = null): array
    {
        $sql = "SELECT * FROM downloads WHERE is_active = 1 AND audience = 'public'";
        $params = [];

        if ($category !== null && $category !== '') {
            $sql .= ' AND category = :category';
            $params['category'] = $category;
        }

        $sql .= ' ORDER BY category, title';

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array<int,string> distinct categories currently in use among public downloads */
    public function publicCategories(): array
    {
        $stmt = $this->pdo()->query(
            "SELECT DISTINCT category FROM downloads
              WHERE is_active = 1 AND audience = 'public' AND category IS NOT NULL AND category <> ''
              ORDER BY category"
        );

        return array_column($stmt->fetchAll(), 'category');
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM downloads WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** For the public streaming route — only ever serves active, public-audience files. */
    public function findPublic(int $id): ?array
    {
        $stmt = $this->pdo()->prepare("SELECT * FROM downloads WHERE id = :id AND is_active = 1 AND audience = 'public'");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function incrementDownloadCount(int $id): void
    {
        $stmt = $this->pdo()->prepare('UPDATE downloads SET download_count = download_count + 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO downloads
                (title, description, category, file_path, file_name, file_size, mime_type, audience, is_active, uploaded_by)
             VALUES
                (:title, :description, :category, :file_path, :file_name, :file_size, :mime_type, :audience, :is_active, :uploaded_by)'
        );
        $stmt->execute($this->bindings($data) + ['uploaded_by' => $data['uploaded_by']]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $bindings = $this->bindings($data);
        $bindings['id'] = $id;

        $stmt = $this->pdo()->prepare(
            'UPDATE downloads SET
                title = :title, description = :description, category = :category,
                file_path = :file_path, file_name = :file_name, file_size = :file_size, mime_type = :mime_type,
                audience = :audience, is_active = :is_active
             WHERE id = :id'
        );
        $stmt->execute($bindings);
    }

    /** Hard delete — no deleted_at column on this table; the caller also unlinks the stored file. */
    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM downloads WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function bindings(array $data): array
    {
        return [
            'title'       => $data['title'],
            'description' => $data['description'] ?: null,
            'category'    => $data['category'] ?: null,
            'file_path'   => $data['file_path'],
            'file_name'   => $data['file_name'],
            'file_size'   => $data['file_size'],
            'mime_type'   => $data['mime_type'],
            'audience'    => $data['audience'],
            'is_active'   => $data['is_active'],
        ];
    }
}
