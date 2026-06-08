<?php
/** Krok 11: Wolny tekst */
$keys = ['dream_plan', 'deal_breakers'];
?>
<header class="mb-8">
    <span class="eyebrow eyebrow--teal" style="margin-bottom:14px"><span class="iconify" data-icon="ph:chat-circle-dots-bold"></span> Krok 11: Twój głos</span>
    <h2 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale" style="margin-top:14px">💭 Co chcesz dorzucić</h2>
    <p class="text-mist mt-2">Cokolwiek dorzuć — wymarzone wakacje albo czego absolutnie unikamy.</p>
</header>

<?php foreach ($keys as $key):
    $meta    = $questions[$key] ?? [];
    $current = $responses[$key] ?? null;
    require BASE_PATH . '/views/partials/wizard/field.php';
endforeach; ?>
