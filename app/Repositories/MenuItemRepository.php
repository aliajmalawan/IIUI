<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class MenuItemRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> flat, ordered by sort_order within each parent */
    public function forMenu(int $menuId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM menu_items WHERE menu_id = :id ORDER BY sort_order, id');
        $stmt->execute(['id' => $menuId]);

        return $stmt->fetchAll();
    }

    /** Admin listing: every item (active or not), nested one level deep. */
    public function tree(int $menuId): array
    {
        return $this->buildTree($this->forMenu($menuId));
    }

    /** Public rendering: only active items, in an active menu, nested one level deep. */
    public function activeTreeForLocation(string $location): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT mi.* FROM menu_items mi
             INNER JOIN menus m ON m.id = mi.menu_id
             WHERE m.location = :loc AND m.is_active = 1 AND mi.is_active = 1
             ORDER BY mi.sort_order, mi.id"
        );
        $stmt->execute(['loc' => $location]);

        return $this->buildTree($stmt->fetchAll());
    }

    /** @return array<int,array<string,mixed>> top-level items only, for the "parent" picker in the form. */
    public function topLevelForMenu(int $menuId, ?int $excludeId = null): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT id, title FROM menu_items WHERE menu_id = :id AND parent_id IS NULL AND id <> :exclude ORDER BY sort_order'
        );
        $stmt->execute(['id' => $menuId, 'exclude' => $excludeId ?? 0]);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM menu_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO menu_items (menu_id, parent_id, title, type, url, icon, target, description, sort_order, is_active)
             VALUES (:menu_id, :parent_id, :title, :type, :url, :icon, :target, :description, :sort_order, :is_active)'
        );
        $stmt->execute($this->bindings($data));

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        // bindings() includes 'menu_id' (needed by create()'s INSERT), but
        // this UPDATE deliberately never reassigns an item to a different
        // menu — an item's menu is fixed at creation. With native prepares
        // (EMULATE_PREPARES=false), passing a bound value with no matching
        // named placeholder throws "Invalid parameter number", so it must
        // be dropped here rather than reusing the array as-is.
        $bindings = $this->bindings($data);
        unset($bindings['menu_id']);
        $bindings['id'] = $id;

        $stmt = $this->pdo()->prepare(
            'UPDATE menu_items SET
                parent_id = :parent_id, title = :title, type = :type, url = :url,
                icon = :icon, target = :target, description = :description,
                sort_order = :sort_order, is_active = :is_active
             WHERE id = :id'
        );
        $stmt->execute($bindings);
    }

    /**
     * Bulk save from the drag-and-drop Structure list: $rows is the full
     * item order for one menu, each `['id' => int, 'depth' => 0|1]`. Depth 1
     * becomes a child of the nearest preceding depth-0 row in the same list
     * (matching the admin UI's indent/outdent, which only ever produces 0 or
     * 1). Only rows that actually belong to $menuId are touched, so a
     * tampered id from a different menu can't be reassigned.
     */
    public function reorder(int $menuId, array $rows): void
    {
        $validIds = array_column($this->forMenu($menuId), 'id');

        $stmt = $this->pdo()->prepare(
            'UPDATE menu_items SET sort_order = :sort, parent_id = :parent_id WHERE id = :id AND menu_id = :menu_id'
        );

        $sort = 0;
        $currentParent = null;
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            if (!in_array($id, $validIds, true)) {
                continue;
            }

            $depth = ((int) $row['depth']) > 0 ? 1 : 0;
            if ($depth === 0) {
                $currentParent = $id;
                $parentId = null;
            } else {
                // An indented row with nothing above it to attach to just stays top-level.
                $parentId = $currentParent;
            }

            $stmt->execute(['sort' => $sort, 'parent_id' => $parentId, 'id' => $id, 'menu_id' => $menuId]);
            $sort++;
        }
    }

    public function delete(int $id): void
    {
        // ON DELETE CASCADE on menu_items.parent_id takes any children with it.
        $stmt = $this->pdo()->prepare('DELETE FROM menu_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function bindings(array $data): array
    {
        return [
            'menu_id'     => $data['menu_id'],
            'parent_id'   => $data['parent_id'] ?: null,
            'title'       => $data['title'],
            'type'        => 'custom',
            'url'         => $data['url'] ?: null,
            'icon'        => $data['icon'] ?: null,
            'target'      => $data['target'],
            'description' => $data['description'] ?: null,
            'sort_order'  => $data['sort_order'],
            'is_active'   => $data['is_active'],
        ];
    }

    /** @param array<int,array<string,mixed>> $flat */
    private function buildTree(array $flat): array
    {
        $byParent = [];
        foreach ($flat as $item) {
            $key = $item['parent_id'] ?? 0;
            $byParent[$key][] = $item;
        }

        $attach = function (array $items) use (&$attach, $byParent): array {
            foreach ($items as &$item) {
                $item['children'] = isset($byParent[$item['id']]) ? $attach($byParent[$item['id']]) : [];
            }

            return $items;
        };

        return $attach($byParent[0] ?? []);
    }
}
