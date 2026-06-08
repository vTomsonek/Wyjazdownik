<?php $loginUrl = url('/admin/login'); ?>
<section class="hero">
    <span class="blob blob-1"></span>
    <span class="blob blob-2"></span>
    <div class="wrap hero-grid">
        <div class="hero-copy">
            <span class="eyebrow"><span class="flag-pl"></span> Polskie narzędzie dla polskich ekip</span>
            <h1>Ogarnij wakacje <br>ze znajomymi <span class="u">raz na zawsze</span>.</h1>
            <p class="hero-lead">Koniec z tygodniami dyskusji na grupie. Wyjazdownik zbiera od ekipy preferencje, terminy i pomysły, a potem pokazuje wam <b>wspólny plan na telewizorze</b>.</p>
            <div class="hero-actions">
                <a class="btn btn-primary btn-lg" href="<?= e($loginUrl) ?>">Zacznij za darmo <span class="iconify" data-icon="ph:arrow-right-bold"></span></a>
                <a class="btn btn-ghost btn-lg" href="#jak-dziala">Zobacz jak to działa</a>
            </div>
            <div class="hero-note"><span class="iconify" data-icon="ph:users-three-fill"></span> Stworzone z myślą o ekipach od 5 do 15 osób</div>
        </div>

        <div class="mock">
            <span class="float-em fe1">🏖️</span>
            <span class="float-em fe2">🗺️</span>
            <span class="float-em fe3">✈️</span>
            <div class="summary">
                <div class="summary-top">
                    <span class="summary-title">Podsumowanie</span>
                    <span class="summary-badge">Lato 2026</span>
                </div>
                <div class="summary-row">
                    <div class="scard">
                        <h5>📅 Najlepsze terminy</h5>
                        <div class="heat">
                            <span class="h1"></span><span class="h2"></span><span class="h3"></span><span class="h4"></span><span class="h3"></span><span class="h2"></span><span class="h1"></span>
                            <span class="h2"></span><span class="h3"></span><span class="h4"></span><span class="h4"></span><span class="h4"></span><span class="h3"></span><span class="h2"></span>
                            <span class="h1"></span><span class="h2"></span><span class="h3"></span><span class="h3"></span><span class="h2"></span><span class="h2"></span><span class="h1"></span>
                        </div>
                    </div>
                    <div class="scard">
                        <h5>🏆 Ranking ekipy</h5>
                        <div class="rank">
                            <div class="rankrow"><span class="em">🥙</span> Kebab Master <span class="nm">· Bartek</span></div>
                            <div class="rankrow"><span class="em">🥾</span> Maszyna <span class="nm">· Ola</span></div>
                            <div class="rankrow"><span class="em">🏖️</span> Plażowicz <span class="nm">· Tomek</span></div>
                            <div class="rankrow"><span class="em">🍻</span> Imprezowicz <span class="nm">· Adam</span></div>
                        </div>
                    </div>
                </div>
                <div class="tvbtn"><span class="iconify" data-icon="ph:television-simple-fill"></span> Tryb prezentacji</div>
            </div>
        </div>
    </div>
</section>
