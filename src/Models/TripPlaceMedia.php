<?php
declare(strict_types=1);

namespace App\Models;

use App\Database\Connection;

/**
 * Media (zdjecia, wideo, linki) zwiazane z atrakcja w trip_places.
 */
final class TripPlaceMedia
{
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';
    public const TYPE_LINK  = 'link';

    public function __construct(
        public readonly int $id,
        public readonly int $placeId,
        public readonly string $type,
        public readonly ?string $filePath,
        public readonly ?string $url,
        public readonly ?string $caption,
        public readonly int $sortOrder,
        public readonly string $createdAt,
    ) {}

    public static function findById(int $id): ?self
    {
        $stmt = Connection::get()->prepare('SELECT * FROM trip_place_media WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    /**
     * @return list<TripPlaceMedia>
     */
    public static function listForPlace(int $placeId): array
    {
        $stmt = Connection::get()->prepare(
            'SELECT * FROM trip_place_media WHERE place_id = :pid
             ORDER BY sort_order ASC, created_at ASC'
        );
        $stmt->execute(['pid' => $placeId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) $out[] = self::fromRow($row);
        return $out;
    }

    /**
     * Liczy ilosc medi konkretnego typu dla miejsca (do walidacji limitow).
     */
    public static function countByTypeForPlace(int $placeId, string $type): int
    {
        $stmt = Connection::get()->prepare(
            'SELECT COUNT(*) AS c FROM trip_place_media WHERE place_id = :pid AND type = :t'
        );
        $stmt->execute(['pid' => $placeId, 't' => $type]);
        return (int) ($stmt->fetch()['c'] ?? 0);
    }

    public static function create(array $data): self
    {
        $pdo = Connection::get();
        $stmt = $pdo->prepare(
            'INSERT INTO trip_place_media (place_id, type, file_path, url, caption, sort_order)
             VALUES (:place_id, :type, :file_path, :url, :caption, :sort_order)'
        );
        $stmt->execute([
            'place_id'   => $data['place_id'],
            'type'       => $data['type'],
            'file_path'  => $data['file_path'] ?? null,
            'url'        => $data['url'] ?? null,
            'caption'    => $data['caption'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        return self::findById((int) $pdo->lastInsertId());
    }

    public function delete(): void
    {
        Connection::get()->prepare('DELETE FROM trip_place_media WHERE id = :id')->execute(['id' => $this->id]);
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'place_id'   => $this->placeId,
            'type'       => $this->type,
            'file_path'  => $this->filePath,
            'url'        => $this->url,
            'caption'    => $this->caption,
            'sort_order' => $this->sortOrder,
        ];
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id:        (int) $row['id'],
            placeId:   (int) $row['place_id'],
            type:      (string) $row['type'],
            filePath:  $row['file_path'] !== null ? (string) $row['file_path'] : null,
            url:       $row['url']       !== null ? (string) $row['url']       : null,
            caption:   $row['caption']   !== null ? (string) $row['caption']   : null,
            sortOrder: (int) $row['sort_order'],
            createdAt: (string) $row['created_at'],
        );
    }
}
