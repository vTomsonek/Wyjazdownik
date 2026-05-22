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
            'start_name'                => $trip->startName ?? $default,
            'start_lat'                 => $trip->startLat !== null ? (string) $trip->startLat : $default,
            'start_lng'                 => $trip->startLng !== null ? (string) $trip->startLng : $default,
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

        <!-- Punkt startowy wyjazdu (opcjonalne) - uwzgledniany w algorytmie propozycji tras -->
        <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-4">
            <label for="start_search" class="block text-sm font-medium text-ink dark:text-pale mb-1.5">
                🏠 Punkt startowy wyjazdu <span class="text-mist font-normal">(opcjonalnie)</span>
            </label>
            <p class="text-xs text-mist mb-2.5">
                Miasto z którego ekipa wyjeżdża - algorytm doliczy dystans od startu do pierwszego miejsca.
            </p>
            <input type="text" id="start_search" autocomplete="off" maxlength="200"
                   placeholder="Wpisz miasto (np. Warszawa) - min 3 znaki"
                   value="<?= e((string) $val('start_name')) ?>"
                   class="w-full px-4 py-2.5 rounded-xl bg-cream dark:bg-night border-2 border-mist/20 focus:border-primary text-ink dark:text-pale outline-none transition">
            <div id="start_search_results" class="mt-1 max-h-48 overflow-y-auto"></div>
            <input type="hidden" name="start_name" id="start_name" value="<?= e((string) $val('start_name')) ?>">
            <input type="hidden" name="start_lat"  id="start_lat"  value="<?= e((string) $val('start_lat')) ?>">
            <input type="hidden" name="start_lng"  id="start_lng"  value="<?= e((string) $val('start_lng')) ?>">
            <?php $hasStart = ((string) $val('start_name')) !== '' && ((string) $val('start_lat')) !== ''; ?>
            <p id="start_current" class="mt-2 text-xs text-secondary <?= $hasStart ? '' : 'hidden' ?>">
                ✓ Aktualnie: <span id="start_current_name"><?= e((string) $val('start_name')) ?></span>
            </p>
            <button type="button" id="start_clear" class="mt-2 ml-3 text-xs text-mist hover:text-red-500 transition <?= $hasStart ? '' : 'hidden' ?>">
                Usuń punkt startowy
            </button>
        </div>

        <script>
        (function () {
            const searchInput = document.getElementById('start_search');
            const resultsBox  = document.getElementById('start_search_results');
            const hiddenName  = document.getElementById('start_name');
            const hiddenLat   = document.getElementById('start_lat');
            const hiddenLng   = document.getElementById('start_lng');
            const clearBtn    = document.getElementById('start_clear');
            const currentBox  = document.getElementById('start_current');
            const currentName = document.getElementById('start_current_name');
            if (!searchInput || !resultsBox) return;

            function updateCurrentDisplay() {
                const has = hiddenName.value.trim() !== '' && hiddenLat.value !== '';
                if (currentBox) currentBox.classList.toggle('hidden', !has);
                if (clearBtn)   clearBtn.classList.toggle('hidden', !has);
                if (currentName && has) currentName.textContent = hiddenName.value;
            }

            clearBtn?.addEventListener('click', () => {
                searchInput.value = '';
                hiddenName.value = '';
                hiddenLat.value = '';
                hiddenLng.value = '';
                resultsBox.innerHTML = '';
                updateCurrentDisplay();
            });

            let searchTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                const q = searchInput.value.trim();
                // Reset gdy user zmienia - musi wybrac z listy
                hiddenLat.value = '';
                hiddenLng.value = '';
                hiddenName.value = q;
                updateCurrentDisplay();
                if (q.length < 3) { resultsBox.innerHTML = ''; return; }
                searchTimeout = setTimeout(() => fetchNominatim(q), 350);
            });

            async function fetchNominatim(q) {
                resultsBox.innerHTML = '<div class="text-xs text-mist p-2">Szukam...</div>';
                try {
                    const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=5&accept-language=pl&q=' + encodeURIComponent(q);
                    const r = await fetch(url);
                    const data = await r.json();
                    if (!Array.isArray(data) || data.length === 0) {
                        resultsBox.innerHTML = '<div class="text-xs text-mist p-2">Brak wyników.</div>';
                        return;
                    }
                    resultsBox.innerHTML = data.map((r, i) => {
                        const short = (r.display_name || '').length > 80 ? r.display_name.substring(0, 77) + '...' : r.display_name;
                        return `<button type="button" data-idx="${i}" class="w-full text-left px-3 py-2 rounded-lg hover:bg-primary/10 transition text-sm border-b border-mist/10 last:border-b-0">
                            <div class="font-medium text-ink dark:text-pale">${esc(r.name || short)}</div>
                            <div class="text-xs text-mist">${esc(short)}</div>
                        </button>`;
                    }).join('');
                    resultsBox.querySelectorAll('button[data-idx]').forEach(btn => {
                        btn.addEventListener('click', () => {
                            const r = data[parseInt(btn.getAttribute('data-idx'), 10)];
                            const displayName = r.name || (r.display_name || '').split(',')[0] || '';
                            searchInput.value = displayName;
                            hiddenName.value  = displayName;
                            hiddenLat.value   = String(r.lat);
                            hiddenLng.value   = String(r.lon);
                            resultsBox.innerHTML = '';
                            updateCurrentDisplay();
                        });
                    });
                } catch (e) {
                    resultsBox.innerHTML = '<div class="text-xs text-red-500 p-2">Błąd wyszukiwania.</div>';
                }
            }

            function esc(s) {
                return String(s ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
            }
        })();
        </script>

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
                <img src="<?= e(asset($trip->bannerImage)) ?>" alt="Aktualny banner" class="mb-2 w-full max-h-72 object-contain rounded-xl border border-mist/15 bg-cream dark:bg-night">
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
