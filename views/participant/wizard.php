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

<!-- Progress bar landing v2 -->
<div style="background: var(--surface); border-bottom: 1px solid var(--line); position: sticky; top: 64px; z-index: 30;">
    <div class="wrap" style="max-width:820px; padding-top:16px; padding-bottom:16px">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; font-size:13px">
            <span style="font-weight:700; color: var(--heading); display:inline-flex; align-items:center; gap:8px">
                <span class="iconify" data-icon="ph:list-numbers-bold" style="font-size:16px"></span>
                Krok <?= $currentStep ?> z <?= $totalSteps ?>
            </span>
            <span style="color: var(--fg-2); font-family: ui-monospace, monospace; font-weight:700"><?= $progress ?>%</span>
        </div>
        <div style="height:6px; border-radius:999px; background: rgba(127,127,127,.15); overflow:hidden">
            <div style="height:100%; border-radius:999px; background: linear-gradient(90deg, var(--orange), var(--sun)); transition: width .5s; width: <?= $progress ?>%"></div>
        </div>
    </div>
</div>

<section class="section" style="padding-top:48px; padding-bottom:48px"
         data-wizard
         data-token="<?= e($accessToken) ?>"
         data-csrf="<?= e(Csrf::token()) ?>"
         data-save-url="<?= e(url('/p/' . $accessToken . '/save')) ?>">
    <div class="wrap" style="max-width:820px">

    <?php
    $stepFile = BASE_PATH . '/views/participant/step-' . $currentStep . '.php';
    if (is_file($stepFile)) {
        require $stepFile;
    } else {
        echo '<div style="padding:24px;border-radius:16px;background:rgba(255,210,63,.15);font-size:14px">';
        echo 'Krok ' . (int) $currentStep . ' jeszcze nie istnieje.';
        echo '</div>';
    }
    ?>

    <!-- Nawigacja kroków landing v2 -->
    <div style="margin-top:48px; padding-top:24px; border-top: 1px solid var(--line); display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px">
        <?php if ($currentStep > 1): ?>
            <a class="btn btn-ghost" href="<?= e(url('/p/' . $accessToken . '/wizard/' . $prevStep)) ?>">
                <span class="iconify" data-icon="ph:arrow-left-bold"></span>
                Wstecz
            </a>
        <?php else: ?>
            <span></span>
        <?php endif; ?>

        <span class="hidden sm:inline-flex" style="font-size:12px; color: var(--fg-3); align-items:center; gap:6px">
            <span class="iconify" data-icon="ph:cloud-check-bold" style="font-size:14px"></span>
            <span data-save-status>Auto-zapis aktywny</span>
        </span>

        <?php if ($currentStep < $totalSteps): ?>
            <a class="btn btn-primary" href="<?= e(url('/p/' . $accessToken . '/wizard/' . $nextStep)) ?>">
                Dalej
                <span class="iconify" data-icon="ph:arrow-right-bold"></span>
            </a>
        <?php else: ?>
            <form method="POST" action="<?= e(url('/p/' . $accessToken . '/submit')) ?>">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-primary" style="background: var(--teal); box-shadow: 0 16px 34px rgba(14,155,170,0.34)">
                    <span class="iconify" data-icon="ph:check-circle-bold"></span>
                    <?= $isAdminEdit ? 'Zapisz' : 'Wyślij ankietę' ?>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Save & continue later -->
    <?php if (!$isAdminEdit): ?>
    <div style="margin-top:18px; text-align:center">
        <a href="<?= e(url('/p/' . $accessToken)) ?>" style="font-size:13px; color: var(--fg-3); text-decoration:none; display:inline-flex; align-items:center; gap:6px">
            <span class="iconify" data-icon="ph:floppy-disk-bold" style="font-size:14px"></span>
            Zapisz i dokończ później
        </a>
    </div>
    <?php endif; ?>

    </div>
</section>
