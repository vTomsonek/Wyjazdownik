<?php
/**
 * Wyjazdownik.pl - landing page (9 sekcji + opcjonalna sekcja diagnostyczna w dev).
 *
 * @var bool       $isDev
 * @var array|null $devData
 */
$isDev   = $isDev   ?? false;
$devData = $devData ?? null;
?>

<!-- ============================================================ SEKCJA 1: HERO ============================================================ -->
<section class="relative overflow-hidden">
    <div class="absolute inset-0 -z-10
                bg-gradient-to-br from-cream via-cream to-accent/20
                dark:from-night dark:via-night dark:to-secondary/10"></div>
    <div class="absolute inset-0 -z-10 opacity-30 dark:opacity-15"
         style="background-image: radial-gradient(circle, #FF6B35 1px, transparent 1.5px); background-size: 24px 24px;"></div>

    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8 pt-10 pb-20 md:pt-16 md:pb-28 3xl:pt-24 3xl:pb-36">
        <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">

            <div class="flex flex-col gap-6 max-w-xl 3xl:max-w-2xl">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs sm:text-sm font-semibold bg-primary/15 text-primary-deep dark:text-primary self-start">
                    🇵🇱 Polskie narzędzie dla polskich ekip
                </span>
                <h1 class="font-display font-bold tracking-tight leading-[1.05] text-4xl sm:text-5xl md:text-6xl 3xl:text-7xl text-ink dark:text-pale">
                    Ogarnij wakacje ze znajomymi
                    <span class="relative inline-block">
                        <span class="text-primary">raz na zawsze.</span>
                        <svg class="absolute -bottom-2 left-0 w-full h-3 text-accent/70" viewBox="0 0 200 12" preserveAspectRatio="none" aria-hidden="true">
                            <path d="M2 8 Q 50 2 100 6 T 198 6" stroke="currentColor" stroke-width="4" fill="none" stroke-linecap="round"/>
                        </svg>
                    </span>
                </h1>
                <p class="text-lg md:text-xl 3xl:text-2xl text-mist leading-relaxed">
                    Koniec z tygodniami dyskusji na grupie. Wyjazdownik zbiera od ekipy preferencje, terminy i pomysły, a potem pokazuje wam <strong class="text-ink dark:text-pale font-semibold">wspólny plan na telewizorze</strong>.
                </p>
                <div class="flex flex-wrap gap-3 mt-2">
                    <a href="<?= e(url('/admin/login')) ?>" class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-primary-deep text-white font-semibold text-base md:text-lg shadow-pop hover:bg-primary hover:scale-105 transition">
                        Zaloguj się
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                    </a>
                    <a href="#jak-dziala" class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-paper dark:bg-deep border-2 border-mist/20 text-ink dark:text-pale font-semibold text-base md:text-lg hover:border-primary hover:scale-105 transition">
                        Zobacz jak to działa
                    </a>
                </div>
                <p class="flex items-center gap-2 text-sm text-mist mt-2">
                    <span class="inline-block w-5 h-5"><?php require BASE_PATH . '/views/partials/mascot.php'; ?></span>
                    Stworzone z myślą o ekipach 5-15 osób
                </p>
            </div>

            <div class="relative max-w-md 3xl:max-w-xl mx-auto lg:mx-0 w-full animate-float-slow">
                <?php require BASE_PATH . '/views/partials/landing/hero-mockup.php'; ?>
            </div>
        </div>
    </div>
</section>

<?php require BASE_PATH . '/views/partials/landing/section-problem.php'; ?>
<?php require BASE_PATH . '/views/partials/landing/section-jak-dziala.php'; ?>
<?php require BASE_PATH . '/views/partials/landing/section-funkcje.php'; ?>
<?php require BASE_PATH . '/views/partials/landing/section-dla-kogo.php'; ?>
<?php require BASE_PATH . '/views/partials/landing/section-ranking.php'; ?>
<?php require BASE_PATH . '/views/partials/landing/section-faq.php'; ?>
<?php require BASE_PATH . '/views/partials/landing/section-cta.php'; ?>

<?php if ($isDev && $devData): require BASE_PATH . '/views/partials/landing/section-dev.php'; endif; ?>
