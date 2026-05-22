<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Trip;
use App\Models\TripPlace;
use App\Models\TripPlaceVote;

/**
 * Algorytm propozycji tras samochodowych z top-ocenionych atrakcji.
 *
 * Krok 1: filtruj miejsca z avg >= MIN_SCORE (zignoruj slabe)
 * Krok 2: klastrowanie greedy single-linkage - miejsca w promieniu CLUSTER_RADIUS_KM trafiaja do jednego klastra
 * Krok 3: per klaster - TSP nearest-neighbor zaczynajac od top-rated
 * Krok 4: nazwij klaster po krajach + oszacuj czas trwania
 * Krok 5: oblicz dystanse Haversine
 *
 * Faktyczne polilinie tras (z drogami) dorabia JS przez OSRM - tu zwracamy
 * tylko liste miejsc w kolejnosci, dystanse i nazwy.
 */
final class RouteSuggestionService
{
    /** Miejsca z mniejsza ocena srednia ignoruj. */
    private const MIN_SCORE = 3.0;

    /** Promien klastrowania - miejsca dalej niz X km tworza osobny klaster. */
    private const CLUSTER_RADIUS_KM = 450.0;

    /** Sredni dystans dzienny jazdy (km). */
    private const DAILY_DRIVE_KM = 400.0;

    /** Dlugosc aktywnego dnia w minutach (zwiedzanie). */
    private const MINUTES_PER_DAY = 480; // 8h dziennie na zwiedzanie

    /** Mapa kod-kraju -> nazwa po polsku. Pelna tabela byloby za dluga - tu top destynacje europejskie. */
    private const COUNTRY_NAMES = [
        'pl' => 'Polska',         'de' => 'Niemcy',        'cz' => 'Czechy',     'sk' => 'Słowacja',
        'at' => 'Austria',        'hu' => 'Węgry',         'ro' => 'Rumunia',    'hr' => 'Chorwacja',
        'si' => 'Słowenia',       'ba' => 'Bośnia',        'rs' => 'Serbia',     'me' => 'Czarnogóra',
        'al' => 'Albania',        'gr' => 'Grecja',        'mk' => 'Macedonia',  'bg' => 'Bułgaria',
        'it' => 'Włochy',         'es' => 'Hiszpania',     'pt' => 'Portugalia', 'fr' => 'Francja',
        'be' => 'Belgia',         'nl' => 'Holandia',      'lu' => 'Luksemburg', 'ch' => 'Szwajcaria',
        'gb' => 'Wielka Brytania','ie' => 'Irlandia',      'dk' => 'Dania',      'no' => 'Norwegia',
        'se' => 'Szwecja',        'fi' => 'Finlandia',     'ee' => 'Estonia',    'lv' => 'Łotwa',
        'lt' => 'Litwa',          'tr' => 'Turcja',        'cy' => 'Cypr',       'mt' => 'Malta',
    ];

    /**
     * Glowna metoda - zwraca propozycje tras dla trip'u.
     * @return list<array{name:string,places:list<array<string,mixed>>,distance_km:float,days_est:int,countries:list<string>}>
     */
    public function suggest(int $tripId): array
    {
        $trip = Trip::findById($tripId);
        if ($trip === null) return [];

        $allPlaces = TripPlace::listForTrip($tripId);
        if (count($allPlaces) < 2) return [];

        $voteStats = TripPlaceVote::statsForTrip($tripId, 0);
        $scored = [];
        foreach ($allPlaces as $p) {
            $stats = $voteStats[$p->id] ?? ['avg' => null, 'count' => 0];
            $avg = $stats['avg'] ?? 3.0;
            if ($avg < self::MIN_SCORE) continue;
            $scored[] = [
                'place' => $p,
                'avg'   => $avg,
                'count' => $stats['count'],
            ];
        }
        if (count($scored) < 2) return [];

        usort($scored, static fn($a, $b) => $b['avg'] <=> $a['avg']);

        $clusters = $this->cluster($scored);

        // Punkt startowy - jesli zdefiniowany dla tripu, dolaczymy go do kazdej trasy
        $startPoint = null;
        if ($trip->startLat !== null && $trip->startLng !== null) {
            $startPoint = [
                'name' => $trip->startName ?? 'Punkt startowy',
                'lat'  => $trip->startLat,
                'lng'  => $trip->startLng,
            ];
        }

        $suggestions = [];
        foreach ($clusters as $cluster) {
            if (count($cluster) < 2) continue;
            $suggestions[] = $this->buildRoute($cluster, $startPoint);
        }

        usort($suggestions, static fn($a, $b) => $b['avg_score'] <=> $a['avg_score']);

        return $suggestions;
    }

    /**
     * Klastrowanie greedy single-linkage.
     * Dla kazdego niezaklasterowanego: znajdz wszystkie w promieniu od dowolnego miejsca w aktualnym klastrze.
     * @param list<array{place:TripPlace,avg:float,count:int}> $scored
     * @return list<list<array{place:TripPlace,avg:float,count:int}>>
     */
    private function cluster(array $scored): array
    {
        $clusters = [];
        $remaining = $scored;

        while (!empty($remaining)) {
            $seed = array_shift($remaining);
            $cluster = [$seed];

            // Single-linkage: dodaj wszystkie w promieniu od KTÓRYKOLWIEK punktu klastra
            $changed = true;
            while ($changed) {
                $changed = false;
                foreach ($remaining as $key => $item) {
                    foreach ($cluster as $member) {
                        $d = $this->haversine(
                            $member['place']->lat, $member['place']->lng,
                            $item['place']->lat, $item['place']->lng
                        );
                        if ($d <= self::CLUSTER_RADIUS_KM) {
                            $cluster[] = $item;
                            unset($remaining[$key]);
                            $changed = true;
                            break;
                        }
                    }
                }
                $remaining = array_values($remaining);
            }

            $clusters[] = $cluster;
        }
        return $clusters;
    }

    /**
     * Zbuduj proponowana trase z klastra: TSP nearest-neighbor + metadane.
     * Jesli $startPoint zdefiniowany, trasa zaczyna sie od niego (np. Warszawa -> Plitvice -> ...).
     * @param list<array{place:TripPlace,avg:float,count:int}> $cluster
     * @param array{name:string,lat:float,lng:float}|null $startPoint
     * @return array<string,mixed>
     */
    private function buildRoute(array $cluster, ?array $startPoint = null): array
    {
        // TSP nearest neighbor.
        // Jesli mamy punkt startowy: zaczynamy od miejsca najblizszego startowi (ekipa minimalizuje dojazd).
        // Bez startu: zaczynamy od miejsca z najwyzsza ocena (najwazniejsze pierwsze).
        if ($startPoint !== null) {
            usort($cluster, function ($a, $b) use ($startPoint) {
                $da = $this->haversine($startPoint['lat'], $startPoint['lng'], $a['place']->lat, $a['place']->lng);
                $db = $this->haversine($startPoint['lat'], $startPoint['lng'], $b['place']->lat, $b['place']->lng);
                return $da <=> $db;
            });
        } else {
            usort($cluster, static fn($a, $b) => $b['avg'] <=> $a['avg']);
        }
        $ordered = [array_shift($cluster)];
        while (!empty($cluster)) {
            $last = end($ordered);
            $nearestKey = null;
            $nearestDist = INF;
            foreach ($cluster as $key => $item) {
                $d = $this->haversine(
                    $last['place']->lat, $last['place']->lng,
                    $item['place']->lat, $item['place']->lng
                );
                if ($d < $nearestDist) {
                    $nearestDist = $d;
                    $nearestKey = $key;
                }
            }
            if ($nearestKey === null) break;
            $ordered[] = $cluster[$nearestKey];
            unset($cluster[$nearestKey]);
            $cluster = array_values($cluster);
        }

        // Total dystans miedzy miejscami
        $totalKm = 0.0;
        for ($i = 1; $i < count($ordered); $i++) {
            $totalKm += $this->haversine(
                $ordered[$i - 1]['place']->lat, $ordered[$i - 1]['place']->lng,
                $ordered[$i]['place']->lat, $ordered[$i]['place']->lng
            );
        }

        // Doliczam start -> pierwsze + ostatnie -> start (round trip)
        $startKm = 0.0;
        if ($startPoint !== null && count($ordered) > 0) {
            $first = $ordered[0];
            $last  = end($ordered);
            $startKm += $this->haversine($startPoint['lat'], $startPoint['lng'], $first['place']->lat, $first['place']->lng);
            $startKm += $this->haversine($last['place']->lat, $last['place']->lng, $startPoint['lat'], $startPoint['lng']);
        }

        // Dystans drogowy jest typowo 1.3x linii prostej
        $drivingKm = ($totalKm + $startKm) * 1.3;

        // Dni: czas zwiedzania (suma visit_minutes / minutowy dzien) + driving days
        $totalVisitMinutes = 0;
        foreach ($ordered as $item) {
            $totalVisitMinutes += $item['place']->visitMinutes;
        }
        $visitDays = $totalVisitMinutes / self::MINUTES_PER_DAY;
        $days = (int) max(1, ceil($visitDays + $drivingKm / self::DAILY_DRIVE_KM));

        // Kraje
        $countries = [];
        $countryCount = [];
        foreach ($ordered as $item) {
            $cc = strtolower($item['place']->countryCode ?? '');
            if ($cc === '') continue;
            $countryCount[$cc] = ($countryCount[$cc] ?? 0) + 1;
        }
        arsort($countryCount);
        $countries = array_keys($countryCount);

        $name = $this->routeName($countries, $days);

        // avg score klastra (do sortowania sugestii)
        $avgScore = array_sum(array_column($ordered, 'avg')) / count($ordered);

        // Format miejsc dla JSON
        $places = array_map(static function ($item) {
            return [
                'id'      => $item['place']->id,
                'name'    => $item['place']->name,
                'lat'     => $item['place']->lat,
                'lng'     => $item['place']->lng,
                'address' => $item['place']->address,
                'avg'     => $item['avg'],
            ];
        }, $ordered);

        return [
            'name'        => $name,
            'places'      => $places,
            'distance_km' => round($drivingKm, 0),
            'days_est'    => $days,
            'countries'   => $countries,
            'avg_score'   => round($avgScore, 2),
            'start'       => $startPoint !== null ? [
                'name' => $startPoint['name'],
                'lat'  => $startPoint['lat'],
                'lng'  => $startPoint['lng'],
            ] : null,
        ];
    }

    private function routeName(array $countries, int $days): string
    {
        if (empty($countries)) return $days . ' dni · do ustalenia';

        $named = [];
        foreach ($countries as $cc) {
            $named[] = self::COUNTRY_NAMES[$cc] ?? strtoupper($cc);
        }

        if (count($named) === 1) {
            return $named[0] . ' · ' . $days . ' dni';
        }
        if (count($named) === 2) {
            return $named[0] . ' + ' . $named[1] . ' · ' . $days . ' dni';
        }
        // 3+ krajow - region
        $region = $this->guessRegion($countries);
        return $region . ' (' . count($countries) . ' kraje) · ' . $days . ' dni';
    }

    private function guessRegion(array $countries): string
    {
        $set = array_flip($countries);
        if (isset($set['hr']) || isset($set['ba']) || isset($set['rs']) || isset($set['me']) || isset($set['al']) || isset($set['mk'])) {
            return 'Bałkany';
        }
        if (isset($set['es']) || isset($set['pt']) || isset($set['fr'])) {
            return 'Europa Zachodnia';
        }
        if (isset($set['no']) || isset($set['se']) || isset($set['fi']) || isset($set['dk'])) {
            return 'Skandynawia';
        }
        if (isset($set['pl']) || isset($set['cz']) || isset($set['sk']) || isset($set['hu'])) {
            return 'Europa Środkowa';
        }
        return 'Europa';
    }

    /**
     * Dystans Haversine miedzy dwoma punktami w km.
     */
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }
}
