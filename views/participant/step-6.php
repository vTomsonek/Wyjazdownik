<?php
/** Krok 6: Komfort i nocleg */
$keys = ['accommodation', 'room_sharing', 'comfort_level', 'pace'];
?>
<header class="mb-8">
    <span class="text-3xl mb-2 block">🏨</span>
    <h2 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale">Komfort i nocleg</h2>
    <p class="text-mist mt-2">Gdzie i jak chcesz spać, jakie tempo wyjazdu.</p>
</header>

<?php foreach ($keys as $key):
    $meta    = $questions[$key] ?? [];
    $current = $responses[$key] ?? null;
    require BASE_PATH . '/views/partials/wizard/field.php';
endforeach; ?>
