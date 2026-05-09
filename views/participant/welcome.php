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
?>

<?php if ($isAdminEdit): ?>
    <?php require BASE_PATH . '/views/partials/wizard/admin-banner.php'; ?>
<?php endif; ?>

<section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-10 md:py-16 3xl:py-24">

    <?php if ($trip->bannerImage): ?>
        <img src="<?= e(asset($trip->bannerImage)) ?>" alt=""
             class="w-full h-48 md:h-64 object-cover rounded-3xl mb-8 shadow-pop" loading="lazy">
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
                <a href="<?= e($startUrl) ?>"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-primary text-white font-semibold hover:bg-primary-dark hover:scale-105 transition shadow-pop">
                    Edytuj odpowiedzi →
                </a>
            <?php else: ?>
                <a href="<?= e($startUrl) ?>"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-primary text-white font-semibold text-lg hover:bg-primary-dark hover:scale-105 transition shadow-pop">
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
