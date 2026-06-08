<?php /** @var string $title */ ?>
<section class="section" style="position:relative; overflow:hidden; min-height:60vh; display:flex; align-items:center">
    <span style="position:absolute;width:420px;height:420px;border-radius:50%;filter:blur(8px);opacity:.45;pointer-events:none;background:radial-gradient(circle, rgba(244,63,94,0.30), transparent 65%);top:-100px;right:-120px;z-index:0"></span>
    <span style="position:absolute;width:360px;height:360px;border-radius:50%;filter:blur(8px);opacity:.45;pointer-events:none;background:radial-gradient(circle, rgba(255,107,53,0.25), transparent 65%);bottom:-100px;left:-120px;z-index:0"></span>

    <div class="wrap" style="max-width:640px; position:relative; z-index:1; text-align:center">
        <span class="eyebrow" style="background:rgba(244,63,94,.16); color:#F43F5E; border-color:rgba(244,63,94,.30); margin-bottom:24px">
            <span class="iconify" data-icon="ph:warning-octagon-bold"></span> Błąd serwera
        </span>

        <div style="font-family: var(--font-display); font-weight: 900; font-size: clamp(96px, 18vw, 180px); line-height: 1; color: #F43F5E; letter-spacing: -0.04em; margin: 8px 0 24px; text-shadow: 0 8px 30px rgba(244,63,94,.25)">
            500
        </div>

        <h1 style="font-family: var(--font-display); font-weight: 800; font-size: clamp(24px, 3.4vw, 36px); margin: 0 0 14px; color: var(--heading); line-height: 1.15">
            Coś się posypało
        </h1>

        <p style="color: var(--fg-2); font-size: 17px; line-height: 1.55; margin: 0 auto 36px; max-width: 460px">
            Po naszej stronie wystąpił problem. Spróbuj za chwilę albo daj znać administratorowi.
        </p>

        <a class="btn btn-primary" href="<?= e(url('/')) ?>">
            <span class="iconify" data-icon="ph:arrow-left-bold"></span>
            Wróć na stronę główną
        </a>
    </div>
</section>
