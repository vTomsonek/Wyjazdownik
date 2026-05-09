<?php
/**
 * Sekcja 1: Hero - banner + nazwa + statystyka + avatary.
 * @var \App\Models\Trip $trip
 * @var \App\Services\SummaryAggregator $agg
 */
$participants = $agg->participants();
$completed    = $agg->completedCount();
$total        = $agg->totalCount();
$colors       = $agg->colorMap();
$anonymous    = $agg->isAnonymous();

// Inteligentne dopasowanie wysokosci sekcji do proporcji bannera.
// Odczytujemy wymiary uploadowanego obrazka i ustawiamy aspect-ratio,
// dzieki czemu caly banner jest widoczny niezaleznie od jego rozmiaru.
$bannerAspect = null;
if ($trip->bannerImage) {
    $publicDir = dirname(__DIR__, 3) . '/public/';
    $absPath   = $publicDir . ltrim($trip->bannerImage, '/');
    if (is_file($absPath)) {
        $size = @getimagesize($absPath);
        if ($size && $size[0] > 0 && $size[1] > 0) {
            $bannerAspect = $size[0] / $size[1];
        }
    }
}
?>
<section class="relative overflow-hidden flex items-center"
    <?php if ($bannerAspect !== null): ?>style="aspect-ratio: <?= number_format($bannerAspect, 4, '.', '') ?>;"<?php endif; ?>>
    <?php if ($trip->bannerImage): ?>
        <img src="<?= e(asset($trip->bannerImage)) ?>" alt=""
             class="absolute inset-0 w-full h-full object-cover object-center opacity-40 dark:opacity-30" loading="lazy">
    <?php endif; ?>
    <div class="absolute inset-0 -z-10 bg-gradient-to-br from-cream via-cream to-accent/20 dark:from-night dark:via-night dark:to-secondary/10"></div>

    <div class="relative w-full mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8 py-16 md:py-24 3xl:py-32">

        <span class="inline-block mb-4 px-4 py-1.5 rounded-full text-sm font-semibold bg-primary/10 text-primary backdrop-blur">
            <?= e(date('d.m', strtotime($trip->dateFrom))) ?> – <?= e(date('d.m.Y', strtotime($trip->dateTo))) ?>
        </span>

        <h1 class="font-display font-bold tracking-tight text-5xl md:text-7xl 3xl:text-8xl text-ink dark:text-pale mb-3">
            <?= e($trip->name) ?>
        </h1>

        <?php if (!empty($trip->description)): ?>
            <p class="text-lg md:text-xl 3xl:text-2xl text-mist max-w-3xl mb-6 leading-relaxed">
                <?= nl2br(e($trip->description)) ?>
            </p>
        <?php endif; ?>

        <div class="flex flex-wrap items-center gap-4 text-sm md:text-base text-mist mb-8">
            <span class="font-mono">
                <strong class="text-ink dark:text-pale text-2xl md:text-3xl 3xl:text-4xl"><?= $completed ?></strong>
                / <?= $total ?> wypełniło ankietę
            </span>
            <?php if ($anonymous): ?>
                <span class="px-3 py-1 rounded-full text-xs font-medium bg-mist/15">tryb anonimowy</span>
            <?php endif; ?>
        </div>

        <!-- Avatary uczestnikow -->
        <div class="flex flex-wrap gap-3 md:gap-4">
            <?php foreach ($participants as $i => $p):
                $color = $colors[$p->id] ?? '#FF6B35';
                $displayName = $anonymous ? ('Uczestnik ' . ($i + 1)) : $p->nickname;
                $isCompleted = $p->isCompleted();
            ?>
            <div class="text-center" title="<?= e($displayName . ($isCompleted ? ' - wypełnił' : ' - nie wypełnił')) ?>">
                <div class="relative w-14 h-14 md:w-16 md:h-16 3xl:w-20 3xl:h-20 mb-1.5">
                    <?php if (!$anonymous && $p->avatarPath): ?>
                        <img src="<?= e(asset($p->avatarPath)) ?>" alt=""
                             class="w-full h-full rounded-full object-cover border-4"
                             style="border-color: <?= e($color) ?>">
                    <?php else: ?>
                        <div class="w-full h-full rounded-full flex items-center justify-center text-white font-bold text-lg md:text-xl"
                             style="background: <?= e($color) ?>">
                            <?= e($anonymous ? (string) ($i + 1) : mb_strtoupper(mb_substr($p->nickname, 0, 1))) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($isCompleted): ?>
                        <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-secondary text-white text-xs flex items-center justify-center border-2 border-cream dark:border-night">✓</span>
                    <?php endif; ?>
                </div>
                <div class="text-xs md:text-sm font-medium text-ink dark:text-pale">
                    <?= e($displayName) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
