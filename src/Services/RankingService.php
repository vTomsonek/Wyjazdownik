<?php
declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;
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

        // Pre-compute danych z platformy: miejsca dodane + glosy oddane per uczestnik
        $extraData = $this->computePlatformActivity($this->agg->trip->id);

        $out = [];
        foreach ($defs as $badgeId => $badge) {
            $scores = [];
            foreach ($participants as $p) {
                $resp = $responses[$p->id] ?? [];
                $unav = count($unavailMap[$p->id] ?? []);
                $pins = $pinsCount[$p->id] ?? 0;
                $extra = [
                    'places_added' => $extraData['places_per_participant'][$p->id] ?? 0,
                    'votes_count'  => $extraData['votes_count_per_participant'][$p->id] ?? 0,
                    'votes_avg'    => $extraData['votes_avg_per_participant'][$p->id] ?? null,
                    'votes_total'  => $extraData['total_places'],
                ];
                $score = ($badge['score'])($p, $resp, $unav, $pins, $extra);
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
     * Pobiera dane z platformy potrzebne odznakom "z aktywnosci":
     * - places_per_participant: ile miejsc dodal kazdy uczestnik
     * - votes_count_per_participant: ile glosow oddal
     * - votes_avg_per_participant: srednia ocen ktore wystawia (1-5)
     * - total_places: ile lacznie miejsc w tripcie (do liczenia 100% pokrycia)
     *
     * @return array{
     *   places_per_participant: array<int,int>,
     *   votes_count_per_participant: array<int,int>,
     *   votes_avg_per_participant: array<int,float>,
     *   total_places: int
     * }
     */
    private function computePlatformActivity(int $tripId): array
    {
        $pdo = Connection::get();

        // Miejsca dodane
        $stmt = $pdo->prepare(
            'SELECT participant_id, COUNT(*) AS cnt
             FROM trip_places
             WHERE trip_id = :tid
             GROUP BY participant_id'
        );
        $stmt->execute(['tid' => $tripId]);
        $places = [];
        foreach ($stmt->fetchAll() as $row) {
            $places[(int) $row['participant_id']] = (int) $row['cnt'];
        }

        // Glosy + srednia per uczestnik
        $stmt = $pdo->prepare(
            'SELECT v.participant_id, COUNT(*) AS cnt, AVG(v.score) AS avg_score
             FROM trip_place_votes v
             JOIN trip_places p ON p.id = v.place_id
             WHERE p.trip_id = :tid
             GROUP BY v.participant_id'
        );
        $stmt->execute(['tid' => $tripId]);
        $votesCount = [];
        $votesAvg   = [];
        foreach ($stmt->fetchAll() as $row) {
            $pid = (int) $row['participant_id'];
            $votesCount[$pid] = (int) $row['cnt'];
            $votesAvg[$pid]   = (float) $row['avg_score'];
        }

        // Total miejsc w tripcie
        $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM trip_places WHERE trip_id = :tid');
        $stmt->execute(['tid' => $tripId]);
        $totalPlaces = (int) ($stmt->fetch()['cnt'] ?? 0);

        return [
            'places_per_participant'      => $places,
            'votes_count_per_participant' => $votesCount,
            'votes_avg_per_participant'   => $votesAvg,
            'total_places'                => $totalPlaces,
        ];
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
                    // GATE: prawdziwy leniwiec nie chodzi 15+ km dziennie ani nie ma 4+ sportow
                    // (zapobiega koliziom z Maszyna / Sportowiec)
                    $w = $r['daily_walking_capacity'] ?? '';
                    if ($w === 'over_25km' || $w === '15_25km') return 0;
                    $phys = $r['physical_activities'] ?? [];
                    if (is_array($phys)) {
                        $active = array_values(array_filter($phys, static fn($a) => $a !== 'none_relax'));
                        if (count($active) >= 4) return 0;
                    }
                    $s = 0;
                    if ($w === 'under_3km') $s += 4;
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
                'description' => 'Najwięcej miejsc dodanych do mapy ekipy.',
                // Uzywa nowego systemu trip_places (ETAP 5 ranking). Stary participant_map_pins
                // byl zastapiony - tu liczymy realne miejsca z ocen, nie historyczne pinezki.
                'score' => static function ($p, $r, $unav, $pins, $extra): float {
                    return (float) ($extra['places_added'] ?? 0);
                },
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

            // ============================================================
            // NOWE odznaki - rozszerzenie palety z dodatkowych pol ankiety
            // ============================================================

            'wegetarianin' => [
                'icon' => '🌱', 'name' => 'Wegetarianin',
                'description' => 'Mięsne dania omija szerokim łukiem.',
                'score' => static function ($p, $r): float {
                    $diet = $r['dietary_restrictions'] ?? [];
                    if (!is_array($diet)) return 0;
                    if (in_array('vegan', $diet, true))      return 10;
                    if (in_array('vegetarian', $diet, true)) return 8;
                    return 0;
                },
            ],
            'alergik' => [
                'icon' => '🤧', 'name' => 'Alergik',
                'description' => 'Trzeba sprawdzić skład każdej potrawy.',
                'score' => static function ($p, $r): float {
                    $allergies = $r['food_allergies'] ?? '';
                    if (!is_string($allergies)) return 0;
                    $trimmed = trim($allergies);
                    if ($trimmed === '') return 0;
                    // Im dluzszy opis tym wyzszy score (wiecej alergii)
                    return min(10, 3 + (mb_strlen($trimmed) / 30));
                },
            ],
            'debiutant' => [
                'icon' => '🐣', 'name' => 'Debiutant',
                'description' => 'Pierwsze poważne wyjście za granicę.',
                'score' => static fn($p, $r): float => ($r['travel_experience'] ?? '') === 'first_time' ? 10 : 0,
            ],
            'adrenalinowiec' => [
                'icon' => '🪂', 'name' => 'Adrenalinowiec',
                'description' => 'Spadochrony, wspinaczki, sporty ekstremalne.',
                'score' => static function ($p, $r): float {
                    $exp  = $r['trip_expectations'] ?? [];
                    $phys = $r['physical_activities'] ?? [];
                    $s = 0;
                    if (is_array($exp) && in_array('adventure_adrenaline', $exp, true)) $s += 5;
                    if (is_array($phys) && in_array('climbing', $phys, true))           $s += 3;
                    if (is_array($phys) && in_array('winter_sports', $phys, true))      $s += 2;
                    if (is_array($phys) && in_array('watersports', $phys, true))        $s += 1;
                    return $s;
                },
            ],
            'romantyk' => [
                'icon' => '💕', 'name' => 'Romantyk',
                'description' => 'Zachody słońca, kolacje przy świecach, "my time".',
                'score' => static function ($p, $r): float {
                    $exp = $r['trip_expectations'] ?? [];
                    $s = 0;
                    if (is_array($exp) && in_array('romance', $exp, true))                $s += 5;
                    if (is_array($exp) && in_array('time_with_loved_ones', $exp, true))   $s += 3;
                    if (($r['pace'] ?? '') === 'chill')                                    $s += 1;
                    if (($r['food_style'] ?? '') === 'fine_dining')                        $s += 1;
                    return $s;
                },
            ],
            'eskapista' => [
                'icon' => '🌲', 'name' => 'Eskapista',
                'description' => 'Ucieczka od cywilizacji, namiot pod gwiazdami.',
                'score' => static function ($p, $r): float {
                    $exp   = $r['trip_expectations'] ?? [];
                    $accom = $r['accommodation'] ?? [];
                    $s = 0;
                    if (is_array($exp) && in_array('escape_civilization', $exp, true)) $s += 5;
                    if (is_array($accom) && in_array('tent', $accom, true))            $s += 3;
                    if (is_array($accom) && in_array('camping', $accom, true))         $s += 2;
                    return $s;
                },
            ],
            'eksperymentator' => [
                'icon' => '✨', 'name' => 'Eksperymentator',
                'description' => 'Wszystko nowe, wszystko spróbować.',
                'score' => static function ($p, $r): float {
                    $exp = $r['trip_expectations'] ?? [];
                    $s = 0;
                    if (is_array($exp) && in_array('trying_new_things', $exp, true)) $s += 5;
                    if (((int) ($r['food_openness'] ?? 0)) === 5)                    $s += 3;
                    if (($r['travel_experience'] ?? '') === 'globetrotter')          $s += 1;
                    return $s;
                },
            ],
            'spalony_sloncem' => [
                'icon' => '🥵', 'name' => 'Spalony Słońcem',
                'description' => 'Tylko ciepło, mróz to nie dla mnie.',
                'score' => static function ($p, $r): float {
                    $clim = $r['climate_tolerance'] ?? [];
                    if (!is_array($clim) || empty($clim)) return 0;
                    $hot  = (int) (in_array('hot_30plus', $clim, true)) + (int) (in_array('warm_20_30', $clim, true));
                    $cold = (int) (in_array('cool_under_10', $clim, true)) + (int) (in_array('cold_winter', $clim, true));
                    if ($cold > 0) return 0; // toleruje zimno - nie kwalifikuje sie
                    return $hot >= 1 ? 5 + $hot : 0;
                },
            ],
            'lubie_mroz' => [
                'icon' => '❄️', 'name' => 'Lubię Mróz',
                'description' => 'Śnieg, zima, ferie zimowe.',
                'score' => static function ($p, $r): float {
                    $clim = $r['climate_tolerance'] ?? [];
                    if (!is_array($clim) || empty($clim)) return 0;
                    $cold = (int) (in_array('cold_winter', $clim, true)) * 3 + (int) (in_array('cool_under_10', $clim, true)) * 2;
                    return $cold;
                },
            ],
            'termoodporny' => [
                'icon' => '🌡️', 'name' => 'Termoodporny',
                'description' => 'Toleruje wszystko - od upału do mrozu.',
                'score' => static function ($p, $r): float {
                    $clim = $r['climate_tolerance'] ?? [];
                    if (!is_array($clim)) return 0;
                    return count($clim) >= 4 ? (float) count($clim) : 0;
                },
            ],
            'sportowiec' => [
                'icon' => '🏃', 'name' => 'Sportowiec',
                'description' => 'Cardio rano, hiking po południu, joga wieczorem.',
                'score' => static function ($p, $r): float {
                    $phys = $r['physical_activities'] ?? [];
                    if (!is_array($phys)) return 0;
                    // Wykluczamy "none_relax" z liczenia
                    $active = array_values(array_filter($phys, static fn($a) => $a !== 'none_relax'));
                    return count($active) >= 4 ? (float) count($active) : 0;
                },
            ],
            'kolejarz' => [
                'icon' => '🚆', 'name' => 'Kolejarz',
                'description' => 'Pociąg > samochód. Bez stania w korkach.',
                'score' => static function ($p, $r): float {
                    $modes = $r['transport_modes'] ?? [];
                    if (!is_array($modes)) return 0;
                    $hasTrain = in_array('train', $modes, true);
                    $hasCar   = in_array('car', $modes, true);
                    if (!$hasTrain) return 0;
                    return $hasCar ? 3 : 7; // bonus za "tylko pociąg"
                },
            ],
            'latacz' => [
                'icon' => '✈️', 'name' => 'Latacz',
                'description' => 'Samolot i paszport gotowy.',
                'score' => static function ($p, $r): float {
                    $modes = $r['transport_modes'] ?? [];
                    if (!is_array($modes) || !in_array('plane', $modes, true)) return 0;
                    $hasPass = ($r['has_passport'] ?? null) === 'true' || $r['has_passport'] === true;
                    return $hasPass ? 8 : 4;
                },
            ],
            'stadny' => [
                'icon' => '👥', 'name' => 'Stadny',
                'description' => 'Pełna ekipa razem, cały czas.',
                'score' => static function ($p, $r): float {
                    $soc = $r['social_preference'] ?? [];
                    if (!is_array($soc)) return 0;
                    if (!in_array('always_together', $soc, true)) return 0;
                    $s = 5;
                    if (in_array('need_alone_time', $soc, true)) $s -= 2;     // mieszane = mniejszy stadny
                    if (in_array('ok_with_solo_activities', $soc, true)) $s -= 1;
                    return max(1, $s);
                },
            ],
            'samotnik' => [
                'icon' => '🧘', 'name' => 'Samotnik',
                'description' => 'Potrzebuje momentu sam ze sobą.',
                'score' => static function ($p, $r): float {
                    $soc = $r['social_preference'] ?? [];
                    if (!is_array($soc)) return 0;
                    $s = 0;
                    if (in_array('need_alone_time', $soc, true))         $s += 4;
                    if (in_array('ok_with_solo_activities', $soc, true)) $s += 2;
                    if (in_array('always_together', $soc, true))         $s -= 3;
                    return max(0, $s);
                },
            ],
            'dormitorium_ok' => [
                'icon' => '🛏️', 'name' => 'Dormitorium OK',
                'description' => 'Hostel z 8 łóżkami w pokoju? Czemu nie.',
                'score' => static function ($p, $r): float {
                    $rs = $r['room_sharing'] ?? '';
                    if ($rs !== 'dormitory_ok') return 0;
                    $s = 7;
                    $accom = $r['accommodation'] ?? [];
                    if (is_array($accom) && in_array('hostel', $accom, true)) $s += 2;
                    return $s;
                },
            ],

            // ============================================================
            // Odznaki z aktywnosci na platformie (TripPlaceVote stats)
            // Uwaga: "Odkrywca" wyzej tez liczy aktywnosc (miejsca dodane) - bylo
            // wczesniej oparte o stary system pinezek, teraz uzywa trip_places.
            // ============================================================

            'hojne_serce' => [
                'icon' => '🌟', 'name' => 'Hojne Serce',
                'description' => 'Najwyższa średnia ocen jakie wystawia (wszystko mu się podoba).',
                'score' => static function ($p, $r, $unav, $pins, $extra): float {
                    if (($extra['votes_count'] ?? 0) < 5) return 0; // min 5 głosów żeby się liczyło
                    return (float) ($extra['votes_avg'] ?? 0);
                },
            ],
            'surowy_krytyk' => [
                'icon' => '👎', 'name' => 'Surowy Krytyk',
                'description' => 'Najniższa średnia ocen (nic mu się nie podoba).',
                'score' => static function ($p, $r, $unav, $pins, $extra): float {
                    if (($extra['votes_count'] ?? 0) < 5) return 0; // min 5 głosów
                    $avg = (float) ($extra['votes_avg'] ?? 5);
                    // Im NIZSZA srednia tym wyzszy score (odwrocone)
                    return max(0, 6 - $avg);
                },
            ],
            'sedzia' => [
                'icon' => '⚡', 'name' => 'Sędzia',
                'description' => 'Najwięcej głosów oddanych w rankingu miejsc.',
                'score' => static function ($p, $r, $unav, $pins, $extra): float {
                    return (float) ($extra['votes_count'] ?? 0);
                },
            ],
            'skupiony' => [
                'icon' => '📝', 'name' => 'Skupiony',
                'description' => 'Ocenił 100% miejsc na liście.',
                'score' => static function ($p, $r, $unav, $pins, $extra): float {
                    $total = $extra['votes_total'] ?? 0;
                    $count = $extra['votes_count'] ?? 0;
                    if ($total < 5) return 0; // za malo miejsc by liczyc
                    return $count >= $total ? 10 : 0;
                },
            ],
        ];
    }
}
