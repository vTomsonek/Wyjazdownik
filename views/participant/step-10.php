<?php /** Krok 10: Mapa - Leaflet + Leaflet.draw */ ?>
<?php require BASE_PATH . '/views/partials/wizard/map-assets.php'; ?>

<header class="mb-6">
    <span class="text-3xl mb-2 block">🗺️</span>
    <h2 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale">Mapa pomysłów</h2>
    <p class="text-mist mt-2">
        Zaznacz miejsca, narysuj trasy, otocz obszary które masz na myśli. Każdy uczestnik ma swój kolor - na podsumowaniu zobaczycie wszystkie pomysły razem.
    </p>
</header>

<div class="grid lg:grid-cols-[1fr_320px] gap-4">
    <div class="rounded-2xl overflow-hidden border-2 border-mist/15 bg-paper dark:bg-deep">
        <div id="participant-map" style="height: 65vh; min-height: 480px;"></div>
    </div>
    <?php require BASE_PATH . '/views/partials/wizard/map-sidebar.php'; ?>
</div>

<?php require BASE_PATH . '/views/partials/wizard/map-modal.php'; ?>
<?php require BASE_PATH . '/views/partials/wizard/map-scripts.php'; ?>
