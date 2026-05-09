<?php
/**
 * Sekcja diagnostyczna - widoczna tylko gdy APP_ENV=dev.
 * Liczniki bazy + linki do testowania.
 *
 * @var array{appEnv:string,appUrl:string,phpVer:string,dbStatus:array} $devData
 */
$devData = $devData ?? [];
$dbOk = ($devData['dbStatus']['ok'] ?? false) === true;
?>
<section id="dev" class="bg-paper dark:bg-deep py-12 md:py-16 border-t-4 border-dashed border-primary/40">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8">

        <div class="flex items-center gap-3 mb-6">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-primary/15 text-primary">
                🔧 DEV TOOLS
            </span>
            <p class="text-sm text-mist">Niewidoczne na produkcji (APP_ENV ≠ dev).</p>
        </div>

        <div class="grid md:grid-cols-2 gap-4 md:gap-6 mb-8">
            <div class="rounded-2xl bg-cream dark:bg-night border border-mist/10 p-5">
                <div class="flex items-center gap-2 text-sm font-medium text-mist mb-3">
                    <span class="w-2 h-2 rounded-full bg-secondary"></span>
                    Środowisko
                </div>
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-mist">APP_ENV</dt>
                        <dd class="font-mono text-ink dark:text-pale"><?= e((string) ($devData['appEnv'] ?? '')) ?></dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-mist">APP_URL</dt>
                        <dd class="font-mono text-ink dark:text-pale truncate"><?= e((string) ($devData['appUrl'] ?? '')) ?></dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-mist">PHP</dt>
                        <dd class="font-mono text-ink dark:text-pale"><?= e((string) ($devData['phpVer'] ?? '')) ?></dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl bg-cream dark:bg-night border border-mist/10 p-5">
                <div class="flex items-center gap-2 text-sm font-medium text-mist mb-3">
                    <span class="w-2 h-2 rounded-full <?= $dbOk ? 'bg-secondary' : 'bg-red-500' ?>"></span>
                    Baza danych
                </div>
                <?php if ($dbOk): ?>
                    <dl class="space-y-1.5 text-sm">
                        <?php foreach ((array) ($devData['dbStatus']['counts'] ?? []) as $label => $count): ?>
                            <div class="flex justify-between gap-3">
                                <dt class="text-mist"><?= e((string) $label) ?></dt>
                                <dd class="font-mono text-ink dark:text-pale"><?= (int) $count ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                <?php else: ?>
                    <p class="text-sm text-red-500"><?= e((string) ($devData['dbStatus']['message'] ?? '')) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <h3 class="font-display font-semibold text-lg mb-3 text-ink dark:text-pale">
            Linki do testowania (seed)
        </h3>
        <?php
        $devLinks = [
            ['Wizard - Tomek',         '/p/1111111111111111111111111111111111111111111111111111111111111111', '/p/1111…1111'],
            ['Wizard - Kasia',         '/p/2222222222222222222222222222222222222222222222222222222222222222', '/p/2222…2222'],
            ['Wizard - Bartek',        '/p/3333333333333333333333333333333333333333333333333333333333333333', '/p/3333…3333'],
            ['Wizard - Ola',           '/p/4444444444444444444444444444444444444444444444444444444444444444', '/p/4444…4444'],
            ['Podsumowanie publiczne', '/summary/cafe0000cafe0000cafe0000cafe0000cafe0000cafe0000cafe0000cafe0000', '/summary/cafe0000…cafe0000'],
            ['Health-check',           '/zdrowie', '/zdrowie'],
        ];
        ?>
        <div class="grid gap-2 md:grid-cols-2 lg:grid-cols-3 text-sm">
            <?php foreach ($devLinks as [$label, $href, $shortUrl]): ?>
            <a href="<?= e(url($href)) ?>" class="block rounded-xl bg-cream dark:bg-night border border-mist/15 p-3 hover:border-primary transition">
                <span class="block font-medium text-ink dark:text-pale"><?= e($label) ?></span>
                <span class="block text-xs text-mist font-mono mt-0.5"><?= e($shortUrl) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
