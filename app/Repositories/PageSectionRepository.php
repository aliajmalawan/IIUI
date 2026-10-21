<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class PageSectionRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> ordered for both the admin editor and the public renderer */
    public function forPage(int $pageId, bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM page_sections WHERE page_id = :page_id';
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' ORDER BY sort_order, id';

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute(['page_id' => $pageId]);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM page_sections WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(int $pageId, string $sectionType): int
    {
        $nextOrder = (int) $this->pdo()
            ->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM page_sections WHERE page_id = ' . $pageId)
            ->fetchColumn();

        $stmt = $this->pdo()->prepare(
            'INSERT INTO page_sections (page_id, section_type, sort_order, content) VALUES (:page_id, :type, :sort, :content)'
        );
        $stmt->execute([
            'page_id' => $pageId,
            'type'    => $sectionType,
            'sort'    => $nextOrder,
            'content' => json_encode(new \stdClass()),
        ]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function updateContent(int $id, array $content, int $sortOrder): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE page_sections SET content = :content, sort_order = :sort WHERE id = :id'
        );
        $stmt->execute(['content' => json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'sort' => $sortOrder, 'id' => $id]);
    }

    public function toggle(int $id): void
    {
        $stmt = $this->pdo()->prepare('UPDATE page_sections SET is_active = 1 - is_active WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM page_sections WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** @return array<string,mixed> decoded `content` JSON, or [] if null/invalid */
    public static function decodeContent(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }
}
