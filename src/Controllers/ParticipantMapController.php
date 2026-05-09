<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Helpers\Csrf;
use App\Models\MapPin;
use App\Models\Participant;
use App\Models\Trip;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\MapColorService;

/**
 * Endpointy AJAX dla kroku 10 wizarda - pinezki/trasy/obszary na mapie.
 */
final class ParticipantMapController extends Controller
{
    public function listPins(Request $request, array $args): never
    {
        [$participant] = $this->resolve((string) $args['token']);
        $pins = array_map(static fn(MapPin $p) => $p->toArray(), MapPin::listForParticipant($participant->id));

        $this->json([
            'ok'    => true,
            'color' => MapColorService::forToken($participant->accessToken),
            'pins'  => $pins,
        ]);
    }

    public function createPin(Request $request, array $args): never
    {
        Csrf::validate();
        [$participant, $trip] = $this->resolve((string) $args['token']);

        $type    = (string) $request->input('pin_type', '');
        $label   = trim((string) $request->input('label', ''));
        $desc    = trim((string) $request->input('description', ''));
        $geojson = $request->input('geojson');

        if (!in_array($type, ['marker', 'polyline', 'polygon'], true)) {
            $this->json(['ok' => false, 'error' => 'Niepoprawny typ pinezki.'], 422);
        }
        if (!is_array($geojson) || empty($geojson)) {
            $this->json(['ok' => false, 'error' => 'Brak danych GeoJSON.'], 422);
        }

        $pin = MapPin::create([
            'participant_id' => $participant->id,
            'pin_type'       => $type,
            'label'          => $label !== '' ? mb_substr($label, 0, 150) : null,
            'description'    => $desc  !== '' ? mb_substr($desc, 0, 5000) : null,
            'geojson'        => json_encode($geojson, JSON_UNESCAPED_UNICODE),
            'color'          => MapColorService::forToken($participant->accessToken),
        ]);

        $this->logIfAdmin($trip, $participant, 'map_pin_created', null, $pin->toArray());

        $this->json(['ok' => true, 'pin' => $pin->toArray()]);
    }

    public function updatePin(Request $request, array $args): never
    {
        Csrf::validate();
        [$participant, $trip] = $this->resolve((string) $args['token']);

        $pin = MapPin::findById((int) $args['id']);
        if ($pin === null || !$pin->belongsToParticipant($participant->id)) {
            $this->json(['ok' => false, 'error' => 'Nie znaleziono pinezki.'], 404);
        }

        $label = trim((string) $request->input('label', ''));
        $desc  = trim((string) $request->input('description', ''));

        $oldData = $pin->toArray();
        $pin = $pin->update(
            $label !== '' ? mb_substr($label, 0, 150) : null,
            $desc  !== '' ? mb_substr($desc, 0, 5000) : null
        );

        $this->logIfAdmin($trip, $participant, 'map_pin_updated', $oldData, $pin->toArray());

        $this->json(['ok' => true, 'pin' => $pin->toArray()]);
    }

    public function deletePin(Request $request, array $args): never
    {
        Csrf::validate();
        [$participant, $trip] = $this->resolve((string) $args['token']);

        $pin = MapPin::findById((int) $args['id']);
        if ($pin === null || !$pin->belongsToParticipant($participant->id)) {
            $this->json(['ok' => false, 'error' => 'Nie znaleziono pinezki.'], 404);
        }

        $oldData = $pin->toArray();
        $pin->delete();

        $this->logIfAdmin($trip, $participant, 'map_pin_deleted', $oldData, null);

        $this->json(['ok' => true]);
    }

    /**
     * @return array{0:Participant,1:Trip}
     */
    private function resolve(string $token): array
    {
        $participant = Participant::findByAccessToken($token);
        if ($participant === null) $this->notFound('Nieznany link.');
        $trip = Trip::findById($participant->tripId);
        if ($trip === null || !$trip->isActive) $this->notFound('Wyjazd niedostepny.');
        return [$participant, $trip];
    }

    private function logIfAdmin(Trip $trip, Participant $participant, string $field, mixed $old, mixed $new): void
    {
        $auth = new AuthService();
        if (!$auth->isLoggedIn()) return;
        $admin = $auth->currentAdmin();
        if ($admin === null) return;
        AuditService::log($trip->id, $participant->id, $admin->id, $field, $old, $new);
    }
}
