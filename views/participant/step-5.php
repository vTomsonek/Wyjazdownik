<?php
/** Krok 5: Aktywność fizyczna */
$keys = ['daily_walking_capacity', 'physical_activities'];
?>
<header class="mb-8">
    <span class="eyebrow eyebrow--teal" style="margin-bottom:14px"><span class="iconify" data-icon="ph:sneaker-bold"></span> Krok 5: Aktywność</span>
    <h2 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale" style="margin-top:14px">🥾 Co wytrzymasz fizycznie</h2>
    <p class="text-mist mt-2">Ile dasz radę wytrzymać dziennie i co lubisz robić.</p>
</header>

<?php foreach ($keys as $key):
    $meta    = $questions[$key] ?? [];
    $current = $responses[$key] ?? null;
    require BASE_PATH . '/views/partials/wizard/field.php';
endforeach; ?>
