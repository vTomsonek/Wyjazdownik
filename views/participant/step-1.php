<?php
/**
 * Krok 1: Dostępność (kalendarz).
 *
 * Tryb 'block_unavailable': klikasz dni gdy NIE możesz.
 * Tryb 'select_preferred_weeks': dla każdego tygodnia w oknie - pasuje/może/nie pasuje.
 */
?>
<header class="mb-8">
    <span class="eyebrow eyebrow--teal" style="margin-bottom:14px"><span class="iconify" data-icon="ph:calendar-bold"></span> Krok 1: Dostępność</span>
    <h2 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale" style="margin-top:14px">
        📅 Kiedy możesz pojechać
    </h2>
    <p class="text-mist mt-2">
        <?php if ($trip->calendarMode === 'block_unavailable'): ?>
            Kliknij dni, w które <strong>NIE możesz</strong>. Reszta = jesteś dostępny.
        <?php else: ?>
            Dla każdego tygodnia powiedz: pasuje / może / nie pasuje.
        <?php endif; ?>
    </p>
</header>

<?php if ($trip->calendarMode === 'block_unavailable'): ?>
    <div data-availability-mode="block_unavailable"
         data-date-from="<?= e($trip->dateFrom) ?>"
         data-date-to="<?= e($trip->dateTo) ?>"
         data-unavailable='<?= e(json_encode($unavailableDates)) ?>'>
        <!-- Calendar renderowany przez JS -->
        <div id="availability-calendar" class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-4"></div>
        <p class="mt-3 text-xs text-mist">
            Kliknięty dzień → niedostępny (czerwony). Kliknij ponownie żeby cofnąć.
        </p>
    </div>
<?php else: ?>
    <div data-availability-mode="select_preferred_weeks"
         data-date-from="<?= e($trip->dateFrom) ?>"
         data-date-to="<?= e($trip->dateTo) ?>"
         data-weeks='<?= e(json_encode($preferredWeeks)) ?>'>
        <div id="availability-weeks" class="space-y-2"></div>
    </div>
<?php endif; ?>
