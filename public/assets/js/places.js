/**
 * Wyjazdownik.pl - kolaboratywna mapa atrakcji (Google Maps NEW API).
 *
 * Uzywa nowych klas (od marca 2025) dla nowych Google Cloud kont:
 * - google.maps.places.PlaceAutocompleteElement (zamiast Autocomplete)
 * - google.maps.places.Place (zamiast PlacesService.getDetails)
 */

(function () {
    'use strict';

    let map = null;
    let geocoder = null;
    let infoWindow = null;
    let markers = [];
    let places = [];
    let authors = {};
    let cfg = null;
    let PlaceCtor = null;
    let PlaceAutocompleteEl = null;
    let selectedPlaceRef = null; // przechowuje obiekt Place po wyborze z autocomplete

    // Wystaw callback NATYCHMIAST - Google Maps wywola go po wczytaniu
    window.initPlacesMap = async function () {
        cfg = window.__PLACES_CONFIG__ || null;
        if (!cfg) {
            console.error('[places] __PLACES_CONFIG__ missing');
            return;
        }

        const mapEl = document.getElementById('places-map');
        if (!mapEl) {
            console.error('[places] #places-map not found');
            return;
        }

        try {
            places = JSON.parse(mapEl.getAttribute('data-places') || '[]');
            authors = JSON.parse(mapEl.getAttribute('data-authors') || '{}');
        } catch (e) {
            console.error('[places] cannot parse data attributes', e);
        }

        // Importy z nowego API (importLibrary jest async)
        try {
            const placesLib = await google.maps.importLibrary('places');
            PlaceCtor = placesLib.Place;
            PlaceAutocompleteEl = placesLib.PlaceAutocompleteElement;
        } catch (e) {
            console.error('[places] cannot import places library', e);
        }

        map = new google.maps.Map(mapEl, {
            center: { lat: 52.0, lng: 19.0 },
            zoom: 5,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
            zoomControl: true,
            gestureHandling: 'greedy',
            styles: [
                { featureType: 'poi', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
            ],
        });

        geocoder = new google.maps.Geocoder();
        infoWindow = new google.maps.InfoWindow({ maxWidth: 360 });

        renderMarkers();
        setupModal();
        setupListListeners();
        setupDetailModal();
        setupLightbox();
    };

    function renderMarkers() {
        markers.forEach(m => m.setMap(null));
        markers = [];

        const bounds = new google.maps.LatLngBounds();
        for (const p of places) {
            const author = authors[p.participant_id] || { nickname: '?', color: '#6B7280' };
            const initial = (author.nickname || '?').charAt(0).toUpperCase();

            const marker = new google.maps.Marker({
                position: { lat: parseFloat(p.lat), lng: parseFloat(p.lng) },
                map: map,
                title: p.name,
                icon: makeMarkerIcon(author.color, initial),
            });
            marker._place = p;
            marker.addListener('click', () => openInfoWindow(marker, p, author));
            markers.push(marker);
            bounds.extend(marker.getPosition());
        }
        if (markers.length > 0) {
            map.fitBounds(bounds, 60);
            if (markers.length === 1) {
                google.maps.event.addListenerOnce(map, 'idle', () => {
                    if (map.getZoom() > 12) map.setZoom(12);
                });
            }
        }
    }

    function makeMarkerIcon(color, initial) {
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="40" height="48" viewBox="0 0 40 48">
            <path d="M20 0C9 0 0 9 0 20c0 13 20 28 20 28s20-15 20-28C40 9 31 0 20 0z" fill="${color}"/>
            <circle cx="20" cy="20" r="11" fill="white"/>
            <text x="20" y="25" text-anchor="middle" font-family="Inter,sans-serif" font-size="13" font-weight="700" fill="${color}">${initial}</text>
        </svg>`;
        return {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
            scaledSize: new google.maps.Size(40, 48),
            anchor: new google.maps.Point(20, 48),
        };
    }

    async function openInfoWindow(marker, place, author) {
        const desc = place.description ? `<p>${escapeHtml(place.description)}</p>` : '';
        const addr = place.address ? `<p style="color:#666;font-size:12px">${escapeHtml(place.address)}</p>` : '';
        const content = `<div class="iw-place">
            <h4>${escapeHtml(place.name)}</h4>
            ${addr}
            ${desc}
            <div class="photo-strip" id="iw-photos-${place.id}"></div>
            <div class="author">— ${escapeHtml(author.nickname)}</div>
            <button type="button" class="iw-details-btn" onclick="window.__wyjazdownikPlaces.openDetail(${place.id})"
                    style="margin-top:8px;width:100%;padding:6px 12px;background:#C2410C;color:white;border:none;border-radius:6px;font-weight:600;cursor:pointer;font-size:13px">
                Szczegóły i media →
            </button>
        </div>`;
        infoWindow.setContent(content);
        infoWindow.open(map, marker);

        // Photos z nowego API: Place.fetchFields(['photos'])
        if (place.google_place_id && PlaceCtor) {
            try {
                const placeObj = new PlaceCtor({ id: place.google_place_id });
                await placeObj.fetchFields({ fields: ['photos'] });
                if (placeObj.photos && placeObj.photos.length > 0) {
                    const container = document.getElementById('iw-photos-' + place.id);
                    if (container) {
                        container.innerHTML = placeObj.photos.slice(0, 4).map(ph => {
                            const url = ph.getURI ? ph.getURI({ maxWidth: 200, maxHeight: 150 })
                                                  : ph.getUrl({ maxWidth: 200, maxHeight: 150 });
                            return `<img src="${url}" alt="">`;
                        }).join('');
                    }
                }
            } catch (e) {
                console.warn('[places] fetch photos failed', e);
            }
        }
    }

    function setupModal() {
        const modal = document.getElementById('add-place-modal');
        const openBtn = document.getElementById('add-place-btn');
        const closeBtn = document.getElementById('close-modal');
        const cancelBtn = document.getElementById('cancel-modal');
        const form = document.getElementById('add-place-form');
        const oldSearchInput = document.getElementById('place-search');
        const nameInput = document.getElementById('place-name');
        const descInput = document.getElementById('place-description');
        const submitBtn = document.getElementById('submit-place');
        const selectedBox = document.getElementById('selected-place');
        const selectedInfo = document.getElementById('selected-place-info');
        const formError = document.getElementById('form-error');

        if (!modal || !openBtn || !oldSearchInput) return;

        const hidden = {
            lat: document.getElementById('place-lat'),
            lng: document.getElementById('place-lng'),
            address: document.getElementById('place-address'),
            country: document.getElementById('place-country'),
            googleId: document.getElementById('place-google-id'),
        };

        // ----- Stworz PlaceAutocompleteElement i wstaw w miejsce starego input -----
        let autocompleteEl = null;
        if (PlaceAutocompleteEl) {
            autocompleteEl = new PlaceAutocompleteEl();
            // Skopiuj klasy z oryginalnego inputa zeby pasowal stylem
            autocompleteEl.id = 'place-search-element';
            // Element jest web component, zawiera shadow DOM - nasz styl moze nie zadzialac
            // Dodajemy wrapper div dla wygodniejszego stylowania
            const wrapper = document.createElement('div');
            wrapper.className = 'gmp-place-autocomplete-wrapper';
            wrapper.appendChild(autocompleteEl);
            oldSearchInput.parentNode.replaceChild(wrapper, oldSearchInput);

            autocompleteEl.addEventListener('gmp-select', async ({ placePrediction }) => {
                if (!placePrediction) return;
                try {
                    const place = placePrediction.toPlace();
                    await place.fetchFields({
                        fields: ['displayName', 'formattedAddress', 'location', 'addressComponents', 'id']
                    });
                    fillFromNewPlace(place);
                } catch (e) {
                    console.error('[places] fetchFields failed', e);
                    formError.textContent = 'Nie udało się pobrać szczegółów miejsca.';
                    formError.classList.remove('hidden');
                }
            });
        } else {
            console.error('[places] PlaceAutocompleteElement not available');
        }

        function openModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                if (autocompleteEl) autocompleteEl.focus();
            }, 50);
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            resetForm();
        }

        function resetForm() {
            form?.reset();
            selectedBox?.classList.add('hidden');
            formError?.classList.add('hidden');
            Object.values(hidden).forEach(el => { if (el) el.value = ''; });
            selectedPlaceRef = null;
            if (autocompleteEl) {
                // Resetuj wartosc autocomplete - PlaceAutocompleteElement nie ma .value
                // ale wewnetrzny input mozna wyczyscic
                try { autocompleteEl.value = ''; } catch (e) {}
            }
            submitBtn.disabled = true;
        }

        function fillFromNewPlace(place) {
            const loc = place.location;
            if (!loc) return;
            hidden.lat.value = typeof loc.lat === 'function' ? loc.lat() : loc.lat;
            hidden.lng.value = typeof loc.lng === 'function' ? loc.lng() : loc.lng;
            hidden.address.value = place.formattedAddress || '';
            hidden.googleId.value = place.id || '';
            if (Array.isArray(place.addressComponents)) {
                const cc = place.addressComponents.find(c => c.types?.includes('country'));
                if (cc) hidden.country.value = (cc.shortText || '').toLowerCase();
            }
            const displayName = place.displayName || '';
            if (!nameInput.value && displayName) nameInput.value = displayName;
            selectedInfo.textContent = place.formattedAddress || displayName || '';
            selectedBox.classList.remove('hidden');
            submitBtn.disabled = false;
            selectedPlaceRef = place;
        }

        // Helper do recznego wypelnienia (klik na mapie)
        function fillFromLegacy(data) {
            hidden.lat.value = data.lat;
            hidden.lng.value = data.lng;
            hidden.address.value = data.address || '';
            hidden.googleId.value = data.placeId || '';
            if (data.countryCode) hidden.country.value = data.countryCode.toLowerCase();
            if (!nameInput.value && data.name) nameInput.value = data.name;
            selectedInfo.textContent = data.address || data.name || `${data.lat}, ${data.lng}`;
            selectedBox.classList.remove('hidden');
            submitBtn.disabled = false;
        }

        openBtn.addEventListener('click', openModal);
        closeBtn?.addEventListener('click', closeModal);
        cancelBtn?.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

        // Klik na mapie - reverse geocoding
        map.addListener('click', (ev) => {
            if (!modal.classList.contains('hidden')) return;
            const latlng = ev.latLng;
            openModal();
            geocoder.geocode({ location: latlng }, (results, status) => {
                if (status === 'OK' && results && results[0]) {
                    let countryCode = '';
                    const cc = results[0].address_components?.find(c => c.types?.includes('country'));
                    if (cc) countryCode = cc.short_name || '';
                    fillFromLegacy({
                        lat: latlng.lat(),
                        lng: latlng.lng(),
                        address: results[0].formatted_address,
                        placeId: results[0].place_id,
                        countryCode: countryCode,
                        name: results[0].address_components?.[0]?.long_name || '',
                    });
                } else {
                    fillFromLegacy({
                        lat: latlng.lat(),
                        lng: latlng.lng(),
                        name: '',
                    });
                }
            });
        });

        form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            formError.classList.add('hidden');

            if (!hidden.lat.value || !hidden.lng.value) {
                formError.textContent = 'Wybierz miejsce z podpowiedzi lub kliknij na mapie.';
                formError.classList.remove('hidden');
                return;
            }
            const finalName = nameInput.value.trim();
            if (!finalName) {
                formError.textContent = 'Podaj nazwę wyświetlaną.';
                formError.classList.remove('hidden');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Dodaję...';

            try {
                const fd = new FormData();
                fd.append('_csrf', cfg.csrf);
                fd.append('name', finalName);
                fd.append('description', descInput.value.trim());
                fd.append('lat', hidden.lat.value);
                fd.append('lng', hidden.lng.value);
                fd.append('address', hidden.address.value);
                fd.append('country_code', hidden.country.value);
                fd.append('google_place_id', hidden.googleId.value);

                const r = await fetch(cfg.urls.create, { method: 'POST', body: fd });
                const data = await r.json();
                if (data && data.ok && data.place) {
                    places.push(data.place);
                    renderMarkers();
                    addPlaceToList(data.place);
                    closeModal();
                } else {
                    formError.textContent = (data && data.error) || 'Nie udało się dodać miejsca.';
                    formError.classList.remove('hidden');
                }
            } catch (err) {
                formError.textContent = 'Błąd sieci. Spróbuj jeszcze raz.';
                formError.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Dodaj';
            }
        });
    }

    function setupListListeners() {
        document.querySelectorAll('#places-list article[data-place-id]').forEach(card => {
            card.addEventListener('click', (e) => {
                if (e.target.closest('[data-delete-place]')) return;
                const lat = parseFloat(card.getAttribute('data-lat'));
                const lng = parseFloat(card.getAttribute('data-lng'));
                if (!isNaN(lat) && !isNaN(lng)) {
                    map.panTo({ lat, lng });
                    map.setZoom(Math.max(map.getZoom(), 10));
                    const pid = parseInt(card.getAttribute('data-place-id'), 10);
                    const marker = markers.find(m => m._place && m._place.id === pid);
                    if (marker) google.maps.event.trigger(marker, 'click');
                }
            });
        });

        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-delete-place]');
            if (!btn) return;
            e.stopPropagation();
            const id = btn.getAttribute('data-delete-place');
            if (!id) return;
            if (!confirm('Usunąć to miejsce?')) return;

            try {
                const fd = new FormData();
                fd.append('_csrf', cfg.csrf);
                const url = cfg.urls.deleteTemplate.replace('ID', id);
                const r = await fetch(url, { method: 'POST', body: fd });
                const data = await r.json();
                if (data && data.ok) {
                    places = places.filter(p => p.id !== parseInt(id, 10));
                    renderMarkers();
                    btn.closest('article')?.remove();
                    updateListCounter();
                } else {
                    alert((data && data.error) || 'Nie udało się usunąć.');
                }
            } catch (err) {
                alert('Błąd sieci.');
            }
        });
    }

    function addPlaceToList(place) {
        const list = document.getElementById('places-list');
        if (!list) return;
        const empty = list.querySelector('p.italic');
        if (empty) empty.remove();

        const author = authors[place.participant_id] || { nickname: '?', color: '#6B7280' };
        const initial = (author.nickname || '?').charAt(0).toUpperCase();
        const isMine = place.participant_id === cfg.myParticipantId;

        const article = document.createElement('article');
        article.className = 'rounded-xl border border-mist/15 p-3 hover:border-primary/30 transition cursor-pointer';
        article.setAttribute('data-place-id', place.id);
        article.setAttribute('data-lat', place.lat);
        article.setAttribute('data-lng', place.lng);
        article.innerHTML = `
            <div class="flex items-start gap-2">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-white text-xs font-bold shrink-0 mt-0.5"
                      style="background:${author.color}">${initial}</span>
                <div class="flex-1 min-w-0">
                    <h4 class="font-semibold text-sm text-ink dark:text-pale truncate">${escapeHtml(place.name)}</h4>
                    ${place.address ? `<p class="text-xs text-mist truncate">${escapeHtml(place.address)}</p>` : ''}
                    ${place.description ? `<p class="text-xs text-ink/70 dark:text-pale/70 mt-1 line-clamp-2">${escapeHtml(place.description)}</p>` : ''}
                    <div class="mt-2 flex items-center gap-2 text-xs text-mist">
                        <span>— ${escapeHtml(author.nickname)}</span>
                        ${isMine ? `<button type="button" class="ml-auto text-red-500 hover:text-red-700 transition" data-delete-place="${place.id}" title="Usuń">🗑</button>` : ''}
                    </div>
                </div>
            </div>
        `;
        article.addEventListener('click', (e) => {
            if (e.target.closest('[data-delete-place]')) return;
            map.panTo({ lat: parseFloat(place.lat), lng: parseFloat(place.lng) });
            map.setZoom(Math.max(map.getZoom(), 10));
            const marker = markers.find(m => m._place && m._place.id === place.id);
            if (marker) google.maps.event.trigger(marker, 'click');
        });
        list.insertBefore(article, list.firstChild);
        updateListCounter();
    }

    function updateListCounter() {
        const counter = document.querySelector('#places-list')?.parentElement?.querySelector('h3 .text-mist');
        if (counter) counter.textContent = '(' + places.length + ')';
    }

    function escapeHtml(s) {
        return String(s ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    // ========================================================================
    // ETAP 2: Modal szczegolow miejsca - galeria mediow, upload, linki
    // ========================================================================

    let currentDetailPlaceId = null;

    function setupDetailModal() {
        const modal = document.getElementById('detail-modal');
        const closeBtn = document.getElementById('detail-close');
        if (!modal) return;

        closeBtn?.addEventListener('click', closeDetailModal);
        modal.addEventListener('click', (e) => { if (e.target === modal) closeDetailModal(); });

        // Upload zdjęcia
        document.getElementById('upload-image')?.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            if (file) uploadMedia(file, 'image', e.target);
        });

        // Upload wideo
        document.getElementById('upload-video')?.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            if (file) uploadMedia(file, 'video', e.target);
        });

        // Dodaj link - otwiera sub-modal
        const linkModal = document.getElementById('link-modal');
        const linkForm = document.getElementById('link-form');
        document.getElementById('add-link-btn')?.addEventListener('click', () => {
            linkModal.classList.remove('hidden');
            linkModal.classList.add('flex');
            setTimeout(() => document.getElementById('link-url')?.focus(), 50);
        });
        document.getElementById('link-cancel')?.addEventListener('click', () => {
            linkModal.classList.add('hidden');
            linkModal.classList.remove('flex');
            linkForm.reset();
            document.getElementById('link-error').classList.add('hidden');
        });
        linkForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            await submitLink();
        });
    }

    // Glowna funkcja - otwiera modal szczegolow dla danego miejsca
    async function openPlaceDetail(placeId) {
        const place = places.find(p => p.id === placeId);
        if (!place) return;
        currentDetailPlaceId = placeId;

        const author = authors[place.participant_id] || { nickname: '?', color: '#6B7280' };
        const isMine = place.participant_id === cfg.myParticipantId;

        document.getElementById('detail-name').textContent = place.name;
        document.getElementById('detail-address').textContent = place.address || '';
        document.getElementById('detail-author').textContent = '— dodał(a): ' + author.nickname;
        document.getElementById('detail-description').textContent = place.description || '';

        document.getElementById('detail-media').innerHTML = '<p class="text-sm text-mist italic">Ładuję media...</p>';
        document.getElementById('detail-uploader').classList.toggle('hidden', !isMine);

        const modal = document.getElementById('detail-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Pobierz user media + Google Photos rownolegle
        await loadAllMediaForPlace(placeId, place.google_place_id);
    }

    async function loadAllMediaForPlace(placeId, googlePlaceId) {
        // Fetch user media + Google photos rownolegle
        const [userMediaResult, googlePhotosResult] = await Promise.allSettled([
            fetchUserMedia(placeId),
            fetchGooglePhotos(googlePlaceId),
        ]);
        const userMedia = userMediaResult.status === 'fulfilled' ? userMediaResult.value : [];
        const googlePhotos = googlePhotosResult.status === 'fulfilled' ? googlePhotosResult.value : [];
        renderMediaSections(userMedia, googlePhotos);
    }

    async function fetchUserMedia(placeId) {
        const url = cfg.urls.mediaListTemplate.replace('ID', placeId);
        const r = await fetch(url);
        const data = await r.json();
        return data.ok ? (data.media || []) : [];
    }

    async function fetchGooglePhotos(googlePlaceId) {
        if (!googlePlaceId || !PlaceCtor) return [];
        try {
            const placeObj = new PlaceCtor({ id: googlePlaceId });
            await placeObj.fetchFields({ fields: ['photos'] });
            return (placeObj.photos || []).slice(0, 6);
        } catch (e) {
            console.warn('[places] cannot fetch google photos', e);
            return [];
        }
    }

    function closeDetailModal() {
        const modal = document.getElementById('detail-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        currentDetailPlaceId = null;
    }

    // Wrapper - po upload/delete reloadujemy media (uzywa loadAllMediaForPlace zeby refresh tez Google photos)
    async function loadMediaForPlace(placeId) {
        const place = places.find(p => p.id === placeId);
        await loadAllMediaForPlace(placeId, place ? place.google_place_id : null);
    }

    // Galeria lightbox - tablica obiektow {type, url, source} dla aktualnego miejsca
    // type: 'image' | 'video', source: 'Google' lub nick uczestnika
    let detailLightboxItems = [];

    function renderMediaSections(mediaList, googlePhotos = []) {
        const container = document.getElementById('detail-media');
        const images = mediaList.filter(m => m.type === 'image');
        const videos = mediaList.filter(m => m.type === 'video');
        const links  = mediaList.filter(m => m.type === 'link');
        const place = places.find(p => p.id === currentDetailPlaceId) || {};
        const isMine = place.participant_id === cfg.myParticipantId;
        const authorNick = (authors[place.participant_id] || {}).nickname || '?';

        let html = '';
        detailLightboxItems = []; // reset

        // Google Places photos - zawsze na poczatku
        if (googlePhotos.length > 0) {
            html += '<div><h4 class="font-semibold text-sm text-mist uppercase tracking-wide mb-2">📸 Zdjęcia z Google</h4><div class="grid grid-cols-2 sm:grid-cols-3 gap-2">';
            for (const ph of googlePhotos) {
                const thumbUrl = ph.getURI ? ph.getURI({ maxWidth: 400, maxHeight: 300 })
                                            : ph.getUrl({ maxWidth: 400, maxHeight: 300 });
                const bigUrl = ph.getURI ? ph.getURI({ maxWidth: 1600, maxHeight: 1200 })
                                          : ph.getUrl({ maxWidth: 1600, maxHeight: 1200 });
                const idx = detailLightboxItems.length;
                detailLightboxItems.push({ type: 'image', url: bigUrl, source: 'Google' });
                html += `<button type="button" data-lightbox-idx="${idx}" class="block hover:opacity-90 transition cursor-zoom-in">
                    <img src="${escapeHtml(thumbUrl)}" alt="" class="w-full h-32 object-cover rounded-lg" loading="lazy">
                </button>`;
            }
            html += '</div></div>';
        }

        if (images.length > 0) {
            html += '<div><h4 class="font-semibold text-sm text-mist uppercase tracking-wide mb-2">📷 Wasze zdjęcia</h4><div class="grid grid-cols-2 sm:grid-cols-3 gap-2">';
            for (const m of images) {
                const src = cfg.urls.assetBase + m.file_path;
                const idx = detailLightboxItems.length;
                detailLightboxItems.push({ type: 'image', url: src, source: authorNick });
                html += `<div class="relative group">
                    <button type="button" data-lightbox-idx="${idx}" class="block w-full cursor-zoom-in">
                        <img src="${escapeHtml(src)}" alt="${escapeHtml(m.caption || '')}" class="w-full h-32 object-cover rounded-lg">
                    </button>
                    ${isMine ? `<button type="button" data-delete-media="${m.id}" class="absolute top-1 right-1 w-7 h-7 rounded-full bg-black/60 text-white text-xs hover:bg-red-600 transition opacity-0 group-hover:opacity-100">🗑</button>` : ''}
                </div>`;
            }
            html += '</div></div>';
        }

        if (videos.length > 0) {
            html += '<div><h4 class="font-semibold text-sm text-mist uppercase tracking-wide mb-2">🎬 Wideo</h4><div class="grid grid-cols-2 sm:grid-cols-3 gap-2">';
            for (const m of videos) {
                const src = cfg.urls.assetBase + m.file_path;
                const idx = detailLightboxItems.length;
                detailLightboxItems.push({ type: 'video', url: src, source: authorNick });
                html += `<div class="relative group">
                    <button type="button" data-lightbox-idx="${idx}" class="block w-full cursor-zoom-in">
                        <video preload="metadata" class="w-full h-32 object-cover rounded-lg pointer-events-none">
                            <source src="${escapeHtml(src)}">
                        </video>
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <span class="w-10 h-10 rounded-full bg-black/60 text-white flex items-center justify-center text-lg">▶</span>
                        </div>
                    </button>
                    ${isMine ? `<button type="button" data-delete-media="${m.id}" class="absolute top-1 right-1 w-7 h-7 rounded-full bg-black/60 text-white text-xs hover:bg-red-600 transition opacity-0 group-hover:opacity-100">🗑</button>` : ''}
                </div>`;
            }
            html += '</div></div>';
        }

        if (links.length > 0) {
            html += '<div><h4 class="font-semibold text-sm text-mist uppercase tracking-wide mb-2">🔗 Linki</h4><ul class="space-y-1.5">';
            for (const m of links) {
                const label = m.caption || m.url;
                html += `<li class="flex items-center gap-2 group">
                    <a href="${escapeHtml(m.url)}" target="_blank" rel="noopener" class="flex-1 text-sm text-secondary hover:underline truncate">${escapeHtml(label)}</a>
                    ${isMine ? `<button type="button" data-delete-media="${m.id}" class="opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 text-sm transition">🗑</button>` : ''}
                </li>`;
            }
            html += '</ul></div>';
        }

        if (images.length === 0 && videos.length === 0 && links.length === 0 && googlePhotos.length === 0) {
            html = '<p class="text-sm text-mist italic">Brak media. ' + (isMine ? 'Dodaj zdjęcia/wideo/linki poniżej.' : '') + '</p>';
        }

        container.innerHTML = html;

        // Bind delete handlers
        container.querySelectorAll('[data-delete-media]').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Usunąć media?')) return;
                await deleteMedia(parseInt(btn.getAttribute('data-delete-media'), 10));
            });
        });

        // Bind lightbox handlers - klik na zdjecie/wideo otwiera fullscreen galerie
        container.querySelectorAll('[data-lightbox-idx]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const idx = parseInt(btn.getAttribute('data-lightbox-idx'), 10);
                openLightbox(detailLightboxItems, idx);
            });
        });
    }

    async function uploadMedia(file, type, inputEl) {
        if (!currentDetailPlaceId) return;
        const status = document.getElementById('upload-status');
        status.classList.remove('hidden', 'text-red-500', 'text-secondary');
        status.classList.add('text-mist');
        status.textContent = 'Wgrywam ' + (type === 'image' ? 'zdjęcie' : 'wideo') + '...';

        try {
            const fd = new FormData();
            fd.append('_csrf', cfg.csrf);
            fd.append('type', type);
            fd.append('file', file);

            const url = cfg.urls.mediaUploadTemplate.replace('ID', currentDetailPlaceId);
            const r = await fetch(url, { method: 'POST', body: fd });
            const data = await r.json();

            if (data.ok) {
                status.classList.remove('text-mist');
                status.classList.add('text-secondary');
                status.textContent = '✓ Wgrano!';
                setTimeout(() => status.classList.add('hidden'), 2000);
                await loadMediaForPlace(currentDetailPlaceId);
            } else {
                status.classList.remove('text-mist');
                status.classList.add('text-red-500');
                status.textContent = '⚠ ' + (data.error || 'Błąd uploadu');
            }
        } catch (e) {
            status.classList.add('text-red-500');
            status.textContent = '⚠ Błąd sieci';
        } finally {
            inputEl.value = ''; // pozwala wybrac ten sam plik ponownie
        }
    }

    async function submitLink() {
        if (!currentDetailPlaceId) return;
        const urlInput = document.getElementById('link-url');
        const captionInput = document.getElementById('link-caption');
        const errorBox = document.getElementById('link-error');
        errorBox.classList.add('hidden');

        try {
            const fd = new FormData();
            fd.append('_csrf', cfg.csrf);
            fd.append('url', urlInput.value.trim());
            fd.append('caption', captionInput.value.trim());

            const reqUrl = cfg.urls.mediaLinkTemplate.replace('ID', currentDetailPlaceId);
            const r = await fetch(reqUrl, { method: 'POST', body: fd });
            const data = await r.json();

            if (data.ok) {
                document.getElementById('link-modal').classList.add('hidden');
                document.getElementById('link-modal').classList.remove('flex');
                document.getElementById('link-form').reset();
                await loadMediaForPlace(currentDetailPlaceId);
            } else {
                errorBox.textContent = data.error || 'Nie udało się dodać linka.';
                errorBox.classList.remove('hidden');
            }
        } catch (e) {
            errorBox.textContent = 'Błąd sieci.';
            errorBox.classList.remove('hidden');
        }
    }

    async function deleteMedia(mediaId) {
        if (!currentDetailPlaceId) return;
        try {
            const fd = new FormData();
            fd.append('_csrf', cfg.csrf);
            const url = cfg.urls.mediaDeleteTemplate
                .replace('ID', currentDetailPlaceId)
                .replace('MID', mediaId);
            const r = await fetch(url, { method: 'POST', body: fd });
            const data = await r.json();
            if (data.ok) {
                await loadMediaForPlace(currentDetailPlaceId);
            } else {
                alert(data.error || 'Nie udało się usunąć.');
            }
        } catch (e) {
            alert('Błąd sieci.');
        }
    }

    // ========================================================================
    // Lightbox - pelnoekranowa galeria z nawigacja prawo/lewo
    // ========================================================================

    // Lightbox state: tablica obiektow {type, url, source}
    let lightboxItems = [];
    let lightboxIdx = 0;

    function setupLightbox() {
        const lb = document.getElementById('lightbox');
        if (!lb) return;

        document.getElementById('lightbox-close')?.addEventListener('click', closeLightbox);
        document.getElementById('lightbox-prev')?.addEventListener('click', () => navLightbox(-1));
        document.getElementById('lightbox-next')?.addEventListener('click', () => navLightbox(1));

        // Klik na ciemne tlo zamyka (nie klik na obraz/video)
        lb.addEventListener('click', (e) => {
            if (e.target === lb) closeLightbox();
        });

        // Klawiatura: Esc = zamknij, ← → = nawigacja
        document.addEventListener('keydown', (e) => {
            if (lb.classList.contains('hidden')) return;
            if (e.key === 'Escape') closeLightbox();
            else if (e.key === 'ArrowLeft') navLightbox(-1);
            else if (e.key === 'ArrowRight') navLightbox(1);
        });

        // Swipe na mobile
        let touchStartX = 0;
        let touchStartY = 0;
        lb.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            touchStartY = e.changedTouches[0].screenY;
        }, { passive: true });
        lb.addEventListener('touchend', (e) => {
            const dx = e.changedTouches[0].screenX - touchStartX;
            const dy = e.changedTouches[0].screenY - touchStartY;
            if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
                navLightbox(dx > 0 ? -1 : 1);
            }
        }, { passive: true });
    }

    function openLightbox(items, idx) {
        if (!Array.isArray(items) || items.length === 0) return;
        lightboxItems = items;
        lightboxIdx = Math.max(0, Math.min(idx, lightboxItems.length - 1));
        showLightboxItem();
        const lb = document.getElementById('lightbox');
        lb.classList.remove('hidden');
        lb.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        const lb = document.getElementById('lightbox');
        lb.classList.add('hidden');
        lb.classList.remove('flex');
        document.body.style.overflow = '';
        // Stop video jak grało
        const container = document.getElementById('lightbox-media-container');
        if (container) container.innerHTML = '';
    }

    function navLightbox(delta) {
        if (lightboxItems.length === 0) return;
        lightboxIdx = (lightboxIdx + delta + lightboxItems.length) % lightboxItems.length;
        showLightboxItem();
    }

    function showLightboxItem() {
        const container = document.getElementById('lightbox-media-container');
        const counter = document.getElementById('lightbox-counter');
        const caption = document.getElementById('lightbox-caption');
        const item = lightboxItems[lightboxIdx];
        if (!container || !item) return;

        // Wstaw nowy element - img lub video (stary jest usuwany - zatrzymuje playback)
        if (item.type === 'video') {
            container.innerHTML = `<video src="${escapeAttr(item.url)}" controls autoplay class="max-w-full max-h-[88vh] rounded-lg"></video>`;
        } else {
            container.innerHTML = `<img src="${escapeAttr(item.url)}" alt="" class="max-w-full max-h-[88vh] object-contain">`;
        }

        if (counter) counter.textContent = (lightboxIdx + 1) + ' / ' + lightboxItems.length;
        if (caption) {
            const typeLabel = item.type === 'video' ? '🎬 Wideo' : '📸 Zdjęcie';
            const source = item.source || '?';
            caption.textContent = `${typeLabel} · ${source}`;
        }

        const showNav = lightboxItems.length > 1;
        document.getElementById('lightbox-prev')?.classList.toggle('hidden', !showNav);
        document.getElementById('lightbox-next')?.classList.toggle('hidden', !showNav);
    }

    function escapeAttr(s) {
        return String(s ?? '').replaceAll('"', '&quot;').replaceAll("'", '&#39;');
    }

    // Eksportuj do globalnego namespace zeby InfoWindow / lista mogly trigger'owac
    window.__wyjazdownikPlaces = { openDetail: openPlaceDetail };
})();
