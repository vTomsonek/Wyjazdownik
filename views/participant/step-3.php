<?php
/** Krok 3: Transport */
$keys = ['transport_modes', 'has_driving_license', 'can_share_car', 'max_daily_driving_km'];
?>
<header class="mb-8">
    <span class="text-3xl mb-2 block">🚗</span>
    <h2 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale">Transport</h2>
    <p class="text-mist mt-2">Jak się przemieszczamy i kto może prowadzić.</p>
</header>

<?php foreach ($keys as $key):
    $meta    = $questions[$key] ?? [];
    $current = $responses[$key] ?? null;
    require BASE_PATH . '/views/partials/wizard/field.php';
endforeach; ?>
