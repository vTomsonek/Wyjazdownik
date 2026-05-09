<?php
declare(strict_types=1);

namespace App\Models;

use App\Database\Connection;
use App\Services\TokenService;
use PDO;

/**
 * Model wyjazdu.
 *
 * Wszystkie zapytania filtrujące "moje wyjazdy" wymagają adminId - nie ma
 * sposobu na zapytanie cross-admin (poza superuserem, którego nie mamy).
 */
final class Trip
{
    public function __construct(
        public readonly int $id,
        public readonly int $adminId,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly ?string $bannerImage,
        public readonly string $dateFrom,
        public readonly string $dateTo,
        public readonly string $calendarMode,
        public readonly bool $showIndividualResponses,
        public readonly bool $isActive,
        public readonly string $summaryPublicToken,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
    }

    public static function findById(int $id): ?self
    {
        $pdo  = Connection::get();
        $stmt = $pdo->prepare('SELECT * FROM trips WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    /**
     * Sprawdza że wyjazd należy do tego admina - dla autoryzacji.
     */
    public static function findByIdForAdmin(int $id, int $adminId): ?self
    {
        $trip = self::findById($id);
        if ($trip === null || $trip->adminId !== $adminId) {
            return null;
        }
        return $trip;
    }

    /**
     * Lista wyjazdów dla danego admina, posortowana od najnowszej.
     * Zwraca tablicę ze wzbogaconymi metadanymi (liczniki uczestników).
     *
     * @return list<array{trip:Trip, totalParticipants:int, completed:int}>
     */
    public static function listForAdmin(int $adminId): array
    {
        $pdo  = Connection::get();
        $stmt = $pdo->prepare(
            'SELECT t.*,
                    (SELECT COUNT(*) FROM participants p WHERE p.trip_id = t.id) AS total_participants,
                    (SELECT COUNT(*) FROM participants p WHERE p.trip_id = t.id AND p.completed_at IS NOT NULL) AS completed
             FROM trips t
             WHERE t.admin_id = :admin_id
             ORDER BY t.created_at DESC'
        );
        $stmt->execute(['admin_id' => $adminId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[] = [
                'trip'              => self::fromRow($row),
                'totalParticipants' => (int) $row['total_participants'],
                'completed'         => (int) $row['completed'],
            ];
        }
        return $out;
    }

    /**
     * @param array{
     *   admin_id:int, name:string, slug:string, description:?string,
     *   banner_image:?string, date_from:string, date_to:string,
     *   calendar_mode:string, show_individual_responses:bool, is_active:bool
     * } $data
     */
    public static function create(array $data): self
    {
        $pdo   = Connection::get();
        $token = TokenService::generate();
        $stmt  = $pdo->prepare(
            'INSERT INTO trips
             (admin_id, name, slug, description, banner_image, date_from, date_to,
              calendar_mode, show_individual_responses, is_active, summary_public_token)
             VALUES
             (:admin_id, :name, :slug, :description, :banner_image, :date_from, :date_to,
              :calendar_mode, :show_individual_responses, :is_active, :summary_public_token)'
        );
        $stmt->execute([
            'admin_id'                  => $data['admin_id'],
            'name'                      => $data['name'],
            'slug'                      => $data['slug'],
            'description'               => $data['description'],
            'banner_image'              => $data['banner_image'],
            'date_from'                 => $data['date_from'],
            'date_to'                   => $data['date_to'],
            'calendar_mode'             => $data['calendar_mode'],
            'show_individual_responses' => $data['show_individual_responses'] ? 1 : 0,
            'is_active'                 => $data['is_active'] ? 1 : 0,
            'summary_public_token'      => $token,
        ]);
        return self::findById((int) $pdo->lastInsertId());
    }

    /**
     * @param array<string,mixed> $data Tylko pola które mają być zaktualizowane.
     */
    public function update(array $data): self
    {
        $allowed = [
            'name', 'slug', 'description', 'banner_image', 'date_from', 'date_to',
            'calendar_mode', 'show_individual_responses', 'is_active',
        ];
        $sets = [];
        $params = ['id' => $this->id];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "`{$field}` = :{$field}";
                $params[$field] = is_bool($data[$field]) ? (int) $data[$field] : $data[$field];
            }
        }
        if (empty($sets)) {
            return $this;
        }
        $sql = 'UPDATE trips SET ' . implode(', ', $sets) . ' WHERE id = :id';
        Connection::get()->prepare($sql)->execute($params);
        return self::findById($this->id);
    }

    public function delete(): void
    {
        $pdo = Connection::get();
        $stmt = $pdo->prepare('DELETE FROM trips WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    public static function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $pdo = Connection::get();
        if ($exceptId !== null) {
            $stmt = $pdo->prepare('SELECT 1 FROM trips WHERE slug = :slug AND id != :id LIMIT 1');
            $stmt->execute(['slug' => $slug, 'id' => $exceptId]);
        } else {
            $stmt = $pdo->prepare('SELECT 1 FROM trips WHERE slug = :slug LIMIT 1');
            $stmt->execute(['slug' => $slug]);
        }
        return (bool) $stmt->fetch();
    }

    /**
     * @param array<string,mixed> $row
     */
    private static function fromRow(array $row): self
    {
        return new self(
            id:                       (int) $row['id'],
            adminId:                  (int) $row['admin_id'],
            name:                     (string) $row['name'],
            slug:                     (string) $row['slug'],
            description:              $row['description'] !== null ? (string) $row['description'] : null,
            bannerImage:              $row['banner_image'] !== null ? (string) $row['banner_image'] : null,
            dateFrom:                 (string) $row['date_from'],
            dateTo:                   (string) $row['date_to'],
            calendarMode:             (string) $row['calendar_mode'],
            showIndividualResponses:  (bool) $row['show_individual_responses'],
            isActive:                 (bool) $row['is_active'],
            summaryPublicToken:       (string) $row['summary_public_token'],
            createdAt:                (string) $row['created_at'],
            updatedAt:                (string) $row['updated_at'],
        );
    }
}
