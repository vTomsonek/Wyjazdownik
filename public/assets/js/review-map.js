/**
 * Mini-mapa read-only w review (krok 12) i potencjalnie summary (ETAP 7).
 * Wczytuje pinezki z atrybutu [data-review-pins='[json...]'] i fituje do bounds.
 */
(function () {
    'use strict';

    if (typeof L === 'undefined' || !window.MapUtils) return;
    const el = document.getElementById('review-map');
    if (!el || el._reviewMapInited) return;
    el._reviewMapInited = true;

    let pins = [];
    try { pins = JSON.parse(el.getAttribute('data-review-pins') || '[]'); } catch (e) {}
    if (!Array.isArray(pins) || pins.length === 0) return;

    const map = L.map(el, { scrollWheelZoom: false }).setView([52.0, 19.0], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap',
    }).addTo(map);

    const group = L.featureGroup().addTo(map);
    for (const pin of pins) {
        const layer = window.MapUtils.geojsonToLayer(pin, '#FF6B35');
        if (!layer) continue;
        layer.bindPopup(window.MapUtils.buildPopup(pin));
        group.addLayer(layer);
    }
    if (group.getLayers().length > 0) {
        try { map.fitBounds(group.getBounds(), { maxZoom: 10, padding: [30, 30] }); }
        catch (e) {}
    }

    // Wlacz scroll wheel zoom dopiero po kliknieciu w mape (nie blokuje scrolla strony)
    map.once('focus', () => map.scrollWheelZoom.enable());
    el.addEventListener('click', () => map.scrollWheelZoom.enable(), { once: true });
})();
