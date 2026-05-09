/**
 * Krok 10 wizarda - mapa Leaflet + Leaflet.draw.
 * Helpery (pinSvg, escapeHtml, buildPopup, geojsonToLayer) w map-utils.js.
 */
(function () {
    'use strict';

    if (typeof L === 'undefined' || !window.MapUtils) return;
    const wizard = document.querySelector('[data-wizard]');
    if (!wizard) return;

    const csrf  = wizard.getAttribute('data-csrf') || '';
    const apiBase = (wizard.getAttribute('data-save-url') || '').replace(/\/save$/, '') + '/map/pins';
    const U = window.MapUtils;

    let myColor = '#FF6B35';
    const layerByPinId = new Map();
    const pinById = new Map();

    // Mapa
    const map = L.map('participant-map').setView([52.0, 19.0], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap',
    }).addTo(map);

    const drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

    const drawControl = new L.Control.Draw({
        position: 'topright',
        draw: {
            marker:    { icon: U.pinIcon(myColor) },
            polyline:  { shapeOptions: { color: myColor, weight: 4 } },
            polygon:   { shapeOptions: { color: myColor, weight: 3, fillOpacity: 0.2 } },
            rectangle: false, circle: false, circlemarker: false,
        },
        edit: { featureGroup: drawnItems, edit: false },
    });
    map.addControl(drawControl);

    // Modal
    const modal       = document.querySelector('[data-pin-modal]');
    const modalTitle  = modal.querySelector('[data-modal-title]');
    const modalLabel  = modal.querySelector('[data-modal-label]');
    const modalDesc   = modal.querySelector('[data-modal-description]');
    const modalSave   = modal.querySelector('[data-modal-save]');
    const modalCancel = modal.querySelector('[data-modal-cancel]');
    let saveCb = null, cancelCb = null;

    function showModal(title, defaults, onSave, onCancel) {
        modalTitle.textContent = title;
        modalLabel.value = (defaults && defaults.label) || '';
        modalDesc.value  = (defaults && defaults.description) || '';
        saveCb   = onSave;
        cancelCb = onCancel;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => modalLabel.focus(), 50);
    }
    function hideModal(triggerCancel) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (triggerCancel && cancelCb) { try { cancelCb(); } catch(e) {} }
        saveCb = null; cancelCb = null;
    }
    modalCancel.addEventListener('click', () => hideModal(true));
    modal.addEventListener('click', e => { if (e.target === modal) hideModal(true); });
    modalSave.addEventListener('click', () => {
        const cb = saveCb;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        saveCb = null; cancelCb = null;
        if (cb) cb({ label: modalLabel.value.trim(), description: modalDesc.value.trim() });
    });

    // AJAX
    function api(path, method, body) {
        return fetch(apiBase + path, {
            method: method || 'GET',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: body ? JSON.stringify(Object.assign({ _csrf: csrf }, body)) : undefined,
        }).then(r => r.json());
    }

    // Lista pinezek
    const listEl  = document.querySelector('[data-pin-list]');
    const countEl = document.querySelector('[data-pin-count]');
    const emptyEl = document.querySelector('[data-pin-empty]');

    function refreshList() {
        countEl.textContent = pinById.size;
        listEl.querySelectorAll('[data-pin-item]').forEach(n => n.remove());
        if (pinById.size === 0) { emptyEl.style.display = ''; return; }
        emptyEl.style.display = 'none';
        const sorted = Array.from(pinById.values()).sort((a, b) => a.id - b.id);
        for (const p of sorted) {
            const item = document.createElement('div');
            item.setAttribute('data-pin-item', String(p.id));
            item.className = 'p-3 rounded-xl bg-cream dark:bg-night border border-mist/15 group';
            const typeLabel = { marker: '📍 Pinezka', polyline: '➡️ Trasa', polygon: '⬡ Obszar' }[p.pin_type] || p.pin_type;
            item.innerHTML =
                '<div class="flex items-start gap-2">' +
                '  <div class="w-3 h-3 rounded-full mt-1.5 shrink-0" style="background:' + U.escapeHtml(p.color || myColor) + '"></div>' +
                '  <div class="flex-1 min-w-0">' +
                '    <div class="text-xs text-mist font-mono">' + U.escapeHtml(typeLabel) + '</div>' +
                '    <div class="font-medium text-ink dark:text-pale text-sm">' + U.escapeHtml(p.label || '(bez etykiety)') + '</div>' +
                (p.description ? '    <div class="text-xs text-mist mt-1 line-clamp-2">' + U.escapeHtml(p.description) + '</div>' : '') +
                '  </div>' +
                '</div>' +
                '<div class="flex gap-1 mt-2 opacity-70 group-hover:opacity-100 transition">' +
                '  <button data-act="zoom"   data-id="' + p.id + '" class="px-2 py-1 rounded-lg bg-mist/10 text-xs hover:bg-primary/15 transition">Pokaż</button>' +
                '  <button data-act="edit"   data-id="' + p.id + '" class="px-2 py-1 rounded-lg bg-mist/10 text-xs hover:bg-primary/15 transition">Edytuj</button>' +
                '  <button data-act="delete" data-id="' + p.id + '" class="px-2 py-1 rounded-lg bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-300 text-xs hover:bg-red-200 transition ml-auto">Usuń</button>' +
                '</div>';
            listEl.appendChild(item);
        }
    }

    listEl.addEventListener('click', e => {
        const btn = e.target.closest('[data-act]');
        if (!btn) return;
        const id = parseInt(btn.getAttribute('data-id'), 10);
        const pin = pinById.get(id);
        if (!pin) return;
        const act = btn.getAttribute('data-act');
        if (act === 'zoom') {
            const layer = layerByPinId.get(id);
            if (layer && layer.getBounds) map.fitBounds(layer.getBounds(), { maxZoom: 12, padding: [40, 40] });
            else if (layer && layer.getLatLng) map.setView(layer.getLatLng(), 12);
        } else if (act === 'edit') {
            showModal('Edytuj opis', { label: pin.label, description: pin.description }, vals => {
                api('/' + id + '/update', 'POST', vals).then(j => {
                    if (j.ok && j.pin) {
                        pinById.set(id, j.pin);
                        const layer = layerByPinId.get(id);
                        if (layer) layer.bindPopup(U.buildPopup(j.pin));
                        refreshList();
                    }
                });
            }, null);
        } else if (act === 'delete') {
            if (!confirm('Usunąć tę pinezkę?')) return;
            api('/' + id + '/delete', 'POST', {}).then(j => {
                if (j.ok) {
                    const layer = layerByPinId.get(id);
                    if (layer) drawnItems.removeLayer(layer);
                    layerByPinId.delete(id); pinById.delete(id);
                    refreshList();
                }
            });
        }
    });

    // CREATED - po narysowaniu pokaz od razu, otworz modal
    map.on(L.Draw.Event.CREATED, event => {
        const layer = event.layer;
        const layerType = event.layerType;
        const geojson = layer.toGeoJSON();

        drawnItems.addLayer(layer); // od razu widoczne
        let saved = false;

        showModal(
            'Dodaj opis',
            { label: '', description: '' },
            vals => {
                api('', 'POST', { pin_type: layerType, label: vals.label, description: vals.description, geojson: geojson })
                .then(j => {
                    if (j.ok && j.pin) {
                        saved = true;
                        if (layer.setStyle) layer.setStyle({ color: j.pin.color || myColor });
                        if (layer.setIcon)  layer.setIcon(U.pinIcon(j.pin.color || myColor));
                        layer.bindPopup(U.buildPopup(j.pin));
                        layerByPinId.set(j.pin.id, layer);
                        pinById.set(j.pin.id, j.pin);
                        refreshList();
                    } else {
                        drawnItems.removeLayer(layer);
                        alert(j.error || 'Nie udało się zapisać.');
                    }
                }).catch(() => {
                    drawnItems.removeLayer(layer);
                    alert('Błąd sieci - spróbuj ponownie.');
                });
            },
            () => { if (!saved) drawnItems.removeLayer(layer); }
        );
    });

    // Initial load
    api('', 'GET').then(j => {
        if (!j.ok) return;
        myColor = j.color || myColor;
        document.querySelectorAll('[data-color-swatch]').forEach(el => el.style.background = myColor);
        if (drawControl.options.draw.polyline) drawControl.options.draw.polyline.shapeOptions.color = myColor;
        if (drawControl.options.draw.polygon)  drawControl.options.draw.polygon.shapeOptions.color  = myColor;
        if (drawControl.options.draw.marker)   drawControl.options.draw.marker.icon = U.pinIcon(myColor);

        for (const p of (j.pins || [])) {
            const layer = U.geojsonToLayer(p, myColor);
            if (!layer) continue;
            drawnItems.addLayer(layer);
            layer.bindPopup(U.buildPopup(p));
            layerByPinId.set(p.id, layer);
            pinById.set(p.id, p);
        }
        refreshList();
        if (drawnItems.getLayers().length > 0) {
            try { map.fitBounds(drawnItems.getBounds(), { maxZoom: 10, padding: [40, 40] }); } catch (e) {}
        }
    });
})();
