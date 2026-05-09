<?php
/**
 * Banner widoczny tylko gdy admin edytuje odpowiedzi uczestnika.
 *
 * @var \App\Models\Participant $participant
 * @var \App\Models\Trip        $trip
 */
?>
<div class="bg-accent/20 border-b-2 border-accent">
    <div class="mx-auto max-w-5xl 3xl:max-w-6xl px-4 sm:px-6 lg:px-8 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <p class="text-sm text-ink dark:text-pale">
            🔧 <strong>Edytujesz jako admin</strong> odpowiedzi uczestnika
            <strong><?= e($participant->nickname) ?></strong>.
            Każda zmiana lądowuje w audit logu.
        </p>
        <a href="<?= e(url('/admin/trips/' . $trip->id . '/participants')) ?>"
           class="text-sm font-medium text-primary hover:underline">
            ← Powrót do listy uczestników
        </a>
    </div>
</div>
