/**
 * Wizard uczestnika - auto-save AJAX + walidacja klienta + kalendarz dostępności.
 *
 * Konwencje DOM:
 *   <section data-wizard data-token data-csrf data-save-url> ...
 *     <input data-autosave name="X" value="Y">
 *     <input data-autosave-multi="key" type="checkbox" value="Y">
 *     <input data-autosave-lang name="languages[english]" value="basic">
 *
 *   max selections: container has data-max-selections="3", count w [data-multi-count="key"]
 *
 *   slider live preview: span [data-slider-value="key"], input [data-slider-input="key"]
 *
 *   kalendarz: <div data-availability-mode="block_unavailable" data-date-from data-date-to data-unavailable>
 *              <div id="availability-calendar"></div>
 *   weeks: <div data-availability-mode="select_preferred_weeks" data-date-from data-date-to data-weeks>
 *          <div id="availability-weeks"></div>
 *
 *   languages "więcej": [data-lang-toggle-more] toggluje [data-lang-extra]
 *   languages quick "none": [data-lang-quick="none"] ustawia wszystkie na 'none'
 */

(function () {
    'use strict';

    const wizard = document.querySelector('[data-wizard]');
    if (!wizard) return;

    const csrf      = wizard.getAttribute('data-csrf') || '';
    const saveUrl   = wizard.getAttribute('data-save-url') || '';
    const status    = document.querySelector('[data-save-status]');

    // --- pomocnicze ---
    function setStatus(text, color) {
        if (!status) return;
        status.textContent = text;
        status.style.color = color || '';
    }
    let saveTimers = {};

    function postSave(key, value) {
        setStatus('Zapisuję…', '');
        return fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ _csrf: csrf, key, value }),
        })
        .then(r => r.json())
        .then(j => {
            if (j.ok) {
                setStatus('✓ Zapisano', '#10B981');
                setTimeout(() => setStatus('Auto-zapis aktywny', ''), 1500);
            } else {
                setStatus('✗ ' + (j.error || 'Błąd zapisu'), '#EF4444');
            }
            return j;
        })
        .catch(() => setStatus('✗ Błąd sieci', '#EF4444'));
    }

    function debounceSave(key, value, delay = 500) {
        clearTimeout(saveTimers[key]);
        saveTimers[key] = setTimeout(() => postSave(key, value), delay);
    }

    // --- 1. Single radio / slider / textarea --------------------------
    wizard.querySelectorAll('[data-autosave]').forEach(el => {
        const key  = el.name;
        const tag  = el.tagName;
        const type = el.type || tag.toLowerCase();

        const handler = () => {
            let val = el.value;
            if (type === 'radio') {
                if (!el.checked) return;
                val = el.value;
            }
            debounceSave(key, val, type === 'range' || tag === 'TEXTAREA' ? 600 : 0);
        };

        if (tag === 'TEXTAREA' || type === 'range') el.addEventListener('input', handler);
        else el.addEventListener('change', handler);
    });

    // --- 2. Multi checkboxes (group by data-autosave-multi) -----------
    const multiGroups = {};
    wizard.querySelectorAll('[data-autosave-multi]').forEach(el => {
        const k = el.getAttribute('data-autosave-multi');
        (multiGroups[k] = multiGroups[k] || []).push(el);
    });
    Object.entries(multiGroups).forEach(([key, els]) => {
        const container = els[0].closest('[data-max-selections]');
        const max = container ? parseInt(container.getAttribute('data-max-selections'), 10) : 0;
        const counter = document.querySelector('[data-multi-count="' + key + '"]');

        const refresh = () => {
            const checked = els.filter(e => e.checked);
            if (counter) counter.textContent = checked.length;
            if (max > 0) {
                els.forEach(e => {
                    if (!e.checked) e.disabled = checked.length >= max;
                });
            }
        };
        refresh();

        els.forEach(el => {
            el.addEventListener('change', () => {
                refresh();
                const value = els.filter(e => e.checked).map(e => e.value);
                debounceSave(key, value, 200);
            });
        });
    });

    // --- 3. Languages grid --------------------------------------------
    const langGrid = wizard.querySelector('[data-language-grid]');
    if (langGrid) {
        const groupName = langGrid.getAttribute('data-language-grid');
        const collectLangs = () => {
            const out = {};
            langGrid.querySelectorAll('input[type=radio]:checked').forEach(input => {
                const m = input.name.match(/^languages\[(\w+)\]$/);
                if (m) out[m[1]] = input.value;
            });
            return out;
        };
        langGrid.querySelectorAll('input[type=radio]').forEach(input => {
            input.addEventListener('change', () => debounceSave(groupName, collectLangs(), 200));
        });

        const moreBtn = langGrid.querySelector('[data-lang-toggle-more]');
        if (moreBtn) {
            moreBtn.addEventListener('click', () => {
                const extras = langGrid.querySelectorAll('[data-lang-extra]');
                extras.forEach(row => row.classList.toggle('hidden'));
                moreBtn.textContent = extras[0]?.classList.contains('hidden') ? 'Pokaż więcej' : 'Pokaż mniej';
            });
        }

        const noneBtn = langGrid.querySelector('[data-lang-quick="none"]');
        if (noneBtn) {
            noneBtn.addEventListener('click', () => {
                langGrid.querySelectorAll('input[type=radio][value="none"]').forEach(r => { r.checked = true; });
                debounceSave(groupName, collectLangs(), 0);
            });
        }
    }

    // --- 4. Slider live preview ---------------------------------------
    const formatThousands = (n) => Number(n).toLocaleString('pl-PL').replace(/,/g, ' ');
    wizard.querySelectorAll('[data-slider-input]').forEach(input => {
        const key = input.getAttribute('data-slider-input');
        const valueEl = wizard.querySelector('[data-slider-value="' + key + '"]');
        if (valueEl) {
            const useThousands = valueEl.getAttribute('data-slider-format') === 'thousands';
            input.addEventListener('input', () => {
                valueEl.textContent = useThousands ? formatThousands(input.value) : input.value;
            });
        }
    });

    // --- 5. Availability calendar -------------------------------------
    const calBlock = wizard.querySelector('[data-availability-mode="block_unavailable"]');
    if (calBlock) renderBlockCalendar(calBlock);

    const weekBlock = wizard.querySelector('[data-availability-mode="select_preferred_weeks"]');
    if (weekBlock) renderWeekSelector(weekBlock);

    function renderBlockCalendar(host) {
        const dateFrom = host.getAttribute('data-date-from');
        const dateTo   = host.getAttribute('data-date-to');
        const initial  = JSON.parse(host.getAttribute('data-unavailable') || '[]');
        const blocked  = new Set(initial);
        const target   = host.querySelector('#availability-calendar');
        if (!target) return;

        const monthsHtml = buildMonths(dateFrom, dateTo, blocked);
        target.innerHTML = monthsHtml;

        target.addEventListener('click', e => {
            const cell = e.target.closest('[data-day]');
            if (!cell) return;
            const day = cell.getAttribute('data-day');
            if (blocked.has(day)) {
                blocked.delete(day);
                cell.classList.remove('bg-red-200', 'dark:bg-red-900/40', 'text-red-700', 'dark:text-red-300', 'line-through');
                cell.classList.add('hover:bg-primary/10');
            } else {
                blocked.add(day);
                cell.classList.add('bg-red-200', 'dark:bg-red-900/40', 'text-red-700', 'dark:text-red-300', 'line-through');
                cell.classList.remove('hover:bg-primary/10');
            }
            debounceSave('_unavailable_dates', Array.from(blocked), 200);
        });
    }

    function buildMonths(fromStr, toStr, blockedSet) {
        const from = new Date(fromStr + 'T00:00:00');
        const to   = new Date(toStr   + 'T00:00:00');
        const monthNames = ['Styczeń','Luty','Marzec','Kwiecień','Maj','Czerwiec','Lipiec','Sierpień','Wrzesień','Październik','Listopad','Grudzień'];
        const dayNames = ['Pn','Wt','Śr','Cz','Pt','So','Nd'];

        let html = '<div class="grid sm:grid-cols-2 gap-4">';
        let cursor = new Date(from.getFullYear(), from.getMonth(), 1);
        while (cursor <= to) {
            html += '<div><div class="font-display font-bold text-sm mb-2">' + monthNames[cursor.getMonth()] + ' ' + cursor.getFullYear() + '</div>';
            html += '<div class="grid grid-cols-7 gap-0.5 text-xs">';
            for (const dn of dayNames) html += '<div class="text-center text-mist py-1">' + dn + '</div>';
            // Empty cells before 1st
            const firstDay = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
            const startWd = (firstDay.getDay() + 6) % 7; // 0=poniedziałek
            for (let i = 0; i < startWd; i++) html += '<div></div>';
            // Days
            const lastDate = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 0).getDate();
            for (let d = 1; d <= lastDate; d++) {
                const date = new Date(cursor.getFullYear(), cursor.getMonth(), d);
                // Buduj ISO z LOKALNYCH komponentow - toISOString() konwertuje na UTC
                // i w strefie CET (UTC+1/+2) odejmuje 1 dzien (lokalna polnoc = wczoraj UTC).
                const iso = date.getFullYear() + '-' +
                            String(date.getMonth() + 1).padStart(2, '0') + '-' +
                            String(date.getDate()).padStart(2, '0');
                const inRange = date >= from && date <= to;
                if (!inRange) {
                    html += '<div class="text-center py-1.5 text-mist/40">' + d + '</div>';
                } else {
                    const blocked = blockedSet.has(iso);
                    const cls = blocked
                        ? 'bg-red-200 dark:bg-red-900/40 text-red-700 dark:text-red-300 line-through'
                        : 'hover:bg-primary/10 cursor-pointer';
                    html += '<div data-day="' + iso + '" class="text-center py-1.5 rounded ' + cls + '">' + d + '</div>';
                }
            }
            html += '</div></div>';
            cursor = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1);
        }
        html += '</div>';
        return html;
    }

    function renderWeekSelector(host) {
        const fromStr = host.getAttribute('data-date-from');
        const toStr   = host.getAttribute('data-date-to');
        const weeks   = JSON.parse(host.getAttribute('data-weeks') || '{}');
        const target  = host.querySelector('#availability-weeks');
        if (!target) return;

        const from = new Date(fromStr + 'T00:00:00');
        const to   = new Date(toStr   + 'T00:00:00');
        // Aligning to Monday
        const monday = new Date(from);
        monday.setDate(from.getDate() - ((from.getDay() + 6) % 7));

        const labels = { yes: '✅ Pasuje', maybe: '🤔 Może', no: '❌ Nie pasuje' };
        let html = '';
        let weekStart = new Date(monday);
        while (weekStart <= to) {
            // Lokalny ISO (toISOString() konwertuje na UTC i przesuwa o 1 dzien w CET)
            const iso = weekStart.getFullYear() + '-' +
                        String(weekStart.getMonth() + 1).padStart(2, '0') + '-' +
                        String(weekStart.getDate()).padStart(2, '0');
            const end = new Date(weekStart); end.setDate(end.getDate() + 6);
            const fmt = d => String(d.getDate()).padStart(2,'0') + '.' + String(d.getMonth()+1).padStart(2,'0');
            const current = weeks[iso] || '';
            html += '<div class="flex flex-col sm:flex-row sm:items-center gap-2 p-3 rounded-xl bg-paper dark:bg-deep border border-mist/15">';
            html += '  <div class="font-mono text-sm text-ink dark:text-pale w-32 shrink-0">' + fmt(weekStart) + ' – ' + fmt(end) + '</div>';
            html += '  <div class="flex flex-wrap gap-1.5">';
            for (const [val, lbl] of Object.entries(labels)) {
                const active = current === val;
                const cls = active ? 'bg-primary text-white' : 'bg-mist/10 text-ink dark:text-pale hover:bg-mist/20';
                html += '<button type="button" data-week="' + iso + '" data-pref="' + val + '" class="px-3 py-1.5 rounded-full text-xs font-medium transition ' + cls + '">' + lbl + '</button>';
            }
            html += '  </div></div>';
            weekStart = new Date(weekStart); weekStart.setDate(weekStart.getDate() + 7);
        }
        target.innerHTML = html;

        target.addEventListener('click', e => {
            const btn = e.target.closest('[data-week]');
            if (!btn) return;
            const w = btn.getAttribute('data-week');
            const p = btn.getAttribute('data-pref');
            weeks[w] = p;
            // Refresh row
            const row = btn.parentElement;
            row.querySelectorAll('[data-week]').forEach(b => {
                const isActive = b.getAttribute('data-pref') === p;
                b.className = 'px-3 py-1.5 rounded-full text-xs font-medium transition ' +
                    (isActive ? 'bg-primary text-white' : 'bg-mist/10 text-ink dark:text-pale hover:bg-mist/20');
            });
            debounceSave('_preferred_weeks', weeks, 200);
        });
    }
})();
