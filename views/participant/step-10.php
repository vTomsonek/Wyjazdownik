<?php
/**
 * Krok 10: Info-step - mapa pomysłów zastąpiona nową kolaboratywną mapą atrakcji.
 * Po wypełnieniu ankiety uczestnik dostaje link /p/{token}/atrakcje.
 *
 * @var \App\Models\Participant $participant
 */
$placesUrl = url('/p/' . $participant->accessToken . '/atrakcje');
?>

<header class="mb-6">
    <span class="eyebrow eyebrow--teal" style="margin-bottom:14px"><span class="iconify" data-icon="ph:map-pin-bold"></span> Krok 10: Mapa atrakcji</span>
    <h2 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale" style="margin-top:14px">🗺️ Wspólna mapa miejsc</h2>
    <p class="text-mist mt-2">
        Dawniej w tym miejscu rysowało się pinezki w ankiecie. Teraz mamy coś lepszego —
        <strong class="text-ink dark:text-pale">interaktywną mapę atrakcji</strong> z autocomplete Google,
        zdjęciami, ocenami i automatycznymi propozycjami tras.
    </p>
</header>

<div class="rounded-2xl bg-paper dark:bg-deep border-2 border-mist/15 p-6 md:p-8">
    <div class="text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/15 mb-4">
            <span class="text-3xl">📍</span>
        </div>
        <h3 class="font-display font-bold text-xl text-ink dark:text-pale mb-3">
            Dodawaj miejsca razem z ekipą
        </h3>
        <p class="text-mist max-w-2xl mx-auto leading-relaxed mb-5">
            Każdy z Was może dodać konkretne miejsca które chce odwiedzić (np. Park Krka, Plitvice, Kotor).
            Dorzucisz opis, zdjęcia, wideo, linki. Inni ocenią ★ 1-5 jak bardzo chcą tam jechać.
            Algorytm zasugeruje gotowe trasy samochodowe na podstawie najwyżej ocenionych miejsc.
        </p>

        <div class="grid sm:grid-cols-3 gap-4 max-w-2xl mx-auto mb-6">
            <div class="rounded-xl bg-cream dark:bg-night border border-mist/15 p-4">
                <div class="text-2xl mb-1">🔍</div>
                <div class="text-sm font-semibold text-ink dark:text-pale">Search Google</div>
                <div class="text-xs text-mist">Autocomplete miejsc</div>
            </div>
            <div class="rounded-xl bg-cream dark:bg-night border border-mist/15 p-4">
                <div class="text-2xl mb-1">📸</div>
                <div class="text-sm font-semibold text-ink dark:text-pale">Zdjęcia + wideo</div>
                <div class="text-xs text-mist">Dziel się materiałami</div>
            </div>
            <div class="rounded-xl bg-cream dark:bg-night border border-mist/15 p-4">
                <div class="text-2xl mb-1">⭐</div>
                <div class="text-sm font-semibold text-ink dark:text-pale">Oceny ekipy</div>
                <div class="text-xs text-mist">Top miejsca w rankingu</div>
            </div>
        </div>

        <a href="<?= e($placesUrl) ?>" target="_blank" class="btn btn-primary">
            <span class="iconify" data-icon="ph:map-trifold-bold"></span>
            Otwórz mapę atrakcji
        </a>

        <p class="mt-3 text-xs text-mist">
            Możesz dodawać miejsca w dowolnym momencie - nie musisz tego robić teraz.
            <br>Klik <strong>Dalej →</strong> żeby kontynuować ankietę.
        </p>
    </div>
</div>
