<?php
/**
 * Formularz nowego/edycji wyjazdu (shared) - landing v2 design.
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
<section class="admin-page">
    <div class="wrap admin-form">

        <a href="<?= e(url('/admin')) ?>" class="admin-back">
            <span class="iconify" data-icon="ph:arrow-left-bold"></span> Wróć do listy wyjazdów
        </a>

        <header class="admin-head" style="margin-bottom:24px">
            <div>
                <h1 class="h-title"><?= $isEdit ? 'Edycja wyjazdu' : 'Nowy wyjazd' ?></h1>
                <p class="h-sub">
                    <?= $isEdit
                        ? 'Zmień dane wyjazdu. Slug w URLu zachowuje historię. Zmieniaj rozważnie.'
                        : 'Tu zaczynasz. Najpierw nazwa i okno czasowe. Resztę dodasz później.' ?>
                </p>
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

        <form method="POST" action="<?= e($action) ?>" enctype="multipart/form-data" class="form-card">
            <?= Csrf::field() ?>

            <!-- Nazwa -->
            <div class="form-row">
                <label for="name" class="form-label">Nazwa wyjazdu <span class="req">*</span></label>
                <input type="text" id="name" name="name" required maxlength="150"
                       value="<?= e((string) $val('name')) ?>"
                       placeholder="np. Lato 2026 z ekipą"
                       class="form-input<?= isset($errors['name']) ? ' has-error' : '' ?>">
                <?php if (isset($errors['name'])): ?>
                    <p class="form-error-msg"><?= e($errors['name']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Slug -->
            <div class="form-row">
                <label for="slug" class="form-label">
                    Slug (URL)
                    <span class="form-help" style="font-weight:500">— automatyczny, ale możesz zmienić</span>
                </label>
                <input type="text" id="slug" name="slug" maxlength="160"
                       value="<?= e((string) $val('slug')) ?>"
                       placeholder="lato-2026-z-ekipa"
                       class="form-input form-input--mono<?= isset($errors['slug']) ? ' has-error' : '' ?>">
                <?php if (isset($errors['slug'])): ?>
                    <p class="form-error-msg"><?= e($errors['slug']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Opis -->
            <div class="form-row">
                <label for="description" class="form-label">Opis</label>
                <textarea id="description" name="description" rows="4" maxlength="2000"
                          placeholder="Dla kogo, kiedy, jakie są ramy. Twoi znajomi to zobaczą gdy otworzą link."
                          class="form-textarea<?= isset($errors['description']) ? ' has-error' : '' ?>"><?= e((string) $val('description')) ?></textarea>
                <?php if (isset($errors['description'])): ?>
                    <p class="form-error-msg"><?= e($errors['description']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Punkt startowy -->
            <div class="form-inset">
                <label for="start_search" class="form-label">
                    <span class="iconify" data-icon="ph:house-bold" style="color:var(--orange)"></span>
                    Punkt startowy wyjazdu
                    <span class="form-help" style="font-weight:500">(opcjonalnie)</span>
                </label>
                <p class="form-help">Miasto z którego ekipa wyjeżdża. Algorytm doliczy dystans od startu do pierwszego miejsca.</p>
                <input type="text" id="start_search" autocomplete="off" maxlength="200"
                       placeholder="Wpisz miasto (np. Warszawa) — min 3 znaki"
                       value="<?= e((string) $val('start_name')) ?>"
                       class="form-input">
                <div id="start_search_results" class="search-results"></div>
                <input type="hidden" name="start_name" id="start_name" value="<?= e((string) $val('start_name')) ?>">
                <input type="hidden" name="start_lat"  id="start_lat"  value="<?= e((string) $val('start_lat')) ?>">
                <input type="hidden" name="start_lng"  id="start_lng"  value="<?= e((string) $val('start_lng')) ?>">
                <?php $hasStart = ((string) $val('start_name')) !== '' && ((string) $val('start_lat')) !== ''; ?>
                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
                    <p id="start_current" class="start-current"<?= $hasStart ? '' : ' style="display:none"' ?>>
                        <span class="iconify" data-icon="ph:check-circle-fill"></span>
                        Aktualnie: <span id="start_current_name"><?= e((string) $val('start_name')) ?></span>
                    </p>
                    <button type="button" id="start_clear" class="start-clear"<?= $hasStart ? '' : ' style="display:none"' ?>>
                        Usuń punkt startowy
                    </button>
                </div>
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
                    if (currentBox) currentBox.style.display = has ? '' : 'none';
                    if (clearBtn)   clearBtn.style.display   = has ? '' : 'none';
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
                    hiddenLat.value = '';
                    hiddenLng.value = '';
                    hiddenName.value = q;
                    updateCurrentDisplay();
                    if (q.length < 3) { resultsBox.innerHTML = ''; return; }
                    searchTimeout = setTimeout(() => fetchNominatim(q), 350);
                });

                async function fetchNominatim(q) {
                    resultsBox.innerHTML = '<div class="sr-info">Szukam...</div>';
                    try {
                        const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=5&accept-language=pl&q=' + encodeURIComponent(q);
                        const r = await fetch(url);
                        const data = await r.json();
                        if (!Array.isArray(data) || data.length === 0) {
                            resultsBox.innerHTML = '<div class="sr-info">Brak wyników.</div>';
                            return;
                        }
                        resultsBox.innerHTML = data.map((r, i) => {
                            const short = (r.display_name || '').length > 80 ? r.display_name.substring(0, 77) + '...' : r.display_name;
                            return `<button type="button" data-idx="${i}">
                                <div class="sr-title">${esc(r.name || short)}</div>
                                <div class="sr-sub">${esc(short)}</div>
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
                        resultsBox.innerHTML = '<div class="sr-info sr-info--error">Błąd wyszukiwania.</div>';
                    }
                }

                function esc(s) {
                    return String(s ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
                }
            })();
            </script>

            <!-- Daty -->
            <div class="form-row-grid">
                <div class="form-row">
                    <label for="date_from" class="form-label">Okno - od <span class="req">*</span></label>
                    <input type="date" id="date_from" name="date_from" required
                           value="<?= e((string) $val('date_from')) ?>"
                           class="form-date<?= isset($errors['date_from']) ? ' has-error' : '' ?>">
                    <?php if (isset($errors['date_from'])): ?>
                        <p class="form-error-msg"><?= e($errors['date_from']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="form-row">
                    <label for="date_to" class="form-label">Okno - do <span class="req">*</span></label>
                    <input type="date" id="date_to" name="date_to" required
                           value="<?= e((string) $val('date_to')) ?>"
                           class="form-date<?= isset($errors['date_to']) ? ' has-error' : '' ?>">
                    <?php if (isset($errors['date_to'])): ?>
                        <p class="form-error-msg"><?= e($errors['date_to']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tryb kalendarza -->
            <div class="form-row">
                <label class="form-label">Tryb kalendarza</label>
                <label class="radio-card">
                    <input type="radio" name="calendar_mode" value="block_unavailable"
                           <?= ((string) $val('calendar_mode', 'block_unavailable')) === 'block_unavailable' ? 'checked' : '' ?>>
                    <div>
                        <div class="rc-title">Blokuj zajęte dni</div>
                        <div class="rc-help">Każdy uczestnik klika dni, w które NIE może. Najlepsze gdy widzicie konkretną wąską datę.</div>
                    </div>
                </label>
                <label class="radio-card">
                    <input type="radio" name="calendar_mode" value="select_preferred_weeks"
                           <?= ((string) $val('calendar_mode', 'block_unavailable')) === 'select_preferred_weeks' ? 'checked' : '' ?>>
                    <div>
                        <div class="rc-title">Zaznacz preferowane tygodnie</div>
                        <div class="rc-help">Szersze okno (np. wakacje). Każdy zaznacza pasuje / może / nie pasuje na każdy tydzień.</div>
                    </div>
                </label>
            </div>

            <!-- Banner -->
            <div class="form-row">
                <label for="banner_image" class="form-label">
                    Banner <span class="form-help" style="font-weight:500">(opcjonalnie, JPEG/PNG/WebP, max 2 MB)</span>
                </label>
                <?php if ($isEdit && $trip->bannerImage): ?>
                    <img src="<?= e(asset($trip->bannerImage)) ?>" alt="Aktualny banner" class="banner-preview">
                <?php endif; ?>
                <label class="form-file">
                    <span class="iconify" data-icon="ph:image-square-bold"></span>
                    <span class="file-pick-lbl"><?= $isEdit && $trip->bannerImage ? 'Zmień banner' : 'Wybierz banner' ?></span>
                    <input type="file" id="banner_image" name="banner_image" accept="image/jpeg,image/png,image/webp"
                           onchange="this.parentElement.querySelector('.file-pick-lbl').textContent = this.files[0]?.name || 'Wybierz banner'">
                </label>
                <?php if (isset($errors['banner_image'])): ?>
                    <p class="form-error-msg"><?= e($errors['banner_image']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Show individual responses -->
            <div class="form-row">
                <label class="check-row">
                    <input type="checkbox" name="show_individual_responses" value="1"
                           <?= ((bool) $val('show_individual_responses', true)) ? 'checked' : '' ?>>
                    <div>
                        <div class="ck-title">Pokazuj kto co odpowiedział na podsumowaniu</div>
                        <div class="ck-help">Wyłączone = tryb anonimowy: rankingi i cytaty bez ksywek.</div>
                    </div>
                </label>
            </div>

            <!-- Submit -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg submit-cta">
                    <span class="iconify" data-icon="ph:check-bold"></span>
                    <?= $isEdit ? 'Zapisz zmiany' : 'Utwórz wyjazd' ?>
                </button>
                <a href="<?= e(url('/admin')) ?>" class="btn btn-ghost btn-lg cancel">Anuluj</a>

                <?php if ($isEdit): ?>
                    <form method="POST" action="<?= e(url('/admin/trips/' . $trip->id . '/delete')) ?>" class="delete-action"
                          onsubmit="return confirm('Na pewno usunąć wyjazd \'<?= e($trip->name) ?>\'? Zostaną skasowani także wszyscy uczestnicy i ich odpowiedzi.');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn-danger btn-lg">
                            <span class="iconify" data-icon="ph:trash-bold"></span> Usuń wyjazd
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </form>
    </div>
</section>
