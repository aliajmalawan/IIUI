<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class NotificationRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    public function unreadCount(int $userId): int
    {
        $stmt = $this->pdo()->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND read_at IS NULL'
        );
        $stmt->execute(['uid' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    public function recent(int $userId, int $limit = 8): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT :lim'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function markRead(int $notificationId, int $userId): bool
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE notifications SET read_at = NOW() WHERE id = :id AND user_id = :uid AND read_at IS NULL'
        );
        $stmt->execute(['id' => $notificationId, 'uid' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public function markAllRead(int $userId): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE notifications SET read_at = NOW() WHERE user_id = :uid AND read_at IS NULL'
        );
        $stmt->execute(['uid' => $userId]);
    }
}
