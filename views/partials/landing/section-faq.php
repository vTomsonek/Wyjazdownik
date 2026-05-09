<?php
/** Sekcja 7: FAQ */
$faqs = [
    ['Czy moi znajomi muszą zakładać konto?',
     'Nie. Każdy dostaje unikalny link do wypełnienia ankiety, nic nie instaluje, nie zakłada konta. Otwiera link, klika, gotowe.'],
    ['Czy to jest darmowe?',
     'Tak. Narzędzie powstało dla mojej ekipy i udostępniam je za darmo. Bez reklam, bez sprzedaży danych.'],
    ['Co z moimi danymi?',
     'Trzymane na własnym serwerze, nie sprzedawane nikomu. Możesz usunąć wyjazd kiedy chcesz - razem z odpowiedziami wszystkich uczestników.'],
    ['Ile osób może wziąć udział?',
     'Optymalnie 4-15. Działa też z większą ekipą, ale wtedy ciężej znaleźć kompromis - bo każda dodatkowa osoba zwęża zbiór terminów i preferencji.'],
    ['Czy działa na telefonie?',
     'Tak, w pełni. Aplikacja jest mobile-first - wizard uczestnika dobrze wygląda zarówno na telefonie, jak i na laptopie. Tryb prezentacji zoptymalizowany pod telewizor.'],
    ['Czy mogę edytować odpowiedzi znajomych?',
     'Tak, jako admin masz dostęp do edycji wszystkich odpowiedzi. Każda zmiana jest logowana - nikt z ekipy nie zarzuci ci że "po cichu" coś zmieniłeś.'],
];
?>
<section id="faq" class="py-16 md:py-24 3xl:py-32">
    <div class="mx-auto max-w-3xl 3xl:max-w-4xl px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-10 md:mb-14" data-animate>
            <h2 class="font-display font-bold text-3xl md:text-5xl 3xl:text-6xl text-ink dark:text-pale mb-3">
                Pytania, na które padają pytania
            </h2>
            <p class="text-lg text-mist">Krótka ściąga zanim ruszysz.</p>
        </div>

        <div class="space-y-3" data-animate>
            <?php foreach ($faqs as $idx => [$q, $a]): $itemId = 'faq-' . ($idx + 1); ?>
            <div data-faq class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 [&.is-open]:border-primary [&.is-open]:shadow-pop transition">
                <button type="button" data-faq-trigger aria-expanded="false" aria-controls="<?= e($itemId) ?>"
                        class="w-full flex items-center justify-between gap-4 px-5 md:px-6 py-4 md:py-5 text-left">
                    <span class="font-display font-semibold text-base md:text-lg text-ink dark:text-pale"><?= e($q) ?></span>
                    <svg class="faq-chevron w-5 h-5 text-mist shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 9l6 6 6-6"/>
                    </svg>
                </button>
                <div data-faq-panel id="<?= e($itemId) ?>" aria-hidden="true">
                    <p class="px-5 md:px-6 pb-5 md:pb-6 text-mist leading-relaxed"><?= e($a) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
