<?php
/**
 * Lista kafelków wyjazdów (grid).
 * @var list<array{trip:\App\Models\Trip,totalParticipants:int,completed:int}> $trips
 */
?>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php foreach ($trips as $entry):
        $trip       = $entry['trip'];
        $total      = $entry['totalParticipants'];
        $completed  = $entry['completed'];
        $progress   = $total > 0 ? (int) round($completed / $total * 100) : 0;
    ?>
    <article class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 overflow-hidden hover:shadow-pop transition flex flex-col">

        <?php if ($trip->bannerImage): ?>
            <img src="<?= e(asset($trip->bannerImage)) ?>" alt=""
                 class="w-full h-32 object-cover" loading="lazy">
        <?php else: ?>
            <div class="w-full h-32 bg-gradient-to-br from-primary/30 to-accent/30 flex items-center justify-center text-5xl">
                <?= ['plaza' => 'PL'][''] ?? '' ?>
                <span aria-hidden="true">🏖️</span>
            </div>
        <?php endif; ?>

        <div class="p-5 flex-1 flex flex-col">
            <?php if (!$trip->isActive): ?>
                <span class="inline-block mb-2 px-2 py-0.5 rounded-full text-xs font-medium bg-mist/20 text-mist self-start">archiwum</span>
            <?php endif; ?>

            <h2 class="font-display font-bold text-xl text-ink dark:text-pale mb-1">
                <?= e($trip->name) ?>
            </h2>
            <p class="text-xs text-mist mb-3 font-mono">
                <?= e(date('d.m', strtotime($trip->dateFrom))) ?> – <?= e(date('d.m.Y', strtotime($trip->dateTo))) ?>
            </p>

            <div class="mb-4">
                <div class="flex justify-between text-xs text-mist mb-1">
                    <span>Wypełnione</span>
                    <span class="font-mono"><?= $completed ?>/<?= $total ?></span>
                </div>
                <div class="h-2 rounded-full bg-mist/15 overflow-hidden">
                    <div class="h-full bg-primary transition-all" style="width: <?= $progress ?>%"></div>
                </div>
            </div>

            <div class="mt-auto flex flex-wrap gap-2">
                <a href="<?= e(url('/admin/trips/' . $trip->id . '/participants')) ?>"
                   class="flex-1 text-center px-3 py-2 rounded-full bg-primary text-white text-sm font-medium hover:bg-primary-dark transition">
                    Uczestnicy
                </a>
                <a href="<?= e(url('/admin/trips/' . $trip->id . '/edit')) ?>"
                   class="px-3 py-2 rounded-full bg-mist/15 text-ink dark:text-pale text-sm font-medium hover:bg-primary/15 transition"
                   title="Edytuj wyjazd">
                    Edytuj
                </a>
                <a href="<?= e(url('/summary/' . $trip->summaryPublicToken)) ?>" target="_blank"
                   class="px-3 py-2 rounded-full bg-mist/15 text-ink dark:text-pale text-sm font-medium hover:bg-primary/15 transition"
                   title="Podsumowanie publiczne (TV)">
                    TV
                </a>
            </div>
        </div>
    </article>
    <?php endforeach; ?>
</div>
