<?php /** Sekcja 8: CTA koncowy */ ?>
<section id="cta" class="relative overflow-hidden py-20 md:py-32 3xl:py-40">
    <div class="absolute inset-0 -z-10 bg-gradient-to-br from-primary via-primary-dark to-rose-500"></div>
    <div class="absolute inset-0 -z-10 opacity-15" style="background-image: radial-gradient(circle, #FFFFFF 1px, transparent 1.5px); background-size: 28px 28px;"></div>

    <div class="mx-auto max-w-4xl 3xl:max-w-5xl px-4 sm:px-6 lg:px-8 text-center text-white">

        <div class="w-24 h-24 md:w-32 md:h-32 mx-auto mb-6 animate-float-slow" data-animate>
            <?php require BASE_PATH . '/views/partials/mascot.php'; ?>
        </div>

        <h2 class="font-display font-bold text-4xl md:text-6xl 3xl:text-7xl mb-4 leading-tight" data-animate data-animate-delay="1">
            Czas ogarnąć ten wyjazd.
        </h2>

        <p class="text-lg md:text-xl 3xl:text-2xl text-white/90 mb-8 max-w-2xl mx-auto" data-animate data-animate-delay="2">
            Załóż wyjazd w 2 minuty, wyślij ekipie linki, weź telewizor. Reszta jakoś sama się ułoży.
        </p>

        <a href="<?= e(url('/admin/login')) ?>" data-animate data-animate-delay="3"
           class="inline-flex items-center gap-2 px-8 py-4 rounded-full bg-white text-primary font-bold text-lg md:text-xl hover:scale-105 hover:bg-cream transition shadow-2xl">
            Zaloguj się i zacznij
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M13 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</section>
