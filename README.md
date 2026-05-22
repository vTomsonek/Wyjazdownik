<div align="center">

# 🏖️ Wyjazdownik.pl

**Ogarnij wakacje ze znajomymi raz na zawsze.**

Polskie narzędzie do uzgadniania wspólnych wakacji w ekipie 5–15 znajomych.
Każdy znajomy wypełnia ankietę, dorzuca miejsca na wspólną mapę i ocenia pomysły reszty. Razem oglądacie podsumowanie z rekomendacjami, rankingami i propozycjami tras — idealne na wieczór gdy włączacie telewizor.

[**🌐 wyjazdownik.pl**](https://wyjazdownik.pl) · [Funkcje](#funkcje) · [Stack](#stack) · [Architektura](#architektura) · [Instalacja](#instalacja-lokalnie-xampp) · [Deployment](#deployment)

![Wyjazdownik.pl](public/assets/img/og-image.png)

</div>

---

## Funkcje

### Planowanie przed wyjazdem
- 📅 **Inteligentny kalendarz** — heatmapa terminów, automatycznie znajdzie dni gdzie wszyscy mogą
- 💰 **Wspólny budżet** — wykres słupkowy + algorytm "najsłabsze ogniwo decyduje"
- 🏆 **22 odznaki** — Kebab Master, Maszyna, Plażowicz, Górski Wilk, Krezus, Backpacker... algorytm przyznaje deterministycznie na podstawie odpowiedzi
- 🎯 **Auto-rekomendacje destynacji** — dopasowane do kombinacji preferencji ekipy
- 📺 **Tryb prezentacji** — fullscreen z nawigacją klawiaturą, przygotowane pod TV

### Wspólna mapa atrakcji
- 🗺️ **Google Places autocomplete** — każdy dodaje miejsca z pełnej bazy Google (adres, zdjęcia, kategoria)
- 📸 **Galeria mediów per miejsce** — wgrywanie zdjęć (max 5 × 5 MB) i wideo (max 3 × 50 MB), linki do bloga/YouTube/Booking
- ⭐ **Oceny ekipy z półgwiazdkami** — 0,5–5,0★, mini-wizard do szybkiego oceniania serii miejsc
- 🚗 **AI propozycje tras** — algorytm klastruje miejsca po lokalizacji (single-linkage 450 km), TSP nearest-neighbor, round-trip z punktu startowego, oszacowuje liczbę dni na podstawie czasu zwiedzania każdej atrakcji
- 📍 **Punkt startowy wyjazdu** — admin wybiera miasto przez Nominatim, algorytm liczy dystanse stamtąd

### W trasie
- 🚗 **Tryb trasy (live geolocation)** — publiczny widok `/summary/{token}/trasa`, działa na telefonie w aucie
  - Twoja pozycja na żywo (high-accuracy GPS, `watchPosition`)
  - Lista miejsc posortowana po dystansie haversine
  - Modal ze szczegółami: opis, galeria Google + ekipy, lightbox
  - Przycisk „Nawiguj" otwiera natywne Google Maps z trasą A→B
  - Tryb fullscreen z natywnym Fullscreen API
  - Brak wysyłania pozycji na serwer — wszystko w przeglądarce

### System
- 🔗 **Magic link auth** — bez haseł, tylko email
- 🌓 **Dark mode** — pełen, zapamiętany w localStorage
- 📱 **Mobile-first** — wszystkie ekrany działają na telefonie, tablecie i TV (1920+)

## Stack

- **Backend** — PHP 8.1+, własny mikro-framework (router, PSR-4 autoloader, prosty MVC)
- **Baza** — MySQL / MariaDB przez PDO + prepared statements (utf8mb4)
- **Frontend** — Tailwind CSS production build (lokalny, przez npm), Vanilla JS
- **Mapy** — Google Maps JS API + Places API (New) + Geocoding API
- **Routing samochodowy** — OSRM (`router.project-osrm.org`)
- **Geocoding admina** — Nominatim (OpenStreetMap) do wyboru punktu startowego
- **Email** — PHPMailer (driver `log` w dev, `smtp` w prod)
- **Composer** — autoloader + dependencies (`phpmailer/phpmailer`, `vlucas/phpdotenv`)

## Architektura

```
wyjazdownik/
├── public/                  # document root (Apache wskazuje tutaj)
│   ├── index.php            # front controller + router
│   ├── .htaccess            # rewrite + security headers + CSP + caching
│   └── assets/              # CSS (tailwind production), JS, img, uploads
├── src/                     # kod aplikacji (PSR-4: App\)
│   ├── Core/                # Router, Request, Response, Controller
│   ├── Controllers/         # 10 kontrolerów (Admin*, Participant*, Home,
│   │                        # Summary, TripPlaces, LiveRoute)
│   ├── Models/              # 7 modeli (Admin, Trip, Participant, MapPin,
│   │                        # TripPlace, TripPlaceVote, TripPlaceMedia)
│   ├── Services/            # 14 serwisów (Auth, Mailer, Ranking,
│   │                        # Recommendation, RouteSuggestion, MapColor,
│   │                        # SummaryAggregator, Upload, ...)
│   ├── Database/            # Connection.php (PDO singleton)
│   ├── Helpers/             # Csrf, QuestionLabels, QuestionFormatter,
│   │                        # Validator, functions (url, asset, e, view, ...)
│   └── css/tailwind.css     # entry point dla build:css
├── views/                   # 86 szablonów PHP
│   ├── layouts/             # app, admin, summary, wizard, route
│   ├── partials/            # header, footer, landing/, illustrations/, wizard/
│   ├── home/                # landing page
│   ├── admin/               # CRUD wyjazdów i uczestników
│   ├── participant/         # wizard 12 kroków + atrakcje (places.php) +
│   │                        # mini-wizard ocen (places-rate.php)
│   ├── summary/             # 16 sekcji podsumowania
│   └── route/               # live.php — tryb trasy
├── database/                # schema.sql + seed.sql + 12 migracji
├── config/                  # config.php (czyta .env)
├── cron/                    # cleanup.php (cron job)
├── docs/                    # deployment.md + Apache/PHP-FPM templates
├── storage/                 # logi (poza document root)
├── bootstrap.php            # autoloader + .env loader
├── install.php              # instalator pierwszego uruchomienia
├── deploy.sh                # rsync deployment
├── tailwind.config.js       # Tailwind 3.4 config (skanuje views/ + assets/js/)
├── package.json             # npm scripts (build:css, watch:css)
└── composer.json
```

## Instalacja (lokalnie, XAMPP)

**Wymagania**: XAMPP / Apache + PHP 8.1+ + MySQL/MariaDB + Composer + Node.js 18+ + `mod_rewrite`

```bash
git clone https://github.com/vTomsonek/Wyjazdownik.git
cd Wyjazdownik

# 1) Backend
cp .env.example .env
nano .env   # ustaw DB_USER, DB_PASS, ADMIN_INITIAL_EMAIL, GOOGLE_MAPS_API_KEY

composer install

# 2) Frontend (Tailwind production build)
npm install
npm run build:css        # jednorazowy build
# albo: npm run watch:css   # tryb dev z auto-rebuild

# 3) Baza
php install.php          # tworzy bazę, schema, seed (1 admin + 1 trip + 4 uczestników)
```

Otwórz `http://localhost/wyjazdownik/public/`.

### Google API — wymagane do mapy atrakcji i trybu trasy

W [Google Cloud Console](https://console.cloud.google.com/) utwórz projekt i włącz:
- **Maps JavaScript API** (mapa, markery)
- **Places API (New)** — uwaga, *nie* "Places API" stary, tylko nowy
- **Geocoding API** (rezerwa, używane sporadycznie)

Następnie wygeneruj klucz API (zalecane: ograniczenie po HTTP referrer + ograniczenie API do tych trzech) i wpisz do `.env`:
```
GOOGLE_MAPS_API_KEY=AIzaSy...
```

Linki testowe (po `php install.php`):
- Wizard – Tomek: `/p/1111111111111111111111111111111111111111111111111111111111111111`
- Wizard – Kasia: `/p/2222...`
- Wizard – Bartek: `/p/3333...`
- Wizard – Ola: `/p/4444...`
- Podsumowanie publiczne: `/summary/cafe0000cafe0000cafe0000cafe0000cafe0000cafe0000cafe0000cafe0000`
- Tryb trasy: `/summary/cafe0000.../trasa`

## Migracje

Po każdej aktualizacji projektu warto sprawdzić nowe migracje w `database/migrations/`:

```bash
# Najnowsze migracje (kolejność wykonania = nazwa pliku):
ls database/migrations/
# 006_trip_places.sql            - tabela miejsc dodawanych przez uczestników
# 007_trip_places_google.sql     - kolumna google_place_id
# 008_trip_place_media.sql       - galeria mediów
# 009_trip_place_votes.sql       - oceny gwiazdkami
# 010_trip_start_location.sql    - punkt startowy wyjazdu
# 011_trip_place_votes_decimal.sql  - półgwiazdki (DECIMAL zamiast TINYINT)
# 012_trip_places_visit_minutes.sql - czas zwiedzania per miejsce
# 013_extend_google_place_id.sql    - VARCHAR(255) dla nowych dłuższych ID Google
```

Migracja:
```bash
mysql -u $DB_USER -p $DB_NAME < database/migrations/XXX_nazwa.sql
```

## Deployment

Pełna instrukcja krok-po-kroku Ubuntu + Apache + PHP-FPM + MariaDB + Cloudflare:
**[`docs/deployment.md`](docs/deployment.md)**

Skrót:
```bash
./deploy.sh                                          # rsync + composer install + npm build + permissions
ssh user@vps "cd /var/www/wyjazdownik && php install.php --seed=no"
sudo certbot --apache -d twoja-domena.pl              # HTTPS (lub Cloudflare flexible SSL)
```

## Bezpieczeństwo

- ✅ PDO prepared statements (zero konkatenacji SQL)
- ✅ CSRF tokens na każdym POST
- ✅ `bin2hex(random_bytes(32))` dla wszystkich tokenów (256 bit entropii)
- ✅ Rate limiting (3 próby logowania / 15 min / IP)
- ✅ Upload: whitelist MIME przez `finfo`, max size, losowe nazwy plików, defensive truncate przed INSERT
- ✅ Headers: CSP, HSTS, X-Frame-Options, COOP, CORP, Permissions-Policy (`geolocation=(self)` dla trybu trasy)
- ✅ Magic link jednorazowy (`used_at`), 15 min TTL
- ✅ Auto-rejestracja przez magic link (atak: cudzy email → atakujący nie dostanie kodu)
- ✅ `httpOnly` + `samesite=Lax` + `secure` cookies
- ✅ Per-admin authorization (admin widzi tylko swoje wyjazdy)
- ✅ Tryb trasy: pozycja użytkownika nigdy nie idzie na serwer, wszystko w przeglądarce

## Licencja

[**PolyForm Noncommercial 1.0.0**](LICENSE)

✅ Możesz: używać, modyfikować, hostować dla siebie/ekipy/przyjaciół, kontrybuować
❌ Nie możesz: hostować jako komercyjny serwis konkurencyjny

W skrócie: **rób z tym co chcesz prywatnie**, ale nie zarabiaj na tym kopiując pomysł.

## Autor

[**vTomsonek**](https://github.com/vTomsonek)

Pull requesty mile widziane. Zgłaszaj bugi i pomysły przez [GitHub Issues](https://github.com/vTomsonek/Wyjazdownik/issues).

---

<div align="center">

Stworzone z miłością do dobrych wyjazdów ☀️

</div>
