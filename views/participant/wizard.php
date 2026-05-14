<?php
/**
 * Wizard dispatcher - wybiera odpowiedni partial step-N.
 *
 * @var \App\Models\Trip        $trip
 * @var \App\Models\Participant $participant
 * @var int                     $currentStep
 * @var int                     $totalSteps
 * @var array<string,mixed>     $responses
 * @var list<string>            $unavailableDates
 * @var array<string,string>    $preferredWeeks
 * @var array<string,array>     $questions
 * @var bool                    $isAdminEdit
 */
use App\Helpers\Csrf;

$progress = (int) round(($currentStep / $totalSteps) * 100);
$prevStep = max(1, $currentStep - 1);
$nextStep = min($totalSteps, $currentStep + 1);
$accessToken = $participant->accessToken;
?>

<?php if ($isAdminEdit): ?>
    <?php require BASE_PATH . '/views/partials/wizard/admin-banner.php'; ?>
<?php endif; ?>

<!-- Progress bar -->
<div class="bg-paper dark:bg-deep border-b border-mist/15">
    <div class="mx-auto max-w-3xl 3xl:max-w-4xl px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between mb-2 text-sm">
            <span class="font-medium text-ink dark:text-pale">
                Krok <?= $currentStep ?> z <?= $totalSteps ?>
            </span>
            <span class="text-mist font-mono"><?= $progress ?>%</span>
        </div>
        <div class="h-2 rounded-full bg-mist/15 overflow-hidden">
            <div class="h-full bg-primary transition-all duration-500" style="width: <?= $progress ?>%"></div>
        </div>
    </div>
</div>

<section class="mx-auto max-w-3xl 3xl:max-w-4xl px-4 sm:px-6 lg:px-8 py-8 md:py-12"
         data-wizard
         data-token="<?= e($accessToken) ?>"
         data-csrf="<?= e(Csrf::token()) ?>"
         data-save-url="<?= e(url('/p/' . $accessToken . '/save')) ?>">

    <?php
    $stepFile = BASE_PATH . '/views/participant/step-' . $currentStep . '.php';
    if (is_file($stepFile)) {
        require $stepFile;
    } else {
        echo '<div class="p-6 rounded-2xl bg-yellow-100 dark:bg-yellow-950/40 text-sm">';
        echo 'Krok ' . (int) $currentStep . ' jeszcze nie istnieje.';
        echo '</div>';
    }
    ?>

    <!-- Nawigacja kroków -->
    <div class="mt-10 pt-6 border-t border-mist/15 flex flex-wrap items-center justify-between gap-3">
        <?php if ($currentStep > 1): ?>
            <a href="<?= e(url('/p/' . $accessToken . '/wizard/' . $prevStep)) ?>"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-mist/15 text-ink dark:text-pale font-medium hover:bg-mist/25 transition">
                ← Wstecz
            </a>
        <?php else: ?>
            <span></span>
        <?php endif; ?>

        <span class="hidden sm:inline-block text-xs text-mist">
            <span data-save-status>Auto-zapis aktywny</span>
        </span>

        <?php if ($currentStep < $totalSteps): ?>
            <a href="<?= e(url('/p/' . $accessToken . '/wizard/' . $nextStep)) ?>"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-primary-deep text-white font-semibold hover:bg-primary transition shadow-pop">
                Dalej →
            </a>
        <?php else: ?>
            <form method="POST" action="<?= e(url('/p/' . $accessToken . '/submit')) ?>">
                <?= Csrf::field() ?>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-secondary text-white font-bold hover:bg-secondary/90 transition shadow-pop">
                    <?= $isAdminEdit ? 'Zapisz' : 'Wyślij ankietę' ?> ✓
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Save & continue later -->
    <?php if (!$isAdminEdit): ?>
    <div class="mt-4 text-center">
        <a href="<?= e(url('/p/' . $accessToken)) ?>" class="text-sm text-mist hover:text-primary transition">
            Zapisz i dokończ później
        </a>
    </div>
    <?php endif; ?>

</section>
