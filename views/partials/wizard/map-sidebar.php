<?php /** Sidebar listy pinezek - krok 10 wizarda */ ?>
<aside class="rounded-2xl border border-mist/15 bg-paper dark:bg-deep p-4 max-h-[65vh] overflow-y-auto">
    <h3 class="font-display font-bold text-lg text-ink dark:text-pale mb-3 flex items-center gap-2">
        Twoje pinezki
        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-primary/15 text-primary"
              data-pin-count>0</span>
    </h3>

    <div data-pin-list class="space-y-2">
        <p class="text-sm text-mist italic" data-pin-empty>
            Jeszcze nic nie dodałeś. Użyj narzędzi rysowania na mapie po lewej.
        </p>
    </div>

    <div class="mt-4 pt-4 border-t border-mist/15 text-xs text-mist space-y-1">
        <p class="flex items-center gap-2">
            <span class="inline-block w-3 h-3 rounded-full" data-color-swatch></span>
            <span>Twój kolor</span>
        </p>
        <p>OpenStreetMap - bez kluczy API, gratis dla wszystkich.</p>
    </div>
</aside>
