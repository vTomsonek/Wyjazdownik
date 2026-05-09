<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Pełne mapowanie kluczy pytań na polskie etykiety i opcje.
 * Centralne źródło prawdy - używane w wizardzie uczestnika ORAZ na stronie podsumowania.
 */
final class QuestionLabels
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            // ---- Sekcja Podstawy ----
            'budget_range' => [
                'question' => 'Ile maksymalnie chcesz przeznaczyć na ten wyjazd?',
                'helper'   => 'Szacunkowy koszt całego wyjazdu na osobę.',
                'type'     => 'slider',
                'min'      => 0,
                'max'      => 30000,
                'step'     => 500,
                'unit'     => 'zł',
            ],
            'trip_duration_days' => [
                'question' => 'Ile dni planujesz wyjazd?',
                'type'     => 'slider',
                'min'      => 2,
                'max'      => 30,
                'unit'     => 'dni',
            ],
            'has_passport' => [
                'question' => 'Masz ważny paszport?',
                'options'  => ['true' => 'Tak', 'false' => 'Nie'],
            ],
            'money_attitude' => [
                'question' => 'Jak podchodzisz do wydawania pieniędzy na wyjeździe?',
                'options'  => [
                    'strict'                       => '💰 Skrupulatnie liczę każdy grosz',
                    'balanced'                     => '⚖️ Trzymam się budżetu, bez przesady',
                    'save_food_spend_attractions'  => '🎢 Wydaję na atrakcje, oszczędzam na jedzeniu',
                    'vacation_mode'                => '🏖️ Wydaję bo wakacje',
                    'unlimited'                    => '💎 Pieniądze nie grają roli',
                ],
            ],

            // ---- Sekcja Transport ----
            'transport_modes' => [
                'question' => 'Jakim transportem możesz jechać?',
                'helper'   => 'Zaznacz wszystkie OK.',
                'multi'    => true,
                'options'  => [
                    'car'   => '🚗 Samochód',
                    'plane' => '✈️ Samolot',
                    'train' => '🚆 Pociąg',
                    'bus'   => '🚌 Autobus',
                ],
            ],
            'has_driving_license' => [
                'question' => 'Masz prawo jazdy kategorii B?',
                'options'  => ['true' => 'Tak', 'false' => 'Nie'],
            ],
            'can_share_car' => [
                'question' => 'Możesz udostępnić auto na wyjazd?',
                'options'  => [
                    'yes'   => 'Tak',
                    'no'    => 'Nie',
                    'maybe' => 'Może, zależy od sytuacji',
                ],
            ],
            'max_daily_driving_km' => [
                'question' => 'Ile maksymalnie chcesz prowadzić dziennie?',
                'options'  => [
                    'under_200' => 'Do 200 km',
                    '200_500'   => '200 - 500 km',
                    '500_800'   => '500 - 800 km',
                    'over_800'  => 'Powyżej 800 km',
                ],
            ],

            // ---- Sekcja Kierunek i klimat ----
            'landscape_preferences' => [
                'question' => 'Co chcesz zobaczyć?',
                'multi'    => true,
                'options'  => [
                    'mountains'    => '⛰️ Góry',
                    'sea'          => '🌊 Morze',
                    'cities'       => '🏙️ Miasta',
                    'countryside'  => '🌾 Wieś',
                    'lakes'        => '🏞️ Jeziora',
                    'desert'       => '🏜️ Pustynia',
                    'forest'       => '🌲 Lasy',
                    'islands'      => '🏝️ Wyspy',
                ],
            ],
            'climate_tolerance' => [
                'question' => 'Jakie klimaty ci pasują?',
                'helper'   => 'Zaznacz wszystkie OK',
                'multi'    => true,
                'options'  => [
                    'hot_30plus'    => '🥵 Upały (30°C+)',
                    'warm_20_30'    => '☀️ Ciepło (20 - 30°C)',
                    'mild_10_20'    => '🌤️ Umiarkowanie (10 - 20°C)',
                    'cool_under_10' => '🍂 Chłodno (poniżej 10°C)',
                    'cold_winter'   => '❄️ Mróz / zima',
                ],
            ],
            'travel_experience' => [
                'question' => 'Jakie masz doświadczenie podróżnicze?',
                'options'  => [
                    'first_time'      => '🐣 Pierwszy raz za granicą / mało jeździłem',
                    'europe_some'     => '✈️ Trochę pojeździłem po Europie',
                    'worldwide_some'  => '🌍 Sporo wyjazdów, też dalsze kraje',
                    'globetrotter'    => '🎒 Globtrotter, nic mnie nie zaskoczy',
                ],
            ],

            // ---- Sekcja Aktywność fizyczna ----
            'daily_walking_capacity' => [
                'question' => 'Ile jesteś w stanie przejść w ciągu dnia?',
                'options'  => [
                    'under_3km' => '🛋️ Do 3 km - jestem urlopowiczem',
                    '3_7km'     => '🚶 3 - 7 km - normalne zwiedzanie',
                    '7_15km'    => '🥾 7 - 15 km - lubię chodzić',
                    '15_25km'   => '🏃 15 - 25 km - długie trasy',
                    'over_25km' => '🦵 25+ km - jestem maszyną',
                ],
            ],
            'physical_activities' => [
                'question' => 'Jakie aktywności fizyczne chcesz robić?',
                'multi'    => true,
                'options'  => [
                    'hiking'        => '🥾 Piesze wędrówki',
                    'cycling'       => '🚴 Rower',
                    'swimming'      => '🏊 Pływanie',
                    'watersports'   => '🏄 Sporty wodne',
                    'diving'        => '🤿 Nurkowanie / snorkeling',
                    'climbing'      => '🧗 Wspinaczka',
                    'running'       => '🏃 Bieganie',
                    'yoga_fitness'  => '🧘 Joga / fitness',
                    'winter_sports' => '⛷️ Sporty zimowe',
                    'none_relax'    => '🛋️ Żadne, jadę leżeć',
                ],
            ],

            // ---- Sekcja Tempo, komfort i nocleg ----
            'pace' => [
                'question' => 'Jakie tempo wyjazdu preferujesz?',
                'options'  => [
                    'chill'     => '😌 Chill, nigdzie się nie spieszymy',
                    'balanced'  => '⚖️ Zbalansowane',
                    'intensive' => '🏃 Intensywne zwiedzanie',
                ],
            ],
            'accommodation' => [
                'question' => 'Gdzie chcesz spać?',
                'multi'    => true,
                'options'  => [
                    'hostel'  => '🏠 Hostel',
                    'airbnb'  => '🏡 Airbnb',
                    'hotel'   => '🏨 Hotel',
                    'camping' => '⛺ Camping',
                    'tent'    => '🏕️ Pod namiotem',
                    'any'     => '🤷 Cokolwiek',
                ],
            ],
            'room_sharing' => [
                'question' => 'Z kim akceptujesz dzielenie pokoju?',
                'helper'   => 'Typ noclegu (hotel/hostel/namiot) wybierasz osobno wyżej.',
                'options'  => [
                    'private_only'        => '🚪 Chcę osobny pokój',
                    'share_with_friends'  => '👥 Mogę dzielić pokój ze znajomymi',
                    'dormitory_ok'        => '🛏️ Mogę spać w dormitorium z obcymi',
                    'no_bed_sharing'      => '🚫 Mogę pokój, ale nie łóżko',
                ],
            ],
            'comfort_level' => [
                'question' => 'Jaki poziom komfortu jest dla ciebie OK?',
                'options'  => [
                    'luxury'      => '💎 Luksus - chcę komfortu',
                    'comfortable' => '😊 Komfortowo, ale rozsądnie',
                    'rough'       => '🎒 Dam radę spać byle gdzie',
                ],
            ],

            // ---- Sekcja Jedzenie ----
            'dietary_restrictions' => [
                'question' => 'Masz jakieś ograniczenia dietetyczne?',
                'multi'    => true,
                'options'  => [
                    'none'         => 'Brak / wszystkożerca',
                    'vegetarian'   => '🥗 Wegetarianin',
                    'vegan'        => '🌱 Weganin',
                    'gluten_free'  => '🌾 Bezglutenowo',
                    'lactose_free' => '🥛 Bez laktozy',
                    'halal'        => 'Halal',
                    'kosher'       => 'Koszer',
                ],
            ],
            'food_allergies' => [
                'question' => 'Alergie pokarmowe?',
                'helper'   => 'Wpisz na co konkretnie - to ważne dla bezpieczeństwa ekipy.',
                'type'     => 'textarea',
            ],
            'food_style' => [
                'question' => 'Jaki styl jedzenia w podróży preferujesz?',
                'options'  => [
                    'street_food'    => '🍔 Street food / fast food',
                    'local_eateries' => '🍝 Lokalne knajpy',
                    'restaurants'    => '🍽️ Restauracje',
                    'fine_dining'    => '👨‍🍳 Fine dining',
                    'self_cooking'   => '🥪 Sam sobie zrobię',
                ],
            ],
            'food_openness' => [
                'question' => 'Jak bardzo jesteś otwarty kulinarnie?',
                'helper'   => '1 = tylko sprawdzone, 5 = zjem wszystko (owady, flaki, fermenty)',
                'type'     => 'slider',
                'min'      => 1,
                'max'      => 5,
            ],

            // ---- Sekcja Alkohol i imprezy ----
            'alcohol_attitude' => [
                'question' => 'Jaki masz stosunek do alkoholu na wyjeździe?',
                'helper'   => 'Bez owijania w bawełnę 🍻',
                'options'  => [
                    'none'              => '🚫 Zero - nie piję wcale',
                    'wine_with_dinner'  => '🍷 Lampka do kolacji',
                    'social'            => '🍺 Społecznie - piwko, wino',
                    'likes_drinking'    => '🥃 Lubię się napić',
                    'full_party'        => '🍻 CHLANIE - jedziemy na full',
                ],
            ],
            'party_style' => [
                'question' => 'Jak imprezujesz?',
                'options'  => [
                    'party_hard' => '🎉 Imprezujemy na full',
                    'moderate'   => '🍷 Lampka wina, w miarę spokojnie',
                    'quiet'      => '🤫 Spokojnie, bez imprez',
                ],
            ],

            // ---- Sekcja Charakter wyjazdu ----
            'activities' => [
                'question' => 'Jakie aktywności cię interesują?',
                'multi'    => true,
                'options'  => [
                    'beach'         => '🏖️ Plaża',
                    'hiking'        => '🥾 Wędrówki',
                    'sightseeing'   => '🏛️ Zwiedzanie',
                    'watersports'   => '🏄 Sporty wodne',
                    'food_culture'  => '🍽️ Kuchnia lokalna',
                    'nightlife'     => '🌃 Nightlife',
                    'museums'       => '🖼️ Muzea',
                    'shopping'      => '🛍️ Shopping',
                    'nature'        => '🌿 Przyroda',
                    'photography'   => '📸 Fotografia',
                ],
            ],
            'trip_expectations' => [
                'question'        => 'Czego oczekujesz od wyjazdu?',
                'helper'          => 'Wybierz maksymalnie 3 najważniejsze rzeczy.',
                'multi'           => true,
                'max_selections'  => 3,
                'options'         => [
                    'rest'                  => '😌 Odpoczynek i regeneracja',
                    'swimming'              => '🏊 Pływanie (morze / jezioro)',
                    'kebab'                 => '🥙 Jedzenie kebabów',
                    'party_meet_people'     => '🎉 Imprezy i poznawanie ludzi',
                    'sightseeing_culture'   => '🏛️ Zwiedzanie i kultura',
                    'adventure_adrenaline'  => '🪂 Przygoda i adrenalina',
                    'time_with_loved_ones'  => '❤️ Czas z bliskimi',
                    'content_creation'      => '📱 Robienie zdjęć / contentu',
                    'trying_new_things'     => '✨ Próbowanie nowych rzeczy',
                    'escape_civilization'   => '🌲 Ucieczka od cywilizacji',
                    'romance'               => '💕 Romantyka',
                ],
            ],
            'photo_attitude' => [
                'question' => 'Jaki masz stosunek do robienia zdjęć?',
                'options'  => [
                    'hate_posing'      => '📵 Nie cierpię pozowania',
                    'souvenir_only'    => '📷 Pamiątkowe zdjęcia - OK',
                    'casual_sharing'   => '📱 Lubię zdjęcia, czasem wrzucę',
                    'influencer_mode'  => '📸 Influencer mode - sesje, drony',
                ],
            ],
            'social_preference' => [
                'question' => 'Z kim najchętniej dzielisz wyjazd?',
                'multi'    => true,
                'options'  => [
                    'always_together'         => '👥 Pełna ekipa razem cały czas',
                    'small_group_split_ok'    => '🫂 Mała grupa, czasem się rozdzielamy',
                    'need_alone_time'         => '🧘 Lubię momenty samotności',
                    'ok_with_solo_activities' => '🚶 Dam radę żeby ktoś robił coś innego',
                ],
            ],

            // ---- Sekcja Języki ----
            'languages' => [
                'question'  => 'Jakie języki znasz?',
                'helper'    => 'Zaznacz poziom dla każdego.',
                'type'      => 'language_grid',
                'languages' => [
                    'english'    => 'Angielski',
                    'german'     => 'Niemiecki',
                    'spanish'    => 'Hiszpański',
                    'french'     => 'Francuski',
                    'italian'    => 'Włoski',
                    'russian'    => 'Rosyjski',
                    'ukrainian'  => 'Ukraiński',
                    'czech'      => 'Czeski',
                    'slovak'     => 'Słowacki',
                    'portuguese' => 'Portugalski',
                    'dutch'      => 'Holenderski',
                    'swedish'    => 'Szwedzki',
                    'japanese'   => 'Japoński',
                    'mandarin'   => 'Chiński',
                ],
                'levels'    => [
                    'none'          => 'Nie znam',
                    'basic'         => 'Podstawy',
                    'communicative' => 'Komunikatywnie',
                    'fluent'        => 'Biegle',
                ],
                'popular'   => ['english', 'german', 'spanish', 'french'],
            ],
            'other_languages' => [
                'question' => 'Jakieś inne języki?',
                'helper'   => 'Wpisz nazwę i poziom (np. "norweski - podstawy").',
                'type'     => 'textarea',
            ],

            // ---- Sekcja Wolny tekst ----
            'dream_plan' => [
                'question' => 'Twój wymarzony plan na te wakacje',
                'helper'   => 'Opisz jak widzisz idealne wakacje z ekipą.',
                'type'     => 'textarea',
            ],
            'deal_breakers' => [
                'question' => 'Deal breakery - czego absolutnie nie chcesz',
                'helper'   => 'Co sprawi że nie pojedziesz na wyjazd.',
                'type'     => 'textarea',
            ],
        ];
    }

    /**
     * Zwraca metadane jednego pytania albo null gdy klucz nieznany.
     *
     * @return array<string,mixed>|null
     */
    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /**
     * Pełna lista znanych kluczy - przydatne przy walidacji save endpointu.
     * @return list<string>
     */
    public static function knownKeys(): array
    {
        return array_keys(self::all());
    }

    /**
     * @deprecated Use App\Helpers\QuestionFormatter::format() instead.
     */
    public static function valueLabel(string $key, mixed $value): string
    {
        return QuestionFormatter::format($key, $value);
    }
}
