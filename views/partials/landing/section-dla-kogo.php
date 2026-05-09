<?php
/** Sekcja 5: Dla kogo */
$audiences = [
    ['🎓', 'Ekipa z liceum',  'Co roku ten sam problem z dogadaniem się.'],
    ['🏢', 'Znajomi z pracy', 'Integracja wyjazdowa bez chaosu i konfliktów.'],
    ['👥', 'Paczka znajomych','8 osób, 8 pomysłów, 1 wyjazd.'],
    ['👨‍👩‍👧‍👦', 'Rodzina rozszerzona', 'Kuzyni, ciotki, kompromisy.'],
];
?>
<section id="dla-kogo" class="py-16 md:py-24 3xl:py-32 relative overflow-hidden">
    <!-- Ilustracje rozsiane w tle -->
    <div class="absolute top-12 left-8 w-16 opacity-20 hidden md:block animate-float">
        <?php require BASE_PATH . '/views/partials/illustrations/backpack.php'; ?>
    </div>
    <div class="absolute top-32 right-12 w-16 opacity-20 hidden md:block animate-float-slow">
        <?php require BASE_PATH . '/views/partials/illustrations/plane.php'; ?>
    </div>
    <div class="absolute bottom-16 left-16 w-16 opacity-20 hidden md:block animate-float-slow">
        <?php require BASE_PATH . '/views/partials/illustrations/mountains.php'; ?>
    </div>
    <div class="absolute bottom-24 right-8 w-16 opacity-20 hidden md:block animate-float">
        <?php require BASE_PATH . '/views/partials/illustrations/sunset.php'; ?>
    </div>

    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8 relative">

        <div class="text-center mb-12 md:mb-16" data-animate>
            <h2 class="font-display font-bold text-3xl md:text-5xl 3xl:text-6xl text-ink dark:text-pale mb-4">
                Dla każdej ekipy
            </h2>
            <p class="text-lg md:text-xl text-mist max-w-2xl mx-auto">
                Bo każda ekipa, niezależnie od formacji, znalazła się kiedyś w sytuacji "no to gdzie w końcu jedziemy?".
            </p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 max-w-5xl mx-auto">
            <?php foreach ($audiences as $i => [$icon, $title, $desc]): ?>
            <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/10 p-5 md:p-6 text-center hover:shadow-pop transition" data-animate data-animate-delay="<?= e($i + 1) ?>">
                <div class="text-4xl md:text-5xl mb-3"><?= e($icon) ?></div>
                <h3 class="font-display font-bold text-base md:text-lg mb-1 text-ink dark:text-pale"><?= e($title) ?></h3>
                <p class="text-mist text-sm leading-relaxed"><?= e($desc) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
