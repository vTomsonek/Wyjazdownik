<?php
/**
 * Edycja uczestnika - ksywka + avatar.
 *
 * @var \App\Models\Trip        $trip
 * @var \App\Models\Participant $participant
 * @var array<string,string>    $errors
 * @var array<string,mixed>     $old
 * @var string|null             $flashSuccess
 * @var string|null             $flashError
 */
use App\Helpers\Csrf;

$errors       = $errors       ?? [];
$old          = $old          ?? [];
$flashSuccess = $flashSuccess ?? null;
$flashError   = $flashError   ?? null;

$nicknameValue = (string) ($old['nickname'] ?? $participant->nickname);
$accessLink    = url('/p/' . $participant->accessToken);
?>
<section class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 py-10 md:py-14">

    <a href="<?= e(url('/admin/trips/' . $trip->id . '/participants')) ?>" class="text-sm text-mist hover:text-primary transition mb-4 inline-flex items-center gap-1">
        ← Wróć do uczestników wyjazdu "<?= e($trip->name) ?>"
    </a>

    <h1 class="font-display font-bold text-3xl md:text-4xl text-ink dark:text-pale mb-2">
        Edycja uczestnika
    </h1>
    <p class="text-mist mb-8">Możesz zmienić ksywkę i avatar. Link dostępu zostaje ten sam.</p>

    <?php if ($flashSuccess): ?>
        <div class="mb-4 p-4 rounded-2xl bg-secondary/10 border border-secondary/30 text-sm">✅ <?= e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="mb-4 p-4 rounded-2xl bg-red-100 dark:bg-red-950/40 border border-red-300 dark:border-red-800 text-sm text-red-700 dark:text-red-300">⚠️ <?= e($flashError) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= e(url('/admin/participants/' . $participant->id . '/edit')) ?>" enctype="multipart/form-data"
          class="bg-paper dark:bg-deep rounded-3xl border border-mist/15 p-6 md:p-8 space-y-5">
        <?= Csrf::field() ?>

        <!-- Avatar preview + upload -->
        <div class="flex items-center gap-4">
            <?php if ($participant->avatarPath): ?>
                <img src="<?= e(asset($participant->avatarPath)) ?>" alt="" class="w-20 h-20 rounded-full object-cover border-2 border-mist/15">
            <?php else: ?>
                <div class="w-20 h-20 rounded-full bg-primary/15 text-primary font-bold flex items-center justify-center text-2xl">
                    <?= e(mb_strtoupper(mb_substr($participant->nickname, 0, 1))) ?>
                </div>
            <?php endif; ?>
            <div class="flex-1">
                <label for="avatar" class="block text-sm font-medium text-ink dark:text-pale mb-1.5">
                    Nowy avatar <span class="text-mist text-xs font-normal">(opcjonalnie, JPEG/PNG/WebP, max 2 MB)</span>
                </label>
                <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp"
                       class="block w-full text-sm text-mist file:mr-4 file:px-4 file:py-2 file:rounded-full file:border-0 file:bg-primary/10 file:text-primary file:font-medium hover:file:bg-primary/20 file:cursor-pointer">
                <?php if (isset($errors['avatar'])): ?><p class="mt-1 text-xs text-red-500"><?= e($errors['avatar']) ?></p><?php endif; ?>
            </div>
        </div>

        <!-- Kolor uczestnika -->
        <?php
        $palette = \App\Services\MapColorService::palette();
        $currentColor = $participant->color;
        ?>
        <div>
            <label class="block text-sm font-medium text-ink dark:text-pale mb-1.5">
                Kolor uczestnika
                <span class="text-mist text-xs font-normal">(używany na mapie i w rankingu)</span>
            </label>
            <div class="flex flex-wrap items-center gap-2 p-3 rounded-xl bg-cream dark:bg-night border-2 border-mist/15">
                <label class="cursor-pointer" title="Automatyczny (z access_token)">
                    <input type="radio" name="color" value="" <?= $currentColor === null ? 'checked' : '' ?> class="sr-only peer">
                    <span class="block w-9 h-9 rounded-full border-2 border-mist/30 peer-checked:border-ink dark:peer-checked:border-pale peer-checked:scale-110 transition flex items-center justify-center text-xs">auto</span>
                </label>
                <?php foreach ($palette as $hex): ?>
                <label class="cursor-pointer" title="<?= e($hex) ?>">
                    <input type="radio" name="color" value="<?= e($hex) ?>" <?= $currentColor === $hex ? 'checked' : '' ?> class="sr-only peer">
                    <span class="block w-9 h-9 rounded-full border-2 border-transparent peer-checked:border-ink dark:peer-checked:border-pale peer-checked:scale-110 transition" style="background:<?= e($hex) ?>"></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Ksywka -->
        <div>
            <label for="nickname" class="block text-sm font-medium text-ink dark:text-pale mb-1.5">Ksywka *</label>
            <input type="text" id="nickname" name="nickname" required maxlength="60"
                   value="<?= e($nicknameValue) ?>"
                   class="w-full px-4 py-3 rounded-xl bg-cream dark:bg-night border-2 <?= isset($errors['nickname']) ? 'border-red-400' : 'border-mist/20' ?> focus:border-primary text-ink dark:text-pale outline-none transition">
            <?php if (isset($errors['nickname'])): ?><p class="mt-1 text-xs text-red-500"><?= e($errors['nickname']) ?></p><?php endif; ?>
        </div>

        <!-- Link dostępu (read-only) -->
        <div class="pt-2 border-t border-mist/15">
            <label class="block text-sm font-medium text-ink dark:text-pale mb-1.5">Link dostępu uczestnika</label>
            <div class="flex gap-2">
                <input type="text" readonly value="<?= e($accessLink) ?>" id="access-link"
                       class="flex-1 px-3 py-2 rounded-xl bg-cream dark:bg-night border border-mist/15 text-xs font-mono text-mist">
                <button type="button" onclick="copyAccessLink(this)"
                        class="px-3 py-2 rounded-xl bg-primary/10 text-primary text-sm font-medium hover:bg-primary/20 transition">
                    📋 Skopiuj
                </button>
            </div>
            <p class="mt-2 text-xs text-mist">Wyślij ten link uczestnikowi - to jego "klucz" do wypełnienia ankiety.</p>
        </div>

        <!-- Status (read-only) -->
        <div class="grid sm:grid-cols-3 gap-3 pt-2 border-t border-mist/15">
            <div class="rounded-xl bg-cream dark:bg-night p-3">
                <div class="text-xs text-mist mb-0.5">Status</div>
                <div class="text-sm font-medium <?= $participant->isCompleted() ? 'text-secondary' : 'text-mist' ?>">
                    <?= $participant->isCompleted() ? '✓ wypełnione' : '— oczekuje' ?>
                </div>
            </div>
            <div class="rounded-xl bg-cream dark:bg-night p-3">
                <div class="text-xs text-mist mb-0.5">Pinezki na mapie</div>
                <div class="text-sm font-mono text-ink dark:text-pale"><?= $participant->mapPinsCount() ?></div>
            </div>
            <div class="rounded-xl bg-cream dark:bg-night p-3">
                <div class="text-xs text-mist mb-0.5">Niedostępne dni</div>
                <div class="text-sm font-mono text-ink dark:text-pale"><?= $participant->unavailableDatesCount() ?></div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex flex-wrap gap-3 pt-4 border-t border-mist/15">
            <button type="submit"
                    class="px-6 py-3 rounded-full bg-primary text-white font-semibold hover:bg-primary-dark hover:scale-[1.01] transition shadow-pop">
                Zapisz zmiany
            </button>
            <?php if ($participant->isCompleted()): ?>
                <a href="<?= e(url('/admin/participants/' . $participant->id . '/responses')) ?>"
                   class="px-5 py-3 rounded-full bg-mist/15 text-ink dark:text-pale font-medium hover:bg-primary/15 transition">
                    Zobacz odpowiedzi
                </a>
            <?php endif; ?>
            <a href="<?= e(url('/admin/trips/' . $trip->id . '/participants')) ?>"
               class="px-5 py-3 rounded-full bg-mist/15 text-ink dark:text-pale font-medium hover:bg-mist/25 transition">
                Anuluj
            </a>
        </div>
    </form>
</section>

<script>
function copyAccessLink(btn) {
    const el = document.getElementById('access-link');
    el.select(); el.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(el.value).then(() => {
        const original = btn.innerHTML;
        btn.innerHTML = '✓ Skopiowano';
        setTimeout(() => { btn.innerHTML = original; }, 1500);
    });
}
</script>
