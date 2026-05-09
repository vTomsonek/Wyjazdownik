<?php
/**
 * Read-only podgląd odpowiedzi uczestnika.
 * Edycja przez wizard (z banerem "edytujesz jako admin") - dorzucona w ETAPIE 5.
 *
 * @var \App\Models\Trip        $trip
 * @var \App\Models\Participant $participant
 * @var array<string,mixed>     $responses
 */
$responses = $responses ?? [];

// Pomocnicza funkcja: format wartości dla wyświetlenia
$formatValue = static function (mixed $value): string {
    if ($value === null) return '<span class="text-mist italic">— brak —</span>';
    if (is_bool($value)) return $value ? '✓ tak' : '✗ nie';
    if (is_array($value)) {
        if (empty($value)) return '<span class="text-mist italic">— pusta lista —</span>';
        // Asoc array (np. languages)?
        if (array_keys($value) !== range(0, count($value) - 1)) {
            $items = [];
            foreach ($value as $k => $v) {
                $items[] = '<strong>' . e((string) $k) . ':</strong> ' . e((string) $v);
            }
            return implode(', ', $items);
        }
        // Lista
        return implode(', ', array_map('e', array_map(static fn($x) => (string) $x, $value)));
    }
    return nl2br(e((string) $value));
};
?>
<section class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-10 md:py-14">

    <a href="<?= e(url('/admin/trips/' . $trip->id . '/participants')) ?>"
       class="text-sm text-mist hover:text-primary transition mb-4 inline-flex items-center gap-1">
        ← Wróć do uczestników wyjazdu "<?= e($trip->name) ?>"
    </a>

    <div class="flex items-center gap-4 mb-2">
        <?php if ($participant->avatarPath): ?>
            <img src="<?= e(asset($participant->avatarPath)) ?>" alt="" class="w-16 h-16 rounded-full object-cover border-2 border-mist/15">
        <?php else: ?>
            <div class="w-16 h-16 rounded-full bg-primary/15 text-primary font-bold flex items-center justify-center text-2xl">
                <?= e(mb_strtoupper(mb_substr($participant->nickname, 0, 1))) ?>
            </div>
        <?php endif; ?>
        <div>
            <h1 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale">
                Odpowiedzi: <?= e($participant->nickname) ?>
            </h1>
            <p class="text-mist text-sm">
                <?= $participant->isCompleted() ? 'Wypełnione ' . e(date('d.m.Y', strtotime((string) $participant->completedAt))) : 'Niewypełnione' ?>
            </p>
        </div>
    </div>

    <div class="my-6 p-4 rounded-2xl bg-accent/15 border border-accent/40 text-sm text-ink dark:text-pale">
        <strong>Tryb tylko-do-odczytu.</strong> Edycja odpowiedzi przez interfejs wizarda będzie dostępna w ETAPIE 5
        - z auto-zapisem zmian do <code class="px-1.5 py-0.5 rounded bg-primary/10 text-primary text-xs">trip_responses_audit</code>.
    </div>

    <?php if (empty($responses)): ?>
        <div class="rounded-2xl bg-paper dark:bg-deep border-2 border-dashed border-mist/30 p-8 text-center">
            <p class="text-mist">Ten uczestnik jeszcze nic nie wypełnił.</p>
        </div>
    <?php else: ?>
        <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream dark:bg-night text-mist text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-left">Pytanie (klucz)</th>
                        <th class="px-4 py-3 text-left">Odpowiedź</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 0; foreach ($responses as $key => $value): $i++; ?>
                    <tr class="<?= $i % 2 === 0 ? 'bg-cream/40 dark:bg-night/40' : '' ?> border-t border-mist/10">
                        <td class="px-4 py-3 align-top">
                            <code class="text-xs font-mono text-primary"><?= e($key) ?></code>
                        </td>
                        <td class="px-4 py-3 text-ink dark:text-pale leading-relaxed">
                            <?= $formatValue($value) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p class="mt-4 text-xs text-mist">
            Łącznie <?= count($responses) ?> odpowiedzi.
            Pełne nazwy pytań i polskie etykiety opcji pojawią się w ETAPIE 5 (przez QuestionLabels.php).
        </p>
    <?php endif; ?>

    <!-- Pinezki / niedostępne dni - tylko liczniki w tym etapie -->
    <div class="grid sm:grid-cols-2 gap-3 mt-6">
        <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-4">
            <div class="text-xs text-mist mb-1">📅 Niedostępne dni</div>
            <div class="text-2xl font-display font-bold text-ink dark:text-pale">
                <?= $participant->unavailableDatesCount() ?>
            </div>
        </div>
        <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-4">
            <div class="text-xs text-mist mb-1">🗺️ Pinezki / trasy / obszary na mapie</div>
            <div class="text-2xl font-display font-bold text-ink dark:text-pale">
                <?= $participant->mapPinsCount() ?>
            </div>
        </div>
    </div>
</section>
