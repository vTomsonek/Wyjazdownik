-- ============================================================================
-- Wyjazdownik.pl - seed data
-- 1 admin, 1 trip, 4 participants with full responses + dates + map pins
-- ============================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- admins
-- ----------------------------------------------------------------------------
INSERT INTO `admins` (`id`, `email`, `name`, `created_at`) VALUES
(1, 'tomasz@jiko.pl', 'Tomasz', NOW());

-- ----------------------------------------------------------------------------
-- trips
-- ----------------------------------------------------------------------------
-- summary token: 'cafe0000...' x8 (64 hex chars) - łatwy do skopiowania w testach
INSERT INTO `trips` (
    `id`, `admin_id`, `name`, `slug`, `description`, `banner_image`,
    `date_from`, `date_to`,
    `calendar_mode`, `show_individual_responses`, `is_active`,
    `summary_public_token`, `created_at`, `updated_at`
) VALUES (
    1, 1,
    'Lato 2026 z ekipą',
    'lato-2026-z-ekipa',
    'Wakacyjny wyjazd ekipy. Mamy do uzgodnienia termin, kierunek, budżet i styl. Wypełnijcie ankietę i spotykamy się żeby zobaczyć co ekipa wymyśliła.',
    NULL,
    '2026-07-01', '2026-08-31',
    'block_unavailable', 1, 1,
    'cafe0000cafe0000cafe0000cafe0000cafe0000cafe0000cafe0000cafe0000',
    NOW(), NOW()
);

-- ----------------------------------------------------------------------------
-- participants
-- ----------------------------------------------------------------------------
-- access tokens: 64 hex chars (one digit repeated)
INSERT INTO `participants` (
    `id`, `trip_id`, `nickname`, `avatar_path`, `access_token`,
    `completed_at`, `last_activity_at`, `created_at`
) VALUES
(1, 1, 'Tomek',  NULL, '1111111111111111111111111111111111111111111111111111111111111111', NOW(), NOW(), NOW()),
(2, 1, 'Kasia',  NULL, '2222222222222222222222222222222222222222222222222222222222222222', NOW(), NOW(), NOW()),
(3, 1, 'Bartek', NULL, '3333333333333333333333333333333333333333333333333333333333333333', NOW(), NOW(), NOW()),
(4, 1, 'Ola',    NULL, '4444444444444444444444444444444444444444444444444444444444444444', NOW(), NOW(), NOW());

-- ----------------------------------------------------------------------------
-- participant_unavailable_dates
-- ----------------------------------------------------------------------------
-- Tomek: 2 dni
INSERT INTO `participant_unavailable_dates` (`participant_id`, `unavailable_date`) VALUES
(1, '2026-08-15'),
(1, '2026-08-16'),
-- Kasia: 10 dni (wiecznie zajęta)
(2, '2026-07-01'), (2, '2026-07-02'), (2, '2026-07-03'), (2, '2026-07-04'), (2, '2026-07-05'),
(2, '2026-07-06'), (2, '2026-07-07'), (2, '2026-07-08'), (2, '2026-07-09'), (2, '2026-07-10'),
-- Bartek: 0 dni
-- Ola: 4 dni pod koniec sierpnia
(4, '2026-08-25'), (4, '2026-08-26'), (4, '2026-08-27'), (4, '2026-08-28');

-- ----------------------------------------------------------------------------
-- participant_responses (Tomek - "Mr Luzak / Plażowicz")
-- ----------------------------------------------------------------------------
INSERT INTO `participant_responses` (`participant_id`, `question_key`, `value_text`, `value_json`) VALUES
(1, 'budget_range',          '4000', NULL),
(1, 'trip_duration_days',    '10', NULL),
(1, 'has_passport',          'true', NULL),
(1, 'money_attitude',        'balanced', NULL),
(1, 'transport_modes',       NULL, '["car","plane","train","bus"]'),
(1, 'has_driving_license',   'true', NULL),
(1, 'can_share_car',         'yes', NULL),
(1, 'max_daily_driving_km',  '200_500', NULL),
(1, 'landscape_preferences', NULL, '["sea","islands","cities"]'),
(1, 'climate_tolerance',     NULL, '["hot_30plus","warm_20_30"]'),
(1, 'travel_experience',     'europe_some', NULL),
(1, 'daily_walking_capacity','7_15km', NULL),
(1, 'physical_activities',   NULL, '["swimming","watersports"]'),
(1, 'pace',                  'chill', NULL),
(1, 'accommodation',         NULL, '["airbnb","hotel","any"]'),
(1, 'room_sharing',          'share_with_friends', NULL),
(1, 'comfort_level',         'comfortable', NULL),
(1, 'dietary_restrictions',  NULL, '["none"]'),
(1, 'food_allergies',        '', NULL),
(1, 'food_style',            'local_eateries', NULL),
(1, 'food_openness',         '4', NULL),
(1, 'alcohol_attitude',      'social', NULL),
(1, 'party_style',           'moderate', NULL),
(1, 'activities',            NULL, '["beach","food_culture","photography","sightseeing"]'),
(1, 'trip_expectations',     NULL, '["rest","swimming","time_with_loved_ones"]'),
(1, 'photo_attitude',        'casual_sharing', NULL),
(1, 'social_preference',     NULL, '["always_together","small_group_split_ok"]'),
(1, 'languages',             NULL, '{"english":"communicative","german":"basic","spanish":"basic"}'),
(1, 'other_languages',       '', NULL),
(1, 'dream_plan',            'Tygodniowy chill nad ciepłym morzem - apartament z widokiem, knajpki na lokalnym targu, długie kolacje, kilka spacerów po starówkach. Bez biegania od atrakcji do atrakcji.', NULL),
(1, 'deal_breakers',         'Nie chcę spać w namiocie ani imprezować do rana. Brak dostępu do prysznica = nie jadę.', NULL);

-- ----------------------------------------------------------------------------
-- participant_responses (Kasia - "Najbardziej wymagająca / Influencer / Foodie")
-- ----------------------------------------------------------------------------
INSERT INTO `participant_responses` (`participant_id`, `question_key`, `value_text`, `value_json`) VALUES
(2, 'budget_range',          '8000', NULL),
(2, 'trip_duration_days',    '7', NULL),
(2, 'has_passport',          'true', NULL),
(2, 'money_attitude',        'vacation_mode', NULL),
(2, 'transport_modes',       NULL, '["plane"]'),
(2, 'has_driving_license',   'true', NULL),
(2, 'can_share_car',         'no', NULL),
(2, 'max_daily_driving_km',  'under_200', NULL),
(2, 'landscape_preferences', NULL, '["cities","sea","islands"]'),
(2, 'climate_tolerance',     NULL, '["warm_20_30"]'),
(2, 'travel_experience',     'worldwide_some', NULL),
(2, 'daily_walking_capacity','3_7km', NULL),
(2, 'physical_activities',   NULL, '["swimming","yoga_fitness"]'),
(2, 'pace',                  'balanced', NULL),
(2, 'accommodation',         NULL, '["hotel"]'),
(2, 'room_sharing',          'private_only', NULL),
(2, 'comfort_level',         'luxury', NULL),
(2, 'dietary_restrictions',  NULL, '["gluten_free","lactose_free"]'),
(2, 'food_allergies',        'orzechy włoskie - silna alergia, anafilaksja', NULL),
(2, 'food_style',            'fine_dining', NULL),
(2, 'food_openness',         '5', NULL),
(2, 'alcohol_attitude',      'wine_with_dinner', NULL),
(2, 'party_style',           'quiet', NULL),
(2, 'activities',            NULL, '["food_culture","museums","shopping","photography","sightseeing"]'),
(2, 'trip_expectations',     NULL, '["rest","sightseeing_culture","content_creation"]'),
(2, 'photo_attitude',        'influencer_mode', NULL),
(2, 'social_preference',     NULL, '["small_group_split_ok","need_alone_time"]'),
(2, 'languages',             NULL, '{"english":"fluent","italian":"communicative","french":"basic","spanish":"basic"}'),
(2, 'other_languages',       '', NULL),
(2, 'dream_plan',            'Capri lub Amalfitana - boutique hotel, śniadania z widokiem na morze, jeden dzień na łodzi, wieczory w restauracjach z gwiazdkami. Czas na sesję, bo robię content na IG.', NULL),
(2, 'deal_breakers',         'Brak Wi-Fi i prysznica osobistego. Dormitoria, hostele, fast foody na 5 dni z rzędu. Pasywne leżenie 8 godzin na plaży.', NULL);

-- ----------------------------------------------------------------------------
-- participant_responses (Bartek - "Kebab Master / Imprezowicz / Backpacker")
-- ----------------------------------------------------------------------------
INSERT INTO `participant_responses` (`participant_id`, `question_key`, `value_text`, `value_json`) VALUES
(3, 'budget_range',          '2500', NULL),
(3, 'trip_duration_days',    '14', NULL),
(3, 'has_passport',          'true', NULL),
(3, 'money_attitude',        'save_food_spend_attractions', NULL),
(3, 'transport_modes',       NULL, '["car","plane","bus"]'),
(3, 'has_driving_license',   'true', NULL),
(3, 'can_share_car',         'yes', NULL),
(3, 'max_daily_driving_km',  '500_800', NULL),
(3, 'landscape_preferences', NULL, '["sea","cities"]'),
(3, 'climate_tolerance',     NULL, '["hot_30plus","warm_20_30"]'),
(3, 'travel_experience',     'europe_some', NULL),
(3, 'daily_walking_capacity','7_15km', NULL),
(3, 'physical_activities',   NULL, '["swimming","watersports"]'),
(3, 'pace',                  'balanced', NULL),
(3, 'accommodation',         NULL, '["hostel","airbnb","camping"]'),
(3, 'room_sharing',          'share_with_friends', NULL),
(3, 'comfort_level',         'rough', NULL),
(3, 'dietary_restrictions',  NULL, '["none"]'),
(3, 'food_allergies',        '', NULL),
(3, 'food_style',            'street_food', NULL),
(3, 'food_openness',         '5', NULL),
(3, 'alcohol_attitude',      'full_party', NULL),
(3, 'party_style',           'party_hard', NULL),
(3, 'activities',            NULL, '["beach","nightlife","food_culture"]'),
(3, 'trip_expectations',     NULL, '["kebab","party_meet_people","swimming"]'),
(3, 'photo_attitude',        'souvenir_only', NULL),
(3, 'social_preference',     NULL, '["always_together"]'),
(3, 'languages',             NULL, '{"english":"communicative","german":"basic"}'),
(3, 'other_languages',       'turecki - kilka słów do zamawiania kebaba', NULL),
(3, 'dream_plan',            'Bałkany roadtrip - Chorwacja, Bośnia, Albania. Hostele i kempingi, kebaby przy każdej okazji, plaża w dzień, klub w nocy. Spotykamy lokalsów, jemy gdzie oni.', NULL),
(3, 'deal_breakers',         'Wakacje w hotelu all-inclusive bez kontaktu ze światem zewnętrznym. Ekstremalna wspinaczka. Brak alkoholu i imprezy.', NULL);

-- ----------------------------------------------------------------------------
-- participant_responses (Ola - "Maszyna / Górski Wilk / Globtrotter")
-- ----------------------------------------------------------------------------
INSERT INTO `participant_responses` (`participant_id`, `question_key`, `value_text`, `value_json`) VALUES
(4, 'budget_range',          '4000', NULL),
(4, 'trip_duration_days',    '12', NULL),
(4, 'has_passport',          'true', NULL),
(4, 'money_attitude',        'balanced', NULL),
(4, 'transport_modes',       NULL, '["car","plane","train","bus"]'),
(4, 'has_driving_license',   'true', NULL),
(4, 'can_share_car',         'maybe', NULL),
(4, 'max_daily_driving_km',  '200_500', NULL),
(4, 'landscape_preferences', NULL, '["mountains","forest","lakes"]'),
(4, 'climate_tolerance',     NULL, '["warm_20_30","mild_10_20","cool_under_10"]'),
(4, 'travel_experience',     'globetrotter', NULL),
(4, 'daily_walking_capacity','over_25km', NULL),
(4, 'physical_activities',   NULL, '["hiking","cycling","climbing","running"]'),
(4, 'pace',                  'intensive', NULL),
(4, 'accommodation',         NULL, '["hostel","camping","tent"]'),
(4, 'room_sharing',          'dormitory_ok', NULL),
(4, 'comfort_level',         'rough', NULL),
(4, 'dietary_restrictions',  NULL, '["vegetarian"]'),
(4, 'food_allergies',        '', NULL),
(4, 'food_style',            'self_cooking', NULL),
(4, 'food_openness',         '4', NULL),
(4, 'alcohol_attitude',      'none', NULL),
(4, 'party_style',           'quiet', NULL),
(4, 'activities',            NULL, '["hiking","nature","photography"]'),
(4, 'trip_expectations',     NULL, '["adventure_adrenaline","escape_civilization","trying_new_things"]'),
(4, 'photo_attitude',        'souvenir_only', NULL),
(4, 'social_preference',     NULL, '["small_group_split_ok","ok_with_solo_activities"]'),
(4, 'languages',             NULL, '{"english":"fluent","spanish":"communicative","german":"communicative","czech":"basic","slovak":"basic"}'),
(4, 'other_languages',       'norweski - podstawy', NULL),
(4, 'dream_plan',            'Trekking w Pirenejach - 12 dni, każdego dnia 20-30 km. Namiot pod gwiazdami, gotujemy nad ogniskiem, brak zasięgu. Wracamy zmęczeni, ale szczęśliwi.', NULL),
(4, 'deal_breakers',         'Pasywne leżenie na plaży, all-inclusive, miasta gdzie tylko się chodzi po sklepach. Imprezy do rana, alkohol jako główna atrakcja.', NULL);

-- ----------------------------------------------------------------------------
-- participant_map_pins
-- ----------------------------------------------------------------------------
-- Tomek: marker w Trogir (HR), polygon nad Adriatykiem
INSERT INTO `participant_map_pins` (`participant_id`, `pin_type`, `label`, `description`, `geojson`, `color`) VALUES
(1, 'marker', 'Trogir', 'Stare miasto, plaża obok',
    '{"type":"Feature","geometry":{"type":"Point","coordinates":[16.2519,43.5170]},"properties":{}}',
    '#FF6B35'),
(1, 'marker', 'Hvar', 'Wyspa, fajne plaże',
    '{"type":"Feature","geometry":{"type":"Point","coordinates":[16.4419,43.1729]},"properties":{}}',
    '#FF6B35');

-- Kasia: marker na Capri
INSERT INTO `participant_map_pins` (`participant_id`, `pin_type`, `label`, `description`, `geojson`, `color`) VALUES
(2, 'marker', 'Capri', 'Boutique hotel, sesja zdjęciowa',
    '{"type":"Feature","geometry":{"type":"Point","coordinates":[14.2167,40.5500]},"properties":{}}',
    '#2EC4B6'),
(2, 'marker', 'Positano', 'Amalfitana, restauracje',
    '{"type":"Feature","geometry":{"type":"Point","coordinates":[14.4847,40.6280]},"properties":{}}',
    '#2EC4B6');

-- Bartek: polyline Bałkany roadtrip
INSERT INTO `participant_map_pins` (`participant_id`, `pin_type`, `label`, `description`, `geojson`, `color`) VALUES
(3, 'polyline', 'Bałkany roadtrip',
    'Split → Mostar → Sarajewo → Tirana',
    '{"type":"Feature","geometry":{"type":"LineString","coordinates":[[16.4402,43.5081],[17.8138,43.3438],[18.4131,43.8563],[19.8189,41.3275]]},"properties":{}}',
    '#FFD23F'),
(3, 'marker', 'Berat (AL)',
    'Stare miasto, kebab',
    '{"type":"Feature","geometry":{"type":"Point","coordinates":[19.9522,40.7058]},"properties":{}}',
    '#FFD23F');

-- Ola: polygon w Pirenejach
INSERT INTO `participant_map_pins` (`participant_id`, `pin_type`, `label`, `description`, `geojson`, `color`) VALUES
(4, 'polygon', 'Pireneje (środkowe)',
    'Strefa trekkingu - Aigüestortes, Ordesa',
    '{"type":"Feature","geometry":{"type":"Polygon","coordinates":[[[0.5,42.5],[1.2,42.5],[1.2,42.85],[0.5,42.85],[0.5,42.5]]]},"properties":{}}',
    '#10B981'),
(4, 'marker', 'Aigüestortes NP',
    'Schronisko, start treku',
    '{"type":"Feature","geometry":{"type":"Point","coordinates":[0.9333,42.5667]},"properties":{}}',
    '#10B981');
