<?php
/**
 * @var string $heading
 * @var string $message
 */
$heading = $heading ?? 'Wkrótce';
$message = $message ?? '';
?>
<section class="section" style="position:relative; overflow:hidden">
    <span style="position:absolute;width:380px;height:380px;border-radius:50%;filter:blur(8px);opacity:.40;pointer-events:none;background:radial-gradient(circle, rgba(14,155,170,0.28), transparent 65%);top:-100px;right:-120px;z-index:0"></span>

    <div class="wrap" style="max-width:600px; position:relative; z-index:1; text-align:center">
        <span class="eyebrow eyebrow--teal" style="margin-bottom:24px">
            <span class="iconify" data-icon="ph:hourglass-medium-bold"></span> Wkrótce
        </span>

        <h1 style="font-family: var(--font-display); font-weight: 800; font-size: clamp(28px, 4.4vw, 44px); margin: 14px 0 14px; color: var(--heading); line-height: 1.1">
            <?= e($heading) ?>
        </h1>

        <?php if (!empty($message)): ?>
        <p style="color: var(--fg-2); font-size: 17px; line-height: 1.55; margin: 0 auto 36px; max-width: 500px">
            <?= e($message) ?>
        </p>
        <?php endif; ?>

        <a class="btn btn-primary" href="<?= e(url('/')) ?>">
            <span class="iconify" data-icon="ph:arrow-left-bold"></span>
            Wróć na stronę główną
        </a>
    </div>
</section>
