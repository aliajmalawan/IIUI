<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use Throwable;

/**
 * Single PDO connection, lazily created. Mandatory options per
 * docs/01-architecture.md §5: ERRMODE_EXCEPTION, EMULATE_PREPARES=false
 * (real server-side prepares — the whole point of using PDO with bound
 * parameters), FETCH_ASSOC by default, utf8mb4 throughout.
 */
final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = self::connect();
        }

        return self::$connection;
    }

    private static function connect(): PDO
    {
        $config = (array) config('database');

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            return new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
                PDO::ATTR_PERSISTENT         => false,
            ]);
        } catch (PDOException $e) {
            Logger::critical('Database connection failed: ' . $e->getMessage());

            throw new PDOException('Unable to connect to the database.', (int) $e->getCode());
        }
    }

    /**
     * Runs $callback inside a transaction; commits on success, rolls back and
     * re-throws on any exception. Used by every multi-table write (admission,
     * promotion, challan generation, payment posting).
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();

            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }
    }
}
