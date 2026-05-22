<?php /** Sekcja 3: Jak to dziala - 3 kroki */ ?>
<section id="jak-dziala" class="py-16 md:py-24 3xl:py-32">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12 md:mb-16" data-animate>
            <span class="inline-block mb-4 px-3 py-1 rounded-full text-xs font-semibold bg-secondary/10 text-secondary">Trzy kroki</span>
            <h2 class="font-display font-bold text-3xl md:text-5xl 3xl:text-6xl text-ink dark:text-pale mb-4">
                Wyjazdownik to ogarnia.
            </h2>
            <p class="text-lg md:text-xl text-mist max-w-2xl mx-auto">
                Bez instalowania apek, bez zakładania kont przez znajomych, bez exceli na grupie.
            </p>
        </div>

        <div class="grid lg:grid-cols-3 gap-8 lg:gap-10">

            <div class="relative" data-animate data-animate-delay="1">
                <div class="absolute -top-4 -left-4 w-12 h-12 rounded-full bg-primary-deep text-white font-display font-bold text-2xl flex items-center justify-center shadow-pop z-10">1</div>
                <div class="rounded-3xl bg-paper dark:bg-deep border border-mist/10 p-6 hover:shadow-pop-lg transition h-full flex flex-col gap-5">
                    <div class="rounded-2xl bg-cream dark:bg-night p-4 border border-mist/10">
                        <?php require BASE_PATH . '/views/partials/landing/step1-mockup.php'; ?>
                    </div>
                    <div>
                        <div class="text-3xl mb-2">🔧</div>
                        <h3 class="font-display font-bold text-xl md:text-2xl mb-2 text-ink dark:text-pale">Tworzysz wyjazd</h3>
                        <p class="text-mist leading-relaxed">
                            Dodajesz nazwę, opis, okno czasowe (np. wakacje letnie). Generujesz unikalny link dla każdego ze znajomych.
                        </p>
                    </div>
                </div>
            </div>

            <div class="relative" data-animate data-animate-delay="2">
                <div class="absolute -top-4 -left-4 w-12 h-12 rounded-full bg-primary-deep text-white font-display font-bold text-2xl flex items-center justify-center shadow-pop z-10">2</div>
                <div class="rounded-3xl bg-paper dark:bg-deep border border-mist/10 p-6 hover:shadow-pop-lg transition h-full flex flex-col gap-5">
                    <div class="rounded-2xl bg-cream dark:bg-night p-4 border border-mist/10">
                        <?php require BASE_PATH . '/views/partials/landing/step2-mockup.php'; ?>
                    </div>
                    <div>
                        <div class="text-3xl mb-2">📝</div>
                        <h3 class="font-display font-bold text-xl md:text-2xl mb-2 text-ink dark:text-pale">Każdy wypełnia ankietę</h3>
                        <p class="text-mist leading-relaxed">
                            Twoi znajomi przez przeglądarkę odpowiadają na pytania o budżet, terminy, pomysły. Dorzucają miejsca na wspólną mapę ze zdjęciami i opisem.
                        </p>
                    </div>
                </div>
            </div>

            <div class="relative" data-animate data-animate-delay="3">
                <div class="absolute -top-4 -left-4 w-12 h-12 rounded-full bg-primary-deep text-white font-display font-bold text-2xl flex items-center justify-center shadow-pop z-10">3</div>
                <div class="rounded-3xl bg-paper dark:bg-deep border border-mist/10 p-6 hover:shadow-pop-lg transition h-full flex flex-col gap-5">
                    <div class="rounded-2xl bg-cream dark:bg-night p-4 border border-mist/10">
                        <?php require BASE_PATH . '/views/partials/landing/step3-mockup.php'; ?>
                    </div>
                    <div>
                        <div class="text-3xl mb-2">🎉</div>
                        <h3 class="font-display font-bold text-xl md:text-2xl mb-2 text-ink dark:text-pale">Oglądacie wspólny plan</h3>
                        <p class="text-mist leading-relaxed">
                            Włączacie telewizor, otwieracie podsumowanie. Widzicie najlepsze terminy, wspólny budżet, mapę pomysłów i rankingi ekipy.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
