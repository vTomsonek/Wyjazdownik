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

// Parser tekstu deal-breakera - wykrywa czy to lista (myslniki/bullets) czy paragraf
$parseBreaker = static function (string $text): array {
    $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];
    // Zlapie: -, *, •, ·, ▪, ‣, ◦, en/em dash na poczatku linii
    $bulletPattern = '/^\s*[-*•·▪‣◦–—]+\s*/u';

    $items = [];
    $bulletCount = 0;
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '') continue;
        if (preg_match($bulletPattern, $trimmed)) {
            $bulletCount++;
            $items[] = trim((string) preg_replace($bulletPattern, '', $trimmed));
        } else {
            $items[] = $trimmed;
        }
    }

    // Traktuj jako liste tylko gdy mamy >= 2 linii i wiekszosc z myslnikami
    $isList = count($items) >= 2 && $bulletCount >= 2;

    return ['is_list' => $isList, 'items' => $items];
};

// Zbierz deal_breakers
$breakers = [];
foreach ($participants as $i => $p) {
    $b = trim((string) ($responses[$p->id]['deal_breakers'] ?? ''));
    if ($b !== '') {
        $parsed = $parseBreaker($b);
        $breakers[] = [
            'name'    => $anonymous ? ('Uczestnik ' . ($i + 1)) : $p->nickname,
            'text'    => $b,
            'color'   => $colors[$p->id] ?? '#FF6B35',
            'avatar'  => !$anonymous ? $p->avatarPath : null,
            'is_list' => $parsed['is_list'],
            'items'   => $parsed['items'],
        ];
    }
}
?>

<section class="section">
    <div class="wrap">

        <header class="sec-head">
            <span class="eyebrow"><span class="iconify" data-icon="ph:chat-circle-dots-bold"></span> Głosy ekipy</span>
            <h2 style="margin-top:18px">💭 Co kto chce, czego unikać</h2>
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
                            <img src="<?= e(asset($plan['avatar'])) ?>" alt="" class="w-10 h-10 rounded-full object-cover border-2" style="border-color: <?= e($plan['color']) ?>" loading="lazy" decoding="async">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold" style="background: <?= e($plan['color']) ?>">
                                <?= e(mb_strtoupper(mb_substr($plan['name'], 0, 1))) ?>
                            </div>
                        <?php endif; ?>
                        <cite class="not-italic font-medium text-ink dark:text-pale text-sm"><?= e($plan['name']) ?></cite>
                    </footer>
                </blockquote>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($breakers)): ?>
            <h3 class="font-display font-bold text-xl md:text-2xl mb-5 text-ink dark:text-pale">
                🚧 Deal-breakery ekipy
            </h3>
            <div class="grid md:grid-cols-2 gap-4 md:gap-5">
                <?php foreach ($breakers as $b): ?>
                <article class="relative rounded-2xl bg-red-50/60 dark:bg-red-950/20 border border-red-200 dark:border-red-900/50 p-4 md:p-5 overflow-hidden">
                    <!-- Czerwony pasek z lewej -->
                    <span class="absolute inset-y-0 left-0 w-1 bg-red-500"></span>

                    <header class="flex items-center gap-3 mb-3 pb-3 border-b border-red-200/70 dark:border-red-900/40 ml-2">
                        <?php if ($b['avatar']): ?>
                            <img src="<?= e(asset($b['avatar'])) ?>" alt=""
                                 class="w-10 h-10 rounded-full object-cover border-2 shrink-0"
                                 style="border-color: <?= e($b['color']) ?>"
                                 loading="lazy" decoding="async">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shrink-0"
                                 style="background: <?= e($b['color']) ?>">
                                <?= e(mb_strtoupper(mb_substr($b['name'], 0, 1))) ?>
                            </div>
                        <?php endif; ?>
                        <span class="font-semibold text-ink dark:text-pale"><?= e($b['name']) ?></span>
                    </header>

                    <div class="ml-2">
                        <?php if ($b['is_list']): ?>
                            <ul class="space-y-2 text-sm md:text-base text-ink dark:text-pale">
                                <?php foreach ($b['items'] as $item): ?>
                                <li class="flex gap-2.5">
                                    <span class="text-red-500 mt-0.5 shrink-0" aria-hidden="true">✗</span>
                                    <span class="flex-1 leading-snug"><?= e($item) ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-sm md:text-base text-ink dark:text-pale leading-relaxed">
                                <?= nl2br(e($b['text'])) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <p class="mt-4 text-xs text-mist text-center">
                To są rzeczy które uniemożliwią ludziom pojechanie. Brać pod uwagę przy wyborze.
            </p>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</section>
