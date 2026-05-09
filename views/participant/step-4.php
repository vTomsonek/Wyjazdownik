<?php
/** Krok 4: Kierunek i klimat */
$keys = ['landscape_preferences', 'climate_tolerance', 'travel_experience'];
?>
<header class="mb-8">
    <span class="text-3xl mb-2 block">🌍</span>
    <h2 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale">Kierunek i klimat</h2>
    <p class="text-mist mt-2">Co chcesz zobaczyć, jakie temperatury OK.</p>
</header>

<?php foreach ($keys as $key):
    $meta    = $questions[$key] ?? [];
    $current = $responses[$key] ?? null;
    require BASE_PATH . '/views/partials/wizard/field.php';
endforeach; ?>
