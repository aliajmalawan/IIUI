<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ContactMessageRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        return $this->pdo()->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();
    }

    /**
     * Filtered inbox query. Filters: status (exact), q (name/email/phone/subject/message contains).
     *
     * @param array{status?:string,q?:string} $filters
     * @return array<int,array<string,mixed>>
     */
    public function search(array $filters, ?int $limit = null, int $offset = 0): array
    {
        [$where, $params] = $this->conditions($filters);
        $sql = 'SELECT * FROM contact_messages' . $where . ' ORDER BY created_at DESC, id DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);
        }
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @param array{status?:string,q?:string} $filters */
    public function countMatching(array $filters): int
    {
        [$where, $params] = $this->conditions($filters);
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM contact_messages' . $where);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /** @return array<string,int> status => count (every status present, zero-filled) */
    public function statusCounts(array $statuses): array
    {
        $counts = array_fill_keys($statuses, 0);
        foreach ($this->pdo()->query('SELECT status, COUNT(*) c FROM contact_messages GROUP BY status')->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['c'];
        }

        return $counts;
    }

    /** @return array{0:string,1:array<string,string>} */
    private function conditions(array $filters): array
    {
        $where = [];
        $params = [];
        if (($filters['status'] ?? '') !== '') {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . addcslashes($q, '\\%_') . '%';
            $parts = [];
            foreach (['name', 'email', 'phone', 'subject', 'message'] as $i => $col) {
                $parts[] = "$col LIKE :q$i";
                $params["q$i"] = $like;
            }
            $where[] = '(' . implode(' OR ', $parts) . ')';
        }

        return [$where === [] ? '' : ' WHERE ' . implode(' AND ', $where), $params];
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM contact_messages WHERE status = :status');
        $stmt->execute(['status' => $status]);

        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM contact_messages WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** Public form submission — ip_address stored via INET6_ATON (handles both IPv4 and IPv6). */
    public function create(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO contact_messages (name, email, phone, subject, message, ip_address, user_agent)
             VALUES (:name, :email, :phone, :subject, :message, INET6_ATON(:ip), :ua)'
        );
        $stmt->execute([
            'name'    => $data['name'],
            'email'   => $data['email'],
            'phone'   => $data['phone'] ?: null,
            'subject' => $data['subject'] ?: null,
            'message' => $data['message'],
            'ip'      => $data['ip_address'],
            'ua'      => mb_substr((string) $data['user_agent'], 0, 255),
        ]);

        return (int) $this->pdo()->lastInsertId();
    }

    /** Marks 'new' as 'read' the first time an admin opens it; leaves any other status untouched. */
    public function markAsReadIfNew(int $id): void
    {
        $stmt = $this->pdo()->prepare("UPDATE contact_messages SET status = 'read' WHERE id = :id AND status = 'new'");
        $stmt->execute(['id' => $id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo()->prepare('UPDATE contact_messages SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function saveReply(int $id, string $replyMessage, int $repliedBy): void
    {
        $stmt = $this->pdo()->prepare(
            "UPDATE contact_messages
                SET reply_message = :reply, replied_by = :by, replied_at = NOW(), status = 'replied'
              WHERE id = :id"
        );
        $stmt->execute(['reply' => $replyMessage, 'by' => $repliedBy, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM contact_messages WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
