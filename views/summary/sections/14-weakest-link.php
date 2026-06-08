<?php
/**
 * Sekcja 14: Realne parametry wyjazdu - najslabsze ogniwo.
 * @var \App\Services\SummaryAggregator $agg
 */
use App\Helpers\QuestionLabels;
use App\Services\RecommendationService;

$rec = (new RecommendationService($agg))->weakestLink();

$comfortLabels   = QuestionLabels::get('comfort_level')['options']  ?? [];
$climLabels      = QuestionLabels::get('climate_tolerance')['options'] ?? [];
$transportLabels = QuestionLabels::get('transport_modes')['options']   ?? [];

$fmt = static fn($n): string => is_numeric($n) ? number_format((int) $n, 0, ',', ' ') : '—';
?>

<section class="section section--cream" style="background:linear-gradient(135deg, rgba(255,107,53,.08), rgba(255,210,63,.10) 50%, rgba(255,107,53,.08))">
    <div class="wrap">

        <header class="sec-head">
            <span class="eyebrow" style="background:rgba(255,107,53,.16);color:#ED5320;border-color:rgba(255,107,53,.30)">
                <span class="iconify" data-icon="ph:warning-circle-bold"></span> Brutalna prawda
            </span>
            <h2 style="margin-top:18px">🎯 Realne parametry wyjazdu</h2>
            <p>Najsłabsze ogniwo wyznacza realny zakres tego, co ekipa zrobi. Bez owijania w bawełnę.</p>
        </header>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">

            <!-- Tempo / km dziennie -->
            <div class="rounded-2xl bg-paper dark:bg-deep border border-rose-300 dark:border-rose-800 p-5 md:p-6">
                <div class="text-xs uppercase tracking-wider text-mist font-semibold mb-2">Tempo</div>
                <div class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale mb-1">
                    <?= $rec['paceKm'] !== null ? 'Max ' . $rec['paceKm'] . ' km/dzień' : '— brak danych —' ?>
                </div>
                <p class="text-sm text-mist">Najsłabsze ogniwo. Nie planujcie 20 km tras.</p>
            </div>

            <!-- Klimat -->
            <div class="rounded-2xl bg-paper dark:bg-deep border border-rose-300 dark:border-rose-800 p-5 md:p-6">
                <div class="text-xs uppercase tracking-wider text-mist font-semibold mb-2">Klimat</div>
                <?php if (!empty($rec['climateOk'])): ?>
                    <div class="text-base md:text-lg font-medium text-ink dark:text-pale mb-1">
                        <?= e(implode(', ', array_map(static fn($k) => $climLabels[$k] ?? $k, $rec['climateOk']))) ?>
                    </div>
                    <p class="text-sm text-mist">Wszyscy są OK z tym zakresem.</p>
                <?php else: ?>
                    <div class="text-lg font-medium text-rose-600 dark:text-rose-400 mb-1">Brak konsensusu</div>
                    <p class="text-sm text-mist">Trzeba znaleźć kompromis (np. najczęstszy klimat).</p>
                <?php endif; ?>
            </div>

            <!-- Transport -->
            <div class="rounded-2xl bg-paper dark:bg-deep border border-rose-300 dark:border-rose-800 p-5 md:p-6">
                <div class="text-xs uppercase tracking-wider text-mist font-semibold mb-2">Transport</div>
                <?php if (!empty($rec['transportOk'])): ?>
                    <div class="text-base md:text-lg font-medium text-ink dark:text-pale mb-1">
                        <?= e(implode(', ', array_map(static fn($k) => $transportLabels[$k] ?? $k, $rec['transportOk']))) ?>
                    </div>
                    <p class="text-sm text-mist">Wszyscy się zgodzą tylko na ten transport.</p>
                <?php else: ?>
                    <div class="text-lg font-medium text-rose-600 dark:text-rose-400 mb-1">Brak konsensusu</div>
                    <p class="text-sm text-mist">Każdy ma inne preferencje - dyskusja konieczna.</p>
                <?php endif; ?>
            </div>

            <!-- Komfort -->
            <div class="rounded-2xl bg-paper dark:bg-deep border border-rose-300 dark:border-rose-800 p-5 md:p-6">
                <div class="text-xs uppercase tracking-wider text-mist font-semibold mb-2">Min. komfort</div>
                <div class="font-display font-bold text-xl md:text-2xl text-ink dark:text-pale mb-1">
                    <?= e($comfortLabels[$rec['comfortMin'] ?? ''] ?? '— brak danych —') ?>
                </div>
                <p class="text-sm text-mist">Najwyższe wymaganie z ekipy. Niżej nie schodzimy.</p>
            </div>

            <!-- Budzet realny -->
            <div class="rounded-2xl bg-paper dark:bg-deep border border-rose-300 dark:border-rose-800 p-5 md:p-6">
                <div class="text-xs uppercase tracking-wider text-mist font-semibold mb-2">Realny budżet</div>
                <div class="font-display font-bold text-2xl md:text-3xl text-primary mb-1">
                    <?= $rec['budgetReal'] !== null ? $fmt($rec['budgetReal']) . ' zł' : '— brak —' ?>
                </div>
                <p class="text-sm text-mist">Najniższy w ekipie. Planujcie z myślą o nim.</p>
            </div>

            <!-- Dni -->
            <div class="rounded-2xl bg-paper dark:bg-deep border border-rose-300 dark:border-rose-800 p-5 md:p-6">
                <div class="text-xs uppercase tracking-wider text-mist font-semibold mb-2">Maksymalny czas</div>
                <div class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale mb-1">
                    <?= $rec['durationDays'] !== null ? $rec['durationDays'] . ' dni' : '— brak —' ?>
                </div>
                <p class="text-sm text-mist">Najkrótszy okres jaki ekipa może poświęcić.</p>
            </div>

            <!-- Paszport -->
            <div class="md:col-span-2 lg:col-span-3 rounded-2xl <?= $rec['passportAll'] === false ? 'bg-amber-100 dark:bg-amber-950/40 border-2 border-amber-400 dark:border-amber-700' : 'bg-paper dark:bg-deep border border-rose-300 dark:border-rose-800' ?> p-5 md:p-6">
                <div class="text-xs uppercase tracking-wider text-mist font-semibold mb-2">Paszport</div>
                <?php if ($rec['passportAll'] === true): ?>
                    <div class="font-display font-bold text-xl md:text-2xl text-secondary">✓ Wszyscy mają</div>
                    <p class="text-sm text-mist">Świat stoi otworem - poza UE również.</p>
                <?php elseif ($rec['passportAll'] === false): ?>
                    <div class="font-display font-bold text-xl md:text-2xl text-amber-700 dark:text-amber-300">⚠️ Co najmniej 1 osoba bez paszportu</div>
                    <p class="text-sm text-mist mt-1">Wybierzcie kraj UE albo zachęćcie tę osobę do wyrobienia paszportu z wyprzedzeniem.</p>
                <?php else: ?>
                    <div class="font-display font-bold text-xl md:text-2xl text-mist">— brak danych —</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
