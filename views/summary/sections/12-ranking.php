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

<section class="section section--ink" style="position:relative;overflow:hidden;color:var(--pale)">
    <div style="position:absolute;inset:0;opacity:.10;background-image:radial-gradient(circle, #FFD23F 1px, transparent 1.5px);background-size:32px 32px;pointer-events:none"></div>

    <div class="wrap" style="position:relative">

        <header class="sec-head">
            <span class="eyebrow" style="background:rgba(255,210,63,.18);color:#FFD23F;border-color:rgba(255,210,63,.30)">
                <span class="iconify" data-icon="ph:crown-bold"></span> Klejnot korony
            </span>
            <h2 style="margin-top:18px;color:#fff">🏆 Ranking ekipy</h2>
            <p style="color:rgba(255,255,255,.72)">Algorytm na podstawie odpowiedzi przyznał każdemu odznakę. Czas na śmiech.</p>
        </header>

        <?php if (empty($awards)): ?>
            <p class="text-center text-mist italic">Nie udało się przyznać żadnych odznak - ekipa jeszcze nie wypełniła ankiety.</p>
        <?php else: ?>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-5">
                <?php foreach ($awards as $a):
                    $winners = $a['winners'];
                    $first   = $winners[0];
                    $color   = MapColorService::forParticipant($first);
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
