<?php
/** Krok 11: Wolny tekst */
$keys = ['dream_plan', 'deal_breakers'];
?>
<header class="mb-8">
    <span class="text-3xl mb-2 block">💭</span>
    <h2 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale">Twój głos</h2>
    <p class="text-mist mt-2">Cokolwiek chcesz dorzucić - wymarzone wakacje albo czego absolutnie unikamy.</p>
</header>

<?php foreach ($keys as $key):
    $meta    = $questions[$key] ?? [];
    $current = $responses[$key] ?? null;
    require BASE_PATH . '/views/partials/wizard/field.php';
endforeach; ?>
