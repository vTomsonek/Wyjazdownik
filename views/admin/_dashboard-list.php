<?php
/**
 * Grid kart wyjazdów - landing v2 design.
 * @var list<array{trip:\App\Models\Trip,totalParticipants:int,completed:int}> $trips
 */
?>
<div class="trip-grid">
    <?php foreach ($trips as $entry):
        $trip      = $entry['trip'];
        $total     = $entry['totalParticipants'];
        $completed = $entry['completed'];
        $progress  = $total > 0 ? (int) round($completed / $total * 100) : 0;
    ?>
    <article class="trip-card">

        <?php if ($trip->bannerImage): ?>
            <div class="trip-banner">
                <img src="<?= e(asset($trip->bannerImage)) ?>" alt="" loading="lazy">
            </div>
        <?php else: ?>
            <div class="trip-banner trip-banner--placeholder" aria-hidden="true">🏖️</div>
        <?php endif; ?>

        <div class="trip-body">
            <?php if (!$trip->isActive): ?>
                <span class="trip-tag">archiwum</span>
            <?php endif; ?>

            <div>
                <h2 class="trip-name"><?= e($trip->name) ?></h2>
                <p class="trip-dates">
                    <?= e(date('d.m', strtotime($trip->dateFrom))) ?> – <?= e(date('d.m.Y', strtotime($trip->dateTo))) ?>
                </p>
            </div>

            <div class="trip-progress">
                <div class="trip-progress-meta">
                    <span>Wypełnione</span>
                    <span class="nums"><?= $completed ?>/<?= $total ?> · <?= $progress ?>%</span>
                </div>
                <div class="trip-progress-bar">
                    <div class="trip-progress-fill" style="width: <?= $progress ?>%"></div>
                </div>
            </div>

            <div class="trip-actions">
                <a href="<?= e(url('/admin/trips/' . $trip->id . '/participants')) ?>" class="btn btn-primary">
                    <span class="iconify" data-icon="ph:users-three-bold"></span> Uczestnicy
                </a>
                <a href="<?= e(url('/admin/trips/' . $trip->id . '/edit')) ?>" class="btn btn-ghost compact" title="Edytuj wyjazd" aria-label="Edytuj">
                    <span class="iconify" data-icon="ph:pencil-simple-bold"></span>
                </a>
                <a href="<?= e(url('/summary/' . $trip->summaryPublicToken)) ?>" target="_blank" rel="noopener" class="btn btn-ghost compact" title="Podsumowanie publiczne (TV)" aria-label="TV">
                    <span class="iconify" data-icon="ph:television-simple-bold"></span>
                </a>
            </div>
        </div>
    </article>
    <?php endforeach; ?>
</div>
