<?php
declare(strict_types=1);

namespace App\Models;

use App\Database\Connection;

/**
 * Oceny gwiazdkowe 1-5 dla atrakcji. Kazdy uczestnik moze oddac
 * dokladnie 1 ocene per miejsce (unique key place_id + participant_id).
 */
final class TripPlaceVote
{
    public function __construct(
        public readonly int $id,
        public readonly int $placeId,
        public readonly int $participantId,
        public readonly float $score,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}

    /**
     * Upsert - jesli uczestnik juz oddal glos, aktualizuje. Inaczej tworzy.
     * Akceptuje polowki: 0.5, 1.0, 1.5, ..., 5.0
     */
    public static function upsert(int $placeId, int $participantId, float $score): self
    {
        // Snap do polowki (0.5 step) i waliduj zakres
        $halfSteps = (int) round($score * 2);
        if ($halfSteps < 1 || $halfSteps > 10) {
            throw new \InvalidArgumentException('Ocena musi być 0.5 - 5.0 w połówkach.');
        }
        $score = $halfSteps / 2;
        $pdo = Connection::get();
        $stmt = $pdo->prepare(
            'INSERT INTO trip_place_votes (place_id, participant_id, score)
             VALUES (:p, :u, :s)
             ON DUPLICATE KEY UPDATE score = VALUES(score), updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute(['p' => $placeId, 'u' => $participantId, 's' => $score]);
        $vote = self::findByPlaceAndParticipant($placeId, $participantId);
        if ($vote === null) {
            throw new \RuntimeException('Nie udało się zapisać oceny.');
        }
        return $vote;
    }

    public static function findByPlaceAndParticipant(int $placeId, int $participantId): ?self
    {
        $stmt = Connection::get()->prepare(
            'SELECT * FROM trip_place_votes WHERE place_id = :p AND participant_id = :u LIMIT 1'
        );
        $stmt->execute(['p' => $placeId, 'u' => $participantId]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    public static function deleteByPlaceAndParticipant(int $placeId, int $participantId): void
    {
        Connection::get()->prepare(
            'DELETE FROM trip_place_votes WHERE place_id = :p AND participant_id = :u'
        )->execute(['p' => $placeId, 'u' => $participantId]);
    }

    /**
     * Statystyki dla miejsca: {avg, count, my_score}.
     * @return array{avg:float|null,count:int,my_score:int|null}
     */
    public static function statsForPlace(int $placeId, int $forParticipantId): array
    {
        $stmt = Connection::get()->prepare(
            'SELECT AVG(score) AS avg_score, COUNT(*) AS cnt FROM trip_place_votes WHERE place_id = :p'
        );
        $stmt->execute(['p' => $placeId]);
        $row = $stmt->fetch();
        $avg = $row && $row['cnt'] > 0 ? round((float) $row['avg_score'], 2) : null;
        $count = $row ? (int) $row['cnt'] : 0;

        $myVote = self::findByPlaceAndParticipant($placeId, $forParticipantId);
        $mine = $myVote ? (float) $myVote->score : null;

        return ['avg' => $avg, 'count' => $count, 'my_score' => $mine];
    }

    /**
     * Statystyki ocen dla wszystkich miejsc trip'u - jednym query.
     * @return array<int, array{avg:float|null,count:int,my_score:int|null}> klucz = place_id
     */
    public static function statsForTrip(int $tripId, int $forParticipantId): array
    {
        $stmt = Connection::get()->prepare(
            'SELECT v.place_id, AVG(v.score) AS avg_score, COUNT(*) AS cnt
             FROM trip_place_votes v
             JOIN trip_places p ON p.id = v.place_id
             WHERE p.trip_id = :tid
             GROUP BY v.place_id'
        );
        $stmt->execute(['tid' => $tripId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['place_id']] = [
                'avg'      => round((float) $row['avg_score'], 2),
                'count'    => (int) $row['cnt'],
                'my_score' => null,
            ];
        }

        // Doloz moje oceny do mapy
        $myStmt = Connection::get()->prepare(
            'SELECT v.place_id, v.score
             FROM trip_place_votes v
             JOIN trip_places p ON p.id = v.place_id
             WHERE p.trip_id = :tid AND v.participant_id = :u'
        );
        $myStmt->execute(['tid' => $tripId, 'u' => $forParticipantId]);
        foreach ($myStmt->fetchAll() as $row) {
            $pid = (int) $row['place_id'];
            if (!isset($out[$pid])) {
                $out[$pid] = ['avg' => null, 'count' => 0, 'my_score' => (float) $row['score']];
            } else {
                $out[$pid]['my_score'] = (float) $row['score'];
            }
        }
        return $out;
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id:            (int) $row['id'],
            placeId:       (int) $row['place_id'],
            participantId: (int) $row['participant_id'],
            score:         (float) $row['score'],
            createdAt:     (string) $row['created_at'],
            updatedAt:     (string) $row['updated_at'],
        );
    }
}
