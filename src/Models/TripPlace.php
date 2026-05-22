<?php
declare(strict_types=1);

namespace App\Models;

use App\Database\Connection;

/**
 * Model miejsca/atrakcji na kolaboratywnej mapie trip'u.
 * Kazdy uczestnik moze dodac wiele miejsc - inne mozna potem ocenic (Etap 3).
 */
final class TripPlace
{
    public function __construct(
        public readonly int $id,
        public readonly int $tripId,
        public readonly int $participantId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly float $lat,
        public readonly float $lng,
        public readonly ?string $address,
        public readonly ?string $countryCode,
        public readonly ?string $osmPlaceId,
        public readonly ?string $googlePlaceId,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly int $visitMinutes = 60,
    ) {}

    public static function findById(int $id): ?self
    {
        $stmt = Connection::get()->prepare('SELECT * FROM trip_places WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    /**
     * @return list<TripPlace>
     */
    public static function listForTrip(int $tripId): array
    {
        $stmt = Connection::get()->prepare(
            'SELECT * FROM trip_places WHERE trip_id = :tid
             ORDER BY created_at DESC'
        );
        $stmt->execute(['tid' => $tripId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) $out[] = self::fromRow($row);
        return $out;
    }

    public static function create(array $data): self
    {
        $pdo = Connection::get();
        $stmt = $pdo->prepare(
            'INSERT INTO trip_places
                (trip_id, participant_id, name, description, visit_minutes, lat, lng, address, country_code, osm_place_id, google_place_id)
             VALUES
                (:trip_id, :participant_id, :name, :description, :visit_minutes, :lat, :lng, :address, :country_code, :osm_place_id, :google_place_id)'
        );
        $stmt->execute([
            'trip_id'         => $data['trip_id'],
            'participant_id'  => $data['participant_id'],
            'name'            => $data['name'],
            'description'     => $data['description'] ?? null,
            'visit_minutes'   => $data['visit_minutes'] ?? 60,
            'lat'             => $data['lat'],
            'lng'             => $data['lng'],
            'address'         => $data['address'] ?? null,
            'country_code'    => $data['country_code'] ?? null,
            'osm_place_id'    => $data['osm_place_id'] ?? null,
            'google_place_id' => $data['google_place_id'] ?? null,
        ]);
        return self::findById((int) $pdo->lastInsertId());
    }

    public function update(array $data): self
    {
        $allowed = ['name', 'description', 'visit_minutes'];
        $sets = [];
        $params = ['id' => $this->id];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $sets[] = "`{$f}` = :{$f}";
                $params[$f] = $data[$f];
            }
        }
        if (empty($sets)) return $this;
        Connection::get()->prepare('UPDATE trip_places SET ' . implode(', ', $sets) . ' WHERE id = :id')->execute($params);
        return self::findById($this->id);
    }

    public function delete(): void
    {
        Connection::get()->prepare('DELETE FROM trip_places WHERE id = :id')->execute(['id' => $this->id]);
    }

    public function belongsToParticipant(int $participantId): bool
    {
        return $this->participantId === $participantId;
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'trip_id'         => $this->tripId,
            'participant_id'  => $this->participantId,
            'name'            => $this->name,
            'description'     => $this->description,
            'visit_minutes'   => $this->visitMinutes,
            'lat'             => $this->lat,
            'lng'             => $this->lng,
            'address'         => $this->address,
            'country_code'    => $this->countryCode,
            'google_place_id' => $this->googlePlaceId,
            'created_at'      => $this->createdAt,
        ];
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id:            (int) $row['id'],
            tripId:        (int) $row['trip_id'],
            participantId: (int) $row['participant_id'],
            name:          (string) $row['name'],
            description:   $row['description']  !== null ? (string) $row['description']  : null,
            lat:           (float) $row['lat'],
            lng:           (float) $row['lng'],
            address:       $row['address']      !== null ? (string) $row['address']      : null,
            countryCode:   $row['country_code'] !== null ? (string) $row['country_code'] : null,
            osmPlaceId:    $row['osm_place_id'] !== null ? (string) $row['osm_place_id'] : null,
            googlePlaceId: isset($row['google_place_id']) && $row['google_place_id'] !== null ? (string) $row['google_place_id'] : null,
            createdAt:     (string) $row['created_at'],
            updatedAt:     (string) $row['updated_at'],
            visitMinutes:  isset($row['visit_minutes']) ? (int) $row['visit_minutes'] : 60,
        );
    }
}
