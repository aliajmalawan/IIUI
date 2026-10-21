<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * All SQL for the users/roles/permissions domain lives here — no other
 * layer queries these tables directly (docs/01-architecture.md §1).
 */
final class UserRepository
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    public function findByIdentifier(string $identifier): ?array
    {
        // Native prepares (EMULATE_PREPARES=false) reject reusing one named
        // placeholder twice in a query, so each occurrence gets its own name.
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM users WHERE (username = :id1 OR email = :id2) AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute(['id1' => $identifier, 'id2' => $identifier]);
        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    public function findActiveById(int $id): ?array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT * FROM users WHERE id = :id AND status = 'active' AND deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    public function updatePasswordHash(int $userId, string $hash): void
    {
        $stmt = $this->pdo()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute(['hash' => $hash, 'id' => $userId]);
    }

    public function updateProfile(int $userId, string $firstName, ?string $lastName, string $email, ?string $phone): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE users SET first_name = :first, last_name = :last, email = :email, phone = :phone WHERE id = :id'
        );
        $stmt->execute([
            'first' => $firstName,
            'last'  => $lastName,
            'email' => $email,
            'phone' => $phone,
            'id'    => $userId,
        ]);
    }

    public function updateAvatar(int $userId, ?string $avatarPath): void
    {
        $stmt = $this->pdo()->prepare('UPDATE users SET avatar = :avatar WHERE id = :id');
        $stmt->execute(['avatar' => $avatarPath, 'id' => $userId]);
    }

    public function emailTakenByAnotherUser(string $email, int $exceptUserId): bool
    {
        $stmt = $this->pdo()->prepare(
            'SELECT COUNT(*) FROM users WHERE email = :email AND id <> :id AND deleted_at IS NULL'
        );
        $stmt->execute(['email' => $email, 'id' => $exceptUserId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function updateTheme(int $userId, string $theme): void
    {
        $stmt = $this->pdo()->prepare('UPDATE users SET theme = :theme WHERE id = :id');
        $stmt->execute(['theme' => $theme, 'id' => $userId]);
    }

    public function recordSuccessfulLogin(int $userId, string $ip): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE users
                SET last_login_at = NOW(), last_login_ip = INET6_ATON(:ip),
                    failed_attempts = 0, locked_until = NULL
              WHERE id = :id'
        );
        $stmt->execute(['ip' => $ip, 'id' => $userId]);
    }

    /**
     * $currentAttempts is the value already in hand from findByIdentifier() —
     * deliberately NOT re-read as `failed_attempts + 1` inside the UPDATE.
     * MySQL evaluates later SET/CASE expressions against columns already
     * updated earlier in the SAME statement, so a self-referential
     * `SET failed_attempts = failed_attempts + 1, locked_until = CASE WHEN
     * failed_attempts + 1 >= :threshold …` silently reads the POST-increment
     * value in the CASE, locking the account one attempt early and then
     * skipping the final increment once locked. Computing the new count in
     * PHP avoids the ambiguity entirely.
     */
    public function incrementFailedAttempts(int $userId, int $currentAttempts, int $lockThreshold, int $lockMinutes): void
    {
        $newAttempts = $currentAttempts + 1;

        if ($newAttempts >= $lockThreshold) {
            $stmt = $this->pdo()->prepare(
                'UPDATE users
                    SET failed_attempts = :attempts,
                        locked_until = DATE_ADD(NOW(), INTERVAL :minutes MINUTE)
                  WHERE id = :id'
            );
            $stmt->execute(['attempts' => $newAttempts, 'minutes' => $lockMinutes, 'id' => $userId]);

            return;
        }

        $stmt = $this->pdo()->prepare('UPDATE users SET failed_attempts = :attempts WHERE id = :id');
        $stmt->execute(['attempts' => $newAttempts, 'id' => $userId]);
    }

    public function isLockedOut(array $user): bool
    {
        return $user['locked_until'] !== null && strtotime((string) $user['locked_until']) > time();
    }

    public function logAttempt(string $identifier, string $ip, string $userAgent, bool $success, ?string $reason = null): void
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO login_attempts (identifier, ip_address, user_agent, was_successful, failure_reason)
             VALUES (:identifier, INET6_ATON(:ip), :ua, :success, :reason)'
        );
        $stmt->execute([
            'identifier' => $identifier,
            'ip'         => $ip,
            'ua'         => mb_substr($userAgent, 0, 255),
            'success'    => $success ? 1 : 0,
            'reason'     => $reason,
        ]);
    }

    public function recentFailedAttemptsFromIp(string $ip, int $minutes): int
    {
        $stmt = $this->pdo()->prepare(
            'SELECT COUNT(*) FROM login_attempts
              WHERE ip_address = INET6_ATON(:ip) AND was_successful = 0
                AND attempted_at >= DATE_SUB(NOW(), INTERVAL :minutes MINUTE)'
        );
        $stmt->execute(['ip' => $ip, 'minutes' => $minutes]);

        return (int) $stmt->fetchColumn();
    }

    // -------------------------------------------------------------------
    // Password reset
    // -------------------------------------------------------------------

    public function createPasswordReset(string $email, string $tokenHash, int $expiresInMinutes, string $ip): void
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO password_resets (email, token_hash, ip_address, expires_at)
             VALUES (:email, :hash, INET6_ATON(:ip), DATE_ADD(NOW(), INTERVAL :mins MINUTE))'
        );
        $stmt->execute(['email' => $email, 'hash' => $tokenHash, 'ip' => $ip, 'mins' => $expiresInMinutes]);
    }

    public function findValidPasswordReset(string $tokenHash): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM password_resets
              WHERE token_hash = :hash AND used_at IS NULL AND expires_at > NOW()
              ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['hash' => $tokenHash]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function markPasswordResetUsed(int $id): void
    {
        $stmt = $this->pdo()->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    // -------------------------------------------------------------------
    // RBAC — docs/03-modules-roles.md §4: union of role permissions, plus
    // per-user grant, minus per-user deny. Deny always wins.
    // -------------------------------------------------------------------

    public function userHasRole(int $userId, string $roleSlug): bool
    {
        $stmt = $this->pdo()->prepare(
            'SELECT COUNT(*) FROM user_roles ur
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = :uid AND r.slug = :slug'
        );
        $stmt->execute(['uid' => $userId, 'slug' => $roleSlug]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** Highest role level the user holds (0 if none) — students/parents are 20, staff start at 30. */
    public function maxRoleLevel(int $userId): int
    {
        $stmt = $this->pdo()->prepare(
            'SELECT COALESCE(MAX(r.level), 0) FROM user_roles ur INNER JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = :uid'
        );
        $stmt->execute(['uid' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Every permission slug the user effectively holds, resolved once with the same
     * rules as userHasPermission(): role grants + per-user grants, minus per-user denies.
     *
     * @return string[]
     */
    public function permissionSlugs(int $userId): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT p.slug
               FROM permissions p
               LEFT JOIN user_permissions up ON up.permission_id = p.id AND up.user_id = :uid1
               LEFT JOIN role_permissions rp ON rp.permission_id = p.id
                    AND rp.role_id IN (SELECT role_id FROM user_roles WHERE user_id = :uid2)
              GROUP BY p.id, p.slug
             HAVING MAX(CASE WHEN up.type = 'deny' THEN 1 ELSE 0 END) = 0
                AND (MAX(CASE WHEN up.type = 'grant' THEN 1 ELSE 0 END) = 1
                  OR MAX(CASE WHEN rp.permission_id IS NOT NULL THEN 1 ELSE 0 END) = 1)"
        );
        $stmt->execute(['uid1' => $userId, 'uid2' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function userHasPermission(int $userId, string $permissionSlug): bool
    {
        // :uid1/:uid2 — see the note in findByIdentifier() above.
        $stmt = $this->pdo()->prepare(
            "SELECT
                 MAX(CASE WHEN up.type = 'deny'  THEN 1 ELSE 0 END) AS denied,
                 MAX(CASE WHEN up.type = 'grant' THEN 1 ELSE 0 END) AS granted,
                 MAX(CASE WHEN rp.permission_id IS NOT NULL THEN 1 ELSE 0 END) AS via_role
             FROM permissions p
             LEFT JOIN user_permissions up ON up.permission_id = p.id AND up.user_id = :uid1
             LEFT JOIN role_permissions rp ON rp.permission_id = p.id
                  AND rp.role_id IN (SELECT role_id FROM user_roles WHERE user_id = :uid2)
             WHERE p.slug = :slug"
        );
        $stmt->execute(['uid1' => $userId, 'uid2' => $userId, 'slug' => $permissionSlug]);
        $row = $stmt->fetch();

        if ($row === false) {
            return false;
        }
        if ((int) $row['denied'] === 1) {
            return false;
        }

        return (int) $row['granted'] === 1 || (int) $row['via_role'] === 1;
    }

    // -------------------------------------------------------------------
    // Users & Roles admin module — account management and role assignment.
    // Role/permission *definitions* (role_permissions, user_permissions)
    // stay read-only here: editing a 182-permission matrix per role is a
    // separate, much larger future module. This module answers "who can
    // log in and what role(s) do they hold," not "what can each role do."
    // -------------------------------------------------------------------

    /** @return array<int,array<string,mixed>> every account, with a comma-joined list of role names, newest first. */
    public function allWithRoles(): array
    {
        return $this->pdo()->query(
            "SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.avatar, u.status, u.last_login_at, u.created_at,
                    GROUP_CONCAT(r.name ORDER BY r.level DESC SEPARATOR ', ') AS role_names
               FROM users u
          LEFT JOIN user_roles ur ON ur.user_id = u.id
          LEFT JOIN roles r ON r.id = ur.role_id
              WHERE u.deleted_at IS NULL
           GROUP BY u.id
           ORDER BY u.created_at DESC"
        )->fetchAll();
    }

    /** Unlike findActiveById(), this returns a user regardless of status — the admin editing them needs to see inactive/suspended accounts too. */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM users WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    public function usernameExists(string $username, ?int $exceptId = null): bool
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM users WHERE username = :username AND id <> :except');
        $stmt->execute(['username' => $username, 'except' => $exceptId ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO users
                (username, email, password_hash, first_name, last_name, status, must_change_password, email_verified_at, created_by)
             VALUES
                (:username, :email, :password_hash, :first_name, :last_name, :status, 1, NOW(), :author_id)'
        );
        $stmt->execute([
            'username'      => $data['username'],
            'email'         => $data['email'],
            'password_hash' => $data['password_hash'],
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'] ?: null,
            'status'        => $data['status'],
            'author_id'     => $data['author_id'],
        ]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE users SET
                username = :username, email = :email, first_name = :first_name, last_name = :last_name,
                status = :status, updated_by = :author_id
             WHERE id = :id'
        );
        $stmt->execute([
            'username'   => $data['username'],
            'email'      => $data['email'],
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'] ?: null,
            'status'     => $data['status'],
            'author_id'  => $data['author_id'],
            'id'         => $id,
        ]);
    }

    /** Soft delete — same convention as staff/students (docs/01-architecture.md §1). */
    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('UPDATE users SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function countActiveUsersWithRole(string $roleSlug, ?int $exceptUserId = null): int
    {
        $stmt = $this->pdo()->prepare(
            "SELECT COUNT(*) FROM user_roles ur
               JOIN roles r ON r.id = ur.role_id
               JOIN users u ON u.id = ur.user_id
              WHERE r.slug = :slug AND u.deleted_at IS NULL AND u.status = 'active' AND ur.user_id <> :except"
        );
        $stmt->execute(['slug' => $roleSlug, 'except' => $exceptUserId ?? 0]);

        return (int) $stmt->fetchColumn();
    }

    /** @return array<int,int> role ids currently assigned to this user. */
    public function roleIdsForUser(int $userId): array
    {
        $stmt = $this->pdo()->prepare('SELECT role_id FROM user_roles WHERE user_id = :id');
        $stmt->execute(['id' => $userId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /** @param array<int,int> $roleIds replaces the user's full role set in one transaction. */
    public function syncRoles(int $userId, array $roleIds, int $assignedBy): void
    {
        Database::transaction(function (PDO $pdo) use ($userId, $roleIds, $assignedBy): void {
            $pdo->prepare('DELETE FROM user_roles WHERE user_id = :id')->execute(['id' => $userId]);

            if ($roleIds === []) {
                return;
            }

            $stmt = $pdo->prepare(
                'INSERT INTO user_roles (user_id, role_id, assigned_by) VALUES (:user_id, :role_id, :assigned_by)'
            );
            foreach (array_unique($roleIds) as $roleId) {
                $stmt->execute(['user_id' => $userId, 'role_id' => $roleId, 'assigned_by' => $assignedBy]);
            }
        });
    }

    // -------------------------------------------------------------------
    // Roles — read-only listing/detail. See the notice above this section.
    // -------------------------------------------------------------------

    /** @return array<int,array<string,mixed>> every role with its permission count, highest level first. */
    public function allRoles(): array
    {
        return $this->pdo()->query(
            'SELECT r.*, COUNT(rp.permission_id) AS permission_count,
                    (SELECT COUNT(*) FROM user_roles ur WHERE ur.role_id = r.id) AS user_count
               FROM roles r
          LEFT JOIN role_permissions rp ON rp.role_id = r.id
           GROUP BY r.id
           ORDER BY r.level DESC'
        )->fetchAll();
    }

    public function findRole(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM roles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $role = $stmt->fetch();

        return $role === false ? null : $role;
    }

    /** @return array<string,array<int,array<string,mixed>>> this role's permissions, grouped by group_name. */
    public function permissionsForRole(int $roleId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT p.slug, p.name, p.module, p.group_name
               FROM role_permissions rp
               JOIN permissions p ON p.id = rp.permission_id
              WHERE rp.role_id = :role_id
           ORDER BY p.group_name, p.module, p.name'
        );
        $stmt->execute(['role_id' => $roleId]);

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $grouped[$row['group_name'] ?? 'Other'][] = $row;
        }

        return $grouped;
    }
}
