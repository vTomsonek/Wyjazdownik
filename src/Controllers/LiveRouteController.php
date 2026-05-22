<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Database\Connection;
use App\Models\Participant;
use App\Models\Trip;
use App\Models\TripPlace;
use App\Models\TripPlaceMedia;
use App\Models\TripPlaceVote;
use App\Services\MapColorService;

/**
 * Tryb trasy (in-trip) - publiczna pelnoekranowa mapa z atrakcjami
 * + live tracking pozycji uzytkownika (przez summary_public_token).
 */
final class LiveRouteController extends Controller
{
    public function showRoute(Request $request, array $args): never
    {
        $token = (string) $args['token'];
        $stmt = Connection::get()->prepare('SELECT * FROM trips WHERE summary_public_token = :t LIMIT 1');
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch();
        if (!$row) {
            $this->notFound('Niewlasciwy link do podsumowania.');
        }

        $trip = $this->buildTrip($row);
        $places    = TripPlace::listForTrip($trip->id);
        $voteStats = TripPlaceVote::statsForTrip($trip->id, 0); // 0 = brak biezacego uczestnika (public mode)

        // Mapa: participant_id => nick + color
        $participants = Participant::listVisibleForTrip($trip->id);
        $authors = [];
        foreach ($participants as $p) {
            $authors[$p->id] = [
                'nickname' => $p->nickname,
                'color'    => MapColorService::forParticipant($p),
            ];
        }

        // JSON dla JS - lekka projekcja
        $placesJson = array_map(static function (TripPlace $p) use ($authors, $voteStats) {
            $author = $authors[$p->participantId] ?? ['nickname' => '?', 'color' => '#6B7280'];
            $vs = $voteStats[$p->id] ?? ['avg' => null, 'count' => 0];
            return [
                'id'              => $p->id,
                'name'            => $p->name,
                'description'     => $p->description,
                'address'         => $p->address,
                'lat'             => (float) $p->lat,
                'lng'             => (float) $p->lng,
                'visit_minutes'   => $p->visitMinutes,
                'google_place_id' => $p->googlePlaceId,
                'author'          => $author['nickname'],
                'author_color'    => $author['color'],
                'avg'             => $vs['avg'] !== null ? (float) $vs['avg'] : null,
                'count'           => (int) $vs['count'],
            ];
        }, $places);

        $this->render('route/live', [
            'title'            => 'Tryb trasy - ' . $trip->name,
            'description'      => 'Mapa atrakcji w trasie',
            'trip'             => $trip,
            'placesJson'       => $placesJson,
            'googleMapsApiKey' => (string) config('google.maps_api_key', ''),
        ], 'route');
    }

    /**
     * Publiczny endpoint zwracajacy media miejsca (zdjecia/wideo/linki) -
     * uzywa summary_public_token zamiast tokenu uczestnika.
     */
    public function listMedia(Request $request, array $args): never
    {
        $token = (string) $args['token'];
        $placeId = (int) $args['id'];

        $stmt = Connection::get()->prepare('SELECT id FROM trips WHERE summary_public_token = :t LIMIT 1');
        $stmt->execute(['t' => $token]);
        $tripRow = $stmt->fetch();
        if (!$tripRow) {
            $this->json(['ok' => false, 'error' => 'Nieprawidlowy link.'], 404);
        }

        $place = TripPlace::findById($placeId);
        if ($place === null || $place->tripId !== (int) $tripRow['id']) {
            $this->json(['ok' => false, 'error' => 'Nie znaleziono miejsca.'], 404);
        }

        $media = array_map(static fn(TripPlaceMedia $m) => $m->toArray(), TripPlaceMedia::listForPlace($place->id));
        $this->json(['ok' => true, 'media' => $media]);
    }

    /**
     * @param array<string,mixed> $row
     */
    private function buildTrip(array $row): Trip
    {
        return new Trip(
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
            startName:                isset($row['start_name']) && $row['start_name'] !== null ? (string) $row['start_name'] : null,
            startLat:                 isset($row['start_lat']) && $row['start_lat'] !== null ? (float) $row['start_lat'] : null,
            startLng:                 isset($row['start_lng']) && $row['start_lng'] !== null ? (float) $row['start_lng'] : null,
        );
    }
}
