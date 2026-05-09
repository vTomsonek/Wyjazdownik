<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Helpers\Csrf;
use App\Helpers\QuestionLabels;
use App\Models\MapPin;
use App\Models\Participant;
use App\Models\Trip;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\MapColorService;
use App\Services\ParticipantData;
use App\Services\WizardValidator;

final class ParticipantWizardController extends Controller
{
    private const TOTAL_STEPS = 12;

    public function welcome(Request $request, array $args): never
    {
        [$participant, $trip] = $this->resolve((string) $args['token']);

        $this->render('participant/welcome', [
            'title'       => $trip->name . ' - powitanie',
            'description' => 'Wypelnij ankiete dla wyjazdu.',
            'trip'        => $trip,
            'participant' => $participant,
            'isAdminEdit' => $this->isAdminEdit(),
        ], 'wizard');
    }

    public function step(Request $request, array $args): never
    {
        [$participant, $trip] = $this->resolve((string) $args['token']);
        $step = max(1, min(self::TOTAL_STEPS, (int) $args['step']));

        $this->render('participant/wizard', [
            'title'             => $trip->name . ' - krok ' . $step,
            'description'       => 'Wypelnij ankiete',
            'trip'              => $trip,
            'participant'       => $participant,
            'currentStep'       => $step,
            'totalSteps'        => self::TOTAL_STEPS,
            'responses'         => ParticipantData::getResponses($participant),
            'unavailableDates'  => ParticipantData::getUnavailableDates($participant),
            'preferredWeeks'    => ParticipantData::getPreferredWeeks($participant),
            'mapPins'           => MapPin::listForParticipant($participant->id),
            'questions'         => QuestionLabels::all(),
            'isAdminEdit'       => $this->isAdminEdit(),
        ], 'wizard');
    }

    public function save(Request $request, array $args): never
    {
        Csrf::validate();
        [$participant, $trip] = $this->resolve((string) $args['token']);

        $key   = (string) $request->input('key', '');
        $value = $request->input('value');

        if ($key === '') {
            $this->json(['ok' => false, 'error' => 'Brak klucza.'], 422);
        }

        if ($key === '_unavailable_dates') {
            $dates = is_array($value) ? array_values(array_filter(array_map('strval', $value))) : [];
            $old = ParticipantData::getUnavailableDates($participant);
            ParticipantData::setUnavailableDates($participant, $dates);
            $this->logIfAdmin($trip, $participant, 'unavailable_dates', $old, $dates);
            $this->json(['ok' => true, 'savedAt' => date('c')]);
        }

        if ($key === '_preferred_weeks') {
            $weeks = is_array($value) ? $value : [];
            $old = ParticipantData::getPreferredWeeks($participant);
            ParticipantData::setPreferredWeeks($participant, $weeks);
            $this->logIfAdmin($trip, $participant, 'preferred_weeks', $old, $weeks);
            $this->json(['ok' => true, 'savedAt' => date('c')]);
        }

        if (!in_array($key, QuestionLabels::knownKeys(), true)) {
            $this->json(['ok' => false, 'error' => 'Nieznane pytanie.'], 422);
        }

        $error = WizardValidator::validate($key, $value);
        if ($error !== null) {
            $this->json(['ok' => false, 'error' => $error], 422);
        }

        $existing = ParticipantData::getResponses($participant);
        $oldValue = $existing[$key] ?? null;

        ParticipantData::saveResponse($participant, $key, $value);
        $this->logIfAdmin($trip, $participant, $key, $oldValue, $value);

        $this->json(['ok' => true, 'savedAt' => date('c')]);
    }

    public function submit(Request $request, array $args): never
    {
        Csrf::validate();
        [$participant, $trip] = $this->resolve((string) $args['token']);

        if ($this->isAdminEdit()) {
            flash('success', 'Zapisano zmiany w odpowiedziach uczestnika "' . $participant->nickname . '".');
            $this->redirect(url('/admin/trips/' . $trip->id . '/participants'));
        }

        ParticipantData::markCompleted($participant);
        $this->redirect(url('/p/' . $participant->accessToken . '/dziekujemy'));
    }

    public function thanks(Request $request, array $args): never
    {
        [$participant, $trip] = $this->resolve((string) $args['token']);

        $this->render('participant/thanks', [
            'title'       => 'Dzieki!',
            'description' => 'Ankieta wypelniona.',
            'trip'        => $trip,
            'participant' => $participant,
        ], 'wizard');
    }

    private function resolve(string $token): array
    {
        $participant = Participant::findByAccessToken($token);
        if ($participant === null) $this->notFound('Nieznany link.');
        $trip = Trip::findById($participant->tripId);
        if ($trip === null || !$trip->isActive) $this->notFound('Wyjazd niedostepny.');
        return [$participant, $trip];
    }

    private function isAdminEdit(): bool
    {
        return (new AuthService())->isLoggedIn();
    }

    private function logIfAdmin(Trip $trip, Participant $participant, string $field, mixed $old, mixed $new): void
    {
        if (!$this->isAdminEdit()) return;
        $admin = (new AuthService())->currentAdmin();
        if ($admin === null) return;
        AuditService::log($trip->id, $participant->id, $admin->id, $field, $old, $new);
    }
}
