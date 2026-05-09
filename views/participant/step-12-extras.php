<?php
/**
 * Sekcje "Dostepnosc" + "Mapa pomyslow" w review (krok 12).
 * Dane spoza participant_responses (oddzielne tabele) - dlatego osobny partial.
 *
 * @var \App\Models\Trip        $trip
 * @var list<string>            $unavailableDates
 * @var array<string,string>    $preferredWeeks
 * @var list<\App\Models\MapPin> $mapPins
 */
$unavailable = $unavailableDates ?? [];
$weeks       = $preferredWeeks   ?? [];
$pins        = $mapPins          ?? [];
$pinTypeLabels = ['marker' => '📍 Pinezka', 'polyline' => '➡️ Trasa', 'polygon' => '⬡ Obszar'];
?>

<!-- Dostepnosc -->
<div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 overflow-hidden">
    <div class="px-5 py-3 bg-cream/50 dark:bg-night/40 border-b border-mist/10">
        <h3 class="font-display font-bold text-ink dark:text-pale">📅 Dostępność</h3>
    </div>
    <div class="px-5 py-4 text-sm">
        <?php if ($trip->calendarMode === 'block_unavailable'): ?>
            <?php if (empty($unavailable)): ?>
                <span class="text-mist italic">— jesteś dostępny w całym oknie czasowym —</span>
            <?php else: ?>
                <p class="text-mist mb-2">Zaznaczyłeś <strong class="text-ink dark:text-pale font-mono"><?= count($unavailable) ?></strong> dni niedostępnych:</p>
                <p class="text-ink dark:text-pale font-mono text-xs leading-relaxed">
                    <?php
                    $sorted = $unavailable; sort($sorted);
                    echo e(implode(', ', array_map(static fn($d) => date('d.m', strtotime($d)), $sorted)));
                    ?>
                </p>
            <?php endif; ?>
        <?php else: ?>
            <?php
            $yes = array_filter($weeks, fn($p) => $p === 'yes');
            $may = array_filter($weeks, fn($p) => $p === 'maybe');
            $no  = array_filter($weeks, fn($p) => $p === 'no');
            ?>
            <?php if (empty($weeks)): ?>
                <span class="text-mist italic">— jeszcze nic nie zaznaczone —</span>
            <?php else: ?>
                <div class="flex flex-wrap gap-3 text-mist">
                    <span>✅ Pasuje: <strong class="text-ink dark:text-pale font-mono"><?= count($yes) ?></strong></span>
                    <span>🤔 Może: <strong class="text-ink dark:text-pale font-mono"><?= count($may) ?></strong></span>
                    <span>❌ Nie pasuje: <strong class="text-ink dark:text-pale font-mono"><?= count($no) ?></strong></span>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Mapa pomyslow -->
<div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 overflow-hidden">
    <div class="px-5 py-3 bg-cream/50 dark:bg-night/40 border-b border-mist/10">
        <h3 class="font-display font-bold text-ink dark:text-pale">🗺️ Mapa pomysłów</h3>
    </div>
    <?php if (empty($pins)): ?>
        <div class="px-5 py-4 text-sm text-mist italic">— nie zaznaczyłeś żadnej pinezki ani trasy —</div>
    <?php else:
        $pinsJson = json_encode(array_map(static fn($p) => $p->toArray(), $pins), JSON_UNESCAPED_UNICODE);
    ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" crossorigin="">
        <div id="review-map"
             data-review-pins='<?= e($pinsJson) ?>'
             style="height: 320px;"></div>
        <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js" crossorigin=""></script>
        <script src="<?= e(asset('assets/js/map-utils.js')) ?>"></script>
        <script src="<?= e(asset('assets/js/review-map.js')) ?>"></script>
        <ul class="divide-y divide-mist/10">
            <?php foreach ($pins as $pin):
                $typeLabel = $pinTypeLabels[$pin->pinType] ?? $pin->pinType;
            ?>
            <li class="px-5 py-3 grid sm:grid-cols-[1fr_2fr] gap-2 items-start">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3 h-3 rounded-full shrink-0" style="background:<?= e($pin->color ?? '#FF6B35') ?>"></span>
                    <span class="text-sm text-mist"><?= e($typeLabel) ?></span>
                </div>
                <div>
                    <div class="text-sm font-medium text-ink dark:text-pale">
                        <?= e($pin->label ?? '(bez etykiety)') ?>
                    </div>
                    <?php if (!empty($pin->description)): ?>
                        <div class="text-xs text-mist mt-0.5"><?= e($pin->description) ?></div>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <div class="px-5 py-3 text-xs text-mist border-t border-mist/10">
            Łącznie <strong class="text-ink dark:text-pale font-mono"><?= count($pins) ?></strong> elementów na mapie.
        </div>
    <?php endif; ?>
</div>
