<?php
/** Krok 9: Języki obce */
$keys = ['languages', 'other_languages'];
?>
<header class="mb-8">
    <span class="eyebrow eyebrow--teal" style="margin-bottom:14px"><span class="iconify" data-icon="ph:translate-bold"></span> Krok 9: Języki</span>
    <h2 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale" style="margin-top:14px">🌐 Czym się dogadasz</h2>
    <p class="text-mist mt-2">Pomoże dobrać kierunek — gdzie ekipa się dogada.</p>
</header>

<?php foreach ($keys as $key):
    $meta    = $questions[$key] ?? [];
    $current = $responses[$key] ?? null;
    require BASE_PATH . '/views/partials/wizard/field.php';
endforeach; ?>
