<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Participant;
use App\Models\TripPlace;
use App\Models\TripPlaceVote;

/**
 * Algorytm porownywania kompatybilnosci miedzy uczestnikami.
 *
 * Per para (A, B): liczy similarity score 0-1 jako srednia wazona similarity
 * na poszczegolnych pytaniach ankiety. Trzy typy podobienstwa:
 *  - ordinalSim: pozycja w skali (np. alcohol_attitude: none → full_party)
 *  - jaccardSim: |A∩B| / |A∪B| dla multi-select
 *  - scaleSim: 1 - abs(A-B)/range dla numerycznych
 *  - boolSim: 1 jesli zgodne, else 0
 *
 * Per uczestnik: groupFit = avg pair score do reszty ekipy.
 *
 * Outsider: osoba z najnizszym groupFit. Reasons = top 3 wymiary gdzie najbardziej
 * rozni sie od reszty.
 */
final class CompatibilityService
{
    /** Pytania w skali ordynalnej - kolejnosc OPTIONS od "niskie" do "wysokie". */
    private const ORDINAL_QUESTIONS = [
        'alcohol_attitude'        => ['none', 'wine_with_dinner', 'social', 'likes_drinking', 'full_party'],
        'party_style'             => ['quiet', 'moderate', 'party_hard'],
        'pace'                    => ['chill', 'balanced', 'intensive'],
        'comfort_level'           => ['rough', 'comfortable', 'luxury'],
        'money_attitude'          => ['strict', 'balanced', 'save_food_spend_attractions', 'vacation_mode', 'unlimited'],
        'daily_walking_capacity'  => ['under_3km', '3_7km', '7_15km', '15_25km', 'over_25km'],
        'travel_experience'       => ['first_time', 'europe_some', 'worldwide_some', 'globetrotter'],
        'photo_attitude'          => ['hate_posing', 'souvenir_only', 'casual_sharing', 'influencer_mode'],
        'room_sharing'            => ['private_only', 'no_bed_sharing', 'share_with_friends', 'dormitory_ok'],
        'max_daily_driving_km'    => ['under_200', '200_500', '500_800', 'over_800'],
        'food_style'              => ['self_cooking', 'street_food', 'local_eateries', 'restaurants', 'fine_dining'],
    ];

    /** Pytania multi-select (jaccard). */
    private const MULTI_QUESTIONS = [
        'landscape_preferences', 'climate_tolerance', 'physical_activities',
        'activities', 'trip_expectations', 'accommodation', 'social_preference',
        'dietary_restrictions', 'transport_modes',
    ];

    /** Pytania numeryczne (skala). */
    private const SCALE_QUESTIONS = [
        'budget_range'       => [0, 30000],
        'trip_duration_days' => [2, 30],
        'food_openness'      => [1, 5],
    ];

    /** Pytania boolean. */
    private const BOOL_QUESTIONS = ['has_passport', 'has_driving_license'];

    /** Wagi grupowe - heavy/medium/multi (im wyzsze tym wieksza waga w finalnym score). */
    private const WEIGHTS = [
        // Heavy (deal-breakery)
        'alcohol_attitude' => 3.0, 'party_style' => 3.0, 'pace' => 3.0,
        'comfort_level' => 3.0, 'money_attitude' => 3.0, 'room_sharing' => 3.0,
        // Medium
        'budget_range' => 2.0, 'food_openness' => 2.0, 'food_style' => 2.0,
        'daily_walking_capacity' => 2.0, 'travel_experience' => 2.0,
        'photo_attitude' => 2.0, 'trip_duration_days' => 2.0,
        // Multi (default 1.0 niżej)
    ];

    /** Waga similarity ocen miejsc w finalnym score (reszta = survey). 0.75 = ekipa wpisala
     *  ze konkretne miejsca licza sie bardziej niz abstrakcyjne preferencje ankiety. */
    private const VOTES_WEIGHT = 0.75;

    /** Minimum wspolnych ocen by votes_similarity sie liczylo (inaczej za malo statystyki). */
    private const MIN_SHARED_VOTES = 3;

    /** @var array<int, array<string, mixed>>  */
    private array $responses;

    /** @var list<Participant> */
    private array $participants;

    /**
     * Glosy na miejsca: [place_id => [participant_id => score 1-5]].
     * Pre-loaded raz w konstruktorze, uzywane przez voteSimilarity().
     * @var array<int, array<int, int>>
     */
    private array $votesByPlace;

    /**
     * Pelne dane miejsc z trip (id => TripPlace) - do wyswietlania w breakdownie.
     * @var array<int, TripPlace>
     */
    private array $placesById;

    public function __construct(SummaryAggregator $agg)
    {
        $this->participants = $agg->completedParticipants();
        $this->responses = $agg->allResponses();

        // Pre-load glosow na miejsca - uzywane do votes_similarity
        $tripId = $agg->trip->id;
        $this->votesByPlace = TripPlaceVote::votesByPlaceAndParticipant($tripId);
        $this->placesById = [];
        foreach (TripPlace::listForTrip($tripId) as $place) {
            $this->placesById[$place->id] = $place;
        }
    }

    /**
     * @return list<Participant>
     */
    public function participants(): array
    {
        return $this->participants;
    }

    /** Czy mamy wystarczajaco danych zeby analizowac. */
    public function isAvailable(): bool
    {
        return count($this->participants) >= 3;
    }

    /**
     * Similarity para (A, B). Zwraca float 0-1.
     *
     * Blend: jesli ekipa glosowala na wspolne miejsca >= MIN_SHARED_VOTES, finalny score =
     *   VOTES_WEIGHT * voteSim + (1 - VOTES_WEIGHT) * surveySim
     * Inaczej: 100% surveySim (fallback).
     */
    public function pairScore(Participant $a, Participant $b): float
    {
        $survey = $this->surveyScore($a, $b);
        $vote = $this->voteSimilarity($a, $b);
        if ($vote === null) return $survey;
        return self::VOTES_WEIGHT * $vote['similarity'] + (1.0 - self::VOTES_WEIGHT) * $survey;
    }

    /**
     * Surowy survey-score (bez wpływu ocen miejsc). Dla diagnostyki i fallback.
     */
    private function surveyScore(Participant $a, Participant $b): float
    {
        $ra = $this->responses[$a->id] ?? [];
        $rb = $this->responses[$b->id] ?? [];

        $weightedSum = 0.0;
        $totalWeight = 0.0;

        // Ordinal
        foreach (self::ORDINAL_QUESTIONS as $q => $opts) {
            $sim = $this->ordinalSim($ra[$q] ?? null, $rb[$q] ?? null, $opts);
            if ($sim === null) continue;
            $w = self::WEIGHTS[$q] ?? 1.0;
            $weightedSum += $sim * $w;
            $totalWeight += $w;
        }

        // Multi (jaccard)
        foreach (self::MULTI_QUESTIONS as $q) {
            $sim = $this->jaccardSim($ra[$q] ?? null, $rb[$q] ?? null);
            if ($sim === null) continue;
            $w = self::WEIGHTS[$q] ?? 1.0;
            $weightedSum += $sim * $w;
            $totalWeight += $w;
        }

        // Scale (numeric)
        foreach (self::SCALE_QUESTIONS as $q => [$min, $max]) {
            $sim = $this->scaleSim($ra[$q] ?? null, $rb[$q] ?? null, $min, $max);
            if ($sim === null) continue;
            $w = self::WEIGHTS[$q] ?? 1.0;
            $weightedSum += $sim * $w;
            $totalWeight += $w;
        }

        // Bool
        foreach (self::BOOL_QUESTIONS as $q) {
            $sim = $this->boolSim($ra[$q] ?? null, $rb[$q] ?? null);
            if ($sim === null) continue;
            $w = self::WEIGHTS[$q] ?? 1.0;
            $weightedSum += $sim * $w;
            $totalWeight += $w;
        }

        if ($totalWeight === 0.0) return 0.5; // brak danych - neutral
        return $weightedSum / $totalWeight;
    }

    /**
     * Similarity oparta o oceny WSPÓLNYCH miejsc na mapie.
     * Per wspolne miejsce: 1 - |scoreA - scoreB| / 4 (scoreA,B w 1-5)
     * Agregat: srednia po wspolnych miejscach.
     *
     * @return array{similarity:float, shared_count:int, agreed:list<array{place_id:int,name:string,a_score:int,b_score:int,sim:float}>, disagreed:list<array{place_id:int,name:string,a_score:int,b_score:int,sim:float}>}|null
     *         null jesli wspolnych ocen < MIN_SHARED_VOTES
     */
    public function voteSimilarity(Participant $a, Participant $b): ?array
    {
        $shared = [];
        foreach ($this->votesByPlace as $placeId => $votes) {
            if (!isset($votes[$a->id]) || !isset($votes[$b->id])) continue;
            $scoreA = (int) $votes[$a->id];
            $scoreB = (int) $votes[$b->id];
            $sim = 1.0 - abs($scoreA - $scoreB) / 4.0; // max diff = 4 (5-1)
            $shared[] = [
                'place_id' => $placeId,
                'name'     => $this->placesById[$placeId]->name ?? ('Miejsce #' . $placeId),
                'a_score'  => $scoreA,
                'b_score'  => $scoreB,
                'sim'      => $sim,
            ];
        }

        if (count($shared) < self::MIN_SHARED_VOTES) return null;

        // Aggregate
        $totalSim = array_sum(array_column($shared, 'sim'));
        $avgSim = $totalSim / count($shared);

        // Sort: agreed (high sim) najpierw, disagreed (low sim) na konca
        $sortedDesc = $shared;
        usort($sortedDesc, static fn($x, $y) => $y['sim'] <=> $x['sim']);
        $sortedAsc = $shared;
        usort($sortedAsc, static fn($x, $y) => $x['sim'] <=> $y['sim']);

        return [
            'similarity'   => $avgSim,
            'shared_count' => count($shared),
            'agreed'       => array_slice($sortedDesc, 0, 5),
            'disagreed'    => array_slice($sortedAsc, 0, 5),
        ];
    }

    /**
     * Detaliczny rozklad similarity per pytanie dla pary (A, B).
     * Zwraca liste wymiarow z labelka, wartosciami obu osob i similarity 0-1.
     *
     * @return list<array{key:string, label:string, similarity:float, val_a:string, val_b:string, weight:float}>
     */
    public function pairBreakdown(Participant $a, Participant $b): array
    {
        $ra = $this->responses[$a->id] ?? [];
        $rb = $this->responses[$b->id] ?? [];
        $out = [];

        // Ordinal
        foreach (self::ORDINAL_QUESTIONS as $q => $opts) {
            $sim = $this->ordinalSim($ra[$q] ?? null, $rb[$q] ?? null, $opts);
            if ($sim === null) continue;
            $out[] = [
                'key'        => $q,
                'label'      => $this->questionLabel($q),
                'similarity' => $sim,
                'val_a'      => $this->optionLabel($q, $ra[$q]),
                'val_b'      => $this->optionLabel($q, $rb[$q]),
                'weight'     => self::WEIGHTS[$q] ?? 1.0,
            ];
        }

        // Multi (jaccard)
        foreach (self::MULTI_QUESTIONS as $q) {
            $sim = $this->jaccardSim($ra[$q] ?? null, $rb[$q] ?? null);
            if ($sim === null) continue;
            $out[] = [
                'key'        => $q,
                'label'      => $this->questionLabel($q),
                'similarity' => $sim,
                'val_a'      => $this->arrLabel($q, $ra[$q] ?? []),
                'val_b'      => $this->arrLabel($q, $rb[$q] ?? []),
                'weight'     => self::WEIGHTS[$q] ?? 1.0,
            ];
        }

        // Scale
        foreach (self::SCALE_QUESTIONS as $q => [$min, $max]) {
            $sim = $this->scaleSim($ra[$q] ?? null, $rb[$q] ?? null, $min, $max);
            if ($sim === null) continue;
            $valA = $ra[$q]; $valB = $rb[$q];
            // Sformatuj numerycznie - budget z zł, duration z dni
            $unit = $q === 'budget_range' ? ' zł' : ($q === 'trip_duration_days' ? ' dni' : '');
            $out[] = [
                'key'        => $q,
                'label'      => $this->questionLabel($q),
                'similarity' => $sim,
                'val_a'      => number_format((int) $valA, 0, ',', ' ') . $unit,
                'val_b'      => number_format((int) $valB, 0, ',', ' ') . $unit,
                'weight'     => self::WEIGHTS[$q] ?? 1.0,
            ];
        }

        // Bool
        foreach (self::BOOL_QUESTIONS as $q) {
            $sim = $this->boolSim($ra[$q] ?? null, $rb[$q] ?? null);
            if ($sim === null) continue;
            $boolLbl = static function ($v) {
                $on = ($v === true || $v === 'true' || $v === 1 || $v === '1');
                return $on ? 'Tak' : 'Nie';
            };
            $out[] = [
                'key'        => $q,
                'label'      => $this->questionLabel($q),
                'similarity' => $sim,
                'val_a'      => $boolLbl($ra[$q]),
                'val_b'      => $boolLbl($rb[$q]),
                'weight'     => self::WEIGHTS[$q] ?? 1.0,
            ];
        }

        return $out;
    }

    /**
     * Macierz [participant_id => [participant_id => score]]
     * @return array<int, array<int, float>>
     */
    public function matrix(): array
    {
        $m = [];
        foreach ($this->participants as $a) {
            foreach ($this->participants as $b) {
                if ($a->id === $b->id) {
                    $m[$a->id][$b->id] = 1.0;
                    continue;
                }
                if (isset($m[$b->id][$a->id])) {
                    $m[$a->id][$b->id] = $m[$b->id][$a->id]; // symetria
                    continue;
                }
                $m[$a->id][$b->id] = $this->pairScore($a, $b);
            }
        }
        return $m;
    }

    /**
     * Top N najbardziej kompatybilnych par. Wynik posortowany malejaco po score.
     * @return list<array{a:Participant, b:Participant, score:float}>
     */
    public function topPairs(int $n = 3): array
    {
        return array_slice($this->allPairs(SORT_DESC), 0, $n);
    }

    /**
     * Najgorsze duety - posortowane rosnaco po score.
     * @return list<array{a:Participant, b:Participant, score:float}>
     */
    public function bottomPairs(int $n = 3): array
    {
        return array_slice($this->allPairs(SORT_ASC), 0, $n);
    }

    /**
     * @return list<array{a:Participant, b:Participant, score:float}>
     */
    private function allPairs(int $sortDir): array
    {
        $pairs = [];
        $count = count($this->participants);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $this->participants[$i];
                $b = $this->participants[$j];
                $pairs[] = ['a' => $a, 'b' => $b, 'score' => $this->pairScore($a, $b)];
            }
        }
        usort($pairs, static fn($x, $y) => $sortDir === SORT_DESC ? $y['score'] <=> $x['score'] : $x['score'] <=> $y['score']);
        return $pairs;
    }

    /**
     * Ranking uczestnikow po srednim "group fit" (avg pair score do reszty).
     * Posortowany malejaco (najbardziej pasujacy na czele).
     * @return list<array{participant:Participant, fit:float}>
     */
    public function ranking(): array
    {
        $matrix = $this->matrix();
        $rows = [];
        foreach ($this->participants as $p) {
            $sum = 0.0;
            $count = 0;
            foreach ($matrix[$p->id] ?? [] as $otherId => $score) {
                if ($otherId === $p->id) continue;
                $sum += $score;
                $count++;
            }
            $rows[] = [
                'participant' => $p,
                'fit'         => $count > 0 ? $sum / $count : 0.0,
            ];
        }
        usort($rows, static fn($a, $b) => $b['fit'] <=> $a['fit']);
        return $rows;
    }

    /**
     * Outsider = osoba z najnizszym group fit + powody (top 3 wymiary gdzie sie rozni).
     *
     * @return array{participant:Participant, fit:float, reasons:list<array{label:string, own:string, group:string}>}|null
     */
    public function outsider(): ?array
    {
        $ranking = $this->ranking();
        if (count($ranking) < 3) return null;
        $last = end($ranking);
        return [
            'participant' => $last['participant'],
            'fit'         => $last['fit'],
            'reasons'     => $this->outsiderReasons($last['participant']),
        ];
    }

    /**
     * Top 3 wymiary gdzie outsider najbardziej rozni sie od grupy.
     * Zwraca human-readable labelki dla UI.
     *
     * Typy roznic:
     *  - ordinal: wartosc outsidera vs mediana grupy (np. "3-7 km vs 25+ km")
     *  - multi:   trzy moga byc rozne sytuacje:
     *      (a) "Tylko outsider": ma cos czego reszta nie wybrala
     *      (b) "Brakuje outsiderowi": reszta wybrala cos czego outsider nie ma
     *      (c) Mix obu - pokazujemy oba kierunki
     *
     * @return list<array{label:string, type:string, own:string, group:string}>
     *   type = 'ordinal' | 'only_outsider' | 'only_group' | 'mixed'
     */
    private function outsiderReasons(Participant $out): array
    {
        $own = $this->responses[$out->id] ?? [];
        $others = array_values(array_filter($this->participants, static fn($p) => $p->id !== $out->id));
        $diffs = [];

        // Ordinal - sprawdz roznice w pozycji
        foreach (self::ORDINAL_QUESTIONS as $q => $opts) {
            $ownVal = $own[$q] ?? null;
            $ownIdx = $ownVal === null ? null : array_search($ownVal, $opts, true);
            if ($ownIdx === false) $ownIdx = null;
            if ($ownIdx === null) continue;

            $otherIdxes = [];
            foreach ($others as $o) {
                $v = $this->responses[$o->id][$q] ?? null;
                $idx = $v === null ? null : array_search($v, $opts, true);
                if ($idx !== false && $idx !== null) $otherIdxes[] = $idx;
            }
            if (empty($otherIdxes)) continue;
            sort($otherIdxes);
            $median = $otherIdxes[(int) floor(count($otherIdxes) / 2)];
            $dist = abs($ownIdx - $median);
            if ($dist === 0) continue;

            $diffs[] = [
                'distance' => $dist / max(1, count($opts) - 1),
                'label'    => $this->questionLabel($q),
                'type'     => 'ordinal',
                'own'      => $this->optionLabel($q, $ownVal),
                'group'    => $this->optionLabel($q, $opts[$median]),
            ];
        }

        // Multi - prawdziwa roznica miedzy outsiderem a grupa
        foreach (self::MULTI_QUESTIONS as $q) {
            $ownArr = $own[$q] ?? [];
            if (!is_array($ownArr)) $ownArr = [];

            // Najczesciej wybierane opcje w grupie (>= 50% wybralo)
            $tally = [];
            foreach ($others as $o) {
                $arr = $this->responses[$o->id][$q] ?? [];
                if (!is_array($arr)) continue;
                foreach (array_unique($arr) as $opt) {
                    $tally[$opt] = ($tally[$opt] ?? 0) + 1;
                }
            }
            if (empty($tally)) continue;

            $threshold = (int) ceil(count($others) / 2);
            $groupCommon = array_keys(array_filter($tally, static fn($n) => $n >= $threshold));

            // CZYSTA roznica:
            //  - groupOnly: rzeczy w common dla grupy, ktorych outsider NIE ma
            //  - outsiderOnly: rzeczy ktore tylko outsider wybral (nikt z grupy lub mniej niz 30%)
            $groupOnly = array_values(array_diff($groupCommon, $ownArr));

            $rareThreshold = max(1, (int) floor(count($others) * 0.30));
            $outsiderOnly = [];
            foreach ($ownArr as $opt) {
                $cnt = $tally[$opt] ?? 0;
                if ($cnt <= $rareThreshold && !in_array($opt, $groupCommon, true)) {
                    $outsiderOnly[] = $opt;
                }
            }

            // Brak prawdziwej roznicy - pomijamy
            if (empty($groupOnly) && empty($outsiderOnly)) continue;

            // Jaccard total dla scoringu (do sortowania)
            $unionAll = array_unique(array_merge($ownArr, array_keys($tally)));
            $intersect = array_intersect($ownArr, array_keys($tally));
            $jaccardDist = empty($unionAll) ? 0 : 1.0 - (count($intersect) / count($unionAll));

            // Klasyfikacja
            if (!empty($groupOnly) && !empty($outsiderOnly)) {
                $type = 'mixed';
                $ownLabel = $this->arrLabel($q, $outsiderOnly);
                $grpLabel = $this->arrLabel($q, $groupOnly);
            } elseif (!empty($groupOnly)) {
                $type = 'only_group';   // outsider nie ma czegos co grupa lubi
                $ownLabel = '—';
                $grpLabel = $this->arrLabel($q, $groupOnly);
            } else {
                $type = 'only_outsider'; // outsider ma cos egzotycznego
                $ownLabel = $this->arrLabel($q, $outsiderOnly);
                $grpLabel = '—';
            }

            // Ignoruj male roznice (< 1 element diff w sumie)
            if (count($groupOnly) + count($outsiderOnly) < 1) continue;

            $diffs[] = [
                'distance' => max($jaccardDist, 0.30 + count($groupOnly) * 0.10 + count($outsiderOnly) * 0.10),
                'label'    => $this->questionLabel($q),
                'type'     => $type,
                'own'      => $ownLabel,
                'group'    => $grpLabel,
            ];
        }

        usort($diffs, static fn($a, $b) => $b['distance'] <=> $a['distance']);
        $top = array_slice($diffs, 0, 4);
        return array_map(static fn($d) => [
            'label' => $d['label'],
            'type'  => $d['type'],
            'own'   => $d['own'],
            'group' => $d['group'],
        ], $top);
    }

    // ============================================================
    // Similarity helpers
    // ============================================================

    /** @param list<int|string> $opts */
    private function ordinalSim($a, $b, array $opts): ?float
    {
        if ($a === null || $b === null) return null;
        $ia = array_search($a, $opts, true);
        $ib = array_search($b, $opts, true);
        if ($ia === false || $ib === false) return null;
        $n = count($opts) - 1;
        return $n > 0 ? 1.0 - abs($ia - $ib) / $n : 1.0;
    }

    private function jaccardSim($a, $b): ?float
    {
        if (!is_array($a) || !is_array($b)) return null;
        if (empty($a) && empty($b)) return null;
        $setA = array_unique($a);
        $setB = array_unique($b);
        $intersect = array_intersect($setA, $setB);
        $union = array_unique(array_merge($setA, $setB));
        return count($union) > 0 ? count($intersect) / count($union) : null;
    }

    private function scaleSim($a, $b, $min, $max): ?float
    {
        if (!is_numeric($a) || !is_numeric($b)) return null;
        $range = $max - $min;
        if ($range <= 0) return null;
        return 1.0 - min(1.0, abs($a - $b) / $range);
    }

    private function boolSim($a, $b): ?float
    {
        if ($a === null || $b === null) return null;
        $na = ($a === true || $a === 'true' || $a === 1 || $a === '1');
        $nb = ($b === true || $b === 'true' || $b === 1 || $b === '1');
        return $na === $nb ? 1.0 : 0.0;
    }

    // ============================================================
    // Human-readable labels
    // ============================================================

    private function questionLabel(string $key): string
    {
        return [
            'alcohol_attitude'        => 'Alkohol',
            'party_style'             => 'Imprezy',
            'pace'                    => 'Tempo',
            'comfort_level'           => 'Komfort',
            'money_attitude'          => 'Pieniądze',
            'room_sharing'            => 'Pokój',
            'budget_range'            => 'Budżet',
            'food_openness'           => 'Otwartość kulinarna',
            'food_style'              => 'Styl jedzenia',
            'daily_walking_capacity'  => 'Chodzenie',
            'travel_experience'       => 'Doświadczenie',
            'photo_attitude'          => 'Zdjęcia',
            'trip_duration_days'      => 'Długość wyjazdu',
            'landscape_preferences'   => 'Krajobraz',
            'climate_tolerance'       => 'Klimat',
            'physical_activities'     => 'Sport',
            'activities'              => 'Aktywności',
            'trip_expectations'       => 'Oczekiwania',
            'accommodation'           => 'Nocleg',
            'social_preference'       => 'Z kim',
            'dietary_restrictions'    => 'Dieta',
            'transport_modes'         => 'Transport',
        ][$key] ?? $key;
    }

    private function optionLabel(string $key, $value): string
    {
        $labels = [
            'alcohol_attitude' => [
                'none' => 'zero', 'wine_with_dinner' => 'lampka do kolacji',
                'social' => 'piwko społecznie', 'likes_drinking' => 'lubi się napić',
                'full_party' => 'pełna impreza',
            ],
            'party_style' => ['party_hard' => 'na full', 'moderate' => 'umiarkowanie', 'quiet' => 'spokojnie'],
            'pace' => ['chill' => 'chill', 'balanced' => 'zbalansowane', 'intensive' => 'intensywne'],
            'comfort_level' => ['luxury' => 'luksus', 'comfortable' => 'komfortowo', 'rough' => 'byle gdzie'],
            'money_attitude' => [
                'strict' => 'oszczędnie', 'balanced' => 'rozsądnie',
                'save_food_spend_attractions' => 'na atrakcje', 'vacation_mode' => 'wakacje, wydaję',
                'unlimited' => 'bez limitu',
            ],
            'daily_walking_capacity' => [
                'under_3km' => 'do 3 km', '3_7km' => '3-7 km', '7_15km' => '7-15 km',
                '15_25km' => '15-25 km', 'over_25km' => '25+ km',
            ],
            'travel_experience' => [
                'first_time' => 'pierwszy raz', 'europe_some' => 'trochę Europy',
                'worldwide_some' => 'też dalej', 'globetrotter' => 'globtrotter',
            ],
            'photo_attitude' => [
                'hate_posing' => 'nie cierpi pozowania', 'souvenir_only' => 'tylko pamiątkowe',
                'casual_sharing' => 'czasem wrzuca', 'influencer_mode' => 'influencer',
            ],
            'room_sharing' => [
                'private_only' => 'osobny pokój', 'no_bed_sharing' => 'pokój tak, łóżko nie',
                'share_with_friends' => 'pokój z ekipą', 'dormitory_ok' => 'dormitorium OK',
            ],
            'food_style' => [
                'street_food' => 'street food', 'local_eateries' => 'lokalne knajpy',
                'restaurants' => 'restauracje', 'fine_dining' => 'fine dining',
                'self_cooking' => 'sam gotuje',
            ],
        ];
        return $labels[$key][$value] ?? (string) $value;
    }

    /** @param list<string> $values */
    private function arrLabel(string $key, array $values): string
    {
        if (empty($values)) return '—';
        $human = array_map(fn($v) => $this->optionLabel($key, $v), array_slice($values, 0, 3));
        $rest = count($values) - 3;
        return implode(', ', $human) . ($rest > 0 ? " (+{$rest})" : '');
    }
}
