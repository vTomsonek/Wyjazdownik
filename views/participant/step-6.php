<?php
/** Krok 6: Komfort i nocleg */
$keys = ['accommodation', 'room_sharing', 'comfort_level', 'pace'];
?>
<header class="mb-8">
    <span class="eyebrow" style="margin-bottom:14px"><span class="iconify" data-icon="ph:bed-bold"></span> Krok 6: Komfort</span>
    <h2 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale" style="margin-top:14px">🏨 Gdzie spać, jak żyć</h2>
    <p class="text-mist mt-2">Gdzie i jak chcesz spać, jakie tempo wyjazdu.</p>
</header>

<?php foreach ($keys as $key):
    $meta    = $questions[$key] ?? [];
    $current = $responses[$key] ?? null;
    require BASE_PATH . '/views/partials/wizard/field.php';
endforeach; ?>
