<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Append-only audit trail writer — docs/05-security.md §9. Every sensitive
 * action (login, profile change, password reset, and every module built from
 * here on) calls Audit::log() instead of writing to audit_logs directly.
 */
final class Audit
{
    public static function log(
        string $action,
        string $module,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $stmt = Database::connection()->prepare(
            'INSERT INTO audit_logs
                (user_id, action, module, entity_type, entity_id, description, old_values, new_values, ip_address, user_agent)
             VALUES (:uid, :action, :module, :etype, :eid, :descr, :old, :new, INET6_ATON(:ip), :ua)'
        );

        $stmt->execute([
            'uid'    => Auth::id(),
            'action' => $action,
            'module' => $module,
            'etype'  => $entityType,
            'eid'    => $entityId,
            'descr'  => $description,
            'old'    => $oldValues !== null ? json_encode($oldValues, JSON_UNESCAPED_SLASHES) : null,
            'new'    => $newValues !== null ? json_encode($newValues, JSON_UNESCAPED_SLASHES) : null,
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'ua'     => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    }
}
