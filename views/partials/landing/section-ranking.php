<?php
/** Sekcja 6: Ranking showcase */
$badges = [
    ['🏆', 'Najbardziej wymagający', 'Kasia',  'from-rose-500/20 to-pink-500/10',     'text-rose-300'],
    ['😎', 'Mr Luzak',               'Tomek',  'from-secondary/20 to-secondary/5',    'text-secondary'],
    ['🥙', 'Kebab Master',           'Bartek', 'from-amber-500/20 to-yellow-500/5',   'text-accent'],
    ['🍻', 'Imprezowicz',            'Adam',   'from-orange-500/20 to-amber-500/5',   'text-primary'],
    ['🥾', 'Maszyna',                'Ola',    'from-emerald-500/20 to-teal-500/5',   'text-emerald-300'],
    ['📸', 'Influencer',             'Magda',  'from-purple-500/20 to-fuchsia-500/5', 'text-fuchsia-300'],
];
?>
<section id="ranking" class="bg-gradient-to-br from-ink via-deep to-ink dark:from-night dark:via-deep dark:to-night text-pale py-16 md:py-24 3xl:py-32 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, #FFD23F 1px, transparent 1.5px); background-size: 32px 32px;"></div>

    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8 relative">

        <div class="text-center mb-12 md:mb-16 max-w-3xl mx-auto" data-animate>
            <span class="inline-block mb-4 px-3 py-1 rounded-full text-xs font-semibold bg-accent/20 text-accent">Klejnot korony</span>
            <h2 class="font-display font-bold text-3xl md:text-5xl 3xl:text-6xl mb-4">
                Poznaj swoją ekipę z innej strony
            </h2>
            <p class="text-lg md:text-xl text-mist">
                Algorytm na podstawie odpowiedzi przyznaje każdemu odznakę. Idealne na wieczór gdy włączacie TV i bawicie się przy wynikach.
            </p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-5 max-w-5xl mx-auto">
            <?php foreach ($badges as $i => [$icon, $title, $name, $bgGrad, $accentClass]): ?>
            <div class="rounded-2xl p-5 md:p-6 bg-gradient-to-br <?= e($bgGrad) ?> border border-white/10 hover:scale-[1.03] transition flex items-center gap-4" data-animate data-animate-delay="<?= e(min(5, $i + 1)) ?>">
                <div class="text-4xl md:text-5xl"><?= e($icon) ?></div>
                <div>
                    <div class="text-xs font-medium <?= e($accentClass) ?> uppercase tracking-wider mb-0.5"><?= e($title) ?></div>
                    <div class="font-display font-bold text-xl md:text-2xl"><?= e($name) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <p class="text-center mt-12 text-mist max-w-xl mx-auto" data-animate>
            <span class="font-accent text-2xl md:text-3xl text-accent">+ kilkanaście innych</span>
            <br>
            Krezus, Plażowicz, Górski Wilk, Foodie, Trzeźwy Duch, Spokojny Duch, Globtrotter, Backpacker...
        </p>
    </div>
</section>
