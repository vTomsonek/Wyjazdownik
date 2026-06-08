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
    let voteStats = {};   // place_id => {avg, count, my_score}
    let tripStart = null; // {name, lat, lng} - punkt startowy wyjazdu (zdefiniowany przez admina)
    let startMarker = null;
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
            voteStats = JSON.parse(mapEl.getAttribute('data-votes') || '{}');
            const startRaw = mapEl.getAttribute('data-start') || '';
            tripStart = startRaw !== '' ? JSON.parse(startRaw) : null;
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
        setupVisitSliders();
        loadRouteSuggestions();
    };

    function formatVisitFull(vm) {
        vm = parseInt(vm, 10) || 60;
        if (vm < 60) return vm + ' min';
        const h = Math.floor(vm / 60);
        const m = vm % 60;
        if (m === 0) return h + 'h';
        return h + 'h ' + m + 'min';
    }

    function visitHint(vm) {
        vm = parseInt(vm, 10) || 60;
        if (vm <= 30)  return 'photo stop, punkt widokowy';
        if (vm <= 90)  return 'krótki przystanek';
        if (vm <= 180) return 'krótkie zwiedzanie';
        if (vm <= 360) return 'półdniówka';
        if (vm <= 540) return 'duże zwiedzanie';
        return 'cały dzień (Plitvice itp.)';
    }

    function bindVisitSlider(rangeId, displayId, hintId) {
        const range   = document.getElementById(rangeId);
        const display = document.getElementById(displayId);
        const hint    = document.getElementById(hintId);
        if (!range || !display) return;
        const update = () => {
            const v = range.value;
            display.textContent = formatVisitFull(v);
            if (hint) hint.textContent = visitHint(v);
        };
        range.addEventListener('input', update);
        update();
    }

    function setupVisitSliders() {
        bindVisitSlider('place-visit-minutes', 'place-visit-display', 'place-visit-hint');
        bindVisitSlider('detail-edit-visit-minutes', 'detail-edit-visit-display', 'detail-edit-visit-hint');
    }

    function renderMarkers() {
        markers.forEach(m => m.setMap(null));
        markers = [];
        if (startMarker) { startMarker.setMap(null); startMarker = null; }

        const bounds = new google.maps.LatLngBounds();

        // Marker startu - stale widoczny gdy admin go ustawil
        if (tripStart) {
            startMarker = new google.maps.Marker({
                position: { lat: parseFloat(tripStart.lat), lng: parseFloat(tripStart.lng) },
                map: map,
                title: '🏠 Start wyjazdu: ' + tripStart.name,
                icon: makeHomeIcon('#1A1A2E'),  // ciemny - wyrozniajacy sie od kolorowych pinezek
                zIndex: 100,
            });
            startMarker.addListener('click', () => {
                infoWindow.setContent(`<div class="iw-place">
                    <h4>🏠 Start wyjazdu</h4>
                    <p style="margin-top:4px;font-size:14px"><strong>${escapeHtml(tripStart.name)}</strong></p>
                    <p style="margin-top:6px;font-size:12px;color:#666">Punkt z którego ekipa wyjeżdża i wraca. Uwzględniany w propozycjach tras.</p>
                </div>`);
                infoWindow.open(map, startMarker);
            });
            bounds.extend(startMarker.getPosition());
        }

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
        if (markers.length > 0 || startMarker) {
            map.fitBounds(bounds, 60);
            if (markers.length + (startMarker ? 1 : 0) === 1) {
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
            // Trigger slidera by display+hint odswiezyl sie po form.reset()
            document.getElementById('place-visit-minutes')?.dispatchEvent(new Event('input'));
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
                fd.append('visit_minutes', document.getElementById('place-visit-minutes')?.value || '60');
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
                } else if (data && data.duplicate && data.duplicate.id) {
                    // Duplikat - daj przycisk do istniejacego miejsca
                    formError.innerHTML = '';
                    const msg = document.createElement('div');
                    msg.textContent = data.error || 'To miejsce już istnieje na mapie.';
                    msg.className = 'mb-2';
                    const openBtn = document.createElement('button');
                    openBtn.type = 'button';
                    openBtn.textContent = 'Otwórz istniejące miejsce →';
                    openBtn.className = 'text-sm font-semibold text-primary hover:text-primary-deep underline';
                    openBtn.addEventListener('click', () => {
                        closeModal();
                        if (window.__wyjazdownikPlaces && window.__wyjazdownikPlaces.openDetail) {
                            window.__wyjazdownikPlaces.openDetail(data.duplicate.id);
                        }
                    });
                    formError.appendChild(msg);
                    formError.appendChild(openBtn);
                    formError.classList.remove('hidden');
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
        const vm = parseInt(place.visit_minutes, 10) || 60;
        const visitFmt = vm < 60 ? vm + 'min' : (vm % 60 === 0 ? (vm / 60) + 'h' : (vm / 60).toFixed(1).replace('.', ',') + 'h');
        article.innerHTML = `
            <div class="flex items-start gap-2">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-white text-xs font-bold shrink-0 mt-0.5"
                      style="background:${author.color}">${initial}</span>
                <div class="flex-1 min-w-0">
                    <h4 class="font-semibold text-sm text-ink dark:text-pale truncate">${escapeHtml(place.name)}</h4>
                    ${place.address ? `<p class="text-xs text-mist truncate">${escapeHtml(place.address)}</p>` : ''}
                    ${place.description ? `<p class="text-xs text-ink/70 dark:text-pale/70 mt-1 line-clamp-2">${escapeHtml(place.description)}</p>` : ''}
                    <div class="mt-1.5 flex items-center gap-1.5 text-xs">
                        <span data-vote-summary class="inline-flex items-center gap-1.5"><span class="text-mist italic">Brak ocen</span></span>
                        <span class="text-mist">·</span>
                        <span data-visit-chip class="text-mist">⏱️ ${visitFmt}</span>
                        <span data-my-score class="ml-auto text-secondary hidden"></span>
                    </div>
                    <div class="mt-1.5 flex items-center gap-2 text-xs text-mist">
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

        // Edit button (nazwa + opis) - tylko dla autora
        document.getElementById('detail-edit-btn')?.addEventListener('click', () => enterEditMode());
        document.getElementById('detail-edit-cancel')?.addEventListener('click', () => exitEditMode());
        document.getElementById('detail-edit-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            submitEdit();
        });

        // Rating stars (half-star support)
        document.querySelectorAll('[data-detail-rate]').forEach(btn => {
            btn.addEventListener('click', () => {
                const score = parseFloat(btn.getAttribute('data-detail-rate'));
                submitVote(score);
            });
            btn.addEventListener('mouseenter', () => {
                const score = parseFloat(btn.getAttribute('data-detail-rate'));
                highlightStars(score, true);
            });
            btn.addEventListener('mouseleave', () => {
                renderRatingStars();
            });
        });
        document.getElementById('detail-rating-clear')?.addEventListener('click', () => {
            submitVoteDelete();
        });

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
        // Uploader widoczny dla kazdego uczestnika - kazdy moze dorzucic media do dowolnego miejsca
        document.getElementById('detail-uploader').classList.remove('hidden');
        // Edycja nazwy/opisu/czasu - tylko autor miejsca
        document.getElementById('detail-edit-btn')?.classList.toggle('hidden', !isMine);

        // Wyjdz z trybu edycji przy otwarciu (mogliśmy zostać w edit z poprzedniej karty)
        exitEditMode();

        // Render gwiazdek na podstawie aktualnych voteStats
        renderRatingStars();

        const modal = document.getElementById('detail-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Pobierz user media + Google Photos rownolegle
        await loadAllMediaForPlace(placeId, place.google_place_id);
    }

    // ========================================================================
    // Rating - gwiazdki + AJAX vote
    // ========================================================================

    function renderRatingStars() {
        if (!currentDetailPlaceId) return;
        const stats = voteStats[currentDetailPlaceId] || { avg: null, count: 0, my_score: null };
        const my = stats.my_score;
        highlightStars(my || 0, false);

        // Statystyki po prawej
        const statsEl = document.getElementById('detail-rating-stats');
        if (statsEl) {
            if (stats.avg !== null && stats.count > 0) {
                statsEl.innerHTML = `<div class="text-base"><strong class="text-amber-500">★ ${stats.avg.toFixed(1).replace('.', ',')}</strong> <span class="text-mist">/ 5</span></div><div class="text-xs text-mist">${stats.count} ${stats.count === 1 ? 'ocena' : (stats.count < 5 ? 'oceny' : 'ocen')}</div>`;
            } else {
                statsEl.innerHTML = '<div class="text-xs text-mist italic">Brak ocen</div>';
            }
        }

        // Aktualna wartosc obok gwiazdek
        const current = document.getElementById('detail-current-value');
        if (current) current.textContent = my ? Number(my).toFixed(1).replace('.', ',') : '';

        const clearBtn = document.getElementById('detail-rating-clear');
        if (clearBtn) clearBtn.classList.toggle('hidden', my === null);
    }

    function highlightStars(score, isHover) {
        // Half-star fill: width procentowy gwiazdek wypelnionych
        const filled = document.querySelector('#detail-half-stars .stars-filled');
        if (filled) {
            const pct = (Number(score) / 5) * 100;
            filled.style.width = pct + '%';
        }
        // Hover: pokaz wartosc obok
        if (isHover) {
            const current = document.getElementById('detail-current-value');
            if (current) current.textContent = score > 0 ? Number(score).toFixed(1).replace('.', ',') : '';
        }
    }

    async function submitVote(score) {
        if (!currentDetailPlaceId) return;
        try {
            const fd = new FormData();
            fd.append('_csrf', cfg.csrf);
            fd.append('score', String(score));
            const url = cfg.urls.voteTemplate.replace('ID', currentDetailPlaceId);
            const r = await fetch(url, { method: 'POST', body: fd });
            const data = await r.json();
            if (data.ok && data.stats) {
                voteStats[currentDetailPlaceId] = data.stats;
                renderRatingStars();
                updateListCardVote(currentDetailPlaceId, data.stats);
            } else {
                alert(data.error || 'Nie udało się zapisać oceny.');
            }
        } catch (e) {
            alert('Błąd sieci.');
        }
    }

    async function submitVoteDelete() {
        if (!currentDetailPlaceId) return;
        if (!confirm('Usunąć Twoją ocenę?')) return;
        try {
            const fd = new FormData();
            fd.append('_csrf', cfg.csrf);
            const url = cfg.urls.voteDeleteTemplate.replace('ID', currentDetailPlaceId);
            const r = await fetch(url, { method: 'POST', body: fd });
            const data = await r.json();
            if (data.ok && data.stats) {
                voteStats[currentDetailPlaceId] = data.stats;
                renderRatingStars();
                updateListCardVote(currentDetailPlaceId, data.stats);
            } else {
                alert(data.error || 'Nie udało się usunąć oceny.');
            }
        } catch (e) {
            alert('Błąd sieci.');
        }
    }

    // Refresh statystyk w karcie po prawej (bez przeladowania strony)
    function updateListCardVote(placeId, stats) {
        const card = document.querySelector(`#places-list article[data-place-id="${placeId}"]`);
        if (!card) return;
        // Summary (avg + count)
        const summary = card.querySelector('[data-vote-summary]');
        if (summary) {
            if (stats.avg !== null) {
                summary.innerHTML =
                    '<span class="text-amber-400">★</span>' +
                    '<span class="font-semibold text-ink dark:text-pale">' + Number(stats.avg).toFixed(1).replace('.', ',') + '</span>' +
                    '<span class="text-mist">(' + stats.count + ')</span>';
            } else {
                summary.innerHTML = '<span class="text-mist italic">Brak ocen</span>';
            }
        }
        // Moja ocena
        const my = card.querySelector('[data-my-score]');
        if (my) {
            if (stats.my_score !== null) {
                my.textContent = 'Twoja: ' + Number(stats.my_score).toFixed(1).replace('.', ',') + '★';
                my.classList.remove('hidden');
            } else {
                my.textContent = '';
                my.classList.add('hidden');
            }
        }
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
        const isPlaceOwner = place.participant_id === cfg.myParticipantId;
        const placeAuthorNick = (authors[place.participant_id] || {}).nickname || '?';

        // Helper - czy moge usunac dane media (uploader albo autor miejsca; legacy bez uploadera -> autor)
        const canDelete = m => {
            if (isPlaceOwner) return true;
            return m.participant_id !== null && m.participant_id !== undefined && m.participant_id === cfg.myParticipantId;
        };
        // Pokaz nick uploadera (legacy fallback -> autor miejsca)
        const uploaderNick = m => {
            const pid = m.participant_id;
            if (pid && authors[pid]) return authors[pid].nickname;
            return placeAuthorNick;
        };
        const uploaderColor = m => {
            const pid = m.participant_id;
            if (pid && authors[pid]) return authors[pid].color;
            return (authors[place.participant_id] || {}).color || '#6B7280';
        };
        // Badge "dodal X" w prawym dolnym rogu kafelka
        const uploaderBadge = m => {
            const nick = uploaderNick(m);
            const color = uploaderColor(m);
            return `<span class="absolute bottom-1 left-1 inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-black/65 text-white text-[10px] font-medium backdrop-blur-sm pointer-events-none">
                <span class="inline-block w-2 h-2 rounded-full" style="background:${escapeHtml(color)}"></span>${escapeHtml(nick)}
            </span>`;
        };

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
                detailLightboxItems.push({ type: 'image', url: src, source: uploaderNick(m) });
                html += `<div class="relative group">
                    <button type="button" data-lightbox-idx="${idx}" class="block w-full cursor-zoom-in">
                        <img src="${escapeHtml(src)}" alt="${escapeHtml(m.caption || '')}" class="w-full h-32 object-cover rounded-lg">
                    </button>
                    ${uploaderBadge(m)}
                    ${canDelete(m) ? `<button type="button" data-delete-media="${m.id}" class="absolute top-1 right-1 w-7 h-7 rounded-full bg-black/60 text-white text-xs hover:bg-red-600 transition opacity-0 group-hover:opacity-100">🗑</button>` : ''}
                </div>`;
            }
            html += '</div></div>';
        }

        if (videos.length > 0) {
            html += '<div><h4 class="font-semibold text-sm text-mist uppercase tracking-wide mb-2">🎬 Wideo</h4><div class="grid grid-cols-2 sm:grid-cols-3 gap-2">';
            for (const m of videos) {
                const src = cfg.urls.assetBase + m.file_path;
                const idx = detailLightboxItems.length;
                detailLightboxItems.push({ type: 'video', url: src, source: uploaderNick(m) });
                html += `<div class="relative group">
                    <button type="button" data-lightbox-idx="${idx}" class="block w-full cursor-zoom-in">
                        <video preload="metadata" class="w-full h-32 object-cover rounded-lg pointer-events-none">
                            <source src="${escapeHtml(src)}">
                        </video>
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <span class="w-10 h-10 rounded-full bg-black/60 text-white flex items-center justify-center text-lg">▶</span>
                        </div>
                    </button>
                    ${uploaderBadge(m)}
                    ${canDelete(m) ? `<button type="button" data-delete-media="${m.id}" class="absolute top-1 right-1 w-7 h-7 rounded-full bg-black/60 text-white text-xs hover:bg-red-600 transition opacity-0 group-hover:opacity-100">🗑</button>` : ''}
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
                    <span class="text-[10px] text-mist whitespace-nowrap">— ${escapeHtml(uploaderNick(m))}</span>
                    ${canDelete(m) ? `<button type="button" data-delete-media="${m.id}" class="opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 text-sm transition">🗑</button>` : ''}
                </li>`;
            }
            html += '</ul></div>';
        }

        if (images.length === 0 && videos.length === 0 && links.length === 0 && googlePhotos.length === 0) {
            html = '<p class="text-sm text-mist italic">Brak media. Dodaj zdjęcia/wideo/linki poniżej - kto pierwszy pokaże ekipie czemu warto!</p>';
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
        const box       = document.getElementById('upload-status');
        const label     = document.getElementById('upload-label');
        const percentEl = document.getElementById('upload-percent');
        const bar       = document.getElementById('upload-progress-bar');

        if (!box || !label || !bar) return;

        // Client-side validation: rozmiar + format (zanim uruchomimy XHR)
        const limits = {
            image: { bytes: 5 * 1024 * 1024,  label: '5 MB', types: ['image/jpeg', 'image/png', 'image/webp'], typesLabel: 'JPEG / PNG / WebP' },
            video: { bytes: 50 * 1024 * 1024, label: '50 MB', types: ['video/mp4', 'video/webm', 'video/quicktime'], typesLabel: 'MP4 / WebM / MOV' },
        };
        const lim = limits[type];
        if (lim) {
            if (file.size > lim.bytes) {
                showUploadError(`Plik za duży (${formatBytes(file.size)}). Maksimum dla ${type === 'image' ? 'zdjęć' : 'wideo'}: ${lim.label}.`);
                if (inputEl) inputEl.value = '';
                return;
            }
            if (file.type && !lim.types.includes(file.type)) {
                showUploadError(`Nieobsługiwany format (${file.type || 'nieznany'}). Akceptowane: ${lim.typesLabel}.`);
                if (inputEl) inputEl.value = '';
                return;
            }
        }

        // Reset UI
        box.classList.remove('hidden');
        bar.classList.remove('bg-secondary', 'bg-red-500');
        bar.classList.add('bg-primary-deep');
        bar.style.width = '0%';
        percentEl.textContent = '0%';
        label.textContent = `Wgrywam ${type === 'image' ? 'zdjęcie' : 'wideo'} (${formatBytes(file.size)})`;

        const fd = new FormData();
        fd.append('_csrf', cfg.csrf);
        fd.append('type', type);
        fd.append('file', file);

        const url = cfg.urls.mediaUploadTemplate.replace('ID', currentDetailPlaceId);

        try {
            const data = await xhrUpload(url, fd, (loaded, total) => {
                const pct = total > 0 ? (loaded / total) * 100 : 0;
                bar.style.width = pct + '%';
                percentEl.textContent = Math.floor(pct) + '%';
                if (pct >= 99) {
                    label.textContent = 'Przetwarzam na serwerze...';
                    bar.classList.remove('animate-pulse');
                    bar.classList.add('animate-pulse');
                }
            });

            if (data && data.ok) {
                bar.style.width = '100%';
                percentEl.textContent = '100%';
                bar.classList.remove('animate-pulse', 'bg-primary-deep');
                bar.classList.add('bg-secondary');
                label.textContent = '✓ Wgrano!';
                setTimeout(() => box.classList.add('hidden'), 1800);
                await loadMediaForPlace(currentDetailPlaceId);
            } else {
                bar.classList.remove('animate-pulse', 'bg-primary-deep');
                bar.classList.add('bg-red-500');
                label.textContent = '⚠ ' + ((data && data.error) || 'Błąd uploadu');
            }
        } catch (e) {
            bar.classList.remove('animate-pulse', 'bg-primary-deep');
            bar.classList.add('bg-red-500');
            label.textContent = '⚠ ' + (e?.message || 'Błąd sieci');
        } finally {
            inputEl.value = '';
        }
    }

    /**
     * Upload przez XHR z prawdziwym progress callback (fetch nie wspiera upload progress).
     */
    function xhrUpload(url, formData, onProgress) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) onProgress(e.loaded, e.total);
            });
            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try { resolve(JSON.parse(xhr.responseText)); }
                    catch (e) { reject(new Error('Nieprawidłowa odpowiedź serwera')); }
                } else {
                    // Spróbuj rozparsować błąd JSON z odpowiedzi
                    let err = 'HTTP ' + xhr.status;
                    try {
                        const j = JSON.parse(xhr.responseText);
                        if (j && j.error) err = j.error;
                    } catch (e) {}
                    reject(new Error(err));
                }
            });
            xhr.addEventListener('error', () => reject(new Error('Błąd sieci')));
            xhr.addEventListener('abort', () => reject(new Error('Anulowano upload')));
            xhr.send(formData);
        });
    }

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB';
        return (bytes / 1024 / 1024).toFixed(1) + ' MB';
    }

    function showUploadError(message) {
        const box       = document.getElementById('upload-status');
        const label     = document.getElementById('upload-label');
        const percentEl = document.getElementById('upload-percent');
        const bar       = document.getElementById('upload-progress-bar');
        if (!box || !label || !bar) {
            alert(message);
            return;
        }
        box.classList.remove('hidden');
        bar.classList.remove('bg-secondary', 'bg-primary-deep', 'animate-pulse');
        bar.classList.add('bg-red-500');
        bar.style.width = '100%';
        if (percentEl) percentEl.textContent = '⚠';
        label.textContent = message;
        // Schowaj po chwili
        setTimeout(() => { box.classList.add('hidden'); }, 5000);
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

    // ========================================================================
    // ETAP 4: Propozycje tras - render kart + rysowanie tras na mapie z OSRM
    // ========================================================================

    let routesCache = [];
    let activeRouteMarkers = [];   // numerowane markery na trasie
    let activeRoutePolyline = null;

    async function loadRouteSuggestions() {
        const list = document.getElementById('routes-list');
        if (!list || !cfg.urls.routes) return;
        try {
            const r = await fetch(cfg.urls.routes);
            const data = await r.json();
            if (data.ok && Array.isArray(data.routes)) {
                routesCache = data.routes;
                renderRoutesList();
            } else {
                list.innerHTML = '<p class="text-mist italic text-sm col-span-full">Brak propozycji.</p>';
            }
        } catch (e) {
            list.innerHTML = '<p class="text-red-500 text-sm col-span-full">Błąd ładowania propozycji.</p>';
        }
    }

    function renderRoutesList() {
        const list = document.getElementById('routes-list');
        if (!list) return;
        if (routesCache.length === 0) {
            list.innerHTML = '<p class="text-mist italic text-sm col-span-full">Brak propozycji. Dodaj więcej miejsc (min 2) i oceń je ★3 lub wyżej żeby zobaczyć propozycje tras.</p>';
            return;
        }
        const colors = ['#FF6B35', '#2EC4B6', '#FFD23F', '#C2410C', '#0EA5E9'];
        list.innerHTML = routesCache.map((route, idx) => {
            const color = colors[idx % colors.length];
            const placesShort = route.places.slice(0, 5).map((p, i) => `${i+1}. ${escapeHtml(p.name)}`).join('<br>');
            const more = route.places.length > 5 ? `<br>+${route.places.length - 5} więcej` : '';
            const fromBadge = route.start ? `<span>🏠 z ${escapeHtml(route.start.name)}</span>` : '';
            return `<div class="rounded-2xl border-2 border-mist/15 bg-paper dark:bg-deep p-5 hover:border-primary/30 transition" data-route-idx="${idx}">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <h3 class="font-display font-bold text-lg text-ink dark:text-pale">${escapeHtml(route.name)}</h3>
                    <span class="w-4 h-4 rounded-full shrink-0 mt-1.5" style="background:${color}"></span>
                </div>
                <div class="flex flex-wrap gap-3 text-xs text-mist mb-3">
                    ${fromBadge}
                    <span>📍 ${route.places.length} miejsc</span>
                    <span>🛣️ ~${route.distance_km} km</span>
                    <span>⭐ ${route.avg_score.toFixed(1).replace('.', ',')}</span>
                </div>
                <div class="text-sm text-ink/80 dark:text-pale/80 mb-3 leading-relaxed">${placesShort}${more}</div>
                <button type="button" data-show-route="${idx}"
                        class="w-full px-3 py-2 rounded-full text-white font-semibold text-sm hover:scale-105 transition"
                        style="background:${color}">
                    Pokaż na mapie
                </button>
            </div>`;
        }).join('');

        list.querySelectorAll('[data-show-route]').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = parseInt(btn.getAttribute('data-show-route'), 10);
                showRouteOnMap(idx, colors[idx % colors.length]);
            });
        });

        document.getElementById('routes-clear')?.addEventListener('click', clearRoute);
    }

    async function showRouteOnMap(routeIdx, color) {
        const route = routesCache[routeIdx];
        if (!route) return;
        clearRoute();

        markers.forEach(m => m.setOpacity(0.25));

        // Ukryj permanent startMarker - bedzie route-owy w jego kolorze
        if (startMarker) startMarker.setMap(null);

        const bounds = new google.maps.LatLngBounds();

        // Marker startowy (jak istnieje) z ikona domku
        if (route.start) {
            const routeStartMarker = new google.maps.Marker({
                position: { lat: parseFloat(route.start.lat), lng: parseFloat(route.start.lng) },
                map: map,
                icon: makeHomeIcon(color),
                zIndex: 999,
                title: '🏠 Start: ' + route.start.name,
            });
            routeStartMarker.addListener('click', () => {
                infoWindow.setContent(`<div class="iw-place"><h4>🏠 Start trasy</h4>
                    <p style="margin-top:4px;font-size:14px"><strong>${escapeHtml(route.start.name)}</strong></p>
                    <p style="margin-top:6px;font-size:13px;color:#666">Punkt wyjazdu ekipy. Stąd zaczynamy trasę "${escapeHtml(route.name)}".</p></div>`);
                infoWindow.open(map, routeStartMarker);
            });
            activeRouteMarkers.push(routeStartMarker);
            bounds.extend(routeStartMarker.getPosition());
        }

        // Numerowane markery
        route.places.forEach((p, i) => {
            const marker = new google.maps.Marker({
                position: { lat: parseFloat(p.lat), lng: parseFloat(p.lng) },
                map: map,
                label: { text: String(i + 1), color: 'white', fontWeight: '700', fontSize: '13px' },
                icon: makeNumberedIcon(color),
                zIndex: 1000 + i,
            });
            marker.addListener('click', () => {
                infoWindow.setContent(`<div class="iw-place"><h4>${escapeHtml(p.name)}</h4>
                    <p style="font-size:12px;color:#666">${escapeHtml(p.address || '')}</p>
                    <p style="margin-top:6px;font-size:13px"><strong>Punkt ${i+1}</strong> trasy "${escapeHtml(route.name)}"</p></div>`);
                infoWindow.open(map, marker);
            });
            activeRouteMarkers.push(marker);
            bounds.extend(marker.getPosition());
        });

        // Polyline - z punktem startowym jeśli istnieje + powrót do startu (round trip)
        const pathPoints = route.start
            ? [route.start, ...route.places, route.start]
            : route.places;
        if (pathPoints.length >= 2) {
            await drawRoutePolyline(pathPoints, color);
        }

        try { map.fitBounds(bounds, 80); } catch (e) {}

        document.getElementById('routes-clear')?.classList.remove('hidden');
        document.getElementById('places-map')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function makeHomeIcon(color) {
        // Ikona domu z kolorem trasy - dla punktu startowego
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="44" height="56" viewBox="0 0 44 56">
            <path d="M22 0C10 0 0 10 0 22c0 16 22 34 22 34s22-18 22-34C44 10 34 0 22 0z" fill="${color}" stroke="white" stroke-width="3"/>
            <path d="M22 11 L33 21 L31 21 L31 31 L24 31 L24 24 L20 24 L20 31 L13 31 L13 21 L11 21 Z" fill="white"/>
        </svg>`;
        return {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
            scaledSize: new google.maps.Size(44, 56),
            anchor: new google.maps.Point(22, 56),
        };
    }

    function makeNumberedIcon(color) {
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="44" height="56" viewBox="0 0 44 56">
            <path d="M22 0C10 0 0 10 0 22c0 16 22 34 22 34s22-18 22-34C44 10 34 0 22 0z" fill="${color}" stroke="white" stroke-width="3"/>
        </svg>`;
        return {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
            scaledSize: new google.maps.Size(44, 56),
            anchor: new google.maps.Point(22, 56),
            labelOrigin: new google.maps.Point(22, 21),
        };
    }

    async function drawRoutePolyline(places, color) {
        // Skladaj URL OSRM z par koordynatow
        const coords = places.map(p => `${p.lng},${p.lat}`).join(';');
        const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${coords}?overview=full&geometries=geojson`;

        try {
            const r = await fetch(osrmUrl);
            const data = await r.json();
            if (data.code === 'Ok' && data.routes && data.routes[0]) {
                const geojson = data.routes[0].geometry;
                const path = geojson.coordinates.map(c => ({ lat: c[1], lng: c[0] }));
                activeRoutePolyline = new google.maps.Polyline({
                    path,
                    geodesic: false,
                    strokeColor: color,
                    strokeOpacity: 0.85,
                    strokeWeight: 4,
                    map: map,
                });
                return;
            }
        } catch (e) {
            console.warn('[places] OSRM failed, using straight line', e);
        }

        // Fallback - linia prosta przez kolejne punkty
        const path = places.map(p => ({ lat: parseFloat(p.lat), lng: parseFloat(p.lng) }));
        activeRoutePolyline = new google.maps.Polyline({
            path,
            geodesic: true,
            strokeColor: color,
            strokeOpacity: 0.6,
            strokeWeight: 3,
            map: map,
        });
    }

    function clearRoute() {
        activeRouteMarkers.forEach(m => m.setMap(null));
        activeRouteMarkers = [];
        if (activeRoutePolyline) {
            activeRoutePolyline.setMap(null);
            activeRoutePolyline = null;
        }
        // Przywroc opacity oryginalnym markerom
        markers.forEach(m => m.setOpacity(1.0));
        // Przywroc permanent marker startu (byl ukryty przy pokazywaniu trasy)
        if (startMarker && tripStart) startMarker.setMap(map);
        document.getElementById('routes-clear')?.classList.add('hidden');
    }

    // ========================================================================
    // Edycja nazwy + opisu istniejacego miejsca (autor only)
    // ========================================================================

    function enterEditMode() {
        if (!currentDetailPlaceId) return;
        const place = places.find(p => p.id === currentDetailPlaceId);
        if (!place) return;

        document.getElementById('detail-name').classList.add('hidden');
        document.getElementById('detail-description').classList.add('hidden');
        document.getElementById('detail-edit-btn')?.classList.add('hidden');
        // Ukryj resztę sekcji - tylko form do edycji powinien być widoczny
        document.getElementById('detail-rating')?.classList.add('hidden');
        document.getElementById('detail-media')?.classList.add('hidden');
        document.getElementById('detail-uploader')?.classList.add('hidden');

        const form = document.getElementById('detail-edit-form');
        form.classList.remove('hidden');
        document.getElementById('detail-edit-name').value = place.name || '';
        document.getElementById('detail-edit-description').value = place.description || '';
        const visitSel = document.getElementById('detail-edit-visit-minutes');
        if (visitSel) {
            visitSel.value = String(place.visit_minutes || 60);
            visitSel.dispatchEvent(new Event('input'));
        }
        document.getElementById('detail-edit-error').classList.add('hidden');
        setTimeout(() => document.getElementById('detail-edit-name')?.focus(), 50);
    }

    function exitEditMode() {
        document.getElementById('detail-name').classList.remove('hidden');
        document.getElementById('detail-description').classList.remove('hidden');
        document.getElementById('detail-edit-form')?.classList.add('hidden');
        // Pokaż z powrotem wszystkie sekcje
        document.getElementById('detail-rating')?.classList.remove('hidden');
        document.getElementById('detail-media')?.classList.remove('hidden');
        // Uploader widoczny dla kazdego uczestnika; edit-btn tylko dla autora miejsca
        const place = places.find(p => p.id === currentDetailPlaceId);
        const isMine = place && place.participant_id === cfg.myParticipantId;
        document.getElementById('detail-uploader')?.classList.remove('hidden');
        document.getElementById('detail-edit-btn')?.classList.toggle('hidden', !isMine);
        document.getElementById('detail-edit-error')?.classList.add('hidden');
    }

    async function submitEdit() {
        if (!currentDetailPlaceId) return;
        const name = document.getElementById('detail-edit-name').value.trim();
        const description = document.getElementById('detail-edit-description').value.trim();
        const errorBox = document.getElementById('detail-edit-error');
        errorBox.classList.add('hidden');

        if (name === '') {
            errorBox.textContent = 'Podaj nazwę.';
            errorBox.classList.remove('hidden');
            return;
        }

        try {
            const fd = new FormData();
            fd.append('_csrf', cfg.csrf);
            fd.append('name', name);
            fd.append('description', description);
            fd.append('visit_minutes', document.getElementById('detail-edit-visit-minutes')?.value || '60');
            const url = cfg.urls.editTemplate.replace('ID', currentDetailPlaceId);
            const r = await fetch(url, { method: 'POST', body: fd });
            const data = await r.json();
            if (data.ok && data.place) {
                // Update lokalna kopia
                const idx = places.findIndex(p => p.id === currentDetailPlaceId);
                if (idx !== -1) places[idx] = data.place;
                // Update widoki
                document.getElementById('detail-name').textContent = data.place.name;
                document.getElementById('detail-description').textContent = data.place.description || '';
                // Update karta po prawej (lista)
                updateListCardMeta(data.place);
                // Update marker title
                const marker = markers.find(m => m._place && m._place.id === currentDetailPlaceId);
                if (marker) {
                    marker._place = data.place;
                    marker.setTitle(data.place.name);
                }
                exitEditMode();
            } else {
                errorBox.textContent = data.error || 'Nie udało się zapisać.';
                errorBox.classList.remove('hidden');
            }
        } catch (e) {
            errorBox.textContent = 'Błąd sieci.';
            errorBox.classList.remove('hidden');
        }
    }

    function formatVisitMinutes(vm) {
        vm = parseInt(vm, 10) || 60;
        if (vm < 60) return vm + 'min';
        if (vm % 60 === 0) return (vm / 60) + 'h';
        return (vm / 60).toFixed(1).replace('.', ',') + 'h';
    }

    function updateListCardMeta(place) {
        const card = document.querySelector(`#places-list article[data-place-id="${place.id}"]`);
        if (!card) return;

        // Nazwa
        const nameEl = card.querySelector('h4');
        if (nameEl) nameEl.textContent = place.name;

        // Opis: dodaj/aktualizuj/usun
        const innerCol = card.querySelector('.flex-1.min-w-0');
        let descEl = card.querySelector('p.line-clamp-2');
        if (place.description) {
            if (descEl) {
                descEl.textContent = place.description;
                descEl.classList.remove('hidden');
            } else if (innerCol) {
                // Wstaw po adresie (lub po h4 jezeli adres nie istnieje)
                const addrEl = innerCol.querySelector('h4 + p.text-mist');
                const newDesc = document.createElement('p');
                newDesc.className = 'text-xs text-ink/70 dark:text-pale/70 mt-1 line-clamp-2';
                newDesc.textContent = place.description;
                if (addrEl) addrEl.insertAdjacentElement('afterend', newDesc);
                else nameEl.insertAdjacentElement('afterend', newDesc);
            }
        } else if (descEl) {
            descEl.remove();
        }

        // Czas zwiedzania
        const visitChip = card.querySelector('[data-visit-chip]');
        if (visitChip) {
            visitChip.textContent = '⏱️ ' + formatVisitMinutes(place.visit_minutes);
        }
    }

    // Eksportuj do globalnego namespace zeby InfoWindow / lista mogly trigger'owac
    window.__wyjazdownikPlaces = { openDetail: openPlaceDetail };
})();
