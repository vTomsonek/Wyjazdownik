<?php
/** Krok 8: Charakter wyjazdu */
$keys = ['activities', 'trip_expectations', 'photo_attitude', 'social_preference'];
?>
<header class="mb-8">
    <span class="text-3xl mb-2 block">✨</span>
    <h2 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale">Charakter wyjazdu</h2>
    <p class="text-mist mt-2">Czego oczekujesz, jak chcesz fotografować, ile czasu z ekipą.</p>
</header>

<?php foreach ($keys as $key):
    $meta    = $questions[$key] ?? [];
    $current = $responses[$key] ?? null;
    require BASE_PATH . '/views/partials/wizard/field.php';
endforeach; ?>
