<?php
/**
 * Lista uczestników wyjazdu + dodawanie. Landing v2 design.
 *
 * @var \App\Models\Trip $trip
 * @var list<\App\Models\Participant> $participants
 * @var array<string,string> $errors
 * @var array<string,mixed>  $old
 * @var string|null $flashSuccess
 * @var string|null $flashError
 */
use App\Helpers\Csrf;

$csrfToken    = Csrf::token();
$reorderUrl   = url('/admin/trips/' . $trip->id . '/participants/reorder');
$summaryUrl   = url('/summary/' . $trip->summaryPublicToken);
$participants = $participants ?? [];
$errors       = $errors       ?? [];
$old          = $old          ?? [];
$flashSuccess = $flashSuccess ?? null;
$flashError   = $flashError   ?? null;
?>
<section class="admin-page">
    <div class="wrap">

        <a href="<?= e(url('/admin')) ?>" class="admin-back">
            <span class="iconify" data-icon="ph:arrow-left-bold"></span> Wróć do listy wyjazdów
        </a>

        <header class="trip-head">
            <div>
                <h1 class="h-title"><?= e($trip->name) ?></h1>
                <p class="h-dates">
                    <?= e(date('d.m.Y', strtotime($trip->dateFrom))) ?> – <?= e(date('d.m.Y', strtotime($trip->dateTo))) ?>
                </p>
            </div>
            <div class="h-actions">
                <a href="<?= e(url('/admin/trips/' . $trip->id . '/edit')) ?>" class="btn btn-ghost">
                    <span class="iconify" data-icon="ph:gear-bold"></span> Edytuj wyjazd
                </a>
                <a href="<?= e($summaryUrl) ?>" target="_blank" rel="noopener" class="btn btn-primary">
                    <span class="iconify" data-icon="ph:television-simple-fill"></span> Otwórz podsumowanie
                </a>
            </div>
        </header>

        <?php if ($flashSuccess): ?>
            <div class="admin-flash admin-flash--success">
                <span class="iconify" data-icon="ph:check-circle-fill"></span><span><?= e($flashSuccess) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="admin-flash admin-flash--error">
                <span class="iconify" data-icon="ph:warning-circle-fill"></span><span><?= e($flashError) ?></span>
            </div>
        <?php endif; ?>

        <!-- Public summary link -->
        <div class="panel">
            <div class="copy-bar">
                <div class="input-wrap">
                    <div class="lbl">Publiczny link do podsumowania (TV)</div>
                    <input type="text" readonly value="<?= e($summaryUrl) ?>" id="summary-link" class="input-readonly">
                </div>
                <button type="button" class="btn btn-primary" onclick="copyToClipboard('summary-link', this)">
                    <span class="iconify" data-icon="ph:copy-bold"></span> Skopiuj
                </button>
            </div>
        </div>

        <!-- Add participant form -->
        <div class="panel">
            <div class="panel-head">
                <span class="iconify" data-icon="ph:user-plus-bold" style="font-size:22px;color:var(--orange)"></span>
                <span class="panel-title">Dodaj uczestnika</span>
            </div>
            <form method="POST" action="<?= e(url('/admin/trips/' . $trip->id . '/participants')) ?>" enctype="multipart/form-data" class="add-part-form">
                <?= Csrf::field() ?>
                <div>
                    <input type="text" name="nickname" required maxlength="60"
                           value="<?= e((string) ($old['nickname'] ?? '')) ?>"
                           placeholder="Ksywka (np. Kasia, Tomek)"
                           class="field-input<?= isset($errors['nickname']) ? ' has-error' : '' ?>">
                    <?php if (isset($errors['nickname'])): ?>
                        <p class="field-error"><?= e($errors['nickname']) ?></p>
                    <?php endif; ?>
                </div>
                <label class="file-pick">
                    <span class="iconify" data-icon="ph:image-square-bold"></span>
                    <span class="file-pick-lbl">Avatar</span>
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                           onchange="this.parentElement.querySelector('.file-pick-lbl').textContent = this.files[0]?.name || 'Avatar'">
                </label>
                <button type="submit" class="btn btn-primary">
                    <span class="iconify" data-icon="ph:plus-bold"></span> Dodaj
                </button>
            </form>
            <?php if (isset($errors['avatar'])): ?>
                <p class="field-error" style="margin-top:10px"><?= e($errors['avatar']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Participants list -->
        <?php if (empty($participants)): ?>
            <div class="parts-empty">Jeszcze nikt. Dodaj pierwszego uczestnika powyżej.</div>
        <?php else: ?>
            <p class="parts-hint">
                <span class="grip">⠿</span>
                Przeciągnij za uchwyt po lewej żeby zmienić kolejność uczestników.
                <span id="reorder-status" class="reorder-status"></span>
            </p>
            <div class="parts-list" id="participants-sortable" data-reorder-url="<?= e($reorderUrl) ?>" data-csrf="<?= e($csrfToken) ?>">
                <?php foreach ($participants as $p):
                    $accessLink  = url('/p/' . $p->accessToken);
                    $isCompleted = $p->isCompleted();
                    $linkId      = 'link-' . $p->id;
                ?>
                <article id="participant-<?= e($p->id) ?>"
                         data-participant-id="<?= e($p->id) ?>"
                         class="part<?= $p->hidden ? ' part--hidden' : '' ?>">
                    <button type="button" class="part-drag drag-handle" title="Przeciągnij żeby zmienić kolejność" aria-label="Uchwyt do przeciągania">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <circle cx="9" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/>
                            <circle cx="15" cy="6" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="15" cy="18" r="1.6"/>
                        </svg>
                    </button>

                    <?php if ($p->avatarPath): ?>
                        <img src="<?= e(asset($p->avatarPath)) ?>" alt="" class="part-avatar">
                    <?php else: ?>
                        <span class="part-avatar part-avatar--initial">
                            <?= e(mb_strtoupper(mb_substr($p->nickname, 0, 1))) ?>
                        </span>
                    <?php endif; ?>

                    <div class="part-info">
                        <div class="part-name">
                            <span class="nm"><?= e($p->nickname) ?></span>
                            <?php if ($p->hidden): ?>
                                <span class="tag tag--hidden" title="Nie pokazuje się w podsumowaniu - dane zachowane">
                                    <span class="iconify" data-icon="ph:eye-slash-bold"></span> ukryty
                                </span>
                            <?php endif; ?>
                            <?php if ($isCompleted): ?>
                                <span class="tag tag--ok"><span class="iconify" data-icon="ph:check-bold"></span> wypełnione</span>
                            <?php else: ?>
                                <span class="tag tag--pending">— oczekuje</span>
                            <?php endif; ?>
                        </div>
                        <div class="part-link">
                            <input type="text" readonly value="<?= e($accessLink) ?>" id="<?= e($linkId) ?>" class="input-readonly">
                            <button type="button" class="btn btn-ghost compact" onclick="copyToClipboard('<?= e($linkId) ?>', this)" aria-label="Kopiuj link">
                                <span class="iconify" data-icon="ph:copy-bold"></span>
                            </button>
                        </div>
                    </div>

                    <div class="part-actions">
                        <?php if ($isCompleted): ?>
                            <a href="<?= e(url('/admin/participants/' . $p->id . '/responses')) ?>" class="btn btn-ghost">
                                <span class="iconify" data-icon="ph:list-bullets-bold"></span> Odpowiedzi
                            </a>
                        <?php endif; ?>
                        <a href="<?= e(url('/admin/participants/' . $p->id . '/edit')) ?>" class="btn btn-ghost">
                            <span class="iconify" data-icon="ph:pencil-simple-bold"></span> Edytuj
                        </a>
                        <form method="POST" action="<?= e(url('/admin/participants/' . $p->id . '/toggle-hidden')) ?>" style="display:inline">
                            <?= Csrf::field() ?>
                            <?php if ($p->hidden): ?>
                                <button type="submit" class="btn btn-restore" title="Pokaż uczestnika w podsumowaniu">
                                    <span class="iconify" data-icon="ph:eye-bold"></span> Przywróć
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-warn" title="Ukryj w podsumowaniu (dane zachowane)">
                                    <span class="iconify" data-icon="ph:eye-slash-bold"></span> Ukryj
                                </button>
                            <?php endif; ?>
                        </form>
                        <form method="POST" action="<?= e(url('/admin/participants/' . $p->id . '/delete')) ?>"
                              onsubmit="return confirm('Usunąć uczestnika \'<?= e($p->nickname) ?>\' wraz z odpowiedziami?');" style="display:inline">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn-danger">
                                <span class="iconify" data-icon="ph:trash-bold"></span> Usuń
                            </button>
                        </form>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<script>
function copyToClipboard(elementId, btn) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const original = btn.innerHTML;
    navigator.clipboard.writeText(el.value).then(() => {
        btn.innerHTML = '<span class="iconify" data-icon="ph:check-bold"></span> Skopiowano';
        btn.classList.add('copied-flash');
        setTimeout(() => {
            btn.innerHTML = original;
            btn.classList.remove('copied-flash');
        }, 1500);
    }).catch(() => { /* noop */ });
}
</script>

<!-- Drag & drop kolejnosci uczestnikow -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const list = document.getElementById('participants-sortable');
    if (!list || typeof Sortable === 'undefined') return;

    const reorderUrl = list.getAttribute('data-reorder-url');
    const csrfToken  = list.getAttribute('data-csrf');
    const status     = document.getElementById('reorder-status');

    const showStatus = (text, ok) => {
        if (!status) return;
        status.textContent = text;
        status.classList.toggle('error', !ok);
        status.classList.add('visible');
        clearTimeout(showStatus._t);
        showStatus._t = setTimeout(() => status.classList.remove('visible'), 2500);
    };

    new Sortable(list, {
        handle: '.drag-handle',
        animation: 180,
        ghostClass: 'opacity-30',
        chosenClass: 'is-chosen',
        onEnd: () => {
            const order = Array.from(list.querySelectorAll('article[data-participant-id]'))
                .map(el => parseInt(el.getAttribute('data-participant-id'), 10))
                .filter(n => !isNaN(n));

            fetch(reorderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({ order }),
            })
            .then(r => r.json())
            .then(data => showStatus(data && data.ok ? '✓ Zapisano kolejność' : '⚠️ Błąd zapisu', !!(data && data.ok)))
            .catch(() => showStatus('⚠️ Błąd sieci', false));
        },
    });
})();
</script>
