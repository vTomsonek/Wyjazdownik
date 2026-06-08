<?php
/**
 * Strona powitalna uczestnika - landing v2 design.
 * Po otwarciu linku /p/{token}.
 *
 * @var \App\Models\Trip        $trip
 * @var \App\Models\Participant $participant
 * @var bool                    $isAdminEdit
 */
$isCompleted = $participant->isCompleted();
$startUrl    = url('/p/' . $participant->accessToken . '/wizard/1');
$mapUrl      = url('/p/' . $participant->accessToken . '/atrakcje');

$placesTotal   = (int) ($placesTotal ?? 0);
$placesMyVotes = (int) ($placesMyVotes ?? 0);
$placesMissing = $placesTotal - $placesMyVotes;

$fmtDate = static fn(string $d) => date('d.m.Y', strtotime($d));
?>

<?php if ($isAdminEdit): ?>
    <?php require BASE_PATH . '/views/partials/wizard/admin-banner.php'; ?>
<?php endif; ?>

<section class="pw-hero">
    <span class="pw-blob pw-blob-1"></span>
    <span class="pw-blob pw-blob-2"></span>

    <?php if ($trip->bannerImage): ?>
    <div class="pw-banner-wrap">
        <img class="pw-banner" src="<?= e(asset($trip->bannerImage)) ?>" alt="" fetchpriority="high" decoding="async">
        <div class="pw-banner-fade"></div>
    </div>
    <?php endif; ?>

    <div class="wrap pw-body">
        <span class="eyebrow eyebrow--teal pw-dates">
            <span class="iconify" data-icon="ph:calendar-blank-bold"></span>
            <?= e($fmtDate($trip->dateFrom)) ?> – <?= e($fmtDate($trip->dateTo)) ?>
        </span>

        <h1 class="pw-greeting">Cześć, <span class="pw-name"><?= e($participant->nickname) ?></span>!</h1>
        <p class="pw-lead">
            Pomóż zaplanować <strong><?= e($trip->name) ?></strong>.
        </p>

        <?php if (!empty($trip->description)): ?>
        <div class="pw-desc">
            <p><?= nl2br(e($trip->description)) ?></p>
        </div>
        <?php endif; ?>

        <!-- Status ocen miejsc - alert -->
        <?php if ($isCompleted && $placesTotal > 0 && $placesMissing > 0): ?>
        <a href="<?= e(url('/p/' . $participant->accessToken . '/atrakcje/oceniaj')) ?>" class="pw-alert pw-alert--warn">
            <span class="iconify pw-alert-icon" data-icon="ph:warning-circle-fill"></span>
            <div class="pw-alert-body">
                <div class="pw-alert-title">
                    Masz <?= $placesMissing ?> <?= $placesMissing === 1 ? 'miejsce' : ($placesMissing < 5 ? 'miejsca' : 'miejsc') ?> do oceny
                </div>
                <div class="pw-alert-sub">
                    Oceniłeś <?= $placesMyVotes ?> z <?= $placesTotal ?> atrakcji. Kliknij żeby ocenić jednym przyciskiem.
                </div>
            </div>
            <span class="iconify pw-alert-arrow" data-icon="ph:arrow-right-bold"></span>
        </a>
        <?php elseif ($isCompleted && $placesTotal > 0): ?>
        <div class="pw-alert pw-alert--ok">
            <span class="iconify pw-alert-icon" data-icon="ph:check-circle-fill"></span>
            <div class="pw-alert-body">
                <div class="pw-alert-title">Wszystko ocenione!</div>
                <div class="pw-alert-sub">Oceniłeś <?= $placesTotal ?> z <?= $placesTotal ?> atrakcji ekipy.</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- CTA -->
        <div class="pw-cta">
            <?php if ($isCompleted): ?>
                <p class="pw-status">
                    <span class="iconify" data-icon="ph:check-circle-bold" style="color:var(--green);font-size:18px;vertical-align:-3px"></span>
                    Już wypełniłeś tę ankietę <b><?= e($fmtDate((string) $participant->completedAt)) ?></b>. Możesz przejrzeć lub zedytować.
                </p>
                <div class="pw-btn-row">
                    <a class="btn btn-primary" href="<?= e($startUrl) ?>">
                        <span class="iconify" data-icon="ph:pencil-bold"></span>
                        Edytuj odpowiedzi
                    </a>
                    <a class="btn btn-ghost" href="<?= e($mapUrl) ?>">
                        <span class="iconify" data-icon="ph:map-trifold-bold"></span>
                        Mapa atrakcji ekipy
                    </a>
                </div>
            <?php else: ?>
                <a class="btn btn-primary btn-large" href="<?= e($startUrl) ?>">
                    <span class="iconify" data-icon="ph:play-fill"></span>
                    Zacznij wypełniać
                </a>
                <p class="pw-status">
                    <span class="iconify" data-icon="ph:clock-bold" style="font-size:14px;vertical-align:-2px"></span>
                    12 krótkich kroków · możesz przerwać i wrócić w każdej chwili
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
/* ============================================================
   PARTICIPANT WELCOME - landing v2 design
   ============================================================ */
.pw-hero { position: relative; padding: 0 0 120px; overflow: hidden; }
.pw-blob { position: absolute; border-radius: 50%; filter: blur(8px); opacity: .55; pointer-events: none; z-index: 0; }
.pw-blob-1 { width: 420px; height: 420px; background: radial-gradient(circle, rgba(255,107,53,0.30), transparent 65%); top: -100px; right: -120px; }
.pw-blob-2 { width: 360px; height: 360px; background: radial-gradient(circle, rgba(14,155,170,0.22), transparent 65%); bottom: -100px; left: -120px; }

.pw-banner-wrap { position: relative; width: 100%; line-height: 0; }
.pw-banner { width: 100%; height: auto; display: block; }
.pw-banner-fade { position: absolute; inset: auto 0 0 0; height: 30%;
  background: linear-gradient(to bottom, transparent, var(--bg)); pointer-events: none; }

.pw-body { position: relative; z-index: 1; max-width: 900px; padding: 80px 24px 0;
  display: flex; flex-direction: column; align-items: center; text-align: center; }

.pw-dates { font-size: 14px; padding: 9px 18px; }
.pw-dates .iconify { font-size: 16px; }

.pw-greeting {
  font-family: var(--font-display); font-weight: 800; letter-spacing: -0.025em; line-height: 1.05;
  font-size: clamp(36px, 5.5vw, 64px); color: var(--heading);
  margin: 20px 0 12px;
  text-wrap: balance;
}
.pw-name { color: var(--orange); }

.pw-lead { font-size: clamp(16px, 1.4vw, 19px); color: var(--fg-2); line-height: 1.55;
  margin: 0 0 24px; max-width: 600px; }
.pw-lead strong { color: var(--heading); font-weight: 700; }

.pw-desc {
  margin: 0 0 36px; max-width: 720px; width: 100%;
  background: var(--surface); border: 1px solid var(--line); border-radius: 18px;
  padding: 20px 24px; text-align: left;
}
.pw-desc p { color: var(--fg-2); line-height: 1.6; margin: 0; white-space: pre-wrap; }

/* Alert kart */
.pw-alert { display: inline-flex; align-items: center; gap: 14px; padding: 14px 18px; border-radius: 16px;
  border: 2px solid; max-width: 560px; width: 100%; text-decoration: none;
  transition: transform .18s, border-color .18s, box-shadow .18s; margin: 0 0 28px; }
.pw-alert:hover { transform: translateY(-2px); box-shadow: 0 14px 34px rgba(0,0,0,.10); }
.pw-alert--warn { background: rgba(244,63,94,.08); border-color: rgba(244,63,94,.30); color: var(--fg); }
.pw-alert--ok { background: rgba(14,155,170,.08); border-color: rgba(14,155,170,.30); color: var(--fg); cursor: default; }
.pw-alert--ok:hover { transform: none; box-shadow: none; }
.pw-alert-icon { font-size: 28px; flex-shrink: 0; }
.pw-alert--warn .pw-alert-icon { color: #F43F5E; }
.pw-alert--ok .pw-alert-icon { color: var(--teal); }
.pw-alert-body { flex: 1; text-align: left; }
.pw-alert-title { font-weight: 700; font-size: 15px; color: var(--heading); line-height: 1.3; }
.pw-alert-sub { font-size: 13px; color: var(--fg-2); margin-top: 2px; line-height: 1.4; }
.pw-alert-arrow { font-size: 20px; flex-shrink: 0; opacity: .6; }
.pw-alert:hover .pw-alert-arrow { opacity: 1; transform: translateX(3px); }

/* CTA section */
.pw-cta { margin-top: 8px; display: flex; flex-direction: column; align-items: center; gap: 14px; }
.pw-btn-row { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }
.pw-status { font-size: 14px; color: var(--fg-2); margin: 0; }
.pw-status b { color: var(--heading); font-weight: 700; }

.btn.btn-large { padding: 18px 32px; font-size: 17px; }

@media (max-width: 720px) {
  .pw-hero { padding-bottom: 80px; }
  .pw-body { padding-top: 56px; }
  .pw-greeting { font-size: clamp(28px, 8vw, 40px); }
  .pw-desc { padding: 16px 18px; margin-bottom: 28px; }
  .pw-alert { padding: 12px 14px; gap: 10px; }
  .pw-alert-icon { font-size: 24px; }
}
</style>
