<?php
/** Krok 8: Charakter wyjazdu */
$keys = ['activities', 'trip_expectations', 'photo_attitude', 'social_preference'];
?>
<header class="mb-8">
    <span class="eyebrow" style="margin-bottom:14px"><span class="iconify" data-icon="ph:sparkle-bold"></span> Krok 8: Charakter</span>
    <h2 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale" style="margin-top:14px">✨ Czego oczekujesz</h2>
    <p class="text-mist mt-2">Aktywności, oczekiwania, zdjęcia, ile czasu razem.</p>
</header>

<?php foreach ($keys as $key):
    $meta    = $questions[$key] ?? [];
    $current = $responses[$key] ?? null;
    require BASE_PATH . '/views/partials/wizard/field.php';
endforeach; ?>
