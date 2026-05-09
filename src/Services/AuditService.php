<?php
declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;

/**
 * Logowanie zmian odpowiedzi do trip_responses_audit.
 * Wywoływane gdy admin edytuje odpowiedź uczestnika przez wizard.
 */
final class AuditService
{
    public static function log(
        int $tripId,
        int $participantId,
        int $adminId,
        string $field,
        mixed $oldValue,
        mixed $newValue,
    ): void {
        $pdo = Connection::get();
        $stmt = $pdo->prepare(
            'INSERT INTO trip_responses_audit
             (trip_id, participant_id, changed_by_admin_id, field_changed, old_value, new_value)
             VALUES (:tid, :pid, :aid, :f, :ov, :nv)'
        );
        $stmt->execute([
            'tid' => $tripId,
            'pid' => $participantId,
            'aid' => $adminId,
            'f'   => $field,
            'ov'  => self::stringify($oldValue),
            'nv'  => self::stringify($newValue),
        ]);
    }

    private static function stringify(mixed $value): ?string
    {
        if ($value === null) return null;
        if (is_array($value)) return json_encode($value, JSON_UNESCAPED_UNICODE);
        if (is_bool($value))  return $value ? 'true' : 'false';
        return (string) $value;
    }
}
