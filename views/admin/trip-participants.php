<?php
/**
 * Lista uczestników wyjazdu + formularz dodania nowego.
 *
 * @var \App\Models\Trip $trip
 * @var list<\App\Models\Participant> $participants
 * @var array<string,string> $errors
 * @var array<string,mixed>  $old
 * @var string|null $flashSuccess
 * @var string|null $flashError
 */
use App\Helpers\Csrf;

$csrfToken = Csrf::token();
$reorderUrl = url('/admin/trips/' . $trip->id . '/participants/reorder');

$participants = $participants ?? [];
$errors       = $errors       ?? [];
$old          = $old          ?? [];
$flashSuccess = $flashSuccess ?? null;
$flashError   = $flashError   ?? null;

$summaryUrl = url('/summary/' . $trip->summaryPublicToken);
?>
<section class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8 py-10 md:py-14">

    <a href="<?= e(url('/admin')) ?>" class="text-sm text-mist hover:text-primary transition mb-4 inline-flex items-center gap-1">
        ← Wróć do listy wyjazdów
    </a>

    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
        <div>
            <h1 class="font-display font-bold text-3xl md:text-4xl text-ink dark:text-pale">
                <?= e($trip->name) ?>
            </h1>
            <p class="mt-1 text-mist font-mono text-sm">
                <?= e(date('d.m.Y', strtotime($trip->dateFrom))) ?> – <?= e(date('d.m.Y', strtotime($trip->dateTo))) ?>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="<?= e(url('/admin/trips/' . $trip->id . '/edit')) ?>"
               class="px-4 py-2 rounded-full bg-mist/15 text-ink dark:text-pale text-sm font-medium hover:bg-primary/15 transition">
                ⚙️ Edytuj wyjazd
            </a>
            <a href="<?= e($summaryUrl) ?>" target="_blank"
               class="px-4 py-2 rounded-full bg-primary/10 text-primary text-sm font-medium hover:bg-primary/20 transition">
                📺 Otwórz podsumowanie
            </a>
        </div>
    </div>

    <?php if ($flashSuccess): ?>
        <div class="mb-4 p-4 rounded-2xl bg-secondary/10 border border-secondary/30 text-sm">✅ <?= e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="mb-4 p-4 rounded-2xl bg-red-100 dark:bg-red-950/40 border border-red-300 dark:border-red-800 text-sm text-red-700 dark:text-red-300">⚠️ <?= e($flashError) ?></div>
    <?php endif; ?>

    <!-- Link do udostępnienia podsumowania -->
    <div class="mb-6 p-4 rounded-2xl bg-paper dark:bg-deep border border-mist/15">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex-1 min-w-0">
                <div class="text-xs font-medium text-mist mb-1">Publiczny link do podsumowania (TV)</div>
                <input type="text" readonly value="<?= e($summaryUrl) ?>" id="summary-link"
                       class="w-full px-3 py-2 rounded-xl bg-cream dark:bg-night border border-mist/15 text-sm font-mono text-ink dark:text-pale">
            </div>
            <button type="button" onclick="copyToClipboard('summary-link', this)"
                    class="px-4 py-2 rounded-full bg-primary-deep text-white text-sm font-medium hover:bg-primary transition shrink-0">
                📋 Skopiuj
            </button>
        </div>
    </div>

    <!-- Form dodania uczestnika -->
    <div class="mb-8 p-5 md:p-6 rounded-2xl bg-paper dark:bg-deep border border-mist/15">
        <h2 class="font-display font-bold text-lg md:text-xl mb-3 text-ink dark:text-pale">Dodaj uczestnika</h2>
        <form method="POST" action="<?= e(url('/admin/trips/' . $trip->id . '/participants')) ?>" enctype="multipart/form-data"
              class="grid sm:grid-cols-[1fr_auto_auto] gap-3 items-start">
            <?= Csrf::field() ?>
            <div>
                <input type="text" name="nickname" required maxlength="60"
                       value="<?= e((string) ($old['nickname'] ?? '')) ?>"
                       placeholder="Ksywka (np. Kasia, Tomek)"
                       class="w-full px-4 py-2.5 rounded-xl bg-cream dark:bg-night border-2 <?= isset($errors['nickname']) ? 'border-red-400' : 'border-mist/20' ?> focus:border-primary text-ink dark:text-pale outline-none transition">
                <?php if (isset($errors['nickname'])): ?><p class="mt-1 text-xs text-red-500"><?= e($errors['nickname']) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="block px-4 py-2.5 rounded-xl bg-cream dark:bg-night border-2 border-mist/20 hover:border-primary/40 cursor-pointer text-sm text-mist transition">
                    📷 Avatar
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="this.parentElement.querySelector('span').textContent = this.files[0]?.name || 'Avatar'">
                    <span class="hidden"></span>
                </label>
            </div>
            <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-primary-deep text-white font-semibold hover:bg-primary transition">
                Dodaj
            </button>
        </form>
        <?php if (isset($errors['avatar'])): ?><p class="mt-2 text-xs text-red-500"><?= e($errors['avatar']) ?></p><?php endif; ?>
    </div>

    <!-- Lista uczestników -->
    <?php if (empty($participants)): ?>
        <div class="rounded-2xl bg-paper dark:bg-deep border-2 border-dashed border-mist/30 p-8 text-center">
            <p class="text-mist">Jeszcze nikt - dodaj pierwszego uczestnika powyżej.</p>
        </div>
    <?php else: ?>
        <p class="mb-3 text-xs text-mist flex items-center gap-2">
            <span class="text-base">⠿</span>
            Przeciągnij za uchwyt po lewej żeby zmienić kolejność uczestników.
            <span id="reorder-status" class="ml-auto text-secondary font-medium hidden">✓ Zapisano kolejność</span>
        </p>
        <div class="space-y-3" id="participants-sortable" data-reorder-url="<?= e($reorderUrl) ?>" data-csrf="<?= e($csrfToken) ?>">
            <?php foreach ($participants as $p):
                $accessLink = url('/p/' . $p->accessToken);
                $isCompleted = $p->isCompleted();
                $linkId = 'link-' . $p->id;
            ?>
            <article id="participant-<?= e($p->id) ?>"
                     data-participant-id="<?= e($p->id) ?>"
                     class="rounded-2xl bg-paper dark:bg-deep border <?= $p->hidden ? 'border-amber-300 dark:border-amber-700/50' : 'border-mist/15' ?> p-4 md:p-5 <?= $p->hidden ? 'opacity-60' : '' ?>">
                <div class="flex flex-col md:flex-row md:items-center gap-4">
                    <!-- Drag handle -->
                    <button type="button"
                            class="drag-handle shrink-0 px-2 py-1 -ml-1 rounded-lg text-mist hover:text-primary hover:bg-mist/10 cursor-grab active:cursor-grabbing transition select-none touch-none"
                            title="Przeciągnij żeby zmienić kolejność"
                            aria-label="Uchwyt do przeciągania">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <circle cx="9" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/>
                            <circle cx="15" cy="6" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="15" cy="18" r="1.5"/>
                        </svg>
                    </button>
                    <!-- Avatar -->
                    <div class="shrink-0">
                        <?php if ($p->avatarPath): ?>
                            <img src="<?= e(asset($p->avatarPath)) ?>" alt="" class="w-12 h-12 rounded-full object-cover border-2 <?= $p->hidden ? 'border-amber-400 grayscale' : 'border-mist/15' ?>">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-full <?= $p->hidden ? 'bg-amber-500/15 text-amber-600' : 'bg-primary/15 text-primary' ?> font-bold flex items-center justify-center text-lg">
                                <?= e(mb_strtoupper(mb_substr($p->nickname, 0, 1))) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-display font-bold text-lg text-ink dark:text-pale"><?= e($p->nickname) ?></h3>
                            <?php if ($p->hidden): ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-400/20 text-amber-700 dark:text-amber-300"
                                      title="Nie pokazuje się w podsumowaniu - dane zachowane">
                                    🙈 ukryty w podsumowaniu
                                </span>
                            <?php endif; ?>
                            <?php if ($isCompleted): ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-secondary/15 text-secondary">✓ wypełnione</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-mist/15 text-mist">— oczekuje</span>
                            <?php endif; ?>
                        </div>
                        <div class="mt-1.5 flex items-center gap-2">
                            <input type="text" readonly value="<?= e($accessLink) ?>" id="<?= e($linkId) ?>"
                                   class="flex-1 min-w-0 px-2 py-1 rounded-lg bg-cream dark:bg-night border border-mist/15 text-xs font-mono text-mist">
                            <button type="button" onclick="copyToClipboard('<?= e($linkId) ?>', this)"
                                    class="px-2 py-1 rounded-lg bg-primary/10 text-primary text-xs font-medium hover:bg-primary/20 transition shrink-0">
                                📋
                            </button>
                        </div>
                    </div>

                    <!-- Akcje -->
                    <div class="flex flex-wrap gap-2 md:flex-nowrap">
                        <?php if ($isCompleted): ?>
                            <a href="<?= e(url('/admin/participants/' . $p->id . '/responses')) ?>"
                               class="px-3 py-2 rounded-full bg-mist/15 text-ink dark:text-pale text-sm font-medium hover:bg-primary/15 transition">
                                Odpowiedzi
                            </a>
                        <?php endif; ?>
                        <a href="<?= e(url('/admin/participants/' . $p->id . '/edit')) ?>"
                           class="px-3 py-2 rounded-full bg-mist/15 text-ink dark:text-pale text-sm font-medium hover:bg-primary/15 transition">
                            Edytuj
                        </a>
                        <!-- Toggle hidden - ukryj/przywróć w podsumowaniu -->
                        <form method="POST" action="<?= e(url('/admin/participants/' . $p->id . '/toggle-hidden')) ?>">
                            <?= Csrf::field() ?>
                            <?php if ($p->hidden): ?>
                                <button type="submit"
                                        class="px-3 py-2 rounded-full bg-secondary/15 text-secondary text-sm font-medium hover:bg-secondary/25 transition"
                                        title="Pokaż uczestnika w podsumowaniu">
                                    👁 Przywróć
                                </button>
                            <?php else: ?>
                                <button type="submit"
                                        class="px-3 py-2 rounded-full bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 text-sm font-medium hover:bg-amber-200 dark:hover:bg-amber-950/70 transition"
                                        title="Ukryj w podsumowaniu (dane zachowane, można przywrócić)">
                                    🙈 Ukryj
                                </button>
                            <?php endif; ?>
                        </form>
                        <form method="POST" action="<?= e(url('/admin/participants/' . $p->id . '/delete')) ?>"
                              onsubmit="return confirm('Usunąć uczestnika \'<?= e($p->nickname) ?>\' wraz z odpowiedziami?');">
                            <?= Csrf::field() ?>
                            <button type="submit" class="px-3 py-2 rounded-full bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-300 text-sm font-medium hover:bg-red-200 dark:hover:bg-red-950/70 transition">
                                Usuń
                            </button>
                        </form>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<script>
function copyToClipboard(elementId, btn) {
    const el = document.getElementById(elementId);
    if (!el) return;
    el.select();
    el.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(el.value).then(() => {
        const original = btn.innerHTML;
        btn.innerHTML = '✓ Skopiowano';
        btn.classList.add('bg-secondary', 'text-white');
        setTimeout(() => {
            btn.innerHTML = original;
            btn.classList.remove('bg-secondary', 'text-white');
        }, 1500);
    });
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
        status.classList.remove('hidden', 'text-secondary', 'text-red-500');
        status.classList.add(ok ? 'text-secondary' : 'text-red-500');
        clearTimeout(showStatus._t);
        showStatus._t = setTimeout(() => status.classList.add('hidden'), 2500);
    };

    new Sortable(list, {
        handle: '.drag-handle',
        animation: 180,
        ghostClass: 'opacity-30',
        chosenClass: 'ring-2',
        dragClass: 'shadow-pop',
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
            .then(data => {
                if (data && data.ok) {
                    showStatus('✓ Zapisano kolejność', true);
                } else {
                    showStatus('⚠️ Błąd zapisu', false);
                }
            })
            .catch(() => showStatus('⚠️ Błąd sieci', false));
        },
    });
})();
</script>
