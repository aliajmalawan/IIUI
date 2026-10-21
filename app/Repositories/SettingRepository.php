<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Site-wide settings (docs/02-database.md domain H — `settings` table).
 * The admin UI only ever edits `value` for rows that already exist; adding a
 * brand-new setting KEY is a seeder/migration change, not a runtime action —
 * this keeps "what settings exist" a deliberate, versioned decision while
 * "what they're currently set to" stays fully admin-editable data.
 */
final class SettingRepository
{
    /**
     * Every setting, loaded once per request. The layout, header, footer and
     * views each ask for a dozen keys (often the same key several times);
     * one query for the whole small table replaces ~30 single-row lookups.
     *
     * @var array<string,array{value:mixed,is_public:int}>|null
     */
    private static ?array $cache = null;

    private function all(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach ($this->pdo()->query('SELECT key_name, value, is_public FROM settings')->fetchAll() as $row) {
                self::$cache[$row['key_name']] = ['value' => $row['value'], 'is_public' => (int) $row['is_public']];
            }
        }

        return self::$cache;
    }

    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> */
    public function getByGroup(string $group): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM settings WHERE group_name = :g ORDER BY sort_order, id'
        );
        $stmt->execute(['g' => $group]);

        return $stmt->fetchAll();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key]['value'] : $default;
    }

    /** @return array<string,mixed> key_name => value, for every setting flagged public (read by the frontend). */
    public function allPublic(): array
    {
        $out = [];
        foreach ($this->all() as $key => $row) {
            if ($row['is_public'] === 1) {
                $out[$key] = $row['value'];
            }
        }

        return $out;
    }

    /** @param array<string,string> $values key_name => new value */
    public function updateMany(array $values, int $userId): void
    {
        Database::transaction(function (PDO $pdo) use ($values, $userId) {
            $stmt = $pdo->prepare('UPDATE settings SET value = :v, updated_by = :u WHERE key_name = :k');
            foreach ($values as $key => $value) {
                $stmt->execute(['v' => $value, 'u' => $userId, 'k' => $key]);
            }
        });
        self::$cache = null;
    }
}
