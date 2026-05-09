<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\MapPin;
use App\Models\Participant;
use App\Models\Trip;

/**
 * Zbiera dane wszystkich uczestnikow wyjazdu w formacie gotowym do widoku podsumowania.
 * Cache wewnetrzny - kazdy getter wykonuje query raz.
 */
final class SummaryAggregator
{
    /** @var list<Participant>|null */
    private ?array $participants = null;

    /** @var array<int, array<string, mixed>>|null */
    private ?array $responsesCache = null;

    /** @var array<int, list<string>>|null */
    private ?array $unavailableCache = null;

    /** @var array<int, array<string, string>>|null */
    private ?array $weeksCache = null;

    /** @var list<MapPin>|null */
    private ?array $pinsCache = null;

    public function __construct(public readonly Trip $trip)
    {
    }

    /**
     * @return list<Participant>
     */
    public function participants(): array
    {
        if ($this->participants !== null) return $this->participants;
        return $this->participants = Participant::listForTrip($this->trip->id);
    }

    /**
     * Tylko uczestnicy ktorzy wypelnili ankiete.
     * @return list<Participant>
     */
    public function completedParticipants(): array
    {
        return array_values(array_filter($this->participants(), static fn(Participant $p) => $p->isCompleted()));
    }

    public function totalCount(): int
    {
        return count($this->participants());
    }

    public function completedCount(): int
    {
        return count($this->completedParticipants());
    }

    /**
     * Mapa: participant_id => responses (asoc-array klucz => wartosc).
     * @return array<int, array<string, mixed>>
     */
    public function allResponses(): array
    {
        if ($this->responsesCache !== null) return $this->responsesCache;
        $out = [];
        foreach ($this->participants() as $p) {
            $out[$p->id] = ParticipantData::getResponses($p);
        }
        return $this->responsesCache = $out;
    }

    /**
     * Lista wartosci dla danego klucza pytania (tylko od uczestnikow ktorzy odpowiedzieli).
     *
     * @return list<mixed>
     */
    public function valuesFor(string $questionKey): array
    {
        $values = [];
        foreach ($this->allResponses() as $resp) {
            if (array_key_exists($questionKey, $resp) && $resp[$questionKey] !== null && $resp[$questionKey] !== '') {
                $values[] = $resp[$questionKey];
            }
        }
        return $values;
    }

    /**
     * Mapa: participant_id => list<date YYYY-MM-DD> niedostepnych.
     * @return array<int, list<string>>
     */
    public function unavailableDates(): array
    {
        if ($this->unavailableCache !== null) return $this->unavailableCache;
        $out = [];
        foreach ($this->participants() as $p) {
            $out[$p->id] = ParticipantData::getUnavailableDates($p);
        }
        return $this->unavailableCache = $out;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function preferredWeeks(): array
    {
        if ($this->weeksCache !== null) return $this->weeksCache;
        $out = [];
        foreach ($this->participants() as $p) {
            $out[$p->id] = ParticipantData::getPreferredWeeks($p);
        }
        return $this->weeksCache = $out;
    }

    /**
     * @return list<MapPin>
     */
    public function mapPins(): array
    {
        if ($this->pinsCache !== null) return $this->pinsCache;
        return $this->pinsCache = MapPin::listForTrip($this->trip->id);
    }

    /**
     * Mapa: participant_id => kolor (hex).
     * @return array<int, string>
     */
    public function colorMap(): array
    {
        $out = [];
        foreach ($this->participants() as $p) {
            $out[$p->id] = MapColorService::forParticipant($p);
        }
        return $out;
    }

    /**
     * Czy wyjazd ma tryb anonimowy w podsumowaniu (bez ksywek).
     */
    public function isAnonymous(): bool
    {
        return !$this->trip->showIndividualResponses;
    }
}
