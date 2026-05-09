<?php
/**
 * Dashboard admina - lista wyjazdów + CTA do nowego wyjazdu.
 *
 * @var \App\Models\Admin $admin
 * @var list<array{trip:\App\Models\Trip,totalParticipants:int,completed:int}> $trips
 * @var string|null $flashSuccess
 * @var string|null $flashError
 */
$trips        = $trips        ?? [];
$flashSuccess = $flashSuccess ?? null;
$flashError   = $flashError   ?? null;
?>
<section class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8 py-10 md:py-14">

    <?php if ($flashSuccess !== null): ?>
        <div class="mb-6 p-4 rounded-2xl bg-secondary/10 border border-secondary/30 text-sm">
            OK <?= e($flashSuccess) ?>
        </div>
    <?php endif; ?>
    <?php if ($flashError !== null): ?>
        <div class="mb-6 p-4 rounded-2xl bg-red-100 dark:bg-red-950/40 border border-red-300 dark:border-red-800 text-sm text-red-700 dark:text-red-300">
            <?= e($flashError) ?>
        </div>
    <?php endif; ?>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="font-display font-bold text-3xl md:text-5xl text-ink dark:text-pale">
                Twoje wyjazdy
            </h1>
            <p class="mt-1 text-mist">Cześć, <?= e($admin->name) ?>. Zarządzaj swoimi wyjazdami w jednym miejscu.</p>
        </div>
        <a href="<?= e(url('/admin/trips/new')) ?>"
           class="inline-flex items-center gap-2 px-5 py-3 rounded-full bg-primary text-white font-semibold hover:bg-primary-dark hover:scale-105 transition shadow-pop self-start">
            +&nbsp;Nowy wyjazd
        </a>
    </div>

    <?php if (empty($trips)): ?>
        <?php require BASE_PATH . '/views/admin/_dashboard-empty.php'; ?>
    <?php else: ?>
        <?php require BASE_PATH . '/views/admin/_dashboard-list.php'; ?>
    <?php endif; ?>
</section>
