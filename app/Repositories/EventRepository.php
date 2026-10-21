<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\HtmlSanitizer;
use PDO;

final class EventRepository
{
    /** Public-visible statuses — everything except 'draft'. */
    private const PUBLIC_STATUSES = "'published', 'cancelled', 'completed'";

    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** Admin listing — every status, soft-deleted rows excluded. */
    public function all(): array
    {
        return $this->pdo()->query(
            'SELECT * FROM events WHERE deleted_at IS NULL ORDER BY start_datetime DESC'
        )->fetchAll();
    }

    /** Public — events whose window (or start, if no end) has not yet passed. */
    public function upcoming(?string $category, int $limit, int $offset): array
    {
        return $this->publicQuery(
            "AND COALESCE(end_datetime, start_datetime) >= NOW()",
            'ORDER BY start_datetime ASC',
            $category,
            $limit,
            $offset
        );
    }

    /** Public — events whose window (or start, if no end) has already passed. */
    public function past(?string $category, int $limit, int $offset): array
    {
        return $this->publicQuery(
            "AND COALESCE(end_datetime, start_datetime) < NOW()",
            'ORDER BY start_datetime DESC',
            $category,
            $limit,
            $offset
        );
    }

    public function countUpcoming(?string $category): int
    {
        return $this->publicCount("AND COALESCE(end_datetime, start_datetime) >= NOW()", $category);
    }

    public function countPast(?string $category): int
    {
        return $this->publicCount("AND COALESCE(end_datetime, start_datetime) < NOW()", $category);
    }

    /** Unpaginated, any time window — for the combined News & Events feed, which paginates in PHP after merging with posts. */
    public function publishedAll(): array
    {
        return $this->pdo()->query(
            'SELECT * FROM events
              WHERE deleted_at IS NULL AND status IN (' . self::PUBLIC_STATUSES . ')
              ORDER BY start_datetime DESC'
        )->fetchAll();
    }

    /** @return array<int,string> distinct categories currently in use among public events */
    public function publicCategories(): array
    {
        $stmt = $this->pdo()->query(
            'SELECT DISTINCT category FROM events
              WHERE deleted_at IS NULL AND status IN (' . self::PUBLIC_STATUSES . ")
                AND category IS NOT NULL AND category <> ''
              ORDER BY category"
        );

        return array_column($stmt->fetchAll(), 'category');
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM events WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findPublicBySlug(string $slug): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM events
              WHERE slug = :slug AND deleted_at IS NULL AND status IN (' . self::PUBLIC_STATUSES . ')'
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM events WHERE slug = :slug AND id <> :except');
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
            'INSERT INTO events
                (title, slug, description, content, banner, start_datetime, end_datetime, venue, organizer,
                 category, registration_required, registration_url, capacity, is_featured, status,
                 meta_title, meta_description, author_id)
             VALUES
                (:title, :slug, :description, :content, :banner, :start_datetime, :end_datetime, :venue, :organizer,
                 :category, :registration_required, :registration_url, :capacity, :is_featured, :status,
                 :meta_title, :meta_description, :author_id)'
        );
        $stmt->execute($this->bindings($data) + ['author_id' => $data['author_id']]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $bindings = $this->bindings($data);
        $bindings['id'] = $id;

        $stmt = $this->pdo()->prepare(
            'UPDATE events SET
                title = :title, slug = :slug, description = :description, content = :content, banner = :banner,
                start_datetime = :start_datetime, end_datetime = :end_datetime, venue = :venue, organizer = :organizer,
                category = :category, registration_required = :registration_required, registration_url = :registration_url,
                capacity = :capacity, is_featured = :is_featured, status = :status,
                meta_title = :meta_title, meta_description = :meta_description
             WHERE id = :id'
        );
        $stmt->execute($bindings);
    }

    /** Soft delete — operational content is never hard-removed (docs/01-architecture.md §1). */
    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('UPDATE events SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function publicQuery(string $windowClause, string $orderBy, ?string $category, int $limit, int $offset): array
    {
        $sql = "SELECT * FROM events
                 WHERE deleted_at IS NULL AND status IN (" . self::PUBLIC_STATUSES . ") {$windowClause}";
        $params = [];

        if ($category !== null && $category !== '') {
            $sql .= ' AND category = :category';
            $params['category'] = $category;
        }

        $sql .= " {$orderBy} LIMIT :lim OFFSET :off";

        $stmt = $this->pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue('off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function publicCount(string $windowClause, ?string $category): int
    {
        $sql = "SELECT COUNT(*) FROM events
                 WHERE deleted_at IS NULL AND status IN (" . self::PUBLIC_STATUSES . ") {$windowClause}";
        $params = [];

        if ($category !== null && $category !== '') {
            $sql .= ' AND category = :category';
            $params['category'] = $category;
        }

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function bindings(array $data): array
    {
        return [
            'title'                 => $data['title'],
            'slug'                  => $data['slug'],
            'description'           => $data['description'] ?: null,
            // Sanitized here — the ONE place every event's content is written,
            // so no future caller can accidentally persist raw TinyMCE HTML.
            'content'               => HtmlSanitizer::clean($data['content'] ?? ''),
            'banner'                => $data['banner'] ?: null,
            'start_datetime'        => $data['start_datetime'],
            'end_datetime'          => $data['end_datetime'] ?: null,
            'venue'                 => $data['venue'] ?: null,
            'organizer'             => $data['organizer'] ?: null,
            'category'              => $data['category'] ?: null,
            'registration_required' => $data['registration_required'],
            'registration_url'      => $data['registration_url'] ?: null,
            'capacity'              => $data['capacity'] ?: null,
            'is_featured'           => $data['is_featured'],
            'status'                => $data['status'],
            'meta_title'            => $data['meta_title'] ?: null,
            'meta_description'      => $data['meta_description'] ?: null,
        ];
    }
}
