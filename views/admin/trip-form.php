<?php
/**
 * Formularz nowego/edycji wyjazdu (shared).
 *
 * @var string                $mode          'new' | 'edit'
 * @var \App\Models\Trip|null $trip
 * @var array<string,string>  $errors
 * @var array<string,mixed>   $old
 * @var string|null           $flashError
 * @var string|null           $flashSuccess
 */
use App\Helpers\Csrf;

$mode         = $mode         ?? 'new';
$trip         = $trip         ?? null;
$errors       = $errors       ?? [];
$old          = $old          ?? [];
$flashError   = $flashError   ?? null;
$flashSuccess = $flashSuccess ?? null;

$val = static function (string $field, mixed $default = '') use ($old, $trip): mixed {
    if (array_key_exists($field, $old)) return $old[$field];
    if ($trip !== null) {
        return match ($field) {
            'name'                      => $trip->name,
            'slug'                      => $trip->slug,
            'description'               => (string) ($trip->description ?? ''),
            'date_from'                 => $trip->dateFrom,
            'date_to'                   => $trip->dateTo,
            'calendar_mode'             => $trip->calendarMode,
            'show_individual_responses' => $trip->showIndividualResponses,
            default => $default,
        };
    }
    return $default;
};

$isEdit = $mode === 'edit' && $trip !== null;
$action = $isEdit ? url('/admin/trips/' . $trip->id . '/edit') : url('/admin/trips');
?>
<section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-10 md:py-14">

    <a href="<?= e(url('/admin')) ?>" class="text-sm text-mist hover:text-primary transition mb-4 inline-flex items-center gap-1">
        ← Wróć do listy wyjazdów
    </a>

    <h1 class="font-display font-bold text-3xl md:text-4xl text-ink dark:text-pale mb-2">
        <?= $isEdit ? 'Edycja wyjazdu' : 'Nowy wyjazd' ?>
    </h1>
    <p class="text-mist mb-8">
        <?= $isEdit ? 'Zmień dane wyjazdu. Slug w URLu zachowuje historię - zmieniaj rozważnie.' : 'Tu zaczynasz. Najpierw nazwa i okno czasowe - resztę dodasz później.' ?>
    </p>

    <?php if ($flashSuccess): ?>
        <div class="mb-4 p-4 rounded-2xl bg-secondary/10 border border-secondary/30 text-sm">✅ <?= e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="mb-4 p-4 rounded-2xl bg-red-100 dark:bg-red-950/40 border border-red-300 dark:border-red-800 text-sm text-red-700 dark:text-red-300">⚠️ <?= e($flashError) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= e($action) ?>" enctype="multipart/form-data"
          class="bg-paper dark:bg-deep rounded-3xl border border-mist/15 p-6 md:p-8 space-y-5">
        <?= Csrf::field() ?>

        <!-- Nazwa -->
        <div>
            <label for="name" class="block text-sm font-medium text-ink dark:text-pale mb-1.5">Nazwa wyjazdu *</label>
            <input type="text" id="name" name="name" required maxlength="150"
                   value="<?= e((string) $val('name')) ?>"
                   placeholder="np. Lato 2026 z ekipą"
                   class="w-full px-4 py-3 rounded-xl bg-cream dark:bg-night border-2 <?= isset($errors['name']) ? 'border-red-400' : 'border-mist/20' ?> focus:border-primary text-ink dark:text-pale outline-none transition">
            <?php if (isset($errors['name'])): ?><p class="mt-1 text-xs text-red-500"><?= e($errors['name']) ?></p><?php endif; ?>
        </div>

        <!-- Slug -->
        <div>
            <label for="slug" class="block text-sm font-medium text-ink dark:text-pale mb-1.5">
                Slug (URL)
                <span class="font-normal text-mist text-xs">- automatyczny, ale możesz zmienić</span>
            </label>
            <input type="text" id="slug" name="slug" maxlength="160"
                   value="<?= e((string) $val('slug')) ?>"
                   placeholder="lato-2026-z-ekipa"
                   class="w-full px-4 py-3 rounded-xl bg-cream dark:bg-night border-2 <?= isset($errors['slug']) ? 'border-red-400' : 'border-mist/20' ?> focus:border-primary text-ink dark:text-pale outline-none transition font-mono text-sm">
            <?php if (isset($errors['slug'])): ?><p class="mt-1 text-xs text-red-500"><?= e($errors['slug']) ?></p><?php endif; ?>
        </div>

        <!-- Opis -->
        <div>
            <label for="description" class="block text-sm font-medium text-ink dark:text-pale mb-1.5">Opis</label>
            <textarea id="description" name="description" rows="4" maxlength="2000"
                      placeholder="Dla kogo, kiedy, jakie są ramy. Twoi znajomi to zobaczą gdy otworzą link."
                      class="w-full px-4 py-3 rounded-xl bg-cream dark:bg-night border-2 <?= isset($errors['description']) ? 'border-red-400' : 'border-mist/20' ?> focus:border-primary text-ink dark:text-pale outline-none transition"><?= e((string) $val('description')) ?></textarea>
            <?php if (isset($errors['description'])): ?><p class="mt-1 text-xs text-red-500"><?= e($errors['description']) ?></p><?php endif; ?>
        </div>

        <!-- Daty -->
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label for="date_from" class="block text-sm font-medium text-ink dark:text-pale mb-1.5">Okno - od *</label>
                <input type="date" id="date_from" name="date_from" required
                       value="<?= e((string) $val('date_from')) ?>"
                       class="w-full px-4 py-3 rounded-xl bg-cream dark:bg-night border-2 <?= isset($errors['date_from']) ? 'border-red-400' : 'border-mist/20' ?> focus:border-primary text-ink dark:text-pale outline-none transition">
                <?php if (isset($errors['date_from'])): ?><p class="mt-1 text-xs text-red-500"><?= e($errors['date_from']) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="date_to" class="block text-sm font-medium text-ink dark:text-pale mb-1.5">Okno - do *</label>
                <input type="date" id="date_to" name="date_to" required
                       value="<?= e((string) $val('date_to')) ?>"
                       class="w-full px-4 py-3 rounded-xl bg-cream dark:bg-night border-2 <?= isset($errors['date_to']) ? 'border-red-400' : 'border-mist/20' ?> focus:border-primary text-ink dark:text-pale outline-none transition">
                <?php if (isset($errors['date_to'])): ?><p class="mt-1 text-xs text-red-500"><?= e($errors['date_to']) ?></p><?php endif; ?>
            </div>
        </div>

        <!-- Tryb kalendarza -->
        <div>
            <label class="block text-sm font-medium text-ink dark:text-pale mb-2">Tryb kalendarza</label>
            <div class="space-y-2">
                <label class="flex items-start gap-3 p-3 rounded-xl bg-cream dark:bg-night border-2 border-mist/15 hover:border-primary/40 transition cursor-pointer has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                    <input type="radio" name="calendar_mode" value="block_unavailable"
                           <?= ((string) $val('calendar_mode', 'block_unavailable')) === 'block_unavailable' ? 'checked' : '' ?>
                           class="mt-1 accent-primary">
                    <div>
                        <div class="font-medium">Blokuj zajęte dni</div>
                        <div class="text-xs text-mist">Każdy uczestnik klika dni, w które NIE może. Najlepsze gdy widzicie konkretną wąską datę.</div>
                    </div>
                </label>
                <label class="flex items-start gap-3 p-3 rounded-xl bg-cream dark:bg-night border-2 border-mist/15 hover:border-primary/40 transition cursor-pointer has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                    <input type="radio" name="calendar_mode" value="select_preferred_weeks"
                           <?= ((string) $val('calendar_mode', 'block_unavailable')) === 'select_preferred_weeks' ? 'checked' : '' ?>
                           class="mt-1 accent-primary">
                    <div>
                        <div class="font-medium">Zaznacz preferowane tygodnie</div>
                        <div class="text-xs text-mist">Szersze okno (np. wakacje) - każdy zaznacza pasuje/może/nie pasuje na każdy tydzień.</div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Banner -->
        <div>
            <label for="banner_image" class="block text-sm font-medium text-ink dark:text-pale mb-1.5">
                Banner <span class="font-normal text-mist text-xs">(opcjonalnie, JPEG/PNG/WebP, max 2 MB)</span>
            </label>
            <?php if ($isEdit && $trip->bannerImage): ?>
                <img src="<?= e(asset($trip->bannerImage)) ?>" alt="Aktualny banner" class="mb-2 w-full h-32 object-cover rounded-xl border border-mist/15">
            <?php endif; ?>
            <input type="file" id="banner_image" name="banner_image" accept="image/jpeg,image/png,image/webp"
                   class="block w-full text-sm text-mist file:mr-4 file:px-4 file:py-2 file:rounded-full file:border-0 file:bg-primary/10 file:text-primary file:font-medium hover:file:bg-primary/20 file:cursor-pointer">
            <?php if (isset($errors['banner_image'])): ?><p class="mt-1 text-xs text-red-500"><?= e($errors['banner_image']) ?></p><?php endif; ?>
        </div>

        <!-- Show individual responses -->
        <div class="pt-2">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="show_individual_responses" value="1"
                       <?= ((bool) $val('show_individual_responses', true)) ? 'checked' : '' ?>
                       class="mt-1 w-4 h-4 accent-primary">
                <div>
                    <div class="font-medium">Pokazuj kto co odpowiedział na podsumowaniu</div>
                    <div class="text-xs text-mist">Wyłączone = tryb anonimowy: rankingi i cytaty bez ksywek.</div>
                </div>
            </label>
        </div>

        <!-- Submit -->
        <div class="flex flex-wrap gap-3 pt-4 border-t border-mist/15">
            <button type="submit"
                    class="px-6 py-3 rounded-full bg-primary-deep text-white font-semibold hover:bg-primary hover:scale-[1.01] transition shadow-pop">
                <?= $isEdit ? 'Zapisz zmiany' : 'Utwórz wyjazd' ?>
            </button>
            <a href="<?= e(url('/admin')) ?>"
               class="px-6 py-3 rounded-full bg-mist/15 text-ink dark:text-pale font-medium hover:bg-mist/25 transition">
                Anuluj
            </a>

            <?php if ($isEdit): ?>
                <form method="POST" action="<?= e(url('/admin/trips/' . $trip->id . '/delete')) ?>"
                      class="ml-auto" onsubmit="return confirm('Na pewno usunąć wyjazd \'<?= e($trip->name) ?>\'? Zostaną skasowani także wszyscy uczestnicy i ich odpowiedzi.');">
                    <?= Csrf::field() ?>
                    <button type="submit"
                            class="px-4 py-3 rounded-full bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-300 font-medium hover:bg-red-200 dark:hover:bg-red-950/70 transition">
                        Usuń wyjazd
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </form>
</section>
