# Deployment Wyjazdownik.pl na Ubuntu (OVH dedicated)

Stack: **Apache 2.4 + PHP 8.1+ FPM + MariaDB 10.6+**

Kompletna instrukcja od pustego serwera do działającej produkcji. Zakłada że masz:
- VPS / dedykowany Ubuntu z `sudo`
- Domenę z DNS A wskazującym na IP serwera
- Lokalny SSH key dodany do serwera (`ssh-copy-id`)

---

## 1. Przygotowanie serwera (raz, na świeżym Ubuntu)

```bash
sudo apt update && sudo apt upgrade -y

# Apache + PHP-FPM + MariaDB + extensions
sudo apt install -y \
    apache2 \
    php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd \
    mariadb-server \
    composer \
    certbot python3-certbot-apache \
    rsync git

# Apache modules
sudo a2enmod rewrite headers expires deflate proxy_fcgi setenvif http2 ssl
sudo a2dismod php8.1   # uzywamy FPM, nie mod_php
sudo systemctl reload apache2
```

## 2. MariaDB - secure setup + baza

```bash
sudo mysql_secure_installation   # set root password, remove anonymous, disable remote root
```

Stwórz bazę i konto dla aplikacji:
```bash
sudo mysql -u root -p
```
```sql
CREATE DATABASE wyjazdownik CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'wyjazdownik'@'localhost' IDENTIFIED BY 'wymyśl_mocne_hasło';
GRANT ALL PRIVILEGES ON wyjazdownik.* TO 'wyjazdownik'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 3. Katalogi i permissions

```bash
sudo mkdir -p /var/www/wyjazdownik
sudo chown -R www-data:www-data /var/www/wyjazdownik

sudo mkdir -p /var/lib/php/sessions/wyjazdownik
sudo chown www-data:www-data /var/lib/php/sessions/wyjazdownik
sudo chmod 700 /var/lib/php/sessions/wyjazdownik

sudo mkdir -p /var/log/php-fpm
sudo chown www-data:www-data /var/log/php-fpm
```

## 4. PHP-FPM pool dla aplikacji

Z lokalnej maszyny (po deploy.sh) lub bezpośrednio na serwerze:

```bash
sudo cp /var/www/wyjazdownik/docs/php-fpm-pool.conf.example /etc/php/8.1/fpm/pool.d/wyjazdownik.conf
sudo systemctl reload php8.1-fpm
```

Sprawdź że socket działa:
```bash
ls -la /run/php/php8.1-fpm-wyjazdownik.sock
# powinno być: srw-rw---- 1 www-data www-data
```

## 5. Apache VirtualHost (faza HTTP-only)

```bash
sudo cp /var/www/wyjazdownik/docs/apache-vhost.conf.example /etc/apache2/sites-available/wyjazdownik.conf
# Edytuj jeśli musisz (ServerName, ścieżki):
sudo nano /etc/apache2/sites-available/wyjazdownik.conf

sudo a2ensite wyjazdownik
sudo a2dissite 000-default   # opcjonalnie, jeśli nie używasz default
sudo apache2ctl configtest    # test składni
sudo systemctl reload apache2
```

Otwórz `http://wyjazdownik.pl/` — powinieneś zobaczyć stronę (ale jeszcze bez bazy/seed).

## 6. Pierwszy deploy z lokalnej maszyny

Na lokalnym XAMPP:
```bash
# Edytuj deploy.sh - ustaw REMOTE_HOST, REMOTE_USER
cd /xampp/htdocs/wyjazdownik
./deploy.sh
```

Skrypt:
- rsynci wszystkie pliki PHP do `/var/www/wyjazdownik`
- uruchamia `composer install --no-dev --optimize-autoloader` na serwerze
- ustawia permissions (`www-data:www-data`)

## 7. Konfiguracja `.env` na produkcji

```bash
ssh root@vps... 
cd /var/www/wyjazdownik
cp .env.example .env
nano .env
```

Wpisz produkcyjne wartości:
```env
APP_ENV=prod
APP_NAME="Wyjazdownik"
APP_URL=https://wyjazdownik.pl
APP_TIMEZONE=Europe/Warsaw

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=wyjazdownik
DB_USER=wyjazdownik
DB_PASS=<hasło z punktu 2>

# Wybierz jednego providera SMTP - przykłady w .env.example
MAIL_DRIVER=smtp
MAIL_HOST=ssl0.ovh.net
MAIL_PORT=587
MAIL_USERNAME=noreply@wyjazdownik.pl
MAIL_PASSWORD=<hasło ze skrzynki OVH>
MAIL_FROM_ADDRESS=noreply@wyjazdownik.pl
MAIL_FROM_NAME="Wyjazdownik"

ADMIN_INITIAL_EMAIL=tomasz@jiko.pl
```

```bash
sudo chown www-data:www-data .env
sudo chmod 640 .env
```

## 8. Załaduj schema bazy (bez seed, na produkcji nie chcemy testowych danych)

```bash
cd /var/www/wyjazdownik
php install.php --seed=no
```

Zobaczysz `OK, wykonano 25 statementów (tabele utworzone).`

## 9. HTTPS (Let's Encrypt)

```bash
sudo certbot --apache -d wyjazdownik.pl -d www.wyjazdownik.pl
```

Certbot poprosi o email i zgody, potem automatycznie:
- Wygeneruje cert
- Dorzuci `<VirtualHost *:443>` z SSL do Apache
- Doda przekierowanie `:80 → :443`
- Skonfiguruje cron do auto-renew

Test renewal:
```bash
sudo certbot renew --dry-run
```

## 10. Cron - cleanup tabel

```bash
sudo crontab -e
```

Dodaj linię:
```
0 3 * * * /usr/bin/php /var/www/wyjazdownik/cron/cleanup.php >> /var/log/wyjazdownik-cleanup.log 2>&1
```

## 11. Verify

Otwórz `https://wyjazdownik.pl/` — powinieneś zobaczyć landing page z zielonym pluskiem HTTPS.

Test logowania:
1. `/admin/login` → wpisz email
2. Sprawdź skrzynkę — magic link powinien dojść (jeśli SMTP skonfigurowany dobrze)
3. Klij link → `/admin` jako zalogowany

Test bezpieczeństwa:
- DevTools → Network → Headers — powinieneś widzieć `Strict-Transport-Security`, `Content-Security-Policy`, `X-Frame-Options`
- `https://www.ssllabs.com/ssltest/analyze.html?d=wyjazdownik.pl` — powinno dać A albo A+

## 12. Backup (cron drugi)

Plus dorzuć backup bazy do crontab:
```
0 4 * * * mysqldump -u wyjazdownik -p'haslo' wyjazdownik | gzip > /var/backups/wyjazdownik-$(date +\%Y\%m\%d).sql.gz
0 5 * * 0 find /var/backups/wyjazdownik-*.sql.gz -mtime +30 -delete
```
(Backup codziennie, kasowanie >30 dni w niedzielę o 5:00.)

## 13. Aktualizacje

Następne deploy'e — z lokalnej maszyny:
```bash
./deploy.sh
```

Skrypt automatycznie:
- pushnie zmiany rsync'em
- odświeży `composer install` (jeśli composer.json się zmienił)
- ustawi permissions

`.env` na serwerze pozostaje nietknięty (`--exclude=.env` w deploy.sh).

---

## Troubleshooting

**Problem:** Strona główna ładuje się, ale `/admin/login` daje 500.
- Sprawdź `tail -50 /var/log/apache2/wyjazdownik-error.log` i `tail -50 /var/log/php-fpm/wyjazdownik-error.log`
- Często: brak `composer install` na serwerze.

**Problem:** Magic link nie przychodzi.
- `tail -50 /var/log/php-fpm/wyjazdownik-error.log` — szukaj PHPMailer errors
- `sudo -u www-data php /var/www/wyjazdownik/cron/cleanup.php` — test że PHP-FPM user ma dostęp
- Sprawdź `/var/www/wyjazdownik/storage/sent_emails.log` — w trybie `MAIL_DRIVER=log` mail trafia tu, NIE na skrzynkę

**Problem:** Upload bannera daje 500.
- `chown -R www-data:www-data /var/www/wyjazdownik/public/assets/uploads`
- `chmod -R 755 /var/www/wyjazdownik/public/assets/uploads`

**Problem:** CSP blokuje skrypty.
- DevTools → Console — zobacz który URL został zablokowany
- Edytuj `public/.htaccess` → `Content-Security-Policy` → dorzuć whitelist URL'a
