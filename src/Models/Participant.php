<?php
declare(strict_types=1);

namespace App\Models;

use App\Database\Connection;
use App\Services\TokenService;

final class Participant
{
    public function __construct(
        public readonly int $id,
        public readonly int $tripId,
        public readonly string $nickname,
        public readonly ?string $avatarPath,
        public readonly string $accessToken,
        public readonly ?string $completedAt,
        public readonly ?string $lastActivityAt,
        public readonly string $createdAt,
        public readonly ?string $color = null,
    ) {}

    public static function findById(int $id): ?self
    {
        $pdo = Connection::get();
        $stmt = $pdo->prepare('SELECT * FROM participants WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    public static function findByIdForAdmin(int $id, int $adminId): ?array
    {
        $p = self::findById($id);
        if ($p === null) return null;
        $t = Trip::findByIdForAdmin($p->tripId, $adminId);
        if ($t === null) return null;
        return [$p, $t];
    }

    public static function findByAccessToken(string $token): ?self
    {
        if (!TokenService::isValid($token)) return null;
        $stmt = Connection::get()->prepare('SELECT * FROM participants WHERE access_token = :t LIMIT 1');
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    public static function listForTrip(int $tripId): array
    {
        $stmt = Connection::get()->prepare('SELECT * FROM participants WHERE trip_id = :tid ORDER BY created_at ASC');
        $stmt->execute(['tid' => $tripId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) $out[] = self::fromRow($row);
        return $out;
    }

    public static function create(array $data): self
    {
        $pdo  = Connection::get();
        $stmt = $pdo->prepare(
            'INSERT INTO participants (trip_id, nickname, avatar_path, access_token)
             VALUES (:trip_id, :nickname, :avatar_path, :access_token)'
        );
        $stmt->execute([
            'trip_id'      => $data['trip_id'],
            'nickname'     => $data['nickname'],
            'avatar_path'  => $data['avatar_path'],
            'access_token' => TokenService::generate(),
        ]);
        return self::findById((int) $pdo->lastInsertId());
    }

    public function update(array $data): self
    {
        $allowed = ['nickname', 'avatar_path', 'color'];
        $sets = [];
        $params = ['id' => $this->id];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $sets[] = "`{$f}` = :{$f}";
                $params[$f] = $data[$f];
            }
        }
        if (empty($sets)) return $this;
        Connection::get()->prepare('UPDATE participants SET ' . implode(', ', $sets) . ' WHERE id = :id')->execute($params);
        return self::findById($this->id);
    }

    public function delete(): void
    {
        Connection::get()->prepare('DELETE FROM participants WHERE id = :id')->execute(['id' => $this->id]);
    }

    public function isCompleted(): bool { return $this->completedAt !== null; }

    public function unavailableDatesCount(): int
    {
        $s = Connection::get()->prepare('SELECT COUNT(*) AS c FROM participant_unavailable_dates WHERE participant_id = :p');
        $s->execute(['p' => $this->id]);
        return (int) ($s->fetch()['c'] ?? 0);
    }

    public function mapPinsCount(): int
    {
        $s = Connection::get()->prepare('SELECT COUNT(*) AS c FROM participant_map_pins WHERE participant_id = :p');
        $s->execute(['p' => $this->id]);
        return (int) ($s->fetch()['c'] ?? 0);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id:             (int) $row['id'],
            tripId:         (int) $row['trip_id'],
            nickname:       (string) $row['nickname'],
            avatarPath:     $row['avatar_path']      !== null ? (string) $row['avatar_path']      : null,
            accessToken:    (string) $row['access_token'],
            completedAt:    $row['completed_at']     !== null ? (string) $row['completed_at']     : null,
            lastActivityAt: $row['last_activity_at'] !== null ? (string) $row['last_activity_at'] : null,
            createdAt:      (string) $row['created_at'],
            color:          isset($row['color']) && $row['color'] !== null ? (string) $row['color'] : null,
        );
    }
}
