<?php
/** Krok 2: Podstawy - budget, duration, passport, money_attitude */
$keys = ['budget_range', 'trip_duration_days', 'has_passport', 'money_attitude'];
?>
<header class="mb-8">
    <span class="text-3xl mb-2 block">📋</span>
    <h2 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale">Podstawy</h2>
    <p class="text-mist mt-2">Pierwsze rzeczy, które trzeba ustalić - budżet, czas, paszport.</p>
</header>

<?php foreach ($keys as $key):
    $meta    = $questions[$key] ?? [];
    $current = $responses[$key] ?? null;
    require BASE_PATH . '/views/partials/wizard/field.php';
endforeach; ?>
