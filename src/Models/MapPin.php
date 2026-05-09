<?php
declare(strict_types=1);

namespace App\Models;

use App\Database\Connection;

/**
 * Model pinezki/trasy/obszaru na mapie.
 * Geometria zapisana jako GeoJSON Feature.
 */
final class MapPin
{
    public function __construct(
        public readonly int $id,
        public readonly int $participantId,
        public readonly string $pinType,
        public readonly ?string $label,
        public readonly ?string $description,
        public readonly string $geojson,
        public readonly ?string $color,
        public readonly string $createdAt,
    ) {}

    public static function findById(int $id): ?self
    {
        $stmt = Connection::get()->prepare('SELECT * FROM participant_map_pins WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    /**
     * @return list<MapPin>
     */
    public static function listForParticipant(int $participantId): array
    {
        $stmt = Connection::get()->prepare(
            'SELECT * FROM participant_map_pins WHERE participant_id = :p ORDER BY created_at ASC'
        );
        $stmt->execute(['p' => $participantId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[] = self::fromRow($row);
        }
        return $out;
    }

    /**
     * @return list<MapPin>
     */
    public static function listForTrip(int $tripId): array
    {
        $stmt = Connection::get()->prepare(
            'SELECT mp.* FROM participant_map_pins mp
             INNER JOIN participants p ON p.id = mp.participant_id
             WHERE p.trip_id = :tid
             ORDER BY mp.created_at ASC'
        );
        $stmt->execute(['tid' => $tripId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[] = self::fromRow($row);
        }
        return $out;
    }

    /**
     * @param array{participant_id:int, pin_type:string, label:?string, description:?string, geojson:string, color:?string} $data
     */
    public static function create(array $data): self
    {
        $pdo = Connection::get();
        $stmt = $pdo->prepare(
            'INSERT INTO participant_map_pins (participant_id, pin_type, label, description, geojson, color)
             VALUES (:pid, :type, :label, :desc, :geojson, :color)'
        );
        $stmt->execute([
            'pid'     => $data['participant_id'],
            'type'    => $data['pin_type'],
            'label'   => $data['label'],
            'desc'    => $data['description'],
            'geojson' => $data['geojson'],
            'color'   => $data['color'],
        ]);
        return self::findById((int) $pdo->lastInsertId());
    }

    public function update(?string $label, ?string $description): self
    {
        Connection::get()->prepare(
            'UPDATE participant_map_pins SET label = :l, description = :d WHERE id = :id'
        )->execute([
            'l'  => $label,
            'd'  => $description,
            'id' => $this->id,
        ]);
        return self::findById($this->id);
    }

    public function delete(): void
    {
        Connection::get()->prepare('DELETE FROM participant_map_pins WHERE id = :id')
            ->execute(['id' => $this->id]);
    }

    public function belongsToParticipant(int $participantId): bool
    {
        return $this->participantId === $participantId;
    }

    /**
     * Format dla JSON response - sparsowany GeoJSON jako obiekt.
     */
    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'participant_id' => $this->participantId,
            'pin_type'       => $this->pinType,
            'label'          => $this->label,
            'description'    => $this->description,
            'geojson'        => json_decode($this->geojson, true),
            'color'          => $this->color,
            'created_at'     => $this->createdAt,
        ];
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id:            (int) $row['id'],
            participantId: (int) $row['participant_id'],
            pinType:       (string) $row['pin_type'],
            label:         $row['label']       !== null ? (string) $row['label']       : null,
            description:   $row['description'] !== null ? (string) $row['description'] : null,
            geojson:       (string) $row['geojson'],
            color:         $row['color']       !== null ? (string) $row['color']       : null,
            createdAt:     (string) $row['created_at'],
        );
    }
}
