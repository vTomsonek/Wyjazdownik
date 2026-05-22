<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Helpers\Csrf;
use App\Models\Participant;
use App\Models\Trip;
use App\Models\TripPlace;
use App\Models\TripPlaceMedia;
use App\Models\TripPlaceVote;
use App\Services\MapColorService;
use App\Services\RouteSuggestionService;
use App\Services\UploadService;
use RuntimeException;

/**
 * Nowa kolaboratywna mapa atrakcji - zastapuje stara mape pomyslow z kroku 10.
 * Kazdy uczestnik (z prawidlowym tokenem) moze dodawac konkretne miejsca,
 * a w kolejnym etapie ocenia te dodane przez innych.
 *
 * Endpointy:
 *   GET  /p/{token}/atrakcje          - widok mapy + lista
 *   GET  /p/{token}/places            - AJAX: lista wszystkich miejsc trip'u
 *   POST /p/{token}/places            - AJAX: dodaj miejsce
 *   POST /p/{token}/places/{id}/edit  - AJAX: edytuj swoje miejsce (name/description)
 *   POST /p/{token}/places/{id}/delete - AJAX: usun swoje miejsce
 */
final class TripPlacesController extends Controller
{
    public function showMap(Request $request, array $args): never
    {
        [$participant, $trip] = $this->resolve((string) $args['token']);

        $places = TripPlace::listForTrip($trip->id);

        // Mapa: participant_id => [nickname, color, avatar]
        $participants = Participant::listVisibleForTrip($trip->id);
        $authors = [];
        foreach ($participants as $p) {
            $authors[$p->id] = [
                'nickname' => $p->nickname,
                'color'    => MapColorService::forParticipant($p),
                'avatar'   => $p->avatarPath,
            ];
        }

        // Statystyki ocen dla wszystkich miejsc trip'u - jednym query
        $voteStats = TripPlaceVote::statsForTrip($trip->id, $participant->id);

        $this->render('participant/places', [
            'title'             => 'Atrakcje - ' . $trip->name,
            'trip'              => $trip,
            'participant'       => $participant,
            'places'            => $places,
            'authors'           => $authors,
            'voteStats'         => $voteStats,
            'myColor'           => MapColorService::forParticipant($participant),
            'googleMapsApiKey'  => (string) config('google.maps_api_key', ''),
        ], 'app');
    }

    public function listPlaces(Request $request, array $args): never
    {
        [, $trip] = $this->resolve((string) $args['token']);
        $places = array_map(static fn(TripPlace $p) => $p->toArray(), TripPlace::listForTrip($trip->id));
        $this->json(['ok' => true, 'places' => $places]);
    }

    public function createPlace(Request $request, array $args): never
    {
        Csrf::validate();
        [$participant, $trip] = $this->resolve((string) $args['token']);

        $name          = trim((string) $request->input('name', ''));
        $description   = trim((string) $request->input('description', ''));
        $visitMinutes  = (int) $request->input('visit_minutes', 60);
        $lat           = $request->input('lat');
        $lng           = $request->input('lng');
        $address       = trim((string) $request->input('address', ''));
        $countryCode   = strtolower(trim((string) $request->input('country_code', '')));
        $osmPlaceId    = trim((string) $request->input('osm_place_id', ''));
        $googlePlaceId = trim((string) $request->input('google_place_id', ''));

        // Clamp visit_minutes do rozsadnych wartosci (15min - 12h)
        if ($visitMinutes < 15) $visitMinutes = 15;
        if ($visitMinutes > 720) $visitMinutes = 720;

        if ($name === '' || mb_strlen($name) > 200) {
            $this->json(['ok' => false, 'error' => 'Podaj nazwę (do 200 znaków).'], 422);
        }
        if (!is_numeric($lat) || !is_numeric($lng)) {
            $this->json(['ok' => false, 'error' => 'Brak współrzędnych.'], 422);
        }
        $lat = (float) $lat;
        $lng = (float) $lng;
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            $this->json(['ok' => false, 'error' => 'Nieprawidłowe współrzędne.'], 422);
        }
        if ($countryCode !== '' && (strlen($countryCode) !== 2 || !ctype_alpha($countryCode))) {
            $countryCode = ''; // ignoruj nieprawidłowy ISO code
        }

        $place = TripPlace::create([
            'trip_id'         => $trip->id,
            'participant_id'  => $participant->id,
            'name'            => $name,
            'description'     => $description !== '' ? $description : null,
            'visit_minutes'   => $visitMinutes,
            'lat'             => $lat,
            'lng'             => $lng,
            'address'         => $address !== '' ? $address : null,
            'country_code'    => $countryCode !== '' ? $countryCode : null,
            'osm_place_id'    => $osmPlaceId !== '' ? $osmPlaceId : null,
            'google_place_id' => $googlePlaceId !== '' ? $googlePlaceId : null,
        ]);

        $this->json(['ok' => true, 'place' => $place->toArray()]);
    }

    public function updatePlace(Request $request, array $args): never
    {
        Csrf::validate();
        [$participant] = $this->resolve((string) $args['token']);

        $place = TripPlace::findById((int) $args['id']);
        if ($place === null) {
            $this->json(['ok' => false, 'error' => 'Nie znaleziono miejsca.'], 404);
        }
        if (!$place->belongsToParticipant($participant->id)) {
            $this->json(['ok' => false, 'error' => 'Możesz edytować tylko swoje miejsca.'], 403);
        }

        $name = trim((string) $request->input('name', ''));
        $description = trim((string) $request->input('description', ''));
        $visitMinutes = (int) $request->input('visit_minutes', $place->visitMinutes);

        if ($name === '' || mb_strlen($name) > 200) {
            $this->json(['ok' => false, 'error' => 'Podaj nazwę (do 200 znaków).'], 422);
        }
        if ($visitMinutes < 15) $visitMinutes = 15;
        if ($visitMinutes > 720) $visitMinutes = 720;

        $place = $place->update([
            'name'          => $name,
            'description'   => $description !== '' ? $description : null,
            'visit_minutes' => $visitMinutes,
        ]);

        $this->json(['ok' => true, 'place' => $place->toArray()]);
    }

    public function deletePlace(Request $request, array $args): never
    {
        Csrf::validate();
        [$participant] = $this->resolve((string) $args['token']);

        $place = TripPlace::findById((int) $args['id']);
        if ($place === null) {
            $this->json(['ok' => false, 'error' => 'Nie znaleziono miejsca.'], 404);
        }
        if (!$place->belongsToParticipant($participant->id)) {
            $this->json(['ok' => false, 'error' => 'Możesz usuwać tylko swoje miejsca.'], 403);
        }

        // Cleanup mediow z dysku
        foreach (TripPlaceMedia::listForPlace($place->id) as $media) {
            if ($media->filePath !== null) {
                UploadService::delete($media->filePath);
            }
        }

        $place->delete();
        $this->json(['ok' => true]);
    }

    // ========================================================================
    // ETAP 2: Media (zdjecia, wideo, linki) dla miejsca
    // ========================================================================

    public function listMedia(Request $request, array $args): never
    {
        [, $trip] = $this->resolve((string) $args['token']);
        $place = TripPlace::findById((int) $args['id']);
        if ($place === null || $place->tripId !== $trip->id) {
            $this->json(['ok' => false, 'error' => 'Nie znaleziono miejsca.'], 404);
        }
        $media = array_map(static fn(TripPlaceMedia $m) => $m->toArray(), TripPlaceMedia::listForPlace($place->id));
        $this->json(['ok' => true, 'media' => $media]);
    }

    public function uploadMedia(Request $request, array $args): never
    {
        Csrf::validate();
        [$participant, $trip] = $this->resolve((string) $args['token']);

        $place = TripPlace::findById((int) $args['id']);
        if ($place === null || $place->tripId !== $trip->id) {
            $this->json(['ok' => false, 'error' => 'Nie znaleziono miejsca.'], 404);
        }
        // Tylko autor moze wgrywac media do swojego miejsca
        if (!$place->belongsToParticipant($participant->id)) {
            $this->json(['ok' => false, 'error' => 'Możesz wgrywać media tylko do swoich miejsc.'], 403);
        }

        $file    = $_FILES['file'] ?? [];
        $caption = trim((string) $request->input('caption', ''));
        $type    = (string) $request->input('type', 'image'); // image | video

        if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $this->json(['ok' => false, 'error' => 'Brak pliku.'], 422);
        }

        // Limity per miejsce
        if ($type === 'image') {
            $count = TripPlaceMedia::countByTypeForPlace($place->id, TripPlaceMedia::TYPE_IMAGE);
            if ($count >= 5) {
                $this->json(['ok' => false, 'error' => 'Limit 5 zdjęć na miejsce.'], 422);
            }
        } elseif ($type === 'video') {
            $count = TripPlaceMedia::countByTypeForPlace($place->id, TripPlaceMedia::TYPE_VIDEO);
            if ($count >= 3) {
                $this->json(['ok' => false, 'error' => 'Limit 3 wideo na miejsce.'], 422);
            }
        } else {
            $this->json(['ok' => false, 'error' => 'Niepoprawny typ media.'], 422);
        }

        try {
            $relPath = $type === 'image'
                ? UploadService::uploadPlaceImage($file, $place->id)
                : UploadService::uploadPlaceVideo($file, $place->id);
        } catch (RuntimeException $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        if ($relPath === null) {
            $this->json(['ok' => false, 'error' => 'Nie udało się zapisać pliku.'], 500);
        }

        $media = TripPlaceMedia::create([
            'place_id'  => $place->id,
            'type'      => $type,
            'file_path' => $relPath,
            'caption'   => $caption !== '' ? $caption : null,
        ]);

        $this->json(['ok' => true, 'media' => $media->toArray()]);
    }

    public function addLink(Request $request, array $args): never
    {
        Csrf::validate();
        [$participant, $trip] = $this->resolve((string) $args['token']);

        $place = TripPlace::findById((int) $args['id']);
        if ($place === null || $place->tripId !== $trip->id) {
            $this->json(['ok' => false, 'error' => 'Nie znaleziono miejsca.'], 404);
        }
        if (!$place->belongsToParticipant($participant->id)) {
            $this->json(['ok' => false, 'error' => 'Możesz dodawać linki tylko do swoich miejsc.'], 403);
        }

        $url     = trim((string) $request->input('url', ''));
        $caption = trim((string) $request->input('caption', ''));

        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            $this->json(['ok' => false, 'error' => 'Podaj prawidłowy URL (z https://).'], 422);
        }
        if (!preg_match('#^https?://#i', $url)) {
            $this->json(['ok' => false, 'error' => 'URL musi zaczynać się od http:// lub https://.'], 422);
        }
        if (mb_strlen($url) > 500) {
            $this->json(['ok' => false, 'error' => 'URL za długi (max 500 znaków).'], 422);
        }

        $media = TripPlaceMedia::create([
            'place_id' => $place->id,
            'type'     => TripPlaceMedia::TYPE_LINK,
            'url'      => $url,
            'caption'  => $caption !== '' ? $caption : null,
        ]);

        $this->json(['ok' => true, 'media' => $media->toArray()]);
    }

    public function deleteMedia(Request $request, array $args): never
    {
        Csrf::validate();
        [$participant, $trip] = $this->resolve((string) $args['token']);

        $place = TripPlace::findById((int) $args['id']);
        if ($place === null || $place->tripId !== $trip->id) {
            $this->json(['ok' => false, 'error' => 'Nie znaleziono miejsca.'], 404);
        }
        if (!$place->belongsToParticipant($participant->id)) {
            $this->json(['ok' => false, 'error' => 'Możesz usuwać media tylko ze swoich miejsc.'], 403);
        }

        $media = TripPlaceMedia::findById((int) $args['mediaId']);
        if ($media === null || $media->placeId !== $place->id) {
            $this->json(['ok' => false, 'error' => 'Nie znaleziono media.'], 404);
        }

        if ($media->filePath !== null) {
            UploadService::delete($media->filePath);
        }
        $media->delete();

        $this->json(['ok' => true]);
    }

    // ========================================================================
    // ETAP 3: Oceny gwiazdkowe 1-5
    // ========================================================================

    public function vote(Request $request, array $args): never
    {
        Csrf::validate();
        [$participant, $trip] = $this->resolve((string) $args['token']);

        $place = TripPlace::findById((int) $args['id']);
        if ($place === null || $place->tripId !== $trip->id) {
            $this->json(['ok' => false, 'error' => 'Nie znaleziono miejsca.'], 404);
        }

        $rawScore = $request->input('score', null);
        if ($rawScore === null || $rawScore === '' || !is_numeric($rawScore)) {
            $this->json(['ok' => false, 'error' => 'Brak oceny.'], 422);
        }
        $score = (float) $rawScore;
        // Snap do polowki + walidacja zakresu
        $halfSteps = (int) round($score * 2);
        if ($halfSteps < 1 || $halfSteps > 10) {
            $this->json(['ok' => false, 'error' => 'Ocena musi być 0.5 - 5.0.'], 422);
        }
        $score = $halfSteps / 2;

        try {
            TripPlaceVote::upsert($place->id, $participant->id, $score);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }

        $stats = TripPlaceVote::statsForPlace($place->id, $participant->id);
        $this->json(['ok' => true, 'stats' => $stats]);
    }

    public function deleteVote(Request $request, array $args): never
    {
        Csrf::validate();
        [$participant, $trip] = $this->resolve((string) $args['token']);

        $place = TripPlace::findById((int) $args['id']);
        if ($place === null || $place->tripId !== $trip->id) {
            $this->json(['ok' => false, 'error' => 'Nie znaleziono miejsca.'], 404);
        }

        TripPlaceVote::deleteByPlaceAndParticipant($place->id, $participant->id);
        $stats = TripPlaceVote::statsForPlace($place->id, $participant->id);
        $this->json(['ok' => true, 'stats' => $stats]);
    }

    /**
     * Widok mini-wizarda ocen - uczestnik przechodzi przez miejsca ktorych jeszcze nie ocenil.
     * URL: /p/{token}/atrakcje/oceniaj
     */
    public function showRater(Request $request, array $args): never
    {
        [$participant, $trip] = $this->resolve((string) $args['token']);

        $allPlaces = TripPlace::listForTrip($trip->id);
        $voteStats = TripPlaceVote::statsForTrip($trip->id, $participant->id);

        // Filtruj tylko nieocenione przez tego uczestnika
        $toRate = [];
        foreach ($allPlaces as $p) {
            $myScore = $voteStats[$p->id]['my_score'] ?? null;
            if ($myScore === null) {
                $toRate[] = $p;
            }
        }

        // Mapa: participant_id => nick + color (do atrybucji "kto dodal")
        $participants = Participant::listVisibleForTrip($trip->id);
        $authors = [];
        foreach ($participants as $p) {
            $authors[$p->id] = [
                'nickname' => $p->nickname,
                'color'    => MapColorService::forParticipant($p),
            ];
        }

        $this->render('participant/places-rate', [
            'title'             => 'Oceń miejsca - ' . $trip->name,
            'trip'              => $trip,
            'participant'       => $participant,
            'toRate'            => $toRate,
            'authors'           => $authors,
            'totalCount'        => count($allPlaces),
            'remainingCount'    => count($toRate),
            'googleMapsApiKey'  => (string) config('google.maps_api_key', ''),
        ], 'app');
    }

    // ========================================================================
    // ETAP 4: Propozycje tras (algorytm klastrowania + TSP nearest neighbor)
    // ========================================================================

    public function suggestRoutes(Request $request, array $args): never
    {
        [, $trip] = $this->resolve((string) $args['token']);
        $service = new RouteSuggestionService();
        $suggestions = $service->suggest($trip->id);
        $this->json(['ok' => true, 'routes' => $suggestions]);
    }

    /**
     * @return array{0:Participant,1:Trip}
     */
    private function resolve(string $token): array
    {
        $participant = Participant::findByAccessToken($token);
        if ($participant === null) $this->notFound('Nieznany link.');
        $trip = Trip::findById($participant->tripId);
        if ($trip === null || !$trip->isActive) $this->notFound('Wyjazd niedostępny.');
        return [$participant, $trip];
    }
}
