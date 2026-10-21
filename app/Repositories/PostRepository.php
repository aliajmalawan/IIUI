<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\HtmlSanitizer;
use PDO;

/**
 * Backs both News and Blog — they're the same content shape (title, rich
 * content, featured image, scheduling, SEO fields), so `post_type`
 * ('news'|'blog', 0042_add_post_type_for_blog.sql) is the only thing that
 * tells them apart rather than duplicating this whole repository. Every
 * method defaults to 'news' so existing News call sites are unaffected;
 * Admin/Web BlogController pass 'blog' explicitly.
 */
final class PostRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** Admin listing — every status, soft-deleted rows excluded. */
    public function all(string $type = 'news'): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT p.*, pc.name AS category_name
               FROM posts p
               LEFT JOIN post_categories pc ON pc.id = p.category_id
              WHERE p.deleted_at IS NULL AND p.post_type = :type
              ORDER BY p.is_pinned DESC, p.created_at DESC"
        );
        $stmt->execute(['type' => $type]);

        return $stmt->fetchAll();
    }

    /** Public listing — published, in the past, newest first. */
    public function published(?int $categoryId = null, int $limit = 12, int $offset = 0, string $type = 'news'): array
    {
        $sql = "SELECT p.*, pc.name AS category_name, pc.slug AS category_slug
                  FROM posts p
                  LEFT JOIN post_categories pc ON pc.id = p.category_id
                 WHERE p.deleted_at IS NULL AND p.post_type = :type AND p.status = 'published' AND p.published_at <= NOW()";
        $params = ['type' => $type];

        if ($categoryId !== null) {
            $sql .= ' AND p.category_id = :cat';
            $params['cat'] = $categoryId;
        }

        $sql .= ' ORDER BY p.is_pinned DESC, p.published_at DESC LIMIT :lim OFFSET :off';

        $stmt = $this->pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue('off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** Unpaginated — for the combined News & Events feed, which paginates in PHP after merging with events. */
    public function publishedAll(?int $categoryId = null, string $type = 'news'): array
    {
        $sql = "SELECT p.*, pc.name AS category_name, pc.slug AS category_slug
                  FROM posts p
                  LEFT JOIN post_categories pc ON pc.id = p.category_id
                 WHERE p.deleted_at IS NULL AND p.post_type = :type AND p.status = 'published' AND p.published_at <= NOW()";
        $params = ['type' => $type];

        if ($categoryId !== null) {
            $sql .= ' AND p.category_id = :cat';
            $params['cat'] = $categoryId;
        }

        $sql .= ' ORDER BY p.published_at DESC';

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function countPublished(?int $categoryId = null, string $type = 'news'): int
    {
        $sql = "SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL AND post_type = :type AND status = 'published' AND published_at <= NOW()";
        $params = ['type' => $type];
        if ($categoryId !== null) {
            $sql .= ' AND category_id = :cat';
            $params['cat'] = $categoryId;
        }
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM posts WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findPublishedBySlug(string $slug, string $type = 'news'): ?array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT p.*, pc.name AS category_name, pc.slug AS category_slug
               FROM posts p
               LEFT JOIN post_categories pc ON pc.id = p.category_id
              WHERE p.slug = :slug AND p.deleted_at IS NULL AND p.post_type = :type
                AND p.status = 'published' AND p.published_at <= NOW()"
        );
        $stmt->execute(['slug' => $slug, 'type' => $type]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM posts WHERE slug = :slug AND id <> :except');
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

    public function incrementViews(int $id): void
    {
        $stmt = $this->pdo()->prepare('UPDATE posts SET views = views + 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO posts
                (post_type, category_id, title, slug, excerpt, content, featured_image,
                 meta_title, meta_description, status, is_featured, is_pinned, published_at, author_id)
             VALUES
                (:post_type, :category_id, :title, :slug, :excerpt, :content, :featured_image,
                 :meta_title, :meta_description, :status, :is_featured, :is_pinned, :published_at, :author_id)'
        );
        $stmt->execute($this->bindings($data) + ['author_id' => $data['author_id']]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $bindings = $this->bindings($data);
        $bindings['id'] = $id;

        $stmt = $this->pdo()->prepare(
            'UPDATE posts SET
                post_type = :post_type, category_id = :category_id, title = :title, slug = :slug, excerpt = :excerpt,
                content = :content, featured_image = :featured_image,
                meta_title = :meta_title, meta_description = :meta_description,
                status = :status, is_featured = :is_featured, is_pinned = :is_pinned,
                published_at = :published_at
             WHERE id = :id'
        );
        $stmt->execute($bindings);
    }

    /** Soft delete — operational content is never hard-removed (docs/01-architecture.md §1). */
    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('UPDATE posts SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function bindings(array $data): array
    {
        return [
            'post_type'        => $data['post_type'] ?? 'news',
            'category_id'      => $data['category_id'] ?: null,
            'title'            => $data['title'],
            'slug'             => $data['slug'],
            'excerpt'          => $data['excerpt'] ?: null,
            // Sanitized here — the ONE place every post's content is written,
            // so no future caller can accidentally persist raw TinyMCE HTML.
            'content'          => HtmlSanitizer::clean($data['content'] ?? ''),
            'featured_image'   => $data['featured_image'] ?: null,
            'meta_title'       => $data['meta_title'] ?: null,
            'meta_description' => $data['meta_description'] ?: null,
            'status'           => $data['status'],
            'is_featured'      => $data['is_featured'],
            'is_pinned'        => $data['is_pinned'],
            'published_at'     => $data['published_at'] ?: null,
        ];
    }
}
