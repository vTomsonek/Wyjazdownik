<div align="center">

# 🏖️ Wyjazdownik.pl

**Ogarnij wakacje ze znajomymi raz na zawsze.**

Polskie narzędzie do uzgadniania wspólnych wakacji w ekipie 5–15 znajomych.
Każdy znajomy wypełnia ankietę, a wszyscy razem oglądają wspólny plan z rekomendacjami i rankingami — idealne na wieczór gdy włączacie telewizor.

[**🌐 wyjazdownik.pl**](https://wyjazdownik.pl) · [Demo](#demo) · [Funkcje](#funkcje) · [Stack](#stack) · [Instalacja](#instalacja) · [Deployment](#deployment)

![Wyjazdownik.pl](public/assets/img/og-image.png)

</div>

---

## Funkcje

- 📅 **Inteligentny kalendarz** — heatmapa terminów, automatycznie znajdzie dni gdzie wszyscy mogą
- 💰 **Wspólny budżet** — wykres słupkowy + algorytm "najsłabsze ogniwo"
- 🗺️ **Mapa pomysłów** — Leaflet + Leaflet.draw, każdy uczestnik ma własny kolor
- 🏆 **22 odznaki** — Kebab Master, Maszyna, Plażowicz, Górski Wilk... — algorytm przyznaje deterministycznie
- 🎯 **Auto-rekomendacje destynacji** — na podstawie kombinacji preferencji ekipy
- 📺 **Tryb prezentacji** — fullscreen z nawigacją klawiaturą, przygotowane pod TV
- 🔗 **Magic link auth** — bez haseł, tylko email
- 🌓 **Dark mode** — pełen, zapamiętany w localStorage
- 📱 **Mobile-first** — wszystkie ekrany działają na telefonie, tablecie i TV (1920+)

## Demo

| Strona główna | Wizard uczestnika | Podsumowanie |
|:-:|:-:|:-:|
| Landing z 9 sekcjami | 12 kroków, autosave AJAX | 15 sekcji + ranking + auto-rekomendacje |

Live: **[wyjazdownik.pl](https://wyjazdownik.pl)**

## Stack

- **Backend** — PHP 8.1+, własny mikro-framework (router, PSR-4 autoloader, prosty MVC)
- **Baza** — MySQL / MariaDB przez PDO + prepared statements
- **Frontend** — Tailwind CSS (via CDN), Vanilla JS, Alpine-style data attributes
- **Mapa** — Leaflet 1.7 + Leaflet.draw (OpenStreetMap, bez kluczy API)
- **Wykresy** — inline SVG (Chart.js opcjonalnie)
- **Email** — PHPMailer (driver `log` w dev, `smtp` w prod)
- **Composer** — autoloader + dependencies (`phpmailer/phpmailer`, `vlucas/phpdotenv`)

## Architektura

```
wyjazdownik/
├── public/                  # document root (Apache wskazuje tutaj)
│   ├── index.php            # front controller
│   ├── .htaccess            # rewrite + security headers + CSP + caching
│   └── assets/              # CSS, JS, img, uploads
├── src/                     # kod aplikacji (PSR-4: App\)
│   ├── Core/                # Router, Request, Response, Controller
│   ├── Controllers/         # 9 kontrolerów
│   ├── Models/              # Admin, Trip, Participant, MapPin
│   ├── Services/            # 13 serwisów (Auth, Mailer, Ranking, Recommendation, ...)
│   ├── Database/            # Connection.php (PDO singleton)
│   └── Helpers/             # Csrf, QuestionLabels, QuestionFormatter, Validator
├── views/                   # 80+ szablonów PHP (layout + partials per sekcja)
├── database/                # schema.sql + seed.sql + migrations/
├── config/                  # config.php (czyta .env)
├── cron/                    # cleanup.php (cron job)
├── docs/                    # deployment.md + Apache/PHP-FPM templates
├── storage/                 # logi (poza document root)
├── bootstrap.php            # autoloader + .env loader
├── install.php              # instalator pierwszego uruchomienia
├── deploy.sh                # rsync deployment
└── composer.json
```

## Instalacja (lokalnie, XAMPP)

**Wymagania**: XAMPP / Apache + PHP 8.1+ + MySQL/MariaDB + Composer + `mod_rewrite`

```bash
git clone https://github.com/vTomsonek/Wyjazdownik.git
cd Wyjazdownik

cp .env.example .env
nano .env   # ustaw DB_USER, DB_PASS, ADMIN_INITIAL_EMAIL

composer install

php install.php   # tworzy bazę, schema, seed (1 admin + 1 trip + 4 uczestników)
```

Otwórz `http://localhost/wyjazdownik/public/`.

Linki testowe (po seed):
- Wizard - Tomek: `/p/1111111111111111111111111111111111111111111111111111111111111111`
- Wizard - Kasia: `/p/2222222222222222222222222222222222222222222222222222222222222222`
- Wizard - Bartek: `/p/3333333333333333333333333333333333333333333333333333333333333333`
- Wizard - Ola: `/p/4444444444444444444444444444444444444444444444444444444444444444`
- Podsumowanie publiczne: `/summary/cafe0000cafe0000cafe0000cafe0000cafe0000cafe0000cafe0000cafe0000`

## Deployment

Pełna instrukcja krok-po-kroku Ubuntu + Apache + PHP-FPM + MariaDB + Cloudflare:
**[`docs/deployment.md`](docs/deployment.md)**

Skrót:
```bash
./deploy.sh                                          # rsync + composer install + permissions
ssh user@vps "cd /var/www/wyjazdownik && php install.php --seed=no"
sudo certbot --apache -d twoja-domena.pl              # HTTPS (lub Cloudflare flexible SSL)
```

## Bezpieczeństwo

- ✅ PDO prepared statements (zero konkatenacji SQL)
- ✅ CSRF tokens na każdym POST
- ✅ `bin2hex(random_bytes(32))` dla wszystkich tokenów (256 bit entropii)
- ✅ Rate limiting (3 próby logowania / 15 min / IP)
- ✅ Upload: whitelist MIME przez `finfo`, max size, losowe nazwy plików
- ✅ Headers: CSP, HSTS, X-Frame-Options, COOP, CORP, Permissions-Policy
- ✅ Magic link jednorazowy (`used_at`), 15 min TTL
- ✅ Auto-rejestracja przez magic link (atak: cudzy email → atakujący nie dostanie kodu)
- ✅ `httpOnly` + `samesite=Lax` + `secure` cookies
- ✅ Per-admin authorization (admin widzi tylko swoje wyjazdy)

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
