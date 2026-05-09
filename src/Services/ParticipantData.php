<?php
declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;
use App\Models\Participant;

/**
 * Operacje zapisu / odczytu danych ankiety dla uczestnika.
 * Wydzielone z modelu zeby kazdy plik byl mniejszy.
 */
final class ParticipantData
{
    public static function getResponses(Participant $p): array
    {
        $stmt = Connection::get()->prepare(
            'SELECT question_key, value_text, value_json FROM participant_responses WHERE participant_id = :pid'
        );
        $stmt->execute(['pid' => $p->id]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[$row['question_key']] = $row['value_json'] !== null
                ? json_decode((string) $row['value_json'], true)
                : $row['value_text'];
        }
        return $out;
    }

    public static function saveResponse(Participant $p, string $key, mixed $value): void
    {
        if (is_array($value)) {
            $vt = null;
            $vj = json_encode($value, JSON_UNESCAPED_UNICODE);
        } elseif (is_bool($value)) {
            $vt = $value ? 'true' : 'false';
            $vj = null;
        } else {
            $vt = (string) $value;
            $vj = null;
        }
        $stmt = Connection::get()->prepare(
            'INSERT INTO participant_responses (participant_id, question_key, value_text, value_json)
             VALUES (:pid, :qk, :vt, :vj)
             ON DUPLICATE KEY UPDATE value_text = VALUES(value_text), value_json = VALUES(value_json)'
        );
        $stmt->execute(['pid' => $p->id, 'qk' => $key, 'vt' => $vt, 'vj' => $vj]);
        self::touchActivity($p);
    }

    public static function getUnavailableDates(Participant $p): array
    {
        $s = Connection::get()->prepare(
            'SELECT unavailable_date FROM participant_unavailable_dates WHERE participant_id = :p'
        );
        $s->execute(['p' => $p->id]);
        return array_map(static fn($r) => (string) $r['unavailable_date'], $s->fetchAll());
    }

    public static function setUnavailableDates(Participant $p, array $dates): void
    {
        $pdo = Connection::get();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM participant_unavailable_dates WHERE participant_id = :p')
                ->execute(['p' => $p->id]);
            if (!empty($dates)) {
                $ins = $pdo->prepare(
                    'INSERT INTO participant_unavailable_dates (participant_id, unavailable_date) VALUES (:p, :d)'
                );
                foreach ($dates as $date) {
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) continue;
                    $ins->execute(['p' => $p->id, 'd' => $date]);
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        self::touchActivity($p);
    }

    public static function getPreferredWeeks(Participant $p): array
    {
        $s = Connection::get()->prepare(
            'SELECT week_start_date, preference FROM participant_preferred_weeks WHERE participant_id = :p'
        );
        $s->execute(['p' => $p->id]);
        $out = [];
        foreach ($s->fetchAll() as $r) {
            $out[(string) $r['week_start_date']] = (string) $r['preference'];
        }
        return $out;
    }

    public static function setPreferredWeeks(Participant $p, array $weeks): void
    {
        $pdo = Connection::get();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM participant_preferred_weeks WHERE participant_id = :p')
                ->execute(['p' => $p->id]);
            if (!empty($weeks)) {
                $ins = $pdo->prepare(
                    'INSERT INTO participant_preferred_weeks (participant_id, week_start_date, preference) VALUES (:p, :w, :pref)'
                );
                foreach ($weeks as $weekStart => $pref) {
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $weekStart)) continue;
                    if (!in_array($pref, ['yes', 'maybe', 'no'], true)) continue;
                    $ins->execute(['p' => $p->id, 'w' => $weekStart, 'pref' => $pref]);
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        self::touchActivity($p);
    }

    public static function markCompleted(Participant $p): void
    {
        Connection::get()
            ->prepare('UPDATE participants SET completed_at = NOW(), last_activity_at = NOW() WHERE id = :id')
            ->execute(['id' => $p->id]);
    }

    public static function touchActivity(Participant $p): void
    {
        Connection::get()
            ->prepare('UPDATE participants SET last_activity_at = NOW() WHERE id = :id')
            ->execute(['id' => $p->id]);
    }
}
