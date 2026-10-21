<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * The `pages` table (0008_cms.sql) is a full CMS pages module — content,
 * SEO fields, draft/published workflow, parent/child hierarchy. This
 * repository covers: the four `is_system` rows tied to real routes
 * (About/Academics/Admissions/Contact — Controllers/Web/PageController) plus
 * any custom pages created from the admin "Pages" list, each with a hero
 * (`title` as heading, `excerpt` as subheading, `featured_image` as banner)
 * and a stack of content sections — see PageSectionRepository. `content`/
 * `content_image` are no longer written to (superseded by sections, see
 * 0036) but left populated from before that migration rather than dropped.
 * The rest of the schema (SEO fields, status workflow) is there for later.
 */
final class PageRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        return $this->pdo()->query(
            'SELECT * FROM pages WHERE deleted_at IS NULL ORDER BY sort_order, id'
        )->fetchAll();
    }

    /** Non-system, published pages — for the "Your pages" list in the Menus builder's Link-to picker. */
    public function publishedCustom(): array
    {
        return $this->pdo()->query(
            "SELECT id, title, slug FROM pages
              WHERE deleted_at IS NULL AND is_system = 0 AND status = 'published'
              ORDER BY sort_order, title"
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM pages WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM pages WHERE slug = :slug AND deleted_at IS NULL');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function updateHeroImage(int $id, ?string $featuredImage): void
    {
        $stmt = $this->pdo()->prepare('UPDATE pages SET featured_image = :image WHERE id = :id');
        $stmt->execute(['image' => $featuredImage, 'id' => $id]);
    }

    public function updateHero(int $id, string $title, string $excerpt): void
    {
        $stmt = $this->pdo()->prepare('UPDATE pages SET title = :title, excerpt = :excerpt WHERE id = :id');
        $stmt->execute(['title' => $title, 'excerpt' => $excerpt !== '' ? $excerpt : null, 'id' => $id]);
    }

    /** New custom page (not one of the four system pages) — starts unpublished-looking (no banner) with zero sections. */
    public function create(string $title, string $slug): int
    {
        $nextOrder = (int) $this->pdo()->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM pages')->fetchColumn();

        $stmt = $this->pdo()->prepare(
            'INSERT INTO pages (title, slug, sort_order, is_system, show_in_menu, status, published_at)
             VALUES (:title, :slug, :sort, 0, 0, \'published\', NOW())'
        );
        $stmt->execute(['title' => $title, 'slug' => $slug, 'sort' => $nextOrder]);

        return (int) $this->pdo()->lastInsertId();
    }

    /** Custom pages only — callers must check is_system first (system pages are protected in the admin controller). */
    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM pages WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
