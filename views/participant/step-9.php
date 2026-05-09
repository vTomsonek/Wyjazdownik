<?php
/** Krok 9: Języki obce */
$keys = ['languages', 'other_languages'];
?>
<header class="mb-8">
    <span class="text-3xl mb-2 block">🌐</span>
    <h2 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale">Języki obce</h2>
    <p class="text-mist mt-2">Pomoże dobrać kierunek - gdzie ekipa się dogada.</p>
</header>

<?php foreach ($keys as $key):
    $meta    = $questions[$key] ?? [];
    $current = $responses[$key] ?? null;
    require BASE_PATH . '/views/partials/wizard/field.php';
endforeach; ?>
