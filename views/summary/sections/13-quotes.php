<?php
/**
 * Sekcja 13: Plany i deal-breakery - karuzela cytatow + lista.
 * @var \App\Services\SummaryAggregator $agg
 */
$participants = $agg->participants();
$colors = $agg->colorMap();
$responses = $agg->allResponses();
$anonymous = $agg->isAnonymous();

// Zbierz dream_plans (tylko niepuste)
$plans = [];
foreach ($participants as $i => $p) {
    $plan = trim((string) ($responses[$p->id]['dream_plan'] ?? ''));
    if ($plan !== '') {
        $plans[] = [
            'name'  => $anonymous ? ('Uczestnik ' . ($i + 1)) : $p->nickname,
            'text'  => $plan,
            'color' => $colors[$p->id] ?? '#FF6B35',
            'avatar' => !$anonymous ? $p->avatarPath : null,
        ];
    }
}

// Zbierz deal_breakers
$breakers = [];
foreach ($participants as $i => $p) {
    $b = trim((string) ($responses[$p->id]['deal_breakers'] ?? ''));
    if ($b !== '') {
        $breakers[] = [
            'name' => $anonymous ? ('Uczestnik ' . ($i + 1)) : $p->nickname,
            'text' => $b,
        ];
    }
}
?>

<section class="py-16 md:py-24 3xl:py-32 border-t border-mist/15">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8">

        <header class="mb-10 md:mb-14 text-center">
            <span class="inline-block mb-3 px-3 py-1 rounded-full text-xs font-semibold bg-fuchsia-500/15 text-fuchsia-600">SEKCJA 13 / 15</span>
            <h2 class="font-display font-bold text-3xl md:text-5xl 3xl:text-6xl text-ink dark:text-pale mb-3">
                💭 Co kto chce, czego unikać
            </h2>
        </header>

        <?php if (empty($plans) && empty($breakers)): ?>
            <p class="text-center text-mist italic">Nikt nie napisał wymarzonego planu ani deal-breakerów.</p>
        <?php else: ?>

            <?php if (!empty($plans)): ?>
            <h3 class="font-display font-bold text-xl md:text-2xl mb-5 text-ink dark:text-pale">
                🌟 Wymarzone plany
            </h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-5 mb-10">
                <?php foreach ($plans as $plan): ?>
                <blockquote class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 md:p-6 relative">
                    <div class="absolute -top-3 -left-3 text-5xl md:text-6xl opacity-20" style="color: <?= e($plan['color']) ?>">"</div>
                    <p class="font-accent text-lg md:text-xl 3xl:text-2xl text-ink dark:text-pale leading-relaxed mb-4 relative z-10">
                        <?= nl2br(e($plan['text'])) ?>
                    </p>
                    <footer class="flex items-center gap-3 pt-3 border-t border-mist/10">
                        <?php if ($plan['avatar']): ?>
                            <img src="<?= e(asset($plan['avatar'])) ?>" alt="" class="w-10 h-10 rounded-full object-cover border-2" style="border-color: <?= e($plan['color']) ?>">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold" style="background: <?= e($plan['color']) ?>">
                                <?= e(mb_strtoupper(mb_substr($plan['name'], 0, 1))) ?>
                            </div>
                        <?php endif; ?>
                        <cite class="not-italic font-medium text-ink dark:text-pale text-sm">— <?= e($plan['name']) ?></cite>
                    </footer>
                </blockquote>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($breakers)): ?>
            <h3 class="font-display font-bold text-xl md:text-2xl mb-5 text-ink dark:text-pale">
                🚧 Deal-breakery ekipy
            </h3>
            <div class="rounded-2xl bg-red-50 dark:bg-red-950/30 border border-red-300 dark:border-red-800 p-5 md:p-6">
                <ul class="space-y-3">
                    <?php foreach ($breakers as $b): ?>
                    <li class="text-sm md:text-base">
                        <strong class="text-red-700 dark:text-red-300 font-semibold"><?= e($b['name']) ?>:</strong>
                        <span class="text-ink dark:text-pale"><?= nl2br(e($b['text'])) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <p class="mt-4 text-xs text-mist">
                    To są rzeczy które uniemożliwią ludziom pojechanie. Brać pod uwagę przy wyborze.
                </p>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</section>
