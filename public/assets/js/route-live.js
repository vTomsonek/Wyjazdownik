/**
 * Tryb trasy - live geolocation + sortowanie miejsc po dystansie.
 *
 * Public mode: dostep przez summary_public_token, brak my_score.
 * Etap 1: dystans haversine (instant), no OSRM yet.
 */
(function () {
    'use strict';

    const cfg = window.__LIVE_ROUTE_CONFIG__ || { places: [], tripName: '' };
    let map = null;
    let markers = [];
    let userMarker = null;
    let userPos = null;       // {lat, lng, accuracy}
    let watchId = null;
    let hasCenteredOnUser = false;
    let listEl = null;
    let statusEl = null;
    let recenterBtn = null;
    let topBarEl = null;
    let sheetEl = null;
    let fsBtn = null;
    let fsExitBtn = null;
    let fsRecenterBtn = null;
    let isFullscreen = false;

    // Globalny callback dla Google Maps loader
    window.initLiveRoute = function () {
        const mapDiv = document.getElementById('route-map');
        if (!mapDiv) return;

        listEl         = document.getElementById('route-places-list');
        statusEl       = document.getElementById('route-gps-status');
        recenterBtn    = document.getElementById('route-recenter');
        topBarEl       = document.getElementById('route-topbar');
        sheetEl        = document.getElementById('route-sheet');
        fsBtn          = document.getElementById('route-fullscreen');
        fsExitBtn      = document.getElementById('route-fs-exit');
        fsRecenterBtn  = document.getElementById('route-fs-recenter');

        // Initial center: srednia z miejsc, fallback Polska
        const center = computeInitialCenter();
        map = new google.maps.Map(mapDiv, {
            center: center,
            zoom: 7,
            mapTypeControl: false,
            fullscreenControl: false,
            streetViewControl: false,
            clickableIcons: false,
            gestureHandling: 'greedy',
            mapId: 'DEMO_MAP_ID',
        });

        renderMarkers();
        fitMapToPlaces();

        document.getElementById('route-loader')?.remove();

        // Recenter button (oba: w top barze i floating w fullscreen)
        const recenterHandler = () => {
            if (userPos) {
                map.panTo({ lat: userPos.lat, lng: userPos.lng });
                map.setZoom(Math.max(map.getZoom(), 13));
            } else {
                // Brak pozycji - sprobuj jednorazowo z prompt
                requestPositionOnce();
            }
        };
        recenterBtn?.addEventListener('click', recenterHandler);
        fsRecenterBtn?.addEventListener('click', recenterHandler);

        // Fullscreen toggle
        fsBtn?.addEventListener('click', enterFullscreen);
        fsExitBtn?.addEventListener('click', exitFullscreen);

        // Detail modal handlers
        document.getElementById('route-detail-close')?.addEventListener('click', closeDetailModal);
        document.getElementById('route-detail-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'route-detail-modal') closeDetailModal();
        });

        // Lightbox handlers
        document.getElementById('route-lightbox-close')?.addEventListener('click', closeLightbox);
        document.getElementById('route-lightbox-prev')?.addEventListener('click', () => lightboxNav(-1));
        document.getElementById('route-lightbox-next')?.addEventListener('click', () => lightboxNav(1));
        document.getElementById('route-lightbox')?.addEventListener('click', (e) => {
            if (e.target.id === 'route-lightbox') closeLightbox();
        });

        // ESC wychodzi z fullscreen / modali (priorytet: lightbox > modal > fullscreen)
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const lb = document.getElementById('route-lightbox');
                const dm = document.getElementById('route-detail-modal');
                if (lb && !lb.classList.contains('hidden')) { closeLightbox(); return; }
                if (dm && !dm.classList.contains('hidden')) { closeDetailModal(); return; }
                if (isFullscreen) exitFullscreen();
            } else if (e.key === 'ArrowLeft') {
                const lb = document.getElementById('route-lightbox');
                if (lb && !lb.classList.contains('hidden')) lightboxNav(-1);
            } else if (e.key === 'ArrowRight') {
                const lb = document.getElementById('route-lightbox');
                if (lb && !lb.classList.contains('hidden')) lightboxNav(1);
            }
        });
        // Reaguj na natywne Fullscreen API (np. F11)
        document.addEventListener('fullscreenchange', () => {
            if (!document.fullscreenElement && isFullscreen) {
                // user wyszedl natywnie - posprzataj
                applyFullscreenStyles(false);
                isFullscreen = false;
            }
        });

        renderList();    // bez pozycji: pokazuje miejsca bez dystansow
        startGeolocation();
    };

    function enterFullscreen() {
        isFullscreen = true;
        applyFullscreenStyles(true);
        // Sprobuj natywny Fullscreen API (bonus - chowa URL bar)
        const root = document.documentElement;
        if (root.requestFullscreen) {
            root.requestFullscreen({ navigationUI: 'hide' }).catch(() => {});
        }
        // Zmiana ikonki w buttonie (gdyby user wrocil bez click exit)
        document.getElementById('route-fullscreen-icon-expand')?.classList.add('hidden');
        document.getElementById('route-fullscreen-icon-collapse')?.classList.remove('hidden');
        // Powiadom mape ze zmienil sie rozmiar
        setTimeout(() => google.maps.event.trigger(map, 'resize'), 220);
    }

    function exitFullscreen() {
        isFullscreen = false;
        applyFullscreenStyles(false);
        if (document.fullscreenElement && document.exitFullscreen) {
            document.exitFullscreen().catch(() => {});
        }
        document.getElementById('route-fullscreen-icon-expand')?.classList.remove('hidden');
        document.getElementById('route-fullscreen-icon-collapse')?.classList.add('hidden');
        setTimeout(() => google.maps.event.trigger(map, 'resize'), 220);
    }

    function applyFullscreenStyles(on) {
        if (topBarEl) topBarEl.classList.toggle('hidden', on);
        if (sheetEl)  sheetEl.classList.toggle('hidden', on);
        if (fsExitBtn) fsExitBtn.classList.toggle('hidden', !on);
        if (fsRecenterBtn) fsRecenterBtn.classList.toggle('hidden', !on);
    }

    function requestPositionOnce() {
        if (!('geolocation' in navigator)) return;
        navigator.geolocation.getCurrentPosition(handlePosition, handleGeoError, {
            enableHighAccuracy: true, maximumAge: 0, timeout: 15000,
        });
    }

    function computeInitialCenter() {
        if (!cfg.places.length) return { lat: 52.0, lng: 19.0 }; // Polska
        const lat = cfg.places.reduce((s, p) => s + p.lat, 0) / cfg.places.length;
        const lng = cfg.places.reduce((s, p) => s + p.lng, 0) / cfg.places.length;
        return { lat, lng };
    }

    function fitMapToPlaces() {
        if (!cfg.places.length) return;
        const bounds = new google.maps.LatLngBounds();
        cfg.places.forEach(p => bounds.extend({ lat: p.lat, lng: p.lng }));
        map.fitBounds(bounds, 60);
    }

    function renderMarkers() {
        markers.forEach(m => m.setMap(null));
        markers = cfg.places.map(p => {
            const pin = new google.maps.marker.PinElement({
                background: p.author_color || '#FF6B35',
                borderColor: '#FFFFFF',
                glyphColor: '#FFFFFF',
                glyph: (p.author || '?').charAt(0).toUpperCase(),
                scale: 1.05,
            });
            const marker = new google.maps.marker.AdvancedMarkerElement({
                position: { lat: p.lat, lng: p.lng },
                map: map,
                title: p.name,
                content: pin.element,
            });
            marker.addListener('click', () => {
                map.panTo({ lat: p.lat, lng: p.lng });
                // Scroll do karty w liscie
                const card = document.querySelector(`[data-place-id="${p.id}"]`);
                if (card) {
                    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    card.classList.add('ring-2', 'ring-primary');
                    setTimeout(() => card.classList.remove('ring-2', 'ring-primary'), 1500);
                }
            });
            return { marker, place: p };
        });
    }

    // ========================================================================
    // Geolocation - live tracking
    // ========================================================================
    function startGeolocation() {
        if (!('geolocation' in navigator)) {
            showStatus('⚠️ Twoja przeglądarka nie wspiera geolokalizacji.');
            return;
        }
        showStatus('📡 Lokalizacja: szukam GPS...');
        watchId = navigator.geolocation.watchPosition(
            handlePosition,
            handleGeoError,
            { enableHighAccuracy: true, maximumAge: 5000, timeout: 20000 }
        );
    }

    function handlePosition(pos) {
        userPos = {
            lat: pos.coords.latitude,
            lng: pos.coords.longitude,
            accuracy: pos.coords.accuracy,
        };
        hideStatus();
        updateUserMarker();
        if (!hasCenteredOnUser) {
            map.panTo({ lat: userPos.lat, lng: userPos.lng });
            map.setZoom(11);
            hasCenteredOnUser = true;
        }
        if (recenterBtn) recenterBtn.disabled = false;
        renderList();
    }

    function handleGeoError(err) {
        let msg = '⚠️ Nie udało się pobrać pozycji.';
        if (err.code === err.PERMISSION_DENIED) {
            msg = '🔒 Włącz dostęp do lokalizacji w ustawieniach przeglądarki by zobaczyć dystanse.';
        } else if (err.code === err.POSITION_UNAVAILABLE) {
            msg = '📡 GPS niedostępny. Sprawdź czy masz włączoną lokalizację w telefonie.';
        } else if (err.code === err.TIMEOUT) {
            msg = '⏱️ Timeout GPS - spróbuj ponownie pod gołym niebem.';
        }
        showStatus(msg);
    }

    function updateUserMarker() {
        if (!userPos) return;
        const pos = { lat: userPos.lat, lng: userPos.lng };
        if (!userMarker) {
            const dot = document.createElement('div');
            dot.className = 'user-position-marker';
            dot.style.cssText = 'width:18px;height:18px;border-radius:50%;background:#2563EB;border:3px solid #fff;box-shadow:0 0 0 4px rgba(37,99,235,0.25),0 2px 8px rgba(0,0,0,0.3);';
            userMarker = new google.maps.marker.AdvancedMarkerElement({
                position: pos,
                map: map,
                title: 'Twoja pozycja',
                content: dot,
                zIndex: 999,
            });
        } else {
            userMarker.position = pos;
        }
    }

    // ========================================================================
    // Lista miejsc - sortowanie po dystansie haversine
    // ========================================================================
    function haversineKm(a, b) {
        const R = 6371;
        const toRad = d => d * Math.PI / 180;
        const dLat = toRad(b.lat - a.lat);
        const dLng = toRad(b.lng - a.lng);
        const lat1 = toRad(a.lat);
        const lat2 = toRad(b.lat);
        const x = Math.sin(dLat/2) ** 2 + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng/2) ** 2;
        return 2 * R * Math.asin(Math.sqrt(x));
    }

    function renderList() {
        if (!listEl) return;
        const items = cfg.places.map(p => {
            const dist = userPos ? haversineKm(userPos, p) : null;
            return { p, dist };
        });
        // Sortuj: jak mamy pozycje to po dystansie, inaczej oryginalna kolejnosc
        if (userPos) {
            items.sort((a, b) => a.dist - b.dist);
        }
        listEl.innerHTML = items.map(({ p, dist }) => cardHtml(p, dist)).join('');
        // Bind klikow w karty
        listEl.querySelectorAll('[data-place-id]').forEach(card => {
            card.addEventListener('click', (e) => {
                if (e.target.closest('[data-no-detail]')) return; // Nawiguj - przepuscam
                const id = parseInt(card.getAttribute('data-place-id'), 10);
                if (e.target.closest('[data-detail-btn]')) {
                    openDetailModal(id, items);
                    return;
                }
                // Klik w kartę = pan do markera
                const entry = markers.find(m => m.place.id === id);
                if (entry) {
                    map.panTo({ lat: entry.place.lat, lng: entry.place.lng });
                    map.setZoom(Math.max(map.getZoom(), 12));
                }
            });
        });
    }

    // ========================================================================
    // Detail modal - opis + galeria (Google photos + user media)
    // ========================================================================
    function openDetailModal(placeId, items) {
        const modal = document.getElementById('route-detail-modal');
        if (!modal) return;
        const entry = items.find(it => it.p.id === placeId);
        const p = entry ? entry.p : cfg.places.find(pl => pl.id === placeId);
        if (!p) return;

        // Header
        const authorBadge = document.getElementById('route-detail-author');
        authorBadge.style.background = p.author_color || '#FF6B35';
        authorBadge.textContent = (p.author || '?').charAt(0).toUpperCase();
        document.getElementById('route-detail-name').textContent = p.name || '';
        document.getElementById('route-detail-address').textContent = p.address || '';

        // Meta chips
        const ratingEl = document.getElementById('route-detail-rating');
        if (p.avg !== null) {
            ratingEl.innerHTML = '★ ' + Number(p.avg).toFixed(1).replace('.', ',') + ' <span class="opacity-70">(' + p.count + ')</span>';
            ratingEl.classList.remove('hidden');
        } else {
            ratingEl.classList.add('hidden');
        }
        document.getElementById('route-detail-visit').textContent = '⏱️ ' + formatVisit(p.visit_minutes);
        const distEl = document.getElementById('route-detail-distance');
        if (entry && entry.dist !== null && entry.dist !== undefined) {
            const d = entry.dist;
            const distStr = d < 1 ? Math.round(d * 1000) + ' m' : d.toFixed(1).replace('.', ',') + ' km';
            distEl.textContent = '📍 ' + distStr;
            distEl.classList.remove('hidden');
        } else {
            distEl.classList.add('hidden');
        }

        // Opis
        const descEl = document.getElementById('route-detail-description');
        if (p.description && p.description.trim() !== '') {
            descEl.textContent = p.description;
            descEl.classList.remove('hidden');
        } else {
            descEl.textContent = '';
            descEl.classList.add('hidden');
        }

        // Navigate CTA
        document.getElementById('route-detail-navigate').href =
            'https://www.google.com/maps/dir/?api=1&destination=' + p.lat + ',' + p.lng;

        // Show modal
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        // Lazy load media
        loadMediaForDetail(p);
    }

    function closeDetailModal() {
        const modal = document.getElementById('route-detail-modal');
        if (modal) modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    async function loadMediaForDetail(p) {
        const container = document.getElementById('route-detail-media');
        if (!container) return;
        container.innerHTML = '<div class="text-center text-xs text-mist py-3 italic">Ładuję media...</div>';

        const [userMediaResult, googlePhotosResult] = await Promise.allSettled([
            fetchUserMedia(p.id),
            fetchGooglePhotos(p.google_place_id),
        ]);
        const userMedia    = userMediaResult.status === 'fulfilled' ? userMediaResult.value : [];
        const googlePhotos = googlePhotosResult.status === 'fulfilled' ? googlePhotosResult.value : [];

        renderDetailMedia(userMedia, googlePhotos, p.name);
    }

    async function fetchUserMedia(placeId) {
        if (!cfg.mediaUrlTemplate) return [];
        const url = cfg.mediaUrlTemplate.replace('ID', placeId);
        try {
            const r = await fetch(url);
            const data = await r.json();
            return data.ok ? (data.media || []) : [];
        } catch (e) {
            return [];
        }
    }

    async function fetchGooglePhotos(googlePlaceId) {
        if (!googlePlaceId || !google.maps.places || !google.maps.places.Place) return [];
        try {
            const place = new google.maps.places.Place({ id: googlePlaceId });
            await place.fetchFields({ fields: ['photos'] });
            return (place.photos || []).slice(0, 6).map(ph => ph.getURI({ maxWidth: 800, maxHeight: 600 }));
        } catch (e) {
            return [];
        }
    }

    function renderDetailMedia(userMedia, googlePhotos, placeName) {
        const container = document.getElementById('route-detail-media');
        let html = '';

        // Zdjecia z Google
        if (googlePhotos.length > 0) {
            html += '<div><div class="text-[10px] font-semibold text-mist uppercase tracking-wide mb-2">📷 Zdjęcia z Google</div>';
            html += '<div class="grid grid-cols-3 gap-1.5">';
            googlePhotos.forEach((url, i) => {
                html += `<button type="button" data-lb-img="${i}" data-lb-source="google" class="block aspect-[4/3] rounded-lg overflow-hidden bg-mist/10"><img src="${escapeHtml(url)}" alt="" class="w-full h-full object-cover" loading="lazy"></button>`;
            });
            html += '</div></div>';
        }

        // User images
        const userImages = userMedia.filter(m => m.type === 'image');
        if (userImages.length > 0) {
            html += '<div><div class="text-[10px] font-semibold text-mist uppercase tracking-wide mb-2">📸 Zdjęcia ekipy</div>';
            html += '<div class="grid grid-cols-3 gap-1.5">';
            userImages.forEach((m, i) => {
                html += `<button type="button" data-lb-img="${i}" data-lb-source="user-img" class="block aspect-[4/3] rounded-lg overflow-hidden bg-mist/10"><img src="${escapeHtml(absUrl(m.file_path))}" alt="" class="w-full h-full object-cover" loading="lazy"></button>`;
            });
            html += '</div></div>';
        }

        // User videos
        const userVideos = userMedia.filter(m => m.type === 'video');
        if (userVideos.length > 0) {
            html += '<div><div class="text-[10px] font-semibold text-mist uppercase tracking-wide mb-2">🎬 Wideo</div>';
            html += '<div class="grid grid-cols-2 gap-1.5">';
            userVideos.forEach((m, i) => {
                html += `<button type="button" data-lb-img="${i}" data-lb-source="user-vid" class="relative block aspect-video rounded-lg overflow-hidden bg-mist/20"><video src="${escapeHtml(absUrl(m.file_path))}" class="w-full h-full object-cover" preload="metadata"></video><span class="absolute inset-0 flex items-center justify-center text-3xl text-white/90">▶</span></button>`;
            });
            html += '</div></div>';
        }

        // Links
        const userLinks = userMedia.filter(m => m.type === 'link');
        if (userLinks.length > 0) {
            html += '<div><div class="text-[10px] font-semibold text-mist uppercase tracking-wide mb-2">🔗 Linki</div>';
            html += '<div class="space-y-1.5">';
            userLinks.forEach(m => {
                const cap = m.caption || m.url;
                html += `<a href="${escapeHtml(m.url)}" target="_blank" rel="noopener" class="block px-3 py-2 rounded-lg bg-mist/10 hover:bg-mist/20 transition text-sm text-primary truncate">↗ ${escapeHtml(cap)}</a>`;
            });
            html += '</div></div>';
        }

        if (html === '') {
            html = '<div class="text-center text-xs text-mist py-3 italic">Brak dodanych mediów.</div>';
        }

        container.innerHTML = html;

        // Bind lightbox
        const allLightboxItems = buildLightboxItems(userMedia, googlePhotos, placeName);
        container.querySelectorAll('[data-lb-img]').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = parseInt(btn.getAttribute('data-lb-img'), 10);
                const src = btn.getAttribute('data-lb-source');
                const offset = lbOffsetForSource(src, userMedia, googlePhotos);
                openLightbox(allLightboxItems, offset + idx);
            });
        });
    }

    function lbOffsetForSource(src, userMedia, googlePhotos) {
        if (src === 'google')   return 0;
        if (src === 'user-img') return googlePhotos.length;
        if (src === 'user-vid') return googlePhotos.length + userMedia.filter(m => m.type === 'image').length;
        return 0;
    }

    function buildLightboxItems(userMedia, googlePhotos, placeName) {
        const items = [];
        googlePhotos.forEach(url => items.push({ type: 'image', url, caption: '📷 ' + placeName + ' · Google' }));
        userMedia.filter(m => m.type === 'image').forEach(m =>
            items.push({ type: 'image', url: absUrl(m.file_path), caption: '📸 ' + (m.caption || placeName) }));
        userMedia.filter(m => m.type === 'video').forEach(m =>
            items.push({ type: 'video', url: absUrl(m.file_path), caption: '🎬 ' + (m.caption || placeName) }));
        return items;
    }

    function absUrl(path) {
        if (!path) return '';
        if (/^https?:\/\//.test(path)) return path;
        return (cfg.assetBase || '/') + path.replace(/^\//, '');
    }

    // ========================================================================
    // Lightbox
    // ========================================================================
    let lbItems = [];
    let lbIdx = 0;

    function openLightbox(items, idx) {
        if (!items.length) return;
        lbItems = items;
        lbIdx = idx;
        renderLightbox();
        document.getElementById('route-lightbox')?.classList.remove('hidden');
    }

    function closeLightbox() {
        document.getElementById('route-lightbox')?.classList.add('hidden');
        const stage = document.getElementById('route-lightbox-stage');
        if (stage) stage.innerHTML = '';
    }

    function renderLightbox() {
        const stage = document.getElementById('route-lightbox-stage');
        const captionEl = document.getElementById('route-lightbox-caption');
        if (!stage || !lbItems.length) return;
        const it = lbItems[lbIdx];
        const counter = (lbIdx + 1) + ' / ' + lbItems.length;
        if (it.type === 'video') {
            stage.innerHTML = `<video src="${escapeHtml(it.url)}" controls autoplay class="max-w-[92vw] max-h-[88vh] rounded-lg"></video>`;
        } else {
            stage.innerHTML = `<img src="${escapeHtml(it.url)}" alt="" class="max-w-[92vw] max-h-[88vh] object-contain rounded-lg">`;
        }
        captionEl.textContent = counter + ' · ' + (it.caption || '');
    }

    function lightboxNav(delta) {
        if (!lbItems.length) return;
        lbIdx = (lbIdx + delta + lbItems.length) % lbItems.length;
        renderLightbox();
    }

    function cardHtml(p, dist) {
        const initial = (p.author || '?').charAt(0).toUpperCase();
        const navUrl = 'https://www.google.com/maps/dir/?api=1&destination=' + p.lat + ',' + p.lng;
        const distStr = dist !== null
            ? (dist < 1 ? Math.round(dist * 1000) + ' m' : dist.toFixed(1).replace('.', ',') + ' km')
            : null;
        const visitFmt = formatVisit(p.visit_minutes);
        const avgStr = p.avg !== null
            ? '★ ' + Number(p.avg).toFixed(1).replace('.', ',') + ' <span class="text-mist">(' + p.count + ')</span>'
            : '<span class="text-mist italic">Brak ocen</span>';
        const hasDescOrMedia = (p.description && p.description.trim() !== '') || p.google_place_id;
        return `
            <article data-place-id="${p.id}"
                     class="cursor-pointer rounded-xl border border-mist/15 bg-cream dark:bg-night p-2.5 hover:border-primary/40 transition">
                <div class="flex items-start gap-2.5">
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-white text-xs font-bold shrink-0"
                          style="background:${escapeHtml(p.author_color)}">${escapeHtml(initial)}</span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-semibold text-sm text-ink dark:text-pale leading-snug">${escapeHtml(p.name)}</h3>
                            ${distStr ? `<span class="shrink-0 text-xs font-bold text-primary whitespace-nowrap">📍 ${distStr}</span>` : ''}
                        </div>
                        ${p.address ? `<p class="text-[11px] text-mist truncate mt-0.5">${escapeHtml(p.address)}</p>` : ''}
                        <div class="mt-1 flex items-center gap-2 text-xs">
                            <span class="text-amber-500">${avgStr}</span>
                            <span class="text-mist">·</span>
                            <span class="text-mist">⏱️ ${visitFmt}</span>
                            ${hasDescOrMedia ? `<button type="button" data-detail-btn="${p.id}" class="ml-auto inline-flex items-center gap-1 px-2 py-1 rounded-full bg-mist/15 text-ink dark:text-pale text-[11px] font-semibold hover:bg-mist/25 transition">ℹ Szczegóły</button>` : ''}
                            <a href="${navUrl}" target="_blank" rel="noopener" data-no-detail
                               class="${hasDescOrMedia ? '' : 'ml-auto'} inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-primary text-white text-[11px] font-semibold hover:bg-primary-deep transition">
                                🚗 Nawiguj
                            </a>
                        </div>
                    </div>
                </div>
            </article>
        `;
    }

    function formatVisit(vm) {
        vm = parseInt(vm, 10) || 60;
        if (vm < 60) return vm + ' min';
        if (vm % 60 === 0) return (vm / 60) + 'h';
        return (vm / 60).toFixed(1).replace('.', ',') + 'h';
    }

    function escapeHtml(s) {
        return String(s ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
    }

    function showStatus(msg) {
        if (!statusEl) return;
        statusEl.textContent = msg;
        statusEl.classList.remove('hidden');
    }

    function hideStatus() {
        if (statusEl) statusEl.classList.add('hidden');
    }

    // Cleanup przy unmount
    window.addEventListener('beforeunload', () => {
        if (watchId !== null) navigator.geolocation.clearWatch(watchId);
    });
})();
