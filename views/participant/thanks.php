<?php
/**
 * @var \App\Models\Trip        $trip
 * @var \App\Models\Participant $participant
 */
?>
<section class="section" style="position:relative; overflow:hidden">
    <span style="position:absolute;width:460px;height:460px;border-radius:50%;filter:blur(8px);opacity:.55;pointer-events:none;background:radial-gradient(circle, rgba(16,185,129,0.30), transparent 65%);top:-100px;right:-120px;z-index:0"></span>
    <span style="position:absolute;width:380px;height:380px;border-radius:50%;filter:blur(8px);opacity:.55;pointer-events:none;background:radial-gradient(circle, rgba(255,210,63,0.30), transparent 65%);bottom:-100px;left:-120px;z-index:0"></span>

    <!-- Konfetti canvas -->
    <canvas id="confetti-canvas" class="fixed inset-0 pointer-events-none z-0" aria-hidden="true"></canvas>

    <div class="wrap" style="max-width:680px; position:relative; z-index:1; text-align:center">

        <span class="eyebrow" style="background:rgba(16,185,129,.18); color:#10B981; border-color:rgba(16,185,129,.30); margin-bottom:24px">
            <span class="iconify" data-icon="ph:check-circle-fill"></span> Gotowe!
        </span>

        <h1 style="font-family:var(--font-display); font-weight:800; letter-spacing:-0.025em; line-height:1.04; font-size:clamp(40px, 6vw, 72px); color:var(--heading); margin:20px 0 16px">
            Dzięki, <span style="color:var(--orange)"><?= e($participant->nickname) ?></span>!
        </h1>

        <p style="font-size:clamp(16px, 1.4vw, 19px); color:var(--fg-2); line-height:1.55; margin:0 auto 36px; max-width:560px">
            Twoja ankieta dla <strong style="color:var(--heading)"><?= e($trip->name) ?></strong> została wysłana.<br>
            Reszta ekipy wypełnia swoje — a potem włączacie razem podsumowanie na TV.
        </p>

        <!-- Link permanentny -->
        <div style="background:var(--surface); border:1px solid var(--line); border-radius:18px; padding:20px 22px; margin:0 auto 28px; max-width:520px; text-align:left">
            <p style="font-size:13px; color:var(--fg-2); margin:0 0 10px; display:flex; align-items:center; gap:8px">
                <span class="iconify" data-icon="ph:bookmark-simple-bold" style="font-size:18px; color:var(--orange)"></span>
                <span>Zachowaj ten link — możesz edytować odpowiedzi w każdej chwili</span>
            </p>
            <p style="font-family:ui-monospace, monospace; font-size:12px; color:var(--heading); word-break:break-all; background:var(--cream); padding:10px 14px; border-radius:10px; margin:0; border:1px solid var(--line)">
                <?= e(url('/p/' . $participant->accessToken)) ?>
            </p>
        </div>

        <a class="btn btn-primary" href="<?= e(url('/p/' . $participant->accessToken)) ?>">
            <span class="iconify" data-icon="ph:eye-bold"></span>
            Zobacz moje odpowiedzi
        </a>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        if (typeof confetti !== 'function') return;

        const burst = (origin) => confetti({
            particleCount: 80,
            spread: 70,
            startVelocity: 35,
            origin: origin,
            colors: ['#FF6B35', '#FFD23F', '#2EC4B6', '#FFFFFF']
        });

        burst({ x: 0.5, y: 0.4 });
        setTimeout(() => burst({ x: 0.2, y: 0.5 }), 250);
        setTimeout(() => burst({ x: 0.8, y: 0.5 }), 500);
    });
</script>
