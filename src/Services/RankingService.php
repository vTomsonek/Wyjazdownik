<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Participant;

/**
 * Algorytmy odznak - dla kazdej odznaki wybiera zwyciezce(ow) z ekipy.
 *
 * Kazda metoda score* zwraca float - im wyzej tym lepszy kandydat.
 * Score 0 = nie kwalifikuje sie. Top score (z tolerancja) wygrywa.
 *
 * Wzorzec uzycia:
 *   $service = new RankingService($aggregator);
 *   $awards = $service->awardAll();
 *   // $awards['kebab_master'] = ['badge' => [...], 'winners' => [Participant, ...]]
 */
final class RankingService
{
    public function __construct(private readonly SummaryAggregator $agg) {}

    /**
     * @return list<array{id:string, icon:string, name:string, description:string, winners:list<Participant>}>
     */
    public function awardAll(): array
    {
        $defs = $this->definitions();
        $participants = $this->agg->completedParticipants();
        if (empty($participants)) return [];

        $responses = $this->agg->allResponses();
        $unavailMap = $this->agg->unavailableDates();
        $pinsAll    = $this->agg->mapPins();

        // Ile pinezek per uczestnik
        $pinsCount = [];
        foreach ($pinsAll as $pin) {
            $pinsCount[$pin->participantId] = ($pinsCount[$pin->participantId] ?? 0) + 1;
        }

        $out = [];
        foreach ($defs as $badgeId => $badge) {
            $scores = [];
            foreach ($participants as $p) {
                $resp = $responses[$p->id] ?? [];
                $unav = count($unavailMap[$p->id] ?? []);
                $pins = $pinsCount[$p->id] ?? 0;
                $score = ($badge['score'])($p, $resp, $unav, $pins);
                if ($score > 0) $scores[$p->id] = $score;
            }
            if (empty($scores)) continue;
            $maxScore = max($scores);
            // Tolerancja - ex-aequo gdy score == maxScore (z eps dla float)
            $winnerIds = array_keys(array_filter($scores, static fn($s) => abs($s - $maxScore) < 0.0001));
            $winners = array_values(array_filter($participants, static fn(Participant $p) => in_array($p->id, $winnerIds, true)));
            if (empty($winners)) continue;
            $out[] = [
                'id'          => $badgeId,
                'icon'        => $badge['icon'],
                'name'        => $badge['name'],
                'description' => $badge['description'],
                'winners'     => $winners,
            ];
        }
        return $out;
    }

    /**
     * @return array<string, array{icon:string, name:string, description:string, score:callable}>
     */
    private function definitions(): array
    {
        return [
            'kebab_master' => [
                'icon' => '🥙', 'name' => 'Kebab Master',
                'description' => 'Marzy o kebabach na wyjeździe.',
                'score' => static function ($p, $r): float {
                    $exp = $r['trip_expectations'] ?? [];
                    if (!is_array($exp) || !in_array('kebab', $exp, true)) return 0;
                    $bonus = 0;
                    if (($r['food_openness'] ?? null) == 5)        $bonus++;
                    if (($r['food_style'] ?? null) === 'street_food') $bonus++;
                    return 10 + $bonus;
                },
            ],
            'imprezowicz' => [
                'icon' => '🍻', 'name' => 'Imprezowicz',
                'description' => 'Pełna chata, full party mode.',
                'score' => static function ($p, $r): float {
                    $a = $r['alcohol_attitude'] ?? '';
                    $ps = $r['party_style'] ?? '';
                    $score = 0;
                    if ($a === 'full_party')         $score += 5;
                    elseif ($a === 'likes_drinking') $score += 2;
                    if ($ps === 'party_hard')        $score += 5;
                    return $score;
                },
            ],
            'trzezwy_duch' => [
                'icon' => '🚫', 'name' => 'Trzeźwy Duch',
                'description' => 'Bez kropli alkoholu.',
                'score' => static fn($p, $r): float => ($r['alcohol_attitude'] ?? '') === 'none' ? 10 : 0,
            ],
            'plazowicz' => [
                'icon' => '🏖️', 'name' => 'Plażowicz',
                'description' => 'Morze, wyspy, plaża.',
                'score' => static function ($p, $r): float {
                    $land = $r['landscape_preferences'] ?? [];
                    $act  = $r['activities'] ?? [];
                    $s = 0;
                    if (is_array($land)) {
                        if (in_array('sea', $land, true))     $s += 3;
                        if (in_array('islands', $land, true)) $s += 2;
                    }
                    if (is_array($act) && in_array('beach', $act, true)) $s += 3;
                    return $s;
                },
            ],
            'gorski_wilk' => [
                'icon' => '⛰️', 'name' => 'Górski Wilk',
                'description' => 'Pireneje, Tatry, dowolny szczyt.',
                'score' => static function ($p, $r): float {
                    $land = $r['landscape_preferences'] ?? [];
                    $act  = $r['activities'] ?? [];
                    $phys = $r['physical_activities'] ?? [];
                    $s = 0;
                    if (is_array($land) && in_array('mountains', $land, true)) $s += 3;
                    if (is_array($act)  && in_array('hiking', $act, true))     $s += 2;
                    if (is_array($phys) && in_array('hiking', $phys, true))    $s += 2;
                    if (is_array($phys) && in_array('climbing', $phys, true))  $s += 2;
                    return $s;
                },
            ],
            'rybka' => [
                'icon' => '🏊', 'name' => 'Rybka',
                'description' => 'W wodzie się rodzi, w wodzie umiera.',
                'score' => static function ($p, $r): float {
                    $exp  = $r['trip_expectations'] ?? [];
                    $land = $r['landscape_preferences'] ?? [];
                    $phys = $r['physical_activities'] ?? [];
                    $s = 0;
                    if (is_array($exp)  && in_array('swimming', $exp, true)) $s += 3;
                    if (is_array($phys) && in_array('swimming', $phys, true)) $s += 2;
                    if (is_array($land) && (in_array('sea', $land, true) || in_array('lakes', $land, true))) $s += 2;
                    return $s;
                },
            ],
            'maszyna' => [
                'icon' => '🥾', 'name' => 'Maszyna',
                'description' => '25+ km dziennie i ani odrobiny zmęczenia.',
                'score' => static function ($p, $r): float {
                    $w = $r['daily_walking_capacity'] ?? '';
                    $s = match ($w) { 'over_25km' => 10, '15_25km' => 5, default => 0 };
                    if (is_array($r['physical_activities'] ?? null) && count($r['physical_activities']) >= 4) $s += 2;
                    return (float) $s;
                },
            ],
            'leniwiec' => [
                'icon' => '🛋️', 'name' => 'Leniwiec',
                'description' => 'Leżenie, regeneracja, koniec dyskusji.',
                'score' => static function ($p, $r): float {
                    $s = 0;
                    if (($r['daily_walking_capacity'] ?? '') === 'under_3km') $s += 4;
                    if (($r['pace'] ?? '') === 'chill') $s += 3;
                    $exp = $r['trip_expectations'] ?? [];
                    if (is_array($exp) && in_array('rest', $exp, true)) $s += 3;
                    return $s;
                },
            ],
            'foodie' => [
                'icon' => '🍔', 'name' => 'Foodie',
                'description' => 'Restauracje, fine dining, kuchnia świata.',
                'score' => static function ($p, $r): float {
                    $s = 0;
                    if (($r['food_style'] ?? '') === 'fine_dining')   $s += 4;
                    if (($r['food_style'] ?? '') === 'restaurants')   $s += 2;
                    if (((int) ($r['food_openness'] ?? 0)) === 5)     $s += 3;
                    return $s;
                },
            ],
            'awanturnik_kulinarny' => [
                'icon' => '🌶️', 'name' => 'Awanturnik kulinarny',
                'description' => 'Owady, flaki, fermenty - wszystko spróbuję.',
                'score' => static fn($p, $r): float => ((int) ($r['food_openness'] ?? 0)) === 5 ? 10 : 0,
            ],
            'wybredny' => [
                'icon' => '🦷', 'name' => 'Wybredny',
                'description' => 'Tylko sprawdzone smaki.',
                'score' => static function ($p, $r): float {
                    $s = ((int) ($r['food_openness'] ?? 5)) === 1 ? 5 : 0;
                    $diet = $r['dietary_restrictions'] ?? [];
                    if (is_array($diet)) {
                        $real = array_values(array_filter($diet, static fn($d) => $d !== 'none'));
                        $s += min(5, count($real));
                    }
                    return $s;
                },
            ],
            'najbardziej_wymagajacy' => [
                'icon' => '🏆', 'name' => 'Najbardziej Wymagający',
                'description' => 'Luksus, osobny pokój, ciepło.',
                'score' => static function ($p, $r): float {
                    $s = 0;
                    if (($r['comfort_level'] ?? '') === 'luxury')       $s += 4;
                    if (($r['room_sharing'] ?? '') === 'private_only')  $s += 3;
                    $clim = $r['climate_tolerance'] ?? [];
                    if (is_array($clim) && count($clim) === 1)          $s += 2;
                    return $s;
                },
            ],
            'mr_luzak' => [
                'icon' => '😎', 'name' => 'Mr/Mrs Luzak',
                'description' => 'Wszystko mu pasuje, nigdy nie marudzi.',
                'score' => static function ($p, $r): float {
                    $s = 0;
                    if (($r['comfort_level'] ?? '') === 'rough')   $s += 3;
                    $accom = $r['accommodation'] ?? [];
                    if (is_array($accom) && in_array('any', $accom, true)) $s += 3;
                    if (($r['pace'] ?? '') === 'chill')             $s += 1;
                    return $s;
                },
            ],
            'krezus' => [
                'icon' => '💰', 'name' => 'Krezus',
                'description' => 'Najwyższy budżet w ekipie.',
                'score' => static fn($p, $r): float => is_numeric($r['budget_range'] ?? null) ? (float) $r['budget_range'] : 0,
            ],
            'backpacker' => [
                'icon' => '🎒', 'name' => 'Backpacker',
                'description' => 'Plecak, hostel, namiot - dam radę spać byle gdzie.',
                'score' => static function ($p, $r): float {
                    $s = 0;
                    $b = is_numeric($r['budget_range'] ?? null) ? (float) $r['budget_range'] : 99999;
                    if ($b < 2500)                                  $s += 3;
                    if (($r['comfort_level'] ?? '') === 'rough')    $s += 3;
                    $accom = $r['accommodation'] ?? [];
                    if (is_array($accom) && (in_array('hostel', $accom, true) || in_array('camping', $accom, true) || in_array('tent', $accom, true))) $s += 2;
                    return $s;
                },
            ],
            'wiecznie_zajety' => [
                'icon' => '📅', 'name' => 'Wiecznie Zajęty',
                'description' => 'Najmniej dostępnych dni w oknie wyjazdu.',
                'score' => static fn($p, $r, $unav): float => (float) $unav,
            ],
            'odkrywca' => [
                'icon' => '🗺️', 'name' => 'Odkrywca',
                'description' => 'Najwięcej pinezek, tras i obszarów na mapie.',
                'score' => static fn($p, $r, $unav, $pins): float => (float) $pins,
            ],
            'globtrotter' => [
                'icon' => '✈️', 'name' => 'Globtrotter',
                'description' => 'Paszport + doświadczenie podróżnicze.',
                'score' => static function ($p, $r): float {
                    $s = 0;
                    if (($r['has_passport'] ?? null) === 'true' || $r['has_passport'] === true) $s += 2;
                    if (($r['travel_experience'] ?? '') === 'globetrotter')  $s += 5;
                    elseif (($r['travel_experience'] ?? '') === 'worldwide_some') $s += 2;
                    return $s;
                },
            ],
            'influencer' => [
                'icon' => '📸', 'name' => 'Influencer',
                'description' => 'Sesje, drony, content na social media.',
                'score' => static function ($p, $r): float {
                    $s = 0;
                    if (($r['photo_attitude'] ?? '') === 'influencer_mode')   $s += 5;
                    $exp = $r['trip_expectations'] ?? [];
                    if (is_array($exp) && in_array('content_creation', $exp, true)) $s += 3;
                    return $s;
                },
            ],
            'mobilny' => [
                'icon' => '🚗', 'name' => 'Mobilny',
                'description' => 'Prawo jazdy + udostępnia auto.',
                'score' => static function ($p, $r): float {
                    $s = 0;
                    if (($r['has_driving_license'] ?? null) === 'true' || $r['has_driving_license'] === true) $s += 3;
                    $share = $r['can_share_car'] ?? '';
                    if ($share === 'yes')   $s += 4;
                    elseif ($share === 'maybe') $s += 1;
                    return $s;
                },
            ],
            'spokojny_duch' => [
                'icon' => '🧘', 'name' => 'Spokojny Duch',
                'description' => 'Bez imprez, bez pośpiechu, regeneracja.',
                'score' => static function ($p, $r): float {
                    $s = 0;
                    if (($r['party_style'] ?? '') === 'quiet') $s += 3;
                    if (($r['pace'] ?? '') === 'chill')        $s += 2;
                    if (($r['alcohol_attitude'] ?? '') === 'none' || ($r['alcohol_attitude'] ?? '') === 'wine_with_dinner') $s += 1;
                    return $s;
                },
            ],
            'poliglota' => [
                'icon' => '🌐', 'name' => 'Poliglota',
                'description' => '3+ języki na poziomie biegłym.',
                'score' => static function ($p, $r): float {
                    $langs = $r['languages'] ?? [];
                    if (!is_array($langs)) return 0;
                    $fluent = 0;
                    foreach ($langs as $level) {
                        if ($level === 'fluent') $fluent++;
                    }
                    return $fluent >= 3 ? (float) $fluent : 0;
                },
            ],
        ];
    }
}
