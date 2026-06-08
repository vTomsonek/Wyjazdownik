<?php
/**
 * Sekcja 1: Hero - landing v2 design.
 * Banner u gory (z gradientem na dole), pod nim eyebrow + H1 + lead + stats + avatary.
 * @var \App\Models\Trip $trip
 * @var \App\Services\SummaryAggregator $agg
 */
$participants = $agg->participants();
$completed    = $agg->completedCount();
$total        = $agg->totalCount();
$colors       = $agg->colorMap();
$anonymous    = $agg->isAnonymous();

// Aspect bannera (do limitowania wysokosci na wide screens)
$bannerAspect = null;
if ($trip->bannerImage) {
    $publicDir = dirname(__DIR__, 3) . '/public/';
    $absPath   = $publicDir . ltrim($trip->bannerImage, '/');
    if (is_file($absPath)) {
        $size = @getimagesize($absPath);
        if ($size && $size[0] > 0 && $size[1] > 0) {
            $bannerAspect = $size[0] / $size[1];
        }
    }
}

$fmtDate = static fn(string $d) => date('d.m.Y', strtotime($d));
?>
<section class="summary-hero">
    <span class="sh-blob sh-blob-1"></span>
    <span class="sh-blob sh-blob-2"></span>

    <?php if ($trip->bannerImage): ?>
    <div class="sh-banner-wrap">
        <img class="sh-banner" src="<?= e(asset($trip->bannerImage)) ?>" alt="" fetchpriority="high" decoding="async">
        <div class="sh-banner-fade"></div>
    </div>
    <?php endif; ?>

    <div class="wrap sh-body">
        <span class="eyebrow eyebrow--teal sh-dates">
            <span class="iconify" data-icon="ph:calendar-blank-bold"></span>
            <?= e($fmtDate($trip->dateFrom)) ?> – <?= e($fmtDate($trip->dateTo)) ?>
        </span>

        <h1 class="sh-title"><?= e($trip->name) ?></h1>

        <?php if (!empty($trip->description)): ?>
        <p class="sh-lead"><?= nl2br(e($trip->description)) ?></p>
        <?php endif; ?>

        <div class="sh-stats">
            <span class="sh-stat-inline">
                <span class="iconify" data-icon="ph:check-circle-fill" style="color:var(--green);font-size:18px"></span>
                <b><?= $completed ?> z <?= $total ?></b> wypełniło ankietę
            </span>
            <?php if ($anonymous): ?>
            <span class="sh-stat-inline sh-anon-inline">
                <span class="iconify" data-icon="ph:eye-slash-bold" style="color:var(--teal-600);font-size:16px"></span>
                Tryb anonimowy
            </span>
            <?php endif; ?>
        </div>

        <!-- Avatary uczestnikow -->
        <div class="sh-avatars">
            <?php foreach ($participants as $i => $p):
                $color = $colors[$p->id] ?? '#FF6B35';
                $displayName = $anonymous ? ('Uczestnik ' . ($i + 1)) : $p->nickname;
                $isCompleted = $p->isCompleted();
            ?>
            <div class="sh-avatar" title="<?= e($displayName . ($isCompleted ? ' · wypełnił' : ' · oczekuje')) ?>">
                <div class="sh-avatar-pic">
                    <?php if (!$anonymous && $p->avatarPath): ?>
                        <img src="<?= e(asset($p->avatarPath)) ?>" alt="" style="border-color:<?= e($color) ?>">
                    <?php else: ?>
                        <span class="sh-avatar-init" style="background:<?= e($color) ?>;border-color:<?= e($color) ?>">
                            <?= e($anonymous ? (string) ($i + 1) : mb_strtoupper(mb_substr($p->nickname, 0, 1))) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($isCompleted): ?>
                        <span class="sh-avatar-check"><span class="iconify" data-icon="ph:check-bold"></span></span>
                    <?php endif; ?>
                </div>
                <div class="sh-avatar-name"><?= e($displayName) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
/* ============================================================
   SUMMARY HERO - landing v2 design
   ============================================================ */
.summary-hero { position: relative; padding: 0 0 160px; overflow: hidden; }
.sh-blob { position: absolute; border-radius: 50%; filter: blur(8px); opacity: .55; pointer-events: none; z-index: 0; }
.sh-blob-1 { width: 460px; height: 460px; background: radial-gradient(circle, rgba(255,107,53,0.30), transparent 65%); top: -100px; right: -120px; }
.sh-blob-2 { width: 380px; height: 380px; background: radial-gradient(circle, rgba(14,155,170,0.22), transparent 65%); bottom: -100px; left: -120px; }

.sh-banner-wrap { position: relative; width: 100%; line-height: 0; }
.sh-banner { width: 100%; height: auto; display: block; }
.sh-banner-fade { position: absolute; inset: auto 0 0 0; height: 30%;
  background: linear-gradient(to bottom, transparent, var(--bg)); pointer-events: none; }

.sh-body { position: relative; z-index: 1; max-width: 1100px; padding: 112px 24px 0; line-height: 1.6;
  display: flex; flex-direction: column; align-items: center; text-align: center; }

.sh-dates { font-size: 14px; padding: 9px 18px; }
.sh-dates .iconify { font-size: 16px; }

.sh-title {
  font-family: var(--font-display); font-weight: 800; letter-spacing: -0.025em; line-height: 1.04;
  font-size: clamp(42px, 6.2vw, 80px); color: var(--heading); margin: 20px 0 16px;
  text-wrap: balance;
}

.sh-lead { font-size: clamp(17px, 1.6vw, 22px); color: var(--fg-2); line-height: 1.55; margin: 0 0 24px; max-width: 720px;
  text-wrap: pretty; }

.sh-stats { display: flex; align-items: center; justify-content: center; gap: 20px; flex-wrap: wrap; margin-bottom: 32px; }
.sh-stat-inline { display: inline-flex; align-items: center; gap: 8px; font-size: 15px; color: var(--fg-2); font-weight: 500; }
.sh-stat-inline b { color: var(--heading); font-weight: 700; }
.sh-anon-inline { color: var(--teal-600); font-weight: 600; }

/* Avatary - centruje */
.sh-avatars { display: flex; flex-wrap: wrap; justify-content: center; gap: 18px 22px; }
.sh-avatar { text-align: center; display: flex; flex-direction: column; align-items: center; gap: 8px; }
.sh-avatar-pic { position: relative; width: 60px; height: 60px; }
.sh-avatar-pic img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid; box-shadow: var(--sh-sm); }
.sh-avatar-init { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; border-radius: 50%;
  color: #fff; font-family: var(--font-display); font-weight: 800; font-size: 22px; border: 3px solid; box-shadow: var(--sh-sm); }
.sh-avatar-check { position: absolute; bottom: -2px; right: -2px; width: 22px; height: 22px; border-radius: 50%;
  background: var(--green); color: #fff; display: grid; place-items: center; border: 2px solid var(--bg); }
.sh-avatar-check .iconify { font-size: 12px; }
.sh-avatar-name { font-size: 13px; font-weight: 700; color: var(--fg); }

@media (max-width: 720px) {
  .summary-hero { padding-bottom: var(--s8); }
  .sh-stat-num { font-size: 32px; }
  .sh-avatar-pic { width: 52px; height: 52px; }
  .sh-avatar-init { font-size: 19px; }
}
</style>
