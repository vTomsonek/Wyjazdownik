<?php
declare(strict_types=1);

namespace App\Services;

/**
 * "Najslabsze ogniwo" + auto-rekomendacje destynacji.
 * Operuje na danych z SummaryAggregator.
 */
final class RecommendationService
{
    public function __construct(private readonly SummaryAggregator $agg) {}

    /**
     * Zwraca twardy obraz "co realnie ekipa moze zrobic":
     * - paceKm: max km/dzien (najslabsze ogniwo)
     * - climateOk: klimaty ktore wszyscy zaakceptowali
     * - transportOk: srodki ktore wszyscy zaakceptowali
     * - comfortMin: najwyzsze wymaganie komfortu (jesli ktos chce luxury - wszyscy luxury)
     * - budgetReal: najnizszy budzet
     * - durationDays: minimalny okres
     * - passportRequired: czy wszyscy maja paszport
     *
     * @return array<string, mixed>
     */
    public function weakestLink(): array
    {
        $responses = $this->agg->allResponses();
        $count = count($responses);

        // Pace (km/dzien)
        $kmMap = ['under_3km' => 3, '3_7km' => 7, '7_15km' => 15, '15_25km' => 25, 'over_25km' => 30];
        $kmValues = [];
        foreach ($responses as $r) {
            $k = $r['daily_walking_capacity'] ?? null;
            if (is_string($k) && isset($kmMap[$k])) $kmValues[] = $kmMap[$k];
        }
        $paceKm = !empty($kmValues) ? min($kmValues) : null;

        // Climate intersect
        $climateOk = $this->intersectMulti($responses, 'climate_tolerance');

        // Transport intersect
        $transportOk = $this->intersectMulti($responses, 'transport_modes');

        // Comfort - max wymagany (luxury > comfortable > rough)
        $comfortRank = ['rough' => 1, 'comfortable' => 2, 'luxury' => 3];
        $comfortReverse = array_flip($comfortRank);
        $maxComfort = 0;
        foreach ($responses as $r) {
            $c = $r['comfort_level'] ?? null;
            if (is_string($c) && isset($comfortRank[$c])) $maxComfort = max($maxComfort, $comfortRank[$c]);
        }
        $comfortMin = $maxComfort > 0 ? $comfortReverse[$maxComfort] : null;

        // Budget - min
        $budgets = [];
        foreach ($responses as $r) {
            if (is_numeric($r['budget_range'] ?? null)) $budgets[] = (int) $r['budget_range'];
        }
        $budgetReal = !empty($budgets) ? min($budgets) : null;

        // Duration - min
        $durations = [];
        foreach ($responses as $r) {
            if (is_numeric($r['trip_duration_days'] ?? null)) $durations[] = (int) $r['trip_duration_days'];
        }
        $durationDays = !empty($durations) ? min($durations) : null;

        // Passport
        $allHave = true; $someoneAnswered = false;
        foreach ($responses as $r) {
            $hp = $r['has_passport'] ?? null;
            if ($hp === null) continue;
            $someoneAnswered = true;
            if ($hp === 'false' || $hp === false) { $allHave = false; break; }
        }
        $passportAll = $someoneAnswered ? $allHave : null;

        return [
            'paceKm'          => $paceKm,
            'climateOk'       => $climateOk,
            'transportOk'     => $transportOk,
            'comfortMin'      => $comfortMin,
            'budgetReal'      => $budgetReal,
            'durationDays'    => $durationDays,
            'passportAll'     => $passportAll,
            'count'           => $count,
        ];
    }

    /**
     * Mediana budzetu - sugerowany budzet planning.
     */
    public function medianBudget(): ?int
    {
        $vals = [];
        foreach ($this->agg->allResponses() as $r) {
            if (is_numeric($r['budget_range'] ?? null)) $vals[] = (int) $r['budget_range'];
        }
        if (empty($vals)) return null;
        sort($vals);
        $c = count($vals);
        return $c % 2 === 0
            ? (int) round(($vals[$c/2 - 1] + $vals[$c/2]) / 2)
            : $vals[(int) floor($c/2)];
    }

    /**
     * Sugerowane destynacje na podstawie kombinacji tagow.
     * Bardzo prosty mapping - kategorie -> kraje pasujące.
     *
     * @return list<array{name:string, why:string}>
     */
    public function suggestedDestinations(): array
    {
        $resp = $this->agg->allResponses();
        if (empty($resp)) return [];

        $weak = $this->weakestLink();
        $climate = $weak['climateOk'] ?? [];
        $transport = $weak['transportOk'] ?? [];
        $passportAll = $weak['passportAll'] ?? null;

        // Najczestsze tagi krajobrazu
        $landscapeCounts = [];
        foreach ($resp as $r) {
            $vs = $r['landscape_preferences'] ?? [];
            if (is_array($vs)) {
                foreach ($vs as $v) $landscapeCounts[$v] = ($landscapeCounts[$v] ?? 0) + 1;
            }
        }
        arsort($landscapeCounts);
        $topLandscapes = array_keys(array_slice($landscapeCounts, 0, 3, true));

        $isHot     = in_array('hot_30plus', $climate, true);
        $isWarm    = in_array('warm_20_30', $climate, true);
        $isMild    = in_array('mild_10_20', $climate, true);
        $hasSea    = in_array('sea', $topLandscapes, true) || in_array('islands', $topLandscapes, true);
        $hasMtn    = in_array('mountains', $topLandscapes, true);
        $hasCity   = in_array('cities', $topLandscapes, true);
        $hasLake   = in_array('lakes', $topLandscapes, true);
        $onlyEU    = !$passportAll;
        $needsPlane = !empty($transport) && !in_array('plane', $transport, true) === false; // plane jest OK

        $candidates = [];

        if ($hasSea && ($isWarm || $isHot)) {
            $candidates[] = ['name' => '🇭🇷 Chorwacja - Dalmacja',  'why' => 'Morze, ciepło, UE, jeziora i miasteczka. Z PL można dojechać autem.'];
            $candidates[] = ['name' => '🇪🇸 Hiszpania - Costa Brava','why' => 'Plaże, gastronomia, klimat ciepły. Z PL samolot 3h.'];
            $candidates[] = ['name' => '🇮🇹 Włochy - Sardynia',     'why' => 'Wyspa, plaże, kuchnia, średni budżet ok.'];
            $candidates[] = ['name' => '🇬🇷 Grecja - Peloponez',    'why' => 'Plaże + zabytki, taniej niż wyspy.'];
        }

        if ($hasMtn) {
            $candidates[] = ['name' => '🇸🇮 Słowenia - Triglav',         'why' => 'Górskie szlaki, jeziora, kompaktowo.'];
            $candidates[] = ['name' => '🇨🇭 Szwajcaria - Berner Oberland','why' => 'Klasyczne Alpy. Pricey.'];
            $candidates[] = ['name' => '🇪🇸 Pireneje',                    'why' => 'Wielodniowy trekking, mniej tłumno niż Alpy.'];
            $candidates[] = ['name' => '🇸🇰 Tatry słowackie',             'why' => 'Niedrogo, blisko PL, świetne szlaki.'];
        }

        if ($hasCity) {
            $candidates[] = ['name' => '🇵🇹 Lizbona + Porto',  'why' => 'Atrakcyjne ceny, kuchnia, ocean obok.'];
            $candidates[] = ['name' => '🇨🇿 Czechy - Praga + Czeski Raj','why' => 'Niedrogo, kompaktowo, miasta + przyroda.'];
            $candidates[] = ['name' => '🇭🇺 Budapeszt + Balaton','why' => 'Łazienki termalne, jezioro, kuchnia, niska cena.'];
        }

        if ($hasLake) {
            $candidates[] = ['name' => '🇮🇹 Lago di Garda',     'why' => 'Górskie jezioro, plaże, hiking obok.'];
            $candidates[] = ['name' => '🇫🇮 Finlandia - Lakeland','why' => 'Lasy + jeziora + sauny. Nieco zimniej.'];
        }

        if (empty($candidates)) {
            $candidates[] = ['name' => '🇵🇱 Polska - Bieszczady', 'why' => 'Ekipa nie ma jasnych preferencji - blisko, tanio, własny kraj.'];
        }

        // Dedupe + max 6
        $seen = [];
        $uniq = [];
        foreach ($candidates as $c) {
            if (isset($seen[$c['name']])) continue;
            $seen[$c['name']] = true;
            $uniq[] = $c;
            if (count($uniq) >= 6) break;
        }
        return $uniq;
    }

    /**
     * @return list<string> czesc wspolna (intersect) wartosci dla danego klucza multi.
     */
    private function intersectMulti(array $responses, string $key): array
    {
        $intersect = null;
        foreach ($responses as $r) {
            $vs = $r[$key] ?? null;
            if (!is_array($vs) || empty($vs)) continue;
            if ($intersect === null) {
                $intersect = $vs;
            } else {
                $intersect = array_values(array_intersect($intersect, $vs));
            }
        }
        return $intersect ?? [];
    }
}
