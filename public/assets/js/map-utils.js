/**
 * Map utilities - male helpery uzywane przez map-wizard.js i (w ETAP 7)
 * przez summary map renderer.
 */
window.MapUtils = (function () {
    'use strict';

    function pinSvg(color) {
        const c = String(color || '#FF6B35');
        return '<svg viewBox="0 0 28 36" xmlns="http://www.w3.org/2000/svg">'
             + '<path d="M14 2 C 7 2 2 7 2 14 C 2 22 14 34 14 34 C 14 34 26 22 26 14 C 26 7 21 2 14 2 Z" '
             + 'fill="' + c + '" stroke="#1A1A2E" stroke-width="2"/>'
             + '<circle cx="14" cy="14" r="4.5" fill="#fff"/></svg>';
    }

    function pinIcon(color) {
        return L.divIcon({
            className: 'pin-marker',
            html: pinSvg(color),
            iconSize: [28, 36],
            iconAnchor: [14, 36],
        });
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function buildPopup(pin) {
        const lbl = escapeHtml(pin.label || '(bez etykiety)');
        const dsc = pin.description
            ? '<br><span style="color:#6B7280">' + escapeHtml(pin.description) + '</span>'
            : '';
        return '<strong>' + lbl + '</strong>' + dsc;
    }

    function geojsonToLayer(pin, fallbackColor) {
        const color = pin.color || fallbackColor || '#FF6B35';
        const gj = pin.geojson;
        if (!gj || !gj.geometry) return null;
        const coords = gj.geometry.coordinates;
        if (pin.pin_type === 'marker') {
            return L.marker([coords[1], coords[0]], { icon: pinIcon(color) });
        }
        if (pin.pin_type === 'polyline') {
            const latlngs = coords.map(c => [c[1], c[0]]);
            return L.polyline(latlngs, { color: color, weight: 4 });
        }
        if (pin.pin_type === 'polygon') {
            const ring = coords[0].map(c => [c[1], c[0]]);
            return L.polygon(ring, { color: color, weight: 3, fillOpacity: 0.2 });
        }
        return null;
    }

    return {
        pinSvg: pinSvg,
        pinIcon: pinIcon,
        escapeHtml: escapeHtml,
        buildPopup: buildPopup,
        geojsonToLayer: geojsonToLayer,
    };
})();
