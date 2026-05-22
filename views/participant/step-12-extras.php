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

<!-- Mapa atrakcji - link do nowej osobnej funkcji -->
<?php
$placesUrl = isset($participant) ? url('/p/' . $participant->accessToken . '/atrakcje') : null;
?>
<div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 overflow-hidden">
    <div class="px-5 py-3 bg-cream/50 dark:bg-night/40 border-b border-mist/10">
        <h3 class="font-display font-bold text-ink dark:text-pale">🗺️ Atrakcje</h3>
    </div>
    <div class="px-5 py-4 text-sm">
        <p class="text-mist mb-3">
            Konkretne miejsca, oceny i propozycje tras są w osobnej interaktywnej mapie -
            możesz wracać i dodawać miejsca w dowolnym momencie.
        </p>
        <?php if ($placesUrl !== null): ?>
        <a href="<?= e($placesUrl) ?>" target="_blank"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary-deep text-white font-semibold text-sm hover:bg-primary transition">
            Otwórz mapę atrakcji →
        </a>
        <?php endif; ?>
    </div>
</div>
