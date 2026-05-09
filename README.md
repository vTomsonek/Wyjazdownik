# Wyjazdownik.pl

> Ogarnij wakacje ze znajomymi raz na zawsze.

Aplikacja webowa do uzgadniania wspólnych wakacji w ekipie 5–15 znajomych: każdy dostaje unikatowy link, wypełnia ankietę, a wszyscy razem oglądają podsumowanie z rekomendacjami i rankingami (np. "Kebab Master") — idealne do włączenia na telewizorze.

---

## Stack

- **PHP 8.1+** (czysty PHP, bez frameworka, ale ze zorganizowaną strukturą — PSR-4)
- **MySQL / MariaDB** przez PDO + prepared statements
- **Tailwind CSS** (CDN) + **Vanilla JS** + **Alpine.js**
- **Leaflet.js** + **Leaflet.draw** dla mapy (OpenStreetMap, bez kluczy API)
- **Chart.js** dla wykresów
- **PHPMailer** dla maili (driver `log` w dev, SMTP w prod)
- **Composer** + autoloader PSR-4

---

## Wymagania

- XAMPP (Apache + MySQL + PHP 8.1+) lub równoważnik
- Composer (opcjonalnie do ETAPU 1, **wymagany od ETAPU 3** dla PHPMailer/Dotenv)
- Włączony `mod_rewrite` w Apache
- Rozszerzenia PHP: `pdo_mysql`, `json`, `mbstring`, `fileinfo`

---

## Instalacja na XAMPP (Windows)

### 1. Sklonuj/skopiuj projekt do `htdocs`

Projekt powinien znajdować się w katalogu `S:\xampp\htdocs\wyjazdownik` (lub odpowiedniku w Twojej instalacji XAMPP).

### 2. Konfiguracja środowiska

```bash
cp .env.example .env
```

Otwórz `.env` i uzupełnij:

```env
APP_ENV=dev
APP_URL=http://localhost/wyjazdownik/public

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=wyjazdownik
DB_USER=root
DB_PASS=

ADMIN_INITIAL_EMAIL=tomasz@jiko.pl
```

> Hasło do bazy w domyślnej instalacji XAMPP jest puste (`root` bez hasła).

### 3. Instalacja zależności (Composer) — opcjonalnie w ETAPIE 1

```bash
composer install
```

Jeśli nie masz Composera, ETAP 1 zadziała na fallbackowym autoloaderze. Composer będzie wymagany od ETAPU 3 dla bibliotek `phpmailer/phpmailer` i `vlucas/phpdotenv`.

### 4. Utworzenie bazy + załadowanie danych testowych

W panelu XAMPP uruchom Apache i MySQL, a następnie z katalogu projektu:

**CLI:**
```bash
php install.php
```

**Przeglądarka (tylko gdy `APP_ENV=dev`):**
```
http://localhost/wyjazdownik/install.php
```

Skrypt:
1. Połączy się z MySQL używając danych z `.env`
2. Utworzy bazę `wyjazdownik` (jeśli nie istnieje)
3. Załaduje `database/schema.sql` (10 tabel)
4. Załaduje `database/seed.sql` (1 admin, 1 wyjazd, 4 uczestników z pełnymi odpowiedziami)

Aby pominąć dane testowe: `php install.php --seed=no`

### 5. Otwarcie aplikacji

```
http://localhost/wyjazdownik/public/
```

Powinieneś zobaczyć stronę startową ETAPU 1 z mascotką, statusem środowiska i bazą oraz linkami do testowania (widoczne tylko w trybie `dev`).

---

## Struktura katalogów

```
wyjazdownik/
├── public/                  document root (Apache wskazuje tutaj)
│   ├── index.php            front controller
│   ├── .htaccess            rewrite + security headers
│   └── assets/              CSS, JS, img, uploads
├── src/                     kod aplikacji (PSR-4: App\)
│   ├── Core/                Router, Request, Response, Controller
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   ├── Database/            Connection.php (PDO singleton)
│   └── Helpers/             Csrf, Validator, QuestionLabels, functions.php
├── views/                   szablony PHP
├── database/                schema.sql + seed.sql
├── config/                  config.php (czyta .env)
├── storage/                 logi (poza document root)
├── bootstrap.php            autoloader + .env loader
├── install.php              instalator pierwszego uruchomienia
├── composer.json
├── .env.example
└── README.md
```

---

## Linki do testowania (po seed)

Po uruchomieniu `install.php` (z seed):

| Co                       | URL                                                                                    |
|--------------------------|----------------------------------------------------------------------------------------|
| Strona główna            | `/`                                                                                    |
| Health-check             | `/zdrowie`                                                                             |
| Wizard - Tomek           | `/p/1111111111111111111111111111111111111111111111111111111111111111`                  |
| Wizard - Kasia           | `/p/2222222222222222222222222222222222222222222222222222222222222222`                  |
| Wizard - Bartek          | `/p/3333333333333333333333333333333333333333333333333333333333333333`                  |
| Wizard - Ola             | `/p/4444444444444444444444444444444444444444444444444444444444444444`                  |
| Podsumowanie publiczne   | `/summary/cafe0000cafe0000cafe0000cafe0000cafe0000cafe0000cafe0000cafe0000`            |
| Logowanie admina         | `/admin/login`                                                                         |

> Po seed wszystkie linki działają z testowymi danymi. Bez seed (`--seed=no`) tylko strona główna i logowanie.

---

## Plan etapów

Wszystkie zakończone — projekt gotowy do deploymentu na produkcję.

| Etap | Zakres                                                              | Status      |
|------|---------------------------------------------------------------------|-------------|
| 1    | Fundament (router, DB, schema, seed, install, layout)               | ✅ gotowe   |
| 2    | Landing page (9 sekcji, animacje, dark mode)                        | ✅ gotowe   |
| 3    | Logowanie admina (magic link, auto-rejestracja)                     | ✅ gotowe   |
| 4    | CRUD wyjazdów i uczestników (upload bannerów/avatarów, audit log)   | ✅ gotowe   |
| 5    | Wizard uczestnika (12 kroków, autosave AJAX, walidacja)             | ✅ gotowe   |
| 6    | Mapa (Leaflet + Leaflet.draw, GeoJSON, kolory uczestników)          | ✅ gotowe   |
| 7    | Podsumowanie (15 sekcji + 22 odznaki + auto-rekomendacje + TV mode) | ✅ gotowe   |
| 8    | Polish + deployment Ubuntu (Apache + PHP-FPM + MariaDB + certbot)   | ✅ gotowe   |

---

## Deployment Ubuntu

Pełna instrukcja krok-po-kroku: [`docs/deployment.md`](docs/deployment.md).

**Skrót:**
- Apache 2.4 + PHP-FPM 8.1+ + MariaDB 10.6+
- Document root: `/var/www/wyjazdownik/public`
- Templates: `docs/apache-vhost.conf.example` + `docs/php-fpm-pool.conf.example`
- Deploy z lokalnej maszyny: `./deploy.sh` (rsync + composer install + permissions)
- HTTPS: `sudo certbot --apache -d wyjazdownik.pl`
- Cron cleanup: `0 3 * * * php /var/www/wyjazdownik/cron/cleanup.php`

---

## Bezpieczeństwo

- Wszystkie zapytania SQL przez **PDO prepared statements**
- **CSRF tokeny** na każdym POST (`Csrf::field()`, `Csrf::validate()`)
- Tokeny dostępu: `bin2hex(random_bytes(32))` (64 hex)
- Upload plików: whitelist MIME przez `finfo`, `.htaccess` blokujący PHP w `/uploads`
- Rate limiting (login 3/15 min, submit 30/h)
- Headers: X-Content-Type-Options, X-Frame-Options, Referrer-Policy
- HTTPS-only na produkcji (redirect w `.htaccess`)

---

## Licencja

Projekt prywatny. Kontakt: tomasz@jiko.pl
