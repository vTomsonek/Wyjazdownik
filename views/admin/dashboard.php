<?php
/**
 * Admin dashboard - lista wyjazdów + CTA nowy. Landing v2 design.
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
<section class="admin-page">
    <div class="wrap">

        <?php if ($flashSuccess !== null): ?>
            <div class="admin-flash admin-flash--success">
                <span class="iconify" data-icon="ph:check-circle-fill"></span>
                <span><?= e($flashSuccess) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($flashError !== null): ?>
            <div class="admin-flash admin-flash--error">
                <span class="iconify" data-icon="ph:warning-circle-fill"></span>
                <span><?= e($flashError) ?></span>
            </div>
        <?php endif; ?>

        <header class="admin-head">
            <div>
                <h1 class="h-title">Twoje wyjazdy</h1>
                <p class="h-sub">Cześć, <b><?= e($admin->name) ?></b>. Zarządzaj wszystkim w jednym miejscu.</p>
            </div>
            <a href="<?= e(url('/admin/trips/new')) ?>" class="btn btn-primary h-cta">
                <span class="iconify" data-icon="ph:plus-bold"></span> Nowy wyjazd
            </a>
        </header>

        <?php if (empty($trips)): ?>
            <?php require BASE_PATH . '/views/admin/_dashboard-empty.php'; ?>
        <?php else: ?>
            <?php require BASE_PATH . '/views/admin/_dashboard-list.php'; ?>
        <?php endif; ?>

    </div>
</section>
