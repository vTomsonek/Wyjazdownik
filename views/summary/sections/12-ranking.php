<?php
/**
 * Sekcja 12: Ranking ekipy - karty z odznakami (jewel of the crown).
 * @var \App\Services\SummaryAggregator $agg
 */
use App\Services\MapColorService;
use App\Services\RankingService;

$awards = (new RankingService($agg))->awardAll();
$anonymous = $agg->isAnonymous();

// Dla kazdej odznaki - dolacz kolor zwyciezcy
$gradient = static function (string $color): string {
    return 'background: linear-gradient(135deg, ' . $color . '33 0%, ' . $color . '0d 100%);';
};
?>

<section class="bg-gradient-to-br from-ink via-deep to-ink text-pale py-16 md:py-24 3xl:py-32 relative overflow-hidden border-t border-mist/15">
    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, #FFD23F 1px, transparent 1.5px); background-size: 32px 32px;"></div>

    <div class="relative mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8">

        <header class="mb-10 md:mb-14 text-center max-w-3xl mx-auto">
            <span class="inline-block mb-3 px-3 py-1 rounded-full text-xs font-semibold bg-accent/20 text-accent">SEKCJA 12 / 15 &middot; Klejnot korony</span>
            <h2 class="font-display font-bold text-4xl md:text-6xl 3xl:text-7xl mb-4">
                🏆 Ranking ekipy
            </h2>
            <p class="text-mist text-lg md:text-xl">
                Algorytm na podstawie odpowiedzi przyznał każdemu odznakę. Czas na śmiech.
            </p>
        </header>

        <?php if (empty($awards)): ?>
            <p class="text-center text-mist italic">Nie udało się przyznać żadnych odznak - ekipa jeszcze nie wypełniła ankiety.</p>
        <?php else: ?>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-5">
                <?php foreach ($awards as $a):
                    $winners = $a['winners'];
                    $first   = $winners[0];
                    $color   = MapColorService::forToken($first->accessToken);
                ?>
                <div class="rounded-2xl p-5 md:p-6 border border-white/10 hover:scale-[1.02] transition"
                     style="<?= $gradient($color) ?>">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl md:text-6xl shrink-0"><?= $a['icon'] ?></div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs uppercase tracking-wider text-pale/70 font-semibold mb-1">
                                <?= e($a['name']) ?>
                            </div>
                            <div class="font-display font-bold text-xl md:text-2xl mb-2 truncate">
                                <?php
                                $names = array_map(static fn($w, $i) => $anonymous ? ('Uczestnik ' . ($i + 1)) : $w->nickname, $winners, array_keys($winners));
                                echo e(implode(' & ', $names));
                                ?>
                            </div>
                            <p class="text-xs md:text-sm text-pale/60 leading-snug">
                                <?= e($a['description']) ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <p class="text-center mt-12 text-mist max-w-xl mx-auto">
                <span class="font-accent text-2xl md:text-3xl text-accent">
                    Zsumowano <?= count($awards) ?> odznak dla ekipy.
                </span>
                <br>
                Pełna lista możliwych odznak: ~22 - jedna osoba może mieć wiele.
            </p>

        <?php endif; ?>
    </div>
</section>
