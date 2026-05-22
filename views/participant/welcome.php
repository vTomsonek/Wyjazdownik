<?php
/**
 * Strona powitalna uczestnika - po otwarciu linku /p/{token}.
 *
 * @var \App\Models\Trip        $trip
 * @var \App\Models\Participant $participant
 * @var bool                    $isAdminEdit
 */
$isCompleted = $participant->isCompleted();
$startUrl    = url('/p/' . $participant->accessToken . '/wizard/1');

// Inteligentne dopasowanie wysokosci bannera do jego proporcji.
$bannerAspect = null;
if ($trip->bannerImage) {
    $publicDir = dirname(__DIR__, 2) . '/public/';
    $absPath   = $publicDir . ltrim($trip->bannerImage, '/');
    if (is_file($absPath)) {
        $size = @getimagesize($absPath);
        if ($size && $size[0] > 0 && $size[1] > 0) {
            $bannerAspect = $size[0] / $size[1];
        }
    }
}
?>

<?php if ($isAdminEdit): ?>
    <?php require BASE_PATH . '/views/partials/wizard/admin-banner.php'; ?>
<?php endif; ?>

<section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-10 md:py-16 3xl:py-24">

    <?php if ($trip->bannerImage): ?>
        <div class="w-full mb-8 rounded-3xl overflow-hidden shadow-pop bg-paper/40 dark:bg-deep/40"
             style="<?php if ($bannerAspect !== null): ?>aspect-ratio: <?= number_format($bannerAspect, 4, '.', '') ?>;<?php endif; ?> max-height: 480px;">
            <img src="<?= e(asset($trip->bannerImage)) ?>" alt=""
                 class="w-full h-full object-contain" fetchpriority="high" decoding="async">
        </div>
    <?php endif; ?>

    <div class="text-center">
        <span class="inline-block mb-4 px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary">
            <?= e(date('d.m', strtotime($trip->dateFrom))) ?> – <?= e(date('d.m.Y', strtotime($trip->dateTo))) ?>
        </span>

        <h1 class="font-display font-bold text-3xl md:text-5xl 3xl:text-6xl text-ink dark:text-pale mb-3">
            Cześć, <?= e($participant->nickname) ?>!
        </h1>
        <p class="text-lg md:text-xl text-mist max-w-xl mx-auto">
            Pomóż nam zaplanować <strong class="text-ink dark:text-pale"><?= e($trip->name) ?></strong>.
        </p>

        <?php if (!empty($trip->description)): ?>
            <div class="mt-6 p-5 md:p-6 rounded-2xl bg-paper dark:bg-deep border border-mist/15 text-left">
                <p class="text-mist leading-relaxed whitespace-pre-line"><?= e($trip->description) ?></p>
            </div>
        <?php endif; ?>

        <div class="mt-8">
            <?php if ($isCompleted): ?>
                <p class="text-mist mb-4">
                    Już wypełniłeś tę ankietę
                    <span class="font-mono"><?= e(date('d.m.Y', strtotime((string) $participant->completedAt))) ?></span>.
                    Możesz przejrzeć i zedytować odpowiedzi.
                </p>
                <div class="flex flex-wrap gap-3 justify-center">
                    <a href="<?= e($startUrl) ?>"
                       class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-primary-deep text-white font-semibold hover:bg-primary hover:scale-105 transition shadow-pop">
                        Edytuj odpowiedzi →
                    </a>
                    <a href="<?= e(url('/p/' . $participant->accessToken . '/atrakcje')) ?>"
                       class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-paper dark:bg-deep border-2 border-secondary text-secondary font-semibold hover:bg-secondary hover:text-white transition">
                        🗺️ Mapa atrakcji ekipy
                    </a>
                </div>
            <?php else: ?>
                <a href="<?= e($startUrl) ?>"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-primary-deep text-white font-semibold text-lg hover:bg-primary hover:scale-105 transition shadow-pop">
                    Zacznij wypełniać →
                </a>
                <p class="mt-3 text-sm text-mist">12 krótkich kroków, możesz przerwać i wrócić.</p>
            <?php endif; ?>
        </div>

        <!-- Mascotka jako akcent -->
        <div class="mt-10 w-20 h-20 mx-auto opacity-70 animate-float-slow">
            <?php require BASE_PATH . '/views/partials/mascot.php'; ?>
        </div>
    </div>
</section>
