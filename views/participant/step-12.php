<?php
/** Krok 12: Review - podsumowanie odpowiedzi przed wyslaniem */
use App\Helpers\QuestionFormatter;
use App\Helpers\QuestionLabels;

$totalAnswered = count($responses);
$totalKnown    = count(QuestionLabels::knownKeys());
$progress      = $totalKnown > 0 ? (int) round($totalAnswered / $totalKnown * 100) : 0;
?>
<header class="mb-8">
    <span class="text-3xl mb-2 block">✨</span>
    <h2 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale">
        Prawie gotowe!
    </h2>
    <p class="text-mist mt-2">
        Sprawdź odpowiedzi przed wysłaniem.
        <?= $isAdminEdit ? '' : 'Po wysłaniu nadal możesz wracać i edytować.' ?>
    </p>
</header>

<!-- Statystyki -->
<div class="mb-6 rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5">
    <div class="flex items-center justify-between mb-2 text-sm">
        <span class="font-medium text-ink dark:text-pale">Odpowiedzi</span>
        <span class="font-mono text-mist"><?= $totalAnswered ?> / <?= $totalKnown ?></span>
    </div>
    <div class="h-2 rounded-full bg-mist/15 overflow-hidden">
        <div class="h-full bg-secondary" style="width: <?= $progress ?>%"></div>
    </div>
    <p class="mt-2 text-xs text-mist">
        Pytania są opcjonalne (oprócz dostępności). Im więcej wypełnisz - tym lepsze rekomendacje.
    </p>
</div>

<?php
$sections = [
    'Podstawy'           => ['budget_range', 'trip_duration_days', 'has_passport', 'money_attitude'],
    'Transport'          => ['transport_modes', 'has_driving_license', 'can_share_car', 'max_daily_driving_km'],
    'Kierunek'           => ['landscape_preferences', 'climate_tolerance', 'travel_experience'],
    'Aktywność'          => ['daily_walking_capacity', 'physical_activities'],
    'Komfort i nocleg'   => ['accommodation', 'room_sharing', 'comfort_level', 'pace'],
    'Jedzenie i picie'   => ['dietary_restrictions', 'food_allergies', 'food_style', 'food_openness', 'alcohol_attitude', 'party_style'],
    'Charakter wyjazdu'  => ['activities', 'trip_expectations', 'photo_attitude', 'social_preference'],
    'Języki'             => ['languages', 'other_languages'],
    'Wolny tekst'        => ['dream_plan', 'deal_breakers'],
];
?>

<div class="space-y-4">
    <?php foreach ($sections as $title => $sectionKeys): ?>
        <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 overflow-hidden">
            <div class="px-5 py-3 bg-cream/50 dark:bg-night/40 border-b border-mist/10">
                <h3 class="font-display font-bold text-ink dark:text-pale"><?= e($title) ?></h3>
            </div>
            <dl class="divide-y divide-mist/10">
                <?php foreach ($sectionKeys as $k):
                    $meta = $questions[$k] ?? [];
                    $val  = $responses[$k] ?? null;
                    $hasVal = !($val === null || $val === '' || (is_array($val) && empty($val)));
                ?>
                <div class="px-5 py-3 grid sm:grid-cols-[1fr_2fr] gap-2">
                    <dt class="text-sm text-mist"><?= e($meta['question'] ?? $k) ?></dt>
                    <dd class="text-sm <?= $hasVal ? 'text-ink dark:text-pale' : 'text-mist italic' ?>">
                        <?= $hasVal ? e(QuestionFormatter::format($k, $val)) : '— nie wypełniono —' ?>
                    </dd>
                </div>
                <?php endforeach; ?>
            </dl>
        </div>
    <?php endforeach; ?>

    <?php require BASE_PATH . '/views/participant/step-12-extras.php'; ?>
</div>

<p class="mt-8 text-center text-sm text-mist">
    Wszystko OK? Klij <strong class="text-ink dark:text-pale"><?= $isAdminEdit ? 'Zapisz' : 'Wyślij ankietę' ?></strong> poniżej.
</p>
