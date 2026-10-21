<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class SliderRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        return $this->pdo()->query('SELECT * FROM sliders ORDER BY sort_order, id')->fetchAll();
    }

    /**
     * What the public homepage actually renders: active, and inside its
     * optional publish window. A slide with starts_at/ends_at set is a
     * scheduled promotion (e.g. an admissions banner) that should disappear
     * on its own without an admin having to remember to deactivate it.
     *
     * @return array<int,array<string,mixed>>
     */
    public function activeForDisplay(): array
    {
        return $this->pdo()->query(
            "SELECT * FROM sliders
              WHERE is_active = 1
                AND (starts_at IS NULL OR starts_at <= NOW())
                AND (ends_at IS NULL OR ends_at >= NOW())
              ORDER BY sort_order, id"
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM sliders WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO sliders
                (title, subtitle, description, image, mobile_image, button_text, button_url,
                 text_position, overlay_opacity, sort_order, is_active, starts_at, ends_at)
             VALUES
                (:title, :subtitle, :description, :image, :mobile_image, :button_text, :button_url,
                 :text_position, :overlay_opacity, :sort_order, :is_active, :starts_at, :ends_at)'
        );
        $stmt->execute($this->bindings($data));

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $bindings = $this->bindings($data);
        $bindings['id'] = $id;

        $stmt = $this->pdo()->prepare(
            'UPDATE sliders SET
                title = :title, subtitle = :subtitle, description = :description,
                image = :image, mobile_image = :mobile_image,
                button_text = :button_text, button_url = :button_url,
                text_position = :text_position, overlay_opacity = :overlay_opacity,
                sort_order = :sort_order, is_active = :is_active,
                starts_at = :starts_at, ends_at = :ends_at
             WHERE id = :id'
        );
        $stmt->execute($bindings);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM sliders WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function bindings(array $data): array
    {
        return [
            'title'           => $data['title'],
            'subtitle'        => $data['subtitle'] ?: null,
            'description'     => $data['description'] ?: null,
            'image'           => $data['image'],
            'mobile_image'    => $data['mobile_image'] ?: null,
            'button_text'     => $data['button_text'] ?: null,
            'button_url'      => $data['button_url'] ?: null,
            'text_position'   => $data['text_position'],
            'overlay_opacity' => $data['overlay_opacity'],
            'sort_order'      => $data['sort_order'],
            'is_active'       => $data['is_active'],
            'starts_at'       => $data['starts_at'] ?: null,
            'ends_at'         => $data['ends_at'] ?: null,
        ];
    }
}
