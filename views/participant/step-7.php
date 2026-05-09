<?php
/** Krok 7: Jedzenie i picie */
$keys = ['dietary_restrictions', 'food_allergies', 'food_style', 'food_openness',
         'alcohol_attitude', 'party_style'];
?>
<header class="mb-8">
    <span class="text-3xl mb-2 block">🍻</span>
    <h2 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale">Jedzenie i picie</h2>
    <p class="text-mist mt-2">Dieta, alergie, alkohol, imprezy - bez owijania w bawełnę.</p>
</header>

<?php foreach ($keys as $key):
    $meta    = $questions[$key] ?? [];
    $current = $responses[$key] ?? null;
    require BASE_PATH . '/views/partials/wizard/field.php';
endforeach; ?>
